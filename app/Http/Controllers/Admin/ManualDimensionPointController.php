<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualDimensionFigure;
use App\Models\ManualDimensionPoint;
use App\Models\ManualFit;
use Illuminate\Http\Request;

class ManualDimensionPointController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, ManualDimensionFigure $manualDimensionFigure)
    {
        $data = $request->validate([
            'point_type'         => 'required|in:navigation,measurement,circle,text',
            'child_figure_id'    => 'nullable|exists:manual_dimension_figures,id',
            'child_ic_id'        => 'nullable|exists:manual_inspection_components,id',
            'code'               => 'required_unless:point_type,text|nullable|string|max:50',
            'description'        => 'nullable|string|max:255',
            'is_fits_clearance'  => 'boolean',
            'x_pct'              => 'required|numeric|min:0|max:100',
            'y_pct'              => 'required|numeric|min:0|max:100',
            'width_pct'          => 'nullable|numeric|min:0.1|max:100',
            'height_pct'         => 'nullable|numeric|min:0.1|max:100',
            'x2_pct'             => 'nullable|numeric|min:0|max:100',
            'y2_pct'             => 'nullable|numeric|min:0|max:100',
            'label_x_pct'        => 'nullable|numeric|min:0|max:100',
            'label_y_pct'        => 'nullable|numeric|min:0|max:100',
            'sort_order'         => 'nullable|integer',
        ]);

        if ($data['point_type'] === 'text' && empty($data['code'])) {
            $data['code'] = 'lbl_' . ($data['child_ic_id'] ?? uniqid());
        }

        $point = ManualDimensionPoint::create(array_merge($data, [
            'manual_dimension_figure_id' => $manualDimensionFigure->id,
        ]));

        return response()->json($point->load('childIc'), 201);
    }

    public function update(Request $request, ManualDimensionPoint $manualDimensionPoint)
    {
        $data = $request->validate([
            'point_type'         => 'sometimes|in:navigation,measurement,circle,text',
            'child_figure_id'    => 'nullable|exists:manual_dimension_figures,id',
            'child_ic_id'        => 'nullable|exists:manual_inspection_components,id',
            'code'               => 'sometimes|nullable|string|max:50',
            'description'        => 'nullable|string|max:255',
            'is_fits_clearance'  => 'boolean',
            'x_pct'              => 'sometimes|numeric|min:0|max:100',
            'y_pct'              => 'sometimes|numeric|min:0|max:100',
            'width_pct'          => 'nullable|numeric|min:0.1|max:100',
            'height_pct'         => 'nullable|numeric|min:0.1|max:100',
            'x2_pct'             => 'nullable|numeric|min:0|max:100',
            'y2_pct'             => 'nullable|numeric|min:0|max:100',
            'label_x_pct'        => 'nullable|numeric|min:0|max:100',
            'label_y_pct'        => 'nullable|numeric|min:0|max:100',
            'sort_order'         => 'nullable|integer',
        ]);

        $manualDimensionPoint->update($data);

        // Keep manual_fit.is_fc in sync with the point's F&C checkbox: a fit
        // anchored on this point (both members attached here) is shown in the
        // F&C table iff the point is flagged Fits & Clearances. Cross-point fits
        // (members on other points) are not touched.
        if (array_key_exists('is_fits_clearance', $data)) {
            $paramIds = $manualDimensionPoint->parameters()->pluck('manual_parameters.id');
            if ($paramIds->isNotEmpty()) {
                ManualFit::whereIn('od_param_id', $paramIds)
                    ->whereIn('id_param_id', $paramIds)
                    ->update(['is_fc' => (bool) $data['is_fits_clearance']]);
            }
        }

        return response()->json($manualDimensionPoint->fresh()->load('childIc'));
    }

    public function destroy(ManualDimensionPoint $manualDimensionPoint)
    {
        $manualDimensionPoint->delete();

        return response()->json(['ok' => true]);
    }
}
