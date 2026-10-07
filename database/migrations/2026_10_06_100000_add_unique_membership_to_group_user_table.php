<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // vispirms iztīra jau esošos dublikātus – paliek vecākais ieraksts (ar mazāko id)
        $keep = DB::table('group_user')
            ->selectRaw('MIN(id) as id')
            ->groupBy('group_id', 'user_id')
            ->pluck('id');

        DB::table('group_user')->whereNotIn('id', $keep)->delete();

        // viens un tas pats cilvēks grupā var būt tikai vienreiz – to garantē datubāze, ne tikai kods
        Schema::table('group_user', function (Blueprint $table) {
            $table->unique(['group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropUnique(['group_id', 'user_id']);
        });
    }
};
