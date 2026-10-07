<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // esošu kontu skolotājs grupai nepievieno uzreiz – lietotājs saņem uzaicinājumu un pats to pieņem vai noraida
        Schema::create('group_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            // komplekts, kuru skolotājs izvēlējās pievienojot – piešķir, kad uzaicinājums pieņemts
            $table->foreignId('costume_set_id')->nullable()->constrained('costume_sets')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending | accepted | declined | cancelled
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // viens ieraksts katram cilvēkam katrā grupā – atkārtots uzaicinājums atjauno esošo
            $table->unique(['group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_invitations');
    }
};
