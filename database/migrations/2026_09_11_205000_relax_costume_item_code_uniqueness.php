<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "code" (piem. "COS-01") ir tikai cilvēkam salasāma birka drukātai QR uzlīmei – tā NEKAD netiek
     * izmantota, lai atrastu vienību (to dara qr_code, kas ir nejaušs un patiesi unikāls visur).
     * Tāpēc nav iemesla prasīt, lai "code" būtu unikāls VISĀ lietotnē – pietiek, ja tas ir unikāls
     * vienas grupas ietvaros (ko jau nodrošina Costume::makeCodePrefix). Vecais visaptverošais
     * ierobežojums izraisīja īstu kļūdu: divas dažādas skolotāju grupas ar līdzīgiem tērpu
     * nosaukumiem nejauši sadūrās kodos un izmeta datubāzes kļūdu.
     */
    public function up(): void
    {
        Schema::table('costume_items', function (Blueprint $table) {
            $table->dropUnique(['code']);

            // drošības tīkls pret īstu kļūdu (divkāršs kods vienam un tam pašam tērpam) –
            // šķērsgrupu sakritības tas apzināti pieļauj, jo tās ir nekaitīgas
            $table->unique(['costume_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('costume_items', function (Blueprint $table) {
            $table->dropUnique(['costume_id', 'code']);
            $table->unique('code');
        });
    }
};
