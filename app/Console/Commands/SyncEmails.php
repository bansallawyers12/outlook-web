<?php

namespace App\Console\Commands;

use App\Models\EmailAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

class SyncEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan emails:sync {id}
     */
    protected $signature = 'emails:sync {id : ID of the email_accounts row}';

    /**
     * The console command description.
     */
    protected $description = 'Sync emails for the given EmailAccount using the Python IMAP script';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $account = EmailAccount::findOrFail($id);

        $python = 'python'; // relies on python in PATH
        $script = base_path('sync_emails.py');

        $args = [
            $python,
            $script,
            (string) $account->provider,
            (string) $account->email,
            (string) $account->access_token,
        ];

        $process = new Process($args, base_path());
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Failed to run sync: ' . $process->getErrorOutput());
            return self::FAILURE;
        }

        $output = trim($process->getOutput());

        try {
            $emails = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->error('Invalid JSON from Python script: ' . $e->getMessage());
            $this->line($output);
            return self::FAILURE;
        }

        if (! is_array($emails)) {
            $this->error('Unexpected result from Python script.');
            return self::FAILURE;
        }

        foreach ($emails as $mail) {
            $from = $mail['from'] ?? null;
            $subject = $mail['subject'] ?? null;
            $date = $mail['date'] ?? null;

            DB::table('emails')->updateOrInsert(
                [
                    'account_id' => $account->id,
                    'subject' => $subject,
                ],
                [
                    'from_email' => $from,
                    'date' => $date,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->info("Synced emails for {$account->email}");
        return self::SUCCESS;
    }
}


