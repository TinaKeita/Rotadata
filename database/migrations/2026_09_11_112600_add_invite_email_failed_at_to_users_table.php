<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // atzīmē, ka uzaicinājuma e-pasts (ar pagaidu paroli) neizdevās nosūtīt,
        // lai skolotājs to redz un var vēlreiz nosūtīt, nevis paļauties uz vienreizējo paziņojumu ekrānā
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('invite_email_failed_at')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('invite_email_failed_at');
        });
    }
};
