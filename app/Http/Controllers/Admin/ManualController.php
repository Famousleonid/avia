<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\ManualProcessNameLock;
use App\Models\ManualRevisionCheck;
use App\Models\ManualServiceBulletin;
use App\Models\ManualPartGroup;
use App\Models\StdProcess;
use App\Models\Plane;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Scope;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserUiSetting;
use App\Services\ManualIplBranchRuleResolver;
use App\Services\ManualRevisionCheckService;
use App\Services\StdProcessAuditService;
use App\Services\WorkorderStdProcessItemsService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManualController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manuals.viewAny')->only('index');
        $this->middleware('can:manuals.view')->only('show');
        $this->middleware('can:manuals.create')->only(['create', 'store']);
        $this->middleware('can:manuals.update')->only(['edit', 'update']);
        $this->middleware('can:units.manageAdditionalManuals')->only('updateAdditionalManuals');
        $this->middleware('can:manuals.delete')->only(['destroy', 'forceDestroy']);
    }


    public function index(Request $request)
    {
        $query = Manual::with(['plane', 'builder', 'scope']);
        $showDeleted = $request->boolean('with_deleted') && auth()->user()->roleIs('Admin');

        if ($showDeleted) {
            $query->withTrashed();
        }

        if (! auth()->user()->roleIs('Admin') && ! auth()->user()->hasFullManualsAccess()) {
            $query->whereHas('permittedUsers', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        $cmms = $query->get();
        $additionalManualsById = Manual::query()
            ->withTrashed()
            ->whereKey($cmms->flatMap(fn (Manual $manual): array => $manual->additionalManualIds())->unique()->all())
            ->get(['id', 'number', 'title', 'lib'])
            ->keyBy('id');
        $cmms->each(function (Manual $manual) use ($additionalManualsById): void {
            $manual->setAttribute(
                'additional_manuals_display',
                collect($manual->additionalManualIds())
                    ->map(function (int $manualId) use ($additionalManualsById): ?array {
                        $additional = $additionalManualsById->get($manualId);

                        return $additional ? [
                            'id' => (int) $additional->id,
                            'number' => (string) ($additional->number ?? ''),
                            'title' => (string) ($additional->title ?? ''),
                            'lib' => (string) ($additional->lib ?? ''),
                        ] : null;
                    })
                    ->filter()
                    ->values()
                    ->all()
            );
        });
        $canManageAdditionalManuals = auth()->user()?->can('units.manageAdditionalManuals') ?? false;
        $additionalManualOptions = $canManageAdditionalManuals
            ? Manual::query()
                ->orderByRaw('CASE WHEN number IS NULL OR number = "" THEN 1 ELSE 0 END')
                ->orderBy('number')
                ->orderBy('title')
                ->get(['id', 'number', 'title', 'lib'])
            : collect();
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();
        $users = auth()->user()?->roleIs('Admin')
            ? User::query()->withoutReviewAccounts()->orderBy('name')->get(['id', 'name', 'selection_name_order', 'email'])
                ->sortBy(fn (User $user) => mb_strtolower($user->selection_name))
                ->values()
            : collect();

        return view('admin.manuals.index', compact(
            'cmms',
            'planes',
            'builders',
            'scopes',
            'users',
            'showDeleted',
            'canManageAdditionalManuals',
            'additionalManualOptions'
        ));

    }

    public function create()
    {
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();

        return view('admin.manuals.create', compact('planes', 'builders', 'scopes'));
    }

//    public function store(Request $request)
//    {
//        {
//            $validatedData = $request->validate([
//                'number' => 'required',
//                'title' => 'required',
//                'revision_date' => 'required',
//                'unit_name' => 'nullable',
//                'unit_name_training' => 'nullable',
//                'training_hours' => 'nullable',
//
//                'planes_id' => 'required|exists:planes,id',
//                'builders_id' => 'required|exists:builders,id',
//                'scopes_id' => 'required|exists:scopes,id',
//                'lib' => 'required'
//
//            ]);
//
//            $manual = Manual::create($validatedData);
//
//            if ($request->hasFile('img')) {
//                $manual->addMedia($request->file('img'))->toMediaCollection('manuals');
//            }
//
//            return redirect()->route('.manuals.index')->with('success', 'Manual success created.');
//        }
//    }
    public function store(Request $request)
    {
        $request->merge([
            'number' => trim((string) $request->input('number', '')),
        ]);

        $request->validate([
            'number' => ['required', 'string', 'max:255', Rule::unique('manuals', 'number')],
            'title' => 'required|string|max:255',
            'revision_number' => 'nullable|string|max:255',
            'revision_date' => 'required|date',
            'unit_name' => 'nullable',
            'unit_name_training' => 'nullable',
            'training_hours' => 'nullable',
            'ovh_life' => 'nullable',
            'reg_sb' => 'nullable',

            'planes' => 'nullable|array',
            'planes.*' => 'integer|exists:planes,id',
            'planes_id' => 'nullable|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
            'units' => 'nullable|array',
            'units.*' => 'required|string|max:255',
            'unit_names' => 'nullable|array',
            'unit_names.*' => 'nullable|string|max:255',
        ], $this->manualNumberValidationMessages($request));

        $planeIds = $this->resolvePlaneIds($request);

        try {
            $manual = DB::transaction(function () use ($request, $planeIds) {
            // Создаем новый CMM
                $manual = Manual::create($request->only([
                    'number', 'title', 'revision_number', 'revision_date', 'unit_name','unit_name_training','training_hours','ovh_life','reg_sb',
                    'builders_id', 'scopes_id', 'lib',
                ]) + ['planes_id' => $planeIds[0]]);
                $manual->planes()->sync($planeIds);

                if ($request->hasFile('img')) {
                    $manual->addMedia($request->file('img'))->toMediaCollection('manuals');
                }

                if ($request->hasFile('log_img')) {
                    $manual->addMedia($request->file('log_img'))->toMediaCollection('manuals_log');
                }

            // Если есть юниты, добавляем их
                if ($request->has('units') && is_array($request->units)) {
                    foreach ($request->units as $index => $partNumber) {
                    // Пропускаем пустые значения
                        if (empty(trim($partNumber))) {
                            continue;
                        }

                        $unitName = $request->unit_names[$index] ?? $manual->title;
                        $manual->units()->create([
                            'part_number' => $partNumber,
                            'name' => $unitName,
                            'eff_code' => null,
                            'manual_id' => $manual->id,
                            'verified' => 1,
                        ]);

                    }
                }

                return $manual;
            });
        } catch (QueryException $exception) {
            $this->throwManualNumberConflict($request, $exception);
        }

        $message = 'CMM created successfully';
        if ($request->has('units') && is_array($request->units)) {
            $unitCount = count(array_filter($request->units, function($unit) {
                return !empty(trim($unit));
            }));
            if ($unitCount > 0) {
                $message .= " with {$unitCount} unit(s)";
            }
        }

        // Автопривязка к строке матрицы тренингов по training PN
        if ($row = \App\Models\TrainingMatrixRow::autoLinkManual($manual)) {
            $message .= '. Linked to training matrix row "' . $row->part_number . '"';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'manual_id' => $manual->id,
            ]);
        }

        return redirect()->route('manuals.index')->with('success', $message);
    }



    public function show(string $id)
    {
        $cmm = Manual::findOrFail($id);
        $this->ensureManualAccess($cmm);
        $cmm->load('partLock.lockedBy');

        $manualTabKeys = ['components', 'parts', 'processes', 'std', 'sb', 'revision', 'dimensions', 'fc'];
        $requestedTab = (string) request('tab', '');
        $savedTab = UserUiSetting::query()
            ->where('user_id', auth()->id())
            ->where('scope', 'manuals.show')
            ->where('key', 'activeTab:'.$cmm->id)
            ->first()
            ?->value;
        $savedTab = is_string($savedTab) ? $savedTab : null;

        $manualShowTab = 'components';
        if (in_array($requestedTab, $manualTabKeys, true)) {
            $manualShowTab = $requestedTab;
        } elseif (request()->filled('std_inner')) {
            $manualShowTab = 'std';
        } elseif (in_array($savedTab, $manualTabKeys, true)) {
            $manualShowTab = $savedTab;
        }

        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();

//Components CMM
        $units = Unit::where('manual_id', $cmm->id)
            ->with(['defaultScopeComponent', 'defaultScopePartGroupOption.group'])
            ->get();
        $branchRuleResolver = app(ManualIplBranchRuleResolver::class);
        $scopeResolver = app(\App\Services\WorkorderPartScopeResolver::class);
        $units->each(function (Unit $unit) use ($branchRuleResolver, $scopeResolver, $cmm): void {
            $rule = $branchRuleResolver->resolveRuleForUnit($unit, (int) $cmm->id);
            $unit->setAttribute('ipl_branch_rule_display', $rule?->displayLabel() ?? '');
            $unit->setAttribute('work_scope_display', $scopeResolver->displayLabelForUnit($unit));
        });

// Parts (sorted by IPL Number in natural order: 1-10, 1-20, 1-20A, 1-30, ...)
        $parts = Component::with(['assemblies', 'manual.partLock.lockedBy'])->where('manual_id', $cmm->id)->get()->sortBy(function ($part) {
            $ipl = $part->ipl_num ?? '';

            // Ожидаемый формат: "1-10", "1-20A" и т.п.
            if (!preg_match('/^(\d+)([A-Za-z]*)-(\d+)\s*([A-Za-z][A-Za-z0-9]*)?$/', trim($ipl), $m)) {
                // Неизвестный формат отправляем в конец
                return PHP_INT_MAX;
            }

            $section = (int)$m[1];      // число до дефиса (1)
            $sectionSuffix = strtoupper(trim($m[2] ?? ''));
            $number = (int)$m[3];       // число после дефиса (10, 20, 100)
            $suffix = strtoupper(trim($m[4] ?? '')); // суффикс A, B и т.п.

            // Без суффикса должны идти раньше, чем с суффиксом
            $sectionSuffixVal = $sectionSuffix === '' ? 0 : ord($sectionSuffix[0]);
            $suffixVal = $suffix === '' ? 0 : ord($suffix[0]); // A=65, B=66...

            // Keep the full suffix so 6-70RS sorts before 6-70RS20.
            return sprintf('%06d-%04d-%06d-%04d-%s', $section, $sectionSuffixVal, $number, $suffixVal, $suffix);
        })->values();

        $parts = $parts->sort(function (Component $a, Component $b) {
            $aKey = $this->componentIplSortKey($a->ipl_num);
            $bKey = $this->componentIplSortKey($b->ipl_num);

            return $aKey <=> $bKey ?: $a->id <=> $b->id;
        })->values();

        if (request()->filled('part_id') && ctype_digit((string) request('part_id'))) {
            $partId = (int) request('part_id');
            if ($parts->pluck('id')->contains($partId)) {
                $manualShowTab = 'parts';
            }
        }


        // Processes: процессы руководства с подгруженным именем, сортировка по ProcessName (abc)
        $manualProcesses = ManualProcess::where('manual_id', $cmm->id)
            ->whereDoesntHave('process.process_name', function ($query) {
                $query->where('name', ProcessName::SYSTEM_TRAVELER_NAME);
            })
            ->with(['process.process_name', 'lockedBy'])
            ->get()
            ->sortBy(function ($mp) {
                return $mp->process && $mp->process->process_name
                    ? $mp->process->process_name->name
                    : '';
            })
            ->values();

        $processNameLocks = ManualProcessNameLock::query()
            ->where('manual_id', $cmm->id)
            ->with('lockedBy')
            ->get()
            ->keyBy('process_name_id');

        $manualProcessGroups = $manualProcesses
            ->groupBy(function (ManualProcess $manualProcess) {
                return (int) ($manualProcess->process?->process_names_id ?? 0);
            })
            ->map(function ($rows, $processNameId) use ($processNameLocks) {
                /** @var \Illuminate\Support\Collection $rows */
                $firstRow = $rows->first();
                $processName = $firstRow?->process?->process_name;
                $groupLock = $processNameId ? $processNameLocks->get((int) $processNameId) : null;

                return [
                    'process_name' => $processName,
                    'group_lock' => $groupLock,
                    'items' => $rows->sortBy(function (ManualProcess $manualProcess) {
                        return mb_strtolower((string) ($manualProcess->process?->process ?? ''));
                    })->values(),
                ];
            })
            ->sortBy(function (array $group) {
                return mb_strtolower((string) ($group['process_name']?->name ?? ''));
            })
            ->values();

        $userCanManageLockedManualProcesses = auth()->user()?->canManageLockedManualProcesses() ?? false;
        $userCanManageLockedManualParts = auth()->user()?->canManageLockedManualParts() ?? false;
        $manualPartLock = $cmm->partLock;
        $manualPartsLocked = $manualPartLock !== null;

        if (in_array($manualShowTab, ['std'], true)) {
            StdProcess::syncFromComponentFlagsForManualWhenCountsDiffer($cmm);
        }

        $stdProcessesByType = collect(StdProcess::validStdValues())->mapWithKeys(function ($std) use ($cmm) {
            $rows = StdProcess::where('manual_id', $cmm->id)
                ->where('std', $std)
                ->with('component')
                ->get()
                ->sort(function (StdProcess $a, StdProcess $b) {
                $cmp = StdProcess::iplNumSortRank($a->component?->ipl_num) <=> StdProcess::iplNumSortRank($b->component?->ipl_num);

                return $cmp !== 0 ? $cmp : $a->id <=> $b->id;
            })->values();

            return [$std => $rows];
        });

        $stdExistingPartKeysByStd = collect(StdProcess::validStdValues())->mapWithKeys(function ($std) use ($stdProcessesByType) {
            $rows = $stdProcessesByType->get($std, collect());
            $keys = $rows->map(fn (StdProcess $row) => StdProcess::duplicateKeyForClient($row->component?->ipl_num, $row->component?->part_number))->values()->all();

            return [$std => $keys];
        })->all();
        $stdProcessAuditWarnings = app(StdProcessAuditService::class)
            ->warningsByStdProcessIdForManual((int) $cmm->id);

        // Kin CMMs: same builder + plane sets INTERSECT (a manual may apply to
        // several planes of one builder — any shared plane makes them kin).
        $cmmPlaneIds = $cmm->planes()->pluck('planes.id');
        if ($cmmPlaneIds->isEmpty() && $cmm->planes_id) {
            $cmmPlaneIds = collect([(int) $cmm->planes_id]); // pre-pivot safety
        }
        $stdAddSourceManuals = Manual::query()
            ->where(function ($q) use ($cmmPlaneIds) {
                $q->whereHas('planes', fn ($p) => $p->whereIn('planes.id', $cmmPlaneIds))
                  ->orWhereIn('planes_id', $cmmPlaneIds); // manuals created outside the sync path
            })
            ->where('builders_id', $cmm->builders_id)
            ->when(! auth()->user()->roleIs('Admin') && ! auth()->user()->hasFullManualsAccess(), function ($q) {
                $q->whereHas('permittedUsers', function ($q2) {
                    $q2->where('users.id', auth()->id());
                });
            })
            ->orderBy('number')
            ->get(['id', 'number', 'title']);

        $stdProcessPicklists = [
            'ndt' => StdProcess::processPicklistValuesForManual($cmm->id, StdProcess::STD_NDT),
            'cad' => StdProcess::processPicklistValuesForManual($cmm->id, StdProcess::STD_CAD),
            'stress' => StdProcess::processPicklistValuesForManual($cmm->id, StdProcess::STD_STRESS),
            'paint' => StdProcess::processPicklistValuesForManual($cmm->id, StdProcess::STD_PAINT),
        ];
        $stdProcessPicklistOptions = [
            'ndt' => StdProcess::processPicklistOptionsForManual($cmm->id, StdProcess::STD_NDT),
            'cad' => StdProcess::processPicklistOptionsForManual($cmm->id, StdProcess::STD_CAD),
            'stress' => StdProcess::processPicklistOptionsForManual($cmm->id, StdProcess::STD_STRESS),
            'paint' => StdProcess::processPicklistOptionsForManual($cmm->id, StdProcess::STD_PAINT),
        ];

        $serviceBulletins = ManualServiceBulletin::query()
            ->where('manual_id', $cmm->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $partGroups = ManualPartGroup::query()
            ->where('manual_id', $cmm->id)
            ->with([
                'options.coverages.component:id,ipl_num,part_number,name',
                'options.coverages.coveredOption.group:id,name,type',
                'options.coverages.coveredOption.coverages.component:id,ipl_num,part_number,name',
                'options.component:id,ipl_num,part_number,name',
                'serviceBulletin',
            ])
            ->orderBy('name')
            ->get();

        $partGroupsByComponent = collect();
        $partGroupsById = $partGroups->keyBy('id');
        $optionToGroupId = $partGroups
            ->flatMap(fn (ManualPartGroup $group) => $group->options)
            ->mapWithKeys(fn ($option): array => [(int) $option->id => (int) $option->manual_part_group_id]);
        $componentIdsMemo = [];
        $componentIdsForGroup = function (int $groupId, array $visited = []) use (&$componentIdsForGroup, &$componentIdsMemo, $partGroupsById, $optionToGroupId) {
            if (isset($visited[$groupId])) {
                return collect();
            }
            if (isset($componentIdsMemo[$groupId])) {
                return $componentIdsMemo[$groupId];
            }

            $group = $partGroupsById->get($groupId);
            if (! $group) {
                return collect();
            }

            $visited[$groupId] = true;
            $componentIds = $group->options->flatMap(function ($option) use (&$componentIdsForGroup, $optionToGroupId, $visited) {
                $ids = collect([$option->component_id])->merge($option->coverages->pluck('component_id'));
                foreach ($option->coverages as $coverage) {
                    $nestedGroupId = (int) ($optionToGroupId->get((int) $coverage->covered_manual_part_group_option_id) ?? 0);
                    if ($nestedGroupId > 0) {
                        $ids = $ids->merge($componentIdsForGroup($nestedGroupId, $visited));
                    }
                }

                return $ids;
            })->filter(fn ($componentId): bool => (int) $componentId > 0)
                ->map(fn ($componentId): int => (int) $componentId)
                ->unique()
                ->values();

            return $componentIdsMemo[$groupId] = $componentIds;
        };

        foreach ($partGroups as $partGroup) {
            $componentIds = $componentIdsForGroup((int) $partGroup->id);

            foreach ($componentIds as $componentId) {
                $partGroupsByComponent->put(
                    $componentId,
                    collect($partGroupsByComponent->get($componentId, []))->push($partGroup)
                );
            }
        }

        $revisionChecks = ManualRevisionCheck::query()
            ->where('manual_id', $cmm->id)
            ->with('checkedBy:id,name,selection_name_order')
            ->latest('checked_at')
            ->latest('id')
            ->get();
        $latestRevisionCheck = $revisionChecks->first();
        $revisionStatus = app(ManualRevisionCheckService::class)->statusFor($cmm, $latestRevisionCheck);
        $canRecordRevisionCheck = auth()->user()?->can('manuals.update', $cmm) ?? false;

        $dimensionFigures = \App\Models\ManualDimensionFigure::where('manual_id', $cmm->id)
            ->with(['points' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $dimManualProcesses = \App\Models\ManualProcess::where('manual_id', $cmm->id)
            ->whereDoesntHave('process.process_name', function ($query) {
                $query->where('name', ProcessName::SYSTEM_TRAVELER_NAME);
            })
            ->with('process.process_name')
            ->get()
            ->map(fn($mp) => [
                'id'               => $mp->id,
                'process_name'     => $mp->process?->process_name?->name ?? '',
                'process_names_id' => $mp->process?->process_name?->id,
                'label'            => trim(($mp->process?->process_name?->name ?? '') . ' — ' . ($mp->process?->process ?? '')),
            ]);

        $codes = \App\Models\Code::orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.manuals.show', compact('cmm','planes','builders','scopes',
        'units','parts','manualProcesses','manualProcessGroups','userCanManageLockedManualProcesses','userCanManageLockedManualParts','manualPartLock','manualPartsLocked','stdProcessesByType','stdExistingPartKeysByStd','stdAddSourceManuals','stdProcessPicklists','stdProcessPicklistOptions','serviceBulletins',
        'revisionChecks', 'latestRevisionCheck', 'revisionStatus', 'canRecordRevisionCheck', 'manualShowTab',
        'dimensionFigures', 'dimManualProcesses', 'codes', 'stdProcessAuditWarnings', 'partGroups', 'partGroupsByComponent'
        ));

    }

    public function storeRevisionCheck(Request $request, Manual $manual)
    {
        $this->ensureManualAccess($manual);
        abort_unless(auth()->user()?->can('manuals.update', $manual), 403);

        try {
            $request->merge([
                'revision_date' => parse_project_date($request->input('revision_date')),
                'checked_at' => parse_project_date($request->input('checked_at')),
            ]);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'revision_date' => $e->getMessage(),
                'checked_at' => $e->getMessage(),
            ]);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:'.ManualRevisionCheck::STATUS_UNCHANGED.','.ManualRevisionCheck::STATUS_CHANGED],
            'revision_number' => ['nullable', 'string', 'max:255'],
            'revision_date' => ['required', 'date'],
            'checked_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($manual, $validated): void {
            if ($validated['status'] === ManualRevisionCheck::STATUS_CHANGED) {
                $manual->update([
                    'revision_number' => $validated['revision_number'] ?? null,
                    'revision_date' => $validated['revision_date'],
                ]);
            }

            ManualRevisionCheck::create([
                'manual_id' => $manual->id,
                'revision_number' => $validated['revision_number'] ?? null,
                'revision_date' => $validated['revision_date'],
                'checked_at' => $validated['checked_at'],
                'checked_by_user_id' => auth()->id(),
                'checked_by_stamp' => auth()->user()?->stamp,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('manuals.show', ['manual' => $manual->id, 'tab' => 'revision'])
            ->with('success', __('Manual revision check saved.'));
    }

    public function edit($id)
    {
        $cmm = Manual::with('units')->findOrFail($id);
        $this->ensureManualAccess($cmm);
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();
        $users = User::query()->withoutReviewAccounts()->orderBy('name')->get(['id', 'name', 'selection_name_order', 'email'])
            ->sortBy(fn (User $user) => mb_strtolower($user->selection_name))
            ->values();
        $permittedUserIds = $cmm->permittedUsers()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'manual' => [
                    'id' => $cmm->id,
                    'number' => $cmm->number,
                    'title' => $cmm->title,
                    'revision_number' => $cmm->revision_number,
                    'revision_date' => $cmm->revision_date,
                    'unit_name' => $cmm->unit_name,
                    'unit_name_training' => $cmm->unit_name_training,
                    'training_hours' => $cmm->training_hours,
                    'ovh_life' => $cmm->ovh_life,
                    'reg_sb' => $cmm->reg_sb,
                    'planes_id' => $cmm->planes_id,
                    'plane_ids' => $cmm->planes()->pluck('planes.id')->values(),
                    'builders_id' => $cmm->builders_id,
                    'scopes_id' => $cmm->scopes_id,
                    'lib' => $cmm->lib,
                    'units' => $cmm->units->map(fn ($unit) => [
                        'part_number' => $unit->part_number,
                        'name' => $unit->name,
                    ])->values(),
                    'permitted_user_ids' => $permittedUserIds,
                ],
            ]);
        }

        return view('admin.manuals.edit', compact('cmm', 'planes', 'builders', 'scopes', 'users', 'permittedUserIds'));
    }

    public function update(Request $request, $id)
    {
        $cmm = Manual::findOrFail($id);
        $this->ensureManualAccess($cmm);

        $request->merge([
            'number' => trim((string) $request->input('number', '')),
        ]);

        $validatedData = $request->validate([
            'number' => ['required', 'string', 'max:255', Rule::unique('manuals', 'number')->ignore($cmm->id)],
            'title' => 'required',
            'revision_number' => 'nullable|string|max:255',
            'revision_date' => 'required',
            'unit_name' => 'nullable',
            'unit_name_training' => 'nullable',
            'training_hours' => 'nullable',
            'ovh_life' => 'nullable',
            'reg_sb' => 'nullable',
            'planes' => 'nullable|array',
            'planes.*' => 'integer|exists:planes,id',
            'planes_id' => 'nullable|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
            'units' => 'nullable|array',
            'units.*' => 'required|string|max:255',
            'unit_names' => 'nullable|array',
            'unit_names.*' => 'nullable|string|max:255',
            'permitted_user_ids' => 'nullable|array',
            'permitted_user_ids.*' => 'integer|exists:users,id',
        ], $this->manualNumberValidationMessages($request));

        $planeIds = $this->resolvePlaneIds($request);
        $validatedData['planes_id'] = $planeIds[0];
        $cmm->planes()->sync($planeIds);

        if ($request->hasFile('img')) {
            if ($cmm->getMedia('manuals')->isNotEmpty()) {
                $cmm->getMedia('manuals')->first()->delete();
            }
            $cmm->addMedia($request->file('img'))->toMediaCollection('manuals');
        }
        if ($request->hasFile('log_img')) {
            if ($cmm->getMedia('manuals_log')->isNotEmpty()) {
                $cmm->getMedia('manuals_log')->first()->delete();
            }
            $cmm->addMedia($request->file('log_img'))->toMediaCollection('manuals_log');
        }

        $cmm->update(collect($validatedData)->except(['permitted_user_ids', 'planes'])->all());

        // Обновляем units если они предоставлены
        if ($request->has('units') && is_array($request->units)) {
            $existingUnits = $cmm->units()->pluck('id')->toArray();
            $newUnits = [];

            // Обрабатываем каждый unit
            foreach ($request->units as $index => $partNumber) {
                // Пропускаем пустые значения
                if (empty(trim($partNumber))) {
                    continue;
                }

                // Если у нас есть существующий unit с таким же part_number, обновляем его
                $existingUnit = $cmm->units()->where('part_number', $partNumber)->first();

                if ($existingUnit) {
                    $unitName = $request->unit_names[$index] ?? $existingUnit->name ?? $cmm->title;
                    $unitName = trim((string) $unitName) !== '' ? $unitName : ($existingUnit->name ?? $cmm->title);
                    $existingUnit->update([
                        'name' => $unitName,
                        'eff_code' => null,
                    ]);
                    $newUnits[] = $existingUnit->id;
                } else {
                    $unitName = $request->unit_names[$index] ?? $cmm->title;
                    $unitName = trim((string) $unitName) !== '' ? $unitName : $cmm->title;
                    // Создаем новый unit
                    $newUnit = $cmm->units()->create([
                        'part_number' => $partNumber,
                        'name' => $unitName,
                        'eff_code' => null,
                        'manual_id' => $cmm->id,
                        'verified' => 1,
                    ]);
                    $newUnits[] = $newUnit->id;
                }
            }

            // Удаляем только те units, которые больше не используются
            $unitsToDelete = array_diff($existingUnits, $newUnits);
            if (!empty($unitsToDelete)) {


                // Проверяем, есть ли связанные workorders
                foreach ($unitsToDelete as $unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit) {
                        $workorderCount = $unit->workorders()->count();

                        if ($workorderCount == 0) {
                            $unit->delete();
                        } else {
                        }
                    }
                }
            }
        }

        $message = 'Manual updated successfully';
        if ($request->has('units') && is_array($request->units)) {
            $unitCount = count(array_filter($request->units, function($unit) {
                return !empty(trim($unit));
            }));
            if ($unitCount > 0) {
                $message .= " with {$unitCount} unit(s)";
            }
        }

        // Автопривязка к строке матрицы тренингов по training PN
        if ($row = \App\Models\TrainingMatrixRow::autoLinkManual($cmm)) {
            $message .= '. Linked to training matrix row "' . $row->part_number . '"';
        }

        if (auth()->user()->roleIs('Admin')) {
            $cmm->permittedUsers()->sync($request->input('permitted_user_ids', []));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('manuals.index')->with('success', $message);
    }

    public function destroy($id)
    {
        $cmm = Manual::findOrFail($id);
        if ($cmm->getMedia('manuals')->isNotEmpty()) {
            $cmm->getMedia('manuals')->first()->delete();
        }
        $cmm->delete();

        return redirect()->route('manuals.index')->with('success', 'Manual deleted successfully');
    }

    public function forceDestroy($id)
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $deletedPartsCount = DB::transaction(function () use ($id) {
            $manual = Manual::withTrashed()->findOrFail($id);
            $deletedPartsCount = Component::withTrashed()
                ->where('manual_id', $manual->id)
                ->count();

            Component::withTrashed()
                ->where('manual_id', $manual->id)
                ->chunkById(100, function ($components): void {
                    foreach ($components as $component) {
                        $component->forceDelete();
                    }
                });

            $manual->forceDelete();

            return $deletedPartsCount;
        });

        return redirect()
            ->route('manuals.index')
            ->with('success', "Manual permanently deleted successfully. Deleted parts: {$deletedPartsCount}");
    }

    protected function ensureManualAccess(Manual $manual): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        if ($user->roleIs('Admin') || $user->hasFullManualsAccess()) {
            return;
        }

        $allowed = $manual->permittedUsers()->where('users.id', $user->id)->exists();
        abort_unless($allowed, 403);
    }

    private function componentIplSortKey(?string $ipl): array
    {
        $value = trim((string) $ipl);

        if (! preg_match('/^(\d+)([A-Za-z]*)-(\d+)\s*([A-Za-z][A-Za-z0-9]*)?$/', $value, $matches)) {
            return [1, 0, 0, strtoupper($value)];
        }

        return [
            0,
            (int) $matches[1],
            strtoupper($matches[2] ?? ''),
            (int) $matches[3],
            strtoupper($matches[4] ?? ''),
        ];
    }

    public function updateAdditionalManuals(Request $request, Manual $manual): JsonResponse
    {
        $validated = $request->validate([
            'additional_manual_ids' => ['present', 'array'],
            'additional_manual_ids.*' => ['integer', 'distinct', 'exists:manuals,id'],
        ]);

        $additionalManualIds = collect($validated['additional_manual_ids'])
            ->map(fn ($manualId): int => (int) $manualId)
            ->filter(fn (int $manualId): bool => $manualId > 0 && $manualId !== (int) $manual->id)
            ->unique()
            ->values()
            ->all();

        $manual->update(['additional_manual_ids' => $additionalManualIds]);
        app(WorkorderStdProcessItemsService::class)->invalidateForManual((int) $manual->id);

        $manualsById = Manual::query()
            ->whereKey($additionalManualIds)
            ->get(['id', 'number', 'title', 'lib'])
            ->keyBy('id');

        return response()->json([
            'success' => true,
            'message' => __('Additional Manuals updated successfully.'),
            'additional_manual_ids' => $additionalManualIds,
            'additional_manuals' => collect($additionalManualIds)
                ->map(function (int $manualId) use ($manualsById): ?array {
                    $additional = $manualsById->get($manualId);

                    return $additional ? [
                        'id' => (int) $additional->id,
                        'number' => (string) ($additional->number ?? ''),
                        'title' => (string) ($additional->title ?? ''),
                        'lib' => (string) ($additional->lib ?? ''),
                    ] : null;
                })
                ->filter()
                ->values()
                ->all(),
        ]);
    }

    /** @return array<string, string> */
    private function manualNumberValidationMessages(Request $request): array
    {
        return [
            'number.unique' => __('Manual :number already exists.', [
                'number' => trim((string) $request->input('number', '')),
            ]),
        ];
    }

    private function throwManualNumberConflict(Request $request, QueryException $exception): void
    {
        $isManualNumberConflict = (string) $exception->getCode() === '23000'
            && str_contains($exception->getMessage(), 'manuals_number_unique');

        if ($isManualNumberConflict) {
            throw ValidationException::withMessages([
                'number' => $this->manualNumberValidationMessages($request)['number.unique'],
            ]);
        }

        throw $exception;
    }

    /**
     * Aircraft set of the CMM from the request: multi-select planes[] (new) with
     * a legacy single planes_id fallback. At least one required; the first id
     * mirrors into manuals.planes_id (denormalized "primary" for old readers).
     *
     * @return array<int, int>
     */
    private function resolvePlaneIds(Request $request): array
    {
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('planes', []))));

        if ($ids === [] && $request->filled('planes_id')) {
            $ids = [(int) $request->input('planes_id')];
        }

        if ($ids === []) {
            throw ValidationException::withMessages(['planes' => 'Select at least one aircraft type.']);
        }

        return $ids;
    }
}


