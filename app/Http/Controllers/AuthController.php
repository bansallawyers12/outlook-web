<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        if ($provider !== 'zoho') {
            abort(404);
        }

        $clientId = config('services.zoho.client_id');
        $redirectUri = config('services.zoho.redirect');
        
        if (!$clientId) {
            return redirect('/dashboard')->with('error', 'Zoho OAuth not configured. Please check your environment variables.');
        }

        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $authUrl = 'https://accounts.zoho.com/oauth/v2/auth?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'ZohoMail.messages.READ,ZohoMail.messages.CREATE,ZohoMail.accounts.READ',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);

        return redirect($authUrl);
    }

    public function callback(string $provider): RedirectResponse
    {
        if ($provider !== 'zoho') {
            abort(404);
        }

        $code = request('code');
        $state = request('state');
        $error = request('error');

        if ($error) {
            return redirect('/dashboard')->with('error', 'OAuth error: ' . $error);
        }

        if (!$code || !$state) {
            return redirect('/dashboard')->with('error', 'Missing authorization code or state.');
        }

        if ($state !== session('oauth_state')) {
            return redirect('/dashboard')->with('error', 'Invalid state parameter.');
        }

        $clientId = config('services.zoho.client_id');
        $clientSecret = config('services.zoho.client_secret');
        $redirectUri = config('services.zoho.redirect');

        if (!$clientId || !$clientSecret) {
            return redirect('/dashboard')->with('error', 'Zoho OAuth not properly configured.');
        }

        // Exchange code for tokens
        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            return redirect('/dashboard')->with('error', 'Failed to exchange code for tokens: ' . $response->body());
        }

        $tokenData = $response->json();

        if (!isset($tokenData['access_token'])) {
            return redirect('/dashboard')->with('error', 'No access token received from Zoho.');
        }

        // Get user's email from Zoho API
        $userResponse = Http::withToken($tokenData['access_token'])
            ->get('https://mail.zoho.com/api/accounts');

        $userEmail = null;
        if ($userResponse->successful()) {
            $userData = $userResponse->json();
            if (isset($userData['data'][0]['accountId'])) {
                $userEmail = $userData['data'][0]['accountId'];
            }
        }

        if (!$userEmail) {
            return redirect('/dashboard')->with('error', 'Could not retrieve email address from Zoho.');
        }

        // Store the email account
        EmailAccount::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'provider' => 'zoho',
                'email' => $userEmail,
            ],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
            ]
        );

        session()->forget('oauth_state');

        return redirect('/dashboard')->with('success', 'Zoho account connected successfully!');
    }

    public function addZohoAccount()
    {
        $request = request();
        
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'remember' => 'boolean'
        ]);

        try {
            // For now, we'll store the credentials directly
            // In a production environment, you should encrypt the password
            $emailAccount = EmailAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => 'zoho',
                    'email' => $validated['email'],
                ],
                [
                    'password' => $validated['remember'] ? encrypt($validated['password']) : null,
                    'access_token' => null, // Will be set when we implement IMAP/SMTP
                    'refresh_token' => null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Zoho account added successfully!',
                'account' => [
                    'id' => $emailAccount->id,
                    'email' => $emailAccount->email,
                    'provider' => $emailAccount->provider
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add Zoho account: ' . $e->getMessage()
            ], 500);
        }
    }
}


