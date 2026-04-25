<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GeneralTask;
use App\Models\Instruction;
use App\Models\Main;
use App\Models\Manual;
use App\Models\Task;
use App\Models\Unit;
use App\Models\ProcessName;
use App\Models\User;
use App\Models\Workorder;
use App\Models\TdrProcess;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ZipStream\ZipStream;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Validation\Rule;


class WorkorderController extends Controller
{
    public function logs(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $activitiesQuery = Activity::query()
            ->where('log_name', 'workorder')
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at');

        if ($q !== '') {
            $activitiesQuery->where(function ($qq) use ($q) {

                // 1) событие
                $qq->where('event', 'like', "%{$q}%")

                    // 2) пользователь
                    ->orWhereHas('causer', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%");
                    })

                    // 3) Workorder.number (ВАЖНО: whereHasMorph!)
                    ->orWhereHasMorph(
                        'subject',
                        [Workorder::class],
                        function ($s) use ($q) {
                            $s->where('number', 'like', "%{$q}%");
                        }
                    )

                    // 4) изменения (JSON как текст)
                    ->orWhere('properties', 'like', "%{$q}%");
            });
        }

        $activities = $activitiesQuery
            ->paginate(50)
            ->appends(['q' => $q]);

        // Мапы id → имя
        $unitsMap        = Unit::pluck('part_number', 'id')->all();
        $customersMap    = Customer::pluck('name', 'id')->all();
        $instructionsMap = Instruction::pluck('name', 'id')->all();
        $usersMap        = User::pluck('name', 'id')->all();

        $fieldLabels = [
            'number'         => 'WO Number',
            'unit_id'        => 'Unit',
            'customer_id'    => 'Customer',
            'instruction_id' => 'Instruction',
            'user_id'        => 'Technik',
            'approve_at'     => 'Approve date',
            'approve_name'   => 'Approved by',
            'description'    => 'Description',
        ];

