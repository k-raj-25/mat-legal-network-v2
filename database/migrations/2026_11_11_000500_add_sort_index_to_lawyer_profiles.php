<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Supports the "Newly joined" sort without a filesort on large tables. */
    public function up(): void
    {
        Schema::table('lawyer_profiles', function (Blueprint $table) {
            $table->index(['status', 'approved_at'], 'lawyer_profiles_status_approved_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('lawyer_profiles', function (Blueprint $table) {
            $table->dropIndex('lawyer_profiles_status_approved_at_index');
        });
    }
};
