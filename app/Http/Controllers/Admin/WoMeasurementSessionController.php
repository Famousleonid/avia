<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use App\Models\WoMeasurementSession;
use Illuminate\Http\Request;

class WoMeasurementSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Workorder $workorder)
    {
        $sessions = WoMeasurementSession::where('workorder_id', $workorder->id)
            ->with(['tdr.component', 'figure', 'instruction', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($sessions);
    }

    public function store(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'tdr_id'                     => 'nullable|exists:tdrs,id',
            'manual_dimension_figure_id' => 'required|exists:manual_dimension_figures,id',
            'instruction_id'             => 'required|exists:instructions,id',
        ]);

        $session = WoMeasurementSession::create(array_merge($data, [
            'workorder_id' => $workorder->id,
            'user_id'      => auth()->id(),
            'status'       => 'open',
        ]));

        return response()->json($session->load(['tdr.component', 'figure', 'instruction', 'user']), 201);
    }

    public function show(WoMeasurementSession $woMeasurementSession)
    {
        $session = $woMeasurementSession->load([
            'tdr.component',
            'figure.points.specs.component',
            'figure.points.specs.repairRules.code',
            'figure.points.specs.repairRules.procedure',
            'instruction',
            'user',
            'measurements.spec',
            'measurements.code',
            'measurements.repairProcedure',
            'measurements.user',
            'measurements.replaces',
        ]);

        if (request()->expectsJson()) {
            return response()->json($session);
        }

        $codes = \App\Models\Code::orderBy('name')->get(['id', 'name', 'code']);
        $repairProcedures = \App\Models\ManualRepairProcedure::where('manual_id', $session->figure->manual_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.measurement_sessions.show', compact('session', 'codes', 'repairProcedures'));
    }

    public function destroy(WoMeasurementSession $woMeasurementSession)
    {
        if ($woMeasurementSession->isFinalized()) {
            return response()->json(['error' => 'Cannot delete a finalized session.'], 422);
        }

        $woMeasurementSession->delete();

        return response()->json(['ok' => true]);
    }

    public function finalize(WoMeasurementSession $woMeasurementSession)
    {
        if ($woMeasurementSession->isFinalized()) {
            return response()->json(['error' => 'Session already finalized.'], 422);
        }

        if (! $woMeasurementSession->canFinalize()) {
            return response()->json(['error' => 'Not all required measurements are complete or FAIL records missing repair action.'], 422);
        }

        $woMeasurementSession->update([
            'status'       => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => auth()->id(),
        ]);

        return response()->json($woMeasurementSession->fresh());
    }
}
