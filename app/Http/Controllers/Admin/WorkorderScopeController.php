<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\Workorder;
use App\Services\WorkorderScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkorderScopeController extends Controller
{
    public function options(
        Request $request,
        Workorder $workorder,
        WorkorderScopeService $scopeService
    ): JsonResponse {
        abort_unless(auth()->user()?->can('workorders.update'), 403);

        $validated = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
        ]);
        $unit = Unit::query()->findOrFail((int) $validated['unit_id']);

        return response()->json($scopeService->optionsForUnit($unit));
    }
}
