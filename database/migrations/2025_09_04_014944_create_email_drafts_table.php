<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('email_accounts')->nullOnDelete();
            $table->string('to_email')->nullable();
            $table->string('cc_email')->nullable();
            $table->string('bcc_email')->nullable();
            $table->string('subject')->nullable();
            $table->longText('message')->nullable();
            $table->json('attachments')->nullable(); // Store attachment metadata
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_drafts');
    }
};