<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paint;
use App\Models\Workorder;
use App\Services\PaintIndexRowsBuilder;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PaintController extends Controller
{
    private const QUEUE_ROLES = ['Admin', 'Manager', 'Paint'];
    private const LOST_DELETE_ROLES = ['Admin', 'Manager', 'Paint'];

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
            ->orderByRaw('CASE WHEN paint_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('paint_queue_order', 'asc')
            ->orderBy('number', 'asc')
            ->get();

        $rows = app(PaintIndexRowsBuilder::class)->build($workorders);

        $queuedCount = $rows
            ->filter(static fn ($r) => $r->workorder->paint_queue_order !== null)
            ->pluck('workorder.id')
            ->unique()
            ->count();

        $user = auth()->user();

        $lostParts = Paint::query()
            ->with(['user:id,name', 'media'])
            ->latest()
            ->limit(100)
            ->get();

        return view('admin.paint.index', [
            'rows' => $rows,
            'queuedCount' => $queuedCount,
            'canReorderPaint' => $this->canManageQueue($user),
            'lostParts' => $lostParts,
        ]);
    }

    public function storeLost(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $validated = $request->validate([
            'part_number' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        $paint = Paint::query()->create([
            'user_id' => $user->id,
            'part_number' => $validated['part_number'],
            'serial_number' => $validated['serial_number'] !== null && $validated['serial_number'] !== ''
                ? $validated['serial_number']
                : null,
            'comment' => $validated['comment'] !== null && $validated['comment'] !== ''
                ? $validated['comment']
                : null,
        ]);

        $paint->addMediaFromRequest('photo')->toMediaCollection('lost');

        return response()->json(['success' => true, 'message' => 'Saved']);
    }

    public function destroyLost(Paint $paint): JsonResponse
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        if (! $this->canDeleteLost($user, $paint)) {
            abort(403);
        }

        $mediaIds = $paint->media()->pluck('id')->map(static fn ($id) => (int) $id)->values()->all();
        activity('paint_lost_delete')
            ->causedBy($user)
            ->performedOn($paint)
            ->event('deleted')
            ->withProperties([
                'paint_id' => (int) $paint->id,
                'part_number' => (string) ($paint->part_number ?? ''),
                'serial_number' => (string) ($paint->serial_number ?? ''),
                'comment' => (string) ($paint->comment ?? ''),
                'owner_user_id' => (int) ($paint->user_id ?? 0),
                'media_ids' => $mediaIds,
                'source' => 'admin.paint.index',
            ])
            ->log('Paint lost image deleted');

        $paint->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $this->canManageQueue($user)) {
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

        $normalizePaintQueueWoIds = static function (array $idList): array {
            $idList = array_values(array_unique(array_filter(
                array_map(static fn ($v) => (int) $v, $idList),
                static fn (int $id) => $id > 0
            )));
            sort($idList, SORT_NUMERIC);

            return $idList;
        };

        $expectedIds = $normalizePaintQueueWoIds(
            Workorder::query()
                ->whereNotNull('approve_at')
                ->whereNull('done_at')
                ->where('is_draft', 0)
                ->whereNotNull('paint_queue_order')
                ->pluck('id')
                ->all()
        );

        $incomingIds = $normalizePaintQueueWoIds($ids);

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
        if (! $this->canManageQueue($user)) {
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
        if (! $this->canManageQueue($user)) {
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

    private function canManageQueue($user): bool
    {
        return $user !== null
            && $user->roleIs(self::QUEUE_ROLES)
            && $user->can('feature.paint');
    }

    private function canDeleteLost($user, Paint $paint): bool
    {
        if ($user === null) {
            return false;
        }

        return (int) $paint->user_id === (int) $user->id
            || $user->roleIs(self::LOST_DELETE_ROLES);
    }

}
