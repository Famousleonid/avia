<?php

namespace App\Services;

use App\Models\{WoBushing, WoBushingBatch, WoBushingLine, WoBushingProcess};
use App\Support\WoBushingProcessColumnKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** A route number is shared across operations; each operation retains its own RO and dates. */
class BushingRouteBatches
{
    public function hasHistory($row): bool
    {
        return trim((string) $row->repair_order) !== '' || $row->date_start || $row->date_finish
            || ($row->machining_work_steps_count ?? $row->machiningWorkSteps()->count()) > 0;
    }

    public function protectedLineIds(Collection $lines): array
    {
        $ids = [];
        $batchIds = [];
        $routes = [];
        foreach ($lines as $line) {
            foreach ($line->processes as $row) {
                if ($this->hasHistory($row) || ($row->batch && $this->hasHistory($row->batch))) {
                    $ids[$line->id] = true;
                }
            }
        }
        // Preserve complete existing groups, including other operations of a started route.
        do {
            $before = count($ids);
            foreach ($lines as $line) {
                if (isset($ids[$line->id])) {
                    foreach ($line->processes as $row) {
                        if ($row->batch_id) $batchIds[$row->batch_id] = true;
                        if ($row->batch?->route_number) $routes[$row->batch->route_number] = true;
                    }
                }
            }
            foreach ($lines as $line) {
                foreach ($line->processes as $row) {
                    if (isset($batchIds[$row->batch_id]) || isset($routes[$row->batch?->route_number])) {
                        $ids[$line->id] = true;
                    }
                }
            }
        } while (count($ids) !== $before);
        return array_keys($ids);
    }

    public function labels(int $workorderId): array
    {
        $labels = [];
        $counts = [];
        foreach (WoBushingBatch::where('workorder_id', $workorderId)->whereHas('woBushingProcesses')
            ->with('process.process_name')->orderBy('id')->get() as $batch) {
            $key = $batch->process_column_key ?: WoBushingProcessColumnKey::fromProcess($batch->process);
            if (!$batch->route_number) $counts[$key] = ($counts[$key] ?? 0) + 1;
            $number = $batch->route_number ?: ($batch->legacy_number ?: $counts[$key]);
            $labels[$key][$batch->id] = 'B'.$number;
        }
        return $labels;
    }

    public function rebuild(WoBushing $bushing): void
    {
        DB::transaction(function () use ($bushing) {
            WoBushing::whereKey($bushing->id)->lockForUpdate()->firstOrFail();
            // Also remove drafts left empty by earlier list saves before allocating a number.
            $this->deleteEmptyDrafts((int) $bushing->workorder_id);
            $lines = $bushing->lines()->with([
                'processes' => fn ($query) => $query->withCount('machiningWorkSteps'),
                'processes.process.process_name',
                'processes.batch' => fn ($query) => $query->withCount('machiningWorkSteps'),
            ])
                ->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get();
            $protected = $this->protectedLineIds($lines);
            $labels = $this->labels((int) $bushing->workorder_id);
            // Snapshot legacy display numbers before any draft memberships change.
            $batches = WoBushingBatch::where('workorder_id', $bushing->workorder_id)->lockForUpdate()->get();
            foreach ($batches as $batch) {
                if (!$batch->route_number && !$batch->legacy_number) {
                    foreach ($labels as $byId) {
                        if (isset($byId[$batch->id])) {
                            $batch->legacy_number = (int) substr($byId[$batch->id], 1);
                            DB::table('wo_bushing_batches')->where('id', $batch->id)->update(['legacy_number' => $batch->legacy_number]);
                        }
                    }
                }
            }
            $reserved = [];
            foreach ($lines->whereIn('id', $protected) as $line) {
                foreach ($line->processes as $row) {
                    if ($row->batch_id) {
                        $batch = $batches->firstWhere('id', $row->batch_id);
                        $reserved[] = $batch->route_number ?: $batch->legacy_number;
                    }
                }
            }
            $next = max([0, ...$reserved, ...$batches->pluck('route_number')->filter()->all()]);
            $groups = [];
            foreach ($lines->whereNotIn('id', $protected) as $line) {
                $keys = $line->processes->map(fn ($row) => WoBushingProcessColumnKey::fromProcess($row->process))
                    ->filter(fn ($key) => $key !== 'other')->unique()->sort()->values()->all();
                if ($keys) $groups[implode('|', $keys)][] = $line;
            }
            $usedNumbers = $reserved;
            foreach ($groups as $groupLines) {
                $rows = collect($groupLines)->flatMap(fn ($line) => $line->processes);
                $number = $rows->map(fn ($row) => $row->batch?->route_number)->filter()
                    ->first(fn ($number) => !in_array($number, $usedNumbers));
                $number = $number ?: ++$next;
                $usedNumbers[] = $number;
                foreach ($rows->groupBy(fn ($row) => WoBushingProcessColumnKey::fromProcess($row->process)) as $key => $processRows) {
                    if ($key === 'other') continue;
                    $batch = WoBushingBatch::firstOrCreate([
                        'workorder_id' => $bushing->workorder_id,
                        'route_number' => $number,
                        'process_column_key' => $key,
                    ], ['process_id' => $processRows->first()->process_id]);
                    WoBushingProcess::whereIn('id', $processRows->pluck('id'))->update(['batch_id' => $batch->id]);
                }
            }
            $this->deleteEmptyDrafts((int) $bushing->workorder_id);
        });
    }

    private function deleteEmptyDrafts(int $workorderId): void
    {
        WoBushingBatch::where('workorder_id', $workorderId)
            ->whereDoesntHave('woBushingProcesses')
            ->whereDoesntHave('machiningWorkSteps')
            ->where(fn ($query) => $query->whereNull('repair_order')->orWhereRaw("TRIM(repair_order) = ''"))
            ->whereNull('date_start')->whereNull('date_finish')->whereNull('date_promise')
            ->whereNull('vendor_id')
            ->where(fn ($query) => $query->whereNull('working_steps_count')->orWhere('working_steps_count', 0))
            ->delete();
    }
}
