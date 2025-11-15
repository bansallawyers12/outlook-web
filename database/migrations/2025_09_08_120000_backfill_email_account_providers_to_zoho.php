<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guard for environments where the column may not exist yet (SQLite safety)
        if (!Schema::hasColumn('email_accounts', 'provider')) {
            return; // Nothing to backfill
        }

        // Backfill any NULL provider
        DB::table('email_accounts')
            ->whereNull('provider')
            ->update(['provider' => 'brevo']);

        // Backfill empty string provider
        DB::table('email_accounts')
            ->where('provider', '=','')
            ->update(['provider' => 'brevo']);

        // Backfill any non-brevo literal values (case-sensitive here; normalization handled at model/controller)
        DB::table('email_accounts')
            ->where('provider', '!=', 'brevo')
            ->update(['provider' => 'brevo']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: cannot reliably restore previous provider values
    }
};


