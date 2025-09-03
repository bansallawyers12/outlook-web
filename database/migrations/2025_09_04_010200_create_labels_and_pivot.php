<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#3B82F6');
            $table->string('type')->default('custom');
            $table->string('icon')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::create('email_label', function (Blueprint $table) {
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('labels')->cascadeOnDelete();
            $table->primary(['email_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_label');
        Schema::dropIfExists('labels');
    }
};


