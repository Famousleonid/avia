<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MachiningWorkStep;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use App\Models\Workorder;
use App\Services\MachiningListingRowsBuilder;
use App\Services\MachiningWorkStepsService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachiningController extends Controller
{

    public function index(): View
    {
        return view('admin.machining.index', $this->machiningIndexData());
    }

    /**
     * HTML tbody content + queue count for live refresh without full page reload.
     */
    public function tableFragment(): JsonResponse
    {
        $data = $this->machiningIndexData();
        $html = view('admin.machining.partials.table-body', [
            'rows' => $data['rows'],
            'machiningLinesPerWo' => $data['machiningLinesPerWo'],
            'machiningMachinists' => $data['machiningMachinists'],
            'canReorderMachining' => $data['canReorderMachining'],
        ])->render();

        return response()->json([
            'html' => $html,
            'queuedCount' => $data['queuedCount'],
        ]);
    }

    /**
     * @return array{
     *     rows: \Illuminate\Support\Collection,
     *     machiningLinesPerWo: \Illuminate\Support\Collection,
     *     queuedCount: int,
     *     machiningMachinists: \Illuminate\Database\Eloquent\Collection<int, User>,
     *     canReorderMachining: bool
     * }
     */
    private function machiningIndexData(): array
    {
        $workorders = Workorder::query()
            ->whereNull('done_at')
            ->whereMachiningHasDateSent()
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

        $rows = app(MachiningListingRowsBuilder::class)->build($workorders);

        $machiningLinesPerWo = $rows->countBy(static fn ($r) => (int) $r->workorder->id);

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

        return [
            'rows' => $rows,
            'machiningLinesPerWo' => $machiningLinesPerWo,
            'queuedCount' => $queuedCount,
            'canReorderMachining' => $user !== null && $user->roleIs(['Admin', 'Manager', 'Team Leader']),
            'machiningMachinists' => $machiningMachinists,
        ];
    }

    public function updateMachiningWorkStep(Request $request, MachiningWorkStep $machiningWorkStep): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'machinist_user_id' => 'sometimes|nullable|integer|exists:users,id',
            'date_finish' => 'sometimes|nullable|date',
            'date_start' => 'sometimes|nullable|date',
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
                $request->exists('date_start'),
                $request->input('date_start'),
            );
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        $machiningWorkStep->refresh();
        $parent = app(MachiningWorkStepsService::class)->resolveParent($machiningWorkStep);

        return response()->json([
            'success' => true,
            'date_start' => $machiningWorkStep->date_start?->format('Y-m-d'),
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

        if ($wo->done_at !== null || (int) $wo->is_draft !== 0) {
            return response()->json(['success' => false, 'message' => 'Workorder must not be completed or draft'], 422);
        }

        if (! Workorder::query()->whereKey($wo->id)->whereMachiningHasDateSent()->exists()) {
            return response()->json(['success' => false, 'message' => 'Machining Date Sent is required'], 422);
        }

        if ($wo->machining_queue_order !== null) {
            return response()->json(['success' => false, 'message' => 'Already in machining queue'], 422);
        }

        $max = Workorder::query()
            ->whereNull('done_at')
            ->whereMachiningHasDateSent()
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
            ->whereNull('done_at')
            ->whereMachiningHasDateSent()
            ->first();

        if ($wo === null) {
            return response()->json(['success' => false, 'message' => 'Workorder not found'], 422);
        }

        $queuedIds = Workorder::query()
            ->whereNull('done_at')
            ->whereMachiningHasDateSent()
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
}
