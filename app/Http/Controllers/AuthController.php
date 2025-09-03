<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\EmailAccount;

class AuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        if ($provider !== 'zoho') {
            abort(404);
        }

        // Zoho OAuth not implemented yet. Prevent hitting Google Socialite.
        return redirect('/dashboard')->with('status', 'Zoho account connection will be available soon.');
    }

    public function callback(string $provider): RedirectResponse
    {
        if ($provider !== 'zoho') {
            abort(404);
        }

        // Placeholder: Zoho OAuth callback handling will be implemented later.
        return redirect('/dashboard')->with('status', 'Zoho OAuth callback not implemented yet.');
    }
}


