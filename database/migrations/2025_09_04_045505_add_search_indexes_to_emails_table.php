<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            // Add indexes for better search performance (check if they exist first)
            if (!Schema::hasIndex('emails', ['account_id', 'folder', 'received_at'])) {
                $table->index(['account_id', 'folder', 'received_at']);
            }
            if (!Schema::hasIndex('emails', ['account_id', 'is_read', 'received_at'])) {
                $table->index(['account_id', 'is_read', 'received_at']);
            }
            if (!Schema::hasIndex('emails', ['account_id', 'is_flagged', 'received_at'])) {
                $table->index(['account_id', 'is_flagged', 'received_at']);
            }
            if (!Schema::hasIndex('emails', ['account_id', 'from_email'])) {
                $table->index(['account_id', 'from_email']);
            }
            if (!Schema::hasIndex('emails', ['account_id', 'to_email'])) {
                $table->index(['account_id', 'to_email']);
            }
            if (!Schema::hasIndex('emails', ['account_id', 'subject'])) {
                $table->index(['account_id', 'subject']);
            }
            
            // Full-text search indexes for better text searching
            if (!Schema::hasIndex('emails', 'emails_subject_fulltext')) {
                $table->index(['subject'], 'emails_subject_fulltext');
            }
            if (!Schema::hasIndex('emails', 'emails_from_fulltext')) {
                $table->index(['from_email'], 'emails_from_fulltext');
            }
            if (!Schema::hasIndex('emails', 'emails_to_fulltext')) {
                $table->index(['to_email'], 'emails_to_fulltext');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'folder', 'received_at']);
            $table->dropIndex(['account_id', 'is_read', 'received_at']);
            $table->dropIndex(['account_id', 'is_flagged', 'received_at']);
            $table->dropIndex(['account_id', 'from_email']);
            $table->dropIndex(['account_id', 'to_email']);
            $table->dropIndex(['account_id', 'subject']);
            $table->dropIndex('emails_subject_fulltext');
            $table->dropIndex('emails_from_fulltext');
            $table->dropIndex('emails_to_fulltext');
        });
    }
};
