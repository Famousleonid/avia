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
        $column = $row->process_column_key ?: WoBushingProcessColumnKey::fromProcess($row->process);
        // Clearing Machining's sent date explicitly returns it to editable state.
        // Other operations still protect the complete connected route below.
        if ($column === 'machining') {
            return (bool) $row->date_start;
        }

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
            $labels[$key][$batch->id] = $batch->route_label ?: 'B'.$number;
        }
        return $labels;
    }

    public function rebuild(WoBushing $bushing, array $retainedLineIds = []): void
    {
        DB::transaction(function () use ($bushing, $retainedLineIds) {
            WoBushing::whereKey($bushing->id)->lockForUpdate()->firstOrFail();
            // Also remove drafts left empty by earlier list saves before allocating a number.
            $this->deleteEmptyDrafts((int) $bushing->workorder_id);
            $lines = $bushing->lines()->with([
                'processes' => fn ($query) => $query->withCount('machiningWorkSteps'),
                'processes.process.process_name',
                'processes.batch' => fn ($query) => $query->withCount('machiningWorkSteps'),
            ])
                ->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get();
            $protected = array_values(array_unique(array_merge($this->protectedLineIds($lines), $retainedLineIds)));
            $retainedBatchIds = $lines->whereIn('id', $retainedLineIds)->flatMap->processes->pluck('batch_id')->filter()->all();
            $labels = $this->labels((int) $bushing->workorder_id);
            // Snapshot legacy display numbers before any draft memberships change.
            $batches = WoBushingBatch::where('workorder_id', $bushing->workorder_id)->lockForUpdate()->get();
            foreach ($batches as $batch) {
                if (in_array($batch->id, $retainedBatchIds, true)) continue;
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
            $usedLabels = $batches->map(fn ($batch) => $batch->route_label ?: 'B'.($batch->route_number ?: $batch->legacy_number))->all();
            $nextLabel = max([0, ...array_map(fn ($label) => (int) substr(explode('.', $label)[0], 1), $usedLabels)]);
            $groups = [];
            foreach ($lines->whereNotIn('id', $protected) as $line) {
                $keys = $line->processes->map(fn ($row) => WoBushingProcessColumnKey::fromProcess($row->process))
                    ->filter(fn ($key) => $key !== 'other')->unique()->sort()->values()->all();
                $partition = $line->manual_route_number
                    ? $batches->firstWhere('route_number', $line->manual_route_number)
                    : $line->processes->first(fn ($row) => $row->batch?->manual_partition)?->batch;
                if ($keys) $groups[$partition ? 'manual:'.$partition->route_number.'|'.implode('|', $keys) : implode('|', $keys)][] = $line;
            }
            $usedNumbers = $reserved;
            foreach ($groups as $groupLines) {
                $rows = collect($groupLines)->flatMap(fn ($line) => $line->processes);
                $partition = $groupLines[0]->manual_route_number
                    ? $batches->firstWhere('route_number', $groupLines[0]->manual_route_number)
                    : $rows->first(fn ($row) => $row->batch?->manual_partition)?->batch;
                $number = collect([$partition?->route_number])->merge($rows->map(fn ($row) => $row->batch?->route_number))->filter()
                    ->first(fn ($number) => !in_array($number, $usedNumbers));
                $newRoute = !$number;
                $number = $number ?: ++$next;
                $displayLabel = $partition?->route_label ?: $rows->first(fn ($row) => $row->batch?->route_number == $number)?->batch?->route_label;
                if ($newRoute && $partition) {
                    $root = explode('.', $displayLabel ?: 'B'.$partition->route_number)[0];
                    $suffix = 1;
                    while (in_array($root.'.'.$suffix, $usedLabels)) $suffix++;
                    $displayLabel = $root.'.'.$suffix;
                } elseif ($newRoute) {
                    $displayLabel = 'B'.++$nextLabel;
                }
                if ($displayLabel) $usedLabels[] = $displayLabel;
                $usedNumbers[] = $number;
                if ($partition) WoBushingLine::whereIn('id', collect($groupLines)->pluck('id'))->update(['manual_route_number' => $number]);
                foreach ($rows->groupBy(fn ($row) => WoBushingProcessColumnKey::fromProcess($row->process)) as $key => $processRows) {
                    if ($key === 'other') continue;
                    $batch = WoBushingBatch::firstOrCreate([
                        'workorder_id' => $bushing->workorder_id,
                        'route_number' => $number,
                        'process_column_key' => $key,
                    ], ['process_id' => $processRows->first()->process_id,
                        'manual_partition' => (bool) $partition,
                        'route_label' => $displayLabel]);
                    WoBushingProcess::whereIn('id', $processRows->pluck('id'))->update(['batch_id' => $batch->id]);
                }
            }
            $this->deleteEmptyDrafts((int) $bushing->workorder_id);
        });
    }

    /** Split whole bushing lines across every operation; quantities remain on their lines. */
    public function split(WoBushing $bushing, array $processIds): string
    {
        return DB::transaction(function () use ($bushing, $processIds) {
            WoBushing::whereKey($bushing->id)->lockForUpdate()->firstOrFail();
            $lineIds = $bushing->lines()->orderBy('id')->lockForUpdate()->pluck('id');
            WoBushingProcess::whereIn('wo_bushing_line_id', $lineIds)->orderBy('id')->lockForUpdate()->get();
            WoBushingBatch::where('workorder_id', $bushing->workorder_id)->orderBy('id')->lockForUpdate()->get();
            $lines = $bushing->lines()->with(['component', 'processes.process.process_name', 'processes.batch.process.process_name'])
                ->lockForUpdate()->get();
            $selectedRows = $lines->flatMap->processes->whereIn('id', $processIds);
            abort_if($selectedRows->count() !== count(array_unique($processIds)), 422, 'Invalid bushing selection.');
            $routes = $selectedRows->map(fn ($row) => $row->batch?->route_number)->unique();
            abort_if($routes->count() !== 1 || !$routes->first(), 422, 'Select bushings from one automatic batch.');
            $route = $routes->first();
            $members = $lines->filter(fn ($line) => $line->processes->contains(fn ($row) => $row->batch?->route_number == $route));
            $selected = $members->whereIn('id', $selectedRows->pluck('wo_bushing_line_id'));
            abort_if($selected->flatMap->processes->contains(fn ($row) => $row->batch?->route_number != $route), 422, 'The selected bushing has inconsistent batch memberships. Refresh the list first.');
            abort_if($selected->isEmpty() || $selected->count() === $members->count(), 422, 'Select part of the batch; leave at least one bushing in the original batch.');
            $protected = $this->protectedLineIds($lines);
            abort_if($members->whereIn('id', $protected)->isNotEmpty(), 422, 'This batch already has process history and cannot be split.');
            $batches = WoBushingBatch::where('workorder_id', $bushing->workorder_id)->lockForUpdate()->get();
            $source = $batches->firstWhere('route_number', $route);
            $sourceLabel = $source->route_label ?: 'B'.$route;
            $root = explode('.', $sourceLabel)[0];
            $suffix = 1;
            while ($batches->contains('route_label', $root.'.'.$suffix)) $suffix++;
            $label = $root.'.'.$suffix;
            $number = ((int) $batches->max('route_number')) + 1;
            WoBushingBatch::where('workorder_id', $bushing->workorder_id)->where('route_number', $route)
                ->update(['manual_partition' => true, 'route_label' => $sourceLabel]);
            WoBushingLine::whereIn('id', $members->pluck('id'))->update(['manual_route_number' => $route]);
            WoBushingLine::whereIn('id', $selected->pluck('id'))->update(['manual_route_number' => $number]);
            foreach ($selected->flatMap->processes->groupBy(fn ($row) => WoBushingProcessColumnKey::fromProcess($row->process)) as $key => $rows) {
                abort_if($key === 'other', 422, 'Unsupported process in this batch.');
                $batch = WoBushingBatch::create(['workorder_id' => $bushing->workorder_id,
                    'route_number' => $number, 'route_label' => $label, 'manual_partition' => true,
                    'process_column_key' => $key, 'process_id' => $rows->first()->process_id]);
                WoBushingProcess::whereIn('id', $rows->pluck('id'))->update([
                    'batch_id' => $batch->id, 'repair_order' => null, 'vendor_id' => null,
                    'date_start' => null, 'date_finish' => null,
                ]);
            }
            activity()->performedOn($bushing->workorder)->causedBy(auth()->user())
                ->withProperties(['from' => $sourceLabel, 'to' => $label, 'bushings' => $selected->map(fn ($line) => [
                    'component_id' => $line->component_id, 'ipl' => $line->component?->ipl_num,
                    'part_number' => $line->component?->part_number, 'qty' => $line->qty,
                ])->values()->all()])->log('Bushing batch split');
            return $label;
        });
    }

    private function deleteEmptyDrafts(int $workorderId): void
    {
        WoBushingBatch::where('workorder_id', $workorderId)
            ->where('manual_partition', false)
            ->whereDoesntHave('woBushingProcesses')
            ->whereDoesntHave('machiningWorkSteps')
            ->where(fn ($query) => $query->whereNull('repair_order')->orWhereRaw("TRIM(repair_order) = ''"))
            ->whereNull('date_start')->whereNull('date_finish')->whereNull('date_promise')
            ->whereNull('vendor_id')
            ->where(fn ($query) => $query->whereNull('working_steps_count')->orWhere('working_steps_count', 0))
            ->delete();
    }
}
