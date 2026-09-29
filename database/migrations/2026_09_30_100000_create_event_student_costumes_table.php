<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // papildu tērpi konkrētam studentam konkrētā koncertā (piem. solistam vēl viens tērps),
        // kas vajadzīgi virs tā, ko prasa viņa komplekts
        Schema::create('event_student_costumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('costume_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['event_id', 'user_id', 'costume_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_student_costumes');
    }
};
