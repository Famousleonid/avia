<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualRepairProcedure;
use App\Models\ManualRepairProcedureStep;
use Illuminate\Http\Request;

class ManualRepairProcedureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Manual $manual)
    {
        $procedures = ManualRepairProcedure::where('manual_id', $manual->id)
            ->with(['steps.processName'])
            ->orderBy('name')
            ->get();

        return response()->json($procedures);
    }

    public function store(Request $request, Manual $manual)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $procedure = ManualRepairProcedure::create(array_merge($data, ['manual_id' => $manual->id]));

        return response()->json($procedure->load('steps.processName'), 201);
    }

    public function update(Request $request, ManualRepairProcedure $manualRepairProcedure)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $manualRepairProcedure->update($data);

        return response()->json($manualRepairProcedure);
    }

    public function destroy(ManualRepairProcedure $manualRepairProcedure)
    {
        $manualRepairProcedure->delete();

        return response()->json(['ok' => true]);
    }

    // --- Steps ---

    public function storeStep(Request $request, ManualRepairProcedure $manualRepairProcedure)
    {
        $data = $request->validate([
            'process_name_id' => 'required|exists:process_names,id',
            'sort_order'      => 'nullable|integer',
            'notes'           => 'nullable|string',
        ]);

        $step = ManualRepairProcedureStep::create(array_merge($data, [
            'manual_repair_procedure_id' => $manualRepairProcedure->id,
        ]));

        return response()->json($step->load('processName'), 201);
    }

    public function updateStep(Request $request, ManualRepairProcedureStep $manualRepairProcedureStep)
    {
        $data = $request->validate([
            'process_name_id' => 'sometimes|exists:process_names,id',
            'sort_order'      => 'nullable|integer',
            'notes'           => 'nullable|string',
        ]);

        $manualRepairProcedureStep->update($data);

        return response()->json($manualRepairProcedureStep->load('processName'));
    }

    public function destroyStep(ManualRepairProcedureStep $manualRepairProcedureStep)
    {
        $manualRepairProcedureStep->delete();

        return response()->json(['ok' => true]);
    }

    public function reorderSteps(Request $request, ManualRepairProcedure $manualRepairProcedure)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:manual_repair_procedure_steps,id',
        ]);

        foreach ($request->order as $sortOrder => $stepId) {
            ManualRepairProcedureStep::where('id', $stepId)
                ->where('manual_repair_procedure_id', $manualRepairProcedure->id)
                ->update(['sort_order' => $sortOrder]);
        }

        return response()->json(['ok' => true]);
    }
}
