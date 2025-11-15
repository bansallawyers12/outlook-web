<?php

namespace App\Console\Commands;

use App\Models\EmailAccount;
use Illuminate\Console\Command;

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
    protected $description = 'Legacy sync command (Brevo inbound sync runs automatically)';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $account = EmailAccount::findOrFail($id);

        $this->warn('Brevo accounts sync automatically via inbound webhooks.');
        $this->info('No action taken. Confirm that /api/brevo/inbound is configured as the Brevo parse URL.');

        return self::SUCCESS;
    }
}


