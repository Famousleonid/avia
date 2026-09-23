<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Workorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\ProcessAccessDecision;
use App\Services\ProcessAccessGuard;

class ProcessController extends Controller
{
    private const BUSHING_PROCESS_NAMES = [
        ...ProcessName::BUSHING_MACHINING_NAMES,
        'Stress Relief',
        'NDT-1',
        'NDT-4',
        'Passivation',
        'Cad plate',
        'Anodizing',
        'Xylan coating',
    ];

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
        $bushingContext = $request->query('context') === 'bushing';
        $workorder = $bushingContext
            ? $this->resolveBushingWorkorder($request, $manual)
            : null;
        $decision = $bushingContext
            ? $this->guard()->canManageWorkorderBushingProcesses($request->user(), $manual, $workorder)
            : $this->guard()->canManageManual($request->user(), $manual);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision, route('manuals.index'));
        }

        if ($bushingContext) {
            $processNameOrder = array_flip(self::BUSHING_PROCESS_NAMES);
            $processNames = ProcessName::forPicker()
                ->whereIdentityNames(self::BUSHING_PROCESS_NAMES)
                ->get()
                ->sortBy(fn (ProcessName $processName): int => $processNameOrder[$processName->identityName()] ?? PHP_INT_MAX)
                ->values();
        } else {
            $processNames = ProcessName::forPicker()->orderBy('name')->get();
        }
        $processes = Process::all();

        return view('admin.processes.create', compact('manual', 'processNames', 'processes', 'bushingContext', 'workorder'));
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
            'context' => 'nullable|string|in:bushing',
            'workorder_id' => 'nullable|integer|required_if:context,bushing|exists:workorders,id',
        ]);

        $manual = Manual::findOrFail((int) $validated['manual_id']);
        $processName = ProcessName::findOrFail((int) $validated['process_names_id']);
        $bushingContext = ($validated['context'] ?? null) === 'bushing';
        $workorder = null;

        if ($bushingContext) {
            $workorder = $this->resolveBushingWorkorder($request, $manual);
            $decision = $this->guard()->canManageWorkorderBushingProcesses(
                $request->user(),
                $manual,
                $workorder
            );
            if (! $decision->allowed) {
                return $this->denyDecision($request, $decision, route('tdrs.show', $workorder->id));
            }

            if (! in_array($processName->identityName(), self::BUSHING_PROCESS_NAMES, true)) {
                throw ValidationException::withMessages([
                    'process_names_id' => 'This process name is not available for bushings.',
                ]);
            }
        }

        if ($processName->identityName() === ProcessName::SYSTEM_TRAVELER_NAME) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Traveler is a system process and cannot be added to manual processes.',
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Traveler is a system process and cannot be added to manual processes.');
        }
        // Проверяем, какой сценарий используется
        if ($request->has('selected_process_id') && $request->selected_process_id) {
            // Сценарий 1: Выбор существующего процесса
            $decision = $bushingContext
                ? $this->guard()->canManageWorkorderBushingProcesses($request->user(), $manual, $workorder)
                : $this->guard()->canAttachExistingManualProcess($request->user(), $manual);
            if (! $decision->allowed) {
                return $this->denyDecision($request, $decision, route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
            }

            $validated['selected_process_id'] = $request->validate([
                'selected_process_id' => [
                    'required',
                    'integer',
                    Rule::exists('processes', 'id')->where(
                        fn ($query) => $query->where('process_names_id', $processName->id)
                    ),
                ],
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
            $manualProcessCreated = false;

            // Если связи не существует, создаем её
            if (!$existingManualProcess) {
                $existingManualProcess = ManualProcess::create([
                    'manual_id' => $validated['manual_id'],
                    'processes_id' => $processId,
                    'process_comment' => filled($validated['process_comment'] ?? null)
                        ? trim((string) $validated['process_comment'])
                        : null,
                ]);
                $manualProcessCreated = true;
            } elseif (filled($validated['process_comment'] ?? null)) {
                $existingManualProcess->forceFill([
                    'process_comment' => trim((string) $validated['process_comment']),
                ])->save();
            }

            // Загружаем процесс для возврата (независимо от того, новый он или существующий)
            $processToReturn = $process ?? Process::find($processId);
            $processToReturn->setAttribute('process_comment', $existingManualProcess->process_comment);

            if ($bushingContext && $workorder && $manualProcessCreated) {
                $this->logBushingProcessAdded(
                    $request,
                    $workorder,
                    $manual,
                    $processName,
                    $processToReturn,
                    $process !== null
                );
            }

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
        $bushingContext = $request->query('context') === 'bushing';
        $workorder = $bushingContext
            ? $this->resolveBushingWorkorder($request, $manual)
            : null;
        $decision = $bushingContext
            ? $this->guard()->canManageWorkorderBushingProcesses($request->user(), $manual, $workorder)
            : $this->guard()->canBrowseProcessCatalog($request->user(), $manual);
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
                if ($bushingContext && ! in_array($processName->identityName(), self::BUSHING_PROCESS_NAMES, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This process name is not available for bushings.',
                    ], 422);
                }
                $createDecision = $this->guard()->canCreateProcessDefinition($request->user(), $manual, $processName);
            }
        }
        $manualId = $request->query('manualId'); // Добавляем manualId в запрос

        // Определяем, является ли выбранный процесс одним из вариантов Machining
        // Для всех вариантов Machining ('Machining', 'Machining (EC)', 'Machining (Blend)')
        // показываем одинаковые existingProcesses
        $machiningProcessNameIds = [];
        $machiningEC = ProcessName::whereIdentityName('Machining (EC)')->first();
        $machining = ProcessName::whereIdentityName('Machining')->first();
        $machiningBlend = ProcessName::whereIdentityName('Machining (Blend)')->first();

        if ($machiningEC) {
            $machiningProcessNameIds[] = $machiningEC->id;
        }
        if ($machining) {
            $machiningProcessNameIds[] = $machining->id;
        }
        if ($machiningBlend) {
            $machiningProcessNameIds[] = $machiningBlend->id;
        }
        $machiningAtIds = ProcessName::whereIdentityNames(ProcessName::BUSHING_MACHINING_NAMES)->pluck('id')->all();
        $machiningProcessNameIds = $bushingContext
            ? $machiningAtIds
            : array_values(array_unique(array_merge($machiningProcessNameIds, $machiningAtIds)));

        $isMachiningProcess = ! $bushingContext && in_array((int)$processNameId, $machiningProcessNameIds);

        // Получаем ID процессов, которые уже связаны с данным manual_id
        $existingProcessIds = DB::table('manual_processes')
            ->where('manual_id', $manualId)
            ->pluck('processes_id')
            ->toArray();

        // Если это процесс Machining, получаем existingProcesses для всех вариантов Machining
        if ($isMachiningProcess && !empty($machiningProcessNameIds)) {
            $existingProcesses = Process::whereIn('id', $existingProcessIds)
                ->whereIn('process_names_id', $machiningProcessNameIds)
                ->orderBy('process')
                ->get();

            // Фильтруем процессы для выбора (исключаем существующие) для всех вариантов Machining
            $availableProcesses = Process::whereIn('process_names_id', $machiningProcessNameIds)
                ->whereNotIn('id', $existingProcessIds)
                ->orderBy('process')
                ->get();
        } else {
            // Для обычных процессов используем стандартную логику
            $existingProcesses = Process::whereIn('id', $existingProcessIds)
                ->where('process_names_id', $processNameId)
                ->orderBy('process')
                ->get();

            // Фильтруем процессы для выбора (исключаем существующие)
            $availableProcesses = Process::where('process_names_id', $processNameId)
                ->whereNotIn('id', $existingProcessIds)
                ->orderBy('process')
                ->get();
        }

        $manualProcessSettings = ManualProcess::query()
            ->where('manual_id', $manualId)
            ->whereIn('processes_id', $existingProcesses->pluck('id')->all())
            ->get(['processes_id', 'process_comment', 'requires_fig', 'requires_zone'])
            ->keyBy('processes_id');

        $existingProcesses = $existingProcesses->map(function (Process $process) use ($manualProcessSettings) {
            $settings = $manualProcessSettings->get($process->id);
            $comment = trim((string) ($settings?->process_comment ?? ''));
            $process->process_comment = $comment !== '' ? $comment : null;
            $process->requires_fig = (bool) ($settings?->requires_fig ?? false);
            $process->requires_zone = (bool) ($settings?->requires_zone ?? false);

            return $process;
        });

        return response()->json([
            'success' => true,
            'existingProcesses' => $existingProcesses,
            'availableProcesses' => $availableProcesses,
            'canCreateProcess' => $createDecision?->allowed ?? false,
            'createProcessMessage' => $createDecision && ! $createDecision->allowed ? $createDecision->message : null,
        ]);
    }

    private function resolveBushingWorkorder(Request $request, Manual $manual): Workorder
    {
        $workorderId = (int) $request->input('workorder_id', $request->query('workorder_id'));
        abort_if($workorderId <= 0, 400, 'Workorder ID is required for bushing processes.');

        $workorder = Workorder::query()
            ->with('unit:id,manual_id')
            ->findOrFail($workorderId);

        abort_unless(
            (int) ($workorder->unit?->manual_id ?? 0) === (int) $manual->id,
            422,
            'The workorder does not use this CMM.'
        );

        return $workorder;
    }

    private function logBushingProcessAdded(
        Request $request,
        Workorder $workorder,
        Manual $manual,
        ProcessName $processName,
        Process $process,
        bool $createdDefinition
    ): void {
        $summary = sprintf(
            '%s: %s',
            trim((string) $processName->name),
            trim((string) $process->process)
        );

        activity('workorder')
            ->causedBy($request->user())
            ->performedOn($workorder)
            ->event('bushing_process_catalog_added')
            ->withProperties([
                'source' => 'bushing_process_catalog',
                'manual_id' => (int) $manual->id,
                'manual_number' => (string) $manual->number,
                'process_name_id' => (int) $processName->id,
                'process_name' => (string) $processName->name,
                'process_id' => (int) $process->id,
                'process' => (string) $process->process,
                'created_definition' => $createdDefinition,
                'attributes' => [
                    'bushing_process_catalog' => $summary,
                ],
                'old' => [
                    'bushing_process_catalog' => null,
                ],
            ])
            ->log('Bushing process added to CMM');
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

    public function updateScope(Request $request, ProcessName $processName)
    {
        $validated = $request->validate([
            'scope' => 'required|in:point,part',
        ]);
        $processName->update(['scope' => $validated['scope']]);
        return response()->json(['ok' => true, 'scope' => $processName->scope]);
    }
}
