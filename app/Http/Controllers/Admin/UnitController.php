<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualIplBranchRule;
use App\Models\Manual;
use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\Unit;
use App\Services\ManualIplBranchRuleResolver;
use App\Services\WorkorderPartScopeResolver;
use App\Services\WorkorderStdProcessItemsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Throwable;


class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:units.view')->only('show');
        $this->middleware('can:units.create')->only('store');
        $this->middleware('can:units.update')->only(['update', 'updateSingle', 'assignManual']);
        $this->middleware('can:units.delete')->only('destroySingle');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if (!$request->ajax()) {
                return response()->json(['error' => 'Invalid request type'], 400);
            }

            // Ветка 1: фронт прислал ОДИН юнит (manual_id + part_number)
            if ($request->has(['manual_id', 'part_number'])) {
                $data = $request->validate([
                    'manual_id'   => 'required|exists:manuals,id',
                    'part_number' => [
                        'required','string','max:255',
                        Rule::unique('units', 'part_number')
                            ->where(fn($q) => $q->where('manual_id', $request->input('manual_id'))),
                    ],
                    'eff_code'    => 'nullable|string|max:255',
                    'name'        => 'nullable|string|max:255',
                    'description' => 'nullable|string|max:255',
                    'default_scope_type' => ['nullable', Rule::in(Unit::validScopeTypes())],
                    'default_scope_component_id' => ['nullable', 'integer', 'exists:components,id'],
                    'default_scope_part_group_option_id' => ['nullable', 'integer', 'exists:manual_part_group_options,id'],
                ], [
                    'part_number.unique' => 'Part number already exists in this CMM.',
                ]);

                $scope = $this->normalizedScopePayload($data, (int) $data['manual_id']);
                $unit = Unit::create([
                    'manual_id'   => $data['manual_id'],
                    'part_number' => $data['part_number'],
                    'eff_code'    => $data['eff_code'] ?? null,
                    'name'        => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                    'verified'    => true,
                ] + $scope);

                // Отдаём то, что ожидает фронт при добавлении опции в селект
                return response()->json([
                    'id'            => $unit->id,
                    'part_number'   => $unit->part_number,
                    'name'          => $unit->name,
                    'description'   => $unit->description,
                    'manual_title'  => optional($unit->manual)->title,
                    'manual_number' => optional($unit->manual)->number,
                ], 201);
            }

            // Ветка 2: батч-формат (cmm_id + units[])
            $validated = $request->validate([
                'cmm_id'            => 'required|exists:manuals,id',
                'units'             => 'required|array|min:1',
                'units.*.part_number' => [
                    'required','string','max:255',
                    Rule::unique('units', 'part_number')
                        ->where(fn($q) => $q->where('manual_id', $request->input('cmm_id'))),
                ],
                'units.*.name'        => 'nullable|string|max:255',
            ]);

            $createdUnits = [];
            foreach ($validated['units'] as $unitData) {
                $createdUnits[] = Unit::create([
                    'part_number' => $unitData['part_number'],
                    'manual_id'   => $validated['cmm_id'],
                    'name'        => $unitData['name'] ?? null,
                    'eff_code'    => null,
                    'verified'    => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => count($createdUnits) . ' unit(s) created successfully',
                'units'   => $createdUnits,
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? 0) == 1062) {
                return response()->json([
                    'error'   => 'Part number already exists in another manual (database constraint).',
                    'errors'  => ['part_number' => ['Part number already exists in another manual.']],
                ], 422);
            }
            return response()->json(['error' => 'Server error'], 500);
        } catch (Throwable $e) {
            return response()->json(['error' => 'Server error'], 500);
        }
    }




    /**
     * Display the specified resource.
     */
    public function show(string $manualId)
    {
        Manual::query()->findOrFail($manualId);
        $units = Unit::where('manual_id', $manualId)->get();
        $resolver = app(ManualIplBranchRuleResolver::class);
        $defaultRule = $resolver->resolveDefaultRuleForManual((int) $manualId);

        return response()->json([
            'units' => $units
                ->map(fn (Unit $unit): array => $this->unitPayload($unit, $resolver))
                ->values()
                ->all(),
            'default_rule' => $this->defaultRulePayload($defaultRule),
            'scope_components' => $this->scopeComponentOptions((int) $manualId),
            'scope_group_options' => $this->scopePartGroupOptions((int) $manualId),
        ]);
    }

    public function update($manualId, Request $request)
    {
        try {
            Manual::findOrFail($manualId);

            $partNumbersPayload = $request->input('part_numbers');
            if (!is_array($partNumbersPayload) || $partNumbersPayload === []) {
                return response()->json(['success' => false, 'error' => 'Invalid part_numbers format'], 400);
            }

            $newPartNumbersArray = array_map(function ($unit) {
                return $unit['part_number'] ?? null;
            }, $partNumbersPayload);
            if (in_array(null, $newPartNumbersArray, true)) {
                return response()->json(['success' => false, 'error' => 'Invalid part_numbers format'], 400);
            }

            $rulesPayload = $this->collectManualBranchRulesPayload(
                $partNumbersPayload,
                $request->input('default_rule')
            );
            $partNumbersPayload = array_map(function (array $unit) use ($manualId): array {
                $unit['_normalized_scope'] = $this->normalizedScopePayload($unit, (int) $manualId);

                return $unit;
            }, $partNumbersPayload);

            // Validate the complete payload before changing existing rows.
            Unit::where('manual_id', $manualId)
                ->whereNotIn('part_number', $newPartNumbersArray)
                ->delete();

            // С учётом soft deletes: иначе при повторном добавлении ранее удалённого PN insert бьёт unique (manual_id, part_number).

            foreach ($partNumbersPayload as $unit) {
                $attributes = [
                    'name'     => $unit['name'] ?? null,
                    'verified' => (bool) ($unit['verified'] ?? false),
                ] + $unit['_normalized_scope'];

                if (array_key_exists('eff_code', $unit)) {
                    $attributes['eff_code'] = $unit['eff_code'] !== null && (string) $unit['eff_code'] !== ''
                        ? (string) $unit['eff_code']
                        : null;
                }

                $row = Unit::withTrashed()->updateOrCreate(
                    ['manual_id' => $manualId, 'part_number' => $unit['part_number']],
                    $attributes
                );
                if ($row->trashed()) {
                    $row->restore();
                }
            }

            ManualIplBranchRule::query()->where('manual_id', $manualId)->delete();
            foreach ($rulesPayload as $rulePayload) {
                ManualIplBranchRule::query()->create([
                    'manual_id' => (int) $manualId,
                ] + $rulePayload);
            }

            app(WorkorderStdProcessItemsService::class)->invalidateForManual((int) $manualId);

            return response()->json(['success' => true]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('units.update: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'error'   => config('app.debug') ? $e->getMessage() : 'An error occurred while updating units',
            ], 500);
        }
    }


    /**
     * Удалить ОДИН конкретный Unit (component) по его ID.
     */
    public function destroySingle(Unit $unit)
    {
        // Если есть связанные workorders, запрещаем удаление, чтобы не ломать целостность
        $workorderCount = $unit->workorders()->count();
        if ($workorderCount > 0) {
            return back()->with('error', "Cannot delete component: {$workorderCount} workorder(s) are linked to it.");
        }

        $unit->delete();

        return back()->with('success', 'Component deleted successfully.');
    }

    /**
     * Обновить ОДИН конкретный Unit (component) по его ID.
     */
    public function updateSingle(Unit $unit, Request $request)
    {
        $data = $request->validate([
            'part_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'part_number')
                    ->where(fn($q) => $q->where('manual_id', $unit->manual_id))
                    ->ignore($unit->id),
            ],
            'name'        => 'nullable|string|max:255',
            'verified'    => 'required|boolean',
            'eff_code'    => 'sometimes|nullable|string|max:255',
            'default_scope_type' => ['sometimes', Rule::in(Unit::validScopeTypes())],
            'default_scope_component_id' => ['nullable', 'integer', 'exists:components,id'],
            'default_scope_part_group_option_id' => ['nullable', 'integer', 'exists:manual_part_group_options,id'],
        ], [
            'part_number.unique' => 'Part number already exists in this CMM (manual).',
        ]);

        if ($request->has('eff_code')) {
            $data['eff_code'] = $request->filled('eff_code')
                ? (string) $request->input('eff_code')
                : null;
        }

        $manualId = (int) $unit->manual_id;

        $scope = $request->has('default_scope_type')
            ? $this->normalizedScopePayload($request->all(), $manualId)
            : [];
        $unit->update(array_merge($data, $scope));
        app(WorkorderStdProcessItemsService::class)->invalidateForManual($manualId);

        return response()->json([
            'success' => true,
            'id' => $unit->id,
            'part_number' => $unit->part_number,
            'name' => $unit->name,
            'verified' => $unit->verified,
            'eff_code' => $unit->eff_code,
            'default_scope_type' => $unit->default_scope_type,
            'default_scope_component_id' => $unit->default_scope_component_id,
            'default_scope_part_group_option_id' => $unit->default_scope_part_group_option_id,
        ]);
    }

    public function assignManual(Unit $unit, Request $request): JsonResponse
    {
        $data = $request->validate([
            'manual_id' => ['required', 'exists:manuals,id'],
        ]);
        $manual = Manual::query()->findOrFail($data['manual_id']);

        $duplicate = Unit::query()
            ->where('manual_id', $data['manual_id'])
            ->where('part_number', $unit->part_number)
            ->whereKeyNot($unit->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'manual_id' => ['This manual already has a unit with this part number.'],
            ]);
        }

        $updates = [
            'manual_id' => $manual->id,
            'verified' => true,
            'default_scope_type' => Unit::SCOPE_FULL_UNIT,
            'default_scope_component_id' => null,
            'default_scope_part_group_option_id' => null,
        ];

        $manualTitle = trim((string) $manual->title);
        if (trim((string) $unit->name) === '' && $manualTitle !== '') {
            $updates['name'] = $manualTitle;
        }

        $unit->update($updates);

        $unit->setRelation('manual', $manual);

        return response()->json([
            'success' => true,
            'id' => $unit->id,
            'part_number' => $unit->part_number,
            'manual_id' => $unit->manual_id,
            'name' => $unit->name,
            'description' => $unit->description,
            'manual_title' => optional($unit->manual)->title,
            'manual_number' => optional($unit->manual)->number,
            'verified' => (bool) $unit->verified,
            'default_scope_type' => $unit->default_scope_type,
            'default_scope_component_id' => $unit->default_scope_component_id,
            'default_scope_part_group_option_id' => $unit->default_scope_part_group_option_id,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $unitsPayload
     * @return array<int, array{is_default:bool,unit_match_value:?string,include_prefix:string,exclude_prefix:string}>
     */
    private function collectManualBranchRulesPayload(array $unitsPayload, mixed $defaultRuleInput): array
    {
        $rulesByMatch = [];
        $defaultRule = $this->normalizeDefaultBranchRule($defaultRuleInput);

        foreach ($unitsPayload as $unit) {
            $matchValue = $this->normalizeBranchRulePrefix($unit['unit_match_value'] ?? null);
            $includePrefix = $this->normalizeBranchRulePrefix($unit['include_prefix'] ?? null);
            $excludePrefix = $this->normalizeBranchRulePrefix($unit['exclude_prefix'] ?? null);

            if ($matchValue === null && $includePrefix === null && $excludePrefix === null) {
                continue;
            }

            if ($matchValue === null || $includePrefix === null || $excludePrefix === null) {
                throw ValidationException::withMessages([
                    'part_numbers' => ['Rule fields Unit Prefix, Use IPL and Hide IPL must all be filled, or all left blank.'],
                ]);
            }

            $existing = $rulesByMatch[$matchValue] ?? null;
            if ($existing !== null) {
                if ($existing['include_prefix'] !== $includePrefix || $existing['exclude_prefix'] !== $excludePrefix) {
                    throw ValidationException::withMessages([
                        'part_numbers' => ["Conflicting IPL rules found for unit prefix {$matchValue}."],
                    ]);
                }
                continue;
            }

            $rulesByMatch[$matchValue] = [
                'is_default' => false,
                'unit_match_value' => $matchValue,
                'include_prefix' => $includePrefix,
                'exclude_prefix' => $excludePrefix,
            ];
        }

        $rules = array_values($rulesByMatch);

        if ($defaultRule !== null) {
            array_unshift($rules, $defaultRule);
        }

        return $rules;
    }

    /**
     * @return array{is_default:bool,unit_match_value:null,include_prefix:string,exclude_prefix:string}|null
     */
    private function normalizeDefaultBranchRule(mixed $defaultRuleInput): ?array
    {
        if (! is_array($defaultRuleInput)) {
            return null;
        }

        $includePrefix = $this->normalizeBranchRulePrefix($defaultRuleInput['include_prefix'] ?? null);
        $excludePrefix = $this->normalizeBranchRulePrefix($defaultRuleInput['exclude_prefix'] ?? null);

        if ($includePrefix === null && $excludePrefix === null) {
            return null;
        }

        if ($includePrefix === null || $excludePrefix === null) {
            throw ValidationException::withMessages([
                'default_rule' => ['Default rule: fill both Use IPL and Hide IPL, or leave both empty.'],
            ]);
        }

        return [
            'is_default' => true,
            'unit_match_value' => null,
            'include_prefix' => $includePrefix,
            'exclude_prefix' => $excludePrefix,
        ];
    }

    private function normalizeBranchRulePrefix(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) ($value ?? '')));

        return $normalized !== '' ? $normalized : null;
    }

    private function unitPayload(
        Unit $unit,
        ?ManualIplBranchRuleResolver $resolver = null
    ): array
    {
        $resolver ??= app(ManualIplBranchRuleResolver::class);
        $effectiveRule = $resolver->resolveRuleForUnit($unit, (int) $unit->manual_id);
        $exactRule = $resolver->resolveExactRuleForUnit($unit, (int) $unit->manual_id);
        return [
            'id' => (int) $unit->id,
            'part_number' => (string) ($unit->part_number ?? ''),
            'verified' => (bool) $unit->verified,
            'eff_code' => $unit->eff_code,
            'name' => $unit->name,
            'description' => $unit->description,
            'unit_match_value' => $exactRule?->unit_match_value ?? '',
            'include_prefix' => $exactRule?->include_prefix ?? '',
            'exclude_prefix' => $exactRule?->exclude_prefix ?? '',
            'ipl_branch_rule_display' => $effectiveRule?->displayLabel() ?? '',
            'default_scope_type' => $unit->default_scope_type ?: Unit::SCOPE_FULL_UNIT,
            'default_scope_component_id' => $unit->default_scope_component_id ? (int) $unit->default_scope_component_id : null,
            'default_scope_part_group_option_id' => $unit->default_scope_part_group_option_id ? (int) $unit->default_scope_part_group_option_id : null,
            'scope_display' => app(WorkorderPartScopeResolver::class)->displayLabelForUnit($unit),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{default_scope_type:string,default_scope_component_id:?int,default_scope_part_group_option_id:?int}
     */
    private function normalizedScopePayload(array $payload, int $manualId): array
    {
        $type = trim((string) ($payload['default_scope_type'] ?? ''));
        if ($type === '') {
            $type = Unit::SCOPE_FULL_UNIT;
        }
        if (! in_array($type, Unit::validScopeTypes(), true)) {
            throw ValidationException::withMessages([
                'default_scope_type' => ['Invalid Work Scope type.'],
            ]);
        }

        $componentId = (int) ($payload['default_scope_component_id'] ?? 0);
        $optionId = (int) ($payload['default_scope_part_group_option_id'] ?? 0);

        if ($type === Unit::SCOPE_COMPONENT) {
            $valid = $componentId > 0 && Component::query()
                ->whereKey($componentId)
                ->where('manual_id', $manualId)
                ->exists();
            if (! $valid) {
                throw ValidationException::withMessages([
                    'default_scope_component_id' => ['Select a Part from this CMM for the Work Scope.'],
                ]);
            }

            return [
                'default_scope_type' => $type,
                'default_scope_component_id' => $componentId,
                'default_scope_part_group_option_id' => null,
            ];
        }

        if ($type === Unit::SCOPE_PART_GROUP_OPTION) {
            $valid = $optionId > 0 && ManualPartGroupOption::query()
                ->whereKey($optionId)
                ->whereHas('group', fn ($query) => $query
                    ->where('manual_id', $manualId)
                    ->whereIn('type', [ManualPartGroup::TYPE_ASSY, ManualPartGroup::TYPE_KIT]))
                ->exists();
            if (! $valid) {
                throw ValidationException::withMessages([
                    'default_scope_part_group_option_id' => ['Select an ASSY or KIT option from this CMM for the Work Scope.'],
                ]);
            }

            return [
                'default_scope_type' => $type,
                'default_scope_component_id' => null,
                'default_scope_part_group_option_id' => $optionId,
            ];
        }

        return [
            'default_scope_type' => Unit::SCOPE_FULL_UNIT,
            'default_scope_component_id' => null,
            'default_scope_part_group_option_id' => null,
        ];
    }

    /** @return array<int, array{id:int,label:string}> */
    private function scopeComponentOptions(int $manualId): array
    {
        return Component::query()
            ->where('manual_id', $manualId)
            ->orderBy('ipl_num')
            ->orderBy('part_number')
            ->get(['id', 'ipl_num', 'part_number', 'name'])
            ->map(fn (Component $component): array => [
                'id' => (int) $component->id,
                'label' => trim(implode(' · ', array_filter([
                    (string) $component->part_number,
                    $component->ipl_num ? 'IPL '.$component->ipl_num : null,
                    (string) $component->name,
                ]))),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{id:int,label:string}> */
    private function scopePartGroupOptions(int $manualId): array
    {
        return ManualPartGroupOption::query()
            ->whereHas('group', fn ($query) => $query
                ->where('manual_id', $manualId)
                ->whereIn('type', [ManualPartGroup::TYPE_ASSY, ManualPartGroup::TYPE_KIT]))
            ->with('group:id,type,name')
            ->orderBy('part_number')
            ->get(['id', 'manual_part_group_id', 'part_number', 'ipl_num', 'label'])
            ->map(fn (ManualPartGroupOption $option): array => [
                'id' => (int) $option->id,
                'label' => trim(implode(' · ', array_filter([
                    strtoupper((string) $option->group?->type),
                    (string) $option->part_number,
                    $option->ipl_num ? 'IPL '.$option->ipl_num : null,
                    (string) ($option->label ?: $option->group?->name),
                ]))),
            ])
            ->values()
            ->all();
    }

    private function defaultRulePayload(?ManualIplBranchRule $rule): array
    {
        return [
            'include_prefix' => $rule?->include_prefix ?? '',
            'exclude_prefix' => $rule?->exclude_prefix ?? '',
        ];
    }

}
