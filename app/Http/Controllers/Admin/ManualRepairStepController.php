<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualParameter;
use App\Models\ManualRepairStep;
use Illuminate\Http\Request;

class ManualRepairStepController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(ManualParameter $manualParameter)
    {
        $steps = $manualParameter->repairSteps()
            ->with('component')
            ->get()
            ->map(fn($s) => $this->payload($s));

        return response()->json($steps);
    }

    public function store(Request $request, ManualParameter $manualParameter)
    {
        $data = $this->validateData($request);

        $step = ManualRepairStep::create([
            'manual_parameter_id' => $manualParameter->id,
            'step_no'             => $data['step_no'],
            'component_id'        => $data['component_id'] ?? null,
            'dim_min'             => $data['dim_min'] ?? null,
            'dim_max'             => $data['dim_max'] ?? null,
            'after_dim_min'       => $data['after_dim_min'] ?? null,
            'after_dim_max'       => $data['after_dim_max'] ?? null,
            'sort_order'          => $data['sort_order'] ?? 0,
        ]);

        return response()->json($this->payload($step->load('component')), 201);
    }

    public function update(Request $request, ManualRepairStep $manualRepairStep)
    {
        $data = $this->validateData($request, true);

        $manualRepairStep->update($data);

        return response()->json($this->payload($manualRepairStep->fresh()->load('component')));
    }

    public function destroy(ManualRepairStep $manualRepairStep)
    {
        $manualRepairStep->delete();

        return response()->json(['ok' => true]);
    }

    private function validateData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'step_no'       => ($partial ? 'sometimes|' : '') . 'required|string|max:20',
            'component_id'  => 'nullable|exists:components,id',
            'dim_min'       => 'nullable|numeric',
            'dim_max'       => 'nullable|numeric',
            'after_dim_min' => 'nullable|numeric',
            'after_dim_max' => 'nullable|numeric',
            'sort_order'    => 'nullable|integer',
        ]);
    }

    private function payload(ManualRepairStep $step): array
    {
        return [
            'id'            => $step->id,
            'step_no'       => $step->step_no,
            'sort_order'    => $step->sort_order,
            'dim_min'       => $step->dim_min,
            'dim_max'       => $step->dim_max,
            'after_dim_min' => $step->after_dim_min,
            'after_dim_max' => $step->after_dim_max,
            'component_id'  => $step->component_id,
            'component'     => $step->component ? [
                'id'          => $step->component->id,
                'ipl_num'     => $step->component->ipl_num,
                'part_number' => $step->component->part_number,
                'name'        => $step->component->name,
            ] : null,
        ];
    }
}
