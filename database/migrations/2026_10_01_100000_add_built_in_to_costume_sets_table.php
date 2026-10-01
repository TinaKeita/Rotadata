<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // iebūvētie komplekti, kas katrai grupai ir automātiski
    private const BUILT_IN = ['Girls', 'Boys'];

    public function up(): void
    {
        // iebūvētos komplektus ("Girls", "Boys") skolotājs nevar pārsaukt vai dzēst
        Schema::table('costume_sets', function (Blueprint $table) {
            $table->boolean('built_in')->default(false)->after('name');
        });

        // esošajām grupām pievieno iebūvētos komplektus; ja skolotājs tādu jau izveidojis, to tikai atzīmē
        foreach (DB::table('groups')->pluck('id') as $groupId) {
            foreach (self::BUILT_IN as $name) {
                $existing = DB::table('costume_sets')->where('group_id', $groupId)->where('name', $name)->first();

                if ($existing) {
                    DB::table('costume_sets')->where('id', $existing->id)->update(['built_in' => true]);
                } else {
                    DB::table('costume_sets')->insert([
                        'group_id' => $groupId,
                        'name' => $name,
                        'built_in' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('costume_sets', function (Blueprint $table) {
            $table->dropColumn('built_in');
        });
    }
};
