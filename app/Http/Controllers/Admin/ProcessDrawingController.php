<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualParameterRuleProcess;
use App\Models\ProcessDrawing;
use App\Models\ProcessDrawingElement;
use Illuminate\Http\Request;

class ProcessDrawingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get the drawing for a process inside a point repair rule.
     * Does NOT create — returns a stub {id:null} if none exists yet (lazy create).
     */
    public function show(ManualParameterRuleProcess $manualParameterRuleProcess)
    {
        $sourceParams = $this->sourceParameters($manualParameterRuleProcess);

        $drawing = ProcessDrawing::where('rule_process_id', $manualParameterRuleProcess->id)
            ->with('elements')
            ->first();

        if (!$drawing) {
            return response()->json([
                'id'                => null,
                'rule_process_id'   => $manualParameterRuleProcess->id,
                'drawing_type'      => null,
                'title'             => null,
                'image_path'        => null,
                'image_width'       => null,
                'image_height'      => null,
                'elements'          => [],
                'source_parameters' => $sourceParams,
            ]);
        }

        $payload = $this->payload($drawing);
        $payload['source_parameters'] = $sourceParams;

        return response()->json($payload);
    }

    /**
     * Parameters available as measurement value sources — every parameter linked
     * to the same point(s) as this rule's parameter (i.e. the F&C-paired params).
     */
    private function sourceParameters(ManualParameterRuleProcess $rp): array
    {
        $ruleParam = $rp->rule?->parameter;
        if (!$ruleParam) {
            return [];
        }

        $pointIds = $ruleParam->points()->pluck('manual_dimension_points.id');
        if ($pointIds->isEmpty()) {
            return [];
        }

        return \App\Models\ManualParameter::whereHas('points', fn($q) =>
                $q->whereIn('manual_dimension_points.id', $pointIds))
            ->with('inspectionComponent')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'description' => $p->description,
                'part'        => $p->inspectionComponent?->label,
            ])
            ->values()
            ->all();
    }

    /**
     * Lazily create the drawing on first real action (upload / add element).
     */
    public function store(ManualParameterRuleProcess $manualParameterRuleProcess)
    {
        $drawing = ProcessDrawing::firstOrCreate(
            ['rule_process_id' => $manualParameterRuleProcess->id]
        );

        return response()->json($this->payload($drawing->load('elements')), 201);
    }

    public function uploadImage(Request $request, ProcessDrawing $processDrawing)
    {
        $request->validate([
            'image' => 'required|file|image|mimes:png,jpg,jpeg,webp,gif|max:10240',
        ]);

        // Image stored on the owning manual's media (same scheme as dimension figures)
        $manual = $processDrawing->ruleProcess?->rule?->parameter?->manual;
        if (!$manual) {
            return response()->json(['message' => 'Manual not found for this drawing'], 422);
        }

        $media = $manual->addMedia($request->file('image'))->toMediaCollection('process-drawings');

        return response()->json([
            'path'     => route('image.show.big', [
                'mediaId'   => $media->id,
                'modelId'   => $manual->id,
                'mediaName' => 'process-drawings',
            ]),
            'media_id' => $media->id,
        ]);
    }

    public function update(Request $request, ProcessDrawing $processDrawing)
    {
        $data = $request->validate([
            'title'        => 'nullable|string|max:255',
            'drawing_type' => 'nullable|string|max:100',
            'image_path'   => 'nullable|string|max:1000',
            'image_width'  => 'nullable|integer',
            'image_height' => 'nullable|integer',
        ]);

        $processDrawing->update($data);

        return response()->json($this->payload($processDrawing->fresh('elements')));
    }

    // ── Elements ──────────────────────────────────────────────────

    public function storeElement(Request $request, ProcessDrawing $processDrawing)
    {
        $data = $this->validateElement($request);
        $element = $processDrawing->elements()->create($data);

        return response()->json($this->elementPayload($element), 201);
    }

    public function updateElement(Request $request, ProcessDrawingElement $processDrawingElement)
    {
        $data = $this->validateElement($request, true);
        $processDrawingElement->update($data);

        return response()->json($this->elementPayload($processDrawingElement->fresh()));
    }

    public function destroyElement(ProcessDrawingElement $processDrawingElement)
    {
        $processDrawingElement->delete();

        return response()->json(['ok' => true]);
    }

    private function validateElement(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes|required' : 'required';

        return $request->validate([
            'element_type'        => "$req|in:dimension,label,text",
            'x_pct'               => 'nullable|numeric',
            'y_pct'               => 'nullable|numeric',
            'x2_pct'              => 'nullable|numeric',
            'y2_pct'              => 'nullable|numeric',
            'label_x_pct'         => 'nullable|numeric',
            'label_y_pct'         => 'nullable|numeric',
            'mask'                => 'nullable|in:diameter,linear',
            'value_source'        => 'nullable|in:static,measurement',
            'static_value'        => 'nullable|numeric',
            'source_parameter_id' => 'nullable|exists:manual_parameters,id',
            'placeholder'         => 'nullable|string|max:100',
            'text'                => 'nullable|string|max:255',
            'sort_order'          => 'nullable|integer',
        ]);
    }

    private function payload(ProcessDrawing $d): array
    {
        return [
            'id'              => $d->id,
            'rule_process_id' => $d->rule_process_id,
            'drawing_type'    => $d->drawing_type,
            'title'           => $d->title,
            'image_path'      => $d->image_path,
            'image_width'     => $d->image_width,
            'image_height'    => $d->image_height,
            'elements'        => $d->elements->map(fn($e) => $this->elementPayload($e))->values(),
        ];
    }

    private function elementPayload(ProcessDrawingElement $e): array
    {
        return [
            'id'                  => $e->id,
            'element_type'        => $e->element_type,
            'x_pct'               => $e->x_pct,
            'y_pct'               => $e->y_pct,
            'x2_pct'              => $e->x2_pct,
            'y2_pct'              => $e->y2_pct,
            'label_x_pct'         => $e->label_x_pct,
            'label_y_pct'         => $e->label_y_pct,
            'mask'                => $e->mask,
            'value_source'        => $e->value_source,
            'static_value'        => $e->static_value,
            'source_parameter_id' => $e->source_parameter_id,
            'placeholder'         => $e->placeholder,
            'text'                => $e->text,
            'sort_order'          => $e->sort_order,
        ];
    }
}
