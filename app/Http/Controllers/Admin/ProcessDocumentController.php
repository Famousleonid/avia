<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualInspectionComponent;
use App\Models\ManualParameter;
use App\Models\ManualParameterRuleProcess;
use App\Models\MasterRulePhaseRuleProcess;
use App\Models\ProcessDocument;
use App\Models\ProcessDocumentElement;
use App\Models\ProcessDocumentPage;
use App\Models\Workorder;
use App\Services\Measurements\ProcessDocumentRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProcessDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ── Documents (Main = point rule process) ─────────────────────

    /** List documents of a Main process (+ F&C source parameters for measurement values). */
    public function index(ManualParameterRuleProcess $manualParameterRuleProcess)
    {
        return $this->listDocuments($manualParameterRuleProcess, $this->sourceParameters($manualParameterRuleProcess));
    }

    public function storeDocument(Request $request, ManualParameterRuleProcess $manualParameterRuleProcess)
    {
        return $this->createDocument($request, $manualParameterRuleProcess);
    }

    // ── Documents (Start/Finish = master-rule phase process) ──────
    // Accompanying docs only (manual page copies) — no measurement source params.

    public function indexPhase(MasterRulePhaseRuleProcess $masterRulePhaseRuleProcess)
    {
        return $this->listDocuments($masterRulePhaseRuleProcess, []);
    }

    public function storePhaseDocument(Request $request, MasterRulePhaseRuleProcess $masterRulePhaseRuleProcess)
    {
        return $this->createDocument($request, $masterRulePhaseRuleProcess, 'manual_page');
    }

    // ── Documents (part level = inspection component) ─────────────
    // The EC dimensions sheet: one part drawing, measurement elements bound to
    // any of the part's parameters. Source params = ALL the part's parameters.

    public function indexComponent(ManualInspectionComponent $manualInspectionComponent)
    {
        return $this->listDocuments(
            $manualInspectionComponent,
            $this->sourceParametersForComponent($manualInspectionComponent)
        );
    }

    public function storeComponentDocument(Request $request, ManualInspectionComponent $manualInspectionComponent)
    {
        return $this->createDocument($request, $manualInspectionComponent, 'ec');
    }

    // ── Generation (2c.1) — template + WO data → PDF in WO library ─

    public function generate(Request $request, Workorder $workorder, ProcessDocument $processDocument)
    {
        $context = [
            'repair_number' => $request->input('repair_number'),
            'component_pn'  => $request->input('component_pn'),
        ];

        $pdf = app(ProcessDocumentRenderer::class)->render($processDocument, $workorder, $context);

        $title    = $processDocument->title ?: ($processDocument->doc_type ?: 'document');
        $safe     = preg_replace('/[^A-Za-z0-9_-]+/', '_', $title);
        $filename = 'wo_' . ($workorder->number ?? $workorder->id) . '_' . $safe . '_' . now()->format('Ymd_Hi') . '.pdf';

        $media = $workorder->addMediaFromString($pdf)
            ->usingFileName($filename)
            ->toMediaCollection('pdfs');

        return response()->json([
            'ok'           => true,
            'media_id'     => $media->id,
            'filename'     => $filename,
            'show_url'     => route('workorders.pdf.show', ['workorderId' => $workorder->id, 'mediaId' => $media->id]),
            'download_url' => route('workorders.pdf.download', ['workorderId' => $workorder->id, 'mediaId' => $media->id]),
        ], 201);
    }

    // ── shared ────────────────────────────────────────────────────

    private function listDocuments(Model $documentable, array $sourceParams)
    {
        $docs = $documentable->documents()
            ->with('pages.elements')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($d) => $this->docPayload($d));

        return response()->json([
            'documents'         => $docs,
            'source_parameters' => $sourceParams,
        ]);
    }

    private function createDocument(Request $request, Model $documentable, string $defaultType = 'drawing')
    {
        $data = $request->validate([
            'doc_type' => 'nullable|string|max:50',
            'title'    => 'nullable|string|max:255',
        ]);

        $maxOrder = $documentable->documents()->max('sort_order') ?? -1;

        $doc = $documentable->documents()->create([
            'doc_type'   => $data['doc_type'] ?? $defaultType,
            'title'      => $data['title'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json($this->docPayload($doc->load('pages.elements')), 201);
    }

    public function updateDocument(Request $request, ProcessDocument $processDocument)
    {
        $data = $request->validate([
            'doc_type'   => 'nullable|string|max:50',
            'title'      => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $processDocument->update($data);

        return response()->json($this->docPayload($processDocument->fresh('pages.elements')));
    }

    public function destroyDocument(ProcessDocument $processDocument)
    {
        $processDocument->delete();

        return response()->json(['ok' => true]);
    }

    // ── Pages ─────────────────────────────────────────────────────

    public function storePage(ProcessDocument $processDocument)
    {
        $maxNo    = $processDocument->pages()->max('page_no') ?? 0;
        $maxOrder = $processDocument->pages()->max('sort_order') ?? -1;

        $page = ProcessDocumentPage::create([
            'document_id' => $processDocument->id,
            'page_no'     => $maxNo + 1,
            'sort_order'  => $maxOrder + 1,
        ]);

        return response()->json($this->pagePayload($page->load('elements')), 201);
    }

    public function uploadPageImage(Request $request, ProcessDocumentPage $processDocumentPage)
    {
        $request->validate([
            'image' => 'required|file|image|mimes:png,jpg,jpeg,webp,gif|max:10240',
        ]);

        $manual = $processDocumentPage->document?->ruleProcess?->rule?->parameter?->manual;
        if (!$manual) {
            return response()->json(['message' => 'Manual not found for this page'], 422);
        }

        $media = $manual->addMedia($request->file('image'))->toMediaCollection('process-documents');

        return response()->json([
            'path'     => route('image.show.big', [
                'mediaId'   => $media->id,
                'modelId'   => $manual->id,
                'mediaName' => 'process-documents',
            ]),
            'media_id' => $media->id,
        ]);
    }

    public function updatePage(Request $request, ProcessDocumentPage $processDocumentPage)
    {
        $data = $request->validate([
            'image_path'   => 'nullable|string|max:1000',
            'image_width'  => 'nullable|integer',
            'image_height' => 'nullable|integer',
            'page_no'      => 'nullable|integer',
            'sort_order'   => 'nullable|integer',
        ]);

        $processDocumentPage->update($data);

        return response()->json($this->pagePayload($processDocumentPage->fresh('elements')));
    }

    public function destroyPage(ProcessDocumentPage $processDocumentPage)
    {
        $processDocumentPage->delete();

        return response()->json(['ok' => true]);
    }

    // ── Elements ──────────────────────────────────────────────────

    public function storeElement(Request $request, ProcessDocumentPage $processDocumentPage)
    {
        $data = $this->validateElement($request);
        $element = $processDocumentPage->elements()->create($data);

        return response()->json($this->elementPayload($element), 201);
    }

    public function updateElement(Request $request, ProcessDocumentElement $processDocumentElement)
    {
        $data = $this->validateElement($request, true);
        $processDocumentElement->update($data);

        return response()->json($this->elementPayload($processDocumentElement->fresh()));
    }

    public function destroyElement(ProcessDocumentElement $processDocumentElement)
    {
        $processDocumentElement->delete();

        return response()->json(['ok' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────

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
            'value_source'        => 'nullable|in:static,measurement,calc',
            'static_value'        => 'nullable|numeric',
            'source_parameter_id' => 'nullable|exists:manual_parameters,id',
            'placeholder'         => 'nullable|string|max:100',
            'text'                => 'nullable|string|max:255',
            'sort_order'          => 'nullable|integer',
        ]);
    }

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

        return ManualParameter::whereHas('points', fn($q) =>
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

    /** All parameters of a part — selectable as measurement sources on its EC sheet. */
    private function sourceParametersForComponent(ManualInspectionComponent $ic): array
    {
        return ManualParameter::where('inspection_component_id', $ic->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'description' => $p->description,
                'part'        => $ic->label,
            ])
            ->values()
            ->all();
    }

    private function docPayload(ProcessDocument $d): array
    {
        return [
            'id'         => $d->id,
            'doc_type'   => $d->doc_type,
            'title'      => $d->title,
            'sort_order' => $d->sort_order,
            'pages'      => $d->pages->map(fn($p) => $this->pagePayload($p))->values(),
        ];
    }

    private function pagePayload(ProcessDocumentPage $p): array
    {
        return [
            'id'           => $p->id,
            'page_no'      => $p->page_no,
            'image_path'   => $p->image_path,
            'image_width'  => $p->image_width,
            'image_height' => $p->image_height,
            'sort_order'   => $p->sort_order,
            'elements'     => $p->elements->map(fn($e) => $this->elementPayload($e))->values(),
        ];
    }

    private function elementPayload(ProcessDocumentElement $e): array
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