        return view('admin.log.index', compact(
            'activities',
            'unitsMap',
            'customersMap',
            'instructionsMap',
            'usersMap',
            'fieldLabels'
        ));
    }

    public function logsForWorkorder(Workorder $workorder)
    {
        $activities = Activity::query()
            ->where('log_name', 'workorder')
            ->where('subject_type', Workorder::class)
            ->where('subject_id', $workorder->id)
            ->with(['causer'])
            ->orderByDesc('created_at')
            ->get();

        // Мапы для красивых имён
        $unitsMap        = Unit::pluck('part_number', 'id')->all();
        $customersMap    = Customer::pluck('name', 'id')->all();
        $instructionsMap = Instruction::pluck('name', 'id')->all();
        $usersMap        = User::pluck('name', 'id')->all();

        $fieldLabels = [
            'number'         => 'WO Number',
            'unit_id'        => 'Unit',
            'customer_id'    => 'Customer',
            'instruction_id' => 'Instruction',
            'user_id'        => 'Technik',
            'approve_at'     => 'Approve date',
            'approve_name'   => 'Approved by',
            'description'    => 'Description',
            'serial_number'  => 'Serial number',

            // кастомные поля для task deleted
            'task'           => 'Task',
            'assigned_user'  => 'Technik',
            'date_start'     => 'Start',
            'date_finish'    => 'Finish',
            'main_id'        => 'Main ID',
        ];

        $formatValue = function ($field, $value) use ($unitsMap, $customersMap, $instructionsMap, $usersMap) {
            if ($value === null) return null;

            return match ($field) {
                'unit_id'        => $unitsMap[$value]        ?? $value,
                'customer_id'    => $customersMap[$value]    ?? $value,
                'instruction_id' => $instructionsMap[$value] ?? $value,
                'user_id'        => $usersMap[$value]        ?? $value,
                default          => $value,
            };
        };

        $data = $activities->map(function (Activity $log) use ($fieldLabels, $formatValue) {

            // ✅ важно: properties приводим к массиву
            $props = $log->properties ? $log->properties->toArray() : [];

            // стандартная структура Spatie для updated
            $attributes = $props['attributes'] ?? [];
            $old        = $props['old'] ?? [];

            $changes = [];

            // 1) обычные изменения (update)
            foreach ($attributes as $field => $newValue) {
                $oldValue = $old[$field] ?? null;

                if ($oldValue === $newValue) continue;

                $changes[] = [
                    'field' => $field,
                    'label' => $fieldLabels[$field] ?? $field,
                    'old'   => $formatValue($field, $oldValue),
                    'new'   => $formatValue($field, $newValue),
                ];
            }

            // 2) кастомные логи (например "task deleted") — данные лежат НЕ в attributes/old
            if (empty($changes)) {

                // task deleted: props.task / props.dates / props.assigned_user / props.main_id
                if (!empty($props['task']) || !empty($props['dates']) || !empty($props['assigned_user'])) {

                    $taskText = trim(
                        ($props['task']['general'] ?? '') .
                        (($props['task']['general'] ?? null) ? ' → ' : '') .
                        ($props['task']['name'] ?? '')
                    );

                    if ($taskText !== '') {
                        $changes[] = [
                            'field' => 'task',
                            'label' => $fieldLabels['task'],
                            'old'   => null,
                            'new'   => $taskText,
                        ];
                    }

                    if (!empty($props['assigned_user'])) {
                        $changes[] = [
                            'field' => 'assigned_user',
                            'label' => $fieldLabels['assigned_user'],
                            'old'   => null,
                            'new'   => $props['assigned_user'],
                        ];
                    }

                    if (!empty($props['dates']['start'])) {
                        $changes[] = [
                            'field' => 'date_start',
                            'label' => $fieldLabels['date_start'],
                            'old'   => null,
                            'new'   => $props['dates']['start'],
                        ];
                    }

                    if (array_key_exists('finish', $props['dates'] ?? [])) {
                        $changes[] = [
                            'field' => 'date_finish',
                            'label' => $fieldLabels['date_finish'],
                            'old'   => null,
                            'new'   => $props['dates']['finish'] ?? null,
                        ];
                    }

                    if (!empty($props['main_id'])) {
                        $changes[] = [
                            'field' => 'main_id',
                            'label' => $fieldLabels['main_id'],
                            'old'   => null,
                            'new'   => $props['main_id'],
                        ];
                    }
                }
            }

            return [
                'id'          => $log->id,
                'created_at'  => optional($log->created_at)->format('d-M-y H:i'),
                'description' => $log->description,
                'event'       => $log->event,
                'log_name'    => $log->log_name,
                'causer_name' => optional($log->causer)->name,
                'changes'     => $changes,
            ];
        })->values();

        return response()->json($data);
    }

    public function index()
    {
        $payload = $this->buildWorkorderIndexPayload(request());

        if (request()->boolean('fragment') || request()->expectsJson()) {
            return response()->json([
                'html' => view('admin.workorders.partials.rows', [
                    'workorders' => $payload['workorders']->items(),
                    'ecStatuses' => $payload['ecStatuses'],
                    'generalTasks' => $payload['generalTasks'],
                    'tasksByGeneral' => $payload['tasksByGeneral'],
                ])->render(),
                'next_cursor' => $payload['nextCursor'],
                'has_more' => $payload['hasMore'],
                'loaded_count' => count($payload['workorders']->items()),
                'total_count' => $payload['totalCount'],
                'overall_total' => $payload['overallTotal'],
            ]);
        }

        $customers = Customer::query()->orderBy('name')->get(['id', 'name']);
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.workorders.index', [
            'workorders' => $payload['workorders']->items(),
            'customers' => $customers,
            'users' => $users,
            'generalTasks' => $payload['generalTasks'],
            'tasksByGeneral' => $payload['tasksByGeneral'],
            'ecStatuses' => $payload['ecStatuses'],
            'nextCursor' => $payload['nextCursor'],
            'hasMore' => $payload['hasMore'],
            'totalCount' => $payload['totalCount'],
            'overallTotal' => $payload['overallTotal'],
            'initialSort' => $payload['filters']['sort'],
            'initialDirection' => $payload['filters']['direction'],
        ]);

    }

    private function buildWorkorderIndexPayload(Request $request): array
    {
        $filters = $this->normalizeWorkorderIndexFilters($request);

        $overallQuery = Workorder::query()->withDrafts();
        $this->applyWorkorderRoleVisibility($overallQuery);
        $overallTotal = (clone $overallQuery)->count();

        $filteredCountQuery = Workorder::query()->withDrafts();
        $this->applyWorkorderRoleVisibility($filteredCountQuery);
        $this->applyWorkorderIndexFilters($filteredCountQuery, $filters);
        $totalCount = (clone $filteredCountQuery)->count();

        $query = Workorder::query()
            ->withDrafts()
            ->with([
                'main.task',
                'unit.manual',
                'customer',
                'instruction',
                'user',
                'generalTaskStatuses',
            ]);

        $this->applyWorkorderRoleVisibility($query);
        $this->applyWorkorderIndexFilters($query, $filters);
        $this->applyWorkorderIndexSorting($query, $filters);

        $workorders = $query->cursorPaginate(
            $filters['per_page'],
            ['workorders.*'],
            'cursor',
            $filters['cursor']
        );

        $generalTasks = GeneralTask::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tasksByGeneral = Task::query()
            ->select('id', 'name', 'general_task_id')
            ->orderBy('general_task_id')
            ->orderBy('name')
            ->get()
            ->groupBy('general_task_id');

        return [
            'workorders' => $workorders,
            'ecStatuses' => $this->buildEcStatuses(collect($workorders->items())),
            'generalTasks' => $generalTasks,
            'tasksByGeneral' => $tasksByGeneral,
            'nextCursor' => $workorders->nextCursor()?->encode(),
            'hasMore' => $workorders->hasMorePages(),
            'totalCount' => $totalCount,
            'overallTotal' => $overallTotal,
            'filters' => $filters,
        ];
    }

    private function normalizeWorkorderIndexFilters(Request $request): array
    {
        $allowedSorts = ['number', 'ec', 'customer', 'instruction', 'technik'];
        $sort = (string) $request->get('sort', 'number');
        $direction = strtolower((string) $request->get('direction', 'desc'));

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'number';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $sort === 'number' ? 'desc' : 'asc';
        }

        return [
            'q' => trim((string) $request->get('q', '')),
            'customer_id' => (string) $request->get('customer_id', ''),
            'technik_id' => (string) $request->get('technik_id', ''),
            'only_my' => $request->boolean('only_my', false),
            'only_active' => $request->boolean('only_active', false),
            'only_approved' => $request->boolean('only_approved', false),
            'show_drafts' => $request->boolean('show_drafts', false),
            'sort' => $sort,
            'direction' => $direction,
            'cursor' => $request->get('cursor'),
            'per_page' => max(10, min(100, (int) $request->get('per_page', 50))),
        ];
    }

    private function applyWorkorderRoleVisibility(Builder $query): void
    {
        if (auth()->check() && auth()->user()->roleIs('Technician')) {
            $query->where('workorders.is_draft', 0);
        }
    }

    private function applyWorkorderIndexFilters(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $like = '%' . $filters['q'] . '%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('workorders.number', 'like', $like)
                    ->orWhere('workorders.description', 'like', $like)
                    ->orWhere('workorders.serial_number', 'like', $like)
                    ->orWhere('workorders.customer_po', 'like', $like)
                    ->orWhere('workorders.amdt', 'like', $like)
                    ->orWhereHas('customer', function (Builder $customer) use ($like) {
                        $customer->where('name', 'like', $like);
                    })
                    ->orWhereHas('instruction', function (Builder $instruction) use ($like) {
                        $instruction->where('name', 'like', $like);
                    })
                    ->orWhereHas('user', function (Builder $user) use ($like) {
                        $user->where('name', 'like', $like);
                    })
                    ->orWhereHas('unit', function (Builder $unit) use ($like) {
                        $unit->where('part_number', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('eff_code', 'like', $like)
                            ->orWhereHas('manual', function (Builder $manual) use ($like) {
                                $manual->where('number', 'like', $like)
                                    ->orWhere('lib', 'like', $like);
                            });
                    });
            });
        }

        if ($filters['only_my'] && auth()->check()) {
            $query->where('workorders.user_id', auth()->id());
        }

        if ($filters['customer_id'] !== '') {
            $query->where('workorders.customer_id', (int) $filters['customer_id']);
        }

        if ($filters['technik_id'] !== '') {
            $query->where('workorders.user_id', (int) $filters['technik_id']);
        }

        if ($filters['only_approved']) {
            $query->whereNotNull('workorders.approve_at');
        }

        if ($filters['show_drafts']) {
            $query->where('workorders.is_draft', true);
        } else {
            $query->where('workorders.is_draft', false);
        }

        if ($filters['only_active']) {
            $query->whereDoesntHave('main', function (Builder $main) {
                $main->whereNotNull('task_id')
                    ->where('ignore_row', false)
                    ->whereNotNull('date_finish')
                    ->whereHas('task', function (Builder $task) {
                        $task->where('name', 'Completed');
                    });
            });
        }
    }

    private function applyWorkorderIndexSorting(Builder $query, array $filters): void
    {
        $direction = $filters['direction'];

        switch ($filters['sort']) {
            case 'customer':
                $query->addSelect([
                    'customer_sort' => Customer::query()
                        ->select('name')
                        ->whereColumn('customers.id', 'workorders.customer_id')
                        ->limit(1),
                ])->orderBy('customer_sort', $direction);
                break;

            case 'instruction':
                $query->addSelect([
                    'instruction_sort' => Instruction::query()
                        ->select('name')
                        ->whereColumn('instructions.id', 'workorders.instruction_id')
                        ->limit(1),
                ])->orderBy('instruction_sort', $direction);
                break;

            case 'technik':
                $query->addSelect([
                    'technik_sort' => User::query()
                        ->select('name')
                        ->whereColumn('users.id', 'workorders.user_id')
                        ->limit(1),
                ])->orderBy('technik_sort', $direction);
                break;

            case 'ec':
                $ecProcessNameId = $this->getEcProcessNameId();

                if ($ecProcessNameId) {
                    $query->addSelect([
                        'ec_sort' => TdrProcess::query()
                            ->join('tdrs', 'tdrs.id', '=', 'tdr_processes.tdrs_id')
                            ->selectRaw("
                                CASE
                                    WHEN MAX(CASE WHEN tdr_processes.date_finish IS NOT NULL THEN 1 ELSE 0 END) = 1 THEN 3
                                    WHEN MAX(CASE WHEN tdr_processes.date_start IS NOT NULL THEN 1 ELSE 0 END) = 1 THEN 2
                                    WHEN COUNT(*) > 0 THEN 1
                                    ELSE 0
                                END
                            ")
                            ->where('tdr_processes.process_names_id', $ecProcessNameId)
                            ->whereColumn('tdrs.workorder_id', 'workorders.id'),
                    ])->orderBy('ec_sort', $direction);
                }
                break;

            case 'number':
            default:
                $query->orderBy('workorders.number', $direction);
                break;
        }

        $query->orderBy('workorders.id', $direction);
    }

    private function buildEcStatuses(Collection $workorders): array
    {
        $ecStatuses = [];
        $ecProcessNameId = $this->getEcProcessNameId();

        if (!$ecProcessNameId || $workorders->isEmpty()) {
            return $ecStatuses;
        }

        $workorderIds = $workorders->pluck('id')->values()->all();

        $ecRows = TdrProcess::query()
            ->where('process_names_id', $ecProcessNameId)
            ->whereHas('tdr', function (Builder $q) use ($workorderIds) {
                $q->whereIn('workorder_id', $workorderIds);
            })
            ->with([
                'tdr:id,workorder_id',
                'updatedBy:id,name',
            ])
            ->get();

        foreach ($ecRows as $row) {
            $woId = (int) ($row->tdr?->workorder_id ?? 0);
            if ($woId <= 0) {
                continue;
            }

            $current = $ecStatuses[$woId] ?? [
                'state' => 'none',
                'date_start' => null,
                'date_finish' => null,
                'date_start_user' => null,
                'date_finish_user' => null,
                'user_name' => null,
            ];

            $userName = optional($row->updatedBy)->name;

            if (!empty($row->date_finish)) {
                $current['state'] = 'finished';
                $current['date_finish'] = $current['date_finish'] ?? $row->date_finish;
                $current['date_finish_user'] = $current['date_finish_user'] ?? $userName;

                if (!empty($row->date_start)) {
                    $current['date_start'] = $current['date_start'] ?? $row->date_start;
                    $current['date_start_user'] = $current['date_start_user'] ?? $userName;
                }
            } elseif (!empty($row->date_start)) {
                if ($current['state'] !== 'finished') {
                    $current['state'] = 'started';
                }
                $current['date_start'] = $current['date_start'] ?? $row->date_start;
                $current['date_start_user'] = $current['date_start_user'] ?? $userName;
            } elseif ($current['state'] === 'none') {
                $current['state'] = 'exists';
                $current['user_name'] = $current['user_name'] ?? $userName;
            }

            $ecStatuses[$woId] = $current;
        }

        foreach ($ecStatuses as $woId => $state) {
            $ecStatuses[$woId] = [
                'state' => $state['state'] ?? 'none',
                'date_start' => $state['date_start'] ?? null,
                'date_finish' => $state['date_finish'] ?? null,
                'date_start_user' => $state['date_start_user'] ?? null,
                'date_finish_user' => $state['date_finish_user'] ?? null,
                'user_name' => $state['user_name'] ?? null,
            ];
        }

        return $ecStatuses;
    }

    private function getEcProcessNameId(): ?int
    {
        static $ecProcessNameId;
        static $resolved = false;

        if (!$resolved) {
            $ecProcessNameId = ProcessName::query()->where('name', 'EC')->value('id');
            $resolved = true;
        }

        return $ecProcessNameId ? (int) $ecProcessNameId : null;
    }

    public function create()
    {
        $customers = Customer::all();
        $units = Unit::with('manuals')->get();
        $instructions = Instruction::all();
        $manuals = Manual::query()
            ->orderByRaw('CASE WHEN number IS NULL OR number = "" THEN 1 ELSE 0 END')
            ->orderBy('number')
            ->orderBy('title')
            ->get();
        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $currentUser = Auth::user();
        $draftInstructionId = Instruction::where('name','Draft')->value('id');

        return view('admin.workorders.create', compact('customers', 'units', 'instructions', 'users', 'currentUser', 'manuals','draftInstructionId'));
    }

    public function store(Request $request)
    {
        $draftInstructionId = Instruction::where('name', 'Draft')->value('id');

        if (!$draftInstructionId) {
            return redirect()
                ->route('workorders.create')
                ->with('error', 'Instruction "Draft" not found.');
        }

        $isDraft = ((int)$request->input('instruction_id') === (int)$draftInstructionId);

        // ✅ Базовая валидация (number НЕ требуем тут, иначе Draft не пройдет)
        $rules = [
            'unit_id' => ['required', 'exists:units,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'instruction_id' => ['required', 'exists:instructions,id'],
            'number' => ['nullable'],
            'open_at' => ['nullable', 'string'],
            'storage_rack'   => ['nullable','integer','min:1','max:999'],
            'storage_level'  => ['nullable','integer','min:1','max:999'],
            'storage_column' => ['nullable','integer','min:1','max:999'],
        ];

        // ✅ Если НЕ draft — номер обязателен и уникален
        if (!$isDraft) {
            // Колонка workorders.number — INTEGER; значения вида 190-70500-405 дают SQL 1265 / 500
            $rules['number'] = ['required', 'integer', 'min:100000', 'max:999999', 'unique:workorders,number'];
        }

        $data = $request->validate($rules, [
            'number.integer' => __('The workorder number must be a whole number (digits only, no dashes or letters).'),
            'number.min' => __('The workorder number must be 100000 or greater.'),
            'number.max' => __('The workorder number must be 999999 or less.'),
        ]);

        try {
            $data['open_at'] = parse_project_date($request->input('open_at'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['open_at' => $e->getMessage()])->withInput();
        }

        // ✅ Чекбоксы (как у тебя)
        if (!$isDraft) {
            $unit = Unit::find($data['unit_id']);
            if (!$unit || !$unit->manual_id) {
                return back()
                    ->withErrors(['unit_id' => 'Select a unit with assigned Manual before creating Workorder.'])
                    ->withInput();
            }
        }

        $data = array_merge($data, [
            'part_missing' => $request->has('part_missing') ? 1 : 0,
            'external_damage' => $request->has('external_damage') ? 1 : 0,
            'received_disassembly' => $request->has('received_disassembly') ? 1 : 0,
            'disassembly_upon_arrival' => $request->has('disassembly_upon_arrival') ? 1 : 0,
            'nameplate_missing' => $request->has('nameplate_missing') ? 1 : 0,
            'preliminary_test_false' => $request->has('preliminary_test_false') ? 1 : 0,
            'extra_parts' => $request->has('extra_parts') ? 1 : 0,
        ]);

        // ✅ Draft: ставим is_draft и генерим номер в контроллере
        if ($isDraft) {
            $data['is_draft'] = true;
            $data['number'] = Workorder::nextDraftNumber(); // int
        } else {
            $data['is_draft'] = false;
        }

        // ✅ user_id (у тебя есть hidden, но безопаснее так)
        $data['user_id'] = $request->input('user_id', auth()->id());

        // ✅ остальные поля, которые ты отправляешь (description, serial_number, etc.)
        $data['serial_number'] = $request->input('serial_number');
        $data['description'] = $request->input('description');
        $data['amdt'] = $request->input('amdt');
        $data['place'] = $request->input('place');
        $data['customer_po'] = $request->input('customer_po');
        $data['modified'] = $request->input('modified');

        $workorder = Workorder::create($data);

        if (!$isDraft) {
            $overhaulId = Instruction::overhaulId();
            if ($overhaulId && (int) $workorder->instruction_id === (int) $overhaulId) {
                app(WorkorderStdListProcessesService::class)->resolveForWorkorder($workorder);
            }
        }

        // ✅ Если description заполнено и unit->name пустое — обновляем unit->name
        if (!empty($request->description)) {
            $unit = Unit::find($workorder->unit_id);
            if ($unit && empty($unit->name)) {
                $unit->name = $request->description;
                $unit->save();
            }
        }

        return redirect()->route('workorders.index')->with('success', 'Workorder added');
    }

    public function destroy(Workorder $workorder)
    {
        abort_unless(auth()->user()->hasAnyRole('Admin|Manager'), 403);
        $workorder->delete();

        return redirect()->route('workorders.index')->with('success', 'Workorder deleted');
    }

    public function forceDestroy(Workorder $workorder)
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        try {
            $number = $workorder->number;
            $workorder->forceDelete();

            return redirect()
                ->route('workorders.index')
                ->with('success', "Workorder {$number} permanently deleted.");
        } catch (\Throwable $e) {
            return redirect()
                ->route('workorders.index')
                ->with('error', 'Permanent deletion failed. Remove dependent records first.');
        }
    }

    public function edit(Workorder $workorder)
    {

        $draftInstructionId = Instruction::where('name', 'Draft')->value('id');
        $wasDraft = (int)$workorder->instruction_id === (int)$draftInstructionId;

        $instructionsQuery = Instruction::query();
        if (!$wasDraft) {
            $instructionsQuery->where('id', '!=', $draftInstructionId);
        }

        $instructions = $instructionsQuery->get();
        $current_wo = $workorder;
        $customers = Customer::all();
        $units = Unit::with('manual')->get();
        $manuals = Manual::query()
            ->orderByRaw('CASE WHEN number IS NULL OR number = "" THEN 1 ELSE 0 END')
            ->orderBy('number')
            ->orderBy('title')
            ->get();
        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $open_at = format_project_date($current_wo->open_at);
        $hasTdrs = $workorder->tdrs()->exists();

        return view('admin.workorders.edit', compact('users', 'customers', 'units', 'instructions', 'current_wo', 'manuals', 'open_at','draftInstructionId','wasDraft','hasTdrs'));

    }

    public function update(Request $request, Workorder $workorder)
    {
        $draftInstructionId = Instruction::where('name', 'Draft')->value('id');
        $wasDraft = (int)$workorder->instruction_id === (int)$draftInstructionId;
        $newIsDraft = (int)$request->instruction_id === (int)$draftInstructionId;

        // Базовая валидация
        $rules = [
            'unit_id' => ['required', 'exists:units,id'],
            'customer_id' => ['required'],
            'instruction_id' => ['required'],
            'open_at' => ['nullable', 'string'],
            'storage_rack'   => ['nullable','integer','min:1','max:999'],
            'storage_level'  => ['nullable','integer','min:1','max:999'],
            'storage_column' => ['nullable','integer','min:1','max:999'],
        ];

        // Номер разрешаем менять ТОЛЬКО если воркордер был Draft и стал НЕ Draft
        $allowChangeNumber = $wasDraft && !$newIsDraft;

        if ($allowChangeNumber) {
            $rules['number'] = [
                'required',
                'integer',
                'min:100000',
                'max:999999',
                Rule::unique('workorders', 'number')->ignore($workorder->id),
            ];
        }

        $request->validate($rules, [
            'number.integer' => __('The workorder number must be a whole number (digits only, no dashes or letters).'),
            'number.min' => __('The workorder number must be 100000 or greater.'),
            'number.max' => __('The workorder number must be 999999 or less.'),
        ]);

        try {
            $request->merge(['open_at' => parse_project_date($request->input('open_at'))]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['open_at' => $e->getMessage()])->withInput();
        }

        // Чекбоксы
        if ($wasDraft && !$newIsDraft) {
            $unit = Unit::find($request->input('unit_id'));
            if (!$unit || !$unit->manual_id) {
                return back()
                    ->withErrors(['unit_id' => 'Assign Manual to this Unit before releasing Draft.'])
                    ->withInput();
            }
        }

        $request->merge([
            'part_missing' => $request->has('part_missing') ? 1 : 0,
            'external_damage' => $request->has('external_damage') ? 1 : 0,
            'received_disassembly' => $request->has('received_disassembly') ? 1 : 0,
            'disassembly_upon_arrival' => $request->has('disassembly_upon_arrival') ? 1 : 0,
            'nameplate_missing' => $request->has('nameplate_missing') ? 1 : 0,
            'preliminary_test_false' => $request->has('preliminary_test_false') ? 1 : 0,
            'extra_parts' => $request->has('extra_parts') ? 1 : 0,
        ]);

        // ------- activity log for number change attempts -------
        $oldNumber = (string)$workorder->number;
        $requestedNumber = (string)($request->input('number') ?? '');

        $attempted = $requestedNumber !== '' && $requestedNumber !== $oldNumber;

        if ($attempted) {
            $props = [
                'workorder_id' => $workorder->id,
                'old' => $oldNumber,
                'new' => $requestedNumber,
                'allowed' => $allowChangeNumber,
                'was_draft' => $wasDraft,
                'instruction_from_id' => $workorder->instruction_id,
                'instruction_to_id' => (int)$request->instruction_id,
                'ip' => $request->ip(),
                'user_agent' => (string)$request->userAgent(),
            ];

            // Важно: log_name = changeworkordernumber
            activity('changeworkordernumber')
                ->causedBy(Auth::user())
                ->performedOn($workorder)
                ->event($allowChangeNumber ? 'changed_request' : 'attempt_blocked')
                ->withProperties($props)
                ->log($allowChangeNumber
                    ? "Requested workorder number change"
                    : "Blocked workorder number change attempt"
                );
        }


        // Если менять номер нельзя — железно оставляем старый
        if (!$allowChangeNumber) {
            $request->merge(['number' => $workorder->number]);
        }

        // Draft -> Released: ставим is_draft = 0
        if ($wasDraft && !$newIsDraft) {
            $request->merge(['is_draft' => 0]);
        }

        // (опционально) если был Draft и остался Draft — держим 1
        if ($wasDraft && $newIsDraft) {
            $request->merge(['is_draft' => 1]);
        }

        // если изначально не Draft — всегда 0
        if (!$wasDraft) {
            $request->merge(['is_draft' => 0]);
        }

        $oldUnitId = $workorder->unit_id;
        $overhaulId = Instruction::overhaulId();
        $wasOverhaul = $overhaulId !== null && (int) $workorder->instruction_id === (int) $overhaulId;
        $newInstructionId = (int) $request->instruction_id;
        $isNowOverhaul = $overhaulId !== null && $newInstructionId === (int) $overhaulId;

        $workorder->update($request->all());

        if ($wasOverhaul && ! $isNowOverhaul) {
            $this->deleteStdListTdrProcessesForWorkorder($workorder);
        }

        // При смене unit — обновить NdtCadCsv компонентами из нового manual
        if ((int)$request->unit_id !== (int)$oldUnitId) {
            $ndtCadCsv = $workorder->ndtCadCsv;
            if ($ndtCadCsv) {
                \App\Models\NdtCadCsv::loadComponentsFromManual($workorder->id, $ndtCadCsv);
            }
        }

        // Если description заполнено и unit->name пустое, обновляем unit->name
        if (!empty($request->description)) {
            $unitId = $request->unit_id ?? $workorder->unit_id;
            $unit = Unit::find($unitId);
            if ($unit && empty($unit->name)) {
                $unit->name = $request->description;
                $unit->save();
            }
        }

        if (! $newIsDraft && $isNowOverhaul) {
            app(WorkorderStdListProcessesService::class)->resolveForWorkorder($workorder);
        }

        return redirect()->route('workorders.index')->with('success', 'Workorder was edited successfully');
    }

    public function approveAjax(Request $request, Workorder $workorder)
    {
        abort_unless(auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $request->validate([
            // дата из инпута будет "YYYY-MM-DD" или пусто
            'approve_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $user = Auth::user();

        $approvedTask = Task::where('name', 'Approved')->first();
        if (!$approvedTask) {
            return response()->json([
                'ok' => false,
                'message' => 'Task "Approved" not found',
            ], 422);
        }

        $waitingTaskId = $approvedTask->id;
        $generalTaskId = $approvedTask->general_task_id;

        // если дату стерли => null
        $approveDate = $request->input('approve_date');
        $newApproveAt = $approveDate ? Carbon::createFromFormat('Y-m-d', $approveDate)->startOfDay() : null;

        // main по Approved
        $mainQuery = Main::where('workorder_id', $workorder->id)
            ->where('task_id', $waitingTaskId);

        if (is_null($newApproveAt)) {
            // снять аппрув
            $workorder->approve_at = null;
            $workorder->approve_name = null;
            $workorder->save();

            if ($main = $mainQuery->first()) {
                $main->date_finish = null;
                $main->user_id = null;
                $main->save();
            }
        } else {
            // поставить/изменить аппрув
            $workorder->approve_at = $newApproveAt;
            $workorder->approve_name = $user->name; // всегда текущий юзер
            $workorder->save();

            $main = $mainQuery->first();
            if (!$main) {
                $main = new Main();
                $main->workorder_id = $workorder->id;
                $main->task_id = $waitingTaskId;
                $main->general_task_id = $generalTaskId; // важно
            }

            $main->user_id = $user->id;
            $main->date_finish = $newApproveAt;
            $main->save();
        }

        $workorder->recalcGeneralTaskStatuses($generalTaskId);
        $workorder->syncDoneByCompletedTask();

        return response()->json([
            'ok' => true,
            'approved' => (bool) $workorder->approve_at,
            'approve_at_iso' => $workorder->approve_at ? $workorder->approve_at->format('Y-m-d') : null,
            'approve_at_human' => $workorder->approve_at ? $workorder->approve_at->format('d.m.Y') : null,
            'approve_name' => $workorder->approve_name,
        ]);
    }

    public function updateInspect(Request $request, $id)
    {

        try {
            $workOrder = WorkOrder::findOrFail($id);

            $workOrder->part_missing = $request->has('part_missing');
            $workOrder->external_damage = $request->has('external_damage');
            $workOrder->received_disassembly = $request->has('received_disassembly');
            $workOrder->disassembly_upon_arrival = $request->has('disassembly_upon_arrival');
            $workOrder->nameplate_missing = $request->has('nameplate_missing');
            $workOrder->preliminary_test_false = $request->has('preliminary_test_false');
            $workOrder->extra_parts = $request->has('extra_parts');

            $workOrder->save();

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function photos($id)
    {
        try {
            $workorder = Workorder::withDrafts()->findOrFail($id);
            $groups = config('workorder_media.groups');

            if (!is_array($groups) || empty($groups)) {
                // fallback на дефолт (чтобы не было 500 на проде)
                $groups = [
                    'photos'  => 'Photos',
                ];
            }

            $collections = array_keys($groups);
            $media = [];

            foreach ($collections as $col) {

                $media[$col] = $workorder
                    ->getMedia($col)
                    ->filter(fn($m) => $m->mime_type && Str::startsWith($m->mime_type, 'image/'))
                    ->map(function ($m) use ($workorder, $col) {
                        return [
                            'id'    => $m->id,
                            'thumb' => route('image.show.thumb', [
                                'mediaId'   => $m->id,
                                'modelId'   => $workorder->id,
                                'mediaName' => $col,
                            ]),
                            'big'   => route('image.show.big', [
                                'mediaId'   => $m->id,
                                'modelId'   => $workorder->id,
                                'mediaName' => $col,
                            ]),
                        ];
                    })
                    ->values()
                    ->toArray();
            }

            return response()->json([
                'groups' => $groups, // labels
                'media'  => $media,  // данные по коллекциям
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Workorder not found'], 404);
        } catch (\Throwable $e) {
            Log::channel('avia')->error('WO photos error', ['id'=>$id, 'e'=>$e->getMessage()]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function downloadAllGrouped($id)
    {
        try {
            $workorder = Workorder::withDrafts()->findOrFail($id);
            $groups = array_keys(config('workorder_media.groups'));

            return new StreamedResponse(function () use ($workorder, $groups, $id) {
                $options = new ArchiveOptions();
                $options->setSendHttpHeaders(false);

                $zip = new ZipStream('', $options);

                foreach ($groups as $group) {
                    foreach ($workorder->getMedia($group) as $media) {
                        $filePath = $media->getPath();

                        if (!file_exists($filePath)) {
                            // Log::channel('avia')->error("File not found: $filePath");
                            continue;
                        }

                        $filename = Str::slug(pathinfo($media->file_name, PATHINFO_FILENAME)) . '.' .
                            pathinfo($media->file_name, PATHINFO_EXTENSION);

                        $relativePath = "$group/$filename";

                        $zip->addFileFromPath($relativePath, $filePath);
                        //  Log::channel('avia')->info("Added to zip: $relativePath");
                    }
                }

                $zip->finish();
                //  Log::channel('avia')->info("ZIP stream finished for workorder ID: $id");

            }, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="wo_' . $workorder->number . '_images.zip"',
            ]);
        } catch (\Throwable $e) {
            //  Log::channel('avia')->error("ZIP creation failed: " . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function downloadGroup($id, $group)
    {
        try {
            $workorder = Workorder::withDrafts()->findOrFail($id);

            $groupsConfig = config('workorder_media.groups', []);
            $allowed = array_keys($groupsConfig);

            if (!in_array($group, $allowed, true)) {
                return response()->json(['error' => 'Invalid group'], 422);
            }

            return new StreamedResponse(function () use ($workorder, $group) {
                $options = new ArchiveOptions();
                $options->setSendHttpHeaders(false);

                $zip = new ZipStream('', $options);

                foreach ($workorder->getMedia($group) as $media) {
                    $filePath = $media->getPath();
                    if (!file_exists($filePath)) continue;

                    $filename = Str::slug(pathinfo($media->file_name, PATHINFO_FILENAME)) . '.' .
                        pathinfo($media->file_name, PATHINFO_EXTENSION);

                    // кладём в папку группы (или просто filename — как хочешь)
                    $zip->addFileFromPath("$group/$filename", $filePath);
                }

                $zip->finish();

            }, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="wo_' . $workorder->number . '_' . $group . '.zip"',
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function pdfs($id)
    {
        try {
            $workorder = Workorder::findOrFail($id);
            $pdfs = $workorder->getMedia('pdfs')->map(function ($media) use ($workorder) {
                $documentName = $media->getCustomProperty('document_name') ?: ($media->name ?? null);

                return [
                    'id' => $media->id,
                    'name' => $documentName ?: $media->file_name,
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                    'created_at' => $media->created_at->format('Y-m-d H:i:s'),
                    'url' => route('workorders.pdf.show', [
                        'workorderId' => $workorder->id,
                        'mediaId' => $media->id,
                    ]),
                    'download_url' => route('workorders.pdf.download', [
                        'workorderId' => $workorder->id,
                        'mediaId' => $media->id,
                    ]),
                ];
            })->values()->toArray();

            return response()->json([
                'success' => true,
                'pdfs' => $pdfs,
                'count' => count($pdfs),
            ]);
        } catch (\Throwable $e) {
            Log::error("PDF list failed for workorder $id: {$e->getMessage()}");
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function recalcStages()
    {

        abort_unless(auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $generalTasks = GeneralTask::orderBy('sort_order')->orderBy('id')->get();
        $workorders = Workorder::select('id')->get();

        foreach ($workorders as $wo) {
            $wo->recalcGeneralTaskStatuses();
            $wo->syncDoneByCompletedTask();
        }

        return back()->with('success', 'Stages recalculated for all workorders.');
    }

    public function checkNumber(Request $request)
    {
        $number = trim((string)$request->get('number'));
        $ignoreId = (int)$request->get('ignore_id');

        if ($number === '') {
            return response()->json(['ok' => false, 'message' => 'Empty number']);
        }

        if (filter_var($number, FILTER_VALIDATE_INT) === false) {
            return response()->json([
                'ok' => false,
                'message' => __('The workorder number must be a whole number (digits only, no dashes or letters).'),
            ]);
        }

        if ((int) $number < 100000) {
            return response()->json([
                'ok' => false,
                'message' => __('The workorder number must be 100000 or greater.'),
            ]);
        }

        if ((int) $number > 999999) {
            return response()->json([
                'ok' => false,
                'message' => __('The workorder number must be 999999 or less.'),
            ]);
        }

        $exists = Workorder::query()
            ->where('number', $number)
            ->when($ignoreId > 0, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        return response()->json(['ok' => true, 'unique' => !$exists]);
    }

    public function updateNotes(Request $request, Workorder $workorder)
    {
        abort_unless(auth()->check(), 403);

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $old = (string) ($workorder->notes ?? '');
        $new = (string) ($data['notes'] ?? '');

        // если ничего не поменялось — можно вернуть ok без логов
        if ($old === $new) {
            return response()->json(['success' => true, 'notes' => $new]);
        }

        $workorder->notes = $new;
        $workorder->save();

        // Лог old/new (явно)
        activity()
            ->useLog('workorders')
            ->performedOn($workorder)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => ['notes' => $old],
                'new' => ['notes' => $new],
            ])
            ->log($old === '' ? 'workorder_notes_created' : 'workorder_notes_updated');

        return response()->json(['success' => true, 'notes' => $new, 'user' => auth()->user()->name]);
    }

    public function notesLogs(Workorder $workorder)
    {
        abort_unless(auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $logs = Activity::query()
            ->where('subject_type', Workorder::class)
            ->where('subject_id', $workorder->id)
            ->where('log_name', 'workorders')
            ->whereIn('description', ['workorder_notes_created', 'workorder_notes_updated'])
            ->latest('created_at')
            ->limit(200)
            ->get()
            ->map(function (Activity $a) {
                $props = $a->properties ?? collect();

                return [
                    'date' => optional($a->created_at)->format('d-M-Y H:i'),
                    'user' => $a->causer?->name ?? '—',
                    'old'  => (string) data_get($props, 'old.notes', ''),
                    'new'  => (string) data_get($props, 'new.notes', ''),
                ];
            });

        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function updateStorage(Request $request, \App\Models\Workorder $workorder)
    {
        $data = $request->validate([
            'storage_rack'   => ['nullable','integer','min:0','max:999'],
            'storage_level'  => ['nullable','integer','min:0','max:999'],
            'storage_column' => ['nullable','integer','min:0','max:999'],
        ]);

        $workorder->update($data);

        return response()->json([
            'success' => true,
            'storage_location' => $workorder->storage_location,
        ]);
    }

    /**
     * Удаляет процессы STD List (имена из WorkorderStdListProcessesService) по всем TDR воркордера.
     */
    private function deleteStdListTdrProcessesForWorkorder(Workorder $workorder): void
    {
        $names = array_values(WorkorderStdListProcessesService::NAME_BY_KEY);
        $nameIds = ProcessName::query()->whereIn('name', $names)->pluck('id');
        if ($nameIds->isEmpty()) {
            return;
        }

        TdrProcess::query()
            ->whereIn('process_names_id', $nameIds)
            ->whereHas('tdr', static function ($q) use ($workorder) {
                $q->where('workorder_id', (int) $workorder->id);
            })
            ->delete();
    }
}
