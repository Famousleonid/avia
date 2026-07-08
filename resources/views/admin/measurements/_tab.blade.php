<style>
    #ms-tab-body { display: flex; height: calc(100vh - 280px); min-height: 400px; overflow: hidden; }

    #ms-tab-parts { width: 220px; min-width: 220px; border-right: 1px solid var(--bs-border-color); display: flex; flex-direction: column; overflow: hidden; }
    #ms-tab-parts-list { flex: 1 1 auto; overflow-y: auto; padding: 4px 0; }
    .ms-part-group { border-bottom: 1px solid var(--bs-border-color); }
    .ms-part-header { padding: 5px 8px 5px 10px; display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 11px; font-weight: 600; color: #5ee3ff; user-select: none; }
    .ms-part-header:hover { background: rgba(94,227,255,.06); }
    .ms-part-chevron { font-size: 9px; margin-left: auto; opacity: .6; }
    .ms-part-params { display: none; }
    .ms-part-params.open { display: block; }
    .ms-tab-param-item { padding: 4px 10px 4px 14px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 11px; border-left: 3px solid transparent; min-width: 0; }
    .ms-tab-param-item:hover { background: rgba(13,110,253,.07); }
    .ms-tab-param-item.active { border-left-color: #0d6efd; background: rgba(13,110,253,.12); font-weight: 600; }
    .ms-tab-param-desc { flex: 1 1 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ms-pt-code { font-size: 9px; color: var(--bs-secondary-color); flex-shrink: 0; }
    .ms-sdot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; border: 1.5px solid rgba(255,255,255,.3); }
    .ms-sdot.pass { background:#198754; border-color:#28a745; } .ms-sdot.fail { background:#dc3545; border-color:#e04657; }
    .ms-sdot.partial { background:#ffc107; border-color:#ffca2c; } .ms-sdot.none { background:#6c757d; border-color:#868e96; }
    .ms-pdot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .ms-part-prog { font-size: 9px; color: var(--bs-secondary-color); flex-shrink: 0; }
    .ms-pdot.pass { background:#198754; } .ms-pdot.fail { background:#dc3545; } .ms-pdot.partial { background:#ffc107; } .ms-pdot.none { background:#6c757d; }
    .ms-pdot.missing { background:transparent; border:2px dashed #6c757d; }
    .ms-missing-label { font-size:9px; color:#dc3545; font-weight:600; flex-shrink:0; margin-left:2px; }
    .ms-fc-badge { font-size:9px; padding:1px 4px; background:rgba(20,184,166,.15); color:#0d9488; border-radius:2px; flex-shrink:0; font-weight:600; border:1px solid rgba(20,184,166,.3); }

    #ms-tab-viewer { order: 3; flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }
    #ms-tab-fig-label { padding: 3px 8px; font-size: 10px; color: var(--bs-secondary-color); border-bottom: 1px solid var(--bs-border-color); flex-shrink: 0; }
    #ms-tab-fig-nav { flex-shrink: 0; padding: 3px 6px; border-bottom: 1px solid var(--bs-border-color); display: none; gap: 4px; flex-wrap: wrap; align-items: center; }
    #ms-tab-fig-nav.visible { display: flex; }
    .ms-fig-btn { font-size: 10px; padding: 1px 8px; border-radius: 3px; border: 1px solid var(--bs-border-color); cursor: pointer; background: transparent; color: var(--bs-body-color); white-space: nowrap; line-height: 1.9; }
    .ms-fig-btn:hover { background: rgba(13,110,253,.08); border-color: #0d6efd; color: #0d6efd; }
    .ms-fig-btn.active { background: rgba(13,110,253,.15); border-color: #0d6efd; color: #0d6efd; font-weight: 600; }
    #ms-tab-canvas { flex: 1 1 auto; overflow: hidden; position: relative; background: rgba(0,0,0,.04); cursor: grab; }
    #ms-tab-canvas.grabbing { cursor: grabbing; }
    #ms-tab-fig-container { position: absolute; transform-origin: 0 0; user-select: none; }
    #ms-tab-fig-img { display: block; }
    #ms-tab-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5; }
    .ms-tab-marker { pointer-events: all; }
    #ms-tab-empty-viewer { display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; color: var(--bs-secondary-color); font-size: 13px; flex-direction: column; gap: 8px; }
    .ms-tab-marker { position: absolute; transform: translate(-50%,-50%); width: 20px; height: 20px; border-radius: 50%; border: 2px solid #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 7px; font-weight: 700; color: #fff; z-index: 10; box-shadow: 0 1px 4px rgba(0,0,0,.5); transition: transform .12s; }
    .ms-tab-marker:hover { transform: translate(-50%,-50%) scale(1.2); }
    .ms-tab-marker.active { transform: translate(-50%,-50%) scale(1.35); box-shadow: 0 0 0 3px rgba(255,255,255,.6),0 2px 6px rgba(0,0,0,.5); z-index: 11; }
    .ms-tab-marker.dim { opacity: .45; }
    .ms-tab-marker.status-pass { background:#198754; } .ms-tab-marker.status-fail { background:#dc3545; }
    .ms-tab-marker.status-partial { background:#ffc107; color:#000; } .ms-tab-marker.status-none { background:#ffc107; color:#000; }
    .ms-tab-label { position: absolute; transform: translate(-50%,-50%); background: #fff; color: #222; border: 1.5px solid #0d6efd; border-radius: 3px; font-size: 10px; font-weight: 700; padding: 1px 5px; white-space: nowrap; cursor: pointer; z-index: 10; box-shadow: 0 1px 3px rgba(0,0,0,.3); pointer-events: all; transition: box-shadow .12s; }
    .ms-tab-label:hover { box-shadow: 0 0 0 2px rgba(13,110,253,.35), 0 1px 4px rgba(0,0,0,.3); }
    .ms-tab-label.active { border-color: #dc3545; color: #dc3545; box-shadow: 0 0 0 2px rgba(220,53,69,.3), 0 1px 4px rgba(0,0,0,.3); }
    .ms-tab-label.dim { opacity: .45; }
    .ms-tab-label.st-pass { border-color:#198754; color:#198754; }
    .ms-tab-label.st-fail { border-color:#dc3545; color:#dc3545; }
    .ms-tab-label.st-partial { border-color:#ffc107; color:#856404; }
    .ms-text-label { position: absolute; transform: translate(-50%,-50%); background: rgba(20,184,166,.1); border: 1.5px solid #14b8a6; border-radius: 8px; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #0d9488; white-space: nowrap; z-index: 9; pointer-events: none; }
    .ms-dim-marker { position: absolute; transform: translate(-50%,-50%); width: 16px; height: 16px; border-radius: 50%; border: 1.5px solid #6c757d; background: rgba(108,117,125,.2); font-size: 7px; color: #6c757d; display: flex; align-items: center; justify-content: center; z-index: 8; pointer-events: none; }

    #ms-tab-entry { order: 2; width: 460px; min-width: 320px; border-right: 1px solid var(--bs-border-color); display: flex; flex-direction: column; overflow: hidden; }
    #ms-comp-hdr { padding: 6px 10px; border-bottom: 1px solid var(--bs-border-color); flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    #ms-comp-title { font-size: 13px; font-weight: 600; }
    #ms-comp-sub { font-size: 10px; color: var(--bs-secondary-color); }
    #ms-tab-entry-body { flex: 1 1 auto; overflow-y: auto; padding: 6px; }
    .ms-acc-row { border: 1px solid var(--bs-border-color); border-radius: 5px; margin-bottom: 3px; overflow: hidden; }
    .ms-acc-hdr { padding: 5px 8px; display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px; user-select: none; border-left: 3px solid transparent; }
    .ms-acc-hdr:hover { background: rgba(13,110,253,.06); }
    .ms-acc-hdr.active { background: rgba(13,110,253,.1); border-left-color: #0d6efd; }
    .ms-acc-body { padding: 8px; border-top: 1px solid var(--bs-border-color); display: none; }
    .ms-acc-body.open { display: block; }
    .ms-acc-last { font-family: monospace; font-size: 11px; flex-shrink: 0; margin-left: auto; padding-right: 4px; }

    .ms-spec-lims { border: 1px solid var(--bs-border-color); border-radius: 5px; display: flex; flex-wrap: nowrap; gap: 0; margin-bottom: 8px; overflow: hidden; }
    .ms-lim-cell { flex: 1 1 0; min-width: 0; background: rgba(0,0,0,.03); padding: 3px 6px; border-right: 1px solid var(--bs-border-color); }
    .ms-lim-cell:last-child { border-right: none; }
    .ms-lim-lbl { color: var(--bs-secondary-color); font-size: 9px; white-space: nowrap; }
    .ms-lim-val { font-family: monospace; font-weight: 600; font-size: 12px; white-space: nowrap; }
    .ms-wear-cell { background: rgba(255,193,7,.08); }
    .ms-meas-row { padding: 5px 8px; border: 1px solid var(--bs-border-color); border-radius: 5px; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-size: 12px; }
    .ms-stage-badge { font-size: 10px; padding: 1px 5px; border-radius: 3px; background: rgba(108,117,125,.15); color: var(--bs-secondary-color); flex-shrink: 0; }
    .ms-mval { font-family: monospace; font-weight: 700; font-size: 13px; }
    .ms-rpass { color:#198754; } .ms-rfail { color:#dc3545; } .ms-rnull { color: var(--bs-secondary-color); }
    .ms-meta { font-size: 10px; color: var(--bs-secondary-color); }
    .ms-form-wrap { padding: 8px; border: 1px solid var(--bs-border-color); border-radius: 6px; }
    .ms-flabel { font-size: 11px; color: var(--bs-secondary-color); margin-bottom: 2px; }
    .ms-frow { margin-bottom: 6px; }
    .ms-rule-chip { font-size: 10px; color: #fd7e14; opacity: .9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ms-acc-rule-hint { font-size: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; flex-shrink: 1; }
    .ms-acc-hdr.active .ms-acc-rule-hint { display: none; }
    .ms-hint-worn { color: #dc3545; font-weight: 700; }
    .ms-hint-ok   { color: #198754; font-weight: 700; }
    .ms-hint-insp { color: #e6a817; font-weight: 700; }
    .ms-hint-rule { color: #fd7e14; }
    .ms-hint-lim  { color: var(--bs-secondary-color); font-family: monospace; }
    .ms-hint-val  { color: #dc3545; font-family: monospace; }
    .ms-hint-sep  { opacity: .35; margin: 0 2px; }
    .ms-finding-badge { font-size: 10px; padding: 1px 6px; border-radius: 3px; background: rgba(220,53,69,.15); color: #dc3545; font-weight: 600; flex-shrink: 0; }
    .ms-finding-badge.insp { background: rgba(255,193,7,.15); color: #ffc107; }
</style>

<div id="ms-tab-body">
    {{-- Parts --}}
    <div id="ms-tab-parts">
        <div class="px-2 py-2 border-bottom d-flex align-items-center" style="font-size:13px;font-weight:700;flex-shrink:0;color:var(--bs-secondary-color)">
            <span>PARTS</span>
            <span id="ms-parts-mode" class="d-none ms-2" style="font-size:10px;font-weight:600;color:var(--bs-info)"></span>
            <button type="button" id="ms-fc-parts-btn"
               class="btn btn-outline-success btn-sm ms-auto py-0 px-2" style="font-size:11px;font-weight:700"
               title="Only parts with Fits & Clearances points (checkbox on the point), ordered by point code — enter F&C measurements top-down. Click again to return.">
                F&amp;C
            </button>
            <button type="button" id="ms-new-parts-btn"
               class="btn btn-outline-info btn-sm ms-1 py-0 px-2" style="font-size:11px;font-weight:700"
               title="Order New positions — verify the replacement parts (orig limits)">
                Ordered
            </button>
        </div>
        <div id="ms-tab-parts-list">
            <div class="text-center text-secondary py-3" style="font-size:11px" id="ms-tab-loading">Loading…</div>
        </div>
    </div>

    {{-- Figure viewer --}}
    <div id="ms-tab-viewer">
        <div id="ms-tab-fig-label">— select a parameter —</div>
        <div id="ms-tab-fig-nav"></div>
        <div id="ms-tab-canvas">
            <div id="ms-tab-empty-viewer">
                <i class="bi bi-rulers" style="font-size:2rem;opacity:.2"></i>
                <span>Select a parameter</span>
            </div>
            <div id="ms-tab-fig-container" style="display:none">
                <img id="ms-tab-fig-img" src="" alt="">
            </div>
            <svg id="ms-tab-svg" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:4;overflow:visible"></svg>
            <div id="ms-tab-overlay"></div>
        </div>
    </div>

    {{-- Modal: Add to TDR --}}
    <div class="modal fade" id="msTdrModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Add to TDR</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-secondary mb-2" style="font-size:11px" id="msTdrParamLabel"></div>
                    <div class="mb-2 d-none" id="msTdrRuleWrap">
                        <label class="form-label form-label-sm mb-1">Select decision</label>
                        <div id="msTdrRuleList" class="border rounded p-2" style="font-size:12px;max-height:220px;overflow-y:auto"></div>
                        <div class="text-warning small mt-1 d-none" id="msTdrRuleNote"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">IPL# <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="msTdrPn" placeholder="e.g. 6-50" autocomplete="off">
                        <div id="msTdrCompList" class="list-group mt-1" style="display:none;max-height:140px;overflow-y:auto;font-size:12px"></div>
                        <div class="text-secondary mt-1" style="font-size:11px;min-height:14px" id="msTdrComponentInfo"></div>
                    </div>
                    <div class="mb-2" id="msTdrSnRow">
                        <label class="form-label form-label-sm mb-1">Serial Number (SN)</label>
                        <input type="text" class="form-control form-control-sm" id="msTdrSn" placeholder="Optional">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">Qty</label>
                        <input type="number" class="form-control form-control-sm" id="msTdrQty" min="1" value="1" style="width:80px">
                    </div>
                    <div class="text-danger small d-none" id="msTdrErr"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" id="msTdrSaveBtn">Add to TDR</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Gate outcome modal: final out of repair limits → EC / Order New --}}
    <div class="modal fade" id="msGateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Out of repair limits — choose outcome</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2" style="font-size:12px" id="msGateFailedList"></div>
                    <div style="font-size:11px;color:var(--bs-secondary-color)" class="mb-2">
                        Completed work (machining, strip — everything before the gate anchor) is kept.
                        <strong>EC</strong> relabels the failed machining for OEM concession and holds post-gate processes.
                        <strong>Order New</strong> condemns the part and raises an Order New TDR.
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="msGateTypical">
                        <label class="form-check-label" for="msGateTypical" style="font-size:12px">Typical EC (pre-approved) — don't hold post-gate processes</label>
                    </div>
                    <div class="text-danger small d-none mt-2" id="msGateErr"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning btn-sm" id="msGateEcBtn">EC — save the part</button>
                    <button type="button" class="btn btn-danger btn-sm" id="msGateOrderNewBtn">Order New</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Component panel --}}
    <div id="ms-tab-entry">
        <div id="ms-comp-hdr" style="display:none">
            <div>
                <div id="ms-comp-title"></div>
                <div id="ms-comp-sub"></div>
            </div>
            <div class="d-flex gap-1 flex-shrink-0">
                <span id="ms-add-repair-hint" class="d-none text-secondary" style="font-size:11px;white-space:nowrap">
                    <i class="bi bi-arrow-right-circle"></i> Go to TDR tab to add Repair
                </span>
                <button id="ms-update-processes-btn" class="btn btn-outline-primary btn-sm d-none" style="font-size:11px">
                    <i class="bi bi-arrow-repeat"></i> Update Processes
                </button>
                {{-- TDR creation disabled from Measurements — use TDR tab --}}
                <button id="ms-revert-tdr-btn" class="btn btn-outline-warning btn-sm d-none" style="font-size:11px" title="Revert TDR" disabled hidden>
                    <i class="bi bi-arrow-counterclockwise"></i> Change decision
                </button>
                <button id="ms-print-sketch-btn" class="btn btn-outline-info btn-sm d-none" style="font-size:11px">
                    <i class="bi bi-printer"></i> Sketch
                </button>
                <button id="ms-print-figures-btn" class="btn btn-outline-secondary btn-sm" style="font-size:11px"
                        title="Print the figures with this part's points highlighted">
                    <i class="bi bi-printer"></i> Figures
                </button>
                {{-- Missing measurements popup --}}
                <div id="ms-sketch-missing-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center">
                    <div style="position:absolute;inset:0;background:rgba(0,0,0,.35)" onclick="document.getElementById('ms-sketch-missing-modal').style.display='none'"></div>
                    <div style="position:relative;background:#fff8e1;border:1px solid #ffc107;border-radius:8px;padding:20px 24px;min-width:280px;max-width:420px;box-shadow:0 4px 24px rgba(0,0,0,.18)">
                        <div style="font-weight:700;font-size:13px;color:#856404;margin-bottom:10px">⚠ Not measured</div>
                        <div id="ms-sketch-missing-body" style="font-size:12px;color:#495057;line-height:1.8"></div>
                        <button onclick="document.getElementById('ms-sketch-missing-modal').style.display='none'" class="btn btn-sm btn-outline-secondary" style="margin-top:14px;font-size:11px">Close</button>
                    </div>
                </div>
                <button id="ms-missing-part-btn" class="btn btn-outline-secondary btn-sm d-none" disabled hidden style="font-size:11px">
                    <i class="bi bi-question-circle"></i> Missing Part
                </button>
                <button id="ms-add-tdr-btn" class="btn btn-outline-danger btn-sm d-none" disabled hidden style="font-size:11px">
                    <i class="bi bi-plus-circle"></i> Add to TDR
                </button>
            </div>
        </div>
        <div id="ms-tab-entry-empty" class="text-center text-secondary py-4" style="font-size:11px">
            <i class="bi bi-rulers" style="font-size:1.8rem;display:block;opacity:.2;margin-bottom:.4rem"></i>
            Select a part
        </div>
        <div id="ms-tab-entry-body" style="display:none">
            <div id="ms-acc-wrap"></div>
        </div>
    </div>
</div>

{{-- Grid JS lives in public/js/measurements/tab.js; this inline block only
     hands the blade-side values over (see window.MS_CFG reads at its top). --}}
<script>
window.MS_CFG = {
    woId:  @json((int) $wo->id),
    woNum: @json((string) $wo->number),
};
</script>
<script src="{{ asset('js/measurements/tab.js') }}?v={{ @filemtime(public_path('js/measurements/tab.js')) }}"></script>
