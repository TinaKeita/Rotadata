<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // grupas nodošana citam skolotājam: pieprasījums, ko saņēmējs pieņem vai noraida
        Schema::create('group_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            // nejaušs kods saitē e-pastā – saņēmējs to atver, būdams pieslēdzies savā kontā
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending'); // pending | accepted | declined | cancelled
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_transfers');
    }
};
