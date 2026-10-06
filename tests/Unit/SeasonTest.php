<?php

namespace Tests\Unit;

use App\Support\Season;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// mācību gada sezona: 1. septembris – 31. augusts, atgādinājums no maija
class SeasonTest extends TestCase
{
    public function test_season_started_last_september_until_august(): void
    {
        $this->travelTo(Carbon::create(2026, 8, 31, 23, 0));

        $this->assertSame('2025/2026', Season::label());
        $this->assertSame('2025-09-01', Season::start()->toDateString());
        $this->assertSame('2026-08-31', Season::end()->toDateString());
    }

    public function test_new_season_starts_on_the_first_of_september(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 1, 0, 0));

        $this->assertSame('2026/2027', Season::label());
    }

    public function test_start_year_of_any_date(): void
    {
        $this->assertSame(2025, Season::startYearOf(Carbon::create(2026, 3, 15)));
        $this->assertSame(2026, Season::startYearOf(Carbon::create(2026, 10, 1)));
    }

    public function test_reminder_shows_only_from_may_to_season_end(): void
    {
        $this->travelTo(Carbon::create(2026, 4, 30));
        $this->assertFalse(Season::isClosingSoon());

        $this->travelTo(Carbon::create(2026, 5, 1, 12, 0));
        $this->assertTrue(Season::isClosingSoon());

        $this->travelTo(Carbon::create(2026, 10, 6));
        $this->assertFalse(Season::isClosingSoon());
    }
}
