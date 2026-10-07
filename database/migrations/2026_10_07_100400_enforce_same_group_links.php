<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datubāze pati garantē, ka saites nepārkāpj grupas robežas (ne tikai kontrolieru pārbaudes):
 * tērpa, dalības un uzaicinājuma komplekts ir no tās pašas grupas, un koncertam piesaistītie
 * tērpi (arī studentu papildu tērpi) ir no koncerta grupas. To dara saliktās ārējās atslēgas (id + group_id).
 */
return new class extends Migration
{
    // tabulas, kurām ir komplekts un grupa
    private const SET_TABLES = ['costumes', 'group_user', 'group_invitations'];

    // koncerta tabulas, kurām pievieno group_id
    private const EVENT_TABLES = ['event_costume', 'event_student_costumes'];

    public function up(): void
    {
        // jau esošās neatbilstības tiek iztīrītas, citādi atslēgas nevarētu izveidot
        foreach (self::SET_TABLES as $table) {
            DB::table($table)
                ->whereNotNull('costume_set_id')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('costume_sets')
                    ->whereColumn('costume_sets.id', "{$table}.costume_set_id")
                    ->whereColumn('costume_sets.group_id', "{$table}.group_id"))
                ->update(['costume_set_id' => null]);
        }

        // unikālās atslēgas, uz kurām atsaucas saliktās ārējās atslēgas
        foreach (['costume_sets', 'costumes', 'events'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->unique(['id', 'group_id'], "{$table}_id_group_unique"));
        }

        // komplekts tikai no tās pašas grupas. Bez "on delete" darbības – komplekta dzēšana pati vispirms noņem atsauces
        foreach (self::SET_TABLES as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->foreign(['costume_set_id', 'group_id'], "{$table}_set_same_group_fk")
                    ->references(['id', 'group_id'])->on('costume_sets');
            });
        }

        // koncerta tērpu saitēm group_id ņem no koncerta; svešas grupas tērpu saites tiek izdzēstas
        foreach (self::EVENT_TABLES as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('group_id')->nullable()->after('event_id'));

            DB::table($table)->update([
                'group_id' => DB::raw("(select events.group_id from events where events.id = {$table}.event_id)"),
            ]);

            DB::table($table)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('costumes')
                    ->whereColumn('costumes.id', "{$table}.costume_id")
                    ->whereColumn('costumes.group_id', "{$table}.group_id"))
                ->delete();

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unsignedBigInteger('group_id')->nullable(false)->change();

                $t->foreign(['event_id', 'group_id'], "{$table}_event_group_fk")
                    ->references(['id', 'group_id'])->on('events')->cascadeOnDelete();
                $t->foreign(['costume_id', 'group_id'], "{$table}_costume_group_fk")
                    ->references(['id', 'group_id'])->on('costumes')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::EVENT_TABLES as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign("{$table}_event_group_fk");
                $t->dropForeign("{$table}_costume_group_fk");
            });

            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('group_id'));
        }

        foreach (self::SET_TABLES as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign("{$table}_set_same_group_fk"));
        }

        foreach (['costume_sets', 'costumes', 'events'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropUnique("{$table}_id_group_unique"));
        }
    }
};
