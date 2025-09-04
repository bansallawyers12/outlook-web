<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Models\Email;
use App\Models\EmailDraft;
use App\Services\EmailFolderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        return view('emails.compose', compact('account', 'accountId', 'to', 'cc', 'bcc', 'subject', 'body'));
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
            $account->provider,
            $account->email,
            $authToken,
            $validated['to'],
            $validated['subject'],
            $validated['body'],
            $validated['cc'] ?? '',
            $validated['bcc'] ?? '',
            json_encode($attachmentPaths),
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
        $this->storeSentEmail($request, Auth::id(), $account->id);

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

        $folder = $request->get('folder', 'Inbox');
        $limit = min($request->get('limit', 50), 200); // Max 200 emails per sync/list
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $q = trim((string) $request->get('q', ''));
        $searchFields = $request->get('search_fields', 'from,to,subject,body');
        $hasAttachments = $request->get('has_attachments', false);
        $isUnread = $request->get('is_unread', false);
        $isFlagged = $request->get('is_flagged', false);

        try {
            if ($request->isMethod('post')) {
                // Perform actual sync using Python script
                $pythonPath = 'py'; // Use py command for Windows Python launcher
                if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                    $pythonPath = 'python3'; // Use python3 on Unix-like systems
                }

                $script = base_path('sync_emails.py');
                
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

                // Determine folders to sync
                $foldersToSync = [];
                if (strtolower($folder) === 'all') {
                    $foldersToSync = ['Inbox', 'Sent', 'Drafts', 'Trash', 'Spam'];
                } else {
                    $foldersToSync = [$folder];
                }

                $allSyncedEmails = [];
                $debugOutputs = [];

                foreach ($foldersToSync as $folderName) {
                    $args = [
                        $pythonPath,
                        $script,
                        $account->provider,
                        $account->email,
                        $authToken,
                        $folderName,
                        $limit
                    ];

                    // Add date range parameters if provided
                    if ($startDate) {
                        $args[] = $startDate;
                    }
                    if ($endDate) {
                        $args[] = $endDate;
                    }

                    // Log sync attempt
                    Log::info("Starting email sync via controller", [
                        'account_id' => $accountId,
                        'email' => $account->email,
                        'provider' => $account->provider,
                        'folder' => $folderName,
                        'limit' => $limit
                    ]);

                    $process = new Process($args, base_path());
                    $process->setTimeout(120); // 2 minutes timeout for sync
                    
                    // Set environment variables to help with DNS resolution
                    $env = [
                        'PATH' => getenv('PATH'),
                        'SYSTEMROOT' => getenv('SYSTEMROOT'),
                        'WINDIR' => getenv('WINDIR'),
                        'TEMP' => getenv('TEMP'),
                        'TMP' => getenv('TMP'),
                        'PYTHONPATH' => getenv('PYTHONPATH'),
                        'PYTHONIOENCODING' => 'utf-8',
                    ];
                    
                    // Add DNS-related environment variables if available
                    if (getenv('DNS_SERVERS')) {
                        $env['DNS_SERVERS'] = getenv('DNS_SERVERS');
                    }
                    
                    $process->setEnv($env);
                    $process->run();

                    // Capture both stdout and stderr
                    $output = trim($process->getOutput());
                    $errorOutput = trim($process->getErrorOutput());
                    if (!empty($errorOutput)) {
                        $debugOutputs[] = $errorOutput;
                    }

                    if (!$process->isSuccessful()) {
                        Log::error("Email sync failed via controller", [
                            'account_id' => $accountId,
                            'folder' => $folderName,
                            'error_output' => $errorOutput,
                            'stdout' => $output,
                            'exit_code' => $process->getExitCode()
                        ]);
                        continue; // Continue with other folders instead of failing all
                    }

                    // Parse the JSON response from Python script
                    $synced = json_decode($output, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error("Invalid JSON from Python script via controller", [
                            'account_id' => $accountId,
                            'folder' => $folderName,
                            'json_error' => json_last_error_msg(),
                            'output' => $output,
                        ]);
                        continue;
                    }

                    if (isset($synced['error'])) {
                        Log::error("Email sync error via controller", [
                            'account_id' => $accountId,
                            'folder' => $folderName,
                            'error' => $synced['error'],
                            'debug_info' => $synced['debug_info'] ?? null
                        ]);
                        continue;
                    }

                    if (is_array($synced)) {
                        $allSyncedEmails = array_merge($allSyncedEmails, $synced);
                    }
                }

                $syncedEmails = $allSyncedEmails;

                if (isset($syncedEmails['error'])) {
                    Log::error("Email sync error via controller", [
                        'account_id' => $accountId,
                        'error' => $syncedEmails['error'],
                        'debug_info' => $syncedEmails['debug_info'] ?? null
                    ]);
                    
                    $response = [
                        'success' => false,
                        'message' => 'Sync error: ' . $syncedEmails['error'],
                        'emails' => []
                    ];
                    
                    // Include debug information if available
                    if (isset($syncedEmails['debug_info'])) {
                        $response['debug_info'] = $syncedEmails['debug_info'];
                    }
                    
                    return response()->json($response, 422);
                }

                // Process and store emails, preventing duplicates
                $newEmailsCount = 0;
                $duplicateEmailsCount = 0;
                $folderService = new EmailFolderService();

                foreach ($syncedEmails as $emailData) {
                    // Check if email already exists using message_id
                    $existingEmail = Email::where('account_id', $accountId)
                        ->where('message_id', $emailData['message_id'])
                        ->first();

                    if (!$existingEmail) {
                        // Parse common dates
                        $parsedDate = !empty($emailData['parsed_date']) ? \Carbon\Carbon::parse($emailData['parsed_date']) : null;

                        // Build recipients array from available fields
                        $recipients = [];
                        if (!empty($emailData['to'])) {
                            $recipients = is_array($emailData['to']) ? $emailData['to'] : [$emailData['to']];
                        }
                        if (!empty($emailData['cc'])) {
                            $ccList = is_array($emailData['cc']) ? $emailData['cc'] : [$emailData['cc']];
                            $recipients = array_values(array_filter(array_merge($recipients, $ccList)));
                        }

                        // Create new email record matching viewer expectations
                        $email = Email::create([
                            'account_id' => $accountId,
                            'user_id' => $account->user_id,
                            'message_id' => $emailData['message_id'] ?? null,
                            'from_email' => $emailData['from'] ?? null,
                            'sender_email' => $emailData['from'] ?? null,
                            'sender_name' => $emailData['from_name'] ?? ($emailData['from_display'] ?? null),
                            'to_email' => $emailData['to'] ?? null,
                            'cc' => $emailData['cc'] ?? null,
                            'reply_to' => $emailData['reply_to'] ?? null,
                            'recipients' => $recipients ?: null,
                            'subject' => $emailData['subject'] ?? null,
                            'body' => $emailData['body'] ?? null,
                            'html_body' => $emailData['html_body'] ?? null,
                            'text_body' => $emailData['text_body'] ?? null,
                            'html_content' => $emailData['html_body'] ?? null,
                            'text_content' => $emailData['text_body'] ?? ($emailData['body'] ?? null),
                            'headers' => $emailData['headers'] ?? null,
                            'folder' => $emailData['folder'] ?? 'Inbox',
                            'received_at' => $parsedDate,
                            'sent_date' => $parsedDate,
                            'date' => $parsedDate,
                            'status' => 'completed',
                            'file_path' => $emailData['file_path'] ?? null,
                            'file_size' => $emailData['file_size'] ?? null,
                            'is_important' => false,
                            'is_read' => false,
                        ]);

                        // Save email content to local storage
                        if (!empty($emailData['message_id']) && !empty($emailData['folder'])) {
                            $emailContent = $this->buildEmailContent($emailData);
                            $saved = $folderService->saveEmailToFile(
                                $account, 
                                $emailData['folder'], 
                                $emailData['message_id'], 
                                $emailContent
                            );
                            
                            if ($saved) {
                                Log::info('Email saved to local storage', [
                                    'account_id' => $accountId,
                                    'email_id' => $email->id,
                                    'message_id' => $emailData['message_id'],
                                    'folder' => $emailData['folder']
                                ]);
                            }
                        }

                        // Persist attachments if provided by the sync
                        if (!empty($emailData['attachments']) && is_array($emailData['attachments'])) {
                            foreach ($emailData['attachments'] as $att) {
                                try {
                                    $email->attachments()->create([
                                        'filename' => $att['filename'] ?? 'attachment',
                                        'display_name' => $att['display_name'] ?? ($att['filename'] ?? 'attachment'),
                                        'content_type' => $att['content_type'] ?? null,
                                        'file_size' => $att['file_size'] ?? 0,
                                        'file_path' => $att['file_path'] ?? null,
                                        'content_id' => $att['content_id'] ?? null,
                                        'is_inline' => !empty($att['is_inline']),
                                        'headers' => $att['headers'] ?? null,
                                        'extension' => $att['extension'] ?? null,
                                    ]);
                                } catch (\Throwable $t) {
                                    // Skip bad attachment rows without failing the whole sync
                                    Log::warning('Attachment persistence failed', [
                                        'email_id' => $email->id,
                                        'error' => $t->getMessage(),
                                    ]);
                                }
                            }
                        }

                        // Apply a system label for the folder (Inbox/Sent/etc.) if labels exist
                        // Optional: you can pre-seed system labels per user; here we upsert minimal
                        try {
                            if (!empty($emailData['folder'])) {
                                $labelName = ucfirst(strtolower($emailData['folder']));
                                $label = \App\Models\Label::firstOrCreate(
                                    ['user_id' => $account->user_id, 'name' => $labelName],
                                    ['type' => 'system', 'color' => '#6B7280']
                                );
                                $email->labels()->syncWithoutDetaching([$label->id]);
                            }
                        } catch (\Throwable $t) {
                            Log::warning('Label assignment failed', [
                                'email_id' => $email->id,
                                'error' => $t->getMessage(),
                            ]);
                        }
                        $newEmailsCount++;
                    } else {
                        $duplicateEmailsCount++;
                    }
                }

                // Build a friendly, well‑pluralized message with nicer dates
                $emailWord = $newEmailsCount === 1 ? 'email' : 'emails';
                $duplicateWord = $duplicateEmailsCount === 1 ? 'duplicate' : 'duplicates';

                if ($newEmailsCount === 0) {
                    $message = "You're all caught up — no new emails in {$folder}.";
                } else {
                    $message = "Synced {$newEmailsCount} new {$emailWord} from {$folder}.";
                }

                if ($duplicateEmailsCount > 0) {
                    $message .= " ({$duplicateEmailsCount} {$duplicateWord} skipped)";
                }

                if ($startDate || $endDate) {
                    $startFormatted = $startDate ? \Carbon\Carbon::parse($startDate)->format('M j, Y') : null;
                    $endFormatted = $endDate ? \Carbon\Carbon::parse($endDate)->format('M j, Y') : null;

                    if ($startFormatted && $endFormatted) {
                        $message .= " for {$startFormatted} – {$endFormatted}";
                    } elseif ($startFormatted) {
                        $message .= " since {$startFormatted}";
                    } elseif ($endFormatted) {
                        $message .= " up to {$endFormatted}";
                    }
                }
            }

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
                'message' => $message ?? 'Emails loaded successfully',
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
    private function storeSentEmail(Request $request, int $userId, int $accountId)
    {
        try {
            // Create a mail message record
            $email = Email::create([
                'user_id' => $userId,
                'account_id' => $accountId,
                'subject' => $request->input('subject'),
                'from_email' => $request->input('from_email'),
                'sender_email' => $request->input('from_email'),
                'sender_name' => Auth::user()->name,
                'to_email' => $request->input('to'),
                'recipients' => [$request->input('to')],
                'text_body' => $request->input('body'),
                'html_body' => nl2br($request->input('body')),
                'sent_date' => now(),
                'status' => 'sent',
                'folder' => 'sent',
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
}



