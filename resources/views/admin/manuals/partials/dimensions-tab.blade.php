{{-- Dimensions tab: figure list (left) + figure viewer with points (right) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"/>
<style>
    #dim-tab-wrap {
        display: flex;
        height: 72vh;
        gap: 0;
        overflow: hidden;
    }
    #dim-figures-panel {
        width: 220px;
        min-width: 220px;
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
    .dim-spec-card {
        border: 1px solid var(--bs-border-color);
        border-radius: 6px;
        padding: 8px;
        margin-bottom: 8px;
        font-size: 12px;
    }
    .dim-spec-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 4px;
    }
    .dim-spec-label {
        font-weight: 600;
        font-size: 12px;
    }
    .dim-spec-fits-badge {
        font-size: 10px;
    }
    .dim-dim-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 4px;
    }
    .dim-dim-cell {
        flex: 1 1 80px;
        background: rgba(0,0,0,.04);
        border-radius: 4px;
        padding: 3px 6px;
    }
    .dim-dim-cell-label { font-size: 10px; color: var(--bs-secondary-color); }
    .dim-dim-cell-val { font-size: 12px; font-weight: 600; font-family: monospace; }
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
        width: 8px;
        height: 8px;
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
        font-size: 11px;
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
    #dim-figure-canvas-wrap.pan-ready { cursor: grab; }
    #dim-figure-canvas-wrap.panning  { cursor: grabbing; }
</style>

@php
    $figuresJson = $dimensionFigures->toJson();
    $componentsJson = $dimensionComponents->toJson();
    $repairProceduresJson = $repairProcedures->toJson();
    $processListJson = $processList->toJson();
    $codesJson = $codes->toJson();
    $manualId = $cmm->id;
    $csrfToken = csrf_token();
@endphp

<div id="dim-tab-wrap" class="m-2">

    {{-- Left: figures list --}}
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
                <i class="bi bi-plus-lg"></i> Add Spec
            </button>
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
                <input type="hidden" id="dimPointXPct" value="">
                <input type="hidden" id="dimPointYPct" value="">
                <input type="hidden" id="dimPointX2Pct" value="">
                <input type="hidden" id="dimPointY2Pct" value="">
                <input type="hidden" id="dimPointLabelXPct" value="">
                <input type="hidden" id="dimPointLabelYPct" value="">
                <div class="row g-2 mb-3">
                    <div class="col">
                        <label class="form-label form-label-sm">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="dimPointCode" placeholder="A1, K, AA1">
                    </div>
                    <div class="col">
                        <label class="form-label form-label-sm">Type</label>
                        <select class="form-select form-select-sm" id="dimPointType">
                            <option value="measurement">Measurement</option>
                            <option value="navigation">Navigation (link to figure)</option>
                            <option value="circle">Circle area</option>
                        </select>
                    </div>
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
                <div class="mb-3 d-none" id="dimPointChildFigureWrap">
                    <label class="form-label form-label-sm">Links to figure</label>
                    <select class="form-select form-select-sm" id="dimPointChildFigure">
                        <option value="">— Select figure —</option>
                    </select>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col">
                        <label class="form-label form-label-sm">X %</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointXDisplay" step="0.01" min="0" max="100">
                    </div>
                    <div class="col">
                        <label class="form-label form-label-sm">Y %</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointYDisplay" step="0.01" min="0" max="100">
                    </div>
                    <div class="col">
                        <label class="form-label form-label-sm">Sort</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointSort" value="0">
                    </div>
                </div>
                <div class="row g-2 mb-2 d-none" id="dimPointLineEndWrap">
                    <div class="col">
                        <label class="form-label form-label-sm">X2 %</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointX2Display" step="0.01" min="0" max="100">
                    </div>
                    <div class="col">
                        <label class="form-label form-label-sm">Y2 %</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointY2Display" step="0.01" min="0" max="100">
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <span class="text-muted" style="font-size:11px;padding-bottom:6px">point 2</span>
                    </div>
                </div>
                <div class="row g-2 mb-2 d-none" id="dimPointLabelWrap">
                    <div class="col">
                        <label class="form-label form-label-sm">Label X %</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointLabelXDisplay" step="0.01" min="0" max="100">
                    </div>
                    <div class="col">
                        <label class="form-label form-label-sm">Label Y %</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointLabelYDisplay" step="0.01" min="0" max="100">
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <span class="text-muted" style="font-size:11px;padding-bottom:6px">ext. label</span>
                    </div>
                </div>
                <div class="row g-2 mb-2 d-none" id="dimPointAreaSizeWrap">
                    <div class="col">
                        <label class="form-label form-label-sm">Width %</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointWidthDisplay" step="0.01" min="0.1" max="100">
                    </div>
                    <div class="col">
                        <label class="form-label form-label-sm">Height %</label>
                        <input type="number" class="form-control form-control-sm" id="dimPointHeightDisplay" step="0.01" min="0.1" max="100">
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <span class="text-muted" style="font-size:11px;padding-bottom:6px">area size</span>
                    </div>
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

