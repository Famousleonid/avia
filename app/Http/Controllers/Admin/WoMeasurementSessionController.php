<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\ManualDimensionFigure;
use App\Models\ManualRepairProcedure;
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
            ->with(['instruction', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($sessions);
    }

    public function store(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'instruction_id' => 'required|exists:instructions,id',
        ]);

        $session = WoMeasurementSession::create(array_merge($data, [
            'workorder_id' => $workorder->id,
            'user_id'      => auth()->id(),
            'status'       => 'open',
        ]));

        return response()->json($session->load(['instruction', 'user']), 201);
    }

    public function show(WoMeasurementSession $woMeasurementSession)
    {
        $session = $woMeasurementSession->load([
            'instruction',
            'user',
            'measurements.spec',
            'measurements.code',
            'measurements.user',
            'measurements.replaces',
        ]);

        $manual = $woMeasurementSession->workorder->unit->manuals;

        $inspectionComponents = $manual->inspectionComponents()
            ->with('variants.component')
            ->get();

        $figures = ManualDimensionFigure::where('manual_id', $manual->id)
            ->with([
                'points.specs.inspectionComponent',
                'points.specs.repairRules.code',
                'points.specs.repairRules.processes.manualProcess.process.process_name',
                'points.specs.bushingSpec',
            ])
            ->orderBy('sort_order')
            ->get();

        if (request()->expectsJson()) {
            return response()->json($session);
        }

        $codes = Code::orderBy('name')->get(['id', 'name', 'code']);
        $repairProcedures = ManualRepairProcedure::where('manual_id', $manual->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.measurement_sessions.show', compact(
            'session', 'inspectionComponents', 'figures', 'codes', 'repairProcedures'
        ));
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
