<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('costume_item_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('costume_item_id')->constrained()->cascadeOnDelete();

            // kas turēja vienību – FK kļūst null, ja lietotājs dzēsts, bet vārds paliek
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');

            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('returned_at')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('return_note')->nullable(); // 'self' = students atdeva pats, 'admin' = skolotājs paņēma atpakaļ

            $table->timestamps();

            $table->index(['costume_item_id', 'returned_at']);
            $table->index(['user_id', 'returned_at']);
        });

        // aizpilda vēsturi pašreiz piešķirtajām vienībām (viens atvērts ieraksts katrai)
        $rows = DB::table('costume_items')->whereNotNull('assigned_to')->get();

        foreach ($rows as $item) {
            $name = DB::table('users')->where('id', $item->assigned_to)->value('name');

            DB::table('costume_item_assignments')->insert([
                'costume_item_id' => $item->id,
                'user_id' => $item->assigned_to,
                'user_name' => $name ?? 'Unknown',
                'assigned_at' => $item->assigned_at ?? $item->created_at ?? now(),
                'assigned_by' => null,
                'returned_at' => null,
                'returned_by' => null,
                'return_note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costume_item_assignments');
    }
};