{{-- Modal: Add / Edit Spec --}}
<div class="modal fade" id="dimSpecModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dimSpecModalTitle">Add Spec</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dimSpecId" value="">

                {{-- Type toggle --}}
                <div class="mb-3 d-flex align-items-center gap-3">
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="dimSpecType" id="dimSpecTypeMeas" value="measurement" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="dimSpecTypeMeas">Measurement</label>
                        <input type="radio" class="btn-check" name="dimSpecType" id="dimSpecTypeInsp" value="inspection" autocomplete="off">
                        <label class="btn btn-outline-warning" for="dimSpecTypeInsp">Inspection</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="dimSpecRequired" checked>
                        <label class="form-check-label form-label-sm" for="dimSpecRequired">Required</label>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="dimSpecDescription" placeholder="OD, ID, Chrome crack, Corrosion">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Part</label>
                        <select class="w-100" id="dimSpecComponent" style="font-size:13px">
                            <option value="">— None —</option>
                            @foreach($dimensionComponents as $c)
                                <option value="{{ $c->id }}">{{ $c->ipl_num }} — {{ $c->part_number }} {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Inspection: defect code --}}
                <div class="mb-3 d-none" id="dimSpecCodesWrap">
                    <label class="form-label form-label-sm">Defect Code <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="dimSpecCodesId">
                        <option value="">— Select defect type —</option>
                        @foreach($codes as $code)
                            <option value="{{ $code->id }}">{{ $code->code }}{{ $code->description ? ' — '.$code->description : '' }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Measurement: dimensional limits --}}
                <div id="dimSpecMeasWrap">
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold mb-2" style="font-size:12px">Overhaul limits</div>
                        <div class="row g-2 mb-2">
                            <div class="col"><label class="form-label form-label-sm">Min</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecOrigMin" placeholder="0.0000"></div>
                            <div class="col"><label class="form-label form-label-sm">Max</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecOrigMax" placeholder="0.0000"></div>
                        </div>
                        <div class="fw-semibold mb-1" style="font-size:11px;color:var(--bs-secondary-color)">Repair limits (wear) — leave empty to use overhaul</div>
                        <div class="row g-2">
                            <div class="col"><label class="form-label form-label-sm">Min</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecWearMin" placeholder="—"></div>
                            <div class="col"><label class="form-label form-label-sm">Max</label><input type="number" step="0.0001" class="form-control form-control-sm" id="dimSpecWearMax" placeholder="—"></div>
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
                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="dimSpecDeleteBtn">Delete</button>
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
    const MANUAL_ID    = @json($manualId);
    const CSRF         = @json($csrfToken);
    let figures        = @json($dimensionFigures);
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
    const linesSvg          = document.getElementById('dim-lines-svg');

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

    // ---- Spec type toggle ----
    function applySpecType(type) {
        const isMeas = type === 'measurement';
        document.getElementById('dimSpecMeasWrap').classList.toggle('d-none', !isMeas);
        document.getElementById('dimSpecCodesWrap').classList.toggle('d-none', isMeas);
        document.getElementById('dimSpecTypeMeas').checked = isMeas;
        document.getElementById('dimSpecTypeInsp').checked = !isMeas;
    }
    document.querySelectorAll('input[name="dimSpecType"]').forEach(function (r) {
        r.addEventListener('change', function () { applySpecType(this.value); });
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
                if (pt.point_type === 'circle' && pt.width_pct) {
                    areaW = 2 * parseFloat(pt.width_pct)  / 100 * imgW;
                    areaH = 2 * parseFloat(pt.height_pct) / 100 * imgH;
                    cxPx  = parseFloat(pt.x_pct) / 100 * imgW;
                    cyPx  = parseFloat(pt.y_pct) / 100 * imgH;
                } else if (pt.point_type === 'navigation' && pt.width_pct) {
                    areaW = parseFloat(pt.width_pct)  / 100 * imgW;
                    areaH = parseFloat(pt.height_pct) / 100 * imgH;
                    cxPx  = (parseFloat(pt.x_pct) + parseFloat(pt.width_pct)  / 2) / 100 * imgW;
                    cyPx  = (parseFloat(pt.y_pct) + parseFloat(pt.height_pct) / 2) / 100 * imgH;
                } else continue;

                // Center must be within the visible scrolled area
                if (cxPx < scrollL || cxPx > scrollL + wrapW) continue;
                if (cyPx < scrollT || cyPx > scrollT + wrapH) continue;

                const fillW = areaW / wrapW;
                const fillH = areaH / wrapH;
                // Trigger when area is large in EITHER dimension (OR not AND)
                if (fillW > 0.5 || fillH > 0.5) {
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
             cls.includes('dim-callout-dot')  || cls.includes('dim-callout-label'))) return true;
        if (el.dataset && el.dataset.id) return true;
        if (el.closest && el.closest('[data-id]')) return true;
        return false;
    }

    canvasWrap.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return;
        if (addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode) return;
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
            if (e.button !== 0 || addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode) return;
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
        pointsOverlay.innerHTML = '';
        linesSvg.innerHTML = '';
        (points || []).forEach(function (pt) {
            if (pt.point_type === 'circle' && pt.width_pct && pt.height_pct) {
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
            if (justDragged || addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode) return;
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
                if (addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode) return;
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

        // visual line
        const line = document.createElementNS(ns, 'line');
        line.setAttribute('x1', pt.x_pct + '%'); line.setAttribute('y1', pt.y_pct + '%');
        line.setAttribute('x2', pt.x2_pct + '%'); line.setAttribute('y2', pt.y2_pct + '%');
        line.setAttribute('stroke', color); line.setAttribute('stroke-width', '2');
        line.style.pointerEvents = 'none';

        // endpoint circles
        [[pt.x_pct, pt.y_pct], [pt.x2_pct, pt.y2_pct]].forEach(function(ep) {
            const c = document.createElementNS(ns, 'circle');
            c.setAttribute('cx', ep[0] + '%'); c.setAttribute('cy', ep[1] + '%');
            c.setAttribute('r', '5');
            c.setAttribute('fill', color); c.setAttribute('stroke', 'white'); c.setAttribute('stroke-width', '1.5');
            c.style.pointerEvents = 'none';
            g.appendChild(c);
        });

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
            if (e.button !== 0 || addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode) return;
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
            if (justDragged || addPointMode || addCalloutMode || addAreaMode || addLineMode) return;
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
            if (justDragged || addPointMode || addCalloutMode || addAreaMode || addLineMode) return;
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
            if (justDragged || addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode) return;
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
                if (addPointMode || addCalloutMode || addCircleMode || addAreaMode || addLineMode) return;
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
        specsHeader.textContent = pt.code + (pt.description ? ' — ' + pt.description : '');
        specsEmpty.classList.add('d-none');
        specsList.classList.remove('d-none');
        specsFooter.classList.remove('d-none');
        editPointBtn.classList.remove('d-none');

        specsList.innerHTML = '';
        const specs = pt.specs || [];
        if (specs.length === 0) {
            specsList.innerHTML = '<div class="text-secondary text-center py-2" style="font-size:12px">No specs yet</div>';
        }
        specs.forEach(function (spec) {
            const card = document.createElement('div');
            card.className = 'dim-spec-card';
            const isInsp    = spec.spec_type === 'inspection';
            const fitsBadge = (!isInsp && activePoint && activePoint.is_fits_clearance)
                ? '<span class="badge text-bg-success dim-spec-fits-badge">F&C</span>'
                : '';
            const typeBadge = isInsp
                ? '<span class="badge text-bg-warning dim-spec-fits-badge" style="color:#000">Insp</span>'
                : '';
            const reqBadge = spec.is_required
                ? '<span class="badge text-bg-secondary dim-spec-fits-badge">req</span>'
                : '';
            const compLabel = spec.component ? spec.component.ipl_num : '<em class="text-secondary">no component</em>';

            let bodyHtml;
            if (isInsp) {
                const codeLabel = spec.code
                    ? '<span class="fw-semibold">' + escHtml(spec.code.code) + '</span>'
                      + (spec.code.description ? ' <span class="text-secondary">— ' + escHtml(spec.code.description) + '</span>' : '')
                    : '<em class="text-secondary">no defect code</em>';
                bodyHtml = `<div style="font-size:11px;margin-bottom:2px">Component: ${compLabel}</div>
                    <div style="font-size:12px">Defect: ${codeLabel}</div>
                    ${spec.inspection ? '<div style="font-size:11px;color:var(--bs-secondary-color);margin-top:2px">' + escHtml(spec.inspection) + '</div>' : ''}`;
            } else {
                bodyHtml = `<div style="font-size:11px;color:var(--bs-secondary-color);margin-bottom:4px">Component: ${compLabel}</div>
                    <div class="dim-dim-row">
                        <div class="dim-dim-cell">
                            <div class="dim-dim-cell-label">min</div>
                            <div class="dim-dim-cell-val">${fmtDim(spec.orig_dim_min)}</div>
                        </div>
                        <div class="dim-dim-cell">
                            <div class="dim-dim-cell-label">max</div>
                            <div class="dim-dim-cell-val">${fmtDim(spec.orig_dim_max)}</div>
                        </div>
                        ${spec.wear_dim_min !== null ? `
                        <div class="dim-dim-cell" style="background:rgba(255,193,7,.08)">
                            <div class="dim-dim-cell-label">wear min</div>
                            <div class="dim-dim-cell-val">${fmtDim(spec.wear_dim_min)}</div>
                        </div>
                        <div class="dim-dim-cell" style="background:rgba(255,193,7,.08)">
                            <div class="dim-dim-cell-label">wear max</div>
                            <div class="dim-dim-cell-val">${fmtDim(spec.wear_dim_max)}</div>
                        </div>` : ''}
                    </div>`;
            }

            card.innerHTML = `
                <div class="dim-spec-card-header">
                    <span class="dim-spec-label">${escHtml(spec.description)}</span>
                    <div class="d-flex gap-1 align-items-center">
                        ${typeBadge}${fitsBadge}${reqBadge}
                        <button class="btn btn-link btn-sm p-0 ms-1" style="font-size:11px;color:var(--bs-secondary-color)" data-spec-id="${spec.id}" title="Edit spec">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>
                ${bodyHtml}
            `;
            card.querySelector('[data-spec-id]').addEventListener('click', function () {
                openEditSpecModal(spec);
            });
            specsList.appendChild(card);
        });
    }

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
    function openAddPointModal(xPct, yPct, widthPct, heightPct, x2Pct, y2Pct, labelXPct, labelYPct) {
        const isArea    = widthPct   !== null && widthPct   !== undefined;
        const isLine    = x2Pct     !== null && x2Pct     !== undefined;
        const isCallout = labelXPct !== null && labelXPct !== undefined;
        const title = isArea ? 'Add Area' : (isLine ? 'Add Line' : (isCallout ? 'Add Callout' : 'Add Point'));
        document.getElementById('dimPointId').value          = '';
        document.getElementById('dimPointCode').value        = '';
        document.getElementById('dimPointType').value        = isArea ? 'navigation' : 'measurement';
        document.getElementById('dimPointDescription').value = '';
        document.getElementById('dimPointFits').checked      = false;
        document.getElementById('dimPointFitsWrap').classList.toggle('d-none', isArea);
        document.getElementById('dimPointXPct').value        = xPct;
        document.getElementById('dimPointYPct').value        = yPct;
        document.getElementById('dimPointXDisplay').value    = xPct;
        document.getElementById('dimPointYDisplay').value    = yPct;
        document.getElementById('dimPointWidthDisplay').value  = isArea ? widthPct : '';
        document.getElementById('dimPointHeightDisplay').value = isArea ? heightPct : '';
        document.getElementById('dimPointX2Pct').value         = isLine ? x2Pct : '';
        document.getElementById('dimPointY2Pct').value         = isLine ? y2Pct : '';
        document.getElementById('dimPointX2Display').value     = isLine ? x2Pct : '';
        document.getElementById('dimPointY2Display').value     = isLine ? y2Pct : '';
        document.getElementById('dimPointLabelXPct').value     = isCallout ? labelXPct : '';
        document.getElementById('dimPointLabelYPct').value     = isCallout ? labelYPct : '';
        document.getElementById('dimPointLabelXDisplay').value = isCallout ? labelXPct : '';
        document.getElementById('dimPointLabelYDisplay').value = isCallout ? labelYPct : '';
        document.getElementById('dimPointSort').value          = '0';
        document.getElementById('dimPointModalTitle').textContent = title;
        document.getElementById('dimPointDeleteBtn').classList.add('d-none');
        document.getElementById('dimPointError').classList.add('d-none');
        document.getElementById('dimPointChildFigureWrap').classList.toggle('d-none', !isArea);
        document.getElementById('dimPointAreaSizeWrap').classList.toggle('d-none', !isArea);
        document.getElementById('dimPointLineEndWrap').classList.toggle('d-none', !isLine);
        document.getElementById('dimPointLabelWrap').classList.toggle('d-none', !isCallout);
        populateChildFigureSelect(null);
        pointModal.show();
    }

    function openAddCircleModal(cx, cy, rx, ry, labelXPct, labelYPct) {
        document.getElementById('dimPointId').value          = '';
        document.getElementById('dimPointCode').value        = '';
        document.getElementById('dimPointType').value        = 'circle';
        document.getElementById('dimPointDescription').value = '';
        document.getElementById('dimPointFits').checked      = false;
        document.getElementById('dimPointFitsWrap').classList.add('d-none');
        document.getElementById('dimPointXPct').value        = cx;
        document.getElementById('dimPointYPct').value        = cy;
        document.getElementById('dimPointXDisplay').value    = cx;
        document.getElementById('dimPointYDisplay').value    = cy;
        document.getElementById('dimPointWidthDisplay').value  = rx;
        document.getElementById('dimPointHeightDisplay').value = ry;
        document.getElementById('dimPointX2Pct').value         = '';
        document.getElementById('dimPointY2Pct').value         = '';
        document.getElementById('dimPointX2Display').value     = '';
        document.getElementById('dimPointY2Display').value     = '';
        const hasLbl = labelXPct !== null && labelXPct !== undefined;
        document.getElementById('dimPointLabelXPct').value     = hasLbl ? labelXPct : '';
        document.getElementById('dimPointLabelYPct').value     = hasLbl ? labelYPct : '';
        document.getElementById('dimPointLabelXDisplay').value = hasLbl ? labelXPct : '';
        document.getElementById('dimPointLabelYDisplay').value = hasLbl ? labelYPct : '';
        document.getElementById('dimPointSort').value          = '0';
        document.getElementById('dimPointModalTitle').textContent = 'Add Circle';
        document.getElementById('dimPointDeleteBtn').classList.add('d-none');
        document.getElementById('dimPointError').classList.add('d-none');
        document.getElementById('dimPointChildFigureWrap').classList.remove('d-none');
        document.getElementById('dimPointAreaSizeWrap').classList.remove('d-none');
        document.getElementById('dimPointLineEndWrap').classList.add('d-none');
        document.getElementById('dimPointLabelWrap').classList.toggle('d-none', !hasLbl);
        populateChildFigureSelect(null);
        pointModal.show();
    }

    function openEditPointModal(pt) {
        const isCircle  = pt.point_type === 'circle';
        const isArea    = pt.point_type === 'navigation' && pt.width_pct && pt.height_pct;
        const isLine    = pt.x2_pct     !== null && pt.x2_pct     !== undefined;
        const isCallout = pt.label_x_pct !== null && pt.label_x_pct !== undefined;
        const title = isCircle ? 'Edit Circle' : (isArea ? 'Edit Area' : (isLine ? 'Edit Line' : (isCallout ? 'Edit Callout' : 'Edit Point')));
        document.getElementById('dimPointId').value          = pt.id;
        document.getElementById('dimPointCode').value        = pt.code;
        document.getElementById('dimPointType').value        = pt.point_type;
        document.getElementById('dimPointDescription').value = pt.description || '';
        document.getElementById('dimPointFits').checked      = !!pt.is_fits_clearance;
        document.getElementById('dimPointFitsWrap').classList.toggle('d-none', isArea || isCircle);
        document.getElementById('dimPointXPct').value        = pt.x_pct;
        document.getElementById('dimPointYPct').value        = pt.y_pct;
        document.getElementById('dimPointXDisplay').value    = pt.x_pct;
        document.getElementById('dimPointYDisplay').value    = pt.y_pct;
        document.getElementById('dimPointWidthDisplay').value  = pt.width_pct || '';
        document.getElementById('dimPointHeightDisplay').value = pt.height_pct || '';
        document.getElementById('dimPointX2Pct').value         = pt.x2_pct     || '';
        document.getElementById('dimPointY2Pct').value         = pt.y2_pct     || '';
        document.getElementById('dimPointX2Display').value     = pt.x2_pct     || '';
        document.getElementById('dimPointY2Display').value     = pt.y2_pct     || '';
        const hasLabel = isCallout || isArea || isCircle || isLine;
        document.getElementById('dimPointLabelXPct').value     = hasLabel ? (pt.label_x_pct ?? '') : '';
        document.getElementById('dimPointLabelYPct').value     = hasLabel ? (pt.label_y_pct ?? '') : '';
        document.getElementById('dimPointLabelXDisplay').value = hasLabel ? (pt.label_x_pct ?? '') : '';
        document.getElementById('dimPointLabelYDisplay').value = hasLabel ? (pt.label_y_pct ?? '') : '';
        document.getElementById('dimPointSort').value          = pt.sort_order || 0;
        document.getElementById('dimPointModalTitle').textContent = title;
        document.getElementById('dimPointDeleteBtn').classList.remove('d-none');
        document.getElementById('dimPointError').classList.add('d-none');
        document.getElementById('dimPointChildFigureWrap').classList.toggle('d-none', pt.point_type !== 'navigation' && !isCircle);
        document.getElementById('dimPointAreaSizeWrap').classList.toggle('d-none', !isArea && !isCircle);
        document.getElementById('dimPointLineEndWrap').classList.toggle('d-none', !isLine);
        document.getElementById('dimPointLabelWrap').classList.toggle('d-none', !isCallout && !isArea && !isCircle && !isLine);
        populateChildFigureSelect(pt.child_figure_id);
        pointModal.show();
    }

    document.getElementById('dimPointType').addEventListener('change', function () {
        const isNav    = this.value === 'navigation';
        const isCircle = this.value === 'circle';
        const isMeas   = this.value === 'measurement';
        document.getElementById('dimPointChildFigureWrap').classList.toggle('d-none', !isNav && !isCircle);
        document.getElementById('dimPointFitsWrap').classList.toggle('d-none', !isMeas);
        document.getElementById('dimPointAreaSizeWrap').classList.toggle('d-none', !isNav && !isCircle);
        if (!isMeas) document.getElementById('dimPointFits').checked = false;
    });

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
        const id   = document.getElementById('dimPointId').value;
        const ptType    = document.getElementById('dimPointType').value;
        const wPct      = document.getElementById('dimPointWidthDisplay').value;
        const hPct      = document.getElementById('dimPointHeightDisplay').value;
        const x2Val     = document.getElementById('dimPointX2Display').value;
        const y2Val     = document.getElementById('dimPointY2Display').value;
        const areaShown    = !document.getElementById('dimPointAreaSizeWrap').classList.contains('d-none');
        const lineShown    = !document.getElementById('dimPointLineEndWrap').classList.contains('d-none');
        const calloutShown = !document.getElementById('dimPointLabelWrap').classList.contains('d-none');
        const labelXVal    = document.getElementById('dimPointLabelXDisplay').value;
        const labelYVal    = document.getElementById('dimPointLabelYDisplay').value;
        const body = {
            code:            document.getElementById('dimPointCode').value.trim(),
            point_type:      ptType,
            description:     document.getElementById('dimPointDescription').value.trim() || null,
            child_figure_id: (ptType === 'navigation' || ptType === 'circle')
                             ? (document.getElementById('dimPointChildFigure').value || null) : null,
            x_pct:           parseFloat(document.getElementById('dimPointXDisplay').value),
            y_pct:           parseFloat(document.getElementById('dimPointYDisplay').value),
            width_pct:       areaShown && wPct !== '' ? parseFloat(wPct) : null,
            height_pct:      areaShown && hPct !== '' ? parseFloat(hPct) : null,
            x2_pct:          lineShown && x2Val !== '' ? parseFloat(x2Val) : null,
            y2_pct:          lineShown && y2Val !== '' ? parseFloat(y2Val) : null,
            label_x_pct:     calloutShown && labelXVal !== '' ? parseFloat(labelXVal) : null,
            label_y_pct:     calloutShown && labelYVal !== '' ? parseFloat(labelYVal) : null,
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
                saved.specs = [];
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
        if (!id || !confirm('Delete this point and all its specs?')) return;
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
        if (!activePoint) return;
        document.getElementById('dimSpecId').value               = '';
        document.getElementById('dimSpecDescription').value      = '';
        $('#dimSpecComponent').val('').trigger('change');
        document.getElementById('dimSpecRequired').checked       = true;
        document.getElementById('dimSpecOrigMin').value          = '';
        document.getElementById('dimSpecOrigMax').value          = '';
        document.getElementById('dimSpecWearMin').value          = '';
        document.getElementById('dimSpecWearMax').value          = '';
        document.getElementById('dimSpecInspection').value       = '';
        document.getElementById('dimSpecSort').value             = '0';
        document.getElementById('dimSpecCodesId').value          = '';
        applySpecType('measurement');
        document.getElementById('dimSpecModalTitle').textContent = 'Add Spec — ' + activePoint.code;
        document.getElementById('dimSpecDeleteBtn').classList.add('d-none');
        document.getElementById('dimSpecError').classList.add('d-none');
        specModal.show();
    });

    function openEditSpecModal(spec) {
        document.getElementById('dimSpecId').value               = spec.id;
        document.getElementById('dimSpecDescription').value      = spec.description || '';
        $('#dimSpecComponent').val(spec.component_id || '').trigger('change');
        document.getElementById('dimSpecRequired').checked       = !!spec.is_required;
        document.getElementById('dimSpecOrigMin').value          = spec.orig_dim_min || '';
        document.getElementById('dimSpecOrigMax').value          = spec.orig_dim_max || '';
        document.getElementById('dimSpecWearMin').value          = spec.wear_dim_min || '';
        document.getElementById('dimSpecWearMax').value          = spec.wear_dim_max || '';
        document.getElementById('dimSpecInspection').value       = spec.inspection || '';
        document.getElementById('dimSpecSort').value             = spec.sort_order || '0';
        document.getElementById('dimSpecCodesId').value          = spec.codes_id || '';
        applySpecType(spec.spec_type || 'measurement');
        document.getElementById('dimSpecModalTitle').textContent = 'Edit Spec — ' + (activePoint ? activePoint.code : '');
        document.getElementById('dimSpecDeleteBtn').classList.remove('d-none');
        document.getElementById('dimSpecError').classList.add('d-none');
        specModal.show();
    }

    document.getElementById('dimSpecSaveBtn').addEventListener('click', async function () {
        const errEl = document.getElementById('dimSpecError');
        errEl.classList.add('d-none');
        const id       = document.getElementById('dimSpecId').value;
        const specType = document.querySelector('input[name="dimSpecType"]:checked').value;
        const isMeas   = specType === 'measurement';
        const body = {
            spec_type:    specType,
            description:  document.getElementById('dimSpecDescription').value.trim(),
            component_id: document.getElementById('dimSpecComponent').value || null,
            codes_id:     !isMeas ? (document.getElementById('dimSpecCodesId').value || null) : null,
            is_required:  document.getElementById('dimSpecRequired').checked,
            orig_dim_min: isMeas ? (document.getElementById('dimSpecOrigMin').value || null) : null,
            orig_dim_max: isMeas ? (document.getElementById('dimSpecOrigMax').value || null) : null,
            wear_dim_min: isMeas ? (document.getElementById('dimSpecWearMin').value || null) : null,
            wear_dim_max: isMeas ? (document.getElementById('dimSpecWearMax').value || null) : null,
            inspection:   document.getElementById('dimSpecInspection').value.trim() || null,
            sort_order:   parseInt(document.getElementById('dimSpecSort').value) || 0,
        };
        try {
            let saved;
            if (id) {
                saved = await apiFetch('/dimension-specs/' + id, { method: 'PATCH', body: JSON.stringify(body) });
                const idx = activePoint.specs.findIndex(s => s.id == id);
                if (idx !== -1) activePoint.specs[idx] = Object.assign(activePoint.specs[idx], saved);
            } else {
                saved = await apiFetch('/dimension-points/' + activePoint.id + '/specs', { method: 'POST', body: JSON.stringify(body) });
                if (!activePoint.specs) activePoint.specs = [];
                activePoint.specs.push(saved);
            }
            specModal.hide();
            renderSpecsPanel(activePoint);
        } catch (e) {
            errEl.textContent = e.message;
            errEl.classList.remove('d-none');
        }
    });

    document.getElementById('dimSpecDeleteBtn').addEventListener('click', async function () {
        const id = document.getElementById('dimSpecId').value;
        if (!id || !confirm('Delete this spec?')) return;
        try {
            await apiFetch('/dimension-specs/' + id, { method: 'DELETE' });
            activePoint.specs = (activePoint.specs || []).filter(s => s.id != id);
            specModal.hide();
            renderSpecsPanel(activePoint);
        } catch (e) { alert(e.message); }
    });

    // ==========================
    // F&C table builder (called from show.blade.php when user opens the tab)
    // Builds the table from the live `figures` JS array so it reflects unsaved-yet-refreshed data.
    // ==========================
    function fcFmt(v) {
        if (v === null || v === undefined || v === '') return '—';
        return parseFloat(v).toFixed(4);
    }

    window.dimRenderFcTable = function () {
        var rows = [];
        figures.forEach(function (figure) {
            (figure.points || []).forEach(function (point) {
                if (!point.is_fits_clearance) return;
                var specs = (point.specs || []).filter(function (s) {
                    return s.spec_type !== 'inspection';
                }).slice().sort(function (a, b) {
                    return (a.sort_order || 0) - (b.sort_order || 0);
                });
                if (specs.length < 2) return;
                var sA = specs[0], sB = specs[1];

                var fn = function (a, b) {
                    return (a != null && b != null)
                        ? Math.round((parseFloat(a) - parseFloat(b)) * 1e4) / 1e4
                        : null;
                };
                var clearOrigMin = fn(sA.orig_dim_min, sB.orig_dim_max);
                var clearOrigMax = fn(sA.orig_dim_max, sB.orig_dim_min);
                var aWearMin = sA.wear_dim_min != null ? sA.wear_dim_min : sA.orig_dim_min;
                var aWearMax = sA.wear_dim_max != null ? sA.wear_dim_max : sA.orig_dim_max;
                var bWearMin = sB.wear_dim_min != null ? sB.wear_dim_min : sB.orig_dim_min;
                var bWearMax = sB.wear_dim_max != null ? sB.wear_dim_max : sB.orig_dim_max;
                var permClearMax = fn(aWearMax, bWearMin);

                rows.push({ figure: figure, point: point, sA: sA, sB: sB,
                    clearOrigMin: clearOrigMin, clearOrigMax: clearOrigMax,
                    aWearMin: aWearMin, aWearMax: aWearMax,
                    bWearMin: bWearMin, bWearMax: bWearMax,
                    permClearMax: permClearMax });
            });
        });

        if (rows.length === 0) {
            return '<div class="p-3"><h5 class="mb-3">Fits and Clearances</h5>' +
                '<div class="text-secondary">No Fits &amp; Clearances points found. ' +
                'Mark measurement points as F&amp;C in the Dimensions tab.</div></div>';
        }

        var h = '<div class="p-3"><h5 class="mb-3">Fits and Clearances</h5>' +
            '<div class="table-responsive"><table class="table table-bordered table-sm align-middle" ' +
            'style="font-size:12px;white-space:nowrap"><thead class="table-light">' +
            '<tr><th rowspan="3" class="text-center align-middle">Figure</th>' +
            '<th rowspan="3" class="text-center align-middle">Ref.<br>No.</th>' +
            '<th rowspan="3" class="text-center align-middle">Mating IPL<br>Item No.</th>' +
            '<th colspan="4" class="text-center">Original Manufacturer Limits</th>' +
            '<th colspan="3" class="text-center">In-Service Wear Limits</th></tr><tr>' +
            '<th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">mm</span></th>' +
            '<th colspan="2" class="text-center">Assembly<br>Clearance<br><span class="fw-normal text-secondary">mm</span></th>' +
            '<th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">mm</span></th>' +
            '<th class="text-center">Permitted<br>Clearance<br><span class="fw-normal text-secondary">mm</span></th></tr><tr>' +
            '<th class="text-center">Min.</th><th class="text-center">Max.</th>' +
            '<th class="text-center">Min.</th><th class="text-center">Max.</th>' +
            '<th class="text-center">Min.</th><th class="text-center">Max.</th>' +
            '<th class="text-center">Max.</th></tr></thead><tbody>';

        rows.forEach(function (r) {
            var cA = r.sA.component, cB = r.sB.component;
            var dA = escHtml(r.sA.description || '');
            var dB = escHtml(r.sB.description || '');
            var iA = cA ? ' <span class="text-secondary">(' + escHtml(cA.ipl_num || '') + ')</span>' : '';
            var iB = cB ? ' <span class="text-secondary">(' + escHtml(cB.ipl_num || '') + ')</span>' : '';
            var negMin = r.clearOrigMin !== null && r.clearOrigMin < 0 ? ' text-danger' : '';
            var negMax = r.clearOrigMax !== null && r.clearOrigMax < 0 ? ' text-danger' : '';
            var negP   = r.permClearMax !== null && r.permClearMax < 0 ? ' text-danger' : '';

            h += '<tr>' +
                '<td rowspan="2" class="text-center align-middle text-secondary" style="font-size:11px">' + escHtml(r.figure.title) + '</td>' +
                '<td rowspan="2" class="text-center align-middle fw-semibold">' + escHtml(r.point.code) + '</td>' +
                '<td>' + dA + iA + '</td>' +
                '<td class="text-end">' + fcFmt(r.sA.orig_dim_min) + '</td>' +
                '<td class="text-end">' + fcFmt(r.sA.orig_dim_max) + '</td>' +
                '<td rowspan="2" class="text-end align-middle' + negMin + '">' + fcFmt(r.clearOrigMin) + '</td>' +
                '<td rowspan="2" class="text-end align-middle' + negMax + '">' + fcFmt(r.clearOrigMax) + '</td>' +
                '<td class="text-end">' + fcFmt(r.aWearMin) + '</td>' +
                '<td class="text-end">' + fcFmt(r.aWearMax) + '</td>' +
                '<td rowspan="2" class="text-end align-middle' + negP + '">' + fcFmt(r.permClearMax) + '</td>' +
                '</tr><tr>' +
                '<td>' + dB + iB + '</td>' +
                '<td class="text-end">' + fcFmt(r.sB.orig_dim_min) + '</td>' +
                '<td class="text-end">' + fcFmt(r.sB.orig_dim_max) + '</td>' +
                '<td class="text-end">' + fcFmt(r.bWearMin) + '</td>' +
                '<td class="text-end">' + fcFmt(r.bWearMax) + '</td>' +
                '</tr>';
        });

        return h + '</tbody></table></div></div>';
    };

    // ==========================
    // Init
    // ==========================
    renderFiguresList();

});
</script>
