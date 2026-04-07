<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TdrProcess;
use App\Models\Workorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MachiningController extends Controller
{

    public function index(): View
    {
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
            ->orderByRaw('CASE WHEN machining_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('machining_queue_order', 'asc')
            ->orderBy('number', 'asc')
            ->get();

        $rows = $workorders->flatMap(function (Workorder $wo) {
            $wo->unsetRelation('tdrs');
            $wo->load([
                'tdrs' => function ($q) {
                    $q->with([
                        'component:id,part_number,name,ipl_num',
                        'tdrProcesses.processName',
                    ]);
                },
            ]);

            $machiningProcesses = $this->collectMachiningProcessesForRow($wo);

            $active = $machiningProcesses->filter(fn (TdrProcess $tp) => ! ($tp->ignore_row ?? false))->values();

            return $active
                ->filter(static fn (TdrProcess $tp) => trim((string) ($tp->tdr?->component?->part_number ?? '')) !== '')
                ->map(static function (TdrProcess $tp) use ($wo) {
                    $detailPn = trim((string) ($tp->tdr?->component?->part_number ?? ''));

                    return (object) [
                        'workorder' => $wo,
                        'detail_label' => $detailPn,
                        'date_start' => $tp->date_start,
                        'date_finish' => $tp->date_finish,
                        'edit_machining_process' => $tp,
                        'machining_queue_position' => null,
                        'is_queue_master' => false,
                    ];
                })
                ->values();
        })->values();

        $withQueue = $rows->filter(static fn ($r) => $r->workorder->machining_queue_order !== null)->values();
        $withoutQueue = $rows
            ->filter(static fn ($r) => $r->workorder->machining_queue_order === null)
            ->sortBy(static fn ($r) => (int) $r->workorder->number)
            ->values();

        $pos = 0;
        $seenWo = [];
        $rows = $withQueue->concat($withoutQueue)->map(static function ($row) use (&$pos, &$seenWo) {
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

        $queuedCount = $rows
            ->filter(static fn ($r) => $r->workorder->machining_queue_order !== null)
            ->pluck('workorder.id')
            ->unique()
            ->count();

        $user = auth()->user();

        return view('admin.machining.index', [
            'rows' => $rows,
            'queuedCount' => $queuedCount,
            'canReorderMachining' => $user !== null && $user->roleIs(['Admin', 'Manager']),
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
            ->whereNotNull('machining_queue_order')
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
            Workorder::whereKey((int) $id)->update(['machining_queue_order' => (int) $position]);
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

        if ($wo->machining_queue_order !== null) {
            return response()->json(['success' => false, 'message' => 'Already in machining queue'], 422);
        }

        $max = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->whereNotNull('machining_queue_order')
            ->max('machining_queue_order');

        $next = $max === null ? 0 : ((int) $max + 1);
        $wo->update(['machining_queue_order' => $next]);

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
            ->whereNotNull('machining_queue_order')
            ->orderBy('machining_queue_order', 'asc')
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
                Workorder::whereKey($id)->update(['machining_queue_order' => $i]);
            }
            $wo->update(['machining_queue_order' => null]);

            return response()->json(['success' => true]);
        }

        $list = $inQueue
            ? array_values(array_filter($queuedIds, static fn (int $id) => $id !== $wid))
            : $queuedIds;

        $insertIndex = min(max(0, $pos - 1), count($list));
        array_splice($list, $insertIndex, 0, [$wid]);

        foreach ($list as $i => $id) {
            Workorder::whereKey($id)->update(['machining_queue_order' => $i]);
        }

        return response()->json(['success' => true]);
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
