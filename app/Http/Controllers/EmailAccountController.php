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
                'provider' => 'brevo',
                'email' => $request->email,
                'password' => encrypt($request->password),
                'access_token' => null,
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

        $account->password = '';

        return view('accounts.edit', [
            'account' => $account,
        ]);
    }

    /**
     * Update the specified email account.
     */
    public function update(UpdateEmailAccountRequest $request, EmailAccount $account)
    {
        $this->authorize('update', $account);

        try {
            $payload = [
                'provider' => 'brevo',
                'email' => $request->email,
                'access_token' => null,
                'refresh_token' => $request->refresh_token,
            ];

            if ($request->filled('password')) {
                $payload['password'] = encrypt($request->password);
            }

            $account->update($payload);

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
            $provider = strtolower((string) $account->provider);
            if ($provider !== 'brevo') {
                $errorMessage = 'Invalid or missing provider. Only Brevo is supported.';
                Log::warning('Provider rejected during testConnection', [
                    'account_id' => $account->id,
                    'provider' => $account->provider,
                ]);
                $account->update([
                    'connection_status' => false,
                    'last_connection_error' => $errorMessage,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $errorMessage,
                ], 422);
            }

            $host = config('services.brevo.smtp_host', 'smtp-relay.brevo.com');
            $port = (int) config('services.brevo.smtp_port', 587);

            $results = [
                'dns' => false,
                'socket' => false,
                'tls' => false,
                'host' => $host,
                'port' => $port,
            ];

            try {
                if (function_exists('dns_get_record')) {
                    $records = @dns_get_record($host, DNS_A + DNS_AAAA);
                    $results['dns'] = !empty($records);
                } else {
                    $results['dns'] = @checkdnsrr($host, 'A');
                }
            } catch (\Throwable $dnsException) {
                Log::warning('Brevo DNS check failed', [
                    'host' => $host,
                    'error' => $dnsException->getMessage(),
                ]);
            }

            $socketError = null;
            try {
                $stream = @fsockopen($host, $port, $errno, $errstr, 10);
                if ($stream) {
                    $results['socket'] = true;
                    stream_set_timeout($stream, 10);

                    @fgets($stream, 512); // banner
                    fwrite($stream, "EHLO outlook-web\r\n");
                    $this->consumeSmtpResponse($stream);
                    fwrite($stream, "STARTTLS\r\n");
                    $startTlsResponse = fgets($stream, 512);
                    if ($startTlsResponse !== false && str_starts_with(trim($startTlsResponse), '220')) {
                        $cryptoMethod = defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')
                            ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                            : STREAM_CRYPTO_METHOD_TLS_CLIENT;
                        $results['tls'] = @stream_socket_enable_crypto($stream, true, $cryptoMethod) === true;
                    }
                    fclose($stream);
                } else {
                    $socketError = $errstr ?: 'Unable to open socket to Brevo SMTP relay.';
                }
            } catch (\Throwable $socketException) {
                $socketError = $socketException->getMessage();
                Log::warning('Brevo SMTP probe failed', [
                    'host' => $host,
                    'port' => $port,
                    'error' => $socketException->getMessage(),
                ]);
            }

            $isHealthy = $results['dns'] && $results['socket'];

            $account->update([
                'connection_status' => $isHealthy,
                'last_connection_error' => $isHealthy ? null : ($socketError ?: 'Unable to reach Brevo SMTP relay.'),
            ]);

            if ($isHealthy) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully connected to Brevo SMTP relay.',
                    'results' => $results,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed.',
                'error' => $socketError ?: 'DNS or socket check failed.',
                'results' => $results,
            ], 422);
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
            // Validate provider before proceeding
            $provider = $account->provider ? strtolower($account->provider) : null;
            $allowedProviders = ['brevo'];
            if (empty($provider) || !in_array($provider, $allowedProviders, true)) {
                $errorMessage = 'Invalid or missing provider. Only Brevo is supported.';
                Log::warning('Provider rejected during testAuthentication', [
                    'account_id' => $account->id,
                    'provider' => $account->provider
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $errorMessage
                ], 422);
            }

            $python = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'py' : 'python3';
            $script = base_path('send_mail.py');

            $apiKey = $account->access_token;
            if (!$apiKey && $account->password) {
                try {
                    $apiKey = decrypt($account->password);
                } catch (\Throwable $decryptException) {
                    $apiKey = $account->password;
                }
            }

            if (empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Brevo SMTP key is configured for this account.',
                ], 422);
            }

            $smtpUser = config('services.brevo.smtp_user', 'apikey');

            $args = [
                $python,
                $script,
                strtolower(trim($account->provider)),
                $smtpUser,
                $apiKey,
                $account->email,
                'Brevo SMTP authentication test',
                'This is a credential verification initiated from Outlook Web.',
                '',
                '',
                json_encode([]),
                $account->email,
                '--dry-run',
            ];

            $process = new Process($args, base_path());
            $process->setTimeout(30);
            $env = [
                'PATH' => getenv('PATH'),
                'SYSTEMROOT' => getenv('SYSTEMROOT'),
                'WINDIR' => getenv('WINDIR'),
                'TEMP' => getenv('TEMP'),
                'TMP' => getenv('TMP'),
                'BREVO_SMTP_HOST' => config('services.brevo.smtp_host', 'smtp-relay.brevo.com'),
                'BREVO_SMTP_PORT' => (string) config('services.brevo.smtp_port', 587),
            ];
            $process->setEnv($env);
            $process->run();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if ($process->isSuccessful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Brevo API key authenticated successfully (no email was sent).',
                    'output' => $output,
                ]);
            }

            Log::error('Brevo authentication test failed', [
                'account_id' => $account->id,
                'exit_code' => $process->getExitCode(),
                'stdout' => $output,
                'stderr' => $errorOutput,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication test failed.',
                'error' => $errorOutput ?: $output,
            ], 422);
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
     * Consume a multi-line SMTP response until a terminating line is read.
     *
     * @param resource|null $stream
     */
    private function consumeSmtpResponse($stream): void
    {
        if (!$stream) {
            return;
        }

        while (($line = fgets($stream, 512)) !== false) {
            if (strlen($line) < 4) {
                break;
            }

            if ($line[3] !== '-') {
                break;
            }
        }
    }
}

