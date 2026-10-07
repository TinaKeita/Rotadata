<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // daudzums vienmēr ir reālais vienību skaits costume_items tabulā – atsevišķa kopija varēja tam neatbilst
        Schema::table('costumes', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('costumes', function (Blueprint $table) {
            $table->integer('quantity')->default(1);
        });

        // atjauno vērtību no aktīvajām vienībām
        DB::table('costumes')->update([
            'quantity' => DB::raw('(select count(*) from costume_items where costume_items.costume_id = costumes.id and costume_items.deleted_at is null)'),
        ]);
    }
};
