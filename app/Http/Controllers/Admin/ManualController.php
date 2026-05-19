<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\ManualProcessNameLock;
use App\Models\ManualServiceBulletin;
use App\Models\StdProcess;
use App\Models\Plane;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Scope;
use App\Models\Unit;
use App\Models\User;
use App\Services\ManualIplBranchRuleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manuals.viewAny')->only('index');
        $this->middleware('can:manuals.view')->only('show');
        $this->middleware('can:manuals.create')->only(['create', 'store']);
        $this->middleware('can:manuals.update')->only(['edit', 'update']);
        $this->middleware('can:manuals.delete')->only('destroy');
    }


    public function index()
    {
        $query = Manual::with(['plane', 'builder', 'scope']);

        if (! auth()->user()->roleIs('Admin') && ! auth()->user()->hasFullManualsAccess()) {
            $query->whereHas('permittedUsers', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        $cmms = $query->get();
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();
        $users = auth()->user()?->roleIs('Admin')
            ? User::orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('admin.manuals.index', compact('cmms', 'planes', 'builders', 'scopes', 'users'));

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
        $request->validate([
            'number' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'revision_date' => 'required|date',
            'unit_name' => 'nullable',
            'unit_name_training' => 'nullable',
            'training_hours' => 'nullable',
            'ovh_life' => 'nullable',
            'reg_sb' => 'nullable',

            'planes_id' => 'required|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
            'units' => 'nullable|array',
            'units.*' => 'required|string|max:255',
            'unit_names' => 'nullable|array',
            'unit_names.*' => 'nullable|string|max:255',
        ]);

        $manual = DB::transaction(function () use ($request) {
            // Создаем новый CMM
            $manual = Manual::create($request->only([
                'number', 'title', 'revision_date', 'unit_name','unit_name_training','training_hours','ovh_life','reg_sb',
                'planes_id', 'builders_id', 'scopes_id', 'lib',
            ]));

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
                    $newUnit = $manual->units()->create([
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

        $message = 'CMM created successfully';
        if ($request->has('units') && is_array($request->units)) {
            $unitCount = count(array_filter($request->units, function($unit) {
                return !empty(trim($unit));
            }));
            if ($unitCount > 0) {
                $message .= " with {$unitCount} unit(s)";
            }
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

        $manualTabKeys = ['components', 'parts', 'processes', 'std', 'sb'];
        $manualShowTab = in_array(request('tab'), $manualTabKeys, true) ? request('tab') : 'components';

        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();

//Components CMM
        $units = Unit::where('manual_id', $cmm->id)->get();
        $branchRuleResolver = app(ManualIplBranchRuleResolver::class);
        $units->each(function (Unit $unit) use ($branchRuleResolver, $cmm): void {
            $rule = $branchRuleResolver->resolveRuleForUnit($unit, (int) $cmm->id);
            $unit->setAttribute('ipl_branch_rule_display', $rule?->displayLabel() ?? '');
        });

// Parts (sorted by IPL Number in natural order: 1-10, 1-20, 1-20A, 1-30, ...)
        $parts = Component::with(['assemblies', 'manual.partLock.lockedBy'])->where('manual_id', $cmm->id)->get()->sortBy(function ($part) {
            $ipl = $part->ipl_num ?? '';

            // Ожидаемый формат: "1-10", "1-20A" и т.п.
            if (!preg_match('/^(\d+)([A-Za-z\s]*)-(\d+)([A-Za-z\s0-9]*)$/', trim($ipl), $m)) {
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

        $stdAddSourceManuals = Manual::query()
            ->where('planes_id', $cmm->planes_id)
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

        return view('admin.manuals.show', compact('cmm','planes','builders','scopes',
        'units','parts','manualProcesses','manualProcessGroups','userCanManageLockedManualProcesses','userCanManageLockedManualParts','manualPartLock','manualPartsLocked','stdProcessesByType','stdExistingPartKeysByStd','stdAddSourceManuals','stdProcessPicklists','stdProcessPicklistOptions','serviceBulletins'
        ));

    }

    public function edit($id)
    {
        $cmm = Manual::with('units')->findOrFail($id);
        $this->ensureManualAccess($cmm);
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
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
                    'revision_date' => $cmm->revision_date,
                    'unit_name' => $cmm->unit_name,
                    'unit_name_training' => $cmm->unit_name_training,
                    'training_hours' => $cmm->training_hours,
                    'ovh_life' => $cmm->ovh_life,
                    'reg_sb' => $cmm->reg_sb,
                    'planes_id' => $cmm->planes_id,
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

        $validatedData = $request->validate([
            'number' => 'required',
            'title' => 'required',
            'revision_date' => 'required',
            'unit_name' => 'nullable',
            'unit_name_training' => 'nullable',
            'training_hours' => 'nullable',
            'ovh_life' => 'nullable',
            'reg_sb' => 'nullable',
            'planes_id' => 'required|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
            'units' => 'nullable|array',
            'units.*' => 'required|string|max:255',
            'unit_names' => 'nullable|array',
            'unit_names.*' => 'nullable|string|max:255',
            'permitted_user_ids' => 'nullable|array',
            'permitted_user_ids.*' => 'integer|exists:users,id',
        ]);

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

        $cmm->update(collect($validatedData)->except('permitted_user_ids')->all());

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

        if (! preg_match('/^(\d+)([A-Za-z]*)-(\d+)([A-Za-z0-9]*)$/', $value, $matches)) {
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
}


