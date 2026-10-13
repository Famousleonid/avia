<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class OntarioWorkingDays
{
    private array $holidays = [];

    /** Elapsed working days: exclude the start date, include the finish date. */
    public function between(DateTimeInterface $start, DateTimeInterface $finish): int
    {
        $day = CarbonImmutable::parse($start->format('Y-m-d'), 'America/Toronto')->addDay();
        $end = $finish->format('Y-m-d');
        $count = 0;
        while ($day->toDateString() <= $end) {
            $holidays = $this->holidays[$day->year] ??= array_merge(
                $this->forYear($day->year - 1), $this->forYear($day->year), $this->forYear($day->year + 1)
            );
            if ($day->isWeekday() && !isset($holidays[$day->toDateString()])) {
                $count++;
            }
            $day = $day->addDay();
        }
        return $count;
    }

    private function forYear(int $year): array
    {
        $date = fn (string $value) => CarbonImmutable::parse($value, 'America/Toronto')->startOfDay();
        // Gregorian Easter (Meeus/Jones/Butcher), without requiring PHP's calendar extension.
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $easter = CarbonImmutable::create($year, intdiv($h + $l - 7 * $m + 114, 31), ($h + $l - 7 * $m + 114) % 31 + 1, 0, 0, 0, 'America/Toronto');
        // Ontario ESA public holidays. Civic Holiday, Easter Monday and Remembrance Day are not included.
        $dates = [
            $date("$year-01-01"),
            $easter->subDays(2),
            $date("$year-05-24")->subDays(($date("$year-05-24")->dayOfWeek + 6) % 7),
            $date("$year-07-01"),
            $date("first Monday of September $year"),
            $date("second Monday of October $year"),
            $date("$year-12-25"),
            $date("$year-12-26"),
        ];
        if ($year >= 2008) {
            $dates[] = $date("third Monday of February $year");
        }
        $result = [];
        foreach ($dates as $holiday) {
            $result[$holiday->toDateString()] = true;
        }
        // Reporting convention: weekend holidays move to the next unoccupied weekday.
        // A company's actual substitute dates can be set as original date => replacement date.
        $substitutes = config('working_days.ontario_substitutes', []);
        foreach ($dates as $holiday) {
            if (array_key_exists($holiday->toDateString(), $substitutes)) {
                unset($result[$holiday->toDateString()]);
                if ($substitutes[$holiday->toDateString()]) {
                    $result[$substitutes[$holiday->toDateString()]] = true;
                }
            } elseif ($holiday->isWeekend()) {
                do {
                    $holiday = $holiday->addDay();
                } while ($holiday->isWeekend() || isset($result[$holiday->toDateString()]));
                $result[$holiday->toDateString()] = true;
            }
        }
        return $result;
    }
}
