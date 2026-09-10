<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // grupas dzēšana ir atgriezeniska 30 dienas – tāpēc mīkstā dzēšana
        Schema::table('groups', function (Blueprint $table) {
            $table->softDeletes();
        });

        // dalībnieks, kas bija tikai dzēstajā grupā, uz to pašu laiku tiek deaktivizēts
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->unsignedBigInteger('deactivated_with_group_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['deactivated_with_group_id']);
            $table->dropColumn(['deleted_at', 'deactivated_with_group_id']);
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
