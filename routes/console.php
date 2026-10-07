<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// katru dienu iztīra grupas, kas ir soft deleted ilgāk par atjaunošanas logu
Schedule::command('groups:purge')->dailyAt('03:00');

// katru dienu iztīra atsevišķi izņemtus dalībniekus, kas ir soft deleted ilgāk par atjaunošanas logu
Schedule::command('members:purge')->dailyAt('03:05');

// ik pēc 15 minūtēm nofiksē nupat notikušo koncertu gatavību, lai vēlākas izmaiņas grupā to nepārrakstītu
Schedule::call(fn () => \App\Models\Event::snapshotFinished())->everyFifteenMinutes()->name('events:snapshot');
