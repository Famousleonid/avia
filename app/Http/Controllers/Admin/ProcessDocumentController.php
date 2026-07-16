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

        // EC drawings are hidden from the PDF Library listing (source =
        // process_document). The F&C document is meant to be visible there, so
        // it is saved with a library-visible source + a friendly name.
        $toLibrary = $request->boolean('to_library');
        $props = $toLibrary
            ? ['source' => 'fc_document', 'document_id' => $processDocument->id, 'document_name' => ($processDocument->title ?: 'F&C Document')]
            : ['source' => 'process_document'];

        // F&C document is one-per-document: replace the previous PDF instead of
        // piling up duplicates each time it is saved.
        if ($toLibrary) {
            $workorder->getMedia('pdfs')
                ->filter(fn ($m) => $m->getCustomProperty('source') === 'fc_document'
                    && (int) $m->getCustomProperty('document_id') === $processDocument->id)
                ->each->delete();
        }

        $media = $workorder->addMediaFromString($pdf)
            ->usingFileName($filename)
            ->withCustomProperties($props)
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
    /**
     * Persist the WO torque values (map { element_id: value }) entered on the
     * F&C Document's Torque page. Empty entries are dropped.
     */
    public function saveTorqueValues(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'values'   => 'array',
            'values.*' => 'nullable|string|max:50',
        ]);

        $map = collect($data['values'] ?? [])
            ->map(fn($v) => trim((string) $v))
            ->reject(fn($v) => $v === '')
            ->all();

        $workorder->torque_values = $map;
        $workorder->save();

        return response()->json(['ok' => true, 'count' => count($map)]);
    }

    public function fcDocumentView(Workorder $workorder)
    {
        $manual = $workorder->unit->manuals;
        $doc = $manual->documents()->with('pages.elements')->first();

        if (!$doc || $doc->pages->isEmpty()) {
            return response('<div style="font-family:Arial;padding:40px;color:#888">No F&C document prepared for this manual. Create it in the manual\'s Dimensions tab ("F&C Document").</div>', 200)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        $renderer = new ProcessDocumentRenderer();
        $ctx = ['show_missing' => true, 'stage_colors' => true, 'torque_edit' => true];

        // Torque inputs: marks the tech fills during generation. The gate blocks
        // Save PDF until every torque input has a value for this WO.
        $torqueIds = $doc->pages->flatMap(fn($p) => $p->elements)
            ->filter(fn($e) => $e->value_source === 'torque')
            ->pluck('id')->values();
        $hasTorque = $torqueIds->isNotEmpty();
        // Auto-fill is offered only when at least one torque mark carries a CMM range.
        $hasTorqueRange = $doc->pages->flatMap(fn($p) => $p->elements)
            ->contains(fn($e) => $e->value_source === 'torque'
                && $e->torque_min !== null && $e->torque_max !== null);
        $torqueSaveUrl = route('workorders.torque-values.save', ['workorder' => $workorder->id]);

        $pagesHtml = '';
        foreach ($doc->pages as $page) {
            if (!$page->image_path) continue; // skip blank pages — no extra sheet
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
.pdw-dim.st-pass{color:#198754;border-color:#198754}
.pdw-dim.st-fail{color:#dc3545;border-color:#dc3545}
.pdw-dim.st-repair{color:#6f42c1;border-color:#6f42c1}
.pdw-dim.st-nodata{color:#b58900;border-color:#b58900}
.pdw-dim.pdw-value{border:none;background:transparent;padding:0}
.pdw-label{color:#0d9488;background:rgba(255,255,255,0.85);padding:0 3px}
.pdw-torque-input{position:absolute;transform:translate(-50%,-50%);width:56px;font-size:8.5pt;font-weight:700;text-align:center;color:#fd7e14;background:#fff;border:1px solid #fd7e14;border-radius:3px;padding:1px 2px;z-index:10;-moz-appearance:textfield;appearance:textfield}
.pdw-torque-input::-webkit-outer-spin-button,.pdw-torque-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.pdw-torque-input.filled{color:#198754;border-color:#198754}
@media print{
  .pdw-torque-input{border:none;color:#000;background:transparent}
  html,body{margin:0;padding:0;background:#fff}
  .toolbar{display:none!important}
  /* break BEFORE each page (not after) so no trailing blank sheet */
  .fc-doc-page{box-shadow:none;margin:0 auto;max-width:none;width:fit-content;page-break-before:always;page-break-inside:avoid}
  .fc-doc-page:first-child{page-break-before:auto}
  /* keep one manual page on one sheet — scale the scan to the printable area */
  .fc-doc-page .pdw-page img{max-height:261mm;width:auto;max-width:100%}
  @page{size:letter portrait;margin:8mm}
}
</style></head><body>
<div class="toolbar">
  <h1>' . e($title) . '</h1>
  <span class="legend">
    <span><i style="background:#198754"></i>Pass</span>
    <span><i style="background:#6f42c1"></i>Repair</span>
    <span><i style="background:#dc3545"></i>Fail</span>
    <span><i style="background:#b58900"></i>no data</span>
  </span>
  ' . ($hasTorqueRange ? '<button class="btn" id="autoTorqueBtn" title="Suggestions are drafted on open for empty fields (CMM range); this re-rolls them. Fields you typed/edited are never touched. Nothing is stored until Save torque." style="background:#fff;color:#fd7e14;border:1px solid #fd7e14">&#9881; Re-roll</button>' : '') . '
  ' . ($hasTorque ? '<button class="btn" id="saveTorqueBtn" style="background:#fd7e14;color:#fff">&#128295; Save torque</button>' : '') . '
  <button class="btn btn-success" id="savePdfBtn">&#128190; Save PDF</button>
  <button class="btn btn-primary" onclick="window.print()" title="Select «Print on both sides» (duplex) in the print dialog">&#9112; Print</button>
  <span style="font-size:10px;color:#6c757d">two-sided: enable duplex in the print dialog</span>
</div>
' . ($hasTorque ? '<div id="torqueBanner" style="display:none;background:#fff3cd;color:#664d03;border:1px solid #ffe69c;padding:8px 16px;font-size:12px">⚠ Fill every torque value (orange fields on the Torque page) and press “Save torque” before saving the PDF.</div>' : '') . '
' . $pagesHtml . '
<script>
const FC_HAS_TORQUE = ' . ($hasTorque ? 'true' : 'false') . ';
const FC_TORQUE_URL = ' . json_encode($torqueSaveUrl) . ';
const FC_CSRF = ' . json_encode(csrf_token()) . ';

function fcCollectTorque() {
    const map = {};
    document.querySelectorAll(".pdw-torque-input").forEach(function (i) { map[i.dataset.elementId] = i.value.trim(); });
    return map;
}
function fcAnyTorqueEmpty() {
    return [...document.querySelectorAll(".pdw-torque-input")].some(function (i) { return i.value.trim() === ""; });
}
async function fcSaveTorque() {
    const r = await fetch(FC_TORQUE_URL, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": FC_CSRF, "Accept": "application/json", "Content-Type": "application/json" },
        body: JSON.stringify({ values: fcCollectTorque() }),
    });
    const j = await r.json();
    if (!r.ok) throw new Error(j.message || "Torque save failed");
}
document.querySelectorAll(".pdw-torque-input").forEach(function (i) {
    const sync = function () { i.classList.toggle("filled", i.value.trim() !== ""); };
    i.addEventListener("input", sync);
    i.addEventListener("blur", function () {           // mask: 0.00 or N/A
        const raw = i.value.trim();
        if (/^n\/?a$/i.test(raw)) { i.value = "N/A"; }
        else { const v = parseFloat(raw); i.value = isNaN(v) ? "" : v.toFixed(2); }
        sync();
    });
    sync();
});
// Auto-fill: suggest a realistic wrench setting inside the CMM range for every
// EMPTY torque field that carries data-tq-min/max. Step depends on the range
// width — narrow (<5) uses whole units, wider uses multiples of 5 — so the
// value reads like an actual wrench setting (175, not 173.42).
// Runs once on page open (draft suggestions); the button re-rolls. Values the
// tech typed or edited are NEVER overwritten, and nothing is persisted until
// Save torque — the save stays the explicit confirmation step.
function fcTorqueSuggest(min, max) {
    const range = max - min;
    const step = range < 5 ? 1 : 5;
    const lo = Math.ceil(min / step) * step;
    const hi = Math.floor(max / step) * step;
    if (lo > hi) return Math.round(((min + max) / 2) * 100) / 100; // no step candidate inside
    const n = Math.floor((hi - lo) / step) + 1;
    return lo + step * Math.floor(Math.random() * n);
}
function fcAutoFillTorque() {
    let filled = 0, skipped = 0;
    document.querySelectorAll(".pdw-torque-input").forEach(function (i) {
        // fillable = empty, or an untouched auto-suggestion (re-roll)
        if (i.value.trim() !== "" && i.dataset.auto !== "1") return;
        const min = parseFloat(i.dataset.tqMin), max = parseFloat(i.dataset.tqMax);
        if (isNaN(min) || isNaN(max)) { if (i.value.trim() === "") skipped++; return; }
        i.value = fcTorqueSuggest(min, max).toFixed(2);
        i.dispatchEvent(new Event("input"));
        i.dataset.auto = "1"; // re-mark: the input handler clears it on manual edits
        filled++;
    });
    return { filled: filled, skipped: skipped };
}
// a manual edit claims the field — re-roll will not touch it anymore
document.querySelectorAll(".pdw-torque-input").forEach(function (i) {
    i.addEventListener("input", function (ev) { if (ev.isTrusted) delete i.dataset.auto; });
});
const fcAutoBtn = document.getElementById("autoTorqueBtn");
if (fcAutoBtn) {
    fcAutoBtn.addEventListener("click", function () {
        const r = fcAutoFillTorque();
        this.textContent = "⚙ Re-roll (" + r.filled + (r.skipped ? ", manual: " + r.skipped : "") + ")";
    });
    fcAutoFillTorque(); // draft suggestions appear right away; tech adjusts & saves
}
const fcTorqueBtn = document.getElementById("saveTorqueBtn");
if (fcTorqueBtn) {
    fcTorqueBtn.addEventListener("click", async function () {
        this.disabled = true; const t = this.textContent; this.textContent = "Saving…";
        try { await fcSaveTorque(); this.textContent = "Torque saved ✓"; }
        catch (e) { alert(e.message); this.textContent = t; }
        finally { this.disabled = false; }
    });
}
document.getElementById("savePdfBtn").addEventListener("click", async function () {
    if (FC_HAS_TORQUE && fcAnyTorqueEmpty()) {
        const b = document.getElementById("torqueBanner"); if (b) b.style.display = "";
        alert("Fill every torque value and press “Save torque” before saving the PDF.");
        return;
    }
    this.disabled = true; this.textContent = "Saving…";
    try {
        if (FC_HAS_TORQUE) { await fcSaveTorque(); }
        const r = await fetch(' . json_encode($saveUrl) . ', {
            method: "POST",
            headers: { "X-CSRF-TOKEN": FC_CSRF, "Accept": "application/json", "Content-Type": "application/json" },
            body: JSON.stringify({ to_library: 1 }),
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

        // A PASSing initial on the bore is a legal basis for the sketch too:
        // the bore is within limits, machining will never happen, so a final
        // measurement will never exist. Case B computes req OD from that actual
        // size; Case A renders the STANDARD bushing (no oversize step).
        $initialPassBore = function (int $paramId) use ($workorder) {
            return WoMeasurement::where('workorder_id', $workorder->id)
                ->where('manual_parameter_id', $paramId)
                ->where('stage', 'initial')
                ->where('result', 'PASS')
                ->whereNotNull('actual_value')
                ->latest('id')->first();
        };
        $findMatingBoreInitialPass = function ($odParam) use ($manualInspectionComponent, $initialPassBore) {
            $odPointIds = $odParam->points->pluck('id')->all();
            if (empty($odPointIds)) return null;
            return ManualParameter::whereHas('points', fn($q) =>
                    $q->whereIn('manual_dimension_points.id', $odPointIds)
                )
                ->where('inspection_component_id', '!=', $manualInspectionComponent->id)
                ->with('points:id,code')
                ->get()
                ->first(fn($p) => $initialPassBore($p->id) !== null);
        };

        // Collect repair info PER OD param — two bushing positions on different
        // lugs may have different repairs (different steps / different req OD).
        $repairInfos = [];

        // Case A: discrete repair steps
        foreach ($odParamsWithSteps as $odParam) {
            $matingParam = $findMatingBore($odParam, true);
            if ($matingParam) {
                $meas = WoMeasurement::where('workorder_id', $workorder->id)
                    ->where('manual_parameter_id', $matingParam->id)
                    ->where('stage', 'final')->whereNotNull('repair_step_no')
                    ->latest('id')->first();
                $repairInfos[] = [
                    'useTolerance'  => false,
                    'standard'      => false,
                    'boreStage'     => 'final',
                    'odParam'       => $odParam,
                    'matingParam'   => $matingParam,
                    'stepNo'        => $meas->repair_step_no,
                    'step'          => $odParam->repairSteps->first(fn($s) => $s->step_no === $meas->repair_step_no),
                    'measuredValue' => (float) $meas->actual_value,
                ];
                continue;
            }
            // bore not machined into a step: a PASSing initial = within limits →
            // the position takes the STANDARD bushing (no oversize, orig OD dims)
            $matingParam = $findMatingBoreInitialPass($odParam);
            if (!$matingParam) continue;
            $meas = $initialPassBore($matingParam->id);
            $repairInfos[] = [
                'useTolerance'  => false,
                'standard'      => true,
                'boreStage'     => 'initial',
                'odParam'       => $odParam,
                'matingParam'   => $matingParam,
                'stepNo'        => null,
                'step'          => null,
                'measuredValue' => (float) $meas->actual_value,
            ];
        }

        // Case B: continuous — fit derived from the pair's factory limits:
        //   fit_min = OD_orig_min − ID_orig_max; fit_max = OD_orig_max − ID_orig_min
        //   req OD  = [ID_final + fit_min, ID_final + fit_max]
        $caseAIds = collect($repairInfos)->pluck('odParam.id')->flip();
        foreach ($odParamsWithFit as $odParam) {
            if ($caseAIds->has($odParam->id)) continue;
            $matingParam = $findMatingBore($odParam, false) ?? $findMatingBoreInitialPass($odParam);
            if (!$matingParam) continue;
            if ($matingParam->orig_dim_min === null || $matingParam->orig_dim_max === null) continue;
            // final when machined; otherwise the PASSing initial IS the bore size
            $meas = WoMeasurement::where('workorder_id', $workorder->id)
                ->where('manual_parameter_id', $matingParam->id)
                ->where('stage', 'final')->whereNotNull('actual_value')
                ->latest('id')->first()
                ?? $initialPassBore($matingParam->id);
            $boreStage = $meas->stage;
            $bore   = (float) $meas->actual_value;
            $fitMin = round((float)$odParam->orig_dim_min - (float)$matingParam->orig_dim_max, 4);
            $fitMax = round((float)$odParam->orig_dim_max - (float)$matingParam->orig_dim_min, 4);
            $repairInfos[] = [
                'useTolerance'    => true,
                'standard'        => false,
                'boreStage'       => $boreStage,
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
            $key = !empty($info['standard'])
                ? 'S|' . $info['odParam']->orig_dim_min . '|' . $info['odParam']->orig_dim_max
                : ($info['useTolerance']
                    ? 'B|' . $info['calculatedOdMin'] . '|' . $info['calculatedOdMax']
                    : 'A|' . $info['stepNo'] . '|' . ($info['step']?->component?->part_number ?? ''));
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

        $titleText = $icLabel;
        if ($repairInfo) {
            $titleText .= !empty($repairInfo['standard']) ? ' — Standard (bore in limits)'
                : ($repairInfo['useTolerance'] ? ' — Manufacture to fit'
                : ' — Oversize ' . $repairInfo['stepNo']);
        }

        $buildDataPanel = function (array $info, array $pointCodes, int $qty) use ($workorder, $partCell, $partNums, $iplNums): string {
            $posRow = $pointCodes
                ? '<tr><td style="color:#6c757d;padding:3px 12px 3px 0">Position</td><td><strong>' . e(implode(', ', $pointCodes)) . '</strong>'
                    . ($qty > 1 ? ' <span style="color:#0d6efd;font-weight:700">· Qty ' . $qty . '</span>' : '') . '</td></tr>'
                : '';

            $stageTag = ($info['boreStage'] ?? 'final') === 'initial'
                ? ' <span style="color:#6c757d;font-size:10px">(initial)</span>' : '';
            $boreRow = '<tr><td>Bore measured</td><td><strong>'
                . number_format($info['measuredValue'], 4) . ' in</strong>' . $stageTag
                . ' <span style="color:#6c757d">(' . e($info['matingParam']->description ?? '') . ')</span></td></tr>';

            // ── Standard: bore within limits — no oversize, factory OD dims ────
            if (!empty($info['standard'])) {
                $od = $info['odParam'];
                $pnRow = '<tr><td>Required P/N</td><td><strong>' . e($partNums[0] ?? '—') . '</strong>'
                    . (($iplNums[0] ?? null) ? ' <span style="color:#6c757d">(IPL# ' . e($iplNums[0]) . ')</span>' : '')
                    . ' <span style="color:#6c757d">standard</span></td></tr>';
                $odRows = ($od->orig_dim_min !== null && $od->orig_dim_max !== null)
                    ? '<tr style="border-top:1px solid #dee2e6"><td style="padding-top:10px">OD required min</td><td style="padding-top:10px;font-size:14px;font-weight:700">' . number_format((float)$od->orig_dim_min, 4) . ' in</td></tr>'
                      . '<tr><td>OD required max</td><td style="font-size:14px;font-weight:700">' . number_format((float)$od->orig_dim_max, 4) . ' in</td></tr>'
                    : '';
                return '
                <table style="border-collapse:collapse;width:100%;font-size:12px">
                  <tr><td style="color:#6c757d;padding:3px 12px 3px 0;white-space:nowrap">W/O</td><td><strong>' . e('W' . $workorder->number) . '</strong></td></tr>
                  <tr><td style="color:#6c757d;padding:3px 12px 3px 0">Part</td><td>' . $partCell . '</td></tr>
                  ' . $posRow . '
                  <tr><td style="color:#6c757d;padding:3px 12px 3px 0">Repair step</td><td><span style="color:#198754;font-weight:700">standard — bore within limits</span></td></tr>
                  ' . $boreRow . $pnRow . $odRows . '
                </table>';
            }

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
            } elseif ($repairInfo && !empty($repairInfo['standard'])) {
                // standard bushing (bore in limits) → factory OD dims on the drawing
                $od = $repairInfo['odParam'];
                if ($od->orig_dim_min !== null && $od->orig_dim_max !== null) {
                    $drawingContext['od_dim_min'] = (float) $od->orig_dim_min;
                    $drawingContext['od_dim_max'] = (float) $od->orig_dim_max;
                }
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
                $boreParamIds = []; // bore param ids already covered by the messages
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
                            $boreParamIds[$matingBore->id] = true;
                        }
                    }
                }

                $items = array_values(array_filter(array_map(function ($mv) use ($calculatedOdParamIds, $boreParamIds) {
                    // Skip OD params that are calculated — replaced by bore messages below
                    if (isset($mv['param_id']) && isset($calculatedOdParamIds[$mv['param_id']])) return null;
                    // The bore itself may also sit on the drawing as a plain element —
                    // it's already listed once as "(required for OD calculation)"
                    if (isset($mv['param_id']) && isset($boreParamIds[$mv['param_id']])) return null;
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
  .pdw-dim.st-pass{color:#198754;border-color:#198754}
  .pdw-dim.st-fail{color:#dc3545;border-color:#dc3545}
  .pdw-dim.st-repair{color:#6f42c1;border-color:#6f42c1}
  .pdw-dim.st-nodata{color:#b58900;border-color:#b58900}
  .pdw-dim.pdw-value{border:none;background:transparent;padding:0}
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
            'value_source'        => 'nullable|in:static,measurement,calc,formula,torque',
            'static_value'        => 'nullable|numeric',
            'source_parameter_id' => 'nullable|exists:manual_parameters,id',
            'formula_expression'  => 'nullable|string|max:500',
            'formula_tol_plus'    => 'nullable|numeric|min:0',
            'formula_tol_minus'   => 'nullable|numeric|min:0',
            'torque_min'          => 'nullable|numeric|min:0',
            'torque_max'          => 'nullable|numeric|min:0|gte:torque_min',
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
            ->withCount('repairSteps')
            ->orderBy('inspection_component_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'description' => $p->description,
                'points'      => $p->points->pluck('code')->filter()->implode(', '),
                'part'        => $p->inspectionComponent?->label,
                // editor: real-size skeleton of the steps_table element
                'steps_count' => $p->repair_steps_count,
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
            'torque_min'          => $e->torque_min,
            'torque_max'          => $e->torque_max,
            'placeholder'         => $e->placeholder,
            'text'                => $e->text,
            'font_size'           => $e->font_size,
            'sort_order'          => $e->sort_order,
        ];
    }
}
