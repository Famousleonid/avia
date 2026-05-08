<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use App\Models\Workorder;
use App\Models\WorkorderUnitInspection;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TdrUnitInspectionController extends Controller
{
    public function create(int $workorder_id): Application|Factory|View
    {
        $current_wo = Workorder::findOrFail($workorder_id);

        $existingConditionIds = WorkorderUnitInspection::query()
            ->where('workorder_id', $workorder_id)
            ->pluck('condition_id')
            ->filter()
            ->unique()
            ->toArray();

        $unit_conditions = Condition::query()
            ->where('unit', true)
            ->whereNotIn('id', $existingConditionIds)
            ->get();

        return view('admin.tdrs.unit-inspection', compact('current_wo', 'unit_conditions'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'conditions' => 'required|array',
        ]);

        $workorderId = (int) $request->input('workorder_id');
        $conditions = $request->input('conditions', []);

        try {
            $existingInspections = WorkorderUnitInspection::query()
                ->where('workorder_id', $workorderId)
                ->get()
                ->keyBy('condition_id');

            $processedConditionIds = [];

            foreach ($conditions as $conditionId => $conditionData) {
                $conditionId = (int) $conditionId;
                if (! isset($conditionData['selected']) || ! $conditionData['selected']) {
                    continue;
                }

                $processedConditionIds[] = $conditionId;
                $notes = $conditionData['notes'] ?? '';
                $inspectionId = $conditionData['inspection_id'] ?? $conditionData['tdr_id'] ?? null;

                $condition = Condition::query()->find($conditionId);
                if (! $condition) {
                    Log::warning("Condition with ID {$conditionId} not found");
                    continue;
                }

                if ($inspectionId && $existingInspections->has($conditionId)) {
                    $inspection = $existingInspections->get($conditionId);
                    if ((int) $inspection->id === (int) $inspectionId) {
                        $inspection->notes = $notes;
                        $inspection->save();
                        continue;
                    }
                }

                if ($existingInspections->has($conditionId)) {
                    $inspection = $existingInspections->get($conditionId);
                    $inspection->notes = $notes;
                    $inspection->save();
                    continue;
                }

                WorkorderUnitInspection::query()->create([
                    'workorder_id' => $workorderId,
                    'condition_id' => $conditionId,
                    'notes' => $notes,
                    'qty' => 1,
                    'serial_number' => 'NSN',
                    'assy_serial_number' => ' ',
                    'use_tdr' => true,
                    'use_process_forms' => false,
                ]);
            }

            foreach ($existingInspections as $conditionId => $inspection) {
                if (in_array((int) $conditionId, $processedConditionIds, true)) {
                    continue;
                }

                $missingCondition = Condition::query()
                    ->where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                    ->where('id', $conditionId)
                    ->exists();

                if (! $missingCondition) {
                    $inspection->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('Unit inspections saved successfully.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while saving unit inspections.') . ' ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(WorkorderUnitInspection $inspection): RedirectResponse
    {
        $workorderId = $inspection->workorder_id;
        $inspection->delete();

        return redirect()->route('tdrs.show', ['id' => $workorderId])
            ->with('success', 'Inspection deleted successfully.');
    }
}
