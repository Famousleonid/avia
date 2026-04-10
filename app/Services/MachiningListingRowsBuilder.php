<?php

namespace App\Services;

use App\Models\TdrProcess;
use App\Models\Workorder;
use Illuminate\Support\Collection;

/**
 * Строки списка Machining (TDR + бушинги), порядок очереди — как на admin.machining.
 */
final class MachiningListingRowsBuilder
{
    /**
     * @param  Collection<int, Workorder>  $workorders  уже с нужными eager-load (tdrs, woBushingProcesses, …)
     * @return Collection<int, object>
     */
    public function build(Collection $workorders): Collection
    {
        $rows = $workorders->flatMap(function (Workorder $wo) {
            $machiningProcesses = $this->collectMachiningProcessesForRow($wo);

            $active = $machiningProcesses->filter(fn (TdrProcess $tp) => ! ($tp->ignore_row ?? false))->values();

            $tdrRows = $active
                ->filter(static fn (TdrProcess $tp) => trim((string) ($tp->tdr?->component?->part_number ?? '')) !== '')
                ->map(static function (TdrProcess $tp) use ($wo) {
                    $detailPn = trim((string) ($tp->tdr?->component?->part_number ?? ''));
                    $detailNm = trim((string) ($tp->tdr?->component?->name ?? ''));

                    return (object) [
                        'workorder' => $wo,
                        'row_source' => 'tdr',
                        'detail_label' => $detailPn,
                        'detail_name' => $detailNm,
                        'date_start' => $tp->date_start,
                        'date_finish' => $tp->date_finish,
                        'edit_machining_process' => $tp,
                        'machining_queue_position' => null,
                        'is_queue_master' => false,
                        'bushing_batch' => null,
                        'bushing_process' => null,
                    ];
                })
                ->values();

            $bushingRows = MachiningBushingRowsBuilder::forWorkorder($wo);

            return $tdrRows->concat($bushingRows);
        })->values();

        $rows->each(fn (object $row) => $this->applyMachiningParentDatesToRow($row));

        $this->syncMachiningQueueReleaseForFullyClosedWorkorders($rows);

        $withQueue = $rows->filter(static fn ($r) => $r->workorder->machining_queue_order !== null)->values();
        $withoutQueue = $rows
            ->filter(static fn ($r) => $r->workorder->machining_queue_order === null)
            ->sortBy(static fn ($r) => (int) $r->workorder->number)
            ->values();

        $withQueue = $this->sortMachiningRowsBucket($withQueue, true);
        $withoutQueue = $this->sortMachiningRowsBucket($withoutQueue, false);

        $pos = 0;
        $seenWo = [];

        return $withQueue->concat($withoutQueue)->map(static function ($row) use (&$pos, &$seenWo) {
            $woId = (int) $row->workorder->id;
            if (! isset($seenWo[$woId])) {
                $seenWo[$woId] = true;
                $row->is_queue_master = true;
                if ($row->workorder->machining_queue_order !== null) {
                    $pos++;
                }
            }

            if ($row->workorder->machining_queue_order !== null) {
                $row->machining_queue_position = $pos;
            }

            return $row;
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function syncMachiningQueueReleaseForFullyClosedWorkorders(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $release = app(MachiningWorkorderQueueRelease::class);
        foreach ($rows->groupBy(static fn ($r) => (int) $r->workorder->id) as $woId => $_) {
            $woModel = Workorder::query()->find((int) $woId);
            if ($woModel === null || ! $release->machiningFullyClosed($woModel)) {
                continue;
            }
            $release->releaseIfFullyClosed($woModel);
        }

        foreach ($rows->groupBy(static fn ($r) => (int) $r->workorder->id) as $group) {
            $wo = $group->first()?->workorder;
            if ($wo !== null) {
                $wo->refresh();
            }
        }
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function sortMachiningRowsBucket(Collection $rows, bool $queuedBucket): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $rows = $rows->values();
        $queueRelease = app(MachiningWorkorderQueueRelease::class);

        if ($queuedBucket) {
            $woFullyDone = [];
            foreach ($rows->groupBy(static fn ($r) => (int) $r->workorder->id) as $woId => $_) {
                $woModel = Workorder::query()->find((int) $woId);
                $woFullyDone[(int) $woId] = $woModel !== null && $queueRelease->machiningFullyClosed($woModel);
            }
            $open = $rows->filter(fn ($r) => ! $woFullyDone[(int) $r->workorder->id])->values();
            $done = $rows->filter(fn ($r) => $woFullyDone[(int) $r->workorder->id])->values();
        } else {
            $open = $rows->filter(fn ($r) => ! ($this->rowHasMachiningDateStart($r) && $this->rowHasMachiningDateFinish($r)))->values();
            $done = $rows->filter(fn ($r) => $this->rowHasMachiningDateStart($r) && $this->rowHasMachiningDateFinish($r))->values();
        }

        $openSorted = $open->sort(function (object $a, object $b) use ($queuedBucket): int {
            $byStart = ($this->rowHasMachiningDateStart($a) ? 0 : 1) <=> ($this->rowHasMachiningDateStart($b) ? 0 : 1);
            if ($byStart !== 0) {
                return $byStart;
            }

            if ($queuedBucket) {
                $qa = (int) ($a->workorder->machining_queue_order ?? PHP_INT_MAX);
                $qb = (int) ($b->workorder->machining_queue_order ?? PHP_INT_MAX);
                $byQ = $qa <=> $qb;
                if ($byQ !== 0) {
                    return $byQ;
                }
            }

            $byTs = $this->rowMachiningStartTimestamp($a) <=> $this->rowMachiningStartTimestamp($b);
            if ($byTs !== 0) {
                return $byTs;
            }

            if (! $queuedBucket) {
                $byNum = ((int) $a->workorder->number) <=> ((int) $b->workorder->number);
                if ($byNum !== 0) {
                    return $byNum;
                }
            }

            return ((int) $a->workorder->number) <=> ((int) $b->workorder->number);
        })->values();

        $doneSorted = $done->sort(function (object $a, object $b): int {
            $byWo = ((int) $a->workorder->number) <=> ((int) $b->workorder->number);
            if ($byWo !== 0) {
                return $byWo;
            }

            return strcmp((string) ($a->detail_label ?? ''), (string) ($b->detail_label ?? ''));
        })->values();

        return $openSorted->concat($doneSorted);
    }

    private function rowHasMachiningDateStart(object $row): bool
    {
        $s = $row->date_start ?? null;
        if ($s === null) {
            return false;
        }
        if ($s instanceof \DateTimeInterface) {
            return true;
        }

        return trim((string) $s) !== '';
    }

    private function rowMachiningStartTimestamp(object $row): int
    {
        $s = $row->date_start ?? null;
        if ($s === null) {
            return PHP_INT_MAX;
        }
        if ($s instanceof \DateTimeInterface) {
            return (int) $s->format('U');
        }

        return PHP_INT_MAX;
    }

    private function applyMachiningParentDatesToRow(object $row): void
    {
        $parent = $row->edit_machining_process ?? $row->bushing_batch ?? $row->bushing_process ?? null;
        if ($parent === null) {
            return;
        }

        if ($parent->date_start) {
            $row->date_start = $parent->date_start;
        }

        $finish = $parent->date_finish;
        $n = (int) ($parent->working_steps_count ?? 0);
        if ($n >= 1) {
            $parent->loadMissing('machiningWorkSteps');
            if (! $this->machiningDateValuePresent($finish)) {
                $last = $parent->machiningWorkSteps->firstWhere('step_index', $n);
                $finish = $last?->date_finish;
            }
        }

        if ($this->machiningDateValuePresent($finish)) {
            $row->date_finish = $finish;
        }
    }

    private function rowHasMachiningDateFinish(object $row): bool
    {
        if ($this->machiningDateValuePresent($row->date_finish ?? null)) {
            return true;
        }

        $parent = $row->edit_machining_process ?? $row->bushing_batch ?? $row->bushing_process ?? null;
        if ($parent === null) {
            return false;
        }

        if ($this->machiningDateValuePresent($parent->date_finish ?? null)) {
            return true;
        }

        $n = (int) ($parent->working_steps_count ?? 0);
        if ($n < 1) {
            return false;
        }

        $parent->loadMissing('machiningWorkSteps');
        $last = $parent->machiningWorkSteps->firstWhere('step_index', $n);

        return $last !== null && $this->machiningDateValuePresent($last->date_finish ?? null);
    }

    private function machiningDateValuePresent(mixed $d): bool
    {
        if ($d === null) {
            return false;
        }
        if ($d instanceof \DateTimeInterface) {
            return true;
        }

        return trim((string) $d) !== '';
    }

    /**
     * @return Collection<int, TdrProcess>
     */
    private function collectMachiningProcessesForRow(Workorder $wo): Collection
    {
        $out = collect();
        foreach ($wo->tdrs as $tdr) {
            foreach ($tdr->tdrProcesses as $tp) {
                $name = trim((string) ($tp->processName?->name ?? ''));
                if ($name !== 'Machining') {
                    continue;
                }
                $tp->setRelation('tdr', $tdr);
                $out->push($tp);
            }
        }

        return $out;
    }
}
