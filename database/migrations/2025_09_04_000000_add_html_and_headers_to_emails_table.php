<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->longText('html_body')->nullable()->after('body');
            $table->text('text_body')->nullable()->after('html_body');
            $table->string('to_email')->nullable()->after('from_email');
            $table->string('cc')->nullable()->after('to_email');
            $table->string('reply_to')->nullable()->after('cc');
            $table->json('headers')->nullable()->after('reply_to');
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['html_body', 'text_body', 'to_email', 'cc', 'reply_to', 'headers']);
        });
    }
};


