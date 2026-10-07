<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // vajadzīgo skaitu vairs neievada ar roku – to nosaka koncerta dalībnieki un viņu komplekti
        Schema::table('event_costume', function (Blueprint $table) {
            $table->dropColumn('target_count');
        });
    }

    public function down(): void
    {
        Schema::table('event_costume', function (Blueprint $table) {
            $table->unsignedInteger('target_count')->nullable()->after('costume_id');
        });
    }
};
