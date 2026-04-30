<?php

namespace App\Services;

use App\Models\ProcessName;
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
        $machiningProcessNameId = ProcessName::query()->where('name', 'Machining')->value('id');

        $rows = $workorders->flatMap(function (Workorder $wo) use ($machiningProcessNameId) {
            $machiningProcesses = $this->collectMachiningProcessesForRow($wo, $machiningProcessNameId);

            $active = $machiningProcesses
                ->filter(fn (TdrProcess $tp) => ! ($tp->ignore_row ?? false))
                ->filter(fn (TdrProcess $tp) => $this->machiningDateValuePresent($tp->date_start ?? null))
                ->values();

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
                        'date_start' => null,
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
     * Открытые строки: в очереди — сначала machining_queue_order, затем номер WO, затем дата старта
     * (без старта в конце группы WO), затем detail. Вне очереди — номер WO, дата старта, detail.
     * Без глобального «все со стартом выше всех без старта», чтобы части одного WO не разъезжались.
     *
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
            if ($queuedBucket) {
                $qa = (int) ($a->workorder->machining_queue_order ?? PHP_INT_MAX);
                $qb = (int) ($b->workorder->machining_queue_order ?? PHP_INT_MAX);
                $byQ = $qa <=> $qb;
                if ($byQ !== 0) {
                    return $byQ;
                }
            }

            // Один WO подряд; внутри WO — по дате старта (rowMachiningStartTimestamp: без даты = в конец).
            $byWo = ((int) $a->workorder->number) <=> ((int) $b->workorder->number);
            if ($byWo !== 0) {
                return $byWo;
            }

            $byTs = $this->rowMachiningStartTimestamp($a) <=> $this->rowMachiningStartTimestamp($b);
            if ($byTs !== 0) {
                return $byTs;
            }

            return strcmp((string) ($a->detail_label ?? ''), (string) ($b->detail_label ?? ''));
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

        $n = (int) ($parent->working_steps_count ?? 0);

        $effFinish = $parent->date_finish ?? null;
        if ($n >= 1) {
            $parent->loadMissing('machiningWorkSteps');
            if (! $this->machiningDateValuePresent($effFinish)) {
                $effFinish = $parent->machiningWorkSteps->firstWhere('step_index', $n)?->date_finish;
            }

            $stepOne = $parent->machiningWorkSteps->firstWhere('step_index', 1);
            if ($stepOne !== null && $this->machiningDateValuePresent($stepOne->date_start ?? null)) {
                $row->date_start = $stepOne->date_start;
            } elseif (
                $stepOne !== null
                && ! $this->machiningDateValuePresent($stepOne->date_start ?? null)
                && $this->machiningDateValuePresent($parent->date_start ?? null)
            ) {
                /** Нет work start на шаге 1 — Date Sent родителя (в т.ч. только отправлено, без finish по шагам). */
                $row->date_start = $parent->date_start;
            } else {
                $row->date_start = null;
            }
        } else {
            $row->date_start = null;
        }

        if ($this->machiningDateValuePresent($effFinish)) {
            $row->date_finish = $effFinish;
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
    private function collectMachiningProcessesForRow(Workorder $wo, ?int $machiningProcessNameId): Collection
    {
        $out = collect();
        if ($machiningProcessNameId === null) {
            return $out;
        }
        foreach ($wo->tdrs as $tdr) {
            foreach ($tdr->tdrProcesses as $tp) {
                if ((int) ($tp->process_names_id ?? 0) !== $machiningProcessNameId) {
                    continue;
                }
                $tp->setRelation('tdr', $tdr);
                $out->push($tp);
            }
        }

        return $out;
    }
}
