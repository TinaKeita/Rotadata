<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // izdzēsts tērps vai vienība pazūd no inventāra, bet tās izsniegšanas vēsture paliek
        // (cietā dzēšana caur ārējo atslēgu iztīrītu arī costume_item_assignments ierakstus)
        Schema::table('costumes', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('costume_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('costume_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('costumes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
