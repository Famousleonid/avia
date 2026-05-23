<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualDimensionSpec;
use App\Models\WoMeasurement;
use App\Models\WoMeasurementSession;
use Illuminate\Http\Request;

class WoMeasurementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, WoMeasurementSession $woMeasurementSession)
    {
        if ($woMeasurementSession->isFinalized()) {
            return response()->json(['error' => 'Session is finalized.'], 422);
        }

        $data = $request->validate([
            'manual_dimension_spec_id'   => 'required|exists:manual_dimension_specs,id',
            'stage'                      => 'required|in:initial,final',
            'replaces_id'                => 'nullable|exists:wo_measurements,id',
            'actual_value'               => 'nullable|numeric',
            'codes_id'                   => 'nullable|exists:codes,id',
            'finding_notes'              => 'nullable|string',
            'repair_required'            => 'boolean',
            'repair_action'              => 'nullable|in:replace,oversize,blend,machine,scrap,other',
            'manual_repair_procedure_id' => 'nullable|exists:manual_repair_procedures,id',
            'notes'                      => 'nullable|string',
        ]);

        $spec = ManualDimensionSpec::findOrFail($data['manual_dimension_spec_id']);
        $useWear = $woMeasurementSession->instruction->name === 'Repair';
        $limits = $spec->effectiveLimits($useWear);

        $data['limits_source'] = $limits['source'];
        $data['result'] = $this->computeResult($data['actual_value'], $limits);
        $data['user_id'] = auth()->id();

        if (isset($data['actual_value']) && $data['result'] === 'PASS' && isset($spec->bushingSpec)) {
            $data['calculated_oversize'] = $spec->bushingSpec->calculateOversize((float) $data['actual_value']);
        }

        $measurement = WoMeasurement::create(array_merge($data, [
            'wo_measurement_session_id' => $woMeasurementSession->id,
        ]));

        return response()->json($measurement->load(['spec', 'code', 'repairProcedure', 'user', 'replaces']), 201);
    }

    public function update(Request $request, WoMeasurement $woMeasurement)
    {
        if ($woMeasurement->session->isFinalized()) {
            return response()->json(['error' => 'Session is finalized.'], 422);
        }

        $data = $request->validate([
            'actual_value'               => 'nullable|numeric',
            'codes_id'                   => 'nullable|exists:codes,id',
            'finding_notes'              => 'nullable|string',
            'repair_required'            => 'boolean',
            'repair_action'              => 'nullable|in:replace,oversize,blend,machine,scrap,other',
            'manual_repair_procedure_id' => 'nullable|exists:manual_repair_procedures,id',
            'notes'                      => 'nullable|string',
        ]);

        if (array_key_exists('actual_value', $data)) {
            $spec = $woMeasurement->spec;
            $useWear = $woMeasurement->session->instruction->name === 'Repair';
            $limits = $spec->effectiveLimits($useWear);
            $data['limits_source'] = $limits['source'];
            $data['result'] = $this->computeResult($data['actual_value'], $limits);

            if ($data['actual_value'] !== null && $data['result'] === 'PASS' && isset($spec->bushingSpec)) {
                $data['calculated_oversize'] = $spec->bushingSpec->calculateOversize((float) $data['actual_value']);
            }
        }

        $woMeasurement->update($data);

        return response()->json($woMeasurement->load(['spec', 'code', 'repairProcedure', 'user']));
    }

    public function destroy(WoMeasurement $woMeasurement)
    {
        if ($woMeasurement->session->isFinalized()) {
            return response()->json(['error' => 'Session is finalized.'], 422);
        }

        $woMeasurement->delete();

        return response()->json(['ok' => true]);
    }

    private function computeResult(?float $value, array $limits): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($limits['min'] !== null && $limits['max'] !== null) {
            return ($value >= $limits['min'] && $value <= $limits['max']) ? 'PASS' : 'FAIL';
        }

        return null;
    }
}
