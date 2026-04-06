<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Workorder;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PaintController extends Controller
{
    public function index(): View
    {
        $stdPaintLower = strtolower(trim(WorkorderStdListProcessesService::NAME_BY_KEY['paint']));

        $workorders = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->with([
                'user:id,name',
                'customer:id,name',
                'unit' => function ($q) {
                    $q->select('id', 'part_number', 'name', 'manual_id')
                        ->with(['manual.plane:id,type']);
                },
                'tdrs' => function ($q) {
                    $q->with([
                        'component:id,part_number,name,ipl_num',
                        'tdrProcesses.processName',
                    ]);
                },
            ])
            ->orderByRaw('CASE WHEN paint_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('paint_queue_order', 'asc')
            ->orderBy('number', 'asc')
            ->get();

        $stdListProcesses = app(WorkorderStdListProcessesService::class);

        $rows = $workorders->map(function (Workorder $wo) use ($stdPaintLower, $stdListProcesses) {
            try {
                $stdListProcesses->resolveForWorkorder($wo);
            } catch (\Throwable $e) {
                // overhaul / manual: не все WO подходят под STD lists
            }

            $wo->unsetRelation('tdrs');
            $wo->load([
                'tdrs' => function ($q) {
                    $q->with([
                        'component:id,part_number,name,ipl_num',
                        'tdrProcesses.processName',
                    ]);
                },
            ]);

            $paintProcesses = $this->collectPaintProcessesForRow($wo, $stdPaintLower);

            if ($paintProcesses->isEmpty()) {
                $dbPaint = $this->findAnyPaintTdrProcessForWorkorder((int) $wo->id);
                if ($dbPaint !== null) {
                    $paintProcesses = collect([$dbPaint]);
                } else {
                    $ensured = $this->ensureStdPaintTdrProcess($wo);
                    if ($ensured !== null) {
                        $paintProcesses = collect([$ensured]);
                    }
                }
            }

            $active = $paintProcesses->filter(fn (TdrProcess $tp) => ! ($tp->ignore_row ?? false));
            $forAgg = $active->isNotEmpty() ? $active : $paintProcesses;

            $starts = $forAgg->pluck('date_start')->filter();
            $finishes = $forAgg->pluck('date_finish')->filter();

            $detailPns = $paintProcesses
                ->map(function (TdrProcess $tp) {
                    return trim((string) ($tp->tdr?->component?->part_number ?? ''));
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $editPaintProcess = null;
            if ($forAgg->isNotEmpty()) {
                $editPaintProcess = $forAgg
                    ->sortBy(function (TdrProcess $p) {
                        return [(int) ($p->sort_order ?? 0), (int) $p->id];
                    })
                    ->values()
                    ->first();
            }

            return (object) [
                'workorder' => $wo,
                'detail_pns' => $detailPns,
                'date_start' => $starts->isNotEmpty() ? $starts->min() : null,
                'date_finish' => $finishes->isNotEmpty() ? $finishes->max() : null,
                'edit_paint_process' => $editPaintProcess,
                'paint_queue_position' => null,
            ];
        })->values();

        $withQueue = $rows->filter(static fn ($r) => $r->workorder->paint_queue_order !== null)->values();
        $withoutQueue = $rows
            ->filter(static fn ($r) => $r->workorder->paint_queue_order === null)
            ->sortBy(static fn ($r) => (int) $r->workorder->number)
            ->values();

        $pos = 0;
        $rows = $withQueue->concat($withoutQueue)->map(static function ($row) use (&$pos) {
            if ($row->workorder->paint_queue_order !== null) {
                $pos++;
                $row->paint_queue_position = $pos;
            }

            return $row;
        });

        $queuedCount = $rows->filter(static fn ($r) => $r->workorder->paint_queue_order !== null)->count();

        $user = auth()->user();

        return view('admin.paint.index', [
            'rows' => $rows,
            'queuedCount' => $queuedCount,
            'canReorderPaint' => $user !== null && $user->roleIs(['Admin', 'Manager']),
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user === null || ! $user->roleIs(['Admin', 'Manager'])) {
            abort(403);
        }

        $raw = $request->input('workorder_ids');
        if (! is_array($raw)) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 422);
        }

        $ids = array_values(array_filter(
            array_map(static fn ($id) => (int) $id, $raw),
            static fn (int $id) => $id > 0
        ));

        if ($ids === []) {
            return response()->json(['success' => false, 'message' => 'Empty list'], 422);
        }

        $expectedIds = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->whereNotNull('paint_queue_order')
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $incomingIds = collect($ids)->map(static fn ($id) => (int) $id)->sort()->values()->all();

        if ($expectedIds !== $incomingIds) {
            return response()->json(['success' => false, 'message' => 'Invalid workorder list'], 422);
        }

        foreach ($ids as $position => $id) {
            Workorder::whereKey((int) $id)->update(['paint_queue_order' => (int) $position]);
        }

        return response()->json(['success' => true]);
    }

    public function addToQueue(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user === null || ! $user->roleIs(['Admin', 'Manager'])) {
            abort(403);
        }

        $number = (int) $request->input('number');
        if ($number <= 0) {
            return response()->json(['success' => false, 'message' => 'Enter workorder number'], 422);
        }

        $wo = Workorder::query()->where('number', $number)->first();
        if ($wo === null) {
            return response()->json(['success' => false, 'message' => 'Workorder not found'], 422);
        }

        if ($wo->approve_at === null || $wo->done_at !== null || (int) $wo->is_draft !== 0) {
            return response()->json(['success' => false, 'message' => 'Workorder must be approved, not completed, not draft'], 422);
        }

        if ($wo->paint_queue_order !== null) {
            return response()->json(['success' => false, 'message' => 'Already in paint queue'], 422);
        }

        $max = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->whereNotNull('paint_queue_order')
            ->max('paint_queue_order');

        $next = $max === null ? 0 : ((int) $max + 1);
        $wo->update(['paint_queue_order' => $next]);

        return response()->json(['success' => true, 'message' => 'Added']);
    }

    public function setPosition(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user === null || ! $user->roleIs(['Admin', 'Manager'])) {
            abort(403);
        }

        $wid = (int) $request->input('workorder_id');
        $pos = (int) $request->input('position');
        if ($wid <= 0 || $pos < 0) {
            return response()->json(['success' => false, 'message' => 'Invalid data'], 422);
        }

        $wo = Workorder::query()
            ->whereKey($wid)
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->first();

        if ($wo === null) {
            return response()->json(['success' => false, 'message' => 'Workorder not found'], 422);
        }

        $queuedIds = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->whereNotNull('paint_queue_order')
            ->orderBy('paint_queue_order', 'asc')
            ->orderBy('number', 'asc')
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        $inQueue = in_array($wid, $queuedIds, true);

        if ($pos === 0) {
            if (! $inQueue) {
                return response()->json(['success' => true]);
            }

            $rest = array_values(array_filter($queuedIds, static fn (int $id) => $id !== $wid));
            foreach ($rest as $i => $id) {
                Workorder::whereKey($id)->update(['paint_queue_order' => $i]);
            }
            $wo->update(['paint_queue_order' => null]);

            return response()->json(['success' => true]);
        }

        // $pos >= 1: встать в очередь (новая строка) или сменить место
        $list = $inQueue
            ? array_values(array_filter($queuedIds, static fn (int $id) => $id !== $wid))
            : $queuedIds;

        $insertIndex = min(max(0, $pos - 1), count($list));
        array_splice($list, $insertIndex, 0, [$wid]);

        foreach ($list as $i => $id) {
            Workorder::whereKey($id)->update(['paint_queue_order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Сначала процессы покраски кроме STD; если нет — любой процесс с «paint» в имени (включая STD),
     * чтобы даты можно было править у каждой строки, где вообще есть paint-процесс.
     *
     * @return Collection<int, TdrProcess>
     */
    private function collectPaintProcessesForRow(Workorder $wo, string $stdPaintLower): Collection
    {
        $nonStd = $this->collectNonStdPaintProcesses($wo, $stdPaintLower);
        if ($nonStd->isNotEmpty()) {
            return $nonStd;
        }

        return $this->collectAllPaintNamedProcesses($wo);
    }

    /**
     * @return Collection<int, TdrProcess>
     */
    private function collectNonStdPaintProcesses(Workorder $wo, string $stdPaintLower): Collection
    {
        $out = collect();
        foreach ($wo->tdrs as $tdr) {
            foreach ($tdr->tdrProcesses as $tp) {
                $nameLower = strtolower(trim((string) ($tp->processName?->name ?? '')));
                if ($nameLower === '' || strpos($nameLower, 'paint') === false) {
                    continue;
                }
                if ($nameLower === $stdPaintLower) {
                    continue;
                }
                $tp->setRelation('tdr', $tdr);
                $out->push($tp);
            }
        }

        return $out;
    }

    /**
     * Все процессы, в имени которых есть «paint» (в т.ч. STD Paint List).
     *
     * @return Collection<int, TdrProcess>
     */
    private function collectAllPaintNamedProcesses(Workorder $wo): Collection
    {
        $out = collect();
        foreach ($wo->tdrs as $tdr) {
            foreach ($tdr->tdrProcesses as $tp) {
                $nameLower = strtolower(trim((string) ($tp->processName?->name ?? '')));
                if ($nameLower === '' || strpos($nameLower, 'paint') === false) {
                    continue;
                }
                $tp->setRelation('tdr', $tdr);
                $out->push($tp);
            }
        }

        return $out;
    }

    /**
     * Свежий запрос в БД (после firstOrCreate в resolve может понадобиться без полного reload WO).
     */
    private function findAnyPaintTdrProcessForWorkorder(int $workorderId): ?TdrProcess
    {
        return TdrProcess::query()
            ->whereHas('tdr', static fn ($q) => $q->where('workorder_id', $workorderId))
            ->whereHas('processName', static fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%paint%']))
            ->with(['processName', 'tdr.component'])
            ->orderBy('id')
            ->first();
    }

    /**
     * Создаёт строку STD Paint List на первом TDR, чтобы всегда был target для updateDate на странице Paint.
     */
    private function ensureStdPaintTdrProcess(Workorder $wo): ?TdrProcess
    {
        if ($wo->tdrs->isEmpty()) {
            $tdr = Tdr::create([
                'workorder_id' => $wo->id,
                'use_tdr' => true,
                'use_process_forms' => false,
                'serial_number' => 'NSN',
                'assy_serial_number' => ' ',
                'qty' => 1,
            ]);
            $tdr->load(['component:id,part_number,name,ipl_num']);
            $wo->setRelation('tdrs', collect([$tdr]));
        }

        $tdr = $wo->tdrs->sortBy('id')->first();
        if ($tdr === null) {
            return null;
        }

        $name = WorkorderStdListProcessesService::NAME_BY_KEY['paint'];
        $pn = ProcessName::query()->where('name', $name)->first();
        if ($pn === null) {
            return null;
        }

        $tp = TdrProcess::firstOrCreate(
            [
                'tdrs_id' => $tdr->id,
                'process_names_id' => $pn->id,
            ],
            []
        );
        $tp->load(['processName', 'tdr.component']);
        if ($tp->tdr) {
            $tp->tdr->setRelation('workorder', $wo);
        }

        return $tp;
    }
}
