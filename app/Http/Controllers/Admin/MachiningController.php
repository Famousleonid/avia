<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MachiningWorkStep;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use App\Models\Workorder;
use App\Services\MachiningBushingRowsBuilder;
use App\Services\MachiningWorkorderQueueRelease;
use App\Services\MachiningWorkStepsService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
                        'tdrProcesses.machiningWorkSteps.machinist:id,name',
                    ]);
                },
                'woBushingProcesses' => function ($q) {
                    $q->with([
                        'line.component',
                        'process.process_name',
                        'batch.machiningWorkSteps.machinist:id,name',
                        'machiningWorkSteps.machinist:id,name',
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
                        'tdrProcesses.machiningWorkSteps.machinist:id,name',
                    ]);
                },
                'woBushingProcesses' => function ($q) {
                    $q->with([
                        'line.component',
                        'process.process_name',
                        'batch.machiningWorkSteps.machinist:id,name',
                        'machiningWorkSteps.machinist:id,name',
                    ]);
                },
            ]);

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

        $machiningMachinists = User::query()
            ->whereHas('role', static fn ($q) => $q->where('name', 'Machining'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.machining.index', [
            'rows' => $rows,
            'queuedCount' => $queuedCount,
            'canReorderMachining' => $user !== null && $user->roleIs(['Admin', 'Manager']),
            'machiningMachinists' => $machiningMachinists,
        ]);
    }

    public function updateMachiningWorkStep(Request $request, MachiningWorkStep $machiningWorkStep): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'machinist_user_id' => 'sometimes|nullable|integer|exists:users,id',
            'date_finish' => 'sometimes|nullable|date',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        try {
            app(MachiningWorkStepsService::class)->updateStepFromRequest(
                $machiningWorkStep,
                $request->exists('machinist_user_id'),
                $request->input('machinist_user_id'),
                $request->exists('date_finish'),
                $request->input('date_finish'),
            );
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        $machiningWorkStep->refresh();
        $parent = app(MachiningWorkStepsService::class)->resolveParent($machiningWorkStep);

        return response()->json([
            'success' => true,
            'date_finish' => $machiningWorkStep->date_finish?->format('Y-m-d'),
            'machinist_user_id' => $machiningWorkStep->machinist_user_id,
            'parent_date_finish' => $parent?->date_finish?->format('Y-m-d'),
        ]);
    }

    public function updateTdrWorkingStepsCount(Request $request, TdrProcess $tdrProcess): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'working_steps_count' => 'required|integer|min:1|max:50',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        try {
            app(MachiningWorkStepsService::class)->syncWorkingStepsCount($tdrProcess, (int) $request->input('working_steps_count'));
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        $tdrProcess->refresh();

        return response()->json(['success' => true, 'working_steps_count' => $tdrProcess->working_steps_count]);
    }

    public function updateBatchWorkingStepsCount(Request $request, WoBushingBatch $woBushingBatch): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'working_steps_count' => 'required|integer|min:1|max:50',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        try {
            app(MachiningWorkStepsService::class)->syncWorkingStepsCount($woBushingBatch, (int) $request->input('working_steps_count'));
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        $woBushingBatch->refresh();

        return response()->json(['success' => true, 'working_steps_count' => $woBushingBatch->working_steps_count]);
    }

    public function updateProcessWorkingStepsCount(Request $request, WoBushingProcess $woBushingProcess): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'working_steps_count' => 'required|integer|min:1|max:50',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        try {
            app(MachiningWorkStepsService::class)->syncWorkingStepsCount($woBushingProcess, (int) $request->input('working_steps_count'));
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        $woBushingProcess->refresh();

        return response()->json(['success' => true, 'working_steps_count' => $woBushingProcess->working_steps_count]);
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
     * Полностью закрытый по machining WO не должен оставаться в machining_queue_order: иначе строки
     * попадают в блок «в очереди» и висят под w105380, а в колонке № для закрытого показывается «—».
     * Чиним БД и in-memory workorder при открытии индекса (если снятие не произошло при сохранении дат).
     *
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
     * В очереди: закрытый WO = machiningFullyClosed (внизу по number WO).
     * Без очереди: внизу каждая строка, у которой есть старт и финиш (несколько линий одного WO могут
     * быть вверху и внизу независимо); порядок по number WO, затем detail_label.
     * Открытые: в очереди — сначала с датой старта, без даты — ниже; внутри группы с датой
     * сортировка по machining_queue_order (№), затем по date_start. Вне очереди — с датой /
     * без даты, затем date_start и number.
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

    /**
     * Выровнять date_start / date_finish строки с родителем; при шагах — подтянуть финиш с последнего шага,
     * если у родителя в памяти ещё пусто (иначе сортировка «закрытых» вниз ломается).
     */
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
