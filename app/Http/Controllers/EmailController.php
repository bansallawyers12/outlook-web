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
        $limit = min($request->get('limit', 50), 200); // Max 200 emails per sync
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

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

                $args = [
                    $pythonPath,
                    $script,
                    $account->provider,
                    $account->email,
                    $authToken,
                    $folder,
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
                    'folder' => $folder,
                    'limit' => $limit
                ]);

                $process = new Process($args, base_path());
                $process->setTimeout(120); // 2 minutes timeout for sync
                $process->run();

                // Capture both stdout and stderr
                $output = trim($process->getOutput());
                $errorOutput = trim($process->getErrorOutput());

                // Log debug information from stderr
                if (!empty($errorOutput)) {
                    Log::info("Python script debug output", ['debug_output' => $errorOutput]);
                }

                if (!$process->isSuccessful()) {
                    Log::error("Email sync failed via controller", [
                        'account_id' => $accountId,
                        'error_output' => $errorOutput,
                        'stdout' => $output,
                        'exit_code' => $process->getExitCode()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Sync failed: ' . ($errorOutput ?: $output),
                        'debug_output' => $errorOutput,
                        'emails' => []
                    ], 422);
                }

                // Parse the JSON response from Python script
                $syncedEmails = json_decode($output, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error("Invalid JSON from Python script via controller", [
                        'account_id' => $accountId,
                        'json_error' => json_last_error_msg(),
                        'output' => $output,
                        'debug_output' => $errorOutput
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid response from sync script: ' . json_last_error_msg(),
                        'debug_output' => $errorOutput,
                        'emails' => []
                    ], 422);
                }

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
                        // Create new email record
                        Email::create([
                            'account_id' => $accountId,
                            'message_id' => $emailData['message_id'],
                            'from_email' => $emailData['from'],
                            'subject' => $emailData['subject'],
                            'body' => $emailData['body'],
                            'folder' => $emailData['folder'],
                            'received_at' => $emailData['parsed_date'] ? 
                                \Carbon\Carbon::parse($emailData['parsed_date']) : null,
                            'date' => $emailData['parsed_date'] ? 
                                \Carbon\Carbon::parse($emailData['parsed_date']) : null,
                        ]);
                        $newEmailsCount++;
                    } else {
                        $duplicateEmailsCount++;
                    }
                }

                $message = "Successfully synced {$newEmailsCount} new emails from {$folder}";
                if ($duplicateEmailsCount > 0) {
                    $message .= " ({$duplicateEmailsCount} duplicates skipped)";
                }
                if ($startDate || $endDate) {
                    $dateRange = '';
                    if ($startDate && $endDate) {
                        $dateRange = " from {$startDate} to {$endDate}";
                    } elseif ($startDate) {
                        $dateRange = " from {$startDate}";
                    } elseif ($endDate) {
                        $dateRange = " until {$endDate}";
                    }
                    $message .= $dateRange;
                }
            }

            // Return emails from database
            $emails = Email::where('account_id', $accountId)
                ->where('folder', $folder)
                ->orderBy('received_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($email) {
                    return [
                        'id' => $email->id,
                        'from' => $email->from_email,
                        'subject' => $email->subject,
                        'time' => $email->received_at ? $email->received_at->format('H:i') : 'N/A',
                        'snippet' => $email->body ? substr(strip_tags($email->body), 0, 100) . '...' : 'No content',
                        'body' => $email->body
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



