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
            if (!Schema::hasColumn('emails', 'is_flagged')) {
                $table->boolean('is_flagged')->default(false)->after('is_read');
            }
            $table->index(['is_read', 'received_at']);
            $table->index(['is_flagged', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex(['is_read', 'received_at']);
            $table->dropIndex(['is_flagged', 'received_at']);
            if (Schema::hasColumn('emails', 'is_flagged')) {
                $table->dropColumn('is_flagged');
            }
        });
    }
};
