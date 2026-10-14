<?php

namespace App\Services;

use App\Models\Training;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TrainingWeekAvailability
{
    public const WEEKLY_HOURS = 40;

    public function records(int $userId)
    {
        return $this->events($userId)->with(['manual:id,unit_name_training,title,training_hours', 'matrixRow:id,part_number,description'])
            ->when(\Illuminate\Support\Facades\DB::transactionLevel() > 0, fn ($q) => $q->lockForUpdate())
            ->orderBy('date_training')->orderBy('id')->get();
    }

    public function totals($records): array
    {
        $hours = app(TrainingHours::class)->forRecords($records);
        $totals = [];
        foreach ($records as $record) {
            $week = Carbon::parse($record->date_training)->startOfWeek(Carbon::MONDAY)->addDays(4)->toDateString();
            $totals[$week] = ($totals[$week] ?? 0) + ($hours[$record->id] ?? 0);
        }
        return $totals;
    }

    public function assertChangesAvailable(array $before, int $userId): void
    {
        foreach ($this->totals($this->records($userId)) as $week => $total) {
            if ($total > self::WEEKLY_HOURS && $total > ($before[$week] ?? 0)) {
                throw ValidationException::withMessages(['date_training' => 'Training limit is 40 hours per employee per week. Week ending '
                    .format_project_date($week).' would contain '.$total.' hours.']);
            }
        }
    }
    private function events(int $userId)
    {
        return Training::where('user_id', $userId)->where('is_legacy', false)
            ->whereNotNull('date_training')
            ->where(fn ($q) => $q->where('form_type', '112')->orWhereNotNull('matrix_row_id'));
    }

    /** 54 consecutive weeks, starting with the week containing January 1 of the selected year. */
    public function calendar(int $userId, string $month): array
    {
        $first = Carbon::createFromFormat('!Y-m', $month)->startOfYear();
        $start = $first->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addWeeks(54)->subDay()->endOfDay();
        $allRecords = $this->records($userId);
        $hours = app(TrainingHours::class)->forRecords($allRecords);
        $records = $allRecords->filter(fn ($record) => $record->date_training >= $start->toDateString() && $record->date_training <= $end->toDateString())
            ->groupBy(fn ($record) => Carbon::parse($record->date_training)->startOfWeek(Carbon::MONDAY)->toDateString());
        $weeks = [];
        for ($monday = $start->copy(); $monday->lte($end); $monday->addWeek()) {
            $friday = $monday->copy()->addDays(4);
            $events = ($records->get($monday->toDateString()) ?? collect())->map(fn ($record) => [
                'id' => $record->id,
                'approved' => $record->isApproved(),
                'name' => $record->matrixRow?->part_number ?: $record->manual?->unit_name_training ?: 'Training',
                'description' => $record->matrixRow?->description ?: $record->manual?->title,
                'hours' => $hours[$record->id] ?? null,
            ])->values()->all();
            $total = array_sum(array_column($events, 'hours'));
            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $day = $monday->copy()->addDays($i);
                $days[] = ['day' => $day->day, 'in_month' => $day->format('Y-m') === $month];
            }
            $weeks[] = [
                'friday' => $friday->toDateString(),
                'month_key' => $friday->format('Y-m'),
                'month_label' => $friday->format('M'),
                'label' => format_project_date($monday) . ' – ' . format_project_date($friday),
                'status' => $total > self::WEEKLY_HOURS ? 'conflict' : ($events ? 'occupied' : ($friday->gt(Carbon::today()) ? 'future' : 'free')),
                'total_hours' => $total,
                'remaining_hours' => max(0, self::WEEKLY_HOURS - $total),
                'capacity_hours' => self::WEEKLY_HOURS,
                'days' => $days,
                'trainings' => $events,
            ];
        }

        return ['month' => $month, 'today' => Carbon::today()->toDateString(), 'title' => $first->format('Y'), 'range' => format_project_date($start) . ' – ' . format_project_date($end), 'weeks' => $weeks];
    }

    public function friday(string $date): string
    {
        $friday = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->addDays(4)->startOfDay();
        if ($friday->isAfter(Carbon::today())) {
            $friday->subWeek();
        }

        return $friday->format('Y-m-d');
    }

    public function check(int $userId, string $date, ?int $ignoreId = null, array $reservedDates = [], ?int $manualId = null, ?int $courseId = null): array
    {
        $friday = $this->friday($date);
        $original = $this->records($userId);
        $before = $this->totals($original);
        $editing = $ignoreId ? $original->firstWhere('id', $ignoreId) : null;
        $manualId = $editing?->manuals_id ?? $manualId;
        $courseId = $editing?->matrix_row_id ?? $courseId;
        $manual = $manualId ? \App\Models\Manual::find($manualId) : null;
        $base = $original->reject(fn ($row) => $row->id === $ignoreId)->values();
        $nextId = (int) $original->max('id') + 1;
        $append = function ($records, string $day, bool $deduplicate = true) use ($userId, $manualId, $courseId, $manual, &$nextId) {
            $day = $this->friday($day);
            if ($deduplicate && ($manualId || $courseId) && $records->contains(fn ($row) => $row->manuals_id == $manualId && $row->matrix_row_id == $courseId && $row->date_training === $day)) return;
            $row = new Training(['user_id' => $userId, 'manuals_id' => $manualId, 'matrix_row_id' => $courseId,
                'form_type' => $courseId ? null : '112', 'date_training' => $day, 'is_legacy' => false]);
            $row->id = $nextId++;
            $row->setRelation('manual', $manual);
            $records->push($row);
        };
        // Pending dates in each editor belong to the same manual/course as the selected input.
        foreach ($reservedDates as $reserved) $append($base, $reserved);
        $evaluate = function (string $day) use ($base, $append, $before, $manualId, $courseId, $ignoreId) {
            $records = collect($base->all());
            $append($records, $day, !$ignoreId);
            $totals = $this->totals($records);
            $blockedWeek = null;
            foreach ($totals as $week => $total) {
                if ($total > self::WEEKLY_HOURS && $total > ($before[$week] ?? 0)) { $blockedWeek = $week; break; }
            }
            // With no course selected yet, only report whether the week has spare capacity.
            if (!$manualId && !$courseId && ($totals[$day] ?? 0) >= self::WEEKLY_HOURS) $blockedWeek = $day;
            return ['blocked_week' => $blockedWeek, 'total_hours' => $totals[$day] ?? 0,
                'blocked_hours' => $blockedWeek ? $totals[$blockedWeek] : null];
        };
        $result = $evaluate($friday);
        $conflict = $result['blocked_week'] !== null;
        $suggested = $friday;
        if ($conflict) {
            $suggested = null;
            $candidate = Carbon::parse($friday);
            $reservedFridays = array_map(fn ($reserved) => $this->friday($reserved), $reservedDates);
            // Bound the search when a single training cannot fit into a 40-hour week.
            for ($i = 0; $i < 520; $i++) {
                $candidate->subWeek();
                // Do not suggest collapsing this draft into another pending date of the same course.
                if (in_array($candidate->toDateString(), $reservedFridays, true)) continue;
                if (!$evaluate($candidate->toDateString())['blocked_week']) { $suggested = $candidate->toDateString(); break; }
            }
        }

        return [
            'available' => !$conflict,
            'date' => $friday,
            'suggested_date' => $suggested,
            'total_hours' => $result['total_hours'],
            'capacity_hours' => self::WEEKLY_HOURS,
            'remaining_hours' => max(0, self::WEEKLY_HOURS - $result['total_hours']),
            'message' => $conflict
                ? 'Training limit is 40 hours per employee per week. Week ending ' . format_project_date($result['blocked_week'])
                    . ' would contain '.$result['blocked_hours'].' hours.'
                    .($suggested ? ' Nearest week with capacity ends '.format_project_date($suggested).'.' : ' No suitable week found in the previous 10 years.')
                : null,
        ];
    }

}
