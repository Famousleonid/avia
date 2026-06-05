<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualInspectionComponent;
use App\Models\ManualParameter;
use App\Models\ManualParameterRuleProcess;
use App\Models\MasterRule;
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

    /**
     * Document hub for a part: the Part → Point → Rule → Process tree, with a flag
     * for which rule-processes already have a document. Documents attach to the
     * process (rule-process), so they serve both repair and EC.
     */
    public function documentTree(ManualInspectionComponent $manualInspectionComponent)
    {
        // ── Main: Point → Repair rule → Process (ManualParameterRuleProcess) ──
        $params = ManualParameter::where('inspection_component_id', $manualInspectionComponent->id)
            ->with(['points:id,code', 'repairRules.processes.manualProcess.process.process_name'])
            ->orderBy('sort_order')
            ->get();

        $mainRpIds = $params->flatMap(fn($p) => $p->repairRules->flatMap(fn($r) => $r->processes->pluck('id')))->unique();
        $mainDocs  = ProcessDocument::where('documentable_type', ManualParameterRuleProcess::class)
            ->whereIn('documentable_id', $mainRpIds)
            ->pluck('documentable_id')->map(fn($i) => (int) $i)->flip();

        $procNode = function ($rp, $kind, $docs, $withDesc = true) {
            $pname = $rp->manualProcess?->process?->process_name?->name ?? 'Process';

            return [
                'rule_process_id' => $rp->id,
                'kind'            => $kind,
                'label'           => $pname . ($withDesc && $rp->description ? ' — ' . $rp->description : ''),
                'has_document'    => $docs->has((int) $rp->id),
                'is_gate'         => $kind === 'main' ? (bool) $rp->is_gate : false,
            ];
        };

        $points = $params->map(function ($p) use ($mainDocs, $procNode) {
            $pts = $p->points->pluck('code')->filter()->implode(', ');

            return [
                'param_id' => $p->id,
                'label'    => trim(($pts ? $pts . ' · ' : '') . ($p->description ?? '')) ?: ('#' . $p->id),
                'rules'    => $p->repairRules->map(fn($r) => [
                    'rule_id'   => $r->id,
                    'label'     => $r->name ?: ('Rule #' . $r->id),
                    'action'    => $r->action ?? 'repair',
                    'processes' => $r->processes->map(fn($rp) => $procNode($rp, 'main', $mainDocs))->values(),
                ])->values(),
            ];
        })->values();

        // ── Start / Finish: MasterRule phase rule → Process (MasterRulePhaseRuleProcess) ──
        $masterRule = MasterRule::where('inspection_component_id', $manualInspectionComponent->id)
            ->with('phaseRules.processes.manualProcess.process.process_name')
            ->first();
        $phaseRpIds = $masterRule
            ? $masterRule->phaseRules->flatMap(fn($r) => $r->processes->pluck('id'))->unique()
            : collect();
        $phaseDocs = ProcessDocument::where('documentable_type', MasterRulePhaseRuleProcess::class)
            ->whereIn('documentable_id', $phaseRpIds)
            ->pluck('documentable_id')->map(fn($i) => (int) $i)->flip();

        $phaseNodes = function (string $phase) use ($masterRule, $phaseDocs, $procNode) {
            if (!$masterRule) {
                return [];
            }

            return $masterRule->phaseRules->where('phase', $phase)->map(fn($r) => [
                'rule_id'   => $r->id,
                'label'     => $r->name ?: (ucfirst($phase) . ' rule'),
                'processes' => $r->processes->map(fn($rp) => $procNode($rp, 'phase', $phaseDocs, false))->values(),
            ])->values();
        };

        return response()->json([
            'start'  => $phaseNodes('start'),
            'points' => $points,
            'finish' => $phaseNodes('finish'),
        ]);
    }

    // ── Generation (2c.1) — template + WO data → PDF in WO library ─

    public function generate(Request $request, Workorder $workorder, ProcessDocument $processDocument)
    {
        $context = [
            'repair_number' => $request->input('repair_number'),
            'component_pn'  => $request->input('component_pn'),
        ];
        // EC: render only the pages of one place (parameter) → a separate PDF per place.
        $onlyParam = $request->input('parameter_id') ? (int) $request->input('parameter_id') : null;

        $pdf = app(ProcessDocumentRenderer::class)->render($processDocument, $workorder, $context, $onlyParam);

        $title    = $processDocument->title ?: ($processDocument->doc_type ?: 'document');
        $placeTag = $onlyParam ? ('_p' . $onlyParam) : '';
        $safe     = preg_replace('/[^A-Za-z0-9_-]+/', '_', $title);
        $filename = 'wo_' . ($workorder->number ?? $workorder->id) . '_' . $safe . $placeTag . '_' . now()->format('Ymd_Hi') . '.pdf';

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

        $manual = $this->resolveManual($processDocumentPage->document);
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
            'parameter_id' => 'nullable|integer', // EC: the place this page documents
        ]);

        // allow clearing parameter_id explicitly
        if ($request->exists('parameter_id')) {
            $data['parameter_id'] = $request->input('parameter_id') ?: null;
        }

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
            'mask'                => 'nullable|in:diameter,linear,radius',
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
        return $this->manualParamOptions($rp->rule?->parameter?->manual_id);
    }

    /**
     * All parameters of a manual — selectable as measurement/calc sources or label
     * references, so a drawing can point at ANY point, incl. another part (mating
     * dimension for calc). Labelled "Part · Point · Dimension" (e.g. Main Fitting · AA3 · ID 11-10).
     */
    private function manualParamOptions(?int $manualId): array
    {
        if (!$manualId) {
            return [];
        }

        return ManualParameter::where('manual_id', $manualId)
            ->with(['inspectionComponent:id,label', 'points:id,code'])
            ->orderBy('inspection_component_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'description' => $p->description,
                'points'      => $p->points->pluck('code')->filter()->implode(', '),
                'part'        => $p->inspectionComponent?->label,
            ])
            ->values()
            ->all();
    }

    /** Resolve the owning Manual of a document across all documentable types (for media storage). */
    private function resolveManual(?ProcessDocument $doc)
    {
        $d = $doc?->documentable;
        if ($d instanceof ManualInspectionComponent) {
            return $d->manual;
        }
        if ($d instanceof ManualParameterRuleProcess) {
            return $d->rule?->parameter?->manual ?? $d->rule?->parameter?->inspectionComponent?->manual;
        }
        if ($d instanceof MasterRulePhaseRuleProcess) {
            return $d->phaseRule?->masterRule?->manual;
        }

        return null;
    }

    /** All parameters of the manual — selectable as sources/references on the EC sheet
     *  (incl. other parts, e.g. a mating dimension for calc). */
    private function sourceParametersForComponent(ManualInspectionComponent $ic): array
    {
        return $this->manualParamOptions($ic->manual_id);
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
            'parameter_id' => $p->parameter_id,
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
