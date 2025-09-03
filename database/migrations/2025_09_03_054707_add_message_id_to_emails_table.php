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
            $table->string('message_id')->nullable()->after('account_id');
            $table->unique(['account_id', 'message_id'], 'unique_account_message');
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropUnique('unique_account_message');
            $table->dropIndex(['message_id']);
            $table->dropColumn('message_id');
        });
    }
};
