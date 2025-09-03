<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountRequest;
use App\Http\Requests\UpdateEmailAccountRequest;
use App\Models\EmailAccount;
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

            Log::info('Email account created', [
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'email' => $account->email,
                'provider' => $account->provider
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
            $account->delete();

            Log::info('Email account deleted', [
                'user_id' => Auth::id(),
                'email' => $email
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
                
                return response()->json([
                    'success' => true,
                    'message' => 'Connection test completed',
                    'results' => $testResults
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection test failed',
                    'error' => $errorOutput ?: $output
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Connection test failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
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
