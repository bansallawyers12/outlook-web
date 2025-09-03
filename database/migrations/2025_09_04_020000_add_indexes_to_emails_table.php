<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            // Composite index to speed up listing by account + folder + received_at desc
            $table->index(['account_id', 'folder', 'received_at'], 'emails_account_folder_received_idx');

            // Unique index to prevent duplicates by account/message_id
            $table->unique(['account_id', 'message_id'], 'emails_account_message_unique');

            // Helpful narrower index for account + received_at filters
            $table->index(['account_id', 'received_at'], 'emails_account_received_idx');
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex('emails_account_folder_received_idx');
            $table->dropUnique('emails_account_message_unique');
            $table->dropIndex('emails_account_received_idx');
        });
    }
};


