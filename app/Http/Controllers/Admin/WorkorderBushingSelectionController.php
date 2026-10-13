<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Component, Workorder};
use App\Services\WorkorderBushingSelection;
use Illuminate\Http\Request;

class WorkorderBushingSelectionController extends Controller
{
    public function show(Workorder $workorder, WorkorderBushingSelection $selection)
    {
        return response()->json($selection->snapshot($workorder))->header('Cache-Control', 'no-store');
    }

    public function update(Request $request, Workorder $workorder, Component $component, WorkorderBushingSelection $selection)
    {
        $data = $request->validate([
            'revision' => 'required|string|size:64', 'items' => 'required|array|min:1',
            'items.*.selected' => 'required|boolean', 'items.*.qty' => 'required|integer|min:1',
            'items.*.codes_id' => 'nullable|integer|exists:codes,id', 'items.*.do_not_order' => 'sometimes|boolean',
        ]);
        return response()->json($selection->update($workorder, $component, $data['items'], $data['revision']));
    }
}
