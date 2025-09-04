<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_attachments', function (Blueprint $table) {
            $table->string('storage_path')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('email_attachments', function (Blueprint $table) {
            $table->dropColumn('storage_path');
        });
    }
};