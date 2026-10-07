<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pagaidu parole der tikai ierobežotu laiku – pēc tam students prasa skolotājam jaunu uzaicinājuma saiti
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('temporary_password_expires_at')->nullable()->after('must_change_password');
        });

        // jau izsūtītajām pagaidu parolēm dod pilnu termiņu no šodienas, lai neviens netiek uzreiz izslēgts
        DB::table('users')
            ->where('must_change_password', true)
            ->update(['temporary_password_expires_at' => now()->addDays(7)]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('temporary_password_expires_at');
        });
    }
};
