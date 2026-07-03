{{-- Dimensions tab: figure list (left) + figure viewer with points (right) --}}
<style>
    #dim-tab-wrap {
        display: flex;
        height: 72vh;
        gap: 0;
        overflow: hidden;
    }
    #dim-parts-panel {
        width: 200px;
        min-width: 200px;
        border-right: 1px solid var(--bs-border-color);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #dim-insp-components {
        flex: 1 1 auto;
        overflow-y: auto;
    }
    #dim-figures-panel {
        width: 200px;
        min-width: 200px;
        border-right: 1px solid var(--bs-border-color);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #dim-figures-list {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 6px 0;
    }
    .dim-insp-comp-row {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        font-size: 11px;
        border-bottom: 1px solid rgba(0,0,0,.04);
        cursor: grab;
    }
    .dim-insp-comp-row:last-child { border-bottom: none; }
    .dim-insp-comp-row:hover { background: rgba(0,0,0,.04); }
    .dim-insp-comp-row .drag-handle { color: var(--bs-secondary-color); font-size: 10px; cursor: grab; opacity: 0; transition: opacity .15s; }
    .dim-insp-comp-row:hover .drag-handle { opacity: .5; }
    .dim-insp-comp-ipl { font-weight: 700; color: #5ee3ff; min-width: 36px; }
    .dim-insp-comp-name { flex: 1 1 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dim-insp-comp-del { color: var(--bs-secondary-color); font-size: 10px; line-height: 1; padding: 0 2px; opacity: 0; transition: opacity .15s; }
    .dim-insp-comp-row:hover .dim-insp-comp-del { opacity: .45; }
    .dim-insp-comp-del:hover { opacity: 1 !important; color: #dc3545; }
    .dim-ic-expand { opacity: 0; transition: opacity .15s; }
    .dim-insp-comp-row:hover .dim-ic-expand { opacity: .5; }
    .dim-ic-expand:hover { opacity: 1 !important; }
    .dim-ic-plan { opacity: .55; transition: opacity .15s, color .15s; }
    .dim-ic-plan:hover { opacity: 1 !important; color: #0d6efd !important; }
    .dim-figure-item {
        padding: 6px 12px;
        cursor: pointer;
        font-size: 13px;
        border-left: 3px solid transparent;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .dim-figure-item:hover { background: rgba(13,110,253,.08); }
    .dim-figure-item.active {
        border-left-color: #0d6efd;
        background: rgba(13,110,253,.12);
        font-weight: 600;
    }
    .dim-figure-badge {
        font-size: 10px;
        padding: 1px 5px;
    }
    #dim-viewer-panel {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #dim-viewer-toolbar {
        padding: 6px 10px;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        min-height: 44px;
    }
    #dim-figure-canvas-wrap {
        flex: 1 1 auto;
        overflow: auto;
        position: relative;
        background: rgba(0,0,0,.04);
    }
    #dim-figure-canvas-wrap.add-point-mode {
        cursor: crosshair;
    }
    #dim-figure-img {
        display: block;
        max-width: 100%;
        width: auto;
        height: auto;
    }
    #dim-figure-img-container {
        position: relative;
        display: inline-block;
        user-select: none;
        transition: none;
        transform-origin: center center;
    }
    .dim-point-marker {
        position: absolute;
        transform: translate(-50%, -50%);
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #0d6efd;
        border: 2px solid #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        color: #fff;
        z-index: 10;
        box-shadow: 0 1px 4px rgba(0,0,0,.4);
        transition: transform .15s;
    }
    .dim-point-marker:hover { transform: translate(-50%, -50%) scale(1.2); }
    .dim-point-marker.active {
        background: #dc3545;
        transform: translate(-50%, -50%) scale(1.15);
    }
    .dim-point-marker.navigation {
        background: #6f42c1;
    }
    #dim-specs-panel {
        width: 360px;
        min-width: 360px;
        border-left: 1px solid var(--bs-border-color);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #dim-specs-header {
        padding: 8px 12px;
        border-bottom: 1px solid var(--bs-border-color);
        font-size: 13px;
        font-weight: 600;
    }
    #dim-specs-body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 8px;
    }
    .dim-comp-card {
        border: 1px solid var(--bs-border-color);
        border-radius: 6px;
        margin-bottom: 8px;
        font-size: 12px;
        overflow: hidden;
    }
    .dim-comp-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        padding: 5px 8px;
        background: rgba(94,227,255,.08);
        border-bottom: 1px solid rgba(94,227,255,.2);
    }
    .dim-comp-card-title {
        font-size: 12px;
        font-weight: 700;
        color: #5ee3ff;
        text-transform: uppercase;
        letter-spacing: .04em;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dim-btn-xs {
        font-size: 10px;
        padding: 1px 6px;
        line-height: 1.4;
        white-space: nowrap;
    }
    .dim-comp-section {
        padding: 5px 8px 6px;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .dim-comp-section:last-child { border-bottom: none; }
    .dim-comp-section-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--bs-secondary-color);
        margin-bottom: 4px;
    }
    .dim-spec-row {
        padding: 4px 0;
        border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .dim-spec-row:last-child { border-bottom: none; padding-bottom: 0; }
    .dim-spec-label {
        font-weight: 700;
        font-size: 13px;
        color: #0d6efd;
    }
    .dim-spec-fits-badge {
        font-size: 10px;
    }
    .dim-rule-row {
        padding: 3px 0;
        border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .dim-rule-row:last-of-type { border-bottom: none; }
    .dim-rule-process-item {
        display: flex;
        align-items: center;
        gap: 4px;
        background: rgba(0,0,0,.04);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 11px;
        margin-bottom: 2px;
    }
    .dim-dim-row {
        display: flex;
        gap: 4px;
        flex-wrap: nowrap;
        margin-top: 4px;
    }
    .dim-dim-cell {
        flex: 1 1 0;
        min-width: 0;
        background: rgba(0,0,0,.04);
        border-radius: 4px;
        padding: 2px 4px;
    }
    .dim-dim-cell-label { font-size: 9px; color: var(--bs-secondary-color); white-space: nowrap; }
    .dim-dim-cell-val { font-size: 11px; font-weight: 600; font-family: monospace; white-space: nowrap; }
    #dim-empty-state {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--bs-secondary-color);
        font-size: 14px;
    }
    .dim-mode-btn.active {
        background: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #fff !important;
    }
    .dim-area-marker {
        position: absolute;
        background: rgba(200, 200, 200, 0.22);
        border: 1.5px solid rgba(130, 130, 130, 0.45);
        border-radius: 3px;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        padding: 2px 4px;
        box-sizing: border-box;
        transition: background .15s, border-color .15s;
    }
    .dim-area-marker:hover {
        background: rgba(170, 170, 170, 0.38);
        border-color: rgba(90, 90, 90, 0.65);
    }
    .dim-area-marker.active {
        background: rgba(220, 53, 69, 0.12);
        border-color: rgba(220, 53, 69, 0.55);
    }
    .dim-area-label {
        font-size: 9px;
        font-weight: 700;
        color: rgba(60, 60, 60, 0.9);
        background: rgba(255, 255, 255, 0.72);
        padding: 0 3px;
        border-radius: 2px;
        white-space: nowrap;
        line-height: 1.4;
    }
    .dim-area-preview {
        position: absolute;
        background: rgba(13, 110, 253, 0.12);
        border: 2px dashed rgba(13, 110, 253, 0.55);
        border-radius: 3px;
        pointer-events: none;
        z-index: 20;
        box-sizing: border-box;
    }
    #dim-figure-canvas-wrap.add-area-mode { cursor: crosshair; }
    #dim-figure-canvas-wrap.add-line-mode { cursor: crosshair; }
    #dim-figure-canvas-wrap.add-view-mode { cursor: crosshair; }
    #dim-lines-svg {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        overflow: visible;
        z-index: 12;
        pointer-events: none;
    }
    .dim-callout-dot {
        position: absolute;
        transform: translate(-50%, -50%);
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #0d6efd;
        border: 1.5px solid #fff;
        cursor: pointer;
        z-index: 10;
        box-shadow: 0 1px 3px rgba(0,0,0,.35);
        transition: transform .12s;
    }
    .dim-callout-dot:hover { transform: translate(-50%, -50%) scale(1.6); }
    .dim-callout-dot.active { background: #dc3545; transform: translate(-50%, -50%) scale(1.4); }
    .dim-callout-label {
        position: absolute;
        transform: translate(-50%, -50%);
        background: #fff;
        border: 1.5px solid #333;
        border-radius: 2px;
        padding: 1px 6px;
        font-size: 14px;
        font-weight: 700;
        color: #111;
        cursor: pointer;
        z-index: 11;
        white-space: nowrap;
        box-shadow: 0 1px 4px rgba(0,0,0,.18);
        line-height: 1.6;
    }
    .dim-callout-label:hover { background: rgba(225,232,255,1); border-color: #0d6efd; }
    .dim-callout-label.active { border-color: #dc3545; color: #dc3545; background: #fff7f7; }
    #dim-figure-canvas-wrap.add-callout-mode { cursor: crosshair; }
    #dim-figure-canvas-wrap.line-label-mode  { cursor: crosshair; }
    @keyframes dimPointPing {
        0%   { transform: scale(1);   opacity: 1; box-shadow: 0 0 0 0 rgba(255,193,7,.6); }
        100% { transform: scale(3.5); opacity: 0; box-shadow: 0 0 0 12px rgba(255,193,7,0); }
    }
    #dim-figure-canvas-wrap.add-text-mode    { cursor: crosshair; }
    #dim-figure-canvas-wrap.pan-ready { cursor: grab; }
    #dim-figure-canvas-wrap.panning  { cursor: grabbing; }
    .dim-text-label {
        position: absolute;
        transform: translate(-50%, -50%);
        background: rgba(20, 184, 166, 0.12);
        border: 1.5px solid #14b8a6;
        border-radius: 8px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: 600;
        color: #0d9488;
        cursor: pointer;
        z-index: 11;
        white-space: nowrap;
        box-shadow: 0 1px 4px rgba(0,0,0,.12);
        line-height: 1.6;
    }
    .dim-text-label:hover { background: rgba(20, 184, 166, 0.22); border-color: #0d9488; }
    .dim-text-label.active { border-color: #dc3545; color: #dc3545; background: rgba(220,53,69,.06); }
    .dim-callout-dot.text { background: #14b8a6; }
    .dim-callout-dot.text.active { background: #dc3545; }
</style>

@php
    $manualId = $cmm->id;
    $csrfToken = csrf_token();
@endphp

<div id="dim-tab-wrap" class="m-2">

    {{-- Parts panel --}}
    <div id="dim-parts-panel">
        <div class="px-2 py-1 d-flex align-items-center gap-2" style="border-bottom:1px solid var(--bs-border-color)">
            <span class="fw-semibold" style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--bs-secondary-color)">Parts</span>
            <button class="btn btn-link btn-sm ms-auto p-0" style="font-size:11px" id="dimAddInspCompBtn" title="Add part">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
        <div id="dim-insp-components">
            <div id="dim-insp-comp-list"></div>
        </div>
    </div>

    {{-- Figures panel --}}
    <div id="dim-figures-panel">
        <div class="px-2 py-2 border-bottom d-flex align-items-center gap-2">
            <span class="fw-semibold" style="font-size:13px">Figures</span>
            <button class="btn btn-outline-primary btn-sm ms-auto" style="font-size:11px;padding:2px 8px" id="dimAddFigureBtn">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
        <div id="dim-figures-list"></div>
    </div>

    {{-- Center: figure viewer + points --}}
    <div id="dim-viewer-panel">
        <div id="dim-viewer-toolbar">
            <button class="btn btn-outline-secondary btn-sm d-none" id="dimBackToParentBtn" title="Back to parent figure">
                <i class="bi bi-arrow-left"></i> Back
            </button>
            <span id="dim-viewer-title" class="fw-semibold" style="font-size:13px;color:var(--bs-secondary-color)">Select a figure</span>
            <div class="ms-auto d-flex gap-2 align-items-center d-none" id="dim-viewer-actions">
                <button class="btn btn-outline-secondary btn-sm dim-mode-btn" id="dimAddPointModeBtn" title="Add measurement point (click on image)">
                    <i class="bi bi-plus-circle"></i> Add Point
                </button>
                <button class="btn btn-outline-secondary btn-sm dim-mode-btn" id="dimAddCalloutModeBtn" title="Add callout point: 1st click = dot location, 2nd click = label position">
                    <i class="bi bi-geo-alt"></i> Add Callout
                </button>
                <button class="btn btn-outline-secondary btn-sm dim-mode-btn" id="dimAddCircleModeBtn" title="Add circle area (click center, drag radius)">
                    <i class="bi bi-circle"></i> Add Circle
                </button>
                <button class="btn btn-outline-secondary btn-sm dim-mode-btn" id="dimAddAreaModeBtn" title="Add navigation area (drag to draw)">
                    <i class="bi bi-bounding-box"></i> Add Area
                </button>
                <button class="btn btn-outline-secondary btn-sm dim-mode-btn" id="dimAddLineModeBtn" title="Add linear dimension (two clicks)">
                    <i class="bi bi-rulers"></i> Add Line
                </button>
                <button class="btn btn-outline-secondary btn-sm dim-mode-btn" id="dimAddViewModeBtn" title="Add view arrow: click to place; drag the tip to rotate; opens a detail figure">
                    <i class="bi bi-arrow-up-right-circle"></i> View
                </button>
                <button class="btn btn-outline-secondary btn-sm dim-mode-btn" id="dimAddTextModeBtn" title="Add part label: 1st click = dot on part, 2nd click = label position">
                    <i class="bi bi-tag"></i> Add Label
                </button>
                <select class="form-select form-select-sm" id="dimPointFinder" style="width:auto;font-size:11px" title="Find a point on the figure">
                    <option value="">Find point…</option>
                </select>
                <span class="text-secondary ms-1" style="font-size:11px;user-select:none;min-width:38px;text-align:right" id="dimZoomLabel">100%</span>
                <button class="btn btn-outline-secondary btn-sm py-0 px-1" id="dimZoomResetBtn" title="Reset zoom (100%)" style="font-size:12px">↺</button>
                <button class="btn btn-outline-warning btn-sm" id="dimEditFigureBtn" title="Edit figure">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger btn-sm" id="dimDeleteFigureBtn" title="Delete figure">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div id="dim-figure-canvas-wrap">
            <div id="dim-empty-state">
                <div class="text-center">
                    <i class="bi bi-image" style="font-size:3rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
                    Select a figure from the left panel
                </div>
            </div>
            <div id="dim-figure-img-container" class="d-none">
                <img id="dim-figure-img" src="" alt="">
                <div id="dim-points-overlay"></div>
                <svg id="dim-lines-svg"></svg>
            </div>
        </div>
    </div>

    {{-- Right: specs panel --}}
    <div id="dim-specs-panel">
        <div id="dim-specs-header" class="d-flex align-items-center justify-content-between">
            <span id="dim-specs-point-label">Select a point</span>
            <button class="btn btn-outline-secondary btn-sm d-none py-0 px-2" id="dimEditPointBtn" style="font-size:11px">
                <i class="bi bi-pencil"></i> Edit Point
            </button>
        </div>
        <div id="dim-specs-body">
            <div class="text-center text-secondary py-4" style="font-size:12px" id="dim-specs-empty">
                Click a point on the figure to view specs
            </div>
            <div id="dim-specs-list" class="d-none"></div>
        </div>
        <div class="border-top px-2 py-2 d-none" id="dim-specs-footer">
            <button class="btn btn-outline-primary btn-sm w-100" id="dimAddSpecBtn" style="font-size:12px">
                <i class="bi bi-plus-lg"></i> Add Parameter
            </button>
            <div class="d-flex gap-1 mt-1">
                <select class="form-select form-select-sm" id="dimCopyFromSelect" style="font-size:11px">
                    <option value="">Copy setup from point…</option>
                </select>
                <select class="form-select form-select-sm d-none" id="dimCopyIcSelect" style="font-size:11px;max-width:45%">
                    <option value="">All parts</option>
                </select>
                <button class="btn btn-outline-secondary btn-sm" id="dimCopyFromBtn" style="font-size:11px;white-space:nowrap" title="Deep-copy parameters (codes, rules, steps) from the selected point onto this point">
                    <i class="bi bi-copy"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Add / Edit Repair Rule --}}
<div class="modal fade" id="dimRepairRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dimRepairRuleModalTitle">Add Repair Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dimRuleId">
                <input type="hidden" id="dimRuleParamId">

                <div class="mb-3">
                    <label class="form-label form-label-sm">Rule name <span class="text-secondary" style="font-weight:400">(e.g. Repair plating, Replace bushing)</span></label>
                    <input type="text" class="form-control form-control-sm" id="dimRuleName" maxlength="100" placeholder="Optional — identifies this repair procedure">
                </div>

                <div class="row g-3">
                    {{-- Left column: Action + Triggers --}}
                    <div class="col-5">
                        <div class="mb-3">
                            <label class="form-label form-label-sm">Action</label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="dimRuleAction" id="dimRuleActionRepair" value="repair" checked>
                                    <label class="form-check-label" for="dimRuleActionRepair" style="font-size:13px">Repair</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="dimRuleAction" id="dimRuleActionReplace" value="order_new">
                                    <label class="form-check-label" for="dimRuleActionReplace" style="font-size:13px">Order new</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="dimRuleAction" id="dimRuleActionEc" value="ec">
                                    <label class="form-check-label" for="dimRuleActionEc" style="font-size:13px">EC</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-sm">Triggers <span class="text-danger">*</span></label>
                            <div id="dimRuleTriggerList" class="mb-2"></div>
                            <div class="d-flex gap-2 align-items-center flex-wrap" id="dimRuleTriggerAddRow">
                                <select class="form-select form-select-sm" id="dimRuleTriggerSel" style="max-width:220px">
                                    <option value="">+ Add trigger...</option>
                                    <optgroup label="Measurement — below min">
                                        <option value="below_orig">Below original min</option>
                                        <option value="below_wear">Below wear min</option>
                                    </optgroup>
                                    <optgroup label="Measurement — above max">
                                        <option value="above_orig">Above original max</option>
                                        <option value="above_wear">Above wear max</option>
                                    </optgroup>
                                    <optgroup label="Finding">
                                        <option value="finding_measurement">Finding — Measurement</option>
                                        <option value="finding_inspection">Finding — Inspection</option>
                                    </optgroup>
                                    <option value="manual">Manual</option>
                                </select>
                                <select class="form-select form-select-sm d-none" id="dimRuleTriggerCode" style="max-width:180px">
                                    <option value="">— Any defect —</option>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="dimRuleTriggerAddBtn">Add</button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label form-label-sm">Notes</label>
                            <textarea class="form-control form-control-sm" id="dimRuleNotes" rows="2" placeholder="Optional notes"></textarea>
                        </div>
                    </div>

                    {{-- Right column: Processes --}}
                    <div class="col-7 border-start ps-3">
                        <div class="mb-3">
                            <label class="form-label form-label-sm">Processes</label>
                            <div id="dimRuleProcessList" class="mb-2"></div>
                            <select id="dimRuleProcessName" style="width:100%">
                                <option value=""></option>
                            </select>
                            <div id="dimRuleProcessOptions" class="mt-2 d-none" style="display:flex;flex-wrap:wrap;gap:4px"></div>
                        </div>
                    </div>
                </div>

                <div class="text-danger small d-none mt-2" id="dimRuleError"></div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="dimRuleDeleteBtn">Delete</button>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="dimRuleSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Add / Edit Figure --}}
<div class="modal fade" id="dimFigureModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dimFigureModalTitle">Add Figure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dimFigureId" value="">
                <div class="mb-3">
                    <label class="form-label form-label-sm">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="dimFigureTitle" placeholder="e.g. Section A-A MLG">
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-sm">Type <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="dimFigureType">
                        <option value="detail">Detail (section / view)</option>
                        <option value="overview">Overview (general location)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-sm">Parent figure</label>
                    <select class="form-select form-select-sm" id="dimFigureParent">
                        <option value="">— None —</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-sm">Image <span class="text-danger">*</span></label>
                    <input type="hidden" id="dimFigureImagePath">
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="dimFigureUploadBtn" style="white-space:nowrap;font-size:12px">
                            <i class="bi bi-upload"></i> Choose file
                        </button>
                        <span id="dimFigureUploadName" class="text-secondary" style="font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">No file chosen</span>
                        <input type="file" id="dimFigureFileInput" accept="image/png,image/jpeg,image/webp,image/gif" class="d-none">
                    </div>
                    <div id="dimFigureUploadProgress" class="mt-1 d-none">
                        <div class="progress" style="height:4px"><div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div></div>
                    </div>
                    <div id="dimFigurePreviewWrap" class="mt-2 d-none">
                        <img id="dimFigurePreview" src="" alt="preview"
                             style="max-height:100px;max-width:100%;border-radius:4px;border:1px solid var(--bs-border-color)">
                    </div>
                    <div class="form-text">PNG/JPEG/WebP, до 10 MB. Рекомендуемая ширина 1200–2400 px.</div>
                </div>
                <div class="row g-2">
                    <div class="col-auto">
                        <label class="form-label form-label-sm">Sort</label>
                        <input type="number" class="form-control form-control-sm" id="dimFigureSort" value="0">
                    </div>
                    <div class="col">
                        <label class="form-label form-label-sm">Width (px) <span class="text-secondary" style="font-size:10px">авто</span></label>
                        <input type="number" class="form-control form-control-sm" id="dimFigureWidth" placeholder="—" readonly>
                    </div>
                    <div class="col">
                        <label class="form-label form-label-sm">Height (px) <span class="text-secondary" style="font-size:10px">авто</span></label>
                        <input type="number" class="form-control form-control-sm" id="dimFigureHeight" placeholder="—" readonly>
                    </div>
                </div>
                <div class="text-danger small mt-2 d-none" id="dimFigureError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="dimFigureSaveBtn">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Add / Edit Point --}}
<div class="modal fade" id="dimPointModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dimPointModalTitle">Add Point</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dimPointId" value="">
                <input type="hidden" id="dimPointType" value="measurement">
                <input type="hidden" id="dimPointXPct" value="">
                <input type="hidden" id="dimPointYPct" value="">
                <input type="hidden" id="dimPointX2Pct" value="">
                <input type="hidden" id="dimPointY2Pct" value="">
                <input type="hidden" id="dimPointLabelXPct" value="">
                <input type="hidden" id="dimPointLabelYPct" value="">
                <input type="hidden" id="dimPointXDisplay" value="">
                <input type="hidden" id="dimPointYDisplay" value="">
                <input type="hidden" id="dimPointX2Display" value="">
                <input type="hidden" id="dimPointY2Display" value="">
                <input type="hidden" id="dimPointLabelXDisplay" value="">
                <input type="hidden" id="dimPointLabelYDisplay" value="">
                <input type="hidden" id="dimPointWidthDisplay" value="">
                <input type="hidden" id="dimPointHeightDisplay" value="">

                <div class="mb-3 d-none" id="dimPointIcWrap">
                    <label class="form-label form-label-sm">Inspection Component <span class="text-danger">*</span></label>
                    <select class="w-100" id="dimPointIcSelect" style="font-size:13px">
                        <option value="">— Select part —</option>
                    </select>
                </div>
                <div class="mb-3" id="dimPointCodeWrap">
                    <label class="form-label form-label-sm">Ref. No. <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="dimPointCode" placeholder="A1, K, AA1">
                </div>
                <div class="mb-3 d-none" id="dimPointChildFigureWrap">
                    <label class="form-label form-label-sm">Links to figure</label>
                    <select class="form-select form-select-sm" id="dimPointChildFigure">
                        <option value="">— Select figure —</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-sm">Description</label>
                    <input type="text" class="form-control form-control-sm" id="dimPointDescription" placeholder="e.g. Pin-to-bushing interface">
                </div>
                <div class="mb-3 d-none" id="dimPointFitsWrap">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="dimPointFits">
                        <label class="form-check-label form-label-sm" for="dimPointFits">Fits &amp; Clearances</label>
                    </div>
                </div>
                <div class="mb-3 d-none" id="dimExtraArrowsWrap">
                    <label class="form-label form-label-sm d-block">Extra arrows to this name</label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="dimExtraArrowAdd">+ Add arrow</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="dimExtraArrowRemove">− Remove last</button>
                        <span class="small text-muted" id="dimExtraArrowCount">0 arrow(s)</span>
                    </div>
                    <div class="form-text">After Save, drag each extra dot on the figure to its spot; Alt-click a dot to delete it.</div>
                </div>
                <div style="max-width:90px">
                    <label class="form-label form-label-sm">Sort</label>
                    <input type="number" class="form-control form-control-sm" id="dimPointSort" value="0">
                </div>
                <div class="text-danger small mt-2 d-none" id="dimPointError"></div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="dimPointDeleteBtn">Delete Point</button>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="dimPointSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Add / Edit Parameter --}}
<div class="modal fade" id="dimSpecModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dimSpecModalTitle">Add Parameter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dimSpecId" value="">

                <div class="mb-2 d-flex align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="dimSpecRequired" checked>
                        <label class="form-check-label form-label-sm" for="dimSpecRequired">Required</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="dimSpecRequiresValue">
                        <label class="form-check-label form-label-sm" for="dimSpecRequiresValue">Record actual value</label>
                    </div>
                    <div class="d-flex align-items-center gap-1 ms-auto">
                        <label class="form-label form-label-sm mb-0" for="dimSpecQty" title="Parts installed at this position (e.g. 2 bushings per lug)">Qty</label>
                        <input type="number" class="form-control form-control-sm" id="dimSpecQty" value="1" min="1" max="99" style="width:60px">
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Part</label>
                        <select class="w-100" id="dimSpecComponent" style="font-size:13px">
                            <option value="">— None —</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="dimSpecDescription" placeholder="OD, ID, Chrome crack, Corrosion">
                    </div>
                </div>

                {{-- Defect codes --}}
                <div class="mb-3" id="dimSpecCodesWrap">
                    <label class="form-label form-label-sm">Defect Codes <span class="text-secondary" style="font-size:10px">(leave empty for measurement params)</span></label>
                    <div id="dimSpecCodesList" class="mb-2"></div>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="dimSpecCodesAdd" style="flex:1">
                            <option value="">+ Add defect code...</option>
                            @foreach($codes as $code)
                                <option value="{{ $code->id }}" data-name="{{ $code->name }}">{{ $code->name }}</option>
                            @endforeach
                        </select>
                        <select class="form-select form-select-sm" id="dimSpecCodeContext" style="width:130px" title="Finding context">
                            <option value="inspection">Inspection</option>
                            <option value="measurement">Measurement</option>
                        </select>
                    </div>
                </div>

                {{-- Dimensional limits --}}
                <div id="dimSpecMeasWrap">
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold mb-2" style="font-size:12px">Dimensional limits <span class="text-secondary fw-normal" style="font-size:11px">(leave empty for inspection-only params)</span></div>
                        <div class="row g-2 mb-2">
                            <div class="col"><label class="form-label form-label-sm">Orig Min</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecOrigMin" placeholder="0.0000"></div>
                            <div class="col"><label class="form-label form-label-sm">Orig Max</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecOrigMax" placeholder="0.0000"></div>
                        </div>
                        <div class="fw-semibold mb-1" style="font-size:11px;color:var(--bs-secondary-color)">Wear limits — leave empty to use original</div>
                        <div class="row g-2 mb-3">
                            <div class="col"><label class="form-label form-label-sm">Wear Min</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecWearMin" placeholder="—"></div>
                            <div class="col"><label class="form-label form-label-sm">Wear Max</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecWearMax" placeholder="—"></div>
                        </div>
                        <div class="fw-semibold mb-1" style="font-size:11px;color:var(--bs-secondary-color)">Repair limits — machined size after repair (leave empty if repair steps used or no repair)</div>
                        <div class="row g-2 mb-2">
                            <div class="col"><label class="form-label form-label-sm">Repair Min</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecRepairMin" placeholder="—"></div>
                            <div class="col"><label class="form-label form-label-sm">Repair Max</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecRepairMax" placeholder="—"></div>
                        </div>
                        <div class="row g-2 mb-3" id="dimSpecBushingFields" style="display:none">
                            <div class="col-6">
                                <label class="form-label form-label-sm">Fit — derived from pair limits (− = clearance)</label>
                                <div class="form-control form-control-sm font-monospace" id="dimSpecIntRange" style="background:rgba(0,0,0,.06)">—</div>
                            </div>
                        </div>

                        <div class="fw-semibold mb-1" style="font-size:11px;color:var(--bs-secondary-color)">
                            Repair Steps <span class="fw-normal">(oversize, in)</span>
                        </div>
                        <div id="dimRepairStepsList" class="mb-2"></div>
                        <div id="dimRepairStepForm" class="d-none border rounded p-2 mb-2" style="background:rgba(0,0,0,.04)">
                            <input type="hidden" id="dimRsEditId">
                            <div class="row g-2 mb-2">
                                <div class="col-3">
                                    <label class="form-label form-label-sm">Step No.</label>
                                    <input type="text" class="form-control form-control-sm" id="dimRsStepNo" placeholder="R01">
                                </div>
                                <div class="col">
                                    <label class="form-label form-label-sm">Min (before plating)</label>
                                    <input type="number" step="0.0001" class="form-control form-control-sm" id="dimRsDimMin" placeholder="0.0000">
                                </div>
                                <div class="col">
                                    <label class="form-label form-label-sm">Max (before plating)</label>
                                    <input type="number" step="0.0001" class="form-control form-control-sm" id="dimRsDimMax" placeholder="0.0000">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label form-label-sm">Component (IPL#) <span class="text-secondary fw-normal" style="font-size:10px">— optional</span></label>
                                <input type="text" class="form-control form-control-sm" id="dimRsIpl" placeholder="e.g. 11-14" autocomplete="off">
                                <div id="dimRsCompList" class="list-group mt-1" style="display:none;max-height:120px;overflow-y:auto;font-size:12px"></div>
                                <div class="text-secondary mt-1" style="font-size:11px;min-height:14px" id="dimRsCompInfo"></div>
                                <input type="hidden" id="dimRsComponentId">
                            </div>
                            <div class="text-danger small d-none mb-1" id="dimRsErr"></div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary btn-sm" id="dimRsCancelBtn">Cancel</button>
                                <button type="button" class="btn btn-primary btn-sm" id="dimRsSaveBtn">Save Step</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="dimAddRepairStepBtn" style="font-size:11px">
                            <i class="bi bi-plus-lg"></i> Add Repair Step
                        </button>
                    </div>
                </div>

                {{-- Repair surface: shown only for line-type parameters --}}
                <div id="dimSpecRepairSurfaceSection" class="d-none mb-3">
                    <div class="fw-semibold mb-2" style="font-size:12px;color:var(--bs-secondary-color)">REPAIR SURFACE — Linear Endpoints</div>
                    <div id="dimSpecRepairSurfaceList"></div>
                    <div class="row g-2 mt-1">
                        <div class="col-3">
                            <label class="form-label form-label-sm">Flange clearance B min</label>
                            <input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecFlangeClrMin" placeholder="—">
                        </div>
                        <div class="col-3">
                            <label class="form-label form-label-sm">B max</label>
                            <input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecFlangeClrMax" placeholder="—">
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label form-label-sm">Notes</label>
                        <textarea class="form-control form-control-sm" id="dimSpecInspection" rows="2" placeholder="Additional notes from manual"></textarea>
                    </div>
                    <div class="col-4">
                        <label class="form-label form-label-sm">Sort</label>
                        <input type="number" class="form-control form-control-sm" id="dimSpecSort" value="0">
                    </div>
                </div>
                <div class="text-danger small mt-2 d-none" id="dimSpecError"></div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-outline-danger btn-sm d-none" id="dimSpecDeleteBtn">Delete</button>
                    <button type="button" class="btn btn-outline-warning btn-sm d-none" id="dimSpecDetachBtn">Detach from point</button>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="dimSpecSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Repair Plan (MasterRule — Start / Main / Finish) --}}
<div class="modal fade" id="dimMasterRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dimMrTitle">Repair Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dimMrRuleId">

                {{-- START phase --}}
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex align-items-center mb-2">
                        <span class="fw-semibold" style="font-size:12px">START <span class="text-secondary fw-normal">— before main work</span></span>
                        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto dim-mr-add" data-phase="start" style="font-size:11px"><i class="bi bi-plus-lg"></i> Add rule</button>
                    </div>
                    <div id="dimMrStartList"></div>
                </div>

                {{-- MAIN phase (informational) --}}
                <div class="border rounded p-2 mb-2" style="background:rgba(13,110,253,.04)">
                    <span class="fw-semibold" style="font-size:12px">MAIN <span class="text-secondary fw-normal">— auto-assembled from failed points (point rules)</span></span>
                </div>

                {{-- FINISH phase --}}
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex align-items-center mb-2">
                        <span class="fw-semibold" style="font-size:12px">FINISH <span class="text-secondary fw-normal">— after main work</span></span>
                        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto dim-mr-add" data-phase="finish" style="font-size:11px"><i class="bi bi-plus-lg"></i> Add rule</button>
                    </div>
                    <div id="dimMrFinishList"></div>
                </div>

                {{-- Inline add/edit rule form --}}
                <div id="dimMrForm" class="d-none border rounded p-2 mb-2" style="background:rgba(0,0,0,.04)">
                    <input type="hidden" id="dimMrFormPhase">
                    <input type="hidden" id="dimMrFormEditId">
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Rule name <span class="text-secondary fw-normal" style="font-size:10px">— optional</span></label>
                        <input type="text" class="form-control form-control-sm" id="dimMrName" placeholder="e.g. Stress Relief, Cadmium Plating">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Processes</label>
                        <div id="dimMrProcessList" class="mb-2"></div>
                        <select id="dimMrProcessName" style="width:100%">
                            <option value=""></option>
                        </select>
                        <div id="dimMrProcessOptions" class="mt-2 d-none" style="display:flex;flex-wrap:wrap;gap:4px"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Condition <span class="text-secondary fw-normal" style="font-size:10px">— when this rule applies</span></label>
                        <select class="form-select form-select-sm" id="dimMrCondType">
                            <option value="always">Always</option>
                            <option value="has_defect">Only if defect present</option>
                            <option value="has_main_process">Only if Main has process</option>
                            <option value="any_point_fail">Only if any point is repaired</option>
                        </select>
                        <div class="mt-2 d-none" id="dimMrCondDefectWrap">
                            <label class="form-label form-label-sm">Defect(s)</label>
                            <select id="dimMrCondDefects" multiple style="width:100%"></select>
                        </div>
                        <div class="mt-2 d-none" id="dimMrCondProcWrap">
                            <label class="form-label form-label-sm">Main process(es)</label>
                            <select id="dimMrCondProcs" multiple style="width:100%"></select>
                        </div>
                    </div>
                    <div class="text-danger small d-none mb-1" id="dimMrErr"></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" id="dimMrCancelBtn">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm" id="dimMrSaveBtn">Save rule</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Process Documents editor (full-screen) — documents -> pages -> elements --}}
<style>
    #pdw-canvas { flex:1 1 auto; overflow:hidden; position:relative; background:rgba(0,0,0,.05); cursor:grab; }
    #pdw-canvas.grabbing { cursor:grabbing; }
    #pdw-canvas.add-mode { cursor:crosshair; }
    #pdw-img-container { position:absolute; transform-origin:0 0; user-select:none; }
    #pdw-img { display:block; }
    #pdw-overlay { position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; }
    #pdw-svg { position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; overflow:visible; z-index:4; }
    .pdw-dim-label { position:absolute; transform:translate(-50%,-50%); background:#fff; border:1.5px solid #0d6efd; border-radius:3px;
        font-size:12px; font-weight:700; color:#0d6efd; padding:1px 6px; white-space:nowrap; cursor:pointer; z-index:10; pointer-events:all; box-shadow:0 1px 3px rgba(0,0,0,.3); }
    .pdw-dim-label:hover { box-shadow:0 0 0 2px rgba(13,110,253,.35); }
    .pdw-text-label { position:absolute; transform:translate(-50%,-50%); background:rgba(20,184,166,.12); border:1.5px solid #14b8a6;
        border-radius:8px; padding:2px 8px; font-size:12px; font-weight:600; color:#0d9488; white-space:nowrap; cursor:pointer; z-index:10; pointer-events:all; }
    .pdw-anchor-dot { position:absolute; width:9px; height:9px; transform:translate(-50%,-50%); background:#14b8a6; border:1.5px solid #fff;
        border-radius:50%; box-shadow:0 0 0 1px #14b8a6; cursor:pointer; z-index:11; pointer-events:all; }
    .pdw-doc-row { display:flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid var(--bs-border-color); border-radius:6px; margin-bottom:6px; cursor:pointer; }
    .pdw-doc-row:hover { background:rgba(13,110,253,.05); border-color:#0d6efd; }
    .pdw-doc-type { font-size:10px; padding:1px 6px; border-radius:3px; background:rgba(13,110,253,.12); color:#0d6efd; flex-shrink:0; text-transform:uppercase; }
    .pdw-page-tab { font-size:11px; padding:2px 8px; border:1px solid var(--bs-border-color); border-radius:3px; cursor:pointer; background:transparent; }
    .pdw-page-tab.active { background:rgba(13,110,253,.15); border-color:#0d6efd; color:#0d6efd; font-weight:600; }
    .pdw-tree-proc:hover { background:rgba(13,110,253,.10); }
    .pdw-tree-proc.active { background:rgba(13,110,253,.20); box-shadow:inset 3px 0 0 #0d6efd; font-weight:600; }
</style>
<div id="pdw-host" style="display:none">
            <div class="d-flex align-items-center px-3 py-2 border-bottom" style="flex-shrink:0;gap:.5rem">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="pdwCloseBtn"><i class="bi bi-arrow-left"></i> Dimensions</button>
                <h6 class="mb-0" id="pdwTitle">Process Documents</h6>
            </div>
            <div class="pdw-body p-0">

                {{-- LEFT column: Part → Point → Rule → Process tree (always visible) --}}
                <div id="pdw-tree-screen" class="p-3" style="overflow-y:auto;height:100%">
                    <div class="mb-2">
                        <div class="fw-semibold" style="font-size:12px">Point → Rule → Process</div>
                        <div class="text-secondary" style="font-size:11px">click a process to manage its drawing(s)</div>
                    </div>
                    <div id="pdw-tree"></div>
                </div>

                {{-- RIGHT column: placeholder when nothing selected --}}
                <div id="pdw-right-empty" class="d-flex align-items-center justify-content-center text-secondary p-3" style="height:100%;font-size:13px">
                    <div class="text-center"><i class="bi bi-arrow-left-circle" style="font-size:1.6rem;opacity:.4;display:block;margin-bottom:.4rem"></i>Select a process on the left</div>
                </div>

                {{-- Screen A: document list --}}
                <div id="pdw-doc-screen" class="p-3 d-none" style="overflow-y:auto;height:100%">
                    <div class="d-flex align-items-center mb-3">
                        <button class="btn btn-outline-secondary btn-sm me-2 d-none" id="pdwTreeBackBtn"><i class="bi bi-arrow-left"></i> Tree</button>
                        <span class="fw-semibold" id="pdwDocScreenTitle">Documents</span>
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <button class="btn btn-outline-secondary btn-sm" id="pdwAttachDocBtn" title="Attach a copy of an existing document (same scan, own labels)"><i class="bi bi-link-45deg"></i> Attach existing</button>
                            <button class="btn btn-outline-primary btn-sm" id="pdwAddPdfBtn" title="New document from a PDF — every PDF page becomes a document page"><i class="bi bi-file-earmark-pdf"></i> Add from PDF</button>
                            <input type="file" id="pdwAddPdfInput" accept="application/pdf" class="d-none">
                            <button class="btn btn-outline-primary btn-sm" id="pdwAddDocBtn"><i class="bi bi-plus-lg"></i> Add document</button>
                        </div>
                    </div>
                    {{-- attach-existing picker --}}
                    <div id="pdw-attach-wrap" class="d-none border rounded p-2 mb-3" style="background:rgba(0,0,0,.04)">
                        <div class="d-flex gap-2 align-items-center">
                            <select id="pdwAttachSelect" class="form-select form-select-sm" style="font-size:12px"></select>
                            <button class="btn btn-primary btn-sm" id="pdwAttachConfirm" style="white-space:nowrap">Attach</button>
                            <button class="btn btn-secondary btn-sm" id="pdwAttachCancel">Cancel</button>
                        </div>
                        <div class="text-secondary mt-1" style="font-size:11px">The scan is shared; labels and dimensions are copied and editable independently for this process.</div>
                    </div>
                    {{-- inline add/edit document form --}}
                    <div id="pdw-doc-form" class="d-none border rounded p-2 mb-3" style="background:rgba(0,0,0,.04)">
                        <input type="hidden" id="pdwDocEditId">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <label class="form-label form-label-sm">Type</label>
                                <input id="pdwDocType" class="form-control form-control-sm" list="pdwDocTypeList"
                                       placeholder="drawing" style="min-width:130px">
                                <datalist id="pdwDocTypeList">
                                    <option value="drawing">Drawing</option>
                                    <option value="manual_page">Manual page</option>
                                    <option value="test_report">Test report</option>
                                </datalist>
                            </div>
                            <div class="col">
                                <label class="form-label form-label-sm">Title</label>
                                <input type="text" id="pdwDocTitle" class="form-control form-control-sm" placeholder="e.g. Repair sketch">
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-secondary btn-sm" id="pdwDocCancelBtn">Cancel</button>
                                <button class="btn btn-primary btn-sm" id="pdwDocSaveBtn">Save</button>
                            </div>
                        </div>
                    </div>
                    <div id="pdw-doc-list"></div>
                </div>

                {{-- Screen B: page editor --}}
                <div id="pdw-editor-screen" class="d-none flex-column" style="height:100%">
                    <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom flex-wrap" style="flex-shrink:0">
                        <button class="btn btn-outline-secondary btn-sm" id="pdwBackBtn"><i class="bi bi-arrow-left"></i> Documents</button>
                        <span class="fw-semibold" id="pdwEditorTitle" style="font-size:13px"></span>
                        <span class="border-start ps-2 ms-1"></span>
                        {{-- page navigator --}}
                        <span class="text-secondary" style="font-size:11px">Page:</span>
                        <div id="pdw-page-tabs" class="d-flex gap-1 align-items-center"></div>
                        <button class="btn btn-outline-secondary btn-sm py-0 px-2" id="pdwAddPageBtn" title="Add page"><i class="bi bi-plus-lg"></i> Page</button>
                        <button class="btn btn-outline-danger btn-sm py-0 px-2 d-none" id="pdwDelPageBtn" title="Remove current page"><i class="bi bi-trash3"></i></button>
                        <span class="border-start ps-2 ms-1"></span>
                        {{-- tools --}}
                        <button class="btn btn-outline-secondary btn-sm" id="pdwUploadBtn" title="Image for this page, or a PDF — its pages become document pages"><i class="bi bi-upload"></i> Image / PDF</button>
                        <input type="file" id="pdwFileInput" accept="image/png,image/jpeg,image/webp,image/gif,application/pdf" class="d-none">
                        <button class="btn btn-outline-secondary btn-sm pdw-mode-btn" data-mode="diameter" title="Diameter Ø (start → end → value)"><i class="bi bi-circle"></i> Ø</button>
                        <button class="btn btn-outline-secondary btn-sm pdw-mode-btn" data-mode="radius" title="Radius R (start → end → value)"><i class="bi bi-record-circle"></i> R</button>
                        <button class="btn btn-outline-secondary btn-sm pdw-mode-btn" data-mode="linear" title="Linear (start → end → value)"><i class="bi bi-rulers"></i> Linear</button>
                        <button class="btn btn-outline-secondary btn-sm pdw-mode-btn" data-mode="value" title="Value mark — one click, pick the parameter (no arrow)"><i class="bi bi-123"></i> Value</button>
                        <button class="btn btn-outline-secondary btn-sm pdw-mode-btn" data-mode="label" title="Label (anchor + leader)"><i class="bi bi-tag"></i> Label</button>
                        <button class="btn btn-outline-secondary btn-sm pdw-mode-btn" data-mode="steps" title="Oversize steps table (click to place)"><i class="bi bi-table"></i> Steps</button>
                        <span class="text-secondary ms-auto" id="pdwHint" style="font-size:11px"></span>
                        <button class="btn btn-outline-secondary btn-sm py-0 px-2" id="pdwZoomReset" title="Reset zoom">↺</button>
                    </div>
                    <div id="pdw-elem-form" class="d-none px-3 py-2 border-bottom d-flex gap-2 align-items-center flex-wrap" style="background:rgba(13,110,253,.06);flex-shrink:0">
                        {{-- dimension fields --}}
                        <div id="pdw-ef-dim" class="d-none d-flex gap-2 align-items-center flex-wrap">
                            <span style="font-size:12px;font-weight:600">Value:</span>
                            <select id="pdw-ef-source" class="form-select form-select-sm" style="width:auto;font-size:12px">
                                <option value="static">Static value</option>
                                <option value="measurement">From measurement (WO)</option>
                                <option value="calc">Calc from mating (F&amp;C)</option>
                                <option value="formula">Formula</option>
                                <option value="torque">Torque (WO input)</option>
                            </select>
                            <input id="pdw-ef-static" type="number" step="0.0001" class="form-control form-control-sm" style="width:120px;font-size:12px" placeholder="0.0000">
                            <select id="pdw-ef-param" class="form-select form-select-sm d-none" style="width:auto;font-size:12px"></select>
                            {{-- formula fields --}}
                            <div id="pdw-ef-formula" class="d-none d-flex gap-2 align-items-center flex-wrap w-100 mt-1">
                                <div class="d-flex gap-1 align-items-center flex-grow-1">
                                    <input id="pdw-ef-fexpr" type="text" class="form-control form-control-sm font-monospace"
                                           style="font-size:12px;min-width:220px" placeholder="e.g. 0.7128 - [p:45]">
                                    <button type="button" id="pdw-ef-fpick" class="btn btn-outline-secondary btn-sm" style="font-size:11px;white-space:nowrap">+ param</button>
                                </div>
                                <div class="d-flex gap-1 align-items-center">
                                    <span style="font-size:12px;color:#198754">+</span>
                                    <input id="pdw-ef-ftol-plus" type="number" step="0.0001" min="0" class="form-control form-control-sm"
                                           style="width:90px;font-size:12px" placeholder="0.0000">
                                    <span style="font-size:12px;color:#dc3545">−</span>
                                    <input id="pdw-ef-ftol-minus" type="number" step="0.0001" min="0" class="form-control form-control-sm"
                                           style="width:90px;font-size:12px" placeholder="0.0000">
                                </div>
                            </div>
                            {{-- param picker for formula (hidden popover) --}}
                            <select id="pdw-ef-fparam-pick" class="form-select form-select-sm d-none" style="width:auto;font-size:12px"></select>
                        </div>
                        {{-- steps table fields --}}
                        <div id="pdw-ef-steps" class="d-none d-flex gap-2 align-items-center flex-wrap">
                            <span style="font-size:12px;font-weight:600">Steps of:</span>
                            <select id="pdw-ef-steps-param" class="form-select form-select-sm" style="width:auto;font-size:12px"></select>
                            <span class="text-secondary" style="font-size:11px">required step is highlighted on the WO render</span>
                        </div>
                        {{-- label fields --}}
                        <div id="pdw-ef-lbl" class="d-none d-flex gap-2 align-items-center flex-wrap">
                            <span style="font-size:12px;font-weight:600">Label:</span>
                            <select id="pdw-ef-lbltype" class="form-select form-select-sm" style="width:auto;font-size:12px">
                                <option value="text">Free text</option>
                                <option value="placeholder">Placeholder (WO data)</option>
                                <option value="parameter">Parameter (point · dim)</option>
                            </select>
                            <div id="pdw-ef-text-wrap" class="d-flex gap-1 align-items-center">
                                <input id="pdw-ef-text" type="text" class="form-control form-control-sm font-monospace" style="width:220px;font-size:12px" placeholder="text or {manual_number} …">
                                <button type="button" id="pdw-ef-tpick" class="btn btn-outline-secondary btn-sm" style="font-size:11px;white-space:nowrap">+ token</button>
                                <select id="pdw-ef-token-pick" class="form-select form-select-sm d-none" style="width:auto;font-size:12px"></select>
                            </div>
                            <select id="pdw-ef-placeholder" class="form-select form-select-sm d-none" style="width:auto;font-size:12px"></select>
                            <select id="pdw-ef-lblparam" class="form-select form-select-sm d-none" style="width:auto;font-size:12px"></select>
                        </div>
                        <span style="font-size:12px;font-weight:600" class="ms-2">Size:</span>
                        <input id="pdw-ef-fontsize" type="number" min="5" max="72" class="form-control form-control-sm" style="width:62px;font-size:12px" placeholder="pt" title="Font size (pt) — blank = default">
                        <button id="pdw-ef-save" class="btn btn-primary btn-sm ms-2" style="font-size:12px">Add</button>
                        <button id="pdw-ef-cancel" class="btn btn-secondary btn-sm" style="font-size:12px">Cancel</button>
                        <button id="pdw-ef-delete" class="btn btn-outline-danger btn-sm d-none ms-auto" style="font-size:12px"><i class="bi bi-trash3"></i> Delete</button>
                    </div>
                    <div id="pdw-canvas">
                        <div id="pdw-empty" class="d-flex align-items-center justify-content-center h-100 text-secondary" style="font-size:13px">
                            <div class="text-center"><i class="bi bi-image" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.4rem"></i>Upload an image for this page</div>
                        </div>
                        <div id="pdw-img-container" class="d-none">
                            <img id="pdw-img" src="" alt="">
                            <svg id="pdw-svg"></svg>
                            <div id="pdw-overlay"></div>
                        </div>
                    </div>
                </div>

            </div>
</div>

{{-- Authoring JS lives in public/js/manuals/dimensions-tab.js; this inline
     block only hands the blade-side values over (window.DIM_CFG). --}}
@php
    $manualComponentsForJs = $cmm->components->sortBy('ipl_num')->map(fn($c) => [
        'id' => $c->id, 'ipl_num' => $c->ipl_num, 'name' => $c->name, 'part_number' => $c->part_number,
    ])->values();
@endphp
<script>
window.DIM_CFG = {
    manualId:         @json($manualId),
    csrf:             @json($csrfToken),
    processes:        @json($dimManualProcesses),
    codes:            @json($codes),
    figures:          @json($dimensionFigures),
    manualComponents: @json($manualComponentsForJs),
};
</script>
<script src="{{ asset('js/manuals/dimensions-tab.js') }}?v={{ @filemtime(public_path('js/manuals/dimensions-tab.js')) }}"></script>
