<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualBushingSpec;
use App\Models\ManualBushingOversizeOption;
use App\Models\ManualDimensionPoint;
use App\Models\ManualDimensionRepairRule;
use App\Models\ManualDimensionRepairRuleProcess;
use App\Models\ManualDimensionSpec;
use App\Models\ManualDimensionSpecCode;
use Illuminate\Http\Request;

class ManualDimensionSpecController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // --- Specs ---

    public function store(Request $request, ManualDimensionPoint $manualDimensionPoint)
    {
        $data = $request->validate([
            'spec_type'                => 'sometimes|in:measurement,inspection',
            'inspection_component_id'  => 'nullable|exists:manual_inspection_components,id',
            'description'              => 'required|string|max:255',
            'is_required'              => 'boolean',
            'orig_dim_min'             => 'nullable|numeric',
            'orig_dim_max'             => 'nullable|numeric',
            'wear_dim_min'             => 'nullable|numeric',
            'wear_dim_max'             => 'nullable|numeric',
            'inspection'               => 'nullable|string',
            'sort_order'               => 'nullable|integer',
            'defect_code_ids'          => 'nullable|array',
            'defect_code_ids.*'        => 'exists:codes,id',
        ]);

        $spec = ManualDimensionSpec::create(array_merge(
            \Arr::except($data, ['defect_code_ids']),
            ['manual_dimension_point_id' => $manualDimensionPoint->id]
        ));

        $codeIds = $data['defect_code_ids'] ?? [];
        $this->syncSpecCodes($spec, $codeIds);
        $this->propagateSpecCodes($spec, $codeIds);

        return response()->json($spec->load(['inspectionComponent', 'specCodes.code', 'repairRules.code', 'repairRules.processes']), 201);
    }

    public function update(Request $request, ManualDimensionSpec $manualDimensionSpec)
    {
        $data = $request->validate([
            'spec_type'                => 'sometimes|in:measurement,inspection',
            'inspection_component_id'  => 'nullable|exists:manual_inspection_components,id',
            'description'              => 'sometimes|string|max:255',
            'is_required'              => 'boolean',
            'orig_dim_min'             => 'nullable|numeric',
            'orig_dim_max'             => 'nullable|numeric',
            'wear_dim_min'             => 'nullable|numeric',
            'wear_dim_max'             => 'nullable|numeric',
            'inspection'               => 'nullable|string',
            'sort_order'               => 'nullable|integer',
            'defect_code_ids'          => 'nullable|array',
            'defect_code_ids.*'        => 'exists:codes,id',
        ]);

        $manualDimensionSpec->update(\Arr::except($data, ['defect_code_ids']));

        $codeIds = $data['defect_code_ids'] ?? [];
        $this->syncSpecCodes($manualDimensionSpec, $codeIds);
        $this->propagateSpecCodes($manualDimensionSpec, $codeIds);

        return response()->json($manualDimensionSpec->load(['inspectionComponent', 'specCodes.code']));
    }

    private function syncSpecCodes(ManualDimensionSpec $spec, array $codeIds): void
    {
        $spec->specCodes()->delete();
        foreach (array_unique($codeIds) as $codeId) {
            ManualDimensionSpecCode::create([
                'manual_dimension_spec_id' => $spec->id,
                'codes_id'                 => $codeId,
            ]);
        }
    }

    private function propagateSpecCodes(ManualDimensionSpec $spec, array $codeIds): void
    {
        if (empty($codeIds)) return;

        $spec->loadMissing('point.figure');
        $manualId = $spec->point->figure->manual_id;

        /* All point IDs in this manual that have ANY spec with this description */
        $pointIds = ManualDimensionSpec::whereHas('point.figure', fn($q) => $q->where('manual_id', $manualId))
            ->where('description', $spec->description)
            ->where('id', '!=', $spec->id)
            ->pluck('manual_dimension_point_id')
            ->unique();

        foreach ($pointIds as $pointId) {
            /* Find the inspection spec for this point, create if missing */
            $target = ManualDimensionSpec::where('manual_dimension_point_id', $pointId)
                ->where('description', $spec->description)
                ->where('spec_type', 'inspection')
                ->first();

            if (!$target) {
                $target = ManualDimensionSpec::create([
                    'manual_dimension_point_id' => $pointId,
                    'spec_type'                 => 'inspection',
                    'description'               => $spec->description,
                    'inspection_component_id'   => $spec->inspection_component_id,
                    'is_required'               => $spec->is_required,
                    'sort_order'                => $spec->sort_order,
                ]);
            }

            $this->syncSpecCodes($target, $codeIds);
        }
    }

    private function propagateRepairRule(
        ManualDimensionRepairRule $rule,
        ManualDimensionSpec       $spec,
        string                    $action,
        array                     $processes = [],
        ?string                   $matchTrigger = null,
        mixed                     $matchCodesId = null,
    ): void {
        $spec->loadMissing('point.figure');
        $manualId = $spec->point->figure->manual_id;

        /* All point IDs in this manual that have ANY spec with this description */
        $pointIds = ManualDimensionSpec::whereHas('point.figure', fn($q) => $q->where('manual_id', $manualId))
            ->where('description', $spec->description)
            ->where('id', '!=', $spec->id)
            ->pluck('manual_dimension_point_id')
            ->unique();

        $ruleData = [
            'codes_id'          => $rule->codes_id,
            'trigger'           => $rule->trigger,
            'repair_action'     => $rule->repair_action,
            'no_repair'         => $rule->no_repair,
            'order_replacement' => $rule->order_replacement,
            'notes'             => $rule->notes,
        ];

        foreach ($pointIds as $pointId) {
            /* Find the same-type spec for this point, create inspection spec if missing */
            $target = ManualDimensionSpec::where('manual_dimension_point_id', $pointId)
                ->where('description', $spec->description)
                ->where('spec_type', $spec->spec_type)
                ->first();

            if (!$target && $spec->spec_type === 'inspection') {
                $target = ManualDimensionSpec::create([
                    'manual_dimension_point_id' => $pointId,
                    'spec_type'                 => 'inspection',
                    'description'               => $spec->description,
                    'inspection_component_id'   => $spec->inspection_component_id,
                    'is_required'               => $spec->is_required,
                    'sort_order'                => $spec->sort_order,
                ]);
            }

            if (!$target) continue;

            if ($action === 'create') {
                $newRule = ManualDimensionRepairRule::create(
                    array_merge($ruleData, ['manual_dimension_spec_id' => $target->id])
                );
                $this->syncRuleProcesses($newRule, $processes);
            } elseif ($action === 'update') {
                $target->repairRules()
                    ->where('trigger',  $matchTrigger ?? $rule->trigger)
                    ->where('codes_id', $matchCodesId ?? $rule->codes_id)
                    ->get()
                    ->each(function ($sr) use ($ruleData, $processes) {
                        $sr->update($ruleData);
                        $this->syncRuleProcesses($sr, $processes);
                    });
            } elseif ($action === 'delete') {
                $target->repairRules()
                    ->where('trigger',  $rule->trigger)
                    ->where('codes_id', $rule->codes_id)
                    ->delete();
            }
        }
    }

    public function destroy(ManualDimensionSpec $manualDimensionSpec)
    {
        $manualDimensionSpec->delete();

        return response()->json(['ok' => true]);
    }

    // --- Repair Rules ---

    public function storeRepairRule(Request $request, ManualDimensionSpec $manualDimensionSpec)
    {
        $data = $request->validate([
            'codes_id'          => 'nullable|exists:codes,id',
            'trigger'           => 'required|in:below_orig,above_orig,below_wear,above_wear,finding,manual',
            'repair_action'     => 'required|in:repair,replace,oversize,blend,machine,scrap,other',
            'no_repair'         => 'boolean',
            'order_replacement' => 'boolean',
            'notes'             => 'nullable|string',
            'processes'                       => 'nullable|array',
            'processes.*.manual_process_id'   => 'required|exists:manual_processes,id',
            'processes.*.sort_order'          => 'integer',
        ]);

        $rule = ManualDimensionRepairRule::create([
            'manual_dimension_spec_id' => $manualDimensionSpec->id,
            'codes_id'          => $data['codes_id'] ?? null,
            'trigger'           => $data['trigger'],
            'repair_action'     => $data['repair_action'],
            'no_repair'         => $data['no_repair'] ?? false,
            'order_replacement' => $data['order_replacement'] ?? false,
            'notes'             => $data['notes'] ?? null,
        ]);

        $processes = $data['processes'] ?? [];
        $this->syncRuleProcesses($rule, $processes);
        $this->propagateRepairRule($rule, $manualDimensionSpec, 'create', $processes);

        return response()->json($this->ruleWithProcessLabels($rule), 201);
    }

    public function updateRepairRule(Request $request, ManualDimensionRepairRule $manualDimensionRepairRule)
    {
        $data = $request->validate([
            'codes_id'          => 'nullable|exists:codes,id',
            'trigger'           => 'required|in:below_orig,above_orig,below_wear,above_wear,finding,manual',
            'repair_action'     => 'required|in:repair,replace,oversize,blend,machine,scrap,other',
            'no_repair'         => 'boolean',
            'order_replacement' => 'boolean',
            'notes'             => 'nullable|string',
            'processes'                       => 'nullable|array',
            'processes.*.manual_process_id'   => 'required|exists:manual_processes,id',
            'processes.*.sort_order'          => 'integer',
        ]);

        /* Capture old trigger/codes_id before update for sibling matching */
        $oldTrigger  = $manualDimensionRepairRule->trigger;
        $oldCodesId  = $manualDimensionRepairRule->codes_id;

        $manualDimensionRepairRule->update([
            'codes_id'          => $data['codes_id'] ?? null,
            'trigger'           => $data['trigger'],
            'repair_action'     => $data['repair_action'],
            'no_repair'         => $data['no_repair'] ?? false,
            'order_replacement' => $data['order_replacement'] ?? false,
            'notes'             => $data['notes'] ?? null,
        ]);

        $processes = $data['processes'] ?? [];
        $this->syncRuleProcesses($manualDimensionRepairRule, $processes);
        $this->propagateRepairRule(
            $manualDimensionRepairRule,
            $manualDimensionRepairRule->spec,
            'update',
            $processes,
            $oldTrigger,
            $oldCodesId
        );

        return response()->json($this->ruleWithProcessLabels($manualDimensionRepairRule));
    }

    public function destroyRepairRule(ManualDimensionRepairRule $manualDimensionRepairRule)
    {
        $spec = $manualDimensionRepairRule->spec;
        $this->propagateRepairRule($manualDimensionRepairRule, $spec, 'delete');
        $manualDimensionRepairRule->delete();

        return response()->json(['ok' => true]);
    }

    private function syncRuleProcesses(ManualDimensionRepairRule $rule, array $processes): void
    {
        $rule->processes()->delete();
        foreach ($processes as $i => $p) {
            ManualDimensionRepairRuleProcess::create([
                'repair_rule_id'    => $rule->id,
                'manual_process_id' => $p['manual_process_id'],
                'sort_order'        => $p['sort_order'] ?? $i,
            ]);
        }
    }

    private function ruleWithProcessLabels(ManualDimensionRepairRule $rule): array
    {
        $rule->load(['code', 'processes.manualProcess.process.process_name']);
        $data = $rule->toArray();
        $data['processes'] = $rule->processes->map(function ($rp) {
            $mp = $rp->manualProcess;
            $label = trim(
                ($mp?->process?->process_name?->name ?? '') . ' — ' . ($mp?->process?->process ?? '')
            );
            return [
                'id'               => $rp->id,
                'manual_process_id' => $rp->manual_process_id,
                'sort_order'       => $rp->sort_order,
                'label'            => $label,
            ];
        })->values()->all();
        return $data;
    }

    // --- Allowed Codes ---

    public function storeAllowedCode(Request $request, ManualDimensionSpec $manualDimensionSpec)
    {
        $data = $request->validate([
            'codes_id' => 'required|exists:codes,id',
        ]);

        $specCode = ManualDimensionSpecCode::firstOrCreate([
            'manual_dimension_spec_id' => $manualDimensionSpec->id,
            'codes_id'                 => $data['codes_id'],
        ]);

        return response()->json($specCode->load('code'), 201);
    }

    public function destroyAllowedCode(ManualDimensionSpecCode $manualDimensionSpecCode)
    {
        $manualDimensionSpecCode->delete();

        return response()->json(['ok' => true]);
    }

    // --- Bushing Spec ---

    public function storeBushingSpec(Request $request, ManualDimensionSpec $manualDimensionSpec)
    {
        $data = $request->validate([
            'bushing_od_spec_id'    => 'nullable|exists:manual_dimension_specs,id',
            'paired_bushing_spec_id' => 'nullable|exists:manual_bushing_specs,id',
            'arrangement'           => 'required|in:sequential,same_hole,opposing',
            'interference_value'    => 'required|numeric|min:0',
            'oversize_step'         => 'required|numeric|min:0',
            'max_oversize'          => 'required|numeric|min:0',
            'oversize_rounding'     => 'required|in:ceil,nearest,exact',
            'notes'                 => 'nullable|string',
        ]);

        $bushingSpec = ManualBushingSpec::create(array_merge($data, [
            'hole_spec_id' => $manualDimensionSpec->id,
        ]));

        return response()->json($bushingSpec, 201);
    }

    public function updateBushingSpec(Request $request, ManualBushingSpec $manualBushingSpec)
    {
        $data = $request->validate([
            'bushing_od_spec_id'     => 'nullable|exists:manual_dimension_specs,id',
            'paired_bushing_spec_id' => 'nullable|exists:manual_bushing_specs,id',
            'arrangement'            => 'sometimes|in:sequential,same_hole,opposing',
            'interference_value'     => 'sometimes|numeric|min:0',
            'oversize_step'          => 'sometimes|numeric|min:0',
            'max_oversize'           => 'sometimes|numeric|min:0',
            'oversize_rounding'      => 'sometimes|in:ceil,nearest,exact',
            'notes'                  => 'nullable|string',
        ]);

        $manualBushingSpec->update($data);

        return response()->json($manualBushingSpec);
    }

    public function destroyBushingSpec(ManualBushingSpec $manualBushingSpec)
    {
        $manualBushingSpec->delete();

        return response()->json(['ok' => true]);
    }

    // --- Oversize Options ---

    public function storeOversizeOption(Request $request, ManualBushingSpec $manualBushingSpec)
    {
        $data = $request->validate([
            'oversize_value' => 'required|numeric|min:0',
            'part_number'    => 'nullable|string|max:100',
            'description'    => 'nullable|string|max:255',
        ]);

        $option = ManualBushingOversizeOption::create(array_merge($data, [
            'manual_bushing_spec_id' => $manualBushingSpec->id,
        ]));

        return response()->json($option, 201);
    }

    public function destroyOversizeOption(ManualBushingOversizeOption $manualBushingOversizeOption)
    {
        $manualBushingOversizeOption->delete();

        return response()->json(['ok' => true]);
    }
}
