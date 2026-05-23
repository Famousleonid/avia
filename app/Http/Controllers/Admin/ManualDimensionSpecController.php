<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualBushingSpec;
use App\Models\ManualBushingOversizeOption;
use App\Models\ManualDimensionPoint;
use App\Models\ManualDimensionRepairRule;
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
            'spec_type'           => 'sometimes|in:measurement,inspection',
            'component_id'        => 'nullable|exists:components,id',
            'codes_id'            => 'nullable|exists:codes,id',
            'description'         => 'required|string|max:255',
            'is_required'         => 'boolean',
            'orig_dim_min'        => 'nullable|numeric',
            'orig_dim_max'        => 'nullable|numeric',
            'wear_dim_min'        => 'nullable|numeric',
            'wear_dim_max'        => 'nullable|numeric',
            'inspection'          => 'nullable|string',
            'sort_order'          => 'nullable|integer',
        ]);

        $spec = ManualDimensionSpec::create(array_merge($data, [
            'manual_dimension_point_id' => $manualDimensionPoint->id,
        ]));

        return response()->json($spec->load(['component', 'code', 'repairRules.code', 'repairRules.procedure']), 201);
    }

    public function update(Request $request, ManualDimensionSpec $manualDimensionSpec)
    {
        $data = $request->validate([
            'spec_type'           => 'sometimes|in:measurement,inspection',
            'component_id'        => 'nullable|exists:components,id',
            'codes_id'            => 'nullable|exists:codes,id',
            'description'         => 'sometimes|string|max:255',
            'is_required'         => 'boolean',
            'orig_dim_min'        => 'nullable|numeric',
            'orig_dim_max'        => 'nullable|numeric',
            'wear_dim_min'        => 'nullable|numeric',
            'wear_dim_max'        => 'nullable|numeric',
            'inspection'          => 'nullable|string',
            'sort_order'          => 'nullable|integer',
        ]);

        $manualDimensionSpec->update($data);

        return response()->json($manualDimensionSpec->load(['component', 'code']));
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
            'codes_id'                   => 'nullable|exists:codes,id',
            'trigger'                    => 'required|in:fail,finding,manual',
            'repair_action'              => 'required|in:replace,oversize,blend,machine,scrap,other',
            'manual_repair_procedure_id' => 'nullable|exists:manual_repair_procedures,id',
            'notes'                      => 'nullable|string',
        ]);

        $rule = ManualDimensionRepairRule::create(array_merge($data, [
            'manual_dimension_spec_id' => $manualDimensionSpec->id,
        ]));

        return response()->json($rule->load(['code', 'procedure']), 201);
    }

    public function destroyRepairRule(ManualDimensionRepairRule $manualDimensionRepairRule)
    {
        $manualDimensionRepairRule->delete();

        return response()->json(['ok' => true]);
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
