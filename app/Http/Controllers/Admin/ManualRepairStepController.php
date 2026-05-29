<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualDimensionPoint;
use App\Models\ManualRepairStep;
use App\Models\ManualRepairStepDim;
use Illuminate\Http\Request;

class ManualRepairStepController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(ManualDimensionPoint $manualDimensionPoint)
    {
        $steps = $manualDimensionPoint->repairSteps()
            ->with(['component', 'dims'])
            ->get()
            ->map(fn($s) => $this->payload($s));

        return response()->json($steps);
    }

    public function store(Request $request, ManualDimensionPoint $manualDimensionPoint)
    {
        $data = $request->validate([
            'step_no'      => 'required|string|max:20',
            'component_id' => 'nullable|exists:components,id',
            'sort_order'   => 'nullable|integer',
            'dims'         => 'nullable|array',
            'dims.*.manual_parameter_id' => 'required|exists:manual_parameters,id',
            'dims.*.dim_min'             => 'nullable|numeric',
            'dims.*.dim_max'             => 'nullable|numeric',
            'dims.*.after_dim_min'       => 'nullable|numeric',
            'dims.*.after_dim_max'       => 'nullable|numeric',
        ]);

        $step = ManualRepairStep::create([
            'dimension_point_id' => $manualDimensionPoint->id,
            'step_no'            => $data['step_no'],
            'component_id'       => $data['component_id'] ?? null,
            'sort_order'         => $data['sort_order'] ?? 0,
        ]);

        foreach ($data['dims'] ?? [] as $dim) {
            ManualRepairStepDim::create([
                'repair_step_id'      => $step->id,
                'manual_parameter_id' => $dim['manual_parameter_id'],
                'dim_min'             => $dim['dim_min'] ?? null,
                'dim_max'             => $dim['dim_max'] ?? null,
                'after_dim_min'       => $dim['after_dim_min'] ?? null,
                'after_dim_max'       => $dim['after_dim_max'] ?? null,
            ]);
        }

        return response()->json($this->payload($step->load(['component', 'dims'])), 201);
    }

    public function update(Request $request, ManualRepairStep $manualRepairStep)
    {
        $data = $request->validate([
            'step_no'      => 'sometimes|string|max:20',
            'component_id' => 'nullable|exists:components,id',
            'sort_order'   => 'nullable|integer',
            'dims'         => 'nullable|array',
            'dims.*.manual_parameter_id' => 'required|exists:manual_parameters,id',
            'dims.*.dim_min'             => 'nullable|numeric',
            'dims.*.dim_max'             => 'nullable|numeric',
            'dims.*.after_dim_min'       => 'nullable|numeric',
            'dims.*.after_dim_max'       => 'nullable|numeric',
        ]);

        $manualRepairStep->update([
            'step_no'      => $data['step_no']      ?? $manualRepairStep->step_no,
            'component_id' => array_key_exists('component_id', $data) ? $data['component_id'] : $manualRepairStep->component_id,
            'sort_order'   => $data['sort_order']   ?? $manualRepairStep->sort_order,
        ]);

        if (array_key_exists('dims', $data)) {
            $manualRepairStep->dims()->delete();
            foreach ($data['dims'] as $dim) {
                ManualRepairStepDim::create([
                    'repair_step_id'      => $manualRepairStep->id,
                    'manual_parameter_id' => $dim['manual_parameter_id'],
                    'dim_min'             => $dim['dim_min'] ?? null,
                    'dim_max'             => $dim['dim_max'] ?? null,
                    'after_dim_min'       => $dim['after_dim_min'] ?? null,
                    'after_dim_max'       => $dim['after_dim_max'] ?? null,
                ]);
            }
        }

        return response()->json($this->payload($manualRepairStep->fresh()->load(['component', 'dims'])));
    }

    public function destroy(ManualRepairStep $manualRepairStep)
    {
        $manualRepairStep->delete();

        return response()->json(['ok' => true]);
    }

    private function payload(ManualRepairStep $step): array
    {
        return [
            'id'           => $step->id,
            'step_no'      => $step->step_no,
            'sort_order'   => $step->sort_order,
            'component_id' => $step->component_id,
            'component'    => $step->component ? [
                'id'          => $step->component->id,
                'ipl_num'     => $step->component->ipl_num,
                'part_number' => $step->component->part_number,
                'name'        => $step->component->name,
            ] : null,
            'dims' => $step->dims->map(fn($d) => [
                'id'                  => $d->id,
                'manual_parameter_id' => $d->manual_parameter_id,
                'dim_min'             => $d->dim_min,
                'dim_max'             => $d->dim_max,
                'after_dim_min'       => $d->after_dim_min,
                'after_dim_max'       => $d->after_dim_max,
            ])->values(),
        ];
    }
}
