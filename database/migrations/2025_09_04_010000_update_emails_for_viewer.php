<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            if (!Schema::hasColumn('emails', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete()->after('account_id');
            }
            if (!Schema::hasColumn('emails', 'sender_email')) {
                $table->string('sender_email')->nullable()->after('from_email');
            }
            if (!Schema::hasColumn('emails', 'sender_name')) {
                $table->string('sender_name')->nullable()->after('sender_email');
            }
            if (!Schema::hasColumn('emails', 'recipients')) {
                $table->json('recipients')->nullable()->after('reply_to');
            }
            if (!Schema::hasColumn('emails', 'html_content')) {
                $table->longText('html_content')->nullable()->after('html_body');
            }
            if (!Schema::hasColumn('emails', 'text_content')) {
                $table->longText('text_content')->nullable()->after('text_body');
            }
            if (!Schema::hasColumn('emails', 'sent_date')) {
                $table->dateTime('sent_date')->nullable()->after('received_at');
            }
            if (!Schema::hasColumn('emails', 'status')) {
                $table->string('status')->default('completed')->after('sent_date');
            }
            if (!Schema::hasColumn('emails', 'file_path')) {
                $table->string('file_path')->nullable()->after('status');
            }
            if (!Schema::hasColumn('emails', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('emails', 'is_important')) {
                $table->boolean('is_important')->default(false)->after('file_size');
            }
            if (!Schema::hasColumn('emails', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('is_important');
            }
            if (!Schema::hasColumn('emails', 'tags')) {
                $table->string('tags')->nullable()->after('is_read');
            }
            if (!Schema::hasColumn('emails', 'notes')) {
                $table->text('notes')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('emails', 'last_accessed_at')) {
                $table->dateTime('last_accessed_at')->nullable()->after('updated_at');
            }

            $table->index(['user_id', 'sent_date']);
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            if (Schema::hasColumn('emails', 'last_accessed_at')) $table->dropColumn('last_accessed_at');
            if (Schema::hasColumn('emails', 'notes')) $table->dropColumn('notes');
            if (Schema::hasColumn('emails', 'tags')) $table->dropColumn('tags');
            if (Schema::hasColumn('emails', 'is_read')) $table->dropColumn('is_read');
            if (Schema::hasColumn('emails', 'is_important')) $table->dropColumn('is_important');
            if (Schema::hasColumn('emails', 'file_size')) $table->dropColumn('file_size');
            if (Schema::hasColumn('emails', 'file_path')) $table->dropColumn('file_path');
            if (Schema::hasColumn('emails', 'status')) $table->dropColumn('status');
            if (Schema::hasColumn('emails', 'sent_date')) $table->dropColumn('sent_date');
            if (Schema::hasColumn('emails', 'text_content')) $table->dropColumn('text_content');
            if (Schema::hasColumn('emails', 'html_content')) $table->dropColumn('html_content');
            if (Schema::hasColumn('emails', 'recipients')) $table->dropColumn('recipients');
            if (Schema::hasColumn('emails', 'sender_name')) $table->dropColumn('sender_name');
            if (Schema::hasColumn('emails', 'sender_email')) $table->dropColumn('sender_email');
            if (Schema::hasColumn('emails', 'user_id')) $table->dropConstrainedForeignId('user_id');
        });
    }
};


