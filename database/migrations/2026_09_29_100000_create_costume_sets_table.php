<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tērpu komplekti (piem. "Meitenes", "Puiši") – tikai kārtošanai un koncerta gatavībai, skenēšanu neierobežo
        Schema::create('costume_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();

            $table->unique(['group_id', 'name']);
        });

        // tērps bez komplekta ir kopīgs – to vajag visiem
        Schema::table('costumes', function (Blueprint $table) {
            $table->foreignId('costume_set_id')->nullable()->after('group_id')
                ->constrained('costume_sets')->nullOnDelete();
        });

        // komplekts ir piesaistīts dalībai grupā, nevis kontam, jo students var būt vairākās grupās
        Schema::table('group_user', function (Blueprint $table) {
            $table->foreignId('costume_set_id')->nullable()->after('user_id')
                ->constrained('costume_sets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('costume_set_id');
        });

        Schema::table('costumes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('costume_set_id');
        });

        Schema::dropIfExists('costume_sets');
    }
};
