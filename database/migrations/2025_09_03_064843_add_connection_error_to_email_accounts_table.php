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
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->text('last_connection_error')->nullable()->after('refresh_token');
            $table->timestamp('last_connection_attempt')->nullable()->after('last_connection_error');
            $table->boolean('connection_status')->default(false)->after('last_connection_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['last_connection_error', 'last_connection_attempt', 'connection_status']);
        });
    }
};
