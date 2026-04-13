<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TdrProcess;
use App\Models\Workorder;
use Illuminate\Support\Collection;

/**
 * Строки таблицы Paint (admin + mobile): List, детали по P/N, placeholder, позиция очереди.
 */
final class PaintIndexRowsBuilder
{
    /**
     * @param  Collection<int, Workorder>  $workorders  Уже отсортированы как на экране.
     * @return Collection<int, object>
     */
    public function build(Collection $workorders): Collection
    {
        $rows = $workorders->flatMap(function (Workorder $wo) {
            $wo->unsetRelation('tdrs');
            $wo->load([
                'tdrs' => function ($q) {
                    $q->with([
                        'component:id,part_number,name,ipl_num',
                        'tdrProcesses.processName',
                        'tdrProcesses.dateStartUpdatedBy:id,name',
                        'tdrProcesses.dateFinishUpdatedBy:id,name',
                    ]);
                },
            ]);

            $paintProcesses = $this->collectExactPaintProcesses($wo);

            $active = $paintProcesses->filter(static fn (TdrProcess $tp) => ! ($tp->ignore_row ?? false))->values();
            $forAgg = $active->isNotEmpty() ? $active : $paintProcesses->values();

            $stdListTdrProcesses = app(WorkorderStdListProcessesService::class)->resolveForWorkorder($wo);
            $listEditProcess = $stdListTdrProcesses !== null ? $stdListTdrProcesses->get('paint') : null;
            $showListRow = $listEditProcess !== null && ! ((bool) ($listEditProcess->ignore_row ?? false));

            $detailRows = $active
                ->filter(static function (TdrProcess $tp) use ($listEditProcess) {
                    if (trim((string) ($tp->tdr?->component?->part_number ?? '')) === '') {
                        return false;
                    }
                    if ($listEditProcess !== null && (int) $tp->id === (int) $listEditProcess->id) {
                        return false;
                    }

                    return true;
                })
                ->map(static function (TdrProcess $tp) use ($wo) {
                    $detailPn = trim((string) ($tp->tdr?->component?->part_number ?? ''));

                    return (object) [
                        'workorder' => $wo,
                        'detail_label' => $detailPn,
                        'date_start' => $tp->date_start,
                        'date_finish' => $tp->date_finish,
                        'edit_paint_process' => $tp,
                        'paint_queue_position' => null,
                        'is_queue_master' => false,
                    ];
                })
                ->values();

            if ($showListRow) {
                $baseRow = (object) [
                    'workorder' => $wo,
                    'detail_label' => 'List',
                    'date_start' => $listEditProcess->date_start,
                    'date_finish' => $listEditProcess->date_finish,
                    'edit_paint_process' => $listEditProcess,
                    'paint_queue_position' => null,
                    'is_queue_master' => false,
                ];

                return collect([$baseRow])->concat($detailRows);
            }

            return $detailRows;
        })->values();

        $woIdsRendered = $rows->map(static fn ($r) => (int) $r->workorder->id)->unique();
        foreach ($workorders as $wo) {
            if ($wo->paint_queue_order === null) {
                continue;
            }
            $wid = (int) $wo->id;
            if ($woIdsRendered->contains($wid)) {
                continue;
            }
            $rows->push((object) [
                'workorder' => $wo,
                'detail_label' => '—',
                'date_start' => null,
                'date_finish' => null,
                'edit_paint_process' => null,
                'paint_queue_position' => null,
                'is_queue_master' => false,
            ]);
        }

        $withQueue = $rows->filter(static fn ($r) => $r->workorder->paint_queue_order !== null)->values();
        $withoutQueue = $rows
            ->filter(static fn ($r) => $r->workorder->paint_queue_order === null)
            ->sortByDesc(static fn ($r) => (int) $r->workorder->number)
            ->values();

        $pos = 0;
        $seenWo = [];

        return $withQueue->concat($withoutQueue)->map(static function ($row) use (&$pos, &$seenWo) {
            $woId = (int) $row->workorder->id;
            if (! isset($seenWo[$woId])) {
                $seenWo[$woId] = true;
                $row->is_queue_master = true;
                if ($row->workorder->paint_queue_order !== null) {
                    $pos++;
                }
            }

            if ($row->workorder->paint_queue_order !== null) {
                $row->paint_queue_position = $pos;
            }

            return $row;
        });
    }

    /**
     * Все строки Paint для WO (как на paint.index) имеют и start, и finish — как data-paint-closed.
     */
    public function isWorkorderPaintFullyClosed(Workorder $wo): bool
    {
        $rows = $this->build(collect([$wo]));
        if ($rows->isEmpty()) {
            return false;
        }

        return $rows->every(static function (object $row): bool {
            return $row->date_start !== null && $row->date_finish !== null;
        });
    }

    /**
     * @return Collection<int, TdrProcess>
     */
    private function collectExactPaintProcesses(Workorder $wo): Collection
    {
        $out = collect();
        foreach ($wo->tdrs as $tdr) {
            foreach ($tdr->tdrProcesses as $tp) {
                $nameLower = strtolower(trim((string) ($tp->processName?->name ?? '')));
                if ($nameLower !== 'paint') {
                    continue;
                }
                $tp->setRelation('tdr', $tdr);
                $out->push($tp);
            }
        }

        return $out;
    }
}
