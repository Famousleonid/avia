<?php

namespace App\Services;

use App\Models\{ProcessName, Workorder};
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Calculation rules live here; adapters preserve the real process/group dates. */
class WorkorderCompletionForecast
{
    public function includes(?ProcessName $name): bool
    {
        if (!$name) return false;
        $key = ProcessName::normalizedNameKey($name->identityName());
        if (in_array($key, config('workorder_completion.excluded_processes', []), true)) return false;
        return $key === 'traveler' || $name->show_in_process_picker !== false;
    }

    public function days(?ProcessName $name, ?string $identity = null): int
    {
        $key = ProcessName::normalizedNameKey($identity ?? $name?->identityName());
        $days = max(0, (int) $name?->std_days);
        return $days > 0 ? $days : max(0, (int) config('workorder_completion.fallback_days.'.$key, 0));
    }

    public function tdrOperations(Collection $rows, ?ProcessName $traveler): Collection
    {
        return $rows->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->filter(fn ($row) => !$row->ignore_row && $this->includes($row->processName))
            ->groupBy(fn ($row) => $row->in_traveler ? 'traveler:'.($row->traveler_group ?: 1) : 'process:'.$row->id)
            ->map(function ($group, $key) use ($traveler) {
                $isTraveler = str_starts_with($key, 'traveler:');
                $first = $group->first();
                return [
                    'name' => $isTraveler ? 'Traveler' : $first->processName->name,
                    'days' => $this->days($isTraveler ? $traveler : $first->processName, $isTraveler ? 'Traveler' : null),
                    'start' => $group->pluck('date_start')->filter()->min(),
                    'finish' => $group->every(fn ($row) => (bool) $row->date_finish)
                        ? $group->pluck('date_finish')->max() : null,
                ];
            })->values();
    }

    public function bushingOperations(Collection $rows): Collection
    {
        // One batch carries the dates of all its quantities; do not multiply days by quantity.
        return $rows->filter(fn ($row) => $this->includes($row->process?->process_name))
            ->unique('batch_id')->map(fn ($row) => [
                'name' => $row->process->process_name->name,
                'days' => $this->days($row->process->process_name),
                'start' => $row->batch->date_start,
                'finish' => $row->batch->date_finish,
            ])->values();
    }

    public function stdOperations(Collection $rows): Collection
    {
        // STD lists travel as one list, not once per member/quantity.
        return $rows->filter(fn ($row) => !$row->ignore_row && in_array($row->std_type, ['ndt', 'cad'], true))
            ->sortBy(fn ($row) => $row->std_type === 'ndt' ? 0 : 1)
            ->map(fn ($row) => ['name' => strtoupper($row->std_type).' STD', 'days' => $this->days($row->processName), 'finish' => $row->date_finish])->values();
    }

    public function calculate(Workorder $workorder, Collection $routes): array
    {
        if (!$workorder->approve_at) return ['days' => null, 'date' => null, 'routes' => []];
        $routes = $routes->filter(fn ($route) => $route->isNotEmpty());
        if ($routes->isEmpty()) return ['days' => 0, 'date' => Carbon::today(), 'routes' => []];
        $approval = $workorder->approve_at->copy()->startOfDay();
        $end = $approval->copy();
        $details = [];
        foreach ($routes as $label => $operations) {
            $steps = [];
            $routeEnd = $approval->copy();
            $cursor = $approval->copy();
            $first = true;
            foreach ($operations as $operation) {
                $gap = !$first && !$operation['finish'] ? max(0, (int) config('workorder_completion.between_process_days', 2)) : 0;
                if ($operation['finish']) {
                    // Actual completion replaces the prediction at this route milestone.
                    // Following operations continue from it, so an early return saves time.
                    $cursor = Carbon::parse($operation['finish'])->startOfDay()->max($approval);
                    if ($cursor->gt($end)) $end = $cursor->copy();
                } else {
                    if (!$first) $cursor->addDays(max(0, (int) config('workorder_completion.between_process_days', 2)));
                    $cursor->addDays($operation['days']);
                }
                if ($cursor->gt($routeEnd)) $routeEnd = $cursor->copy();
                $steps[] = ['name' => $operation['name'] ?? 'Process', 'days' => $operation['days'], 'gap' => $gap, 'finish' => $operation['finish'], 'end' => $cursor->copy()];
                $first = false;
            }
            $details[] = ['label' => $label, 'steps' => $steps, 'days' => (int) $approval->diffInDays($routeEnd), 'end' => $routeEnd];
            // Routes run in parallel; operations within each route run sequentially.
            if ($cursor->gt($end)) $end = $cursor->copy();
        }
        $days = (int) $approval->diffInDays($end);
        return ['days' => $days, 'date' => $days === 0 ? Carbon::today() : $end, 'routes' => $details];
    }
}
