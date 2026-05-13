<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\ManualProcessNameLock;
use App\Models\Process;
use App\Models\ProcessName;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ProcessAccessDecision;
use App\Services\ProcessAccessGuard;

class ProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */

    public function index()
    {
        // Получаем все manuals
        $manuals = Manual::all();

        // Получаем все процессы с их process_names и manual_processes
        $processes = Process::with(['process_name', 'manuals'])->get();

        // Группируем процессы по manual_id
        $groupedProcesses = [];
        foreach ($processes as $process) {
            foreach ($process->manuals as $manual) {
                $groupedProcesses[$manual->id][] = [
                    'process_name' => optional($process->process_name)->name,
                    'process' => $process->process,
                ];
            }
        }

        return view('admin.processes.index', compact('manuals', 'groupedProcesses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create(Request $request, $manualId = null)
    {
        // Получаем manual_id из параметра маршрута или из query string (для обратной совместимости)
        if (!$manualId) {
            $manualId = $request->route('manual_id') ?? $request->query('manual_id');
        }
//
//        \Log::info('ProcessController::create - Request query parameters:', $request->query());
//        \Log::info('ProcessController::create - Manual ID:', ['manual_id' => $manualId]);

        if (!$manualId) {
            abort(400, 'Manual ID is required');
        }

        $manual = Manual::findOrFail($manualId);
        $decision = $this->guard()->canManageManual($request->user(), $manual);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision, route('manuals.index'));
        }
        $processNames = ProcessName::forPicker()->orderBy('name')->get();
        $processes = Process::all();

        return view('admin.processes.create', compact('manual','processNames','processes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        // Валидация с поддержкой двух сценариев: новый процесс или выбор существующего
        $validated = $request->validate([
            'process_names_id' => 'required|integer|exists:process_names,id',
            'manual_id' => 'required|integer|exists:manuals,id',
            'process_comment' => 'nullable|string|max:2000',
        ]);

        $manual = Manual::findOrFail((int) $validated['manual_id']);
        $processName = ProcessName::findOrFail((int) $validated['process_names_id']);
        // Проверяем, какой сценарий используется
        if ($request->has('selected_process_id') && $request->selected_process_id) {
            // Сценарий 1: Выбор существующего процесса
            $decision = $this->guard()->canAttachExistingManualProcess($request->user(), $manual);
            if (! $decision->allowed) {
                return $this->denyDecision($request, $decision, route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
            }

            $validated['selected_process_id'] = $request->validate([
                'selected_process_id' => 'required|integer|exists:processes,id'
            ])['selected_process_id'];

            $processId = $validated['selected_process_id'];
            $process = null; // Инициализируем переменную для случая выбора существующего процесса
        } else {
            // Сценарий 2: Создание нового процесса
            $decision = $this->guard()->canCreateProcessDefinition($request->user(), $manual, $processName);
            if (! $decision->allowed) {
                return $this->denyDecision($request, $decision, route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
            }

            $validated['process'] = $request->validate([
                'process' => 'required|string|max:255'
            ])['process'];

            // Создаем новый процесс
            $process = Process::create([
                'process_names_id' => $validated['process_names_id'],
                'process' => $validated['process'],
            ]);

            $processId = $process->id;
        }

        try {
            // Проверяем, существует ли уже связь между manual_id и processes_id
            $existingManualProcess = ManualProcess::where('manual_id', $validated['manual_id'])
                ->where('processes_id', $processId)
                ->first();

            // Если связи не существует, создаем её
            if (!$existingManualProcess) {
                ManualProcess::create([
                    'manual_id' => $validated['manual_id'],
                    'processes_id' => $processId,
                    'process_comment' => filled($validated['process_comment'] ?? null)
                        ? trim((string) $validated['process_comment'])
                        : null,
                ]);
            } elseif (filled($validated['process_comment'] ?? null)) {
                $existingManualProcess->forceFill([
                    'process_comment' => trim((string) $validated['process_comment']),
                ])->save();
            }

            // Загружаем процесс для возврата (независимо от того, новый он или существующий)
            $processToReturn = $process ?? Process::find($processId);

            // Если это AJAX-запрос или JSON запрос, возвращаем JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Process added successfully.', 'process' => $processToReturn ]);
            }

            // Проверяем, куда нужно вернуться
            if ($request->has('return_to') && $request->return_to) {
                return redirect($request->return_to)->with('success', 'Process added successfully.');
            }

            return redirect()->back()->with('success', 'Process added successfully.');
        } catch (\Exception $e) {

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error adding process: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error adding process: ' . $e->getMessage());
        }
    }
//

    public function getProcesses(Request $request)
    {
        $manual = Manual::findOrFail((int) $request->query('manualId'));
        $decision = $this->guard()->canBrowseProcessCatalog($request->user(), $manual);
        if (! $decision->allowed) {
            return response()->json([
                'success' => false,
                'message' => $decision->message,
                'reason' => $decision->reason,
                'contacts' => $decision->contacts,
            ], 403);
        }

        $processNameId = $request->query('processNameId');
        $createDecision = null;
        if ($processNameId) {
            $processName = ProcessName::find((int) $processNameId);
            if ($processName) {
                $createDecision = $this->guard()->canCreateProcessDefinition($request->user(), $manual, $processName);
            }
        }
        $manualId = $request->query('manualId'); // Добавляем manualId в запрос

        // Определяем, является ли выбранный процесс одним из вариантов Machining
        // Для всех вариантов Machining ('Machining', 'Machining (EC)', 'Machining (Blend)')
        // показываем одинаковые existingProcesses
        $machiningProcessNameIds = [];
        $machiningEC = ProcessName::where('name', 'Machining (EC)')->first();
        $machining = ProcessName::where('name', 'Machining')->first();
        $machiningBlend = ProcessName::where('name', 'Machining (Blend)')->first();

        if ($machiningEC) {
            $machiningProcessNameIds[] = $machiningEC->id;
        }
        if ($machining) {
            $machiningProcessNameIds[] = $machining->id;
        }
        if ($machiningBlend) {
            $machiningProcessNameIds[] = $machiningBlend->id;
        }

        $isMachiningProcess = in_array((int)$processNameId, $machiningProcessNameIds);

        // Получаем ID процессов, которые уже связаны с данным manual_id
        $existingProcessIds = DB::table('manual_processes')
            ->where('manual_id', $manualId)
            ->pluck('processes_id')
            ->toArray();

        // Если это процесс Machining, получаем existingProcesses для всех вариантов Machining
        if ($isMachiningProcess && !empty($machiningProcessNameIds)) {
            $existingProcesses = Process::whereIn('id', $existingProcessIds)
                ->whereIn('process_names_id', $machiningProcessNameIds)
                ->get();

            // Фильтруем процессы для выбора (исключаем существующие) для всех вариантов Machining
            $availableProcesses = Process::whereIn('process_names_id', $machiningProcessNameIds)
                ->whereNotIn('id', $existingProcessIds)
                ->get();
        } else {
            // Для обычных процессов используем стандартную логику
            $existingProcesses = Process::whereIn('id', $existingProcessIds)
                ->where('process_names_id', $processNameId)
                ->get();

            // Фильтруем процессы для выбора (исключаем существующие)
            $availableProcesses = Process::where('process_names_id', $processNameId)
                ->whereNotIn('id', $existingProcessIds)
                ->get();
        }

        $processComments = DB::table('manual_processes')
            ->where('manual_id', $manualId)
            ->whereIn('processes_id', $existingProcesses->pluck('id')->all())
            ->pluck('process_comment', 'processes_id');

        $existingProcesses = $existingProcesses->map(function (Process $process) use ($processComments) {
            $comment = trim((string) ($processComments[$process->id] ?? ''));
            $process->process_comment = $comment !== '' ? $comment : null;

            return $process;
        });

        return response()->json([
            'existingProcesses' => $existingProcesses,
            'availableProcesses' => $availableProcesses,
            'canCreateProcess' => $createDecision?->allowed ?? false,
            'createProcessMessage' => $createDecision && ! $createDecision->allowed ? $createDecision->message : null,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
//        dd($id);
        $manual = Manual::findorFail($id);
        $decision = $this->guard()->canManageManual(request()->user(), $manual);
        if (! $decision->allowed) {
            return $this->denyDecision(request(), $decision, route('manuals.index'));
        }
        $processNames = ProcessName::forPicker()->orderBy('name')->get();
        $processes = Process::all();
        $man_processes = ManualProcess::query()
            ->where('manual_id', $id)
            ->with(['process.process_name', 'lockedBy'])
            ->get();
        $processNameLocks = ManualProcessNameLock::query()
            ->where('manual_id', $id)
            ->with('lockedBy')
            ->get()
            ->keyBy('process_name_id');
        $userCanManageLockedManualProcesses = auth()->user()?->canManageLockedManualProcesses() ?? false;

        return view('admin.processes.edit', compact('manual','processNames',
            'processes','man_processes', 'processNameLocks', 'userCanManageLockedManualProcesses'

        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $process = Process::findOrFail($id);

        // Prefer explicit manual_id from request; fallback to any linked manual.
        $manualId = (int)($request->input('manual_id') ?? 0);
        if ($manualId <= 0) {
            $manualId = (int) DB::table('manual_processes')
                ->where('processes_id', $process->id)
                ->value('manual_id');
        }

        if ($manualId <= 0) {
            return $this->denyDecision(
                $request,
                ProcessAccessDecision::deny('Manual context not found for this process.', 'manual_context_missing'),
                route('processes.index')
            );
        }

        $linkedManualProcess = ManualProcess::query()
            ->with('process')
            ->where('manual_id', $manualId)
            ->where('processes_id', $process->id)
            ->first();

        if (! $linkedManualProcess) {
            return $this->denyDecision(
                $request,
                ProcessAccessDecision::deny('Manual process link not found for this process.', 'manual_process_missing'),
                route('processes.index')
            );
        }

        $decision = $this->guard()->canUpdateManualProcess($request->user(), $linkedManualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision, route('manuals.show', ['manual' => $manualId, 'tab' => 'processes']));
        }

        $validated = $request->validate([
            'process_names_id' => 'required|integer|exists:process_names,id',
            'process' => 'required|string|max:255',
            'manual_id' => 'nullable|integer|exists:manuals,id',
        ]);

        $process->update([
            'process_names_id' => (int)$validated['process_names_id'],
            'process' => (string)$validated['process'],
        ]);

        // Keep/attach relation to manual if manual_id is explicitly passed.
        if (!empty($validated['manual_id'])) {
            $existingManualProcess = ManualProcess::where('manual_id', (int)$validated['manual_id'])
                ->where('processes_id', $process->id)
                ->first();
            if (!$existingManualProcess) {
                ManualProcess::create([
                    'manual_id' => (int)$validated['manual_id'],
                    'processes_id' => $process->id,
                ]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Process updated successfully.',
                'process' => $process->fresh(),
            ]);
        }

        if ($request->has('return_to') && $request->return_to) {
            return redirect($request->return_to)->with('success', 'Process updated successfully.');
        }

        return redirect()->back()->with('success', 'Process updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function guard(): ProcessAccessGuard
    {
        return app(ProcessAccessGuard::class);
    }

    private function denyDecision(Request $request, ProcessAccessDecision $decision, string $fallbackUrl)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $decision->message,
                'reason' => $decision->reason,
                'contacts' => $decision->contacts,
            ], 403);
        }

        $returnTo = (string) $request->input('return_to', '');

        return redirect($returnTo !== '' ? $returnTo : $fallbackUrl)
            ->with('error', $decision->message);
    }
}
