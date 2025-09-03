<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->string('filename');
            $table->string('display_name')->nullable();
            $table->string('content_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('file_path')->nullable();
            $table->string('content_id')->nullable();
            $table->boolean('is_inline')->default(false);
            $table->string('description')->nullable();
            $table->json('headers')->nullable();
            $table->string('extension')->nullable();
            $table->timestamps();
            $table->index(['email_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};


