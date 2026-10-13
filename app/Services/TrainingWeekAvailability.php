<?php

namespace App\Services;

use App\Models\Training;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TrainingWeekAvailability
{
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
        $records = $this->events($userId)->whereBetween('date_training', [$start->toDateString(), $end->toDateString()])
            ->with(['manual:id,unit_name_training,title', 'matrixRow:id,part_number,description'])
            ->orderBy('date_training')->get()
            ->groupBy(fn ($record) => Carbon::parse($record->date_training)->startOfWeek(Carbon::MONDAY)->toDateString());
        $weeks = [];
        for ($monday = $start->copy(); $monday->lte($end); $monday->addWeek()) {
            $friday = $monday->copy()->addDays(4);
            $events = ($records->get($monday->toDateString()) ?? collect())->map(fn ($record) => [
                'id' => $record->id,
                'approved' => $record->isApproved(),
                'name' => $record->matrixRow?->part_number ?: $record->manual?->unit_name_training ?: 'Training',
                'description' => $record->matrixRow?->description ?: $record->manual?->title,
            ])->values()->all();
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
                'status' => count($events) > 1 ? 'conflict' : ($events ? 'occupied' : ($friday->gt(Carbon::today()) ? 'future' : 'free')),
                'days' => $days,
                'trainings' => $events,
            ];
        }

        return ['month' => $month, 'title' => $first->format('Y'), 'range' => format_project_date($start) . ' – ' . format_project_date($end), 'weeks' => $weeks];
    }

    public function friday(string $date): string
    {
        $friday = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->addDays(4)->startOfDay();
        if ($friday->isAfter(Carbon::today())) {
            $friday->subWeek();
        }

        return $friday->format('Y-m-d');
    }

    public function check(int $userId, string $date, ?int $ignoreId = null, array $reservedDates = []): array
    {
        $friday = $this->friday($date);
        // 132 is a certificate, not a second training. Undated legacy marks occupy no week.
        $dates = $this->events($userId)
            ->when($ignoreId, fn ($q) => $q->where('id', '<>', $ignoreId))
            // A current read is required after waiting for another writer's employee lock.
            ->when(\Illuminate\Support\Facades\DB::transactionLevel() > 0, fn ($q) => $q->lockForUpdate())
            ->pluck('date_training')->all();
        $occupied = [];
        foreach (array_merge($dates, $reservedDates) as $existing) {
            $week = Carbon::parse($existing)->startOfWeek(Carbon::MONDAY)->addDays(4)->format('Y-m-d');
            $occupied[$week] = true;
        }
        $conflict = isset($occupied[$friday]);
        $candidate = Carbon::parse($friday);
        if ($conflict) {
            do {
                $candidate->subWeek();
            } while (isset($occupied[$candidate->format('Y-m-d')]));
        }

        return [
            'available' => !$conflict,
            'date' => $friday,
            'suggested_date' => $candidate->format('Y-m-d'),
            'message' => $conflict
                ? 'Only one training per employee per week is allowed. The week ending ' . format_project_date($friday)
                    . ' is occupied. Nearest free week ends ' . format_project_date($candidate->format('Y-m-d')) . '.'
                : null,
        ];
    }

    public function assertAvailable(int $userId, string $date, ?int $ignoreId = null): void
    {
        $result = $this->check($userId, $date, $ignoreId);
        if (!$result['available']) {
            throw ValidationException::withMessages(['date_training' => $result['message']]);
        }
    }
}
