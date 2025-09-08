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
        Schema::table('email_signatures', function (Blueprint $table) {
            $table->json('images')->nullable()->after('html_content');
            $table->string('template_type')->default('custom')->after('images');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_signatures', function (Blueprint $table) {
            $table->dropColumn(['images', 'template_type']);
        });
    }
};
