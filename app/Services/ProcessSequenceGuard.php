<?php

namespace App\Services;

use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\WorkorderStdProcess;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProcessSequenceGuard
{
    public const READY_EVENT_KEY = 'tdr_process.ready_for_next';

    /** @var array<int, Collection<int, array<string, mixed>>> */
    private array $tdrUnitsCache = [];

    /** @var array<int, Collection<int, array<string, mixed>>> */
    private array $stdUnitsCache = [];

    private const STD_ORDER = [
        'ndt' => 10,
        'cad' => 20,
        'stress' => 30,
        'paint' => 40,
    ];

    public function validateTdrProcessDateUpdate(TdrProcess $process, array $data): ?array
    {
        if (! $this->hasSequenceDateChange($data)) {
            return null;
        }

        if ($this->isTdrProcessSequenceExempt($process)) {
            return null;
        }

        $units = $this->tdrUnits((int) $process->tdrs_id);
        $index = $this->findUnitIndex($units, (int) $process->id);

        return $this->validateUnitDateUpdate($units, $index, $data);
    }

    public function validateTravelerGroupDateUpdate(Tdr $tdr, int $travelerGroup, array $data): ?array
    {
        if (! $this->hasSequenceDateChange($data)) {
            return null;
        }

        $units = $this->tdrUnits((int) $tdr->id);
        $index = $units->search(function (array $unit) use ($travelerGroup): bool {
            return ($unit['type'] ?? null) === 'traveler'
                && (int) ($unit['traveler_group'] ?? 1) === ($travelerGroup > 0 ? $travelerGroup : 1);
        });

        return $this->validateUnitDateUpdate($units, $index === false ? null : (int) $index, $data);
    }

    public function validateStdDateUpdate(WorkorderStdProcess $process, array $data): ?array
    {
        if (! $this->hasSequenceDateChange($data)) {
            return null;
        }

        $units = $this->stdUnits((int) $process->workorder_id);
        $index = $this->findUnitIndex($units, (int) $process->id);

        return $this->validateUnitDateUpdate($units, $index, $data);
    }

    public function tdrInputState(TdrProcess $process): array
    {
        if ($this->isTdrProcessSequenceExempt($process)) {
            return $this->unlockedInputState('Excluded from process sequence.');
        }

        $units = $this->tdrUnits((int) $process->tdrs_id);
        $index = $this->findUnitIndex($units, (int) $process->id);

        return $this->inputState($units, $index);
    }

    public function travelerGroupInputState(Tdr $tdr, int $travelerGroup): array
    {
        $units = $this->tdrUnits((int) $tdr->id);
        $index = $units->search(function (array $unit) use ($travelerGroup): bool {
            return ($unit['type'] ?? null) === 'traveler'
                && (int) ($unit['traveler_group'] ?? 1) === ($travelerGroup > 0 ? $travelerGroup : 1);
        });

        return $this->inputState($units, $index === false ? null : (int) $index);
    }

    public function stdInputState(WorkorderStdProcess $process): array
    {
        $units = $this->stdUnits((int) $process->workorder_id);
        $index = $this->findUnitIndex($units, (int) $process->id);

        return $this->inputState($units, $index);
    }

    public function nextAfterTdrProcess(TdrProcess $process): TdrProcess|WorkorderStdProcess|null
    {
        $units = $this->tdrUnits((int) $process->tdrs_id);
        $index = $this->findUnitIndex($units, (int) $process->id);

        return $this->nextSubject($units, $index);
    }

    public function nextAfterTravelerGroup(Tdr $tdr, int $travelerGroup): TdrProcess|WorkorderStdProcess|null
    {
        $units = $this->tdrUnits((int) $tdr->id);
        $index = $units->search(function (array $unit) use ($travelerGroup): bool {
            return ($unit['type'] ?? null) === 'traveler'
                && (int) ($unit['traveler_group'] ?? 1) === ($travelerGroup > 0 ? $travelerGroup : 1);
        });

        return $this->nextSubject($units, $index === false ? null : (int) $index);
    }

    public function nextAfterStdProcess(WorkorderStdProcess $process): TdrProcess|WorkorderStdProcess|null
    {
        $units = $this->stdUnits((int) $process->workorder_id);
        $index = $this->findUnitIndex($units, (int) $process->id);

        return $this->nextSubject($units, $index);
    }

    private function validateUnitDateUpdate(Collection $units, ?int $index, array $data): ?array
    {
        if ($index === null || ! $units->has($index)) {
            return ['date_start' => ['Process sequence row was not found.']];
        }

        $state = $this->inputState($units, $index);
        $unit = $units[$index];
        $currentStart = $unit['date_start'];
        $currentFinish = $unit['date_finish'];

        if ($state['locked_back'] && $this->changesCurrentSequenceDates($unit, $data)) {
            return ['date_start' => ['This process is locked because a later process already has dates.']];
        }

        if (array_key_exists('date_start', $data)
            && $currentFinish
            && ($data['date_start'] ?: null) !== ($currentStart ?: null)) {
            return ['date_start' => ['The sent date is locked because the returned date is already set.']];
        }

        if (! $state['previous_complete'] && ! $state['has_later_dates']) {
            return ['date_start' => ['Previous processes must have sent and returned dates before this process can be started.']];
        }

        $effectiveStart = array_key_exists('date_start', $data)
            ? ($data['date_start'] ?: null)
            : $currentStart;
        $effectiveFinish = array_key_exists('date_start', $data)
            && ($data['date_start'] ?: null) === null
            && ! array_key_exists('date_finish', $data)
                ? null
                : (array_key_exists('date_finish', $data)
                    ? ($data['date_finish'] ?: null)
                    : $currentFinish);

        if ($effectiveFinish && ! $effectiveStart) {
            return ['date_finish' => ['The start date must be filled in before setting the end date.']];
        }

        if ($effectiveStart && $effectiveFinish && Carbon::parse($effectiveStart)->gt(Carbon::parse($effectiveFinish))) {
            return ['date_finish' => ['The returned date cannot be earlier than the sent date.']];
        }

        $previousFinish = $index > 0 ? ($units[$index - 1]['date_finish'] ?? null) : null;
        if ($previousFinish && $effectiveStart && Carbon::parse($effectiveStart)->lt(Carbon::parse($previousFinish))) {
            return ['date_start' => ['The sent date cannot be earlier than the previous process returned date.']];
        }

        $nextStart = $units->has($index + 1) ? ($units[$index + 1]['date_start'] ?? null) : null;
        if ($nextStart && $effectiveFinish && Carbon::parse($effectiveFinish)->gt(Carbon::parse($nextStart))) {
            return ['date_finish' => ['The returned date cannot be later than the next process sent date.']];
        }

        return null;
    }

    private function inputState(Collection $units, ?int $index): array
    {
        if ($index === null || ! $units->has($index)) {
            return [
                'previous_complete' => false,
                'locked_back' => true,
                'has_later_dates' => false,
                'date_start_disabled' => true,
                'date_finish_disabled' => true,
                'reason' => 'Process sequence row was not found.',
            ];
        }

        $previousComplete = $units
            ->take($index)
            ->every(fn (array $unit): bool => $this->unitComplete($unit));

        $hasLaterDates = $units
            ->slice($index + 1)
            ->contains(fn (array $unit): bool => $this->unitHasAnyDate($unit));
        $lockedBack = $hasLaterDates && $this->unitComplete($units[$index]);
        $blockedForward = ! $previousComplete && ! $hasLaterDates;
        $startLockedByFinish = ! empty($units[$index]['date_finish']);

        return [
            'previous_complete' => $previousComplete,
            'has_later_dates' => $hasLaterDates,
            'locked_back' => $lockedBack,
            'date_start_disabled' => $lockedBack || $blockedForward || $startLockedByFinish,
            'date_finish_disabled' => $lockedBack || $blockedForward,
            'reason' => $lockedBack
                ? 'A later process already has dates.'
                : ($blockedForward
                    ? 'Previous processes must be completed first.'
                    : ($startLockedByFinish ? 'Returned date already exists.' : '')),
        ];
    }

    private function unlockedInputState(string $reason = ''): array
    {
        return [
            'previous_complete' => true,
            'has_later_dates' => false,
            'locked_back' => false,
            'date_start_disabled' => false,
            'date_finish_disabled' => false,
            'reason' => $reason,
        ];
    }

    private function tdrUnits(int $tdrId): Collection
    {
        if (array_key_exists($tdrId, $this->tdrUnitsCache)) {
            return $this->tdrUnitsCache[$tdrId];
        }

        return $this->tdrUnitsCache[$tdrId] = TdrProcess::query()
            ->with('processName')
            ->where('tdrs_id', $tdrId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (TdrProcess $process): bool => optional($process->processName)->show_in_process_picker !== false
                && ! $this->isTdrProcessSequenceExempt($process)
                && ! (bool) ($process->ignore_row ?? false))
            ->groupBy(function (TdrProcess $process): string {
                if ($process->in_traveler) {
                    return 'traveler_'.(int) ($process->traveler_group ?: 1);
                }

                return 'process_'.$process->id;
            })
            ->map(function (Collection $group, string $key): array {
                $leader = $group->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])->first();

                return [
                    'type' => str_starts_with($key, 'traveler_') ? 'traveler' : 'process',
                    'traveler_group' => str_starts_with($key, 'traveler_') ? (int) substr($key, 9) : null,
                    'ids' => $group->pluck('id')->map(fn ($id) => (int) $id)->all(),
                    'subject' => $leader,
                    'sort_order' => (int) ($group->min('sort_order') ?? 999999),
                    'id' => (int) ($group->min('id') ?? 0),
                    'date_start' => $group->contains(fn (TdrProcess $process): bool => ! empty($process->date_start))
                        ? (string) $group->pluck('date_start')->filter()->min()?->format('Y-m-d')
                        : null,
                    'date_finish' => $group->every(fn (TdrProcess $process): bool => ! empty($process->date_finish))
                        ? (string) $group->pluck('date_finish')->filter()->max()?->format('Y-m-d')
                        : null,
                    'records' => $group->values(),
                ];
            })
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    private function stdUnits(int $workorderId): Collection
    {
        if (array_key_exists($workorderId, $this->stdUnitsCache)) {
            return $this->stdUnitsCache[$workorderId];
        }

        return $this->stdUnitsCache[$workorderId] = WorkorderStdProcess::query()
            ->with('processName')
            ->where('workorder_id', $workorderId)
            ->get()
            ->filter(fn (WorkorderStdProcess $process): bool => ! (bool) ($process->ignore_row ?? false))
            ->map(function (WorkorderStdProcess $process): array {
                return [
                    'type' => 'std',
                    'ids' => [(int) $process->id],
                    'subject' => $process,
                    'sort_order' => self::STD_ORDER[$process->std_type] ?? 999999,
                    'id' => (int) $process->id,
                    'date_start' => $process->date_start?->format('Y-m-d'),
                    'date_finish' => $process->date_finish?->format('Y-m-d'),
                    'records' => collect([$process]),
                ];
            })
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    private function findUnitIndex(Collection $units, int $processId): ?int
    {
        $index = $units->search(fn (array $unit): bool => in_array($processId, $unit['ids'], true));

        return $index === false ? null : (int) $index;
    }

    private function isTdrProcessSequenceExempt(TdrProcess $process): bool
    {
        return (bool) ($process->processName?->sequence_exempt ?? false);
    }

    private function nextSubject(Collection $units, ?int $index): TdrProcess|WorkorderStdProcess|null
    {
        if ($index === null || ! $units->has($index + 1)) {
            return null;
        }

        return $units[$index + 1]['subject'] ?? null;
    }

    private function hasSequenceDateChange(array $data): bool
    {
        return array_key_exists('date_start', $data) || array_key_exists('date_finish', $data);
    }

    private function changesCurrentSequenceDates(array $unit, array $data): bool
    {
        if (array_key_exists('date_start', $data) && ($data['date_start'] ?: null) !== ($unit['date_start'] ?: null)) {
            return true;
        }

        if (array_key_exists('date_finish', $data) && ($data['date_finish'] ?: null) !== ($unit['date_finish'] ?: null)) {
            return true;
        }

        return false;
    }

    private function unitComplete(array $unit): bool
    {
        return ! empty($unit['date_start']) && ! empty($unit['date_finish']);
    }

    private function unitHasAnyDate(array $unit): bool
    {
        return ! empty($unit['date_start']) || ! empty($unit['date_finish']);
    }
}
