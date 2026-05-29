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
                <button class="btn btn-outline-secondary btn-sm dim-mode-btn" id="dimAddTextModeBtn" title="Add part label: 1st click = dot on part, 2nd click = label position">
                    <i class="bi bi-tag"></i> Add Label
                </button>
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
                    <label class="form-label form-label-sm">Rule name <span class="text-secondary" style="font-weight:400">(e.g. Rechrome, Replace bushing)</span></label>
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
                                    <input class="form-check-input" type="radio" name="dimRuleAction" id="dimRuleActionReplace" value="replace">
                                    <label class="form-check-label" for="dimRuleActionReplace" style="font-size:13px">Order new</label>
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
                                <label class="form-label form-label-sm">Component (IPL#)</label>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const MANUAL_ID       = @json($manualId);
    const CSRF            = @json($csrfToken);
    const DIM_PROCESSES   = @json($dimManualProcesses);
    const DIM_PROCESSES_BY_NAME = (function () {
        const map = {};
        DIM_PROCESSES.forEach(function (p) {
            const name = p.process_name || '';
            if (!map[name]) map[name] = [];
            map[name].push(p);
        });
        return map;
    })();
    let figures           = @json($dimensionFigures);
    let activeFigure   = null;
    let activePoint    = null;
    let addPointMode   = false;
    let addAreaMode    = false;
    let areaDragStart  = null;
    let areaPreviewEl  = null;
    let addLineMode      = false;
    let lineStart        = null;
    let lineTempMarker   = null;
    let lineTempLine     = null;
    let addCalloutMode   = false;
    let calloutDotStart  = null;
    let calloutTempDot   = null;
    let calloutTempLine  = null;
    let addTextMode      = false;
    let textDotStart     = null;
    let textTempDot      = null;
    let textTempLine     = null;
    let addCircleMode      = false;
    let circleCenter       = null;
    let circleTempEl       = null;
    let areaWaitingLabel   = null;  // {x,y,w,h} after rect drawn, waiting for label click
    let areaTempRect       = null;  // persistent div showing drawn rect while waiting
    let circleWaitingLabel = null;  // {cx,cy,rx,ry} after circle drawn, waiting for label click
    let lineWaitingLabel   = null;  // {x1,y1,x2,y2} after line drawn, waiting for label click
    let waitingTempLine    = null;  // dashed SVG line following cursor while waiting
    let justDragged        = false;
    let autoNavCooldown    = false;
    let isNavigating     = false;
    let zoomFactor       = 1;
    let zoomBaseWidth    = null;
    let isPanning        = false;
    let panStart         = null;

    // ---- DOM refs ----
    const figuresList       = document.getElementById('dim-figures-list');
    const backToParentBtn   = document.getElementById('dimBackToParentBtn');
    const viewerTitle       = document.getElementById('dim-viewer-title');
    const viewerActions     = document.getElementById('dim-viewer-actions');
    const canvasWrap        = document.getElementById('dim-figure-canvas-wrap');
    const imgContainer      = document.getElementById('dim-figure-img-container');
    const figureImg         = document.getElementById('dim-figure-img');
    const pointsOverlay     = document.getElementById('dim-points-overlay');
    const emptyState        = document.getElementById('dim-empty-state');
    const specsHeader       = document.getElementById('dim-specs-point-label');
    const specsEmpty        = document.getElementById('dim-specs-empty');
    const specsList         = document.getElementById('dim-specs-list');
    const specsFooter       = document.getElementById('dim-specs-footer');
    const editPointBtn      = document.getElementById('dimEditPointBtn');
    const zoomLabel         = document.getElementById('dimZoomLabel');
    const zoomResetBtn      = document.getElementById('dimZoomResetBtn');
    const addPointModeBtn   = document.getElementById('dimAddPointModeBtn');
    const addCalloutModeBtn = document.getElementById('dimAddCalloutModeBtn');
    const addCircleModeBtn  = document.getElementById('dimAddCircleModeBtn');
    const addAreaModeBtn    = document.getElementById('dimAddAreaModeBtn');
    const addLineModeBtn    = document.getElementById('dimAddLineModeBtn');
    const addTextModeBtn    = document.getElementById('dimAddTextModeBtn');
    const linesSvg          = document.getElementById('dim-lines-svg');

    // ==========================
    // Inspection Components
    // ==========================

    @php
        $manualComponentsForJs = $cmm->components->sortBy('ipl_num')->map(fn($c) => [
            'id' => $c->id, 'ipl_num' => $c->ipl_num, 'name' => $c->name, 'part_number' => $c->part_number,
        ])->values();
    @endphp
    const MANUAL_COMPONENTS = @json($manualComponentsForJs);

    let inspComponents = [];
    let parameters     = [];
    let expandedIcId   = null;

    function compOptionLabel(c) {
        return (c.ipl_num ? c.ipl_num + ' — ' : '') + (c.name || c.part_number || '');
    }

    function renderInspComponents() {
        const list = document.getElementById('dim-insp-comp-list');
        if (inspComponents.length === 0) {
            list.innerHTML = '<div class="px-2 py-1 text-secondary" style="font-size:10px">No components yet</div>';
            return;
        }
        list.innerHTML = inspComponents.map(function (ic) {
            const isOpen   = expandedIcId === ic.id;
            const varHtml  = isOpen ? ic.variants.map(function (v) {
                const lbl = (v.ipl_num ? v.ipl_num + ' — ' : '') + (v.name || v.part_number || '');
                return `<div class="d-flex align-items-center gap-1 ps-4" style="font-size:10px;padding:1px 8px 1px 28px">
                    <span class="flex-grow-1 text-secondary">${escHtml(lbl)}</span>
                    <button class="btn btn-link p-0 dim-ic-var-del" data-var-id="${v.id}" style="font-size:10px;opacity:.45;color:var(--bs-secondary-color)" title="Remove variant"><i class="bi bi-x"></i></button>
                </div>`;
            }).join('') : '';
            const addVarHtml = isOpen
                ? `<div class="ps-4 pb-1" style="padding-left:28px!important">
                    <select class="form-select form-select-sm dim-ic-var-add" data-ic-id="${ic.id}">
                        <option value=""></option>
                        ${MANUAL_COMPONENTS.filter(function(c){ return !ic.variants.find(function(v){ return v.component_id===c.id; }); }).map(function(c){
                            return `<option value="${c.id}">${escHtml(compOptionLabel(c))}</option>`;
                        }).join('')}
                    </select>
                  </div>` : '';
            return `<div class="dim-insp-comp-row" data-ic-id="${ic.id}" draggable="true">
                <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>
                <span class="dim-insp-comp-name fw-semibold flex-grow-1" data-ic-id="${ic.id}" style="color:#5ee3ff;font-size:11px" title="Double-click to rename">${escHtml(ic.label)}</span>
                <button class="btn btn-link p-0 dim-ic-expand" data-ic-id="${ic.id}" style="font-size:10px;color:var(--bs-secondary-color)" title="Variants">
                    <i class="bi bi-${isOpen ? 'chevron-up' : 'chevron-down'}"></i>
                </button>
                <button class="btn btn-link p-0 dim-insp-comp-del" data-ic-id="${ic.id}" style="font-size:10px;opacity:.45;color:var(--bs-secondary-color)" title="Delete"><i class="bi bi-trash3"></i></button>
            </div>
            ${varHtml}${addVarHtml}`;
        }).join('');

        // expand/collapse
        list.querySelectorAll('.dim-ic-expand').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = parseInt(btn.dataset.icId);
                expandedIcId = (expandedIcId === id) ? null : id;
                renderInspComponents();
            });
        });

        // delete inspection component
        list.querySelectorAll('.dim-insp-comp-del').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const id = parseInt(btn.dataset.icId);
                if (!confirm('Delete this inspection component?')) return;
                try {
                    await apiFetch('/inspection-components/' + id, { method: 'DELETE' });
                    inspComponents = inspComponents.filter(function (ic) { return ic.id !== id; });
                    if (expandedIcId === id) expandedIcId = null;
                    renderInspComponents();
                } catch (e) { alert(e.message); }
            });
        });

        // delete variant
        list.querySelectorAll('.dim-ic-var-del').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const varId = parseInt(btn.dataset.varId);
                try {
                    await apiFetch('/inspection-component-variants/' + varId, { method: 'DELETE' });
                    inspComponents.forEach(function (ic) {
                        ic.variants = ic.variants.filter(function (v) { return v.id !== varId; });
                    });
                    expandedIcId = null;
                    renderInspComponents();
                } catch (e) { alert(e.message); }
            });
        });

        // add variant select — Select2
        $('.dim-ic-var-add').each(function () {
            const $sel = $(this);
            $sel.select2({
                theme:       'bootstrap-5',
                placeholder: '+ Add variant...',
                width:       '100%',
            });
            $sel.on('change', async function () {
                const compId = parseInt($sel.val());
                const icId   = parseInt($sel.data('icId'));
                if (!compId) return;
                try {
                    const v = await apiFetch('/inspection-components/' + icId + '/variants', {
                        method: 'POST',
                        body: JSON.stringify({ component_id: compId }),
                    });
                    const ic = inspComponents.find(function (ic) { return ic.id === icId; });
                    if (ic) ic.variants.push(v);
                    expandedIcId = icId;
                    renderInspComponents();
                } catch (e) { alert(e.message); }
            });
        });

        setupInspCompDrag(list);

        // inline rename on double-click
        list.querySelectorAll('.dim-insp-comp-name').forEach(function (span) {
            span.addEventListener('dblclick', function (e) {
                e.stopPropagation();
                const id      = parseInt(span.dataset.icId);
                const ic      = inspComponents.find(function (x) { return x.id === id; });
                if (!ic) return;
                const row     = span.closest('.dim-insp-comp-row');
                const origVal = ic.label;

                const input   = document.createElement('input');
                input.type    = 'text';
                input.value   = origVal;
                input.className = 'form-control form-control-sm py-0 px-1';
                input.style.cssText = 'font-size:11px;height:20px;flex:1 1 0;min-width:0;color:#5ee3ff;background:rgba(255,255,255,.08);border-color:rgba(94,227,255,.4)';
                row.setAttribute('draggable', 'false');
                span.replaceWith(input);
                input.focus();
                input.select();

                async function commit() {
                    const newLabel = input.value.trim();
                    if (!newLabel || newLabel === origVal) {
                        renderInspComponents();
                        return;
                    }
                    try {
                        const updated = await apiFetch('/inspection-components/' + id, {
                            method: 'PATCH',
                            body: JSON.stringify({ label: newLabel }),
                        });
                        ic.label = updated.label;
                    } catch (ex) { alert(ex.message); }
                    renderInspComponents();
                }

                input.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter')  { ev.preventDefault(); input.blur(); }
                    if (ev.key === 'Escape') { input.value = origVal; input.blur(); }
                });
                input.addEventListener('blur', commit, { once: true });
            });
        });
    }

    function setupInspCompDrag(list) {
        let dragSrc = null;
        list.querySelectorAll('.dim-insp-comp-row').forEach(function (row) {
            row.addEventListener('dragstart', function () { dragSrc = row; row.style.opacity = '.4'; });
            row.addEventListener('dragend',   function () { row.style.opacity = ''; });
            row.addEventListener('dragover',  function (e) { e.preventDefault(); });
            row.addEventListener('drop', async function (e) {
                e.preventDefault();
                if (!dragSrc || dragSrc === row) return;
                const rows    = Array.from(list.querySelectorAll('.dim-insp-comp-row'));
                const fromIdx = rows.indexOf(dragSrc);
                const toIdx   = rows.indexOf(row);
                inspComponents.splice(toIdx, 0, inspComponents.splice(fromIdx, 1)[0]);
                renderInspComponents();
                const ids = inspComponents.map(function (ic) { return ic.id; });
                try {
                    await apiFetch('/manuals/' + MANUAL_ID + '/inspection-components/reorder', { method: 'POST', body: JSON.stringify({ ids }) });
                } catch (e) { console.error('Reorder failed', e); }
            });
        });
    }

    function refreshSpecComponentSelect(selectedId) {
        const sel = document.getElementById('dimSpecComponent');
        const current = selectedId !== undefined ? selectedId : $('#dimSpecComponent').val();
        sel.innerHTML = '<option value="">— None —</option>';
        inspComponents.forEach(function (ic) {
            const opt = document.createElement('option');
            opt.value = ic.id;
            opt.textContent = ic.label;
            sel.appendChild(opt);
        });
        const valToSet = (current !== undefined && current !== null && String(current) !== '') ? String(current) : null;
        $('#dimSpecComponent').val(valToSet).trigger('change.select2');
    }

    function parametersForPoint(pt) {
        return parameters.filter(function (p) {
            return (p.point_ids || []).indexOf(pt.id) !== -1;
        });
    }

    function getParamsForComponent(inspCompId) {
        return parameters.filter(function (p) {
            return String(p.inspection_component_id) === String(inspCompId);
        });
    }

    function autoFillParamFromExisting() {
        const specId = document.getElementById('dimSpecId').value;
        if (specId) return; // editing existing — don't auto-fill
        const inspCompId = $('#dimSpecComponent').val();
        const desc       = document.getElementById('dimSpecDescription').value.trim().toLowerCase();
        if (!desc) return;
        const match = parameters.find(function (p) {
            const sameDesc = (p.description || '').trim().toLowerCase() === desc;
            const sameComp = inspCompId
                ? String(p.inspection_component_id) === String(inspCompId)
                : p.inspection_component_id == null;
            return sameDesc && sameComp;
        });
        if (!match) { document.getElementById('dimSpecId').value = ''; return; }
        // Pre-fill from existing parameter
        document.getElementById('dimSpecId').value          = match.id;
        document.getElementById('dimSpecRequired').checked  = !!match.is_required;
        document.getElementById('dimSpecOrigMin').value     = match.orig_dim_min  || '';
        document.getElementById('dimSpecOrigMax').value     = match.orig_dim_max  || '';
        document.getElementById('dimSpecWearMin').value     = match.wear_dim_min  || '';
        document.getElementById('dimSpecWearMax').value     = match.wear_dim_max  || '';
        document.getElementById('dimSpecInspection').value  = match.inspection    || '';
        document.getElementById('dimSpecSort').value        = match.sort_order    || 0;
        dimSpecCodes = (match.codes || []).map(function (c) { return { id: c.codes_id, name: c.name || '' }; });
        renderSpecCodesList();
        // Show hint in modal title
        document.getElementById('dimSpecModalTitle').textContent = 'Assign existing: ' + match.description;
        document.getElementById('dimSpecDeleteBtn').classList.add('d-none');
        document.getElementById('dimSpecDetachBtn').classList.add('d-none');
    }

    document.getElementById('dimSpecDescription').addEventListener('input', autoFillParamFromExisting);

    async function loadInspComponents() {
        try {
            inspComponents = await apiFetch('/manuals/' + MANUAL_ID + '/inspection-components');
            renderInspComponents();
            refreshSpecComponentSelect();
        } catch (e) { console.error('loadInspComponents', e); }
    }

    async function loadParameters() {
        try {
            const data = await apiFetch('/manuals/' + MANUAL_ID + '/parameters');
            parameters = data;
            if (activePoint) renderSpecsPanel(activePoint);
        } catch (e) { console.error('loadParameters', e); }
    }

    document.getElementById('dimAddInspCompBtn').addEventListener('click', function () {
        const label = prompt('Component label (e.g. "Main Fitting LH", "Bushing"):');
        if (!label || !label.trim()) return;
        apiFetch('/manuals/' + MANUAL_ID + '/inspection-components', {
            method: 'POST',
            body: JSON.stringify({ label: label.trim() }),
        }).then(function (ic) {
            inspComponents.push(ic);
            renderInspComponents();
        }).catch(function (e) { alert(e.message); });
    });

    // ---- Modals ----
    const figureModal = new bootstrap.Modal(document.getElementById('dimFigureModal'));
    const pointModal  = new bootstrap.Modal(document.getElementById('dimPointModal'));
    const specModal   = new bootstrap.Modal(document.getElementById('dimSpecModal'));

    // ---- Select2 for Part picker ----
    $('#dimSpecComponent').select2({
        theme:          'bootstrap-5',
        dropdownParent: $('#dimSpecModal'),
        placeholder:    '— None —',
        allowClear:     true,
        width:          '100%',
    });

    // ---- Select2 for IC picker in point modal ----
    $('#dimPointIcSelect').select2({
        theme:          'bootstrap-5',
        dropdownParent: $('#dimPointModal'),
        placeholder:    '— Select part —',
        allowClear:     true,
        width:          '100%',
    });

    // ---- Select2 for Process Name in repair rule modal ----
    (function () {
        const sel = document.getElementById('dimRuleProcessName');
        Object.keys(DIM_PROCESSES_BY_NAME).sort().forEach(function (name) {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            sel.appendChild(opt);
        });
    })();

    $('#dimRuleProcessName').select2({
        theme:          'bootstrap-5',
        dropdownParent: $('#dimRepairRuleModal'),
        placeholder:    'Process name...',
        allowClear:     true,
        width:          '100%',
    });

    $('#dimRuleProcessName').on('change', function () {
        const name    = this.value;
        const wrap    = document.getElementById('dimRuleProcessOptions');
        if (!name) {
            wrap.classList.add('d-none');
            wrap.innerHTML = '';
            return;
        }
        const procs   = DIM_PROCESSES_BY_NAME[name] || [];
        const prefix  = name + ' — ';
        wrap.innerHTML = procs.map(function (p) {
            const shortLabel = p.label.startsWith(prefix) ? p.label.slice(prefix.length) : p.label;
            return `<button type="button" class="btn btn-outline-secondary btn-sm dim-proc-opt-btn" data-id="${p.id}" data-label="${escHtml(p.label)}" style="font-size:12px">${escHtml(shortLabel)}</button>`;
        }).join('');
        wrap.classList.remove('d-none');
        updateProcOptButtons();
    });

    function updateProcOptButtons() {
        const wrap = document.getElementById('dimRuleProcessOptions');
        if (!wrap) return;
        wrap.querySelectorAll('.dim-proc-opt-btn').forEach(function (btn) {
            const id      = parseInt(btn.dataset.id);
            const already = dimRuleProcesses.some(function (p) { return p.manual_process_id === id; });
            btn.classList.toggle('active', already);
            btn.onclick = function () {
                const idx = dimRuleProcesses.findIndex(function (p) { return p.manual_process_id === id; });
                if (idx !== -1) {
                    dimRuleProcesses.splice(idx, 1);
                } else {
                    dimRuleProcesses.push({ manual_process_id: id, label: btn.dataset.label });
                }
                renderRuleProcessList();
                updateProcOptButtons();
            };
        });
    }

    $('#dimSpecComponent').on('change', autoFillParamFromExisting);

    // ---- Spec defect codes list ----
    let dimSpecCodes = []; // [{id, name, finding_context}]

    function renderSpecCodesList() {
        const wrap = document.getElementById('dimSpecCodesList');
        if (!wrap) return;
        if (dimSpecCodes.length === 0) {
            wrap.innerHTML = '<div class="text-secondary" style="font-size:11px">No defect codes added</div>';
            return;
        }
        wrap.innerHTML = dimSpecCodes.map(function (c, i) {
            const isMeas = c.finding_context === 'measurement';
            const ctxBadge = isMeas
                ? '<span class="badge bg-warning text-dark ms-1" style="font-size:9px">M</span>'
                : '<span class="badge bg-info text-dark ms-1" style="font-size:9px">I</span>';
            return `<span class="badge text-bg-secondary me-1 mb-1" style="font-size:11px;font-weight:500">
                ${escHtml(c.name)}${ctxBadge}
                <button type="button" class="btn-close btn-close-white ms-1 dim-spec-code-remove" data-idx="${i}" style="font-size:8px;vertical-align:middle"></button>
            </span>`;
        }).join('');
        wrap.querySelectorAll('.dim-spec-code-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                dimSpecCodes.splice(parseInt(btn.dataset.idx), 1);
                renderSpecCodesList();
            });
        });
    }

    document.getElementById('dimSpecCodesAdd').addEventListener('change', function () {
        const val     = parseInt(this.value);
        const name    = this.options[this.selectedIndex]?.dataset.name || '';
        const context = document.getElementById('dimSpecCodeContext')?.value || 'inspection';
        this.value = '';
        if (!val || dimSpecCodes.find(function (c) { return c.id === val; })) return;
        dimSpecCodes.push({ id: val, name: name, finding_context: context });
        renderSpecCodesList();
    });

    // ---- Helpers ----
    function csrf() { return CSRF; }
    function fmtDim(v) { return v !== null && v !== undefined ? parseFloat(v).toFixed(4) : '—'; }

    // ---- Zoom ----
    function applyZoom(pivotClientX, pivotClientY) {
        if (!zoomBaseWidth) {
            // Release portrait height constraint and pin container at canvas width
            // before reading BoundingClientRect — avoids dimension mismatch on first zoom.
            figureImg.style.maxHeight = '';
            figureImg.style.width     = '100%';
            figureImg.style.maxWidth  = 'none';
            zoomBaseWidth = canvasWrap.clientWidth;
            imgContainer.style.width  = zoomBaseWidth + 'px';
            void imgContainer.offsetWidth; // force reflow so BoundingClientRect is correct below
        }
        const newWidth = Math.round(zoomBaseWidth * zoomFactor);

        // Position of pivot inside imgContainer before resize (fraction)
        const cRect  = imgContainer.getBoundingClientRect();
        const fracX  = pivotClientX !== undefined ? (pivotClientX - cRect.left)  / cRect.width  : 0.5;
        const fracY  = pivotClientY !== undefined ? (pivotClientY - cRect.top)   / cRect.height : 0.5;

        // Apply new width
        if (zoomFactor === 1) {
            imgContainer.style.width    = '';
            figureImg.style.width       = '';
            figureImg.style.maxWidth    = '';
            figureImg.style.maxHeight   = canvasWrap.clientHeight + 'px';
        } else {
            imgContainer.style.width    = newWidth + 'px';
            figureImg.style.width       = '100%';
            figureImg.style.maxWidth    = 'none';
            figureImg.style.maxHeight   = '';
        }

        // Adjust scroll so pivot point stays under cursor
        const newCRect = imgContainer.getBoundingClientRect();
        const newHeight = newCRect.height || (newWidth / (zoomBaseWidth || newWidth) * imgContainer.offsetHeight);
        const wrapRect  = canvasWrap.getBoundingClientRect();
        const mouseInWrapX = pivotClientX !== undefined ? pivotClientX - wrapRect.left : wrapRect.width  / 2;
        const mouseInWrapY = pivotClientY !== undefined ? pivotClientY - wrapRect.top  : wrapRect.height / 2;
        canvasWrap.scrollLeft = newWidth  * fracX - mouseInWrapX;
        canvasWrap.scrollTop  = newCRect.height * fracY - mouseInWrapY;

        zoomLabel.textContent = Math.round(zoomFactor * 100) + '%';
    }

    // Auto-navigate when an area/circle fills most of the visible viewport
    function checkAutoNavigate() {
        if (!activeFigure || autoNavCooldown) return;
        requestAnimationFrame(function () {
            if (!activeFigure || autoNavCooldown) return;
            const wrapW   = canvasWrap.clientWidth;
            const wrapH   = canvasWrap.clientHeight;
            const imgW    = figureImg.offsetWidth;
            const imgH    = figureImg.offsetHeight;
            const scrollL = canvasWrap.scrollLeft;
            const scrollT = canvasWrap.scrollTop;
            const pts = activeFigure.points || [];
            for (let i = 0; i < pts.length; i++) {
                const pt = pts[i];
                if (!pt.child_figure_id) continue;
                let areaW, areaH, cxPx, cyPx;
                let aLeft, aTop;
                if (pt.point_type === 'circle' && pt.width_pct) {
                    areaW = 2 * parseFloat(pt.width_pct)  / 100 * imgW;
                    areaH = 2 * parseFloat(pt.height_pct) / 100 * imgH;
                    aLeft = parseFloat(pt.x_pct) / 100 * imgW - areaW / 2;
                    aTop  = parseFloat(pt.y_pct) / 100 * imgH - areaH / 2;
                } else if (pt.point_type === 'navigation' && pt.width_pct) {
                    areaW = parseFloat(pt.width_pct)  / 100 * imgW;
                    areaH = parseFloat(pt.height_pct) / 100 * imgH;
                    aLeft = parseFloat(pt.x_pct) / 100 * imgW;
                    aTop  = parseFloat(pt.y_pct) / 100 * imgH;
                } else continue;

                // Area must overlap the visible viewport
                if (aLeft + areaW < scrollL || aLeft > scrollL + wrapW) continue;
                if (aTop  + areaH < scrollT || aTop  > scrollT + wrapH) continue;

                const fillW = areaW / wrapW;
                const fillH = areaH / wrapH;
                if (fillW > 0.4 || fillH > 0.4) {
                    const child = figures.find(function (f) { return f.id == pt.child_figure_id; });
                    if (child) {
                        autoNavCooldown = true;
                        setTimeout(function () { autoNavCooldown = false; }, 2000);
                        selectFigure(child);
                        return;
                    }
                }
            }
        });
    }

    canvasWrap.addEventListener('wheel', function (e) {
        if (!activeFigure || isNavigating) return;
        e.preventDefault();
        const step = e.deltaY < 0 ? 1.1 : (1 / 1.1);
        zoomFactor = Math.min(Math.max(zoomFactor * step, 0.5), 5);
        applyZoom(e.clientX, e.clientY);
        checkAutoNavigate();
        // Auto-navigate to parent when zooming out past minimum
        if (e.deltaY > 0 && zoomFactor <= 0.5 && !autoNavCooldown) {
            const parentId = backToParentBtn.dataset.parentId;
            if (parentId) {
                const parent = figures.find(function (f) { return f.id == parentId; });
                if (parent) {
                    autoNavCooldown = true;
                    setTimeout(function () { autoNavCooldown = false; }, 2000);
                    selectFigure(parent);
                }
            }
        }
    }, { passive: false });

    function resetZoom() {
        zoomFactor = 1;
        imgContainer.style.width  = '';
        figureImg.style.width     = '';
        figureImg.style.maxWidth  = '';
        figureImg.style.maxHeight = canvasWrap.clientHeight + 'px';
        zoomLabel.textContent     = '100%';
        zoomBaseWidth = null;
    }

    zoomResetBtn.addEventListener('click', resetZoom);

    backToParentBtn.addEventListener('click', function () {
        const parentId = backToParentBtn.dataset.parentId;
        if (!parentId) return;
        const parent = figures.find(function (f) { return f.id == parentId; });
        if (parent) selectFigure(parent);
    });

    // ---- Pan (drag canvas to scroll when zoomed) ----
    function isPointEl(el) {
        if (!el) return false;
        const cls = el.className;
        if (typeof cls === 'string' &&
            (cls.includes('dim-point-marker') || cls.includes('dim-area-marker') ||
             cls.includes('dim-callout-dot')  || cls.includes('dim-callout-label') ||
             cls.includes('dim-text-label'))) return true;
        if (el.dataset && el.dataset.id) return true;
        if (el.closest && el.closest('[data-id]')) return true;
        return false;
    }

    canvasWrap.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return;
        if (addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode || addTextMode) return;
        if (isPointEl(e.target)) return;
        isPanning = true;
        panStart  = { x: e.clientX + canvasWrap.scrollLeft, y: e.clientY + canvasWrap.scrollTop };
        canvasWrap.classList.add('panning');
        e.preventDefault();
    });

    async function apiFetch(url, options = {}) {
        const res = await fetch(url, {
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) },
            ...options,
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || json.error || 'Request failed');
        return json;
    }

    // ---- Render figures list ----
    function renderFiguresList() {
        figuresList.innerHTML = '';
        figures.forEach(function (fig) {
            const el = document.createElement('div');
            el.className = 'dim-figure-item' + (activeFigure && activeFigure.id === fig.id ? ' active' : '');
            el.dataset.id = fig.id;
            const badge = fig.figure_type === 'overview'
                ? '<span class="badge text-bg-secondary dim-figure-badge">overview</span>'
                : '<span class="badge text-bg-primary dim-figure-badge">detail</span>';
            el.innerHTML = badge + '<span class="text-truncate">' + escHtml(fig.title) + '</span>';
            el.addEventListener('click', function () { selectFigure(fig); });
            figuresList.appendChild(el);
        });
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ---- Select figure ----
    function selectFigure(fig) {
        activeFigure = fig;
        activePoint  = null;
        addPointMode = false;
        addPointModeBtn.classList.remove('active');
        canvasWrap.classList.remove('add-point-mode');
        addAreaMode = false;
        addAreaModeBtn.classList.remove('active');
        canvasWrap.classList.remove('add-area-mode');
        if (areaPreviewEl) { areaPreviewEl.remove(); areaPreviewEl = null; }
        areaDragStart = null;
        resetLineDraw();
        addLineMode = false;
        addLineModeBtn.classList.remove('active');
        canvasWrap.classList.remove('add-line-mode');
        resetTextDraw();
        addTextMode = false;
        addTextModeBtn.classList.remove('active');
        canvasWrap.classList.remove('add-text-mode');

        isNavigating = true;
        resetZoom();
        renderFiguresList();
        viewerTitle.textContent = fig.title;
        viewerActions.classList.remove('d-none');
        const parent = fig.parent_figure_id ? figures.find(function (f) { return f.id == fig.parent_figure_id; }) : null;
        backToParentBtn.classList.toggle('d-none', !parent);
        backToParentBtn.dataset.parentId = parent ? parent.id : '';
        emptyState.classList.add('d-none');
        imgContainer.classList.remove('d-none');
        clearSpecsPanel();

        // Phase 1: scale out current figure
        imgContainer.style.transition = 'opacity 1s ease, transform 1s ease';
        imgContainer.style.opacity    = '0';
        imgContainer.style.transform  = 'scale(0.02)';
        renderPoints([]);

        setTimeout(function () {
            // Phase 2: swap image (no transition during reset)
            imgContainer.style.transition = 'none';
            imgContainer.style.opacity    = '0';
            imgContainer.style.transform  = 'scale(0.02)';
            void imgContainer.offsetWidth; // force reflow

            figureImg.onload = function () {
                // Phase 3: scale in new figure — hard-reset all zoom state first
                zoomFactor    = 1;
                zoomBaseWidth = null;
                imgContainer.style.width = '';
                figureImg.style.width    = '';
                figureImg.style.maxWidth = '';
                figureImg.style.maxHeight = canvasWrap.clientHeight + 'px';
                zoomLabel.textContent = '100%';

                imgContainer.style.transition = 'opacity 1s ease, transform 1s ease';
                requestAnimationFrame(function () {
                    imgContainer.style.opacity   = '1';
                    imgContainer.style.transform = 'scale(1)';
                    renderPoints(fig.points || []);
                    setTimeout(function () { isNavigating = false; }, 1000);
                });
            };
            figureImg.src = fig.image_path;
            figureImg.alt = fig.title;
        }, 1000);
    }

    // ---- Drag to reposition ----
    async function savePointPosition(pt) {
        const body = {
            x_pct:       parseFloat(pt.x_pct),
            y_pct:       parseFloat(pt.y_pct),
            x2_pct:      (pt.x2_pct      !== null && pt.x2_pct      !== undefined) ? parseFloat(pt.x2_pct)      : null,
            y2_pct:      (pt.y2_pct      !== null && pt.y2_pct      !== undefined) ? parseFloat(pt.y2_pct)      : null,
            label_x_pct: (pt.label_x_pct !== null && pt.label_x_pct !== undefined) ? parseFloat(pt.label_x_pct) : null,
            label_y_pct: (pt.label_y_pct !== null && pt.label_y_pct !== undefined) ? parseFloat(pt.label_y_pct) : null,
        };
        try {
            await apiFetch('/dimension-points/' + pt.id, { method: 'PATCH', body: JSON.stringify(body) });
        } catch (e) { alert('Save position failed: ' + e.message); }
    }

    function addDragBehavior(el, pt) {
        el.addEventListener('mousedown', function (e) {
            if (e.button !== 0 || addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode || addTextMode) return;
            e.stopPropagation();
            const startX = e.clientX, startY = e.clientY;
            let moved = false;

            function onMove(e) {
                const dx = e.clientX - startX, dy = e.clientY - startY;
                if (!moved && Math.sqrt(dx * dx + dy * dy) > 4) moved = true;
                if (moved) {
                    el.style.transform = 'translate(' + dx + 'px,' + dy + 'px)';
                    el.style.cursor    = 'grabbing';
                    document.body.style.userSelect = 'none';
                }
            }

            function onUp(e) {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                document.body.style.userSelect = '';
                el.style.transform = '';
                el.style.cursor    = '';
                if (!moved) return;

                justDragged = true;
                setTimeout(function () { justDragged = false; }, 0);

                const rect   = figureImg.getBoundingClientRect();
                const dxPct  = (e.clientX - startX) / rect.width  * 100;
                const dyPct  = (e.clientY - startY) / rect.height * 100;
                pt.x_pct = Math.min(Math.max(parseFloat(pt.x_pct) + dxPct, 0), 100).toFixed(2);
                pt.y_pct = Math.min(Math.max(parseFloat(pt.y_pct) + dyPct, 0), 100).toFixed(2);
                if (pt.x2_pct !== null && pt.x2_pct !== undefined) {
                    pt.x2_pct = Math.min(Math.max(parseFloat(pt.x2_pct) + dxPct, 0), 100).toFixed(2);
                    pt.y2_pct = Math.min(Math.max(parseFloat(pt.y2_pct) + dyPct, 0), 100).toFixed(2);
                }
                renderPoints(activeFigure.points);
                savePointPosition(pt);
            }

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    // ---- Render points overlay ----
    function renderPoints(points) {
        const ns = 'http://www.w3.org/2000/svg';
        pointsOverlay.innerHTML = '';
        linesSvg.innerHTML = '';
        // arrowhead marker — auto-start-reverse gives both-end arrows from one definition
        const defsEl = document.createElementNS(ns, 'defs');
        defsEl.innerHTML =
            '<marker id="dim-arrow" markerWidth="6" markerHeight="5" refX="6" refY="2.5" ' +
            'orient="auto-start-reverse" markerUnits="strokeWidth">' +
            '<path d="M0,0 L0,5 L6,2.5 z" fill="context-stroke"/>' +
            '</marker>';
        linesSvg.appendChild(defsEl);
        (points || []).forEach(function (pt) {
            if (pt.point_type === 'text') {
                renderTextLabel(pt);
            } else if (pt.point_type === 'circle' && pt.width_pct && pt.height_pct) {
                renderCircle(pt);
            } else if (pt.point_type === 'navigation' && pt.width_pct && pt.height_pct) {
                renderArea(pt);
            } else if (pt.x2_pct !== null && pt.x2_pct !== undefined) {
                renderLine(pt);
            } else if (pt.label_x_pct !== null && pt.label_x_pct !== undefined) {
                renderCallout(pt);
            } else {
                const marker = document.createElement('div');
                marker.className = 'dim-point-marker' + (pt.point_type === 'navigation' ? ' navigation' : '') + (activePoint && activePoint.id === pt.id ? ' active' : '');
                marker.style.left  = pt.x_pct + '%';
                marker.style.top   = pt.y_pct + '%';
                marker.title       = pt.code + (pt.description ? ': ' + pt.description : '');
                marker.textContent = pt.code.length <= 3 ? pt.code : pt.code.slice(0,3);
                marker.dataset.id  = pt.id;
                marker.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (justDragged || addPointMode || addAreaMode || addLineMode) return;
                    if (pt.point_type === 'navigation' && pt.child_figure_id) {
                        const child = figures.find(function(f) { return f.id == pt.child_figure_id; });
                        if (child) { selectFigure(child); return; }
                    }
                    selectPoint(pt);
                });
                marker.addEventListener('dblclick', function (e) {
                    e.stopPropagation();
                    openEditPointModal(pt);
                });
                addDragBehavior(marker, pt);
                pointsOverlay.appendChild(marker);
            }
        });
    }

    function renderArea(pt) {
        const ns       = 'http://www.w3.org/2000/svg';
        const isActive = activePoint && activePoint.id === pt.id;
        const hasExtLabel = pt.label_x_pct !== null && pt.label_x_pct !== undefined;

        const area = document.createElement('div');
        area.className = 'dim-area-marker' + (isActive ? ' active' : '');
        area.style.left   = pt.x_pct + '%';
        area.style.top    = pt.y_pct + '%';
        area.style.width  = pt.width_pct + '%';
        area.style.height = pt.height_pct + '%';
        area.title        = pt.code + (pt.description ? ': ' + pt.description : '');
        area.dataset.id   = pt.id;

        if (!hasExtLabel) {
            const internalLabel = document.createElement('span');
            internalLabel.className   = 'dim-area-label';
            internalLabel.textContent = pt.code;
            area.appendChild(internalLabel);
        }

        area.addEventListener('click', function (e) {
            e.stopPropagation();
            if (justDragged || addPointMode || addCalloutMode || addTextMode || addCircleMode || addAreaMode || addLineMode) return;
            if (pt.child_figure_id) {
                const child = figures.find(function(f) { return f.id == pt.child_figure_id; });
                if (child) { selectFigure(child); return; }
            }
        });
        area.addEventListener('dblclick', function (e) { e.stopPropagation(); openEditPointModal(pt); });
        addDragBehavior(area, pt);
        pointsOverlay.appendChild(area);

        if (hasExtLabel) {
            // External label — append first to measure size
            const lbl = document.createElement('div');
            lbl.className   = 'dim-callout-label' + (isActive ? ' active' : '');
            lbl.style.left  = pt.label_x_pct + '%';
            lbl.style.top   = pt.label_y_pct + '%';
            lbl.textContent = pt.code;
            lbl.title       = pt.code + (pt.description ? ': ' + pt.description : '');
            lbl.dataset.id  = pt.id;
            lbl.addEventListener('dblclick', function (e) {
                e.stopPropagation();
                if (addPointMode || addCalloutMode || addTextMode || addCircleMode || addAreaMode || addLineMode) return;
                openEditPointModal(pt);
            });
            addLabelDragBehavior(lbl, pt);
            pointsOverlay.appendChild(lbl);

            // Leader line: from rect border → label border
            const rcx = parseFloat(pt.x_pct)      + parseFloat(pt.width_pct)  / 2;
            const rcy = parseFloat(pt.y_pct)       + parseFloat(pt.height_pct) / 2;
            const lx  = parseFloat(pt.label_x_pct);
            const ly  = parseFloat(pt.label_y_pct);
            const vx  = lx - rcx, vy = ly - rcy;
            const hw  = parseFloat(pt.width_pct) / 2, hh = parseFloat(pt.height_pct) / 2;
            const t   = Math.min(vx !== 0 ? hw / Math.abs(vx) : Infinity, vy !== 0 ? hh / Math.abs(vy) : Infinity);
            const bx  = rcx + t * vx, by = rcy + t * vy; // rect border point

            // label border point
            const cRect = figureImg.getBoundingClientRect();
            const lblR  = lbl.getBoundingClientRect();
            const lhw   = (lblR.width  / 2) / (cRect.width  || 1) * 100;
            const lhh   = (lblR.height / 2) / (cRect.height || 1) * 100;
            const uvx   = bx - lx, uvy = by - ly;
            const lt    = Math.min(uvx !== 0 ? lhw / Math.abs(uvx) : Infinity, uvy !== 0 ? lhh / Math.abs(uvy) : Infinity);
            const lex   = lx + lt * uvx, ley = ly + lt * uvy;

            const g = document.createElementNS(ns, 'g');
            g.style.pointerEvents = 'none';
            const line = document.createElementNS(ns, 'line');
            line.setAttribute('x1', bx + '%');  line.setAttribute('y1', by + '%');
            line.setAttribute('x2', lex + '%'); line.setAttribute('y2', ley + '%');
            line.setAttribute('stroke', isActive ? '#dc3545' : '#333');
            line.setAttribute('stroke-width', '1');
            g.appendChild(line);
            linesSvg.appendChild(g);
        }
    }

    function renderLine(pt) {
        const ns       = 'http://www.w3.org/2000/svg';
        const isActive = activePoint && activePoint.id === pt.id;
        const color    = isActive ? '#0d6efd' : '#dc3545';
        const midX     = ((parseFloat(pt.x_pct) + parseFloat(pt.x2_pct)) / 2).toFixed(2);
        const midY     = ((parseFloat(pt.y_pct) + parseFloat(pt.y2_pct)) / 2).toFixed(2);
        const hasExtLabel = pt.label_x_pct != null && pt.label_x_pct !== '';

        const g = document.createElementNS(ns, 'g');
        g.dataset.id = pt.id;
        g.style.cursor = 'pointer';
        g.style.pointerEvents = 'auto';

        // wide transparent hit area
        const hit = document.createElementNS(ns, 'line');
        hit.setAttribute('x1', pt.x_pct + '%'); hit.setAttribute('y1', pt.y_pct + '%');
        hit.setAttribute('x2', pt.x2_pct + '%'); hit.setAttribute('y2', pt.y2_pct + '%');
        hit.setAttribute('stroke', 'transparent'); hit.setAttribute('stroke-width', '14');
        hit.style.pointerEvents = 'stroke';

        // visual line with arrowheads at both ends
        const line = document.createElementNS(ns, 'line');
        line.setAttribute('x1', pt.x_pct + '%'); line.setAttribute('y1', pt.y_pct + '%');
        line.setAttribute('x2', pt.x2_pct + '%'); line.setAttribute('y2', pt.y2_pct + '%');
        line.setAttribute('stroke', color); line.setAttribute('stroke-width', '2');
        line.setAttribute('marker-start', 'url(#dim-arrow)');
        line.setAttribute('marker-end',   'url(#dim-arrow)');
        line.style.pointerEvents = 'none';

        g.appendChild(hit);
        g.appendChild(line);

        if (!hasExtLabel) {
            // Fallback: label at midpoint in SVG
            const text = document.createElementNS(ns, 'text');
            text.setAttribute('x', midX + '%'); text.setAttribute('y', midY + '%');
            text.setAttribute('text-anchor', 'middle'); text.setAttribute('dominant-baseline', 'middle');
            text.setAttribute('font-size', '11'); text.setAttribute('font-weight', '700');
            text.setAttribute('fill', color);
            text.setAttribute('stroke', 'white'); text.setAttribute('stroke-width', '3');
            text.setAttribute('paint-order', 'stroke');
            text.style.pointerEvents = 'none';
            text.textContent = pt.code;
            g.appendChild(text);
        }

        g.addEventListener('click', function(e) {
            e.stopPropagation();
            if (justDragged || addPointMode || addAreaMode || addLineMode) return;
            selectPoint(pt);
        });
        g.addEventListener('dblclick', function(e) {
            e.stopPropagation();
            openEditPointModal(pt);
        });
        addDragBehavior(g, pt);
        linesSvg.appendChild(g);

        if (hasExtLabel) {
            // External draggable label (same as callout)
            const lbl = document.createElement('div');
            lbl.className   = 'dim-callout-label' + (isActive ? ' active' : '');
            lbl.style.left  = pt.label_x_pct + '%';
            lbl.style.top   = pt.label_y_pct + '%';
            lbl.textContent = pt.code;
            lbl.title       = pt.code + (pt.description ? ': ' + pt.description : '');
            lbl.dataset.id  = pt.id;
            lbl.addEventListener('click', function(e) {
                e.stopPropagation();
                if (justDragged || addPointMode || addAreaMode || addLineMode) return;
                selectPoint(pt);
            });
            lbl.addEventListener('dblclick', function(e) { e.stopPropagation(); openEditPointModal(pt); });
            addLabelDragBehavior(lbl, pt);
            pointsOverlay.appendChild(lbl);

            // Leader line from midpoint → label border (same calculation as callout)
            const containerRect = figureImg.getBoundingClientRect();
            const lblRect       = lbl.getBoundingClientRect();
            const cW = containerRect.width  || 1;
            const cH = containerRect.height || 1;
            const hw = (lblRect.width  / 2) / cW * 100;
            const hh = (lblRect.height / 2) / cH * 100;
            const vx = parseFloat(midX) - parseFloat(pt.label_x_pct);
            const vy = parseFloat(midY) - parseFloat(pt.label_y_pct);
            const tx = (vx !== 0) ? hw / Math.abs(vx) : Infinity;
            const ty = (vy !== 0) ? hh / Math.abs(vy) : Infinity;
            const t  = Math.min(tx, ty);
            const ex = parseFloat(pt.label_x_pct) + t * vx;
            const ey = parseFloat(pt.label_y_pct) + t * vy;

            const leaderG = document.createElementNS(ns, 'g');
            leaderG.style.pointerEvents = 'none';
            const leaderLine = document.createElementNS(ns, 'line');
            leaderLine.setAttribute('x1', midX + '%'); leaderLine.setAttribute('y1', midY + '%');
            leaderLine.setAttribute('x2', ex + '%');   leaderLine.setAttribute('y2', ey + '%');
            leaderLine.setAttribute('stroke', isActive ? '#0d6efd' : '#333');
            leaderLine.setAttribute('stroke-width', '1');
            leaderLine.style.pointerEvents = 'none';
            leaderG.appendChild(leaderLine);
            linesSvg.appendChild(leaderG);
        }
    }

    function addLabelDragBehavior(el, pt) {
        el.addEventListener('mousedown', function (e) {
            if (e.button !== 0 || addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode || addTextMode) return;
            e.stopPropagation();
            const startX = e.clientX, startY = e.clientY;
            let moved = false;

            function onMove(e) {
                const dx = e.clientX - startX, dy = e.clientY - startY;
                if (!moved && Math.sqrt(dx * dx + dy * dy) > 4) moved = true;
                if (moved) {
                    el.style.transform = 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + dy + 'px))';
                    document.body.style.userSelect = 'none';
                }
            }

            function onUp(e) {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                document.body.style.userSelect = '';
                el.style.transform = '';
                if (!moved) return;

                justDragged = true;
                setTimeout(function () { justDragged = false; }, 0);

                const rect   = figureImg.getBoundingClientRect();
                const dxPct  = (e.clientX - startX) / rect.width  * 100;
                const dyPct  = (e.clientY - startY) / rect.height * 100;
                pt.label_x_pct = Math.min(Math.max(parseFloat(pt.label_x_pct) + dxPct, 0), 100).toFixed(2);
                pt.label_y_pct = Math.min(Math.max(parseFloat(pt.label_y_pct) + dyPct, 0), 100).toFixed(2);
                renderPoints(activeFigure.points);
                savePointPosition(pt);
            }

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    function renderCallout(pt) {
        const ns       = 'http://www.w3.org/2000/svg';
        const isActive = activePoint && activePoint.id === pt.id;
        const stroke   = isActive ? '#dc3545' : '#333';

        // Small dot at measurement location
        const dot = document.createElement('div');
        dot.className = 'dim-callout-dot' + (isActive ? ' active' : '');
        dot.style.left = pt.x_pct + '%';
        dot.style.top  = pt.y_pct + '%';
        dot.title      = pt.code + (pt.description ? ': ' + pt.description : '');
        dot.dataset.id = pt.id;
        dot.addEventListener('click', function (e) {
            e.stopPropagation();
            if (justDragged || addPointMode || addCalloutMode || addTextMode || addAreaMode || addLineMode) return;
            selectPoint(pt);
        });
        dot.addEventListener('dblclick', function (e) { e.stopPropagation(); openEditPointModal(pt); });
        addDragBehavior(dot, pt);
        pointsOverlay.appendChild(dot);

        // Label div — append first so we can measure it
        const lbl = document.createElement('div');
        lbl.className   = 'dim-callout-label' + (isActive ? ' active' : '');
        lbl.style.left  = pt.label_x_pct + '%';
        lbl.style.top   = pt.label_y_pct + '%';
        lbl.textContent = pt.code;
        lbl.title       = pt.code + (pt.description ? ': ' + pt.description : '');
        lbl.dataset.id  = pt.id;
        lbl.addEventListener('click', function (e) {
            e.stopPropagation();
            if (justDragged || addPointMode || addCalloutMode || addTextMode || addAreaMode || addLineMode) return;
            selectPoint(pt);
        });
        lbl.addEventListener('dblclick', function (e) { e.stopPropagation(); openEditPointModal(pt); });
        addLabelDragBehavior(lbl, pt);
        pointsOverlay.appendChild(lbl);

        // Compute leader line endpoint at the label rectangle border
        const containerRect = figureImg.getBoundingClientRect();
        const lblRect       = lbl.getBoundingClientRect();
        const cW = containerRect.width  || 1;
        const cH = containerRect.height || 1;
        // label half-sizes in % of container
        const hw = (lblRect.width  / 2) / cW * 100;
        const hh = (lblRect.height / 2) / cH * 100;
        // vector from label center toward dot
        const vx = parseFloat(pt.x_pct)     - parseFloat(pt.label_x_pct);
        const vy = parseFloat(pt.y_pct)     - parseFloat(pt.label_y_pct);
        // t = how far to travel until we hit the rectangle border
        const tx  = (vx !== 0) ? hw / Math.abs(vx) : Infinity;
        const ty  = (vy !== 0) ? hh / Math.abs(vy) : Infinity;
        const t   = Math.min(tx, ty);
        const ex  = parseFloat(pt.label_x_pct) + t * vx;
        const ey  = parseFloat(pt.label_y_pct) + t * vy;

        // SVG leader line from dot to label border
        const g = document.createElementNS(ns, 'g');
        g.style.pointerEvents = 'none';
        const leaderLine = document.createElementNS(ns, 'line');
        leaderLine.setAttribute('x1', pt.x_pct + '%'); leaderLine.setAttribute('y1', pt.y_pct + '%');
        leaderLine.setAttribute('x2', ex + '%');        leaderLine.setAttribute('y2', ey + '%');
        leaderLine.setAttribute('stroke', stroke);
        leaderLine.setAttribute('stroke-width', '1');
        leaderLine.style.pointerEvents = 'none';
        g.appendChild(leaderLine);
        linesSvg.appendChild(g);
    }

    function renderTextLabel(pt) {
        const ns       = 'http://www.w3.org/2000/svg';
        const isActive = activePoint && activePoint.id === pt.id;
        const stroke   = isActive ? '#dc3545' : '#14b8a6';

        // Look up IC name from local inspComponents array (no server roundtrip needed)
        const ic = inspComponents.find(function (c) { return c.id == pt.child_ic_id; });
        const labelText = ic ? ic.label : (pt.description || pt.code);

        // Dot at part location
        const dot = document.createElement('div');
        dot.className = 'dim-callout-dot text' + (isActive ? ' active' : '');
        dot.style.left = pt.x_pct + '%';
        dot.style.top  = pt.y_pct + '%';
        dot.title      = labelText;
        dot.dataset.id = pt.id;
        dot.addEventListener('click', function (e) {
            e.stopPropagation();
            if (justDragged || addPointMode || addCalloutMode || addTextMode || addAreaMode || addLineMode) return;
            selectPoint(pt);
        });
        dot.addEventListener('dblclick', function (e) { e.stopPropagation(); openEditPointModal(pt); });
        addDragBehavior(dot, pt);
        pointsOverlay.appendChild(dot);

        // Rounded rect label
        const lbl = document.createElement('div');
        lbl.className   = 'dim-text-label' + (isActive ? ' active' : '');
        lbl.style.left  = pt.label_x_pct + '%';
        lbl.style.top   = pt.label_y_pct + '%';
        lbl.textContent = labelText;
        lbl.title       = labelText;
        lbl.dataset.id  = pt.id;
        lbl.addEventListener('click', function (e) {
            e.stopPropagation();
            if (justDragged || addPointMode || addCalloutMode || addTextMode || addAreaMode || addLineMode) return;
            selectPoint(pt);
        });
        lbl.addEventListener('dblclick', function (e) { e.stopPropagation(); openEditPointModal(pt); });
        addLabelDragBehavior(lbl, pt);
        pointsOverlay.appendChild(lbl);

        // Leader line (dot → label border)
        const containerRect = figureImg.getBoundingClientRect();
        const lblRect       = lbl.getBoundingClientRect();
        const cW = containerRect.width  || 1;
        const cH = containerRect.height || 1;
        const hw = (lblRect.width  / 2) / cW * 100;
        const hh = (lblRect.height / 2) / cH * 100;
        const vx = parseFloat(pt.x_pct) - parseFloat(pt.label_x_pct);
        const vy = parseFloat(pt.y_pct) - parseFloat(pt.label_y_pct);
        const tx = (vx !== 0) ? hw / Math.abs(vx) : Infinity;
        const ty = (vy !== 0) ? hh / Math.abs(vy) : Infinity;
        const t  = Math.min(tx, ty);
        const ex = parseFloat(pt.label_x_pct) + t * vx;
        const ey = parseFloat(pt.label_y_pct) + t * vy;

        const g = document.createElementNS(ns, 'g');
        g.style.pointerEvents = 'none';
        const leaderLine = document.createElementNS(ns, 'line');
        leaderLine.setAttribute('x1', pt.x_pct + '%'); leaderLine.setAttribute('y1', pt.y_pct + '%');
        leaderLine.setAttribute('x2', ex + '%');        leaderLine.setAttribute('y2', ey + '%');
        leaderLine.setAttribute('stroke', stroke);
        leaderLine.setAttribute('stroke-width', '1.5');
        leaderLine.style.pointerEvents = 'none';
        g.appendChild(leaderLine);
        linesSvg.appendChild(g);
    }

    function renderCircle(pt) {
        const ns       = 'http://www.w3.org/2000/svg';
        const isActive = activePoint && activePoint.id === pt.id;
        const color    = isActive ? '#dc3545' : '#6f42c1';

        const g = document.createElementNS(ns, 'g');
        g.dataset.id = pt.id;
        g.style.cursor = 'pointer';
        g.style.pointerEvents = 'auto';

        // Transparent fill + wide stroke — catches clicks anywhere inside/on circle
        const hitEl = document.createElementNS(ns, 'ellipse');
        hitEl.setAttribute('cx', pt.x_pct + '%'); hitEl.setAttribute('cy', pt.y_pct + '%');
        hitEl.setAttribute('rx', pt.width_pct + '%'); hitEl.setAttribute('ry', pt.height_pct + '%');
        hitEl.setAttribute('fill', 'transparent');
        hitEl.setAttribute('stroke', 'transparent'); hitEl.setAttribute('stroke-width', '14');
        hitEl.style.pointerEvents = 'all';

        // Visual ellipse
        const el = document.createElementNS(ns, 'ellipse');
        el.setAttribute('cx', pt.x_pct + '%'); el.setAttribute('cy', pt.y_pct + '%');
        el.setAttribute('rx', pt.width_pct + '%'); el.setAttribute('ry', pt.height_pct + '%');
        el.setAttribute('fill', isActive ? 'rgba(220,53,69,0.08)' : 'rgba(111,66,193,0.08)');
        el.setAttribute('stroke', color); el.setAttribute('stroke-width', '1.5');
        el.style.pointerEvents = 'none';

        const hasExtLabel = pt.label_x_pct !== null && pt.label_x_pct !== undefined;

        // Inline code label above circle (hidden when external label is used)
        if (!hasExtLabel) {
            const labelY = (parseFloat(pt.y_pct) - parseFloat(pt.height_pct)).toFixed(2);
            const text = document.createElementNS(ns, 'text');
            text.setAttribute('x', pt.x_pct + '%'); text.setAttribute('y', labelY + '%');
            text.setAttribute('text-anchor', 'middle'); text.setAttribute('dominant-baseline', 'text-bottom');
            text.setAttribute('font-size', '11'); text.setAttribute('font-weight', '700');
            text.setAttribute('fill', color);
            text.setAttribute('stroke', 'white'); text.setAttribute('stroke-width', '3');
            text.setAttribute('paint-order', 'stroke');
            text.style.pointerEvents = 'none';
            text.textContent = pt.code;
            g.appendChild(text);
        }

        g.appendChild(hitEl); g.appendChild(el);

        g.addEventListener('click', function (e) {
            e.stopPropagation();
            if (justDragged || addPointMode || addCalloutMode || addTextMode || addCircleMode || addAreaMode || addLineMode) return;
            if (pt.child_figure_id) {
                const child = figures.find(function(f) { return f.id == pt.child_figure_id; });
                if (child) { selectFigure(child); return; }
            }
        });
        g.addEventListener('dblclick', function (e) { e.stopPropagation(); openEditPointModal(pt); });
        addDragBehavior(g, pt);
        linesSvg.appendChild(g);

        // External label + leader line for circle
        if (hasExtLabel) {
            const lbl = document.createElement('div');
            lbl.className   = 'dim-callout-label' + (isActive ? ' active' : '');
            lbl.style.left  = pt.label_x_pct + '%';
            lbl.style.top   = pt.label_y_pct + '%';
            lbl.textContent = pt.code;
            lbl.title       = pt.code + (pt.description ? ': ' + pt.description : '');
            lbl.dataset.id  = pt.id;
            lbl.addEventListener('dblclick', function (e) {
                e.stopPropagation();
                if (addPointMode || addCalloutMode || addTextMode || addCircleMode || addAreaMode || addLineMode) return;
                openEditPointModal(pt);
            });
            addLabelDragBehavior(lbl, pt);
            pointsOverlay.appendChild(lbl);

            // Leader line: point on ellipse outline → label border
            const cx = parseFloat(pt.x_pct), cy = parseFloat(pt.y_pct);
            const rx = parseFloat(pt.width_pct), ry = parseFloat(pt.height_pct);
            const lx = parseFloat(pt.label_x_pct), ly = parseFloat(pt.label_y_pct);
            const theta = Math.atan2((ly - cy) / ry, (lx - cx) / rx);
            const bx = cx + rx * Math.cos(theta);
            const by = cy + ry * Math.sin(theta);

            // Label border point
            const cRect = figureImg.getBoundingClientRect();
            const lblR  = lbl.getBoundingClientRect();
            const lhw = (lblR.width  / 2) / (cRect.width  || 1) * 100;
            const lhh = (lblR.height / 2) / (cRect.height || 1) * 100;
            const uvx = bx - lx, uvy = by - ly;
            const lt  = Math.min(uvx !== 0 ? lhw / Math.abs(uvx) : Infinity, uvy !== 0 ? lhh / Math.abs(uvy) : Infinity);
            const lex = lx + lt * uvx, ley = ly + lt * uvy;

            const lg = document.createElementNS(ns, 'g');
            lg.style.pointerEvents = 'none';
            const ll = document.createElementNS(ns, 'line');
            ll.setAttribute('x1', bx + '%');  ll.setAttribute('y1', by + '%');
            ll.setAttribute('x2', lex + '%'); ll.setAttribute('y2', ley + '%');
            ll.setAttribute('stroke', isActive ? '#dc3545' : '#333');
            ll.setAttribute('stroke-width', '1');
            lg.appendChild(ll);
            linesSvg.appendChild(lg);
        }
    }

    function resetCircleDraw() {
        circleCenter = null;
        if (circleTempEl) { circleTempEl.remove(); circleTempEl = null; }
    }

    function resetLineDraw() {
        lineStart = null;
        if (lineTempMarker) { lineTempMarker.remove(); lineTempMarker = null; }
        if (lineTempLine)   { lineTempLine.remove();   lineTempLine   = null; }
    }

    function resetCalloutDraw() {
        calloutDotStart = null;
        if (calloutTempDot)  { calloutTempDot.remove();  calloutTempDot  = null; }
        if (calloutTempLine) { calloutTempLine.remove(); calloutTempLine = null; }
    }

    // ---- Add point mode toggle ----
    function resetWaitingLabel() {
        areaWaitingLabel   = null;
        circleWaitingLabel = null;
        lineWaitingLabel   = null;
        canvasWrap.classList.remove('line-label-mode');
        if (areaTempRect)    { areaTempRect.remove();    areaTempRect    = null; }
        if (waitingTempLine) { waitingTempLine.remove(); waitingTempLine = null; }
    }

    function resetTextDraw() {
        textDotStart = null;
        if (textTempDot)  { textTempDot.remove();  textTempDot  = null; }
        if (textTempLine) { textTempLine.remove(); textTempLine = null; }
    }

    function deactivateAllModes() {
        addPointMode   = false; addPointModeBtn.classList.remove('active');   canvasWrap.classList.remove('add-point-mode');
        addCalloutMode = false; addCalloutModeBtn.classList.remove('active'); canvasWrap.classList.remove('add-callout-mode');
        resetCalloutDraw();
        addCircleMode  = false; addCircleModeBtn.classList.remove('active');  canvasWrap.classList.remove('add-circle-mode');
        resetCircleDraw();
        resetWaitingLabel();
        addAreaMode    = false; addAreaModeBtn.classList.remove('active');    canvasWrap.classList.remove('add-area-mode');
        if (areaPreviewEl) { areaPreviewEl.remove(); areaPreviewEl = null; } areaDragStart = null;
        addLineMode    = false; addLineModeBtn.classList.remove('active');    canvasWrap.classList.remove('add-line-mode');
        resetLineDraw();
        addTextMode    = false; addTextModeBtn.classList.remove('active');    canvasWrap.classList.remove('add-text-mode');
        resetTextDraw();
    }

    addPointModeBtn.addEventListener('click', function () {
        const next = !addPointMode;
        deactivateAllModes();
        if (next) { addPointMode = true; addPointModeBtn.classList.add('active'); canvasWrap.classList.add('add-point-mode'); }
    });

    addCalloutModeBtn.addEventListener('click', function () {
        const next = !addCalloutMode;
        deactivateAllModes();
        if (next) { addCalloutMode = true; addCalloutModeBtn.classList.add('active'); canvasWrap.classList.add('add-callout-mode'); }
    });

    addCircleModeBtn.addEventListener('click', function () {
        const next = !addCircleMode;
        deactivateAllModes();
        if (next) { addCircleMode = true; addCircleModeBtn.classList.add('active'); canvasWrap.classList.add('add-circle-mode'); }
    });

    addAreaModeBtn.addEventListener('click', function () {
        const next = !addAreaMode;
        deactivateAllModes();
        if (next) { addAreaMode = true; addAreaModeBtn.classList.add('active'); canvasWrap.classList.add('add-area-mode'); }
    });

    addLineModeBtn.addEventListener('click', function () {
        const next = !addLineMode;
        deactivateAllModes();
        if (next) { addLineMode = true; addLineModeBtn.classList.add('active'); canvasWrap.classList.add('add-line-mode'); }
    });

    addTextModeBtn.addEventListener('click', function () {
        const next = !addTextMode;
        deactivateAllModes();
        if (next) { addTextMode = true; addTextModeBtn.classList.add('active'); canvasWrap.classList.add('add-text-mode'); }
    });

    // ---- mousedown: place label for waiting area or circle (must be before draw handlers) ----
    imgContainer.addEventListener('mousedown', function (e) {
        if (e.button !== 0 || !activeFigure) return;
        if (areaWaitingLabel) {
            e.preventDefault();
            e.stopPropagation();
            const rect = figureImg.getBoundingClientRect();
            const xPct = ((e.clientX - rect.left) / rect.width  * 100).toFixed(2);
            const yPct = ((e.clientY - rect.top)  / rect.height * 100).toFixed(2);
            const { x, y, w, h } = areaWaitingLabel;
            resetWaitingLabel();
            deactivateAllModes();
            openAddPointModal(x.toFixed(2), y.toFixed(2), w.toFixed(2), h.toFixed(2), null, null, xPct, yPct);
            return;
        }
        if (circleWaitingLabel) {
            e.preventDefault();
            e.stopPropagation();
            const rect = figureImg.getBoundingClientRect();
            const xPct = ((e.clientX - rect.left) / rect.width  * 100).toFixed(2);
            const yPct = ((e.clientY - rect.top)  / rect.height * 100).toFixed(2);
            const { cx, cy, rx, ry } = circleWaitingLabel;
            if (circleTempEl) { circleTempEl.remove(); circleTempEl = null; }
            resetWaitingLabel();
            deactivateAllModes();
            openAddCircleModal(cx.toFixed(2), cy.toFixed(2), rx.toFixed(2), ry.toFixed(2), xPct, yPct);
            return;
        }
        if (lineWaitingLabel) {
            e.preventDefault();
            e.stopPropagation();
            const rect = figureImg.getBoundingClientRect();
            const xPct = ((e.clientX - rect.left) / rect.width  * 100).toFixed(2);
            const yPct = ((e.clientY - rect.top)  / rect.height * 100).toFixed(2);
            const { x1, y1, x2, y2 } = lineWaitingLabel;
            resetWaitingLabel();
            deactivateAllModes();
            openAddPointModal(x1, y1, null, null, x2, y2, xPct, yPct);
            return;
        }
    });

    // ---- mousedown: circle draw (center + drag radius) ----
    imgContainer.addEventListener('mousedown', function (e) {
        if (!addCircleMode || !activeFigure || e.button !== 0 || circleWaitingLabel) return;
        e.preventDefault();
        const rect = figureImg.getBoundingClientRect();
        circleCenter = {
            x: (e.clientX - rect.left) / rect.width  * 100,
            y: (e.clientY - rect.top)  / rect.height * 100,
        };
        const ns = 'http://www.w3.org/2000/svg';
        circleTempEl = document.createElementNS(ns, 'ellipse');
        circleTempEl.setAttribute('cx', circleCenter.x + '%'); circleTempEl.setAttribute('cy', circleCenter.y + '%');
        circleTempEl.setAttribute('rx', '0%'); circleTempEl.setAttribute('ry', '0%');
        circleTempEl.setAttribute('fill', 'rgba(13,110,253,0.08)');
        circleTempEl.setAttribute('stroke', '#0d6efd'); circleTempEl.setAttribute('stroke-width', '1.5');
        circleTempEl.setAttribute('stroke-dasharray', '5,3');
        circleTempEl.style.pointerEvents = 'none';
        linesSvg.appendChild(circleTempEl);
    });

    // ---- Drag on image to draw area ----
    imgContainer.addEventListener('mousedown', function (e) {
        if (!addAreaMode || !activeFigure || e.button !== 0 || areaWaitingLabel) return;
        e.preventDefault();
        const rect = figureImg.getBoundingClientRect();
        areaDragStart = {
            x: (e.clientX - rect.left) / rect.width  * 100,
            y: (e.clientY - rect.top)  / rect.height * 100,
        };
        areaPreviewEl = document.createElement('div');
        areaPreviewEl.className = 'dim-area-preview';
        areaPreviewEl.style.left   = areaDragStart.x + '%';
        areaPreviewEl.style.top    = areaDragStart.y + '%';
        areaPreviewEl.style.width  = '0';
        areaPreviewEl.style.height = '0';
        imgContainer.appendChild(areaPreviewEl);
    });

    document.addEventListener('mousemove', function (e) {
        if (isPanning && panStart) {
            canvasWrap.scrollLeft = panStart.x - e.clientX;
            canvasWrap.scrollTop  = panStart.y - e.clientY;
            return;
        }
        // Preview leader line while waiting for label placement click
        if (areaWaitingLabel || circleWaitingLabel || lineWaitingLabel) {
            const rect = figureImg.getBoundingClientRect();
            const cx = Math.min(Math.max((e.clientX - rect.left) / rect.width  * 100, 0), 100);
            const cy = Math.min(Math.max((e.clientY - rect.top)  / rect.height * 100, 0), 100);
            const ns = 'http://www.w3.org/2000/svg';
            let ox, oy; // origin: center of shape
            if (areaWaitingLabel) {
                ox = areaWaitingLabel.x + areaWaitingLabel.w / 2;
                oy = areaWaitingLabel.y + areaWaitingLabel.h / 2;
            } else if (circleWaitingLabel) {
                ox = circleWaitingLabel.cx;
                oy = circleWaitingLabel.cy;
            } else {
                // midpoint of line
                ox = (parseFloat(lineWaitingLabel.x1) + parseFloat(lineWaitingLabel.x2)) / 2;
                oy = (parseFloat(lineWaitingLabel.y1) + parseFloat(lineWaitingLabel.y2)) / 2;
            }
            if (!waitingTempLine) {
                waitingTempLine = document.createElementNS(ns, 'line');
                waitingTempLine.setAttribute('stroke', '#0d6efd');
                waitingTempLine.setAttribute('stroke-width', '1');
                waitingTempLine.setAttribute('stroke-dasharray', '4,3');
                waitingTempLine.style.pointerEvents = 'none';
                linesSvg.appendChild(waitingTempLine);
            }
            waitingTempLine.setAttribute('x1', ox + '%'); waitingTempLine.setAttribute('y1', oy + '%');
            waitingTempLine.setAttribute('x2', cx + '%'); waitingTempLine.setAttribute('y2', cy + '%');
        }
        if (circleCenter && circleTempEl) {
            const rect   = figureImg.getBoundingClientRect();
            const dxPx   = (e.clientX - rect.left) - circleCenter.x / 100 * rect.width;
            const dyPx   = (e.clientY - rect.top)  - circleCenter.y / 100 * rect.height;
            const radius = Math.sqrt(dxPx * dxPx + dyPx * dyPx);
            circleTempEl.setAttribute('rx', (radius / rect.width  * 100) + '%');
            circleTempEl.setAttribute('ry', (radius / rect.height * 100) + '%');
        }
        if (!areaDragStart || !areaPreviewEl) return;
        const rect = figureImg.getBoundingClientRect();
        const cx = Math.min(Math.max((e.clientX - rect.left) / rect.width  * 100, 0), 100);
        const cy = Math.min(Math.max((e.clientY - rect.top)  / rect.height * 100, 0), 100);
        areaPreviewEl.style.left   = Math.min(cx, areaDragStart.x) + '%';
        areaPreviewEl.style.top    = Math.min(cy, areaDragStart.y) + '%';
        areaPreviewEl.style.width  = Math.abs(cx - areaDragStart.x) + '%';
        areaPreviewEl.style.height = Math.abs(cy - areaDragStart.y) + '%';
    });

    document.addEventListener('mouseup', function (e) {
        if (isPanning) {
            isPanning = false;
            panStart  = null;
            canvasWrap.classList.remove('panning');
            return;
        }
        if (circleCenter) {
            const rect   = figureImg.getBoundingClientRect();
            const dxPx   = (e.clientX - rect.left) - circleCenter.x / 100 * rect.width;
            const dyPx   = (e.clientY - rect.top)  - circleCenter.y / 100 * rect.height;
            const radius = Math.sqrt(dxPx * dxPx + dyPx * dyPx);
            if (radius > 5) {
                // Keep circleTempEl visible, wait for label click
                circleWaitingLabel = {
                    cx: parseFloat(circleCenter.x.toFixed(2)),
                    cy: parseFloat(circleCenter.y.toFixed(2)),
                    rx: parseFloat((radius / rect.width  * 100).toFixed(2)),
                    ry: parseFloat((radius / rect.height * 100).toFixed(2)),
                };
                // Make temp ellipse solid (not dashed) to show it's placed
                circleTempEl.removeAttribute('stroke-dasharray');
                circleTempEl.setAttribute('stroke', '#6f42c1');
                circleTempEl.setAttribute('stroke-width', '1.5');
            } else {
                resetCircleDraw();
            }
            circleCenter = null;
            return;
        }
        if (!areaDragStart || !areaPreviewEl) return;
        const rect = figureImg.getBoundingClientRect();
        const cx = Math.min(Math.max((e.clientX - rect.left) / rect.width  * 100, 0), 100);
        const cy = Math.min(Math.max((e.clientY - rect.top)  / rect.height * 100, 0), 100);
        const x  = Math.min(cx, areaDragStart.x);
        const y  = Math.min(cy, areaDragStart.y);
        const w  = Math.abs(cx - areaDragStart.x);
        const h  = Math.abs(cy - areaDragStart.y);
        areaPreviewEl.remove();
        areaPreviewEl = null;
        areaDragStart = null;
        if (w < 1 || h < 1) return;
        // Keep area visible, wait for label click
        areaWaitingLabel = { x: parseFloat(x.toFixed(2)), y: parseFloat(y.toFixed(2)), w: parseFloat(w.toFixed(2)), h: parseFloat(h.toFixed(2)) };
        areaTempRect = document.createElement('div');
        areaTempRect.className = 'dim-area-preview';
        areaTempRect.style.cssText = 'position:absolute;left:' + x + '%;top:' + y + '%;width:' + w + '%;height:' + h + '%;pointer-events:none;border:1.5px solid #6f42c1;background:rgba(111,66,193,0.06);box-sizing:border-box;border-radius:3px;';
        imgContainer.appendChild(areaTempRect);
    });

    // ---- mousemove: line / callout temp preview ----
    imgContainer.addEventListener('mousemove', function (e) {
        const rect = figureImg.getBoundingClientRect();
        const cx = Math.min(Math.max((e.clientX - rect.left) / rect.width  * 100, 0), 100);
        const cy = Math.min(Math.max((e.clientY - rect.top)  / rect.height * 100, 0), 100);
        const ns = 'http://www.w3.org/2000/svg';

        if (addCalloutMode && calloutDotStart) {
            if (!calloutTempLine) {
                calloutTempLine = document.createElementNS(ns, 'line');
                calloutTempLine.setAttribute('stroke', '#0d6efd');
                calloutTempLine.setAttribute('stroke-width', '1');
                calloutTempLine.setAttribute('stroke-dasharray', '4,3');
                calloutTempLine.style.pointerEvents = 'none';
                linesSvg.appendChild(calloutTempLine);
            }
            calloutTempLine.setAttribute('x1', calloutDotStart.x + '%'); calloutTempLine.setAttribute('y1', calloutDotStart.y + '%');
            calloutTempLine.setAttribute('x2', cx + '%');                 calloutTempLine.setAttribute('y2', cy + '%');
        }

        if (addTextMode && textDotStart) {
            if (!textTempLine) {
                textTempLine = document.createElementNS(ns, 'line');
                textTempLine.setAttribute('stroke', '#14b8a6');
                textTempLine.setAttribute('stroke-width', '1');
                textTempLine.setAttribute('stroke-dasharray', '4,3');
                textTempLine.style.pointerEvents = 'none';
                linesSvg.appendChild(textTempLine);
            }
            textTempLine.setAttribute('x1', textDotStart.x + '%'); textTempLine.setAttribute('y1', textDotStart.y + '%');
            textTempLine.setAttribute('x2', cx + '%');               textTempLine.setAttribute('y2', cy + '%');
        }

        if (!addLineMode || !lineStart) return;
        if (!lineTempLine) {
            lineTempLine = document.createElementNS(ns, 'line');
            lineTempLine.setAttribute('stroke', '#0d6efd');
            lineTempLine.setAttribute('stroke-width', '1.5');
            lineTempLine.setAttribute('stroke-dasharray', '6,4');
            lineTempLine.style.pointerEvents = 'none';
            linesSvg.appendChild(lineTempLine);
        }
        lineTempLine.setAttribute('x1', lineStart.x + '%'); lineTempLine.setAttribute('y1', lineStart.y + '%');
        lineTempLine.setAttribute('x2', cx + '%');          lineTempLine.setAttribute('y2', cy + '%');
    });

    // ---- Click on image to add point / callout / line ----
    imgContainer.addEventListener('click', function (e) {
        if (!activeFigure) return;
        if (addAreaMode   && !areaWaitingLabel)   return;
        if (addCircleMode && !circleWaitingLabel) return;
        const rect = figureImg.getBoundingClientRect();
        const xPct = ((e.clientX - rect.left) / rect.width  * 100).toFixed(2);
        const yPct = ((e.clientY - rect.top)  / rect.height * 100).toFixed(2);

        if (addCalloutMode) {
            if (!calloutDotStart) {
                // 1st click: store dot position, show temp dot
                calloutDotStart = { x: xPct, y: yPct };
                calloutTempDot = document.createElement('div');
                calloutTempDot.className = 'dim-callout-dot';
                calloutTempDot.style.left = xPct + '%';
                calloutTempDot.style.top  = yPct + '%';
                calloutTempDot.style.pointerEvents = 'none';
                pointsOverlay.appendChild(calloutTempDot);
            } else {
                // 2nd click: label position → open modal
                const dotX = calloutDotStart.x, dotY = calloutDotStart.y;
                resetCalloutDraw();
                openAddPointModal(dotX, dotY, null, null, null, null, xPct, yPct);
            }
            return;
        }

        if (addTextMode) {
            if (!textDotStart) {
                // 1st click: dot on the part
                textDotStart = { x: xPct, y: yPct };
                textTempDot = document.createElement('div');
                textTempDot.className = 'dim-callout-dot text';
                textTempDot.style.left = xPct + '%';
                textTempDot.style.top  = yPct + '%';
                textTempDot.style.pointerEvents = 'none';
                pointsOverlay.appendChild(textTempDot);
            } else {
                // 2nd click: label position → open modal (text type)
                const dotX = textDotStart.x, dotY = textDotStart.y;
                resetTextDraw();
                deactivateAllModes();
                openAddTextModal(dotX, dotY, xPct, yPct);
            }
            return;
        }

        if (addLineMode) {
            if (!lineStart) {
                lineStart = { x: xPct, y: yPct };
                const ns = 'http://www.w3.org/2000/svg';
                lineTempMarker = document.createElementNS(ns, 'circle');
                lineTempMarker.setAttribute('cx', xPct + '%'); lineTempMarker.setAttribute('cy', yPct + '%');
                lineTempMarker.setAttribute('r', '6');
                lineTempMarker.setAttribute('fill', '#0d6efd'); lineTempMarker.setAttribute('stroke', 'white'); lineTempMarker.setAttribute('stroke-width', '2');
                lineTempMarker.style.pointerEvents = 'none';
                linesSvg.appendChild(lineTempMarker);
            } else {
                const x1 = lineStart.x, y1 = lineStart.y;
                resetLineDraw();
                addLineMode = false;
                addLineModeBtn.classList.remove('active');
                canvasWrap.classList.remove('add-line-mode');
                // Enter waiting state for label placement (same pattern as area/circle)
                lineWaitingLabel = { x1: x1, y1: y1, x2: xPct, y2: yPct };
                canvasWrap.classList.add('line-label-mode');
            }
            return;
        }

        if (addPointMode) {
            openAddPointModal(xPct, yPct);
        }
    });

    // ---- Select point ----
    function selectPoint(pt) {
        activePoint = pt;
        renderPoints(activeFigure.points || []);
        renderSpecsPanel(pt);
    }

    function clearSpecsPanel() {
        specsHeader.textContent = 'Select a point';
        specsEmpty.classList.remove('d-none');
        specsList.classList.add('d-none');
        specsFooter.classList.add('d-none');
        editPointBtn.classList.add('d-none');
    }

    function renderSpecsPanel(pt) {
        // Text labels are annotations only — no specs/parameters panel needed
        if (pt.point_type === 'text') {
            const ic = inspComponents.find(function (c) { return c.id === pt.child_ic_id; });
            specsHeader.textContent = ic ? ic.label : (pt.description || pt.code);
            specsEmpty.classList.remove('d-none');
            specsEmpty.textContent = 'Part label — no parameters';
            specsList.classList.add('d-none');
            specsFooter.classList.add('d-none');
            editPointBtn.classList.remove('d-none');
            return;
        }
        specsEmpty.textContent = 'Click a point on the figure to view specs';
        specsHeader.textContent = pt.code + (pt.description ? ' — ' + pt.description : '');
        specsEmpty.classList.add('d-none');
        specsList.classList.remove('d-none');
        specsFooter.classList.remove('d-none');
        editPointBtn.classList.remove('d-none');

        specsList.innerHTML = '';
        const params = parametersForPoint(pt);

        if (params.length === 0) {
            specsList.innerHTML = '<div class="text-secondary text-center py-2" style="font-size:12px">No parameters yet</div>';
            return;
        }

        // Group by inspection_component_id
        const groups = {};
        const groupOrder = [];
        params.forEach(function (param) {
            const key = param.inspection_component_id != null ? String(param.inspection_component_id) : '__none__';
            if (!groups[key]) {
                const ic = inspComponents.find(function (c) { return c.id === param.inspection_component_id; }) || null;
                groups[key] = { ic: ic, params: [] };
                groupOrder.push(key);
            }
            groups[key].params.push(param);
        });

        const isFc = !!(pt.is_fits_clearance);

        groupOrder.forEach(function (key) {
            const group = groups[key];
            const ic    = group.ic;
            const icId  = ic ? ic.id : null;
            const icLabel = ic ? escHtml(ic.label) : 'No part';

            const card = document.createElement('div');
            card.className = 'dim-comp-card';

            let html = `<div class="dim-comp-card-header">
                <span class="dim-comp-card-title" title="${icLabel}">${icLabel}</span>
                <button class="btn btn-outline-primary dim-btn-xs dim-add-param-btn" data-ic-id="${icId || ''}">+ Param</button>
            </div>`;

            group.params.forEach(function (param) {
                const hasLimits = param.orig_dim_min !== null || param.orig_dim_max !== null;
                const fcBadge  = (isFc && hasLimits) ? '<span class="badge text-bg-success dim-spec-fits-badge">F&C</span>' : '';
                const reqBadge = param.is_required ? '<span class="badge text-bg-secondary dim-spec-fits-badge">req</span>' : '';
                const codes    = param.codes || [];
                const rules    = param.repair_rules || [];

                html += `<div class="dim-comp-section">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="dim-spec-label">${escHtml(param.description || '')}</span>
                        <div class="d-flex gap-1 align-items-center">
                            ${fcBadge}${reqBadge}
                            <button class="btn btn-link btn-sm p-0 ms-1 dim-param-edit-btn" data-param-id="${param.id}" style="font-size:11px;color:var(--bs-secondary-color)">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    </div>`;

                if (hasLimits) {
                    html += `<div class="dim-dim-row">
                        <div class="dim-dim-cell"><div class="dim-dim-cell-label">orig min</div><div class="dim-dim-cell-val">${fmtDim(param.orig_dim_min)}</div></div>
                        <div class="dim-dim-cell"><div class="dim-dim-cell-label">orig max</div><div class="dim-dim-cell-val">${fmtDim(param.orig_dim_max)}</div></div>
                        ${param.wear_dim_min !== null ? `
                        <div class="dim-dim-cell" style="background:rgba(255,193,7,.08)"><div class="dim-dim-cell-label">wear min</div><div class="dim-dim-cell-val">${fmtDim(param.wear_dim_min)}</div></div>
                        <div class="dim-dim-cell" style="background:rgba(255,193,7,.08)"><div class="dim-dim-cell-label">wear max</div><div class="dim-dim-cell-val">${fmtDim(param.wear_dim_max)}</div></div>` : ''}
                    </div>`;
                }

                if (codes.length > 0) {
                    html += `<div class="mt-1">` +
                        codes.map(function (c) {
                            return `<span class="badge text-bg-secondary me-1" style="font-size:10px;font-weight:500">${escHtml(c.name || '')}</span>`;
                        }).join('') +
                        `</div>`;
                }

                if (param.inspection) {
                    html += `<div style="font-size:11px;color:var(--bs-secondary-color);margin-top:2px">${escHtml(param.inspection)}</div>`;
                }

                // Repair rules
                if (rules.length > 0) {
                    html += `<div class="mt-1" style="border-top:1px solid rgba(0,0,0,.08);padding-top:4px">`;
                    rules.forEach(function (r) {
                        const al     = r.order_replacement ? 'Order new' : 'Repair';
                        const pc     = (r.processes || []).length;
                        const pcTxt  = pc > 0 ? ` · ${pc} proc.` : '';
                        const nm     = escHtml(r.name || '—');

                        const trigList      = r.triggers || [];
                        const dimTrigTypes  = ['below_orig','above_orig','below_wear','above_wear'];
                        const measTrigs     = trigList.filter(function (t) { return dimTrigTypes.includes(t.trigger); });
                        const measFindTrigs = trigList.filter(function (t) { return t.trigger === 'finding_measurement'; });
                        const inspFindTrigs = trigList.filter(function (t) { return t.trigger === 'finding_inspection' || t.trigger === 'finding'; });

                        const measNames = measTrigs.map(function (t) {
                            return `<span style="font-weight:600;color:var(--bs-body-color)">${escHtml(TRIGGER_LABELS[t.trigger] || t.trigger)}</span>`;
                        }).join(', ');
                        const measRow = measTrigs.length > 0
                            ? `<div style="padding-left:16px;font-size:11px;color:var(--bs-secondary-color);margin-top:2px">Measurement · ${measNames}</div>`
                            : '';

                        const measFindNames = measFindTrigs.map(function (t) {
                            return t.code_name ? `<span style="font-weight:600;color:var(--bs-body-color)">${escHtml(t.code_name)}</span>` : '<span style="font-style:italic">any</span>';
                        }).join(', ');
                        const measFindRow = measFindTrigs.length > 0
                            ? `<div style="padding-left:16px;font-size:11px;color:var(--bs-secondary-color);margin-top:1px">Meas. Finding · ${measFindNames}</div>`
                            : '';

                        const inspFindNames = inspFindTrigs.map(function (t) {
                            return t.code_name ? `<span style="font-weight:600;color:var(--bs-body-color)">${escHtml(t.code_name)}</span>` : '<span style="font-style:italic">any</span>';
                        }).join(', ');
                        const findRow = inspFindTrigs.length > 0
                            ? `<div style="padding-left:16px;font-size:11px;color:var(--bs-secondary-color);margin-top:1px">Insp. Finding · ${inspFindNames}</div>`
                            : '';

                        html += `<div class="dim-rule-row" data-rule-id="${r.id}">
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="font-size:12px;font-weight:700">${nm} <span style="font-weight:400;color:var(--bs-secondary-color)">(${al}${pcTxt})</span></span>
                                <button class="btn btn-link btn-sm p-0 dim-rule-edit-btn" data-rule-id="${r.id}" data-param-id="${param.id}" style="font-size:11px;color:var(--bs-secondary-color)"><i class="bi bi-pencil"></i></button>
                            </div>
                            ${measRow}${measFindRow}${findRow}
                        </div>`;
                    });
                    html += `</div>`;
                }

                html += `<button class="btn btn-outline-secondary dim-btn-xs mt-1 dim-add-rule-btn" data-param-id="${param.id}" style="width:100%">+ Add rule</button>
                </div>`;
            });

            card.innerHTML = html;

            card.querySelectorAll('.dim-param-edit-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const pid = parseInt(btn.dataset.paramId);
                    const param = parameters.find(function (p) { return p.id === pid; });
                    if (param) openEditParamModal(param);
                });
            });

            card.querySelector('.dim-add-param-btn').addEventListener('click', function () {
                openAddParamModal(icId);
            });

            card.querySelectorAll('.dim-add-rule-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const pid = parseInt(btn.dataset.paramId);
                    const param = parameters.find(function (p) { return p.id === pid; });
                    if (param) openAddRuleModal(param);
                });
            });

            card.querySelectorAll('.dim-rule-edit-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const ruleId = parseInt(btn.dataset.ruleId);
                    const pid    = parseInt(btn.dataset.paramId);
                    const param  = parameters.find(function (p) { return p.id === pid; });
                    if (!param) return;
                    const rule = (param.repair_rules || []).find(function (r) { return r.id === ruleId; });
                    if (rule) openEditRuleModal(rule, param);
                });
            });

            specsList.appendChild(card);
        });
    }

    function openAddParamModal(inspCompId) {
        if (!activePoint) return;
        document.getElementById('dimSpecId').value          = '';
        document.getElementById('dimSpecDescription').value = '';
        document.getElementById('dimSpecRequired').checked  = true;
        document.getElementById('dimSpecOrigMin').value     = '';
        document.getElementById('dimSpecOrigMax').value     = '';
        document.getElementById('dimSpecWearMin').value     = '';
        document.getElementById('dimSpecWearMax').value     = '';
        document.getElementById('dimSpecInspection').value  = '';
        document.getElementById('dimSpecSort').value        = '0';
        dimSpecCodes = [];
        renderSpecCodesList();
        refreshSpecComponentSelect(inspCompId || '');
        document.getElementById('dimSpecModalTitle').textContent = 'Add Parameter — ' + activePoint.code;
        document.getElementById('dimSpecDeleteBtn').classList.add('d-none');
        document.getElementById('dimSpecDetachBtn').classList.add('d-none');
        document.getElementById('dimSpecError').classList.add('d-none');
        specModal.show();
    }

    function openEditParamModal(param) {
        document.getElementById('dimSpecId').value          = param.id;
        document.getElementById('dimSpecDescription').value = param.description || '';
        refreshSpecComponentSelect(param.inspection_component_id || '');
        document.getElementById('dimSpecRequired').checked  = !!param.is_required;
        document.getElementById('dimSpecOrigMin').value     = param.orig_dim_min || '';
        document.getElementById('dimSpecOrigMax').value     = param.orig_dim_max || '';
        document.getElementById('dimSpecWearMin').value     = param.wear_dim_min || '';
        document.getElementById('dimSpecWearMax').value     = param.wear_dim_max || '';
        document.getElementById('dimSpecInspection').value  = param.inspection || '';
        document.getElementById('dimSpecSort').value        = param.sort_order || '0';
        dimSpecCodes = (param.codes || []).map(function (c) { return { id: c.codes_id, name: c.name || '', finding_context: c.finding_context || 'inspection' }; });
        renderSpecCodesList();
        document.getElementById('dimSpecModalTitle').textContent = 'Edit Parameter — ' + (param.description || '');
        document.getElementById('dimSpecDeleteBtn').classList.remove('d-none');
        const multiPoint = (param.point_ids || []).length > 1;
        document.getElementById('dimSpecDetachBtn').classList.toggle('d-none', !multiPoint);
        document.getElementById('dimSpecError').classList.add('d-none');
        // Load repair steps for the first point of this parameter
        closeRepairStepForm();
        const firstPointId = (param.point_ids || [])[0] || null;
        loadRepairSteps(firstPointId, param.id);
        specModal.show();
    }

    // ==========================
    // Repair Steps (inside Edit Parameter modal)
    // ==========================
    let dimRsSteps   = [];   // loaded steps for active point
    let dimRsPointId = null; // current point id
    let dimRsParamId = null; // current parameter id (for dims)
    let dimRsIplTimer = null;

    function fmtDim4(v) { return v != null ? parseFloat(v).toFixed(4) : '—'; }

    function renderRepairSteps() {
        const list = document.getElementById('dimRepairStepsList');
        if (!list) return;
        if (!dimRsSteps.length) {
            list.innerHTML = '<div class="text-secondary" style="font-size:11px">No repair steps yet</div>';
            return;
        }
        list.innerHTML = dimRsSteps.map(function (s) {
            const dim = (s.dims || []).find(function (d) { return d.manual_parameter_id == dimRsParamId; });
            const dimStr = dim ? (fmtDim4(dim.dim_min) + ' – ' + fmtDim4(dim.dim_max)) : '—';
            const compStr = s.component ? (s.component.ipl_num + ' ' + (s.component.part_number || '')) : '—';
            return `<div class="d-flex align-items-center gap-2 py-1 border-bottom" style="font-size:12px">
                <span class="fw-semibold text-info" style="min-width:36px">${escHtml(s.step_no)}</span>
                <span class="font-monospace flex-grow-1">${escHtml(dimStr)}</span>
                <span class="text-secondary" style="font-size:11px">${escHtml(compStr)}</span>
                <button type="button" class="btn btn-link btn-sm p-0 dim-rs-edit-btn" data-id="${s.id}" title="Edit" style="color:var(--bs-secondary-color);font-size:11px"><i class="bi bi-pencil"></i></button>
                <button type="button" class="btn btn-link btn-sm p-0 dim-rs-del-btn" data-id="${s.id}" title="Delete" style="color:#dc3545;font-size:11px"><i class="bi bi-trash3"></i></button>
            </div>`;
        }).join('');
        list.querySelectorAll('.dim-rs-edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { openRepairStepForm(parseInt(btn.dataset.id)); });
        });
        list.querySelectorAll('.dim-rs-del-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                if (!confirm('Delete repair step?')) return;
                try {
                    await apiFetch('/repair-steps/' + btn.dataset.id, { method: 'DELETE' });
                    dimRsSteps = dimRsSteps.filter(function (s) { return s.id != btn.dataset.id; });
                    renderRepairSteps();
                } catch (e) { alert(e.message); }
            });
        });
    }

    async function loadRepairSteps(pointId, paramId) {
        dimRsPointId = pointId;
        dimRsParamId = paramId;
        dimRsSteps   = [];
        renderRepairSteps();
        if (!pointId) return;
        try {
            dimRsSteps = await apiFetch('/dimension-points/' + pointId + '/repair-steps');
            renderRepairSteps();
        } catch (e) { console.error('loadRepairSteps', e); }
    }

    function openRepairStepForm(editId) {
        const form = document.getElementById('dimRepairStepForm');
        const err  = document.getElementById('dimRsErr');
        err.classList.add('d-none');
        document.getElementById('dimRsCompList').style.display = 'none';
        document.getElementById('dimRsCompList').innerHTML = '';

        if (editId) {
            const s   = dimRsSteps.find(function (x) { return x.id === editId; });
            const dim = s ? (s.dims || []).find(function (d) { return d.manual_parameter_id == dimRsParamId; }) : null;
            document.getElementById('dimRsEditId').value  = editId;
            document.getElementById('dimRsStepNo').value  = s ? s.step_no : '';
            document.getElementById('dimRsDimMin').value  = dim ? (dim.dim_min || '') : '';
            document.getElementById('dimRsDimMax').value  = dim ? (dim.dim_max || '') : '';
            document.getElementById('dimRsIpl').value     = s && s.component ? s.component.ipl_num : '';
            document.getElementById('dimRsComponentId').value = s && s.component ? s.component.id : '';
            document.getElementById('dimRsCompInfo').textContent = s && s.component
                ? (s.component.ipl_num + ' — ' + (s.component.part_number || '') + (s.component.name ? ' ' + s.component.name : ''))
                : '';
        } else {
            document.getElementById('dimRsEditId').value  = '';
            document.getElementById('dimRsStepNo').value  = '';
            document.getElementById('dimRsDimMin').value  = '';
            document.getElementById('dimRsDimMax').value  = '';
            document.getElementById('dimRsIpl').value     = '';
            document.getElementById('dimRsComponentId').value = '';
            document.getElementById('dimRsCompInfo').textContent = '';
        }
        form.classList.remove('d-none');
        document.getElementById('dimAddRepairStepBtn').classList.add('d-none');
        document.getElementById('dimRsStepNo').focus();
    }

    function closeRepairStepForm() {
        document.getElementById('dimRepairStepForm').classList.add('d-none');
        document.getElementById('dimAddRepairStepBtn').classList.remove('d-none');
    }

    document.getElementById('dimAddRepairStepBtn').addEventListener('click', function () {
        openRepairStepForm(null);
    });

    document.getElementById('dimRsCancelBtn').addEventListener('click', closeRepairStepForm);

    // IPL# autocomplete for component in repair step form
    document.getElementById('dimRsIpl').addEventListener('input', function () {
        clearTimeout(dimRsIplTimer);
        const val = this.value.trim();
        document.getElementById('dimRsComponentId').value = '';
        document.getElementById('dimRsCompInfo').textContent = '';
        if (!val) { document.getElementById('dimRsCompList').style.display = 'none'; return; }
        dimRsIplTimer = setTimeout(async function () {
            try {
                const items = await apiFetch('/manuals/' + MANUAL_ID + '/inspection-components/component-search?ipl_num=' + encodeURIComponent(val));
                const list  = document.getElementById('dimRsCompList');
                list.innerHTML = '';
                if (!items || !items.length) {
                    document.getElementById('dimRsCompInfo').textContent = 'Not found';
                    list.style.display = 'none';
                } else if (items.length === 1) {
                    selectRsComponent(items[0]);
                } else {
                    items.forEach(function (c) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action py-1 px-2';
                        btn.innerHTML = '<span class="fw-semibold">' + escHtml(c.ipl_num) + '</span> <span class="text-secondary">' + escHtml(c.part_number || '') + '</span>';
                        btn.addEventListener('click', function () { selectRsComponent(c); });
                        list.appendChild(btn);
                    });
                    list.style.display = 'block';
                }
            } catch (e) {}
        }, 350);
    });

    function selectRsComponent(c) {
        document.getElementById('dimRsIpl').value          = c.ipl_num;
        document.getElementById('dimRsComponentId').value  = c.id;
        document.getElementById('dimRsCompInfo').textContent = c.ipl_num + ' — ' + (c.part_number || '') + (c.name ? ' ' + c.name : '');
        document.getElementById('dimRsCompList').style.display = 'none';
        document.getElementById('dimRsCompList').innerHTML  = '';
    }

    document.getElementById('dimRsSaveBtn').addEventListener('click', async function () {
        const err    = document.getElementById('dimRsErr');
        err.classList.add('d-none');
        const editId = document.getElementById('dimRsEditId').value;
        const stepNo = document.getElementById('dimRsStepNo').value.trim();
        const dimMin = document.getElementById('dimRsDimMin').value;
        const dimMax = document.getElementById('dimRsDimMax').value;
        const compId = document.getElementById('dimRsComponentId').value || null;

        if (!stepNo) { err.textContent = 'Step No. is required.'; err.classList.remove('d-none'); return; }
        if (!dimRsPointId) { err.textContent = 'No point selected.'; err.classList.remove('d-none'); return; }

        this.disabled = true;
        try {
            let saved;
            const body = {
                step_no:      stepNo,
                component_id: compId ? parseInt(compId) : null,
                dims: [{
                    manual_parameter_id: dimRsParamId,
                    dim_min: dimMin !== '' ? parseFloat(dimMin) : null,
                    dim_max: dimMax !== '' ? parseFloat(dimMax) : null,
                }],
            };
            if (editId) {
                saved = await apiFetch('/repair-steps/' + editId, { method: 'PATCH', body: JSON.stringify(body) });
                const idx = dimRsSteps.findIndex(function (s) { return s.id == editId; });
                if (idx !== -1) dimRsSteps[idx] = saved;
            } else {
                saved = await apiFetch('/dimension-points/' + dimRsPointId + '/repair-steps', { method: 'POST', body: JSON.stringify(body) });
                dimRsSteps.push(saved);
            }
            renderRepairSteps();
            closeRepairStepForm();
        } catch (e) {
            err.textContent = e.message;
            err.classList.remove('d-none');
        } finally {
            this.disabled = false;
        }
    });

    // ==========================
    // Repair Rule modal
    // ==========================
    let dimRuleProcesses = [];
    let dimRuleTriggers  = [];
    let activeRuleParam  = null;
    const ruleModal = new bootstrap.Modal(document.getElementById('dimRepairRuleModal'));

    function dimProcessLabel(manualProcessId) {
        const p = DIM_PROCESSES.find(function (x) { return x.id === manualProcessId; });
        return p ? p.label : '?';
    }

    function renderRuleProcessList() {
        const wrap = document.getElementById('dimRuleProcessList');
        if (!wrap) return;
        if (dimRuleProcesses.length === 0) {
            wrap.innerHTML = '<div class="text-secondary" style="font-size:11px">No processes added</div>';
            return;
        }
        wrap.innerHTML = dimRuleProcesses.map(function (p, i) {
            return `<div class="dim-rule-process-item">
                <span class="text-secondary me-1" style="min-width:14px">${i + 1}.</span>
                <span class="flex-grow-1">${escHtml(p.label)}</span>
                <button type="button" class="btn btn-link btn-sm p-0 ms-1 dim-rule-proc-remove" data-idx="${i}" style="font-size:11px;color:var(--bs-secondary-color)">
                    <i class="bi bi-x"></i>
                </button>
            </div>`;
        }).join('');
        wrap.querySelectorAll('.dim-rule-proc-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                dimRuleProcesses.splice(parseInt(btn.dataset.idx), 1);
                renderRuleProcessList();
            });
        });
    }

    const TRIGGER_LABELS = {
        below_orig:          'Below orig min',
        above_orig:          'Above orig max',
        below_wear:          'Below wear min',
        above_wear:          'Above wear max',
        finding_measurement: 'Finding — Measurement',
        finding_inspection:  'Finding — Inspection',
        finding:             'Finding',
        manual:              'Manual',
    };

    function renderRuleTriggerList() {
        const wrap = document.getElementById('dimRuleTriggerList');
        if (!wrap) return;
        if (dimRuleTriggers.length === 0) {
            wrap.innerHTML = '<div class="text-secondary" style="font-size:11px">No triggers — add at least one</div>';
            return;
        }
        wrap.innerHTML = dimRuleTriggers.map(function (t, i) {
            const tl = TRIGGER_LABELS[t.trigger] || t.trigger;
            const isFindTrigger = t.trigger === 'finding' || t.trigger === 'finding_measurement' || t.trigger === 'finding_inspection';
            const cl = t.code_name ? ' · ' + escHtml(t.code_name) : (isFindTrigger ? ' · any defect' : '');
            return `<div class="dim-rule-process-item">
                <span class="flex-grow-1" style="font-size:12px">${escHtml(tl)}${cl}</span>
                <button type="button" class="btn btn-link btn-sm p-0 ms-1 dim-rule-trig-remove" data-idx="${i}" style="font-size:11px;color:var(--bs-secondary-color)">
                    <i class="bi bi-x"></i>
                </button>
            </div>`;
        }).join('');
        wrap.querySelectorAll('.dim-rule-trig-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                dimRuleTriggers.splice(parseInt(btn.dataset.idx), 1);
                renderRuleTriggerList();
            });
        });
    }

    function fillTriggerCodeSelect(param) {
        const sel = document.getElementById('dimRuleTriggerCode');
        sel.innerHTML = '<option value="">— Any defect —</option>';
        (param ? (param.codes || []) : []).forEach(function (c) {
            if (!c.name) return;
            const opt = document.createElement('option');
            opt.value = c.codes_id;
            opt.textContent = c.name;
            sel.appendChild(opt);
        });
    }

    function openAddRuleModal(param) {
        activeRuleParam  = param;
        dimRuleProcesses = [];
        dimRuleTriggers  = [];
        document.getElementById('dimRuleId').value      = '';
        document.getElementById('dimRuleParamId').value = param.id;
        document.getElementById('dimRuleName').value    = '';
        document.getElementById('dimRuleActionRepair').checked = true;
        document.getElementById('dimRuleNotes').value   = '';
        document.getElementById('dimRuleTriggerSel').value = '';
        document.getElementById('dimRuleTriggerCode').classList.add('d-none');
        document.getElementById('dimRuleTriggerAddBtn').classList.add('d-none');
        document.getElementById('dimRuleError').classList.add('d-none');
        document.getElementById('dimRuleDeleteBtn').classList.add('d-none');
        document.getElementById('dimRepairRuleModalTitle').textContent = 'Add Rule · ' + (param.description || '');
        fillTriggerCodeSelect(param);
        renderRuleTriggerList();
        renderRuleProcessList();
        updateProcOptButtons();
        resetRuleProcessPicker();
        ruleModal.show();
    }

    function openEditRuleModal(rule, param) {
        activeRuleParam  = param;
        dimRuleProcesses = (rule.processes || []).slice().sort(function (a, b) {
            return (a.sort_order || 0) - (b.sort_order || 0);
        }).map(function (p) {
            return { manual_process_id: p.manual_process_id, label: p.label || dimProcessLabel(p.manual_process_id) };
        });
        dimRuleTriggers = (rule.triggers || []).map(function (t) {
            return { trigger: t.trigger, codes_id: t.codes_id || null, code_name: t.code_name || null };
        });
        document.getElementById('dimRuleId').value      = rule.id;
        document.getElementById('dimRuleParamId').value = param.id;
        document.getElementById('dimRuleName').value    = rule.name || '';
        document.getElementById(rule.order_replacement ? 'dimRuleActionReplace' : 'dimRuleActionRepair').checked = true;
        document.getElementById('dimRuleNotes').value   = rule.notes || '';
        document.getElementById('dimRuleTriggerSel').value = '';
        document.getElementById('dimRuleTriggerCode').classList.add('d-none');
        document.getElementById('dimRuleTriggerAddBtn').classList.add('d-none');
        document.getElementById('dimRuleError').classList.add('d-none');
        document.getElementById('dimRuleDeleteBtn').classList.remove('d-none');
        document.getElementById('dimRepairRuleModalTitle').textContent = 'Edit Rule · ' + (param.description || '');
        fillTriggerCodeSelect(param);
        renderRuleTriggerList();
        renderRuleProcessList();
        updateProcOptButtons();
        resetRuleProcessPicker();
        ruleModal.show();
    }

    document.getElementById('dimRuleTriggerSel').addEventListener('change', function () {
        const val      = this.value;
        const codeEl   = document.getElementById('dimRuleTriggerCode');
        const addBtn   = document.getElementById('dimRuleTriggerAddBtn');
        const isFinding = val === 'finding' || val === 'finding_measurement' || val === 'finding_inspection';
        codeEl.classList.toggle('d-none', !isFinding);
        addBtn.classList.toggle('d-none', !val);
    });

    document.getElementById('dimRuleTriggerAddBtn').addEventListener('click', function () {
        const triggerVal = document.getElementById('dimRuleTriggerSel').value;
        if (!triggerVal) return;
        const isFinding  = triggerVal === 'finding' || triggerVal === 'finding_measurement' || triggerVal === 'finding_inspection';
        const codesIdVal = isFinding ? (document.getElementById('dimRuleTriggerCode').value || null) : null;
        const codeName   = isFinding && codesIdVal
            ? (document.getElementById('dimRuleTriggerCode').options[document.getElementById('dimRuleTriggerCode').selectedIndex]?.text || null)
            : null;
        dimRuleTriggers.push({ trigger: triggerVal, codes_id: codesIdVal ? parseInt(codesIdVal) : null, code_name: codeName });
        document.getElementById('dimRuleTriggerSel').value = '';
        document.getElementById('dimRuleTriggerCode').classList.add('d-none');
        document.getElementById('dimRuleTriggerAddBtn').classList.add('d-none');
        renderRuleTriggerList();
    });

    function resetRuleProcessPicker() {
        $('#dimRuleProcessName').val(null).trigger('change');
        const wrap = document.getElementById('dimRuleProcessOptions');
        if (wrap) { wrap.classList.add('d-none'); wrap.innerHTML = ''; }
    }

    document.getElementById('dimRuleSaveBtn').addEventListener('click', async function () {
        const errEl = document.getElementById('dimRuleError');
        errEl.classList.add('d-none');
        const id      = document.getElementById('dimRuleId').value;
        const paramId = document.getElementById('dimRuleParamId').value;
        const notes   = document.getElementById('dimRuleNotes').value.trim();

        if (!paramId) { errEl.textContent = 'No parameter selected.'; errEl.classList.remove('d-none'); return; }
        if (dimRuleTriggers.length === 0) { errEl.textContent = 'Add at least one trigger.'; errEl.classList.remove('d-none'); return; }

        const body = {
            name:              document.getElementById('dimRuleName').value.trim() || null,
            order_replacement: document.getElementById('dimRuleActionReplace').checked,
            notes:             notes || null,
            triggers:          dimRuleTriggers.map(function (t) {
                return { trigger: t.trigger, codes_id: t.codes_id || null };
            }),
            processes:         dimRuleProcesses.map(function (p, i) {
                return { manual_process_id: p.manual_process_id, sort_order: i };
            }),
        };

        try {
            let saved;
            if (id) {
                saved = await apiFetch('/parameter-rules/' + id, { method: 'PATCH', body: JSON.stringify(body) });
            } else {
                saved = await apiFetch('/parameters/' + paramId + '/rules', { method: 'POST', body: JSON.stringify(body) });
            }
            // Update local parameters array
            const param = parameters.find(function (p) { return p.id == paramId; });
            if (param) {
                if (id) {
                    const idx = (param.repair_rules || []).findIndex(function (r) { return r.id == id; });
                    if (idx !== -1) param.repair_rules[idx] = saved;
                    else { if (!param.repair_rules) param.repair_rules = []; param.repair_rules.push(saved); }
                } else {
                    if (!param.repair_rules) param.repair_rules = [];
                    param.repair_rules.push(saved);
                }
            }
            ruleModal.hide();
            if (activePoint) renderSpecsPanel(activePoint);
        } catch (e) {
            errEl.textContent = e.message;
            errEl.classList.remove('d-none');
        }
    });

    document.getElementById('dimRuleDeleteBtn').addEventListener('click', async function () {
        const id      = document.getElementById('dimRuleId').value;
        const paramId = document.getElementById('dimRuleParamId').value;
        if (!id || !confirm('Delete this repair rule?')) return;
        try {
            await apiFetch('/parameter-rules/' + id, { method: 'DELETE' });
            const param = parameters.find(function (p) { return p.id == paramId; });
            if (param) {
                param.repair_rules = (param.repair_rules || []).filter(function (r) { return r.id != id; });
            }
            ruleModal.hide();
            if (activePoint) renderSpecsPanel(activePoint);
        } catch (e) { alert(e.message); }
    });

    // ==========================
    // Figure modal helpers
    // ==========================
    function resetFigureUploadUI() {
        document.getElementById('dimFigureImagePath').value  = '';
        document.getElementById('dimFigureFileInput').value  = '';
        document.getElementById('dimFigureUploadName').textContent = 'No file chosen';
        document.getElementById('dimFigurePreviewWrap').classList.add('d-none');
        document.getElementById('dimFigureUploadProgress').classList.add('d-none');
        document.getElementById('dimFigureWidth').value  = '';
        document.getElementById('dimFigureHeight').value = '';
    }

    function setFigurePreview(url, name) {
        const preview = document.getElementById('dimFigurePreview');
        const wrap    = document.getElementById('dimFigurePreviewWrap');
        preview.src = url;
        wrap.classList.remove('d-none');
        if (name) document.getElementById('dimFigureUploadName').textContent = name;
    }

    // "Choose file" button → trigger hidden input
    document.getElementById('dimFigureUploadBtn').addEventListener('click', function () {
        document.getElementById('dimFigureFileInput').click();
    });

    // File selected → upload immediately
    document.getElementById('dimFigureFileInput').addEventListener('change', async function () {
        const file = this.files[0];
        if (!file) return;
        const progress = document.getElementById('dimFigureUploadProgress');
        const errEl    = document.getElementById('dimFigureError');
        errEl.classList.add('d-none');
        progress.classList.remove('d-none');

        const fd = new FormData();
        fd.append('image', file);
        fd.append('_token', CSRF);

        try {
            const res  = await fetch('/manuals/' + MANUAL_ID + '/dimension-figures/upload-image', { method: 'POST', body: fd });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Upload failed');

            document.getElementById('dimFigureImagePath').value = json.path;
            setFigurePreview(json.path, file.name);
            // detect dimensions client-side
            const img = new Image();
            img.onload = function () {
                document.getElementById('dimFigureWidth').value  = img.naturalWidth;
                document.getElementById('dimFigureHeight').value = img.naturalHeight;
            };
            img.src = json.path;
        } catch (e) {
            errEl.textContent = e.message;
            errEl.classList.remove('d-none');
        } finally {
            progress.classList.add('d-none');
        }
    });

    // ==========================
    // Figure modal open
    // ==========================
    document.getElementById('dimAddFigureBtn').addEventListener('click', function () {
        document.getElementById('dimFigureId').value    = '';
        document.getElementById('dimFigureTitle').value = '';
        document.getElementById('dimFigureType').value  = 'detail';
        document.getElementById('dimFigureSort').value  = '0';
        document.getElementById('dimFigureModalTitle').textContent = 'Add Figure';
        document.getElementById('dimFigureError').classList.add('d-none');
        resetFigureUploadUI();
        populateFigureParentSelect(null);
        figureModal.show();
    });

    document.getElementById('dimEditFigureBtn').addEventListener('click', function () {
        if (!activeFigure) return;
        const fig = activeFigure;
        document.getElementById('dimFigureId').value    = fig.id;
        document.getElementById('dimFigureTitle').value = fig.title;
        document.getElementById('dimFigureType').value  = fig.figure_type;
        document.getElementById('dimFigureSort').value  = fig.sort_order || 0;
        document.getElementById('dimFigureModalTitle').textContent = 'Edit Figure';
        document.getElementById('dimFigureError').classList.add('d-none');
        resetFigureUploadUI();
        document.getElementById('dimFigureImagePath').value = fig.image_path || '';
        document.getElementById('dimFigureWidth').value     = fig.image_width  || '';
        document.getElementById('dimFigureHeight').value    = fig.image_height || '';
        if (fig.image_path) {
            setFigurePreview(fig.image_path, fig.image_path.split('/').pop());
        }
        populateFigureParentSelect(fig.parent_figure_id);
        figureModal.show();
    });

    function populateFigureParentSelect(selectedId) {
        const sel = document.getElementById('dimFigureParent');
        sel.innerHTML = '<option value="">— None —</option>';
        figures.forEach(function (f) {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = f.title;
            if (selectedId && f.id == selectedId) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    document.getElementById('dimFigureSaveBtn').addEventListener('click', async function () {
        const errEl = document.getElementById('dimFigureError');
        errEl.classList.add('d-none');
        const id    = document.getElementById('dimFigureId').value;
        const body  = {
            title:            document.getElementById('dimFigureTitle').value.trim(),
            figure_type:      document.getElementById('dimFigureType').value,
            parent_figure_id: document.getElementById('dimFigureParent').value || null,
            image_path:       document.getElementById('dimFigureImagePath').value.trim(),
            image_width:      parseInt(document.getElementById('dimFigureWidth').value) || null,
            image_height:     parseInt(document.getElementById('dimFigureHeight').value) || null,
            sort_order:       parseInt(document.getElementById('dimFigureSort').value) || 0,
        };
        try {
            let saved;
            if (id) {
                saved = await apiFetch('/dimension-figures/' + id, { method: 'PATCH', body: JSON.stringify(body) });
                const idx = figures.findIndex(f => f.id == id);
                if (idx !== -1) { figures[idx] = Object.assign(figures[idx], saved); }
                if (activeFigure && activeFigure.id == id) activeFigure = figures[idx];
            } else {
                saved = await apiFetch('/manuals/' + MANUAL_ID + '/dimension-figures', { method: 'POST', body: JSON.stringify(body) });
                saved.points = [];
                figures.push(saved);
            }
            figureModal.hide();
            renderFiguresList();
            if (activeFigure && activeFigure.id == saved.id) {
                viewerTitle.textContent = activeFigure.title;
            }
        } catch (e) {
            errEl.textContent = e.message;
            errEl.classList.remove('d-none');
        }
    });

    document.getElementById('dimDeleteFigureBtn').addEventListener('click', async function () {
        if (!activeFigure) return;
        if (!confirm('Delete figure "' + activeFigure.title + '"? All points and specs will be deleted.')) return;
        try {
            await apiFetch('/dimension-figures/' + activeFigure.id, { method: 'DELETE' });
            figures = figures.filter(f => f.id !== activeFigure.id);
            activeFigure = null;
            activePoint  = null;
            renderFiguresList();
            viewerTitle.textContent = 'Select a figure';
            viewerActions.classList.add('d-none');
            emptyState.classList.remove('d-none');
            imgContainer.classList.add('d-none');
            clearSpecsPanel();
        } catch (e) { alert(e.message); }
    });

    // ==========================
    // Point modal
    // ==========================
    function applyPointTypeUI(type) {
        const isMeas = type === 'measurement';
        const isText = type === 'text';
        const isArea = type === 'circle' || type === 'navigation';
        document.getElementById('dimPointCodeWrap').classList.toggle('d-none', isText);
        document.getElementById('dimPointIcWrap').classList.toggle('d-none', !isText);
        document.getElementById('dimPointChildFigureWrap').classList.toggle('d-none', !isArea);
        document.getElementById('dimPointFitsWrap').classList.toggle('d-none', !isMeas);
        if (!isMeas) document.getElementById('dimPointFits').checked = false;
    }

    function openAddPointModal(xPct, yPct, widthPct, heightPct, x2Pct, y2Pct, labelXPct, labelYPct) {
        const isArea = widthPct !== null && widthPct !== undefined;
        const isLine = x2Pct   !== null && x2Pct   !== undefined;
        const ptType = isArea ? 'navigation' : 'measurement';
        const title  = isArea ? 'Add Area' : (isLine ? 'Add Line' : 'Add Point');
        document.getElementById('dimPointId').value            = '';
        document.getElementById('dimPointCode').value          = '';
        document.getElementById('dimPointType').value          = ptType;
        document.getElementById('dimPointDescription').value   = '';
        document.getElementById('dimPointFits').checked        = false;
        document.getElementById('dimPointXPct').value          = xPct;
        document.getElementById('dimPointYPct').value          = yPct;
        document.getElementById('dimPointXDisplay').value      = xPct;
        document.getElementById('dimPointYDisplay').value      = yPct;
        document.getElementById('dimPointWidthDisplay').value  = isArea ? widthPct : '';
        document.getElementById('dimPointHeightDisplay').value = isArea ? heightPct : '';
        document.getElementById('dimPointX2Pct').value         = isLine ? x2Pct : '';
        document.getElementById('dimPointY2Pct').value         = isLine ? y2Pct : '';
        document.getElementById('dimPointX2Display').value     = isLine ? x2Pct : '';
        document.getElementById('dimPointY2Display').value     = isLine ? y2Pct : '';
        document.getElementById('dimPointLabelXPct').value     = labelXPct ?? '';
        document.getElementById('dimPointLabelYPct').value     = labelYPct ?? '';
        document.getElementById('dimPointLabelXDisplay').value = labelXPct ?? '';
        document.getElementById('dimPointLabelYDisplay').value = labelYPct ?? '';
        document.getElementById('dimPointSort').value          = '0';
        document.getElementById('dimPointModalTitle').textContent = title;
        document.getElementById('dimPointDeleteBtn').classList.add('d-none');
        document.getElementById('dimPointError').classList.add('d-none');
        applyPointTypeUI(ptType);
        populateChildFigureSelect(null);
        pointModal.show();
    }

    function openAddCircleModal(cx, cy, rx, ry, labelXPct, labelYPct) {
        document.getElementById('dimPointId').value            = '';
        document.getElementById('dimPointCode').value          = '';
        document.getElementById('dimPointType').value          = 'circle';
        document.getElementById('dimPointDescription').value   = '';
        document.getElementById('dimPointFits').checked        = false;
        document.getElementById('dimPointXPct').value          = cx;
        document.getElementById('dimPointYPct').value          = cy;
        document.getElementById('dimPointXDisplay').value      = cx;
        document.getElementById('dimPointYDisplay').value      = cy;
        document.getElementById('dimPointWidthDisplay').value  = rx;
        document.getElementById('dimPointHeightDisplay').value = ry;
        document.getElementById('dimPointX2Pct').value         = '';
        document.getElementById('dimPointY2Pct').value         = '';
        document.getElementById('dimPointX2Display').value     = '';
        document.getElementById('dimPointY2Display').value     = '';
        document.getElementById('dimPointLabelXPct').value     = labelXPct ?? '';
        document.getElementById('dimPointLabelYPct').value     = labelYPct ?? '';
        document.getElementById('dimPointLabelXDisplay').value = labelXPct ?? '';
        document.getElementById('dimPointLabelYDisplay').value = labelYPct ?? '';
        document.getElementById('dimPointSort').value          = '0';
        document.getElementById('dimPointModalTitle').textContent = 'Add Circle';
        document.getElementById('dimPointDeleteBtn').classList.add('d-none');
        document.getElementById('dimPointError').classList.add('d-none');
        applyPointTypeUI('circle');
        populateChildFigureSelect(null);
        pointModal.show();
    }

    function openEditPointModal(pt) {
        const isLine = pt.x2_pct !== null && pt.x2_pct !== undefined;
        const title  = pt.point_type === 'text'   ? 'Edit Part Label' :
                       pt.point_type === 'circle'  ? 'Edit Circle'     :
                       pt.point_type === 'navigation' ? 'Edit Area'    :
                       isLine ? 'Edit Line' : 'Edit Point';
        document.getElementById('dimPointId').value            = pt.id;
        document.getElementById('dimPointCode').value          = pt.code || '';
        document.getElementById('dimPointType').value          = pt.point_type;
        document.getElementById('dimPointDescription').value   = pt.description || '';
        document.getElementById('dimPointFits').checked        = !!pt.is_fits_clearance;
        document.getElementById('dimPointXPct').value          = pt.x_pct;
        document.getElementById('dimPointYPct').value          = pt.y_pct;
        document.getElementById('dimPointXDisplay').value      = pt.x_pct;
        document.getElementById('dimPointYDisplay').value      = pt.y_pct;
        document.getElementById('dimPointWidthDisplay').value  = pt.width_pct  ?? '';
        document.getElementById('dimPointHeightDisplay').value = pt.height_pct ?? '';
        document.getElementById('dimPointX2Pct').value         = pt.x2_pct     ?? '';
        document.getElementById('dimPointY2Pct').value         = pt.y2_pct     ?? '';
        document.getElementById('dimPointX2Display').value     = pt.x2_pct     ?? '';
        document.getElementById('dimPointY2Display').value     = pt.y2_pct     ?? '';
        document.getElementById('dimPointLabelXPct').value     = pt.label_x_pct ?? '';
        document.getElementById('dimPointLabelYPct').value     = pt.label_y_pct ?? '';
        document.getElementById('dimPointLabelXDisplay').value = pt.label_x_pct ?? '';
        document.getElementById('dimPointLabelYDisplay').value = pt.label_y_pct ?? '';
        document.getElementById('dimPointSort').value          = pt.sort_order || 0;
        document.getElementById('dimPointModalTitle').textContent = title;
        document.getElementById('dimPointDeleteBtn').classList.remove('d-none');
        document.getElementById('dimPointError').classList.add('d-none');
        applyPointTypeUI(pt.point_type);
        populateChildFigureSelect(pt.child_figure_id);
        if (pt.point_type === 'text') populatePointIcSelect(pt.child_ic_id);
        pointModal.show();
    }

    function populatePointIcSelect(selectedId) {
        const $sel = $('#dimPointIcSelect');
        $sel.empty().append('<option value="">— Select part —</option>');
        inspComponents.forEach(function (ic) {
            $sel.append(new Option(ic.label, ic.id, false, String(ic.id) === String(selectedId)));
        });
        $sel.trigger('change');
    }

    function openAddTextModal(dotX, dotY, labelXPct, labelYPct) {
        document.getElementById('dimPointId').value            = '';
        document.getElementById('dimPointCode').value          = '';
        document.getElementById('dimPointType').value          = 'text';
        document.getElementById('dimPointDescription').value   = '';
        document.getElementById('dimPointFits').checked        = false;
        document.getElementById('dimPointXPct').value          = dotX;
        document.getElementById('dimPointYPct').value          = dotY;
        document.getElementById('dimPointXDisplay').value      = dotX;
        document.getElementById('dimPointYDisplay').value      = dotY;
        document.getElementById('dimPointLabelXPct').value     = labelXPct;
        document.getElementById('dimPointLabelYPct').value     = labelYPct;
        document.getElementById('dimPointLabelXDisplay').value = labelXPct;
        document.getElementById('dimPointLabelYDisplay').value = labelYPct;
        document.getElementById('dimPointX2Pct').value         = '';
        document.getElementById('dimPointY2Pct').value         = '';
        document.getElementById('dimPointX2Display').value     = '';
        document.getElementById('dimPointY2Display').value     = '';
        document.getElementById('dimPointWidthDisplay').value  = '';
        document.getElementById('dimPointHeightDisplay').value = '';
        document.getElementById('dimPointSort').value          = '0';
        document.getElementById('dimPointModalTitle').textContent = 'Add Part Label';
        document.getElementById('dimPointDeleteBtn').classList.add('d-none');
        document.getElementById('dimPointError').classList.add('d-none');
        applyPointTypeUI('text');
        populatePointIcSelect(null);
        pointModal.show();
    }

    function populateChildFigureSelect(selectedId) {
        const sel = document.getElementById('dimPointChildFigure');
        sel.innerHTML = '<option value="">— Select figure —</option>';
        figures.forEach(function (f) {
            if (activeFigure && f.id === activeFigure.id) return;
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = f.title;
            if (selectedId && f.id == selectedId) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    document.getElementById('dimPointSaveBtn').addEventListener('click', async function () {
        const errEl = document.getElementById('dimPointError');
        errEl.classList.add('d-none');
        const id      = document.getElementById('dimPointId').value;
        const ptType  = document.getElementById('dimPointType').value;
        const isText  = ptType === 'text';
        const isArea  = ptType === 'circle' || ptType === 'navigation';
        const x2Val   = document.getElementById('dimPointX2Display').value;
        const isLine  = x2Val !== '';
        const wPct    = document.getElementById('dimPointWidthDisplay').value;
        const hPct    = document.getElementById('dimPointHeightDisplay').value;
        const labelXVal = document.getElementById('dimPointLabelXDisplay').value;
        const labelYVal = document.getElementById('dimPointLabelYDisplay').value;
        const body = {
            code:            isText ? null : document.getElementById('dimPointCode').value.trim(),
            point_type:      ptType,
            description:     document.getElementById('dimPointDescription').value.trim() || null,
            child_figure_id: isArea ? (document.getElementById('dimPointChildFigure').value || null) : null,
            child_ic_id:     isText ? ($('#dimPointIcSelect').val() || null) : null,
            x_pct:           parseFloat(document.getElementById('dimPointXDisplay').value),
            y_pct:           parseFloat(document.getElementById('dimPointYDisplay').value),
            width_pct:       isArea && wPct !== '' ? parseFloat(wPct) : null,
            height_pct:      isArea && hPct !== '' ? parseFloat(hPct) : null,
            x2_pct:          isLine ? parseFloat(x2Val) : null,
            y2_pct:          isLine ? parseFloat(document.getElementById('dimPointY2Display').value) : null,
            label_x_pct:     labelXVal !== '' ? parseFloat(labelXVal) : null,
            label_y_pct:     labelYVal !== '' ? parseFloat(labelYVal) : null,
            is_fits_clearance:  document.getElementById('dimPointFits').checked,
            sort_order:         parseInt(document.getElementById('dimPointSort').value) || 0,
        };
        try {
            let saved;
            if (id) {
                saved = await apiFetch('/dimension-points/' + id, { method: 'PATCH', body: JSON.stringify(body) });
                const pts = activeFigure.points || [];
                const idx = pts.findIndex(p => p.id == id);
                if (idx !== -1) { pts[idx] = Object.assign(pts[idx], saved); }
                activeFigure.points = pts;
            } else {
                saved = await apiFetch('/dimension-figures/' + activeFigure.id + '/points', { method: 'POST', body: JSON.stringify(body) });
                // Attach child_ic lookup data so renderTextLabel can display label immediately
                if (saved.point_type === 'text' && saved.child_ic_id) {
                    const ic = inspComponents.find(function (c) { return c.id === saved.child_ic_id; });
                    if (ic) saved._ic_label = ic.label;
                }
                if (!activeFigure.points) activeFigure.points = [];
                activeFigure.points.push(saved);
                deactivateAllModes();
            }
            pointModal.hide();
            renderPoints(activeFigure.points);
        } catch (e) {
            errEl.textContent = e.message;
            errEl.classList.remove('d-none');
        }
    });

    document.getElementById('dimEditPointBtn').addEventListener('click', function () {
        if (activePoint) openEditPointModal(activePoint);
    });

    document.getElementById('dimPointDeleteBtn').addEventListener('click', async function () {
        const id = document.getElementById('dimPointId').value;
        if (!id || !confirm('Delete this point?')) return;
        try {
            await apiFetch('/dimension-points/' + id, { method: 'DELETE' });
            activeFigure.points = (activeFigure.points || []).filter(p => p.id != id);
            if (activePoint && activePoint.id == id) {
                activePoint = null;
                clearSpecsPanel();
            }
            pointModal.hide();
            renderPoints(activeFigure.points);
        } catch (e) { alert(e.message); }
    });

    // ==========================
    // Spec modal
    // ==========================
    document.getElementById('dimAddSpecBtn').addEventListener('click', function () {
        openAddParamModal(null);
    });

    document.getElementById('dimSpecSaveBtn').addEventListener('click', async function () {
        const errEl = document.getElementById('dimSpecError');
        errEl.classList.add('d-none');
        const id   = document.getElementById('dimSpecId').value;
        const desc = document.getElementById('dimSpecDescription').value.trim();
        if (!id && !desc) { errEl.textContent = 'Description is required.'; errEl.classList.remove('d-none'); return; }

        const body = {
            description:             desc || undefined,
            inspection_component_id: $('#dimSpecComponent').val() || null,
            is_required:             document.getElementById('dimSpecRequired').checked,
            orig_dim_min:            document.getElementById('dimSpecOrigMin').value || null,
            orig_dim_max:            document.getElementById('dimSpecOrigMax').value || null,
            wear_dim_min:            document.getElementById('dimSpecWearMin').value || null,
            wear_dim_max:            document.getElementById('dimSpecWearMax').value || null,
            inspection:              document.getElementById('dimSpecInspection').value.trim() || null,
            sort_order:              parseInt(document.getElementById('dimSpecSort').value) || 0,
        };

        try {
            let saved;
            if (id && !document.getElementById('dimSpecModalTitle').textContent.startsWith('Assign')) {
                // Editing existing parameter
                saved = await apiFetch('/parameters/' + id, { method: 'PATCH', body: JSON.stringify(body) });
                // Sync codes: add new, remove deleted
                const param = parameters.find(function (p) { return p.id == id; });
                if (param) {
                    const newIds = dimSpecCodes.map(function (c) { return c.id; });
                    for (const c of dimSpecCodes) {
                        const existing = (param.codes || []).find(function (e) { return e.codes_id === c.id; });
                        if (!existing) {
                            await apiFetch('/parameters/' + id + '/codes', { method: 'POST', body: JSON.stringify({ codes_id: c.id, finding_context: c.finding_context }) });
                        } else if (existing.finding_context !== c.finding_context) {
                            // context changed → delete and re-create
                            await apiFetch('/parameter-codes/' + existing.id, { method: 'DELETE' });
                            await apiFetch('/parameters/' + id + '/codes', { method: 'POST', body: JSON.stringify({ codes_id: c.id, finding_context: c.finding_context }) });
                        }
                    }
                    for (const existing of (param.codes || [])) {
                        if (newIds.indexOf(existing.codes_id) === -1) {
                            await apiFetch('/parameter-codes/' + existing.id, { method: 'DELETE' });
                        }
                    }
                }
                saved = await apiFetch('/parameters/' + id, { method: 'PATCH', body: JSON.stringify(body) });
                const idx = parameters.findIndex(function (p) { return p.id == id; });
                if (idx !== -1) parameters[idx] = saved;
            } else {
                // Create new or assign existing parameter to this point
                body.manual_parameter_id = id || undefined;
                body.codes_ids = dimSpecCodes.map(function (c) { return c.id; });
                saved = await apiFetch('/dimension-points/' + activePoint.id + '/parameters', { method: 'POST', body: JSON.stringify(body) });
                const existing = parameters.find(function (p) { return p.id === saved.id; });
                if (existing) {
                    Object.assign(existing, saved);
                } else {
                    parameters.push(saved);
                }
            }
            specModal.hide();
            if (activePoint) renderSpecsPanel(activePoint);
        } catch (e) {
            errEl.textContent = e.message;
            errEl.classList.remove('d-none');
        }
    });

    document.getElementById('dimSpecDetachBtn').addEventListener('click', async function () {
        const id = document.getElementById('dimSpecId').value;
        if (!id || !activePoint || !confirm('Detach this parameter from point ' + activePoint.code + '?')) return;
        try {
            const result = await apiFetch('/parameters/' + id + '/points/' + activePoint.id, { method: 'DELETE' });
            if (result.deleted) {
                parameters = parameters.filter(function (p) { return p.id != id; });
            } else {
                const param = parameters.find(function (p) { return p.id == id; });
                if (param) {
                    param.point_ids = (param.point_ids || []).filter(function (pid) { return pid !== activePoint.id; });
                }
            }
            specModal.hide();
            renderSpecsPanel(activePoint);
        } catch (e) { alert(e.message); }
    });

    document.getElementById('dimSpecDeleteBtn').addEventListener('click', async function () {
        const id = document.getElementById('dimSpecId').value;
        if (!id || !confirm('Delete this parameter from all points?')) return;
        try {
            await apiFetch('/parameters/' + id, { method: 'DELETE' });
            parameters = parameters.filter(function (p) { return p.id != id; });
            specModal.hide();
            if (activePoint) renderSpecsPanel(activePoint);
        } catch (e) { alert(e.message); }
    });

    // ==========================
    // F&C table builder (called from show.blade.php when user opens the tab)
    // Builds the table from the live `figures` JS array so it reflects unsaved-yet-refreshed data.
    // ==========================
    function fcFmt(v) {
        if (v == null || v === '') return '—';
        return parseFloat(v).toFixed(4);
    }

    window.dimFilterTable = function (filter) {
        var wrap = document.getElementById('fc-table-content-wrap');
        if (!wrap) return;
        wrap.querySelectorAll('[data-dim-filter]').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.dimFilter === filter);
        });
        var simpleSection = wrap.querySelector('#dim-simple-section');
        var fcSection = wrap.querySelector('#dim-fc-section');
        if (filter === 'fc') {
            if (simpleSection) simpleSection.style.display = 'none';
            if (fcSection) fcSection.style.display = '';
        } else {
            if (fcSection) fcSection.style.display = 'none';
            if (simpleSection) simpleSection.style.display = '';
            wrap.querySelectorAll('tr[data-is-fc]').forEach(function (row) {
                var isFc = row.dataset.isFc === '1';
                row.style.display = (filter === 'std' && isFc) ? 'none' : '';
            });
        }
    };

    window.dimPrintTable = function () {
        var wrap = document.getElementById('fc-table-content-wrap');
        if (!wrap) return;

        var printArea = document.createElement('div');
        printArea.id = 'dim-print-area';
        printArea.innerHTML = wrap.innerHTML;
        printArea.querySelectorAll('button, .dim-no-print').forEach(function (el) { el.remove(); });
        document.body.appendChild(printArea);

        var style = document.createElement('style');
        style.id = 'dim-print-style';
        style.textContent =
            '@media print {' +
            '  body > *:not(#dim-print-area) { display: none !important; }' +
            '  #dim-print-area { display: block !important; padding: 20px; }' +
            '}';
        document.head.appendChild(style);

        window.print();

        document.body.removeChild(printArea);
        document.head.removeChild(style);
    };

    window.dimRenderFcTable = function () {
        var diff = function (a, b) {
            return (a != null && b != null)
                ? Math.round((parseFloat(a) - parseFloat(b)) * 1e4) / 1e4 : null;
        };

        // Figure label with parent prefix: "Parent: Child" (same as WO Measurements F&C)
        var figLabel = function (figure) {
            if (!figure) return '';
            var parent = figure.parent_figure_id
                ? figures.find(function (f) { return f.id == figure.parent_figure_id; })
                : null;
            return parent ? (parent.title + ': ' + figure.title) : figure.title;
        };

        // Build pointFigureMap: point_id → {point, figure}
        var pointFigureMap = {};
        figures.forEach(function (figure) {
            (figure.points || []).forEach(function (point) {
                pointFigureMap[point.id] = { point: point, figure: figure };
            });
        });

        // Collect measurement parameters (those with limits) → rows
        var allRows = [];
        var pointParamMap = {}; // point_id → [param, ...]

        parameters.filter(function (p) {
            return p.orig_dim_min !== null || p.orig_dim_max !== null;
        }).forEach(function (param) {
            (param.point_ids || []).forEach(function (pid) {
                var pf = pointFigureMap[pid];
                if (!pf) return;
                allRows.push({ figure: pf.figure, point: pf.point, param: param });
                if (!pointParamMap[pid]) pointParamMap[pid] = [];
                pointParamMap[pid].push(param);
            });
        });

        // Sort rows by figure sort_order then point code
        allRows.sort(function (a, b) {
            var fs = (a.figure.sort_order || 0) - (b.figure.sort_order || 0);
            if (fs !== 0) return fs;
            return String(a.point.code).localeCompare(String(b.point.code));
        });

        // F&C pairs: points with is_fits_clearance and ≥2 params
        var fcPairRows = [];
        var fcSeen = {};
        allRows.forEach(function (r) {
            if (!r.point.is_fits_clearance || fcSeen[r.point.id]) return;
            var ptParams = (pointParamMap[r.point.id] || []);
            if (ptParams.length < 2) return;
            fcSeen[r.point.id] = true;
            // Sort so pA = hole (ID, larger dim) and pB = shaft (OD, smaller dim)
            var sorted = ptParams.slice(0, 2).sort(function (a, b) {
                var aVal = a.orig_dim_max != null ? a.orig_dim_max : (a.wear_dim_max != null ? a.wear_dim_max : 0);
                var bVal = b.orig_dim_max != null ? b.orig_dim_max : (b.wear_dim_max != null ? b.wear_dim_max : 0);
                return bVal - aVal;
            });
            var pA = sorted[0], pB = sorted[1];
            var aWearMin = pA.wear_dim_min != null ? pA.wear_dim_min : pA.orig_dim_min;
            var aWearMax = pA.wear_dim_max != null ? pA.wear_dim_max : pA.orig_dim_max;
            var bWearMin = pB.wear_dim_min != null ? pB.wear_dim_min : pB.orig_dim_min;
            var bWearMax = pB.wear_dim_max != null ? pB.wear_dim_max : pB.orig_dim_max;
            fcPairRows.push({
                figure: r.figure, point: r.point, pA: pA, pB: pB,
                clearOrigMin: diff(pA.orig_dim_min, pB.orig_dim_max),
                clearOrigMax: diff(pA.orig_dim_max, pB.orig_dim_min),
                aWearMin: aWearMin, aWearMax: aWearMax,
                bWearMin: bWearMin, bWearMax: bWearMax,
                permClearMax: diff(aWearMax, bWearMin),
            });
        });

        // ---- header bar ----
        var h = '<div class="p-3">' +
            '<div class="d-flex align-items-center gap-2 mb-3 dim-no-print-wrap">' +
            '<h5 class="mb-0 me-2">Dimensions</h5>' +
            '<div class="btn-group btn-group-sm">' +
            '<button class="btn btn-outline-secondary active" data-dim-filter="all" onclick="window.dimFilterTable(\'all\')">All</button>' +
            '<button class="btn btn-outline-success" data-dim-filter="fc" onclick="window.dimFilterTable(\'fc\')">F&amp;C only</button>' +
            '<button class="btn btn-outline-secondary" data-dim-filter="std" onclick="window.dimFilterTable(\'std\')">Extra</button>' +
            '</div>' +
            '<button class="btn btn-outline-secondary btn-sm ms-auto dim-no-print" onclick="window.dimPrintTable()">&#128438; Print</button>' +
            '</div>';

        // ---- Dimensions table ----
        h += '<div id="dim-simple-section">';
        if (allRows.length === 0) {
            h += '<div class="text-secondary mb-3">No measurement parameters found.</div>';
        } else {
            h += '<div class="table-responsive"><table class="table table-bordered table-sm align-middle" style="font-size:12px;white-space:nowrap">' +
                '<thead class="table-light"><tr>' +
                '<th class="text-center">Figure</th>' +
                '<th class="text-center">Ref. No.</th>' +
                '<th class="text-center">F&amp;C</th>' +
                '<th class="text-center">Component</th>' +
                '<th>Description</th>' +
                '<th colspan="2" class="text-center">Original Limits <span class="fw-normal text-secondary">mm</span></th>' +
                '<th colspan="2" class="text-center">Wear Limits <span class="fw-normal text-secondary">mm</span></th>' +
                '</tr><tr>' +
                '<th></th><th></th><th></th><th></th><th></th>' +
                '<th class="text-center">Min.</th><th class="text-center">Max.</th>' +
                '<th class="text-center">Min.</th><th class="text-center">Max.</th>' +
                '</tr></thead><tbody>';

            allRows.forEach(function (r) {
                var isFc = r.point.is_fits_clearance ? '1' : '0';
                var fcBadge = r.point.is_fits_clearance
                    ? '<span class="badge text-bg-success" style="font-size:10px">F&amp;C</span>' : '';
                var ic = inspComponents.find(function (c) { return c.id === r.param.inspection_component_id; });
                var compLabel = ic ? escHtml(ic.label || '') : '—';
                var wMin = r.param.wear_dim_min != null ? r.param.wear_dim_min : r.param.orig_dim_min;
                var wMax = r.param.wear_dim_max != null ? r.param.wear_dim_max : r.param.orig_dim_max;
                h += '<tr data-is-fc="' + isFc + '">' +
                    '<td class="text-center text-secondary" style="font-size:11px">' + escHtml(figLabel(r.figure)) + '</td>' +
                    '<td class="text-center fw-semibold">' + escHtml(r.point.code) + '</td>' +
                    '<td class="text-center">' + fcBadge + '</td>' +
                    '<td>' + compLabel + '</td>' +
                    '<td>' + escHtml(r.param.description || '') + '</td>' +
                    '<td class="text-end">' + fcFmt(r.param.orig_dim_min) + '</td>' +
                    '<td class="text-end">' + fcFmt(r.param.orig_dim_max) + '</td>' +
                    '<td class="text-end">' + fcFmt(wMin) + '</td>' +
                    '<td class="text-end">' + fcFmt(wMax) + '</td>' +
                    '</tr>';
            });
            h += '</tbody></table></div>';
        }
        h += '</div>';

        // ---- F&C Pairs table ----
        h += '<div id="dim-fc-section" style="display:none">';
        if (fcPairRows.length > 0) {
            h += '<h6 class="mt-4 mb-2">Fits &amp; Clearances</h6>' +
                '<div class="table-responsive"><table class="table table-bordered table-sm align-middle" style="font-size:12px;white-space:nowrap">' +
                '<thead class="table-light">' +
                '<tr><th rowspan="3" class="text-center align-middle">Figure</th>' +
                '<th rowspan="3" class="text-center align-middle">Ref.<br>No.</th>' +
                '<th rowspan="3" class="text-center align-middle">Mating IPL<br>Item No.</th>' +
                '<th colspan="4" class="text-center">Original Manufacturer Limits</th>' +
                '<th colspan="3" class="text-center">In-Service Wear Limits</th></tr><tr>' +
                '<th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">mm</span></th>' +
                '<th colspan="2" class="text-center">Assembly Clearance<br><span class="fw-normal text-secondary">mm</span></th>' +
                '<th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">mm</span></th>' +
                '<th class="text-center">Permitted<br>Clearance<br><span class="fw-normal text-secondary">mm</span></th></tr><tr>' +
                '<th class="text-center">Min.</th><th class="text-center">Max.</th>' +
                '<th class="text-center">Min.</th><th class="text-center">Max.</th>' +
                '<th class="text-center">Min.</th><th class="text-center">Max.</th>' +
                '<th class="text-center">Max.</th></tr></thead><tbody>';

            fcPairRows.forEach(function (r) {
                var icA = inspComponents.find(function (c) { return c.id === r.pA.inspection_component_id; });
                var icB = inspComponents.find(function (c) { return c.id === r.pB.inspection_component_id; });
                var dA = escHtml(r.pA.description || ''), dB = escHtml(r.pB.description || '');
                var iA = icA ? ' <span class="text-secondary">(' + escHtml(icA.label || '') + ')</span>' : '';
                var iB = icB ? ' <span class="text-secondary">(' + escHtml(icB.label || '') + ')</span>' : '';
                var negMin = r.clearOrigMin !== null && r.clearOrigMin < 0 ? ' text-danger' : '';
                var negMax = r.clearOrigMax !== null && r.clearOrigMax < 0 ? ' text-danger' : '';
                var negP   = r.permClearMax !== null && r.permClearMax < 0 ? ' text-danger' : '';
                h += '<tr>' +
                    '<td rowspan="2" class="text-center align-middle text-secondary" style="font-size:11px">' + escHtml(figLabel(r.figure)) + '</td>' +
                    '<td rowspan="2" class="text-center align-middle fw-semibold">' + escHtml(r.point.code) + '</td>' +
                    '<td>' + dA + iA + '</td>' +
                    '<td class="text-end">' + fcFmt(r.pA.orig_dim_min) + '</td>' +
                    '<td class="text-end">' + fcFmt(r.pA.orig_dim_max) + '</td>' +
                    '<td rowspan="2" class="text-end align-middle' + negMin + '">' + fcFmt(r.clearOrigMin) + '</td>' +
                    '<td rowspan="2" class="text-end align-middle' + negMax + '">' + fcFmt(r.clearOrigMax) + '</td>' +
                    '<td class="text-end">' + fcFmt(r.aWearMin) + '</td>' +
                    '<td class="text-end">' + fcFmt(r.aWearMax) + '</td>' +
                    '<td rowspan="2" class="text-end align-middle' + negP + '">' + fcFmt(r.permClearMax) + '</td>' +
                    '</tr><tr>' +
                    '<td>' + dB + iB + '</td>' +
                    '<td class="text-end">' + fcFmt(r.pB.orig_dim_min) + '</td>' +
                    '<td class="text-end">' + fcFmt(r.pB.orig_dim_max) + '</td>' +
                    '<td class="text-end">' + fcFmt(r.bWearMin) + '</td>' +
                    '<td class="text-end">' + fcFmt(r.bWearMax) + '</td>' +
                    '</tr>';
            });
            h += '</tbody></table></div>';
        }
        h += '</div></div>';
        return h;
    };

    // ==========================
    // Init
    // ==========================
    renderFiguresList();
    loadInspComponents();
    loadParameters();
    if (figures.length > 0) selectFigure(figures[0]);

});
</script>
