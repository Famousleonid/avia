<?php

namespace Tests\Unit;

use App\Services\OntarioWorkingDays;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class OntarioWorkingDaysTest extends TestCase
{
    /** @dataProvider intervals */
    public function test_working_day_intervals(string $start, string $end, int $expected): void
    {
        $this->assertSame($expected, (new OntarioWorkingDays())->between(CarbonImmutable::parse($start), CarbonImmutable::parse($end)));
    }

    public function intervals(): array
    {
        return [
            'same day' => ['2026-01-05', '2026-01-05', 0],
            'weekend' => ['2026-01-09', '2026-01-12', 1],
            'Family Day' => ['2026-02-13', '2026-02-17', 1],
            'Good Friday, Easter Monday working' => ['2026-04-02', '2026-04-06', 1],
            'Victoria Day' => ['2026-05-15', '2026-05-19', 1],
            'Canada Day' => ['2026-06-30', '2026-07-02', 1],
            'Labour Day' => ['2026-09-04', '2026-09-08', 1],
            'Thanksgiving' => ['2026-10-09', '2026-10-13', 1],
            'Christmas and Boxing Day observed' => ['2026-12-24', '2026-12-29', 1],
            'two weekend holidays have distinct substitutes' => ['2021-12-24', '2021-12-29', 1],
            'New Year crosses year boundary' => ['2026-12-31', '2027-01-04', 1],
            'New Year observed' => ['2021-12-31', '2022-01-04', 1],
            'Civic Holiday is working' => ['2026-07-31', '2026-08-04', 2],
            'Remembrance Day is working' => ['2026-11-10', '2026-11-12', 2],
            'no DST day loss' => ['2026-03-06', '2026-03-09', 1],
        ];
    }

    public function test_company_substitute_can_cross_year_boundary(): void
    {
        config(['working_days.ontario_substitutes' => ['2026-12-26' => '2027-01-04']]);
        $calendar = new OntarioWorkingDays();
        $this->assertSame(0, $calendar->between(CarbonImmutable::parse('2027-01-01'), CarbonImmutable::parse('2027-01-04')));
        $this->assertSame(1, $calendar->between(CarbonImmutable::parse('2026-12-27'), CarbonImmutable::parse('2026-12-28')));
    }
}
