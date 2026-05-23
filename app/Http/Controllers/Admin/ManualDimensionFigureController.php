<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualDimensionFigure;
use Illuminate\Http\Request;

class ManualDimensionFigureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function uploadImage(Request $request, Manual $manual)
    {
        $request->validate([
            'image' => 'required|file|image|mimes:png,jpg,jpeg,webp,gif|max:10240',
        ]);

        $media = $manual
            ->addMedia($request->file('image'))
            ->toMediaCollection('dimension-figures');

        return response()->json([
            'path'     => route('image.show.big', [
                'mediaId'   => $media->id,
                'modelId'   => $manual->id,
                'mediaName' => 'dimension-figures',
            ]),
            'media_id' => $media->id,
        ]);
    }

    public function index(Manual $manual)
    {
        $figures = ManualDimensionFigure::where('manual_id', $manual->id)
            ->with(['childFigures', 'points'])
            ->orderBy('sort_order')
            ->get();

        return response()->json($figures);
    }

    public function store(Request $request, Manual $manual)
    {
        $data = $request->validate([
            'parent_figure_id' => 'nullable|exists:manual_dimension_figures,id',
            'figure_type'      => 'required|in:overview,detail',
            'title'            => 'required|string|max:255',
            'image_path'       => 'required|string|max:255',
            'image_width'      => 'nullable|integer',
            'image_height'     => 'nullable|integer',
            'sort_order'       => 'nullable|integer',
        ]);

        $figure = ManualDimensionFigure::create(array_merge($data, ['manual_id' => $manual->id]));

        return response()->json($figure, 201);
    }

    public function update(Request $request, ManualDimensionFigure $manualDimensionFigure)
    {
        $data = $request->validate([
            'parent_figure_id' => 'nullable|exists:manual_dimension_figures,id',
            'figure_type'      => 'sometimes|in:overview,detail',
            'title'            => 'sometimes|string|max:255',
            'image_path'       => 'sometimes|string|max:255',
            'image_width'      => 'nullable|integer',
            'image_height'     => 'nullable|integer',
            'sort_order'       => 'nullable|integer',
        ]);

        $manualDimensionFigure->update($data);

        return response()->json($manualDimensionFigure);
    }

    public function destroy(ManualDimensionFigure $manualDimensionFigure)
    {
        $manualDimensionFigure->delete();

        return response()->json(['ok' => true]);
    }
}
