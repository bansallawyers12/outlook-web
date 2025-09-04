<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountRequest;
use App\Http\Requests\UpdateEmailAccountRequest;
use App\Models\EmailAccount;
use App\Services\EmailFolderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class EmailAccountController extends Controller
{
    /**
     * Display a listing of email accounts.
     */
    public function index()
    {
        $accounts = EmailAccount::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new email account.
     */
    public function create()
    {
        return view('accounts.create');
    }

    /**
     * Store a newly created email account.
     */
    public function store(StoreEmailAccountRequest $request)
    {
        try {
            $account = EmailAccount::create([
                'user_id' => Auth::id(),
                'provider' => $request->provider,
                'email' => $request->email,
                'password' => encrypt($request->password),
                'access_token' => $request->access_token,
                'refresh_token' => $request->refresh_token,
            ]);

            // Create local folder structure for the email account
            $folderService = new EmailFolderService();
            $folderCreated = $folderService->createAccountFolders($account);

            if (!$folderCreated) {
                Log::warning('Failed to create local folders for email account', [
                    'user_id' => Auth::id(),
                    'account_id' => $account->id,
                    'email' => $account->email,
                    'provider' => $account->provider
                ]);
            }

            Log::info('Email account created', [
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'email' => $account->email,
                'provider' => $account->provider,
                'folders_created' => $folderCreated
            ]);

            return redirect()->route('accounts.index')
                ->with('success', 'Email account created successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to create email account', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create email account. Please try again.');
        }
    }

    /**
     * Display the specified email account.
     */
    public function show(EmailAccount $account)
    {
        $this->authorize('view', $account);

        return view('accounts.show', compact('account'));
    }

    /**
     * Show the form for editing the specified email account.
     */
    public function edit(EmailAccount $account)
    {
        $this->authorize('update', $account);

        // Decrypt password for editing
        $account->password = $account->password ? decrypt($account->password) : '';

        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified email account.
     */
    public function update(UpdateEmailAccountRequest $request, EmailAccount $account)
    {
        $this->authorize('update', $account);

        try {
            $account->update([
                'provider' => $request->provider,
                'email' => $request->email,
                'password' => encrypt($request->password),
                'access_token' => $request->access_token,
                'refresh_token' => $request->refresh_token,
            ]);

            Log::info('Email account updated', [
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'email' => $account->email,
                'provider' => $account->provider
            ]);

            return redirect()->route('accounts.index')
                ->with('success', 'Email account updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update email account', [
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to update email account. Please try again.');
        }
    }

    /**
     * Remove the specified email account.
     */
    public function destroy(EmailAccount $account)
    {
        $this->authorize('delete', $account);

        try {
            $email = $account->email;
            
            // Delete local folder structure for the email account
            $folderService = new EmailFolderService();
            $foldersDeleted = $folderService->deleteAccountFolders($account);

            if (!$foldersDeleted) {
                Log::warning('Failed to delete local folders for email account', [
                    'user_id' => Auth::id(),
                    'account_id' => $account->id,
                    'email' => $email
                ]);
            }

            $account->delete();

            Log::info('Email account deleted', [
                'user_id' => Auth::id(),
                'email' => $email,
                'folders_deleted' => $foldersDeleted
            ]);

            return redirect()->route('accounts.index')
                ->with('success', 'Email account deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete email account', [
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to delete email account. Please try again.');
        }
    }

    /**
     * Test the connection for the specified email account.
     */
    public function testConnection(EmailAccount $account)
    {
        $this->authorize('view', $account);

        // Update last connection attempt timestamp
        $account->update(['last_connection_attempt' => now()]);

        try {
            $python = 'py'; // Use py command for Windows Python launcher
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $python = 'python3'; // Use python3 on Unix-like systems
            }

            $script = base_path('test_network.py');
            $args = [$python, $script, $this->getImapHost($account->provider), '993'];

            $process = new Process($args, base_path());
            $process->setTimeout(30);
            $process->run();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if ($process->isSuccessful()) {
                // Parse the test results
                $testResults = $this->parseNetworkTestResults($output);
                
                // Check if all tests passed
                $allPassed = true;
                $errorDetails = [];
                
                foreach ($testResults as $test => $result) {
                    if (!$result) {
                        $allPassed = false;
                        $errorDetails[] = ucfirst($test) . ' test failed';
                    }
                }
                
                if ($allPassed) {
                    // Update account with success
                    $account->update([
                        'connection_status' => true,
                        'last_connection_error' => null
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Connection test completed successfully',
                        'results' => $testResults
                    ]);
                } else {
                    // Update account with failure details
                    $errorMessage = implode(', ', $errorDetails);
                    $account->update([
                        'connection_status' => false,
                        'last_connection_error' => $errorMessage
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Connection test failed: ' . $errorMessage,
                        'error' => $errorMessage,
                        'results' => $testResults
                    ], 422);
                }
            } else {
                // Update account with failure
                $errorMessage = $errorOutput ?: $output ?: 'Unknown connection error';
                $account->update([
                    'connection_status' => false,
                    'last_connection_error' => $errorMessage
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Connection test failed: ' . $errorMessage,
                    'error' => $errorMessage
                ], 422);
            }
        } catch (\Exception $e) {
            // Update account with failure
            $account->update([
                'connection_status' => false,
                'last_connection_error' => $e->getMessage()
            ]);
            
            Log::error('Connection test failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test authentication for the specified email account.
     */
    public function testAuthentication(EmailAccount $account)
    {
        $this->authorize('view', $account);

        try {
            $python = 'py';
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $python = 'python3';
            }

            $script = base_path('sync_emails.py');

            // Get the correct authentication token/password
            $authToken = $account->access_token;
            if (!$authToken && $account->password) {
                try {
                    $authToken = decrypt($account->password);
                } catch (\Exception $e) {
                    $authToken = $account->password;
                }
            }

            $args = [
                $python,
                $script,
                $account->provider,
                $account->email,
                $authToken,
                'inbox',
                '1' // Test with just 1 email
            ];

            $process = new Process($args, base_path());
            $process->setTimeout(30);
            $process->run();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if ($process->isSuccessful()) {
                $result = json_decode($output, true);
                
                if (isset($result['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication failed: ' . $result['error'],
                        'debug_info' => $result['debug_info'] ?? null
                    ], 422);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Authentication successful! Found ' . count($result) . ' emails.',
                        'email_count' => count($result)
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication test failed',
                    'error' => $errorOutput ?: $output
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Authentication test failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get IMAP host for the provider.
     */
    private function getImapHost(string $provider): string
    {
        return match ($provider) {
            'zoho' => 'imap.zoho.com',
            'gmail' => 'imap.gmail.com',
            'outlook' => 'outlook.office365.com',
            default => 'imap.zoho.com'
        };
    }

    /**
     * Parse network test results from output.
     */
    private function parseNetworkTestResults(string $output): array
    {
        // Try to parse JSON output first (new format)
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if this line contains JSON
            if (strpos($line, '{') === 0 && strpos($line, '}') !== false) {
                $jsonData = json_decode($line, true);
                if ($jsonData && is_array($jsonData)) {
                    return $jsonData;
                }
            }
        }
        
        // Fallback to old text parsing method
        $results = [
            'dns' => false,
            'socket' => false,
            'ssl' => false,
            'imap' => false,
            'overall' => false
        ];

        if (strpos($output, 'DNS: ✓ PASS') !== false) $results['dns'] = true;
        if (strpos($output, 'SOCKET: ✓ PASS') !== false) $results['socket'] = true;
        if (strpos($output, 'SSL: ✓ PASS') !== false) $results['ssl'] = true;
        if (strpos($output, 'IMAP: ✓ PASS') !== false) $results['imap'] = true;
        if (strpos($output, '✓ ALL TESTS PASSED') !== false) $results['overall'] = true;

        return $results;
    }
}
