<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
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
            'point_type'         => 'required|in:navigation,measurement,circle,text,view',
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
            'extra_anchors'             => 'nullable|array',
            'extra_anchors.*.x_pct'     => 'required_with:extra_anchors|numeric|min:0|max:100',
            'extra_anchors.*.y_pct'     => 'required_with:extra_anchors|numeric|min:0|max:100',
            'rotation_deg'       => 'nullable|numeric|min:-360|max:360',
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
            'point_type'         => 'sometimes|in:navigation,measurement,circle,text,view',
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
            'extra_anchors'             => 'nullable|array',
            'extra_anchors.*.x_pct'     => 'required_with:extra_anchors|numeric|min:0|max:100',
            'extra_anchors.*.y_pct'     => 'required_with:extra_anchors|numeric|min:0|max:100',
            'rotation_deg'       => 'nullable|numeric|min:-360|max:360',
            'sort_order'         => 'nullable|integer',
        ]);

        // A part label keeps an internal slug in `code` (NOT NULL). The editor sends
        // code=null for text points, so preserve the existing slug (or mint one).
        $effectiveType = $data['point_type'] ?? $manualDimensionPoint->point_type;
        if ($effectiveType === 'text' && empty($data['code'] ?? null)) {
            if (empty($manualDimensionPoint->code)) {
                $data['code'] = 'lbl_' . ($data['child_ic_id'] ?? $manualDimensionPoint->child_ic_id ?? uniqid());
            } else {
                unset($data['code']);
            }
        }

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

    /**
     * Remove the manual's unattached points — leftovers of deleted parameters
     * and parts that linger as ghost marks on the WO Measurements figure:
     *   - measurement points no parameter is attached to;
     *   - text callouts that lost their part (child_ic FK is SET NULL on part
     *     delete) and carry no free-text description of their own.
     */
    public function cleanupUnattached(Manual $manual)
    {
        $figureIds = $manual->dimensionFigures()->pluck('id');

        $orphanMeasurement = ManualDimensionPoint::whereIn('manual_dimension_figure_id', $figureIds)
            ->where('point_type', 'measurement')
            ->whereDoesntHave('parameters')
            ->pluck('id');

        $orphanCallouts = ManualDimensionPoint::whereIn('manual_dimension_figure_id', $figureIds)
            ->where('point_type', 'text')
            ->whereNull('child_ic_id')
            ->where(fn ($q) => $q->whereNull('description')->orWhere('description', ''))
            ->pluck('id');

        $deleted = $orphanMeasurement->merge($orphanCallouts);
        ManualDimensionPoint::whereIn('id', $deleted)->delete();

        return response()->json([
            'measurement' => $orphanMeasurement->count(),
            'callouts'    => $orphanCallouts->count(),
            'deleted_ids' => $deleted->values(),
        ]);
    }
}
