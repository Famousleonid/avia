<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ManualInspectionComponent;
use App\Models\ManualParameter;
use App\Models\ManualParameterRuleProcess;
use App\Models\MasterRule;
use App\Models\MasterRulePhaseRuleProcess;
use App\Models\ProcessDocument;
use App\Models\ProcessDocumentElement;
use App\Models\ProcessDocumentPage;
use App\Models\WoMeasurement;
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

        // Tagged as a process-document generation so it stays out of the PDF Library
        // listing (still in 'pdfs' so show/download keep working).
        $media = $workorder->addMediaFromString($pdf)
            ->usingFileName($filename)
            ->withCustomProperties(['source' => 'process_document'])
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

    // ── Documents (manual level — the F&C document) ───────────────

    public function indexManual(\App\Models\Manual $manual)
    {
        return $this->listDocuments($manual, $this->manualParamOptions($manual->id));
    }

    public function storeManualDocument(Request $request, \App\Models\Manual $manual)
    {
        return $this->createDocument($request, $manual, 'manual_page');
    }

    /**
     * F&C document view for a WO: every page of the manual's document with the
     * WO measurements substituted. Value marks are colored by data state:
     * red — no measurement, yellow — initial only, green — final entered.
     */
    public function fcDocumentView(Workorder $workorder)
    {
        $manual = $workorder->unit->manuals;
        $doc = $manual->documents()->with('pages.elements')->first();

        if (!$doc || $doc->pages->isEmpty()) {
            return response('<div style="font-family:Arial;padding:40px;color:#888">No F&C document prepared for this manual. Create it in the manual\'s Dimensions tab ("F&C Document").</div>', 200)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        $renderer = new ProcessDocumentRenderer();
        $ctx = ['show_missing' => true, 'stage_colors' => true];

        $pagesHtml = '';
        foreach ($doc->pages as $page) {
            $pagesHtml .= '<div class="fc-doc-page">'
                . $renderer->renderSinglePageHtml($page, $workorder, $ctx, null)
                . '</div>';
        }

        $title = 'F&C Document — W' . ($workorder->number ?? $workorder->id);
        $saveUrl = route('process-documents.generate', ['workorder' => $workorder->id, 'processDocument' => $doc->id]);

        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>' . e($title) . '</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:12px;background:#f8f9fa;color:#212529}
.toolbar{display:flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border-bottom:1px solid #dee2e6;position:sticky;top:0;z-index:50}
.toolbar h1{font-size:13px;font-weight:700;flex:1;margin:0}
.btn{display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:4px;font-size:12px;cursor:pointer;border:1px solid transparent}
.btn-primary{background:#0d6efd;color:#fff}
.btn-success{background:#198754;color:#fff}
.legend{font-size:10px;color:#6c757d;display:flex;gap:10px;align-items:center}
.legend i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:3px;vertical-align:-1px}
.fc-doc-page{background:#fff;max-width:980px;margin:14px auto;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.pdw-page{position:relative}
.pdw-page img{width:100%;display:block}
.pdw-svg{position:absolute;top:0;left:0;width:100%;height:100%}
.pdw-dot{position:absolute;width:5px;height:5px;margin:-2.5px 0 0 -2.5px;background:#0d9488;border-radius:50%}
.pdw-el{position:absolute;transform:translate(-50%,-50%);font-size:8.5pt;font-weight:700;white-space:nowrap;line-height:1.2}
.pdw-dim{color:#0d6efd;background:rgba(255,255,255,0.92);border:0.75px solid #0d6efd;border-radius:2px;padding:1px 4px}
.pdw-dim.st-final{color:#198754;border-color:#198754}
.pdw-dim.st-initial{color:#b58900;border-color:#b58900}
.pdw-dim.st-missing{color:#dc3545;border-color:#dc3545}
.pdw-label{color:#0d9488;background:rgba(255,255,255,0.85);padding:0 3px}
@media print{
  .toolbar{display:none!important}
  body{background:#fff}
  .fc-doc-page{box-shadow:none;margin:0;max-width:none;page-break-after:always}
  .fc-doc-page:last-child{page-break-after:auto}
  @page{size:letter portrait;margin:8mm}
}
</style></head><body>
<div class="toolbar">
  <h1>' . e($title) . '</h1>
  <span class="legend">
    <span><i style="background:#198754"></i>final</span>
    <span><i style="background:#b58900"></i>initial</span>
    <span><i style="background:#dc3545"></i>no data</span>
  </span>
  <button class="btn btn-success" id="savePdfBtn">&#128190; Save PDF</button>
  <button class="btn btn-primary" onclick="window.print()" title="Select «Print on both sides» (duplex) in the print dialog">&#9112; Print</button>
  <span style="font-size:10px;color:#6c757d">two-sided: enable duplex in the print dialog</span>
</div>
' . $pagesHtml . '
<script>
document.getElementById("savePdfBtn").addEventListener("click", async function () {
    this.disabled = true; this.textContent = "Saving…";
    try {
        const r = await fetch(' . json_encode($saveUrl) . ', {
            method: "POST",
            headers: { "X-CSRF-TOKEN": ' . json_encode(csrf_token()) . ', "Accept": "application/json" },
        });
        const j = await r.json();
        if (!r.ok) throw new Error(j.message || "Save failed");
        this.textContent = "Saved to WO library ✓";
    } catch (e) { alert(e.message); this.disabled = false; this.textContent = "\u{1F4BE} Save PDF"; }
});
</script>
</body></html>';

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Picker list for "Attach existing": every process document of this manual
     * (rule-process and component owned), labelled with its owner.
     */
    public function manualDocuments(\App\Models\Manual $manual)
    {
        $rpIds = ManualParameterRuleProcess::whereHas('rule.parameter', fn($q) =>
            $q->where('manual_id', $manual->id))->pluck('id');
        $icIds = ManualInspectionComponent::where('manual_id', $manual->id)->pluck('id');

        $docs = ProcessDocument::with([
                'pages:id,document_id,image_path',
                'documentable',
            ])
            ->where(fn($q) => $q
                ->where(fn($q2) => $q2->where('documentable_type', ManualParameterRuleProcess::class)->whereIn('documentable_id', $rpIds))
                ->orWhere(fn($q2) => $q2->where('documentable_type', ManualInspectionComponent::class)->whereIn('documentable_id', $icIds)))
            ->get()
            ->map(function ($d) {
                $owner = $d->documentable;
                $ownerLabel = $owner instanceof ManualParameterRuleProcess
                    ? trim(($owner->manualProcess?->process?->process_name?->name ?? '') . ' · ' . ($owner->rule?->parameter?->description ?? ''))
                    : ($owner->label ?? '');
                return [
                    'id'          => $d->id,
                    'doc_type'    => $d->doc_type,
                    'title'       => $d->title,
                    'pages'       => $d->pages->count(),
                    'owner_label' => $ownerLabel,
                ];
            })->values();

        return response()->json($docs);
    }

    /**
     * "Attach existing document": clone a document onto another process —
     * pages reuse the SAME image files (no re-upload), elements are duplicated
     * so each process can adjust its own labels/dimensions independently.
     */
    public function attachExisting(Request $request, ManualParameterRuleProcess $manualParameterRuleProcess)
    {
        return $this->cloneDocumentTo($request, $manualParameterRuleProcess);
    }

    public function attachExistingPhase(Request $request, MasterRulePhaseRuleProcess $masterRulePhaseRuleProcess)
    {
        return $this->cloneDocumentTo($request, $masterRulePhaseRuleProcess);
    }

    private function cloneDocumentTo(Request $request, Model $documentable)
    {
        $data = $request->validate([
            'source_document_id' => 'required|exists:process_documents,id',
        ]);

        $src = ProcessDocument::with('pages.elements')->findOrFail($data['source_document_id']);

        $doc = \Illuminate\Support\Facades\DB::transaction(function () use ($src, $documentable) {
            $maxOrder = $documentable->documents()->max('sort_order') ?? -1;
            $doc = $documentable->documents()->create([
                'doc_type'   => $src->doc_type,
                'title'      => $src->title,
                'sort_order' => $maxOrder + 1,
            ]);
            foreach ($src->pages as $page) {
                $pageCopy = $doc->pages()->create([
                    'parameter_id' => $page->parameter_id,
                    'page_no'      => $page->page_no,
                    'image_path'   => $page->image_path, // same file on disk — not duplicated
                    'image_width'  => $page->image_width,
                    'image_height' => $page->image_height,
                    'sort_order'   => $page->sort_order,
                ]);
                foreach ($page->elements as $el) {
                    $attrs = $el->getAttributes();
                    unset($attrs['id'], $attrs['page_id'], $attrs['created_at'], $attrs['updated_at']);
                    $pageCopy->elements()->create($attrs);
                }
            }
            return $doc;
        });

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

    /**
     * Return the first page image URL of the process document for a bushing IC's
     * machining (repair) process. Used by the Print Sketch modal in Measurements tab.
     *
     * Searches all ManualParameterRuleProcess documents for this IC and returns
     * the image_path of the first page (sorted by sort_order).
     */
    public function bushingSketchImage(ManualInspectionComponent $manualInspectionComponent)
    {
        // Collect all rule-process IDs for this IC
        $rpIds = ManualParameter::where('inspection_component_id', $manualInspectionComponent->id)
            ->with('repairRules.processes:id,repair_rule_id')
            ->get()
            ->flatMap(fn($p) => $p->repairRules->flatMap(fn($r) => $r->processes->pluck('id')))
            ->unique()
            ->values();

        if ($rpIds->isEmpty()) {
            return response()->json(['image_path' => null, 'label' => null]);
        }

        // Find the first process document page with an image (prefer machining-related)
        $page = ProcessDocumentPage::whereHas('document', fn($q) =>
                $q->where('documentable_type', ManualParameterRuleProcess::class)
                  ->whereIn('documentable_id', $rpIds)
            )
            ->whereNotNull('image_path')
            ->orderBy('sort_order')
            ->with('document.documentable.manualProcess.process.process_name')
            ->first();

        if (!$page) {
            return response()->json(['image_path' => null, 'label' => null]);
        }

        $processName = $page->document->documentable?->manualProcess?->process?->process_name?->name;

        return response()->json([
            'image_path' => $page->image_path,
            'label'      => $processName,
        ]);
    }

    /**
     * Render a 2-column HTML page for the bushing machining sketch:
     * left column = repair data (W/O, step, P/N, OD required),
     * right column = process document drawing with all overlays.
     *
     * Opened directly in a new browser tab from the Measurements tab.
     */
    public function bushingSketchView(Request $request, Workorder $workorder, ManualInspectionComponent $manualInspectionComponent)
    {
        // ── 1. Find OD parameters for this IC (repair steps OR interference) ─
        $allOdParams = ManualParameter::where('inspection_component_id', $manualInspectionComponent->id)
            ->with(['repairSteps.component', 'points:id,code'])
            ->get();

        $odParamsWithSteps = $allOdParams->filter(fn($p) => $p->repairSteps->isNotEmpty());

        // Case B candidates: fit derived from pair limits — OD param with orig
        // limits whose point is shared with another component's param that also
        // has orig limits (mirrors hasMatingBore() in measurements JS).
        $odParamsWithFit = $allOdParams->filter(function ($p) use ($manualInspectionComponent) {
            if ($p->orig_dim_min === null || $p->orig_dim_max === null) return false;
            $ptIds = $p->points->pluck('id')->all();
            if (empty($ptIds)) return false;
            return ManualParameter::whereHas('points', fn($q) =>
                    $q->whereIn('manual_dimension_points.id', $ptIds)
                )
                ->where('inspection_component_id', '!=', $manualInspectionComponent->id)
                ->whereNotNull('orig_dim_min')->whereNotNull('orig_dim_max')
                ->exists();
        });

        // ── 2. Replicate getMatingRepairInfo logic ─────────────────────────
        $repairInfo = null;

        $findMatingBore = function ($odParam, bool $requireStepNo) use ($workorder, $manualInspectionComponent) {
            $odPointIds = $odParam->points->pluck('id')->all();
            if (empty($odPointIds)) return null;
            return ManualParameter::whereHas('points', fn($q) =>
                    $q->whereIn('manual_dimension_points.id', $odPointIds)
                )
                ->where('inspection_component_id', '!=', $manualInspectionComponent->id)
                ->with('points:id,code')
                ->get()
                ->first(function ($p) use ($workorder, $requireStepNo) {
                    $q = WoMeasurement::where('workorder_id', $workorder->id)
                        ->where('manual_parameter_id', $p->id)
                        ->where('stage', 'final')
                        ->whereNotNull('actual_value');
                    if ($requireStepNo) $q->whereNotNull('repair_step_no');
                    return $q->exists();
                });
        };

        // Collect repair info PER OD param — two bushing positions on different
        // lugs may have different repairs (different steps / different req OD).
        $repairInfos = [];

        // Case A: discrete repair steps
        foreach ($odParamsWithSteps as $odParam) {
            $matingParam = $findMatingBore($odParam, true);
            if (!$matingParam) continue;
            $meas = WoMeasurement::where('workorder_id', $workorder->id)
                ->where('manual_parameter_id', $matingParam->id)
                ->where('stage', 'final')->whereNotNull('repair_step_no')
                ->latest('id')->first();
            $repairInfos[] = [
                'useTolerance'  => false,
                'odParam'       => $odParam,
                'matingParam'   => $matingParam,
                'stepNo'        => $meas->repair_step_no,
                'step'          => $odParam->repairSteps->first(fn($s) => $s->step_no === $meas->repair_step_no),
                'measuredValue' => (float) $meas->actual_value,
            ];
        }

        // Case B: continuous — fit derived from the pair's factory limits:
        //   fit_min = OD_orig_min − ID_orig_max; fit_max = OD_orig_max − ID_orig_min
        //   req OD  = [ID_final + fit_min, ID_final + fit_max]
        $caseAIds = collect($repairInfos)->pluck('odParam.id')->flip();
        foreach ($odParamsWithFit as $odParam) {
            if ($caseAIds->has($odParam->id)) continue;
            $matingParam = $findMatingBore($odParam, false);
            if (!$matingParam) continue;
            if ($matingParam->orig_dim_min === null || $matingParam->orig_dim_max === null) continue;
            $meas = WoMeasurement::where('workorder_id', $workorder->id)
                ->where('manual_parameter_id', $matingParam->id)
                ->where('stage', 'final')->whereNotNull('actual_value')
                ->latest('id')->first();
            $bore   = (float) $meas->actual_value;
            $fitMin = round((float)$odParam->orig_dim_min - (float)$matingParam->orig_dim_max, 4);
            $fitMax = round((float)$odParam->orig_dim_max - (float)$matingParam->orig_dim_min, 4);
            $repairInfos[] = [
                'useTolerance'    => true,
                'odParam'         => $odParam,
                'matingParam'     => $matingParam,
                'stepNo'          => null,
                'step'            => null,
                'measuredValue'   => $bore,
                'fitMin'          => $fitMin,
                'fitMax'          => $fitMax,
                'calculatedOdMin' => round($bore + $fitMin, 4),
                'calculatedOdMax' => round($bore + $fitMax, 4),
            ];
        }

        // Optional ?param_id= — limit the sketch to ONE position (used by the
        // per-row Sketch buttons of the Required Bushings report).
        if ($pid = (int) $request->query('param_id')) {
            $repairInfos = array_values(array_filter($repairInfos, fn($i) => $i['odParam']->id === $pid));
        }

        // Group positions with an IDENTICAL repair result onto one sheet
        // (same lug pair / same repair → one P/N, qty 2). Different results
        // print as separate sheets.
        $groups = [];
        foreach ($repairInfos as $info) {
            $key = $info['useTolerance']
                ? 'B|' . $info['calculatedOdMin'] . '|' . $info['calculatedOdMax']
                : 'A|' . $info['stepNo'] . '|' . ($info['step']?->component?->part_number ?? '');
            if (!isset($groups[$key])) {
                $groups[$key] = ['info' => $info, 'points' => [], 'qty' => 0];
            }
            foreach ($info['odParam']->points as $pt) {
                if ($pt->code && !in_array($pt->code, $groups[$key]['points'])) {
                    $groups[$key]['points'][] = $pt->code;
                }
            }
            // qty per position lives on the parameter (e.g. 2 bushings per lug)
            $groups[$key]['qty'] += max(1, (int) ($info['odParam']->qty ?? 1));
        }
        $groups = array_values($groups);

        $repairInfo = $repairInfos[0] ?? null; // back-compat (title, check logic)

        // ── 3. Find the ProcessDocumentPage for this IC ────────────────────
        $findPage = function ($ruleProcessIds) {
            $ids = collect($ruleProcessIds)->unique()->values();
            if ($ids->isEmpty()) return null;
            return ProcessDocumentPage::whereHas('document', fn($q) =>
                    $q->where('documentable_type', ManualParameterRuleProcess::class)
                      ->whereIn('documentable_id', $ids)
                )
                ->whereNotNull('image_path')
                ->orderBy('sort_order')
                ->with(['elements', 'document.documentable.rule.parameter',
                        'document.documentable.manualProcess.process.process_name'])
                ->first();
        };

        $paramsWithRules = ManualParameter::where('inspection_component_id', $manualInspectionComponent->id)
            ->with('repairRules.processes:id,repair_rule_id')
            ->get();

        // rule-process ids per parameter — each bushing position may have its OWN
        // document copy (with its own labels), so sections pick their own page
        $rpIdsByParam = $paramsWithRules->mapWithKeys(fn($p) => [
            $p->id => $p->repairRules->flatMap(fn($r) => $r->processes->pluck('id'))->all(),
        ]);
        $rpIds = $paramsWithRules
            ->flatMap(fn($p) => $p->repairRules->flatMap(fn($r) => $r->processes->pluck('id')))
            ->unique()->values();

        $page = $findPage($rpIds);

        // ── 4. Build data panel HTML ───────────────────────────────────────
        $ic       = $manualInspectionComponent;
        $icLabel  = $ic->label ?? $ic->description ?? 'Bushing';
        $iplNums  = $ic->ipl_nums ?? [];
        $partNums = $ic->part_numbers ?? [];

        $partCell = e($icLabel)
            . ($iplNums  ? ' <span style="color:#6c757d">IPL# ' . e($iplNums[0])  . '</span>' : '')
            . ($partNums ? ' <span style="color:#6c757d">'      . e($partNums[0]) . '</span>' : '');

        $titleText = $icLabel . ($repairInfo ? ' — Oversize ' . $repairInfo['stepNo'] : '');

        $buildDataPanel = function (array $info, array $pointCodes, int $qty) use ($workorder, $partCell): string {
            $posRow = $pointCodes
                ? '<tr><td style="color:#6c757d;padding:3px 12px 3px 0">Position</td><td><strong>' . e(implode(', ', $pointCodes)) . '</strong>'
                    . ($qty > 1 ? ' <span style="color:#0d6efd;font-weight:700">· Qty ' . $qty . '</span>' : '') . '</td></tr>'
                : '';

            $boreRow = '<tr><td>Bore measured</td><td><strong>'
                . number_format($info['measuredValue'], 4) . ' in</strong>'
                . ' <span style="color:#6c757d">(' . e($info['matingParam']->description ?? '') . ')</span></td></tr>';

            if (!$info['useTolerance']) {
                // ── Case A: discrete step ──────────────────────────────────
                $step = $info['step'];
                $comp = $step?->component;

                $pnRow = $comp
                    ? '<tr><td>Required P/N</td><td><strong>' . e($comp->part_number ?? '—') . '</strong>'
                        . ($comp->ipl_num ? ' <span style="color:#6c757d">(IPL# ' . e($comp->ipl_num) . ')</span>' : '') . '</td></tr>'
                    : '<tr><td>Required P/N</td><td style="color:#dc3545">— not configured —</td></tr>';

                $odRows = $step
                    ? '<tr style="border-top:1px solid #dee2e6"><td style="padding-top:10px">OD required min</td><td style="padding-top:10px;font-size:14px;font-weight:700">' . number_format((float)$step->dim_min, 4) . ' in</td></tr>'
                      . '<tr><td>OD required max</td><td style="font-size:14px;font-weight:700">' . number_format((float)$step->dim_max, 4) . ' in</td></tr>'
                      . ($step->after_dim_min !== null ? '<tr><td style="color:#6c757d">After plate min</td><td style="color:#6c757d">' . number_format((float)$step->after_dim_min, 4) . '</td></tr>' : '')
                      . ($step->after_dim_max !== null ? '<tr><td style="color:#6c757d">After plate max</td><td style="color:#6c757d">' . number_format((float)$step->after_dim_max, 4) . '</td></tr>' : '')
                    : '<tr style="border-top:1px solid #dee2e6"><td colspan="2" style="color:#dc3545;padding-top:10px">Step ' . e($info['stepNo']) . ' not configured in OD steps</td></tr>';

                return '
                <table style="border-collapse:collapse;width:100%;font-size:12px">
                  <tr><td style="color:#6c757d;padding:3px 12px 3px 0;white-space:nowrap">W/O</td><td><strong>' . e('W' . $workorder->number) . '</strong></td></tr>
                  <tr><td style="color:#6c757d;padding:3px 12px 3px 0">Part</td><td>' . $partCell . '</td></tr>
                  ' . $posRow . '
                  <tr><td style="color:#6c757d;padding:3px 12px 3px 0">Repair step</td><td><span style="color:#0d6efd;font-weight:700">' . e($info['stepNo']) . '</span></td></tr>
                  ' . $boreRow . $pnRow . $odRows . '
                </table>';
            }

            // ── Case B: continuous fit ─────────────────────────────────────
            $intRow = '<tr><td style="color:#6c757d">+ Fit</td><td>'
                . number_format($info['fitMin'], 4) . ' … ' . number_format($info['fitMax'], 4) . ' in</td></tr>';

            $odRows = '<tr style="border-top:1px solid #dee2e6"><td style="padding-top:10px">OD required min</td><td style="padding-top:10px;font-size:14px;font-weight:700">' . number_format($info['calculatedOdMin'], 4) . ' in</td></tr>'
                    . '<tr><td>OD required max</td><td style="font-size:14px;font-weight:700">' . number_format($info['calculatedOdMax'], 4) . ' in</td></tr>';

            return '
            <table style="border-collapse:collapse;width:100%;font-size:12px">
              <tr><td style="color:#6c757d;padding:3px 12px 3px 0;white-space:nowrap">W/O</td><td><strong>' . e('W' . $workorder->number) . '</strong></td></tr>
              <tr><td style="color:#6c757d;padding:3px 12px 3px 0">Part</td><td>' . $partCell . '</td></tr>
              ' . $posRow . $boreRow . $intRow . $odRows . '
            </table>';
        };

        $emptyDataHtml = '
            <table style="border-collapse:collapse;width:100%;font-size:12px">
              <tr><td style="color:#6c757d;padding:3px 12px 3px 0">W/O</td><td><strong>' . e('W' . $workorder->number) . '</strong></td></tr>
              <tr><td style="color:#6c757d;padding:3px 12px 3px 0">Part</td><td>' . $partCell . '</td></tr>
              <tr><td style="color:#6c757d;padding:3px 12px 3px 0">Repair step</td><td style="color:#6c757d">— mating not measured yet —</td></tr>
            </table>';

        // ── 5. Render drawing with overlays ────────────────────────────────
        if ($request->boolean('check') && !$page) {
            return response()->json(['no_document' => true]);
        }

        $drawingHtml = '<div style="color:#aaa;padding:40px;text-align:center">No drawing attached</div>';
        if ($page) {
            $docParam    = $page->document?->documentable?->rule?->parameter;
            $processName = $page->document?->documentable?->manualProcess?->process?->process_name?->name;
            $renderer    = new ProcessDocumentRenderer();

            // Pass OD range as fallback for OD dimension elements in the drawing
            $drawingContext = ['od_override' => true];
            if ($repairInfo && !$repairInfo['useTolerance'] && $repairInfo['step']) {
                $drawingContext['od_dim_min'] = (float) $repairInfo['step']->dim_min;
                $drawingContext['od_dim_max'] = (float) $repairInfo['step']->dim_max;
            } elseif ($repairInfo && $repairInfo['useTolerance']) {
                $drawingContext['od_dim_min'] = $repairInfo['calculatedOdMin'];
                $drawingContext['od_dim_max'] = $repairInfo['calculatedOdMax'];
            }

            // ── Missing measurements check ───────────────────────────────────
            $missingValues = $renderer->getMissingValues($page, $workorder, $drawingContext, $docParam);

            // ?check=1 — JS pre-flight: return JSON only, don't build full page
            if ($request->boolean('check')) {
                // Collect calculated OD param ids so we can replace their missing messages
                // with the mating bore message (bore must be measured first).
                $calculatedOdParamIds = $odParamsWithSteps->pluck('id')
                    ->merge($odParamsWithFit->pluck('id'))
                    ->unique()->flip()->all(); // id => true map

                // Find mating bore for each unmeasured calculated OD param (no measurement
                // required). Per-position: one lug measured + another not → still warn.
                $boreMissingMessages = [];
                $measuredOdIds = collect($repairInfos)->pluck('odParam.id')->flip();
                {
                    $allOdForBore = $odParamsWithSteps->merge($odParamsWithFit)->unique('id')
                        ->reject(fn($p) => $measuredOdIds->has($p->id));
                    foreach ($allOdForBore as $odParam) {
                        $odPointIds = $odParam->points->pluck('id')->all();
                        if (empty($odPointIds)) continue;
                        $matingBore = ManualParameter::whereHas('points', fn($q) =>
                                $q->whereIn('manual_dimension_points.id', $odPointIds)
                            )
                            ->where('inspection_component_id', '!=', $manualInspectionComponent->id)
                            ->with(['points:id,code', 'inspectionComponent:id,label'])
                            ->first();
                        if ($matingBore) {
                            $borePointCode = $matingBore->points->first()?->code ?? '';
                            $icLabel = $matingBore->inspectionComponent?->label ?? '';
                            $prefix = 'Ø';
                            $parts  = [($prefix . $matingBore->description)];
                            if ($borePointCode) $parts[] = 'point ' . $borePointCode;
                            if ($icLabel)       $parts[] = $icLabel;
                            $boreMissingMessages[] = implode(' · ', $parts) . ' (required for OD calculation)';
                        }
                    }
                }

                $items = array_values(array_filter(array_map(function ($mv) use ($calculatedOdParamIds) {
                    // Skip OD params that are calculated — replaced by bore messages below
                    if (isset($mv['param_id']) && isset($calculatedOdParamIds[$mv['param_id']])) return null;
                    $prefix = $mv['mask'] === 'diameter' ? 'Ø' : ($mv['mask'] === 'radius' ? 'R' : '');
                    $parts  = [];
                    if ($mv['param_desc']) $parts[] = ($prefix . $mv['param_desc']);
                    if ($mv['point_code']) $parts[] = 'point ' . $mv['point_code'];
                    if ($mv['ic_label'])   $parts[] = $mv['ic_label'];
                    return implode(' · ', $parts);
                }, $missingValues)));

                $items = array_merge($boreMissingMessages, $items);
                return response()->json(['missing' => $items]);
            }

            // Always render the drawing — missing elements are silently skipped.
            // The ?check=1 pre-flight (called by JS before opening this tab) already
            // guards against opening when measurements are absent.
            $labelHtml = $processName
                ? '<div style="font-size:10px;color:#6c757d;margin-bottom:6px">' . e($processName) . '</div>'
                : '';

            if ($groups) {
                // One section per distinct repair result — each with its own
                // OD values substituted into the drawing (page break in print).
                // Each section prefers the document of ITS position's processes
                // (cloned copies carry their own labels), falling back to the
                // IC-wide first page.
                $sections = [];
                foreach ($groups as $g) {
                    $info = $g['info'];
                    $ctx  = [
                        'od_override' => true,
                        'qty'         => $g['qty'],
                        'point'       => implode(', ', $g['points']),
                        'hl_step_no'  => $info['stepNo'], // null for Case B
                    ];
                    if (!$info['useTolerance'] && $info['step']) {
                        $ctx['od_dim_min'] = (float) $info['step']->dim_min;
                        $ctx['od_dim_max'] = (float) $info['step']->dim_max;
                    } elseif ($info['useTolerance']) {
                        $ctx['od_dim_min'] = $info['calculatedOdMin'];
                        $ctx['od_dim_max'] = $info['calculatedOdMax'];
                    }
                    $groupPage     = $findPage($rpIdsByParam[$info['odParam']->id] ?? []) ?? $page;
                    $groupDocParam = $groupPage->document?->documentable?->rule?->parameter;
                    $groupLabel    = $groupPage->document?->documentable?->manualProcess?->process?->process_name?->name;
                    $sections[] = [
                        'data'    => $buildDataPanel($info, $g['points'], $g['qty']),
                        'drawing' => ($groupLabel ? '<div style="font-size:10px;color:#6c757d;margin-bottom:6px">' . e($groupLabel) . '</div>' : '')
                                     . $renderer->renderSinglePageHtml($groupPage, $workorder, $ctx, $groupDocParam),
                    ];
                }
            } else {
                $sections = [[
                    'data'    => $emptyDataHtml,
                    'drawing' => $labelHtml . $renderer->renderSinglePageHtml($page, $workorder, $drawingContext, $docParam),
                ]];
            }
        } else {
            $sections = [[
                'data'    => $groups ? $buildDataPanel($groups[0]['info'], $groups[0]['points'], $groups[0]['qty']) : $emptyDataHtml,
                'drawing' => $drawingHtml,
            ]];
        }

        // ── 6. Assemble 2-column page ──────────────────────────────────────
        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<title>' . e($titleText) . '</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:Arial,sans-serif;font-size:12px;color:#212529;background:#f8f9fa}
  .toolbar{display:flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border-bottom:1px solid #dee2e6}
  .toolbar h1{font-size:13px;font-weight:700;flex:1;margin:0}
  .btn{display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:4px;font-size:12px;font-weight:500;cursor:pointer;border:1px solid transparent;text-decoration:none}
  .btn-primary{background:#0d6efd;color:#fff;border-color:#0d6efd}
  .btn-secondary{background:#fff;color:#6c757d;border-color:#dee2e6}
  .btn:hover{opacity:.85}
  .wrap{display:grid;grid-template-columns:300px 1fr;min-height:calc(100vh - 41px)}
  .left{padding:20px 16px;border-right:1px solid #dee2e6;background:#fff}
  .right{padding:12px;background:#fff}
  td{padding:3px 12px 3px 0;vertical-align:top}
  .pdw-page{position:relative}
  .pdw-page img{width:100%;display:block}
  .pdw-svg{position:absolute;top:0;left:0;width:100%;height:100%}
  .pdw-dot{position:absolute;width:5px;height:5px;margin:-2.5px 0 0 -2.5px;background:#0d9488;border-radius:50%}
  .pdw-el{position:absolute;transform:translate(-50%,-50%);font-size:8.5pt;font-weight:700;white-space:nowrap;line-height:1.2;cursor:default}
  .pdw-dim{color:#0d6efd;background:rgba(255,255,255,0.92);border:0.75px solid #0d6efd;border-radius:2px;padding:1px 4px}
  .pdw-label{color:#0d9488;background:rgba(255,255,255,0.85);padding:0 3px}
  body.edit-mode .pdw-el{cursor:grab}
  body.edit-mode .pdw-dim{outline:1.5px dashed rgba(13,110,253,0.45);outline-offset:2px}
  body.edit-mode .pdw-dim:hover{outline-color:#0d6efd;z-index:99}
  body.edit-mode .pdw-el.dragging{cursor:grabbing;opacity:.85;z-index:100}
  body.edit-mode .pdw-dim.dragging{outline:1.5px solid #0d6efd}
  .btn-edit{background:#fff;color:#6c757d;border-color:#dee2e6}
  .btn-edit.active{background:#fff3cd;color:#856404;border-color:#ffc107}
  .save-indicator{font-size:11px;color:#6c757d;min-width:80px;text-align:right}
  .wrap + .wrap{border-top:2px dashed #dee2e6}
  @media print{
    .toolbar{display:none!important}
    .left{display:none!important}
    .wrap{display:block!important;min-height:0}
    .wrap + .wrap{page-break-before:always;border-top:none}
    .right{padding:0}
    body{background:#fff}
    .pdw-el{cursor:default!important;outline:none!important}
  }
</style></head><body>
<div class="toolbar">
  <h1>' . e($titleText) . '</h1>
  <span class="save-indicator" id="saveInd"></span>
  <button class="btn btn-edit" id="editBtn" onclick="toggleEdit()">✎ Edit labels</button>
  <button class="btn btn-secondary" onclick="window.close()">✕ Cancel</button>
  <button class="btn btn-primary" onclick="window.print()">⎙ Print</button>
</div>';

        foreach ($sections as $s) {
            $html .= '
<div class="wrap">
  <div class="left">
    ' . $s['data'] . '
  </div>
  <div class="right">' . $s['drawing'] . '</div>
</div>';
        }

        $html .= '
<script>
const CSRF = \'' . csrf_token() . '\';
let editMode = false;

function toggleEdit() {
  editMode = !editMode;
  document.body.classList.toggle(\'edit-mode\', editMode);
  const btn = document.getElementById(\'editBtn\');
  btn.classList.toggle(\'active\', editMode);
  btn.textContent = editMode ? \'🔒 Lock\' : \'✎ Edit labels\';
  document.getElementById(\'saveInd\').textContent = editMode ? \'Drag labels to reposition\' : \'\';
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function nearestOnSegmentJS(px, py, x1, y1, x2, y2) {
  const dx = x2 - x1, dy = y2 - y1;
  const len2 = dx * dx + dy * dy;
  if (len2 === 0) return [x1, y1];
  let t = ((px - x1) * dx + (py - y1) * dy) / len2;
  t = Math.max(0, Math.min(1, t));
  return [x1 + t * dx, y1 + t * dy];
}

// ── Drag logic ──────────────────────────────────────────────────────────────
let dragged = null, startX = 0, startY = 0, origLeft = 0, origTop = 0;

document.addEventListener(\'mousedown\', function (e) {
  if (!editMode) return;
  const el = e.target.closest(\'.pdw-el[data-element-id]\');
  if (!el) return;
  e.preventDefault();
  dragged = el;
  dragged.classList.add(\'dragging\');
  startX = e.clientX; startY = e.clientY;
  origLeft = parseFloat(el.style.left);
  origTop  = parseFloat(el.style.top);
});

document.addEventListener(\'mousemove\', function (e) {
  if (!dragged) return;
  const page = dragged.closest(\'.pdw-page\');
  const rect = page.getBoundingClientRect();
  const dxPct = (e.clientX - startX) / rect.width  * 100;
  const dyPct = (e.clientY - startY) / rect.height * 100;
  const newLeft = Math.max(0, Math.min(100, origLeft + dxPct));
  const newTop  = Math.max(0, Math.min(100, origTop  + dyPct));
  dragged.style.left = newLeft.toFixed(2) + \'%\';
  dragged.style.top  = newTop.toFixed(2)  + \'%\';

  // Move leader line: x2/y2 = text position (follows label).
  // Multi-sheet sketch renders the same element ids per section — look the
  // leader up inside the dragged element\'s OWN page, not document-wide.
  const id = dragged.dataset.elementId;
  if (id) {
    const leader = page.querySelector(\'[id="dim-leader-\' + id + \'"]\');
    if (leader) {
      leader.setAttribute(\'x2\', newLeft.toFixed(2));
      leader.setAttribute(\'y2\', newTop.toFixed(2));
      // For linear dimensions: also recalculate x1/y1 (nearest point on dim line)
      const lx1 = parseFloat(leader.dataset.lx1);
      const ly1 = parseFloat(leader.dataset.ly1);
      const lx2 = parseFloat(leader.dataset.lx2);
      const ly2 = parseFloat(leader.dataset.ly2);
      if (!isNaN(lx1) && !isNaN(lx2)) {
        const [nx, ny] = nearestOnSegmentJS(newLeft, newTop, lx1, ly1, lx2, ly2);
        leader.setAttribute(\'x1\', nx.toFixed(2));
        leader.setAttribute(\'y1\', ny.toFixed(2));
      }
    }
  }
});

document.addEventListener(\'mouseup\', function (e) {
  if (!dragged) return;
  const el = dragged;
  el.classList.remove(\'dragging\');
  dragged = null;

  const newLeft = parseFloat(el.style.left);
  const newTop  = parseFloat(el.style.top);
  const id = el.dataset.elementId;
  if (!id) return;

  // Save to server
  const ind = document.getElementById(\'saveInd\');
  ind.textContent = \'Saving…\';
  fetch(\'/process-document-elements/\' + id, {
    method: \'PATCH\',
    headers: {\'Content-Type\':\'application/json\',\'X-CSRF-TOKEN\':CSRF,\'Accept\':\'application/json\'},
    body: JSON.stringify({ label_x_pct: newLeft.toFixed(2), label_y_pct: newTop.toFixed(2) })
  })
  .then(r => r.json())
  .then(() => { ind.textContent = \'Saved ✓\'; setTimeout(() => ind.textContent = \'Drag labels to reposition\', 1500); })
  .catch(() => { ind.textContent = \'Save failed\'; });
});
</script>
</body></html>';

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function validateElement(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes|required' : 'required';

        return $request->validate([
            'element_type'        => "$req|in:dimension,label,text,steps_table",
            'x_pct'               => 'nullable|numeric',
            'y_pct'               => 'nullable|numeric',
            'x2_pct'              => 'nullable|numeric',
            'y2_pct'              => 'nullable|numeric',
            'label_x_pct'         => 'nullable|numeric',
            'label_y_pct'         => 'nullable|numeric',
            'mask'                => 'nullable|in:diameter,linear,radius',
            'value_source'        => 'nullable|in:static,measurement,calc,formula',
            'static_value'        => 'nullable|numeric',
            'source_parameter_id' => 'nullable|exists:manual_parameters,id',
            'formula_expression'  => 'nullable|string|max:500',
            'formula_tol_plus'    => 'nullable|numeric|min:0',
            'formula_tol_minus'   => 'nullable|numeric|min:0',
            'placeholder'         => 'nullable|string|max:100',
            'text'                => 'nullable|string|max:255',
            'font_size'           => 'nullable|integer|min:5|max:72',
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
        if ($d instanceof \App\Models\Manual) {
            return $d; // manual-level document (F&C document)
        }
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
            'formula_expression'  => $e->formula_expression,
            'formula_tol_plus'    => $e->formula_tol_plus,
            'formula_tol_minus'   => $e->formula_tol_minus,
            'placeholder'         => $e->placeholder,
            'text'                => $e->text,
            'font_size'           => $e->font_size,
            'sort_order'          => $e->sort_order,
        ];
    }
}
