<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Necessary;
use App\Models\Process;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\Training;
use App\Models\User;
use App\Models\WoBushingProcess;
use App\Models\Workorder;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;


class MainController extends Controller
{
    public function index()
    {
        return 1;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'workorder_id' => ['required', 'exists:workorders,id'],
            'task_id'      => ['required', 'exists:tasks,id'],
            'date_start'   => ['nullable', 'date'],
            'date_finish'  => ['nullable', 'date'],
            'ignore_row'   => ['nullable', 'boolean'],
        ]);

        $task = Task::with('generalTask')->findOrFail($data['task_id']);

        $ignoreRow = $request->boolean('ignore_row');
        $hasStart  = $request->has('date_start');
        $hasFinish = $request->has('date_finish');

        $main = Main::query()
            ->where('workorder_id', $data['workorder_id'])
            ->where('task_id', $task->id)
            ->first();

        $resolved = Main::validateAndResolveDates(
            $data,
            $task,
            $main,
            $ignoreRow,
            $hasStart,
            $hasFinish
        );

        if (!$main) {
            $main = new Main();
            $main->workorder_id     = $data['workorder_id'];
            $main->task_id          = $task->id;
            $main->general_task_id  = $task->general_task_id;
        }
        $main->user_id          = auth()->id();
        $main->date_start       = $resolved['date_start'];
        $main->date_finish      = $resolved['date_finish'];
        $main->ignore_row       = $ignoreRow;
        $main->save();

        $wo = $main->workorder ?: Workorder::find($main->workorder_id);

        if ($wo) {
            $wo->recalcGeneralTaskStatuses($main->general_task_id);
            $wo->syncDoneByCompletedTask();
        }

        if ($request->ajax() || $request->expectsJson()) {
            $updatedAt = now()->format('d ') . Str::lower(now()->format('M')) . now()->format(' Y');
            $ignoreMessage = $main->ignore_row
                ? "Row ignored ({$task->name}) {$updatedAt}"
                : "Row restored ({$task->name}) {$updatedAt}";

            return response()->json([
                'success' => true,
                'message' => $request->has('ignore_row') ? $ignoreMessage : 'Record saved.',
                'main_id' => $main->id,
                'date_start' => optional($main->date_start)?->format('Y-m-d'),
                'date_finish' => optional($main->date_finish)?->format('Y-m-d'),
                'ignore_row' => (bool) $main->ignore_row,
                'general_task_all_finished' => $this->isGeneralTaskAllFinished(
                    (int) $main->workorder_id,
                    (int) $main->general_task_id
                ),
            ]);
        }

        return back()->with('success', 'Record saved.');
    }

    public function show(Workorder $workorder, Request $request)
    {
        // Если binding уже возвращает Workorder::withDrafts(), то withDrafts() ниже можно убрать.
        $current_workorder = Workorder::withDrafts()
            ->with([
                'customer', 'user', 'instruction',
                'unit.manual.media',
                'unit.manual.components',
            ])
            ->findOrFail($workorder->id);

        // Keep current WO context in session for AI widget fallback.
        $request->session()->put('ai_current_workorder_context', [
            'id' => (int)$current_workorder->id,
            'number' => (int)$current_workorder->number,
            'manual_id' => (int)($current_workorder->unit?->manual_id ?? 0),
        ]);

        $this->syncWaitingApproveMain($current_workorder);

        $users = User::all();

        $general_tasks = GeneralTask::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tasks = Task::whereIn('general_task_id', $general_tasks->pluck('id'))
            ->orderBy('general_task_id')
            ->orderBy('name')
            ->get();

        $tasksByGeneral = $tasks->groupBy('general_task_id');

        // mains by task
        $mains = Main::with(['user', 'task'])
            ->where('workorder_id', $current_workorder->id)
            ->get();

        $mainsByTask = $mains->keyBy('task_id');

        // Components & TDRs
        $components = collect();

        if ($current_workorder->unit?->manual) {
            $components = $current_workorder->unit->manual
                ->components()
                ->whereHas('tdrs', function ($q) use ($current_workorder ) {
                    $q->where('workorder_id', $current_workorder->id)
                        ->whereHas('tdrProcesses', function ($qq)  {
                        });
                })
                ->with(['tdrs' => function ($q) use ($current_workorder) {
                    $q->where('workorder_id', $current_workorder->id)
                        ->with(['tdrProcesses' => function ($qq)  {
                            $qq->with('processName')->orderBy('id');
                        }])
                        ->orderBy('id');
                }])
                ->orderBy('name')
                ->get();
        }

        // Manual images (thumb/big)
        $manual = optional($current_workorder->unit)->manual;
        $imgThumb = asset('img/noimage.png');
        $imgFull = null;

        if ($manual) {
            $m = $manual->getFirstMedia('manuals');

            if ($m) {
                $imgFull = route('image.show.big', [
                    'mediaId' => $m->id,
                    'modelId' => $manual->id,
                    'mediaName' => 'manuals',
                ]);

                $imgThumb = route('image.show.thumb', [
                    'mediaId' => $m->id,
                    'modelId' => $manual->id,
                    'mediaName' => 'manuals',
                ]);
            }
        }

        $imgThumb = $imgThumb ?? asset('img/noimage.png');

        // Total image count across all configured workorder media groups
        $photoGroups = array_keys(config('workorder_media.groups', ['photos' => 'Photos']));
        $photoTotalCount = collect($photoGroups)->sum(function (string $group) use ($current_workorder) {
            return $current_workorder->getMedia($group)
                ->filter(fn($m) => $m->mime_type && Str::startsWith($m->mime_type, 'image/'))
                ->count();
        });

        // TDR ids
        $tdrIds = Tdr::where('workorder_id', $current_workorder->id)->pluck('id');

        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

        $ordersPartsNew = collect();
        $prl_parts = collect();
        $orderedQty = 0;
        $receivedQty = 0;

        if ($necessary) {
            $ordersPartsNew = Tdr::where('workorder_id', $current_workorder->id)
                ->where('necessaries_id', $necessary->id)
                ->with([
                    'codes',
                    'orderComponent' => function ($query) {
                        $query->select('id', 'name', 'part_number', 'ipl_num');
                    }
                ])
                ->get();

            $prl_parts = Tdr::where('workorder_id', $current_workorder->id)
                ->where('necessaries_id', $necessary->id)
                ->with([
                    'component' => function ($query) {
                        $query->select('id', 'name', 'part_number', 'ipl_num');
                    },
                    'orderComponent' => function ($query) {
                        $query->select('id', 'name', 'part_number', 'ipl_num');
                    }
                ])
                ->get();

            $orderedQty = $prl_parts->sum('qty');
            $receivedQty = $prl_parts->whereNotNull('received')->sum('qty');
        }

        // Training
        $user = Auth::user();
        $user_wo = $current_workorder->user_id;
        $manual_id = $current_workorder->unit->manual_id ?? null;

        $trainings = null;
        if ($manual_id) {
            $form_type = 112;
            $trainings = Training::where('manuals_id', $manual_id)
                ->where('user_id', $user_wo)
                ->where('form_type', $form_type)
                ->orderBy('date_training', 'desc')
                ->first();
        }

        // General mains (task_id null)
        $generalMains = Main::with('user')
            ->where('workorder_id', $current_workorder->id)
            ->whereNull('task_id')
            ->get()
            ->keyBy('general_task_id');

        // calculate finished by general task
        $gtAllFinished = [];

        foreach ($general_tasks as $gt) {
            $taskIds = ($tasksByGeneral[$gt->id] ?? collect())->pluck('id');

            if ($taskIds->isEmpty()) {
                $gtAllFinished[$gt->id] = false;
                continue;
            }

            $gtAllFinished[$gt->id] = $taskIds->every(function ($taskId) use ($mainsByTask) {
                $main = $mainsByTask->get($taskId);

                if (!$main) return false;
                if ($main->ignore_row) return true;

                return !empty($main->date_finish);
            });
        }

        $historyLimit = 10;

        $trainingAuthLatest = Training::query()
            ->where('manuals_id', $manual_id)
            ->where('user_id', auth()->id())
            ->orderByDesc('date_training')
            ->first();

        $trainingHistoryAuth = Training::query()
            ->where('manuals_id', $manual_id)
            ->where('user_id', auth()->id())
            ->orderByDesc('date_training')
            ->limit($historyLimit)
            ->get(['date_training', 'form_type']);

        $trainingWoLatest = Training::query()
            ->where('manuals_id', $manual_id)
            ->where('user_id', $current_workorder->user_id)
            ->orderByDesc('date_training')
            ->first();


        $trainingHistoryWo = Training::query()
            ->where('manuals_id', $manual_id)
            ->where('user_id', $current_workorder->user_id)
            ->orderByDesc('date_training')
            ->limit($historyLimit)
            ->get(['date_training', 'form_type']);

        $stdListTdrProcesses = app(WorkorderStdListProcessesService::class)
            ->resolveForWorkorder($current_workorder);

        $wpCollection = WoBushingProcess::query()
            ->whereHas('line', fn ($q) => $q->where('workorder_id', $current_workorder->id))
            ->with(['line.component', 'process.process_name'])
            ->get();

        $bushingTotalPcs = (int) $wpCollection->sum('qty');

        $bushingProcessGroupedRows = $wpCollection->groupBy('process_id')->map(function ($group) {
            $first = $group->first();
            $p = $first->process;
            $pn = $p?->process_name;
            $prName = trim((string) ($pn->name ?? ''));
            $prNum = trim((string) ($p->process ?? ''));
            $label = trim(($prName !== '' ? $prName : 'Process').($prNum !== '' ? ' '.$prNum : ''));
            $groupKey = $this->resolveBushingProcessGroupKey($prName, $prNum);

            $repairNorm = $group->map(fn (WoBushingProcess $wp) => trim((string) ($wp->repair_order ?? '')))->unique()->values();
            $repairOrderDisplay = $repairNorm->count() === 1 ? $repairNorm->first() : '';
            $repairOrderMixed = $repairNorm->count() > 1;

            $starts = $group->pluck('date_start')->filter();
            $finishes = $group->pluck('date_finish')->filter();
            $sentQty = (int) $group->filter(fn (WoBushingProcess $wp) => !empty($wp->date_start))->sum('qty');
            $finishedQty = (int) $group->filter(fn (WoBushingProcess $wp) => !empty($wp->date_finish))->sum('qty');

            $lineItems = $group->map(function (WoBushingProcess $wp) {
                $c = $wp->line?->component;

                return [
                    'id' => (int) $wp->id,
                    'qty' => (int) $wp->qty,
                    'repair_order' => (string) ($wp->repair_order ?? ''),
                    'date_start' => $wp->date_start,
                    'date_finish' => $wp->date_finish,
                    'part_number' => trim((string) ($c?->part_number ?? '')),
                    'ipl_num' => trim((string) ($c?->ipl_num ?? '')),
                    'name' => trim((string) ($c?->name ?? '')),
                ];
            })->sortBy(fn (array $row) => ($row['part_number'] !== '' ? $row['part_number'] : 'zzz').'|'.$row['ipl_num'])->values();

            return [
                'process_id' => (int) $first->process_id,
                'process_label' => $label,
                'process_group_key' => $groupKey,
                'process_group_label' => $this->bushingProcessGroupLabel($groupKey),
                'total_qty' => (int) $group->sum('qty'),
                'sent_qty' => $sentQty,
                'finished_qty' => $finishedQty,
                'repair_order' => $repairOrderDisplay,
                'repair_order_mixed' => $repairOrderMixed,
                'date_start' => $starts->isNotEmpty() ? $starts->min() : null,
                'date_finish' => $finishes->isNotEmpty() ? $finishes->max() : null,
                'line_items' => $lineItems,
            ];
        })->sortBy('process_label')->values();

        $groupOrderSeven = ['machining', 'stress_relief', 'ndt', 'passivation', 'cad', 'anodizing', 'xylan'];
        $bushingProcessSections = collect($groupOrderSeven)->map(function (string $key) use ($bushingProcessGroupedRows) {
            $rows = $bushingProcessGroupedRows
                ->where('process_group_key', $key)
                ->sortBy('process_label')
                ->values();

            if ($rows->isEmpty()) {
                return null;
            }

            return [
                'group_key' => $key,
                'group_label' => $this->bushingProcessGroupLabel($key),
                'rows' => $rows,
                'sent_total' => (int) $rows->sum('sent_qty'),
                'finished_total' => (int) $rows->sum('finished_qty'),
                'qty_total' => (int) $rows->sum('total_qty'),
            ];
        })->filter()->values();

        $otherRows = $bushingProcessGroupedRows
            ->where('process_group_key', 'other')
            ->sortBy('process_label')
            ->values();
        if ($otherRows->isNotEmpty()) {
            $bushingProcessSections->push([
                'group_key' => 'other',
                'group_label' => $this->bushingProcessGroupLabel('other'),
                'rows' => $otherRows,
                'sent_total' => (int) $otherRows->sum('sent_qty'),
                'finished_total' => (int) $otherRows->sum('finished_qty'),
                'qty_total' => (int) $otherRows->sum('total_qty'),
            ]);
        }

        return view('admin.mains.main', compact(
            'users',
            'current_workorder',
            'mains',
            'general_tasks',
            'tasks',
            'tasksByGeneral',
            'imgThumb',
            'imgFull',
            'manual',
            'components',
            'ordersPartsNew',
            'prl_parts',
            'orderedQty',
            'receivedQty',
            'trainings',
            'user_wo',
            'manual_id',
            'user',
            'generalMains',
            'mainsByTask',
            'gtAllFinished',
            'photoTotalCount',
            'trainingAuthLatest',
            'trainingHistoryAuth',
            'trainingWoLatest',
            'trainingHistoryWo',
            'stdListTdrProcesses',
            'bushingTotalPcs',
            'bushingProcessGroupedRows',
            'bushingProcessSections',
        ));
    }

    protected function resolveBushingProcessGroupKey(string $processName, string $processCode): string
    {
        $haystack = Str::lower(trim($processName.' '.$processCode));

        if ($haystack === '') {
            return 'other';
        }

        if (Str::contains($haystack, 'machining')) {
            return 'machining';
        }
        if (Str::contains($haystack, ['stress', 'bake'])) {
            return 'stress_relief';
        }
        if (Str::contains($haystack, 'ndt')) {
            return 'ndt';
        }
        if (Str::contains($haystack, 'passivation')) {
            return 'passivation';
        }
        if (Str::contains($haystack, ['cad ', 'cad-', 'cadmium', 'cad plate'])) {
            return 'cad';
        }
        if (Str::contains($haystack, 'anodiz')) {
            return 'anodizing';
        }
        if (Str::contains($haystack, 'xylan')) {
            return 'xylan';
        }

        return 'other';
    }

    protected function bushingProcessGroupLabel(string $key): string
    {
        return match ($key) {
            'machining' => 'Machining',
            'stress_relief' => 'Stress Relief',
            'ndt' => 'NDT',
            'passivation' => 'Passivation',
            'cad' => 'CAD',
            'anodizing' => 'Anodizing',
            'xylan' => 'Xylan',
            default => 'Other',
        };
    }

    protected function syncWaitingApproveMain(Workorder $workorder): void
    {
        // === Если апрува нет — чистим старую запись ===
        if (!$workorder->approve_at) {

            $waitingTaskId = Task::where('name', 'Approved')->value('id');

            if (!$waitingTaskId) {
                return;
            }

            $main = Main::where('workorder_id', $workorder->id)
                ->where('task_id', $waitingTaskId)
                ->first();

            if ($main) {
                $main->user_id = null;
                $main->date_finish = null;
                $main->save();
            }

            return;
        }

        // === Получаем задачу Approved ===
        $waitingTask = Task::where('name', 'Approved')->first();
        if (!$waitingTask) {
            return;
        }

        // === Находим или создаём Main ===
        $main = Main::firstOrNew([
            'workorder_id' => $workorder->id,
            'task_id' => $waitingTask->id,
        ]);

        if (
            $main->exists &&
            $main->date_finish === $workorder->approve_at->toDateString()
        ) {
            return; // уже синхронизировано → выходим
        }

        // === Проставляем general_task_id ===
        $main->general_task_id = $waitingTask->general_task_id;

        // === Проставляем user_id ===
        $userId = null;
        if ($workorder->approve_name) {
            $userId = User::where('name', $workorder->approve_name)->value('id');
        }
        $main->user_id = $userId;

        // === Проставляем дату ===
        $main->date_finish = $workorder->approve_at->toDateString();

        $main->save();
    }

    public function edit($id)
    {
        return 1;
    }

    public function update(Request $request, Main $main)
    {
        $oldIgnore = (int) $main->ignore_row;

        $main->loadMissing(['task.generalTask']);
        $task = $main->task;

        $data = $request->validate([
            'date_start'  => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
            'ignore_row'  => ['nullable', 'boolean'],
        ]);

        $taskName = $main->task?->name;

        if ($taskName === 'Approved') {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'This task is locked and cannot be edited.',
                    'errors' => [
                        'date_finish' => ['This task is locked and cannot be edited.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'date_finish' => 'This task is locked and cannot be edited.',
            ]);
        }

        $isRestrictedFinish = in_array($taskName, ['Approved', 'Completed'], true);

        $ignoreRow = $request->boolean('ignore_row');
        $hasStart  = $request->has('date_start');
        $hasFinish = $request->has('date_finish');

        if ($isRestrictedFinish && !auth()->user()->hasAnyRole('Admin|Manager')) {
            unset($data['date_finish']);
            $hasFinish = false;
        }

        if (!$hasStart && !$hasFinish && $oldIgnore === (int) $ignoreRow) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No changes.',
                ]);
            }

            return back();
        }

        $resolved = Main::validateAndResolveDates(
            $data,
            $task,
            $main,
            $ignoreRow,
            $hasStart,
            $hasFinish
        );

        if ($hasStart) {
            $main->date_start = $resolved['date_start'];
        }

        if ($hasFinish) {
            $main->date_finish = $resolved['date_finish'];
        }

        $afterStart  = $main->date_start;
        $afterFinish = $main->date_finish;

        $main->user_id = (empty($afterStart) && empty($afterFinish)) ? null : auth()->id();
        $main->ignore_row = $ignoreRow;

        $main->save();

        $wo = $main->workorder ?: Workorder::find($main->workorder_id);

        if ($wo) {
            $wo->recalcGeneralTaskStatuses($main->general_task_id);
            $wo->syncDoneByCompletedTask();
        }

        if ($request->ajax() || $request->expectsJson()) {
            $taskLabel = $main->task?->name ?? 'Task';
            $updatedAt = now()->format('d ') . Str::lower(now()->format('M')) . now()->format(' Y');
            $ignoreMessage = $main->ignore_row
                ? "Row ignored ({$taskLabel}) {$updatedAt}"
                : "Row restored ({$taskLabel}) {$updatedAt}";

            return response()->json([
                'success' => true,
                'message' => $request->has('ignore_row') ? $ignoreMessage : 'Record updated.',
                'main_id' => $main->id,
                'date_start' => optional($main->date_start)?->format('Y-m-d'),
                'date_finish' => optional($main->date_finish)?->format('Y-m-d'),
                'ignore_row' => (bool) $main->ignore_row,
                'user_name' => $main->user?->name ?? '',
                'general_task_all_finished' => $this->isGeneralTaskAllFinished(
                    (int) $main->workorder_id,
                    (int) $main->general_task_id
                ),
            ]);
        }

        return back()->with('success', 'Record updated.');
    }

    public function destroy($id)
    {

        return redirect()->back();
    }

    private function isGeneralTaskAllFinished(int $workorderId, int $generalTaskId): bool
    {
        $taskIds = Task::query()
            ->where('general_task_id', $generalTaskId)
            ->pluck('id');

        if ($taskIds->isEmpty()) {
            return false;
        }

        $mainsByTask = Main::query()
            ->where('workorder_id', $workorderId)
            ->whereIn('task_id', $taskIds)
            ->get()
            ->keyBy('task_id');

        return $taskIds->every(function ($taskId) use ($mainsByTask) {
            $main = $mainsByTask->get($taskId);
            if (!$main) return false;
            if ($main->ignore_row) return true;
            return !empty($main->date_finish);
        });
    }

    public function updateWoBushingProcessRepairOrder(Request $request, WoBushingProcess $woBushingProcess)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $woBushingProcess->loadMissing('line');
        abort_unless($woBushingProcess->line, 404);

        $request->validate([
            'repair_order' => 'nullable|string|max:255',
        ]);

        $woBushingProcess->repair_order = $request->repair_order;
        $woBushingProcess->save();

        return response()->json([
            'success' => true,
            'user' => auth()->user()?->name ?? 'system',
            'updated_at' => now()->format('d.m.Y H:i'),
        ]);
    }

    public function updateWoBushingProcessDate(Request $request, WoBushingProcess $woBushingProcess)
    {
        $woBushingProcess->loadMissing('line');
        abort_unless($woBushingProcess->line, 404);

        $isAjax = $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $v = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'date_start' => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
        ]);

        if ($v->fails()) {
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => $v->errors()], 422);
            }

            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();
        $currentStart = $woBushingProcess->date_start ? $woBushingProcess->date_start->format('Y-m-d') : null;
        $effectiveStart = array_key_exists('date_start', $data)
            ? ($data['date_start'] ?: null)
            : $currentStart;

        if (! empty($data['date_finish']) && ! $effectiveStart) {
            $errors = ['date_finish' => ['The start date must be filled in before setting the end date.']];
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => $errors], 422);
            }

            return back()->withErrors($errors)->withInput();
        }

        if (! empty($data['date_finish']) && $effectiveStart) {
            if (\Carbon\Carbon::parse($data['date_finish'])->lt(\Carbon\Carbon::parse($effectiveStart))) {
                $errors = ['date_finish' => ['The end date cannot be earlier than the start date.']];
                if ($isAjax) {
                    return response()->json(['success' => false, 'errors' => $errors], 422);
                }

                return back()->withErrors($errors)->withInput();
            }
        }

        if (array_key_exists('date_start', $data)) {
            $woBushingProcess->date_start = $data['date_start'] ?: null;
            if (empty($data['date_start'])) {
                $woBushingProcess->date_finish = null;
            }
        }

        if (array_key_exists('date_finish', $data)) {
            $woBushingProcess->date_finish = $data['date_finish'] ?: null;
        }

        $woBushingProcess->save();

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'user' => auth()->user()?->name ?? 'system',
            ], 200);
        }

        return back()->with('success', 'Bushing process dates updated');
    }

    /**
     * Все wo_bushing_process по WO и типу процесса (для группового редактирования на main).
     *
     * @return \Illuminate\Support\Collection<int, WoBushingProcess>
     */
    protected function woBushingProcessGroupForWorkorder(Workorder $workorder, Process $process)
    {
        return WoBushingProcess::query()
            ->where('process_id', $process->id)
            ->whereHas('line', fn ($q) => $q->where('workorder_id', $workorder->id))
            ->get();
    }

    public function updateWoBushingProcessGroupRepairOrder(Request $request, Workorder $workorder, Process $process)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $rows = $this->woBushingProcessGroupForWorkorder($workorder, $process);
        abort_if($rows->isEmpty(), 404);

        $request->validate([
            'repair_order' => 'nullable|string|max:255',
        ]);

        $val = $request->repair_order;
        foreach ($rows as $wp) {
            $wp->repair_order = $val;
            $wp->save();
        }

        return response()->json([
            'success' => true,
            'user' => auth()->user()?->name ?? 'system',
            'updated_at' => now()->format('d.m.Y H:i'),
        ]);
    }

    public function updateWoBushingProcessGroupDates(Request $request, Workorder $workorder, Process $process)
    {
        $rows = $this->woBushingProcessGroupForWorkorder($workorder, $process);
        abort_if($rows->isEmpty(), 404);

        $isAjax = $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $v = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'date_start' => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
        ]);

        if ($v->fails()) {
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => $v->errors()], 422);
            }

            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        $minExistingStart = $rows->map(fn (WoBushingProcess $r) => $r->date_start)->filter();
        $effectiveStart = array_key_exists('date_start', $data)
            ? ($data['date_start'] ?: null)
            : ($minExistingStart->isNotEmpty() ? $minExistingStart->min()->format('Y-m-d') : null);

        if (! empty($data['date_finish']) && ! $effectiveStart) {
            $errors = ['date_finish' => ['The start date must be filled in before setting the end date.']];
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => $errors], 422);
            }

            return back()->withErrors($errors)->withInput();
        }

        if (! empty($data['date_finish']) && $effectiveStart) {
            if (\Carbon\Carbon::parse($data['date_finish'])->lt(\Carbon\Carbon::parse($effectiveStart))) {
                $errors = ['date_finish' => ['The end date cannot be earlier than the start date.']];
                if ($isAjax) {
                    return response()->json(['success' => false, 'errors' => $errors], 422);
                }

                return back()->withErrors($errors)->withInput();
            }
        }

        foreach ($rows as $woBushingProcess) {
            if (array_key_exists('date_start', $data)) {
                $woBushingProcess->date_start = $data['date_start'] ?: null;
                if (empty($data['date_start'])) {
                    $woBushingProcess->date_finish = null;
                }
            }

            if (array_key_exists('date_finish', $data)) {
                $woBushingProcess->date_finish = $data['date_finish'] ?: null;
            }

            $woBushingProcess->save();
        }

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'user' => auth()->user()?->name ?? 'system',
            ], 200);
        }

        return back()->with('success', 'Bushing process dates updated');
    }

    public function updateRepairOrder(Request $request, \App\Models\TdrProcess $tdrProcess)
    {
        $request->validate([
            'repair_order' => 'nullable|string|max:255',
        ]);

        $tdrProcess->repair_order = $request->repair_order;
        $tdrProcess->user_id = auth()->id();
        $tdrProcess->save();

        return response()->json([
            'success' => true,
            'user' => auth()->user()?->name ?? 'system',
            'updated_at' => now()->format('d.m.Y H:i'),
        ]);
    }

    public function updateIgnoreRow(Request $request, \App\Models\TdrProcess $tdrProcess)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $data = $request->validate([
            'ignore_row' => ['required', 'boolean'],
        ]);

        $tdrProcess->ignore_row = (bool) $data['ignore_row'];
        $tdrProcess->user_id = auth()->id();
        $tdrProcess->save();

        $rowName = $tdrProcess->processName()->value('name') ?? 'Process';
        $updatedAt = now()->format('d ') . Str::lower(now()->format('M')) . now()->format(' Y');

        return response()->json([
            'success' => true,
            'message' => $tdrProcess->ignore_row
                ? "Row ignored ({$rowName}) {$updatedAt}"
                : "Row restored ({$rowName}) {$updatedAt}",
            'ignore_row' => (bool) $tdrProcess->ignore_row,
            'user' => auth()->user()?->name ?? 'system',
            'updated_at' => $updatedAt,
        ]);
    }

    public function activity(Main $main)
    {
        $logs = Activity::query()
            ->where('subject_type', Main::class)
            ->where('subject_id', $main->id)
            ->latest()
            ->take(200)
            ->get();

        $html = view('admin.mains.partials.activity_list', compact('main', 'logs'))->render();

        return response()->json(['html' => $html]);
    }
}
