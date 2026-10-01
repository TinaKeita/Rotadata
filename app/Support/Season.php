<?php

namespace App\Support;

use Illuminate\Support\Carbon;

// mācību gada "sezonas" robežas sezonas atskaitei un skolotāja atgādinājumam pirms vasaras brīvlaika
// sezona vienmēr ir fiksēta: 1. septembris — 31. augusts, bez iestatījumiem katrai grupai atsevišķi
class Season
{
    private const START_MONTH = 9; // septembris

    private const REMINDER_MONTH = 5; // atgādinājums kļūst redzams no maija

    // pašreizējās sezonas sākums (šī gada 1. septembris, vai pagājušā gada, ja vēl neesam sasnieguši septembri)
    public static function start(): Carbon
    {
        $now = now();
        $year = $now->month >= self::START_MONTH ? $now->year : $now->year - 1;

        return Carbon::create($year, self::START_MONTH, 1)->startOfDay();
    }

    // jebkuras sezonas robežas pēc tās sākuma gada (piem. 2025 → 2025/2026); null – pašreizējā sezona
    public static function bounds(?int $startYear = null): array
    {
        $start = $startYear
            ? Carbon::create($startYear, self::START_MONTH, 1)->startOfDay()
            : self::start();
        $end = $start->copy()->addYear()->subDay()->endOfDay();

        return [
            'year' => $start->year,
            'start' => $start,
            'end' => $end,
            'label' => $start->year.'/'.$end->year,
            'current' => now()->between($start, $end),
        ];
    }

    // sezonas sākuma gads datumam (piem. 2026. gada marts → 2025)
    public static function startYearOf(Carbon $date): int
    {
        return $date->month >= self::START_MONTH ? $date->year : $date->year - 1;
    }

    // pašreizējās sezonas beigas (nākamā gada 31. augusts)
    public static function end(): Carbon
    {
        return self::start()->copy()->addYear()->subDay()->endOfDay();
    }

    // cilvēkam saprotams sezonas apzīmējums, piem. "2025/2026"
    public static function label(): string
    {
        return self::start()->year.'/'.self::end()->year;
    }

    // no kura brīža rādīt atgādinājumu par atskaites eksportēšanu (mēnesi pirms mācību gada noslēguma)
    public static function reminderStartsAt(): Carbon
    {
        return Carbon::create(self::end()->year, self::REMINDER_MONTH, 1)->startOfDay();
    }

    // vai tuvojas sezonas noslēgums un skolotājam būtu jāredz atgādinājums par eksportu
    public static function isClosingSoon(): bool
    {
        return now()->between(self::reminderStartsAt(), self::end());
    }
}
