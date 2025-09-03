<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Models\Email;
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

        $args = [
            $pythonPath,
            $script,
            $account->provider,
            $account->email,
            $authToken,
            $validated['to'],
            $validated['subject'],
            $validated['body'],
        ];

        $process = new Process($args, base_path());
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'ok' => false,
                'error' => $process->getErrorOutput() ?: $process->getOutput(),
            ], 422);
        }

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
                $query->where(function ($q3) use ($like) {
                    $q3->where('subject', 'like', $like)
                        ->orWhere('from_email', 'like', $like)
                        ->orWhere('to_email', 'like', $like)
                        ->orWhere('cc', 'like', $like)
                        ->orWhere('reply_to', 'like', $like)
                        ->orWhere('text_body', 'like', $like)
                        ->orWhere('body', 'like', $like);
                });
            }

            // Return emails from database (select minimal columns for speed)
            $emails = $query
                ->orderBy('received_at', 'desc')
                ->limit($limit)
                ->get(['id', 'from_email', 'to_email', 'subject', 'received_at', 'date', 'created_at', 'body', 'text_body', 'html_body', 'cc', 'reply_to', 'headers'])
                ->map(function ($email) {
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
                        'has_attachment' => false, // TODO: implement attachment detection
                        'is_flagged' => false, // TODO: implement flag detection
                        'attachments' => []
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
}



