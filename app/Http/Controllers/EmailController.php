<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

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

        $pythonPath = 'python';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $pythonPath = 'python';
        }

        $script = base_path('send_mail.py');

        $args = [
            $pythonPath,
            $script,
            $account->provider,
            $account->email,
            $account->access_token,
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
}



