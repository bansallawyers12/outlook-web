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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('email_accounts')->onDelete('cascade');
            $table->string('from_email')->nullable();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('folder')->default('Inbox');
            $table->dateTime('received_at')->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'folder']);
            $table->index(['account_id', 'subject']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};


