<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // notikuša koncerta gatavības "fotogrāfija": dalībnieki, viņu komplekti, tērpi un kas bija rokās
        // koncerta dienā – lai vēlākas izmaiņas grupā nepārrakstītu sezonas atskaiti
        Schema::table('events', function (Blueprint $table) {
            $table->json('readiness_snapshot')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('readiness_snapshot');
        });
    }
};
