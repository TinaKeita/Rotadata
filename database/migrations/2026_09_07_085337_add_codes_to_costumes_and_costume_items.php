<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('costumes', function (Blueprint $table) {
            $table->string('code_prefix', 12)->nullable()->after('name');
        });

        Schema::table('costume_items', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('qr_code');
        });

        // aizpilda kodus esošajiem ierakstiem
        $usedPrefixes = [];

        foreach (DB::table('costumes')->orderBy('id')->get() as $costume) {
            $base = strtoupper(preg_replace('/[^A-Za-z]/', '', Str::ascii($costume->name)));
            $base = substr($base, 0, 3) ?: 'ITM';

            $prefix = $base;
            $suffix = 2;
            while (in_array($prefix, $usedPrefixes[$costume->group_id] ?? [], true)) {
                $prefix = $base.$suffix++;
            }
            $usedPrefixes[$costume->group_id][] = $prefix;

            DB::table('costumes')->where('id', $costume->id)->update(['code_prefix' => $prefix]);

            $seq = 1;
            foreach (DB::table('costume_items')->where('costume_id', $costume->id)->orderBy('id')->get() as $item) {
                DB::table('costume_items')
                    ->where('id', $item->id)
                    ->update(['code' => sprintf('%s-%02d', $prefix, $seq++)]);
            }
        }

        Schema::table('costume_items', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('costume_items', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::table('costumes', function (Blueprint $table) {
            $table->dropColumn('code_prefix');
        });
    }
};
