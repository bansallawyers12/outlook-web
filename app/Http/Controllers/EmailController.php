<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Models\Email;
use App\Models\EmailDraft;
use App\Models\EmailSignature;
use App\Services\EmailFolderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    public function compose(Request $request)
    {
        $accountId = (int) $request->query('account_id');
        $to = (string) $request->query('to', '');
        $cc = (string) $request->query('cc', '');
        $bcc = (string) $request->query('bcc', '');
        $subject = (string) $request->query('subject', '');
        $body = (string) $request->query('body', '');

        // Ensure the account belongs to the user if provided
        $account = null;
        if ($accountId) {
            $account = EmailAccount::where('id', $accountId)
                ->where('user_id', $request->user()->id)
                ->first();
        }

        // Get signatures for the selected account or all accounts
        $signatures = EmailSignature::forUser($request->user()->id)
            ->where(function ($query) use ($accountId) {
                $query->where('account_id', $accountId)
                      ->orWhereNull('account_id');
            })
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('emails.compose', compact('account', 'accountId', 'to', 'cc', 'bcc', 'subject', 'body', 'signatures'));
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'exists:email_accounts,id'],
            'to' => ['required', 'email'],
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:10240'], // Max 10MB per file
        ]);

        $account = EmailAccount::where('id', $validated['account_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Validate provider before proceeding
        $provider = $account->provider ? strtolower(trim($account->provider)) : null;
        $allowedProviders = ['brevo'];
        if (empty($provider) || !in_array($provider, $allowedProviders, true)) {
            Log::warning('Provider rejected during send', [
                'account_id' => $account->id,
                'provider' => $account->provider
            ]);
            return response()->json([
                'ok' => false,
                'error' => 'Invalid or missing provider. Only Brevo is supported.',
            ], 422);
        }

        $pythonPath = 'py'; // Use py command for Windows Python launcher
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $pythonPath = 'python3'; // Use python3 on Unix-like systems
        }

        $script = base_path('send_mail.py');

        // Get the correct authentication token/password
        $authToken = $account->access_token;
        if (!$authToken && $account->password) {
            // Decrypt the password if it's encrypted
            try {
                $authToken = decrypt($account->password);
            } catch (\Exception $e) {
                // If decryption fails, use the password as-is (might be plain text)
                $authToken = $account->password;
            }
        }

        if (empty($authToken)) {
            return response()->json([
                'ok' => false,
                'error' => 'Brevo SMTP key is not configured for this account.',
            ], 422);
        }

        $smtpUser = config('services.brevo.smtp_user', 'apikey');

        // Handle attachments
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            $attachments = $request->file('attachments');
            foreach ($attachments as $attachment) {
                if ($attachment->isValid()) {
                    $filename = $attachment->getClientOriginalName();
                    $path = $attachment->store('temp/attachments', 'local');
                    $attachmentPaths[] = [
                        'path' => storage_path('app/private/' . $path),
                        'filename' => $filename,
                        'mime_type' => $attachment->getMimeType(),
                    ];
                }
            }
        }

        $args = [
            $pythonPath,
            $script,
            strtolower(trim($account->provider)),
            $smtpUser,
            $authToken,
            $validated['to'],
            $validated['subject'],
            $validated['body'],
            $validated['cc'] ?? '',
            $validated['bcc'] ?? '',
            json_encode($attachmentPaths),
            $account->email,
        ];

        $process = new Process($args, base_path());
        $process->setTimeout(30);
        
        // Set environment variables for better Windows compatibility
        $env = [
            'PATH' => getenv('PATH'),
            'SYSTEMROOT' => getenv('SYSTEMROOT'),
            'WINDIR' => getenv('WINDIR'),
            'TEMP' => getenv('TEMP'),
            'TMP' => getenv('TMP'),
            'PYTHONPATH' => getenv('PYTHONPATH'),
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONUNBUFFERED' => '1',
            'BREVO_SMTP_HOST' => config('services.brevo.smtp_host', 'smtp-relay.brevo.com'),
            'BREVO_SMTP_PORT' => (string) config('services.brevo.smtp_port', 587),
        ];
        
        // Add Windows-specific environment variables
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $env['COMSPEC'] = getenv('COMSPEC');
            $env['PATHEXT'] = getenv('PATHEXT');
            $env['PROCESSOR_ARCHITECTURE'] = getenv('PROCESSOR_ARCHITECTURE');
            $env['PROCESSOR_IDENTIFIER'] = getenv('PROCESSOR_IDENTIFIER');
        }
        
        $process->setEnv($env);
        $process->run();

        // Log the process details for debugging
        Log::info('Email sending process completed', [
            'account_id' => $account->id,
            'account_email' => $account->email,
            'provider' => $account->provider,
            'to' => $validated['to'],
            'subject' => $validated['subject'],
            'is_successful' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
            'has_attachments' => !empty($attachmentPaths),
            'attachment_count' => count($attachmentPaths)
        ]);

        // Clean up temporary attachment files AFTER processing is complete
        foreach ($attachmentPaths as $attachment) {
            if (file_exists($attachment['path'])) {
                unlink($attachment['path']);
            }
        }

        if (!$process->isSuccessful()) {
            $errorMessage = $process->getErrorOutput() ?: $process->getOutput();
            
            // If no error message, provide a more descriptive one
            if (empty(trim($errorMessage))) {
                $errorMessage = "Email sending failed with exit code {$process->getExitCode()}. No error details available.";
            }
            
            Log::error('Email sending failed', [
                'account_id' => $account->id,
                'account_email' => $account->email,
                'provider' => $account->provider,
                'to' => $validated['to'],
                'subject' => $validated['subject'],
                'exit_code' => $process->getExitCode(),
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput(),
                'error_message' => $errorMessage
            ]);
            
            return response()->json([
                'ok' => false,
                'error' => $errorMessage,
            ], 422);
        }

        // Store sent email in database for tracking
        $request->merge(['from_email' => $account->email]);
        $this->storeSentEmail($request, Auth::id(), $account);

        return response()->json([
            'ok' => true,
            'output' => $process->getOutput(),
        ]);
    }

        public function sync($accountId, Request $request)
    {
        $account = EmailAccount::where('id', $accountId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Validate provider before proceeding
        $provider = $account->provider ? strtolower(trim($account->provider)) : null;
        $allowedProviders = ['brevo'];
        if (empty($provider) || !in_array($provider, $allowedProviders, true)) {
            Log::warning('Provider rejected during sync', [
                'account_id' => $account->id,
                'provider' => $account->provider
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing provider. Only Brevo is supported.',
            ], 422);
        }

        $folder = $request->get('folder', 'Inbox');
        // Validate limit strictly (1–200). Reject out-of-range instead of silently clamping.
        $rawLimit = $request->get('limit', 50);
        $limit = (int) $rawLimit;
        if ($limit < 1 || $limit > 200) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid limit. Please choose a value between 1 and 200.',
            ], 422);
        }
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        // Enforce maximum 5-day inclusive range
        if (!empty($startDate) && !empty($endDate)) {
            try {
                $sd = new \DateTime($startDate);
                $ed = new \DateTime($endDate);
                if ($sd > $ed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Start date cannot be after end date.',
                    ], 422);
                }
                $diff = $sd->diff($ed)->days + 1; // inclusive
                if ($diff > 5) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected range is too large. Please choose a range within 5 days.',
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range provided.',
                ], 422);
            }
        }
        $q = trim((string) $request->get('q', ''));
        $searchFields = $request->get('search_fields', 'from,to,subject,body');
        $hasAttachments = $request->get('has_attachments', false);
        $isUnread = $request->get('is_unread', false);
        $isFlagged = $request->get('is_flagged', false);

        try {
            $message = $request->isMethod('post')
                ? 'Brevo sync runs automatically via inbound webhooks. Showing the latest stored emails.'
                : 'Emails loaded successfully';

            // Build listing query with optional folder, dates, and search
            $query = Email::where('account_id', $accountId);

            if (strtolower($folder) !== 'all') {
                $query->where('folder', $folder);
            }

            if ($startDate) {
                $query->where(function ($q2) use ($startDate) {
                    $q2->whereDate('received_at', '>=', $startDate)
                        ->orWhereDate('date', '>=', $startDate);
                });
            }
            if ($endDate) {
                $query->where(function ($q2) use ($endDate) {
                    $q2->whereDate('received_at', '<=', $endDate)
                        ->orWhereDate('date', '<=', $endDate);
                });
            }

            if (!empty($q)) {
                $like = '%' . str_replace(['%','_'], ['\\%','\\_'], $q) . '%';
                $searchFieldsArray = explode(',', $searchFields);
                
                $query->where(function ($q3) use ($like, $searchFieldsArray) {
                    if (in_array('subject', $searchFieldsArray)) {
                        $q3->orWhere('subject', 'like', $like);
                    }
                    if (in_array('from', $searchFieldsArray)) {
                        $q3->orWhere('from_email', 'like', $like);
                    }
                    if (in_array('to', $searchFieldsArray)) {
                        $q3->orWhere('to_email', 'like', $like);
                    }
                    if (in_array('cc', $searchFieldsArray)) {
                        $q3->orWhere('cc', 'like', $like);
                    }
                    if (in_array('reply_to', $searchFieldsArray)) {
                        $q3->orWhere('reply_to', 'like', $like);
                    }
                    if (in_array('body', $searchFieldsArray)) {
                        $q3->orWhere('text_body', 'like', $like)
                           ->orWhere('body', 'like', $like);
                    }
                });
            }
            
            // Apply additional filters
            if ($hasAttachments) {
                $query->whereHas('attachments');
            }
            
            if ($isUnread) {
                // For now, we'll assume all emails are unread if this filter is applied
                // TODO: Add read/unread status to emails table
                $query->where('is_read', false);
            }
            
            if ($isFlagged) {
                // For now, we'll assume all emails are unflagged if this filter is applied
                // TODO: Add flagged status to emails table
                $query->where('is_flagged', true);
            }

            // Return emails from database with attachments
            $emails = $query
                ->with('attachments')
                ->orderBy('received_at', 'desc')
                ->limit($limit)
                ->get(['id', 'from_email', 'to_email', 'subject', 'received_at', 'date', 'created_at', 'body', 'text_body', 'html_body', 'cc', 'reply_to', 'headers', 'is_read', 'is_flagged'])
                ->map(function ($email) {
                    $attachments = $email->attachments->map(function ($attachment) {
                        return [
                            'id' => $attachment->id,
                            'filename' => $attachment->filename,
                            'display_name' => $attachment->display_name,
                            'content_type' => $attachment->content_type,
                            'file_size' => $attachment->file_size,
                            'formatted_file_size' => $attachment->formatted_file_size,
                            'extension' => $attachment->extension,
                            'is_inline' => $attachment->is_inline,
                            'can_preview' => $attachment->canPreview(),
                            'preview_type' => $attachment->getPreviewType(),
                        ];
                    });

                    return [
                        'id' => $email->id,
                        'from' => $email->from_email,
                        'to' => $email->to_email,
                        'subject' => $email->subject,
                        'date' => $email->received_at ? $email->received_at->toISOString() : ($email->date ? $email->date->toISOString() : null),
                        'received_at' => $email->received_at ? $email->received_at->toISOString() : null,
                        'created_at' => $email->created_at ? $email->created_at->toISOString() : null,
                        'snippet' => $email->body ? substr(strip_tags($email->body), 0, 100) . '...' : 'No content',
                        'body' => $email->text_body ?? $email->body,
                        'html_body' => $email->html_body,
                        'cc' => $email->cc,
                        'reply_to' => $email->reply_to,
                        'headers' => $email->headers,
                        'has_attachment' => $attachments->count() > 0,
                        'is_read' => $email->is_read ?? false,
                        'is_flagged' => $email->is_flagged ?? false,
                        'attachments' => $attachments->toArray()
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => $message,
                'emails' => $emails
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'emails' => []
            ], 500);
        }
    }

    /**
     * Handle bulk actions on emails
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:mark_read,mark_unread,flag,unflag,delete'],
            'email_ids' => ['required', 'array'],
            'email_ids.*' => ['integer', 'exists:emails,id']
        ]);

        $emailIds = $validated['email_ids'];
        $action = $validated['action'];

        // Verify all emails belong to the authenticated user
        $emails = Email::whereIn('id', $emailIds)
            ->whereHas('emailAccount', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->get();

        if ($emails->count() !== count($emailIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some emails not found or access denied.'
            ], 403);
        }

        try {
            switch ($action) {
                case 'mark_read':
                    Email::whereIn('id', $emailIds)->update(['is_read' => true]);
                    break;
                case 'mark_unread':
                    Email::whereIn('id', $emailIds)->update(['is_read' => false]);
                    break;
                case 'flag':
                    Email::whereIn('id', $emailIds)->update(['is_flagged' => true]);
                    break;
                case 'unflag':
                    Email::whereIn('id', $emailIds)->update(['is_flagged' => false]);
                    break;
                case 'delete':
                    Email::whereIn('id', $emailIds)->delete();
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk action completed successfully.',
                'affected_count' => $emails->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save email as draft
     */
    public function saveDraft(Request $request)
    {
        $validated = $request->validate([
            'account_id' => ['nullable', 'integer', 'exists:email_accounts,id'],
            'to' => ['nullable', 'email'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
        ]);

        try {
            $userId = Auth::id();
            
            // Store draft in database
            $draft = EmailDraft::create([
                'user_id' => $userId,
                'account_id' => $validated['account_id'] ?? null,
                'to_email' => $validated['to'] ?? null,
                'cc_email' => $validated['cc'] ?? null,
                'bcc_email' => $validated['bcc'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'message' => $validated['body'] ?? null,
                'attachments' => $validated['attachments'] ?? [],
            ]);

            Log::info('Email draft saved', [
                'draft_id' => $draft->id,
                'user_id' => $userId,
                'subject' => $validated['subject'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft saved successfully!',
                'draft_id' => $draft->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save draft', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save draft: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's email drafts
     */
    public function drafts()
    {
        $userId = Auth::id();
        
        $drafts = EmailDraft::forUser($userId)
            ->with('emailAccount')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'drafts' => $drafts->items(),
            'pagination' => [
                'current_page' => $drafts->currentPage(),
                'last_page' => $drafts->lastPage(),
                'per_page' => $drafts->perPage(),
                'total' => $drafts->total(),
            ]
        ]);
    }

    /**
     * Get draft data for editing
     */
    public function getDraft(int $id)
    {
        $userId = Auth::id();
        $draft = EmailDraft::where('user_id', $userId)->findOrFail($id);

        return response()->json([
            'success' => true,
            'draft' => $draft
        ]);
    }

    /**
     * Delete a draft
     */
    public function deleteDraft(int $id)
    {
        $userId = Auth::id();
        $draft = EmailDraft::where('user_id', $userId)->findOrFail($id);
        $draft->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted successfully'
        ]);
    }

    /**
     * Get reply data for an email
     */
    public function getReplyData(int $id)
    {
        $userId = Auth::id();
        $email = Email::where('user_id', $userId)->findOrFail($id);

        return response()->json([
            'success' => true,
            'subject' => 'Re: ' . ($email->subject ?: '(No subject)'),
            'to_email' => $email->from_email,
            'sender_name' => $email->sender_name,
            'original_message' => $email->text_body ?: $email->body,
            'original_date' => $email->received_at ? $email->received_at->format('Y-m-d H:i') : '',
        ]);
    }

    /**
     * Store sent email in database for tracking
     */
    private function storeSentEmail(Request $request, int $userId, EmailAccount $account)
    {
        try {
            // Create a mail message record
            $email = Email::create([
                'user_id' => $userId,
                'account_id' => $account->id,
                'subject' => $request->input('subject'),
                'from_email' => $request->input('from_email', $account->email),
                'sender_email' => $request->input('from_email', $account->email),
                'sender_name' => Auth::user()->name,
                'to_email' => $request->input('to'),
                'recipients' => [$request->input('to')],
                'text_body' => $request->input('body'),
                'html_body' => nl2br($request->input('body')),
                'sent_date' => now(),
                'status' => 'sent',
                'folder' => 'Sent',
            ]);

            // Store attachments if any
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('email-attachments', 'public');
                    
                    $email->attachments()->create([
                        'filename' => $attachment->getClientOriginalName(),
                        'display_name' => $attachment->getClientOriginalName(),
                        'storage_path' => $path,
                        'content_type' => $attachment->getMimeType(),
                        'file_size' => $attachment->getSize(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::warning('Failed to store sent email', [
                'error' => $e->getMessage(),
                'mail_data' => $request->all(),
            ]);
        }
    }

    /**
     * Get email content from EML file stored in S3
     */
    public function getEmailContent(int $id)
    {
        $email = Email::where('id', $id)
            ->whereHas('emailAccount', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with('emailAccount')
            ->firstOrFail();

        $folderService = new EmailFolderService();
        
        // Try to get content from EML file first
        $emlContent = $folderService->getEmailFromFile(
            $email->emailAccount, 
            $email->folder, 
            $email->message_id
        );

        if ($emlContent) {
            // Parse EML content to extract body
            $parsedContent = $this->parseEmlContent($emlContent);
            
            return response()->json([
                'success' => true,
                'content' => $parsedContent,
                'source' => 'eml'
            ]);
        }

        // Fallback to database content
        return response()->json([
            'success' => true,
            'content' => [
                'text_body' => $email->text_body,
                'html_body' => $email->html_body,
                'body' => $email->body,
                'headers' => $email->headers
            ],
            'source' => 'database'
        ]);
    }

    /**
     * Parse EML content to extract body and headers
     */
    private function parseEmlContent(string $emlContent): array
    {
        // Split headers and body
        $parts = explode("\r\n\r\n", $emlContent, 2);
        $headers = $parts[0] ?? '';
        $body = $parts[1] ?? '';

        // Parse headers
        $parsedHeaders = [];
        $headerLines = explode("\r\n", $headers);
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $parsedHeaders[trim($key)] = trim($value);
            }
        }

        // Check if body is multipart
        $contentType = $parsedHeaders['Content-Type'] ?? '';
        $isMultipart = strpos($contentType, 'multipart/') === 0;

        if ($isMultipart) {
            // Extract boundary
            preg_match('/boundary="([^"]+)"/', $contentType, $matches);
            $boundary = $matches[1] ?? '--boundary';
            
            // Parse multipart content
            $multipartParts = explode('--' . $boundary, $body);
            $textBody = '';
            $htmlBody = '';
            
            foreach ($multipartParts as $part) {
                if (strpos($part, 'Content-Type: text/plain') !== false) {
                    $textPart = explode("\r\n\r\n", $part, 2);
                    $textBody = trim($textPart[1] ?? '');
                } elseif (strpos($part, 'Content-Type: text/html') !== false) {
                    $htmlPart = explode("\r\n\r\n", $part, 2);
                    $htmlBody = trim($htmlPart[1] ?? '');
                }
            }
            
            return [
                'text_body' => $textBody,
                'html_body' => $htmlBody,
                'body' => $textBody ?: $htmlBody,
                'headers' => $parsedHeaders
            ];
        } else {
            // Single part content
            $isHtml = strpos($contentType, 'text/html') !== false;
            
            return [
                'text_body' => $isHtml ? '' : $body,
                'html_body' => $isHtml ? $body : '',
                'body' => $body,
                'headers' => $parsedHeaders
            ];
        }
    }

    /**
     * Build email content in RFC 2822 format for local storage
     */
    private function buildEmailContent(array $emailData): string
    {
        $headers = [];
        
        // Basic headers
        if (!empty($emailData['message_id'])) {
            $headers[] = 'Message-ID: ' . $emailData['message_id'];
        }
        
        if (!empty($emailData['from'])) {
            $fromName = !empty($emailData['from_name']) ? $emailData['from_name'] : '';
            $fromEmail = $emailData['from'];
            if ($fromName) {
                $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
            } else {
                $headers[] = 'From: ' . $fromEmail;
            }
        }
        
        if (!empty($emailData['to'])) {
            $headers[] = 'To: ' . $emailData['to'];
        }
        
        if (!empty($emailData['cc'])) {
            $headers[] = 'Cc: ' . $emailData['cc'];
        }
        
        if (!empty($emailData['reply_to'])) {
            $headers[] = 'Reply-To: ' . $emailData['reply_to'];
        }
        
        if (!empty($emailData['subject'])) {
            $headers[] = 'Subject: ' . $emailData['subject'];
        }
        
        if (!empty($emailData['date'])) {
            $headers[] = 'Date: ' . $emailData['date'];
        } elseif (!empty($emailData['parsed_date'])) {
            $headers[] = 'Date: ' . $emailData['parsed_date'];
        }
        
        // Add custom headers if available
        if (!empty($emailData['headers']) && is_array($emailData['headers'])) {
            foreach ($emailData['headers'] as $key => $value) {
                if (!in_array(strtolower($key), ['message-id', 'from', 'to', 'cc', 'reply-to', 'subject', 'date'])) {
                    $headers[] = $key . ': ' . $value;
                }
            }
        }
        
        // Content-Type header
        $hasHtml = !empty($emailData['html_body']);
        $hasText = !empty($emailData['text_body']) || !empty($emailData['body']);
        
        if ($hasHtml && $hasText) {
            $boundary = 'boundary_' . uniqid();
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        } elseif ($hasHtml) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        // Build the email content
        $content = implode("\r\n", $headers) . "\r\n\r\n";
        
        if ($hasHtml && $hasText) {
            // Multipart email
            $content .= "--" . $boundary . "\r\n";
            $content .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $content .= ($emailData['text_body'] ?? $emailData['body'] ?? '') . "\r\n\r\n";
            
            $content .= "--" . $boundary . "\r\n";
            $content .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $content .= ($emailData['html_body'] ?? '') . "\r\n\r\n";
            
            $content .= "--" . $boundary . "--\r\n";
        } elseif ($hasHtml) {
            $content .= $emailData['html_body'];
        } else {
            $content .= ($emailData['text_body'] ?? $emailData['body'] ?? '');
        }
        
        return $content;
    }

    /**
     * Return a temporary S3 URL for the EML of the given email
     */
    public function getEmailEmlUrl(int $id)
    {
        $email = Email::where('id', $id)
            ->whereHas('emailAccount', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with('emailAccount')
            ->firstOrFail();

        $folderService = new EmailFolderService();
        $relativePath = $folderService->getEmailFilePath(
            $email->emailAccount,
            $email->folder,
            $email->message_id
        );

        if (!Storage::disk('s3-emails')->exists($relativePath)) {
            return response()->json([
                'success' => false,
                'message' => 'EML file not found in storage',
            ], 404);
        }

        $expiresInMinutes = 15;
        $url = Storage::disk('s3-emails')->temporaryUrl($relativePath, now()->addMinutes($expiresInMinutes));

        return response()->json([
            'success' => true,
            'url' => $url,
            'expires_in_minutes' => $expiresInMinutes,
            'path' => $relativePath,
        ]);
    }
}



