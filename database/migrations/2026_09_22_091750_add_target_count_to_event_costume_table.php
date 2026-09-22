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
        Schema::table('event_costume', function (Blueprint $table) {
            // cik studentiem šis tērps nepieciešams šim koncertam; null = visi grupas dalībnieki
            $table->unsignedInteger('target_count')->nullable()->after('costume_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_costume', function (Blueprint $table) {
            $table->dropColumn('target_count');
        });
    }
};
