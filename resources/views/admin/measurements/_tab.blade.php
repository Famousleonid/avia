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
    .ms-pdot.pass { background:#198754; } .ms-pdot.fail { background:#dc3545; } .ms-pdot.partial { background:#ffc107; } .ms-pdot.none { background:#ffc107; }
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
        <div class="px-2 py-1 border-bottom" style="font-size:10px;font-weight:600;flex-shrink:0;color:var(--bs-secondary-color)">PARTS</div>
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

    {{-- Modal: Main results gate (Path A — after machining/final measurements) --}}
    <div class="modal fade" id="msGateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Main results — confirm outcome</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="font-size:12px">
                    <div class="text-secondary mb-2" id="msGatePartLabel"></div>
                    <div id="msGatePoints" class="border rounded p-2 mb-2" style="max-height:240px;overflow-y:auto"></div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="msGateNdt">
                        <label class="form-check-label" for="msGateNdt" style="cursor:pointer;font-weight:600">NDT Pass</label>
                    </div>
                    <div class="form-check d-none" id="msGateEcTypicalWrap">
                        <input class="form-check-input" type="checkbox" id="msGateEcTypical">
                        <label class="form-check-label" for="msGateEcTypical" style="cursor:pointer">
                            Typical EC (pre-approved) — keep working (plating / finish proceed)
                        </label>
                    </div>
                    <div class="text-danger small mt-1 d-none" id="msGateErr"></div>
                </div>
                <div class="modal-footer py-2 d-flex justify-content-between">
                    <span class="text-secondary" style="font-size:11px" id="msGateHint"></span>
                    <div id="msGateButtons" class="d-flex gap-2"></div>
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
                {{-- TDR creation disabled from Measurements — use TDR tab --}}
                <button id="ms-revert-tdr-btn" class="btn btn-outline-warning btn-sm d-none" style="font-size:11px" title="Revert TDR" disabled hidden>
                    <i class="bi bi-arrow-counterclockwise"></i> Change decision
                </button>
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

<script>
(function () {
    const WO_ID = @json((int)$wo->id);
    const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let inspComponents = [], figures = [], parameters = [], measurements = [], USE_WEAR = false;
    let allCodes = [], MISSING_CODE_ID = null;
    let partsTree = [];
    let icsWithTdr = new Set(), icsMissingTdr = new Set(), icsTdrLabel = new Map();
    let activePartId = null, activeParam = null, activeFigure = null;
    let callouts = [];
    let loaded = false;

    const partsList    = document.getElementById('ms-tab-parts-list');
    const loadingEl    = document.getElementById('ms-tab-loading');
    const figLabel     = document.getElementById('ms-tab-fig-label');
    const figNav       = document.getElementById('ms-tab-fig-nav');
    const emptyViewer  = document.getElementById('ms-tab-empty-viewer');
    const figContainer = document.getElementById('ms-tab-fig-container');
    const figImg       = document.getElementById('ms-tab-fig-img');
    const overlay      = document.getElementById('ms-tab-overlay');
    const entryEmpty   = document.getElementById('ms-tab-entry-empty');
    const entryBody    = document.getElementById('ms-tab-entry-body');
    const compHdr      = document.getElementById('ms-comp-hdr');
    const accWrap      = document.getElementById('ms-acc-wrap');

    function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function fmtDim(v) { return v!=null ? parseFloat(v).toFixed(4) : '—'; }

    /* ── Zoom & Pan ────────────────────────────────────────────── */
    const canvas = document.getElementById('ms-tab-canvas');
    let scale=1, tx=0, ty=0, isDragging=false, dragStartX=0, dragStartY=0, dragTx=0, dragTy=0;

    const svgEl = document.getElementById('ms-tab-svg');

    function applyTransform() {
        figContainer.style.transform = `translate(${tx}px,${ty}px) scale(${scale})`;
        updateMarkerPositions();
        updateCalloutLines();
    }
    function updateMarkerPositions() {
        const iw=figImg.naturalWidth, ih=figImg.naturalHeight;
        if(!iw||!ih) return;
        overlay.querySelectorAll('.ms-tab-marker, .ms-tab-label, .ms-text-label, .ms-dim-marker').forEach(m=>{
            const xp=parseFloat(m.dataset.xPct), yp=parseFloat(m.dataset.yPct);
            if(isNaN(xp)||isNaN(yp)) return;
            m.style.left=(tx+xp/100*iw*scale)+'px';
            m.style.top =(ty+yp/100*ih*scale)+'px';
        });
    }
    function updateCalloutLines() {
        const iw=figImg.naturalWidth, ih=figImg.naturalHeight;
        svgEl.innerHTML='';
        if(!iw||!ih||!callouts.length) return;
        const ns='http://www.w3.org/2000/svg';
        callouts.forEach(c=>{
            const op=c.dim?'0.4':'1';
            const dash=c.dim?'4 3':'none';
            if(c.type==='line'){
                // measurement line between two endpoints
                const ax=tx+c.x_pct/100*iw*scale,  ay=ty+c.y_pct/100*ih*scale;
                const bx=tx+c.x2_pct/100*iw*scale, by=ty+c.y2_pct/100*ih*scale;
                const mline=document.createElementNS(ns,'line');
                mline.setAttribute('x1',ax); mline.setAttribute('y1',ay);
                mline.setAttribute('x2',bx); mline.setAttribute('y2',by);
                mline.setAttribute('stroke',c.color); mline.setAttribute('stroke-width','2');
                mline.setAttribute('stroke-dasharray',dash); mline.setAttribute('opacity',op);
                svgEl.appendChild(mline);
                for(const [cx,cy] of [[ax,ay],[bx,by]]){
                    const d=document.createElementNS(ns,'circle');
                    d.setAttribute('cx',cx); d.setAttribute('cy',cy);
                    d.setAttribute('r','3'); d.setAttribute('fill',c.color); d.setAttribute('opacity',op);
                    svgEl.appendChild(d);
                }
                if(c.lx_pct!=null){
                    // leader line from label to midpoint
                    const mx=(ax+bx)/2, my=(ay+by)/2;
                    const lx=tx+c.lx_pct/100*iw*scale, ly=ty+c.ly_pct/100*ih*scale;
                    const ll=document.createElementNS(ns,'line');
                    ll.setAttribute('x1',lx); ll.setAttribute('y1',ly);
                    ll.setAttribute('x2',mx); ll.setAttribute('y2',my);
                    ll.setAttribute('stroke',c.color); ll.setAttribute('stroke-width','1.5');
                    ll.setAttribute('stroke-dasharray',dash); ll.setAttribute('opacity',op);
                    svgEl.appendChild(ll);
                }
            } else {
                // dot callout (existing behavior)
                const px=tx+c.x_pct/100*iw*scale, py=ty+c.y_pct/100*ih*scale;
                const lx=tx+c.lx_pct/100*iw*scale, ly=ty+c.ly_pct/100*ih*scale;
                const line=document.createElementNS(ns,'line');
                line.setAttribute('x1',lx); line.setAttribute('y1',ly);
                line.setAttribute('x2',px); line.setAttribute('y2',py);
                line.setAttribute('stroke',c.color); line.setAttribute('stroke-width','1.5');
                line.setAttribute('stroke-dasharray',dash); line.setAttribute('opacity',op);
                const dot=document.createElementNS(ns,'circle');
                dot.setAttribute('cx',px); dot.setAttribute('cy',py);
                dot.setAttribute('r','3.5'); dot.setAttribute('fill',c.color); dot.setAttribute('opacity',op);
                svgEl.appendChild(line);
                svgEl.appendChild(dot);
            }
        });
    }
    function fitImage() {
        if(!figImg.naturalWidth) return;
        const rect=canvas.getBoundingClientRect();
        const cw=rect.width, ch=rect.height;
        if(!cw||!ch) { requestAnimationFrame(fitImage); return; }
        const iw=figImg.naturalWidth, ih=figImg.naturalHeight;
        figImg.style.width=iw+'px'; figImg.style.height=ih+'px';
        figContainer.style.width=iw+'px'; figContainer.style.height=ih+'px';
        scale=Math.min(cw/iw, ch/ih);
        tx=(cw-iw*scale)/2; ty=(ch-ih*scale)/2;
        applyTransform();
    }
    window.addEventListener('resize', ()=>{ if(figImg.src&&figContainer.style.display!=='none') fitImage(); });
    canvas.addEventListener('wheel', function(e){
        e.preventDefault();
        const rect=canvas.getBoundingClientRect();
        const mx=e.clientX-rect.left, my=e.clientY-rect.top;
        const delta=e.deltaY<0?1.12:1/1.12;
        const ns=Math.min(Math.max(scale*delta,0.1),10);
        tx=mx-(mx-tx)*(ns/scale); ty=my-(my-ty)*(ns/scale); scale=ns;
        applyTransform();
    },{passive:false});
    canvas.addEventListener('mousedown', function(e){
        if(e.button!==0) return;
        isDragging=true; dragStartX=e.clientX; dragStartY=e.clientY; dragTx=tx; dragTy=ty;
        canvas.classList.add('grabbing'); e.preventDefault();
    });
    window.addEventListener('mousemove', function(e){
        if(!isDragging) return;
        tx=dragTx+(e.clientX-dragStartX); ty=dragTy+(e.clientY-dragStartY);
        applyTransform();
    });
    window.addEventListener('mouseup', function(){
        if(!isDragging) return;
        isDragging=false; canvas.classList.remove('grabbing');
    });

    /* ── API ──────────────────────────────────────────────────── */
    async function apiFetch(url, opts={}) {
        const res=await fetch(url,{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json',...(opts.headers||{})},...opts});
        const j=await res.json();
        if(!res.ok) throw new Error(j.message||j.errors?Object.values(j.errors)[0][0]:'Error');
        return j;
    }

    /* ── Data ─────────────────────────────────────────────────── */
    function buildPartsTree() {
        /* Build point_id → {pt, fig} lookup */
        const pointMap = new Map();
        figures.forEach(fig=>{
            (fig.points||[]).forEach(pt=>{ pointMap.set(pt.id, {pt, fig}); });
        });

        return inspComponents.map(ic=>{
            const params = parameters
                .filter(p=>p.inspection_component_id===ic.id)
                .map(p=>({
                    ...p,
                    locations: (p.point_ids||[]).map(pid=>pointMap.get(pid)).filter(Boolean),
                }));
            return {...ic, params};
        }).filter(ic=>ic.params.length>0);
    }

    function paramMeasurements(param) {
        return measurements.filter(m=>m.manual_parameter_id===param.id).sort((a,b)=>a.id-b.id);
    }

    /* ── Status ───────────────────────────────────────────────── */
    function paramStatus(param) {
        const ms=paramMeasurements(param);
        const fins=ms.filter(m=>m.stage==='final'), inits=ms.filter(m=>m.stage==='initial');
        const cur=fins.length?fins[fins.length-1]:(inits.length?inits[inits.length-1]:null);
        if(!cur) return 'none';
        if(cur.result==='PASS') return 'pass';
        if(cur.result==='FAIL') return 'fail';
        // inspection-only point (no dimensional limits) that was measured with no defect → OK (green)
        const lim = effectiveLimits(param);
        if ((lim.min === null || lim.max === null) && !cur.codes_id) return 'pass';
        return 'partial';
    }
    function paramFailRuleLabel(param) {
        const ms = paramMeasurements(param);
        const failMeas = [...ms].reverse().find(m => m.result === 'FAIL' && m.manual_parameter_repair_rule_id);
        if (!failMeas) return null;
        const rule = (param.repair_rules || []).find(r => r.id === failMeas.manual_parameter_repair_rule_id);
        if (!rule) return null;
        let label = rule.name || '';
        if (!label && rule.processes?.length) label = rule.processes.map(p => p.label).filter(Boolean).slice(0, 2).join(', ');
        if (!label && rule.order_replacement) label = 'Order New';
        return label || null;
    }

    function buildParamHintHtml(param) {
        const ms = paramMeasurements(param);
        const lastFail = [...ms].reverse().find(m =>
            m.result === 'FAIL' && !(MISSING_CODE_ID && m.codes_id == MISSING_CODE_ID)
        );
        if (!lastFail) {
            // inspection-only point that was inspected and is clean → show explicit "No defect"
            // (otherwise the row looks blank and it's unclear it was checked)
            const limX = effectiveLimits(param);
            const isInspectionOnly = limX.min === null || limX.max === null;
            if (ms.length > 0 && isInspectionOnly) {
                return `<span class="ms-acc-rule-hint"><span class="ms-hint-ok">No defect</span></span>`;
            }
            return '';
        }

        const lim = effectiveLimits(param);
        const hasDimLimits = lim.min !== null && lim.max !== null;

        let dimFail = false;
        if (hasDimLimits && lastFail.actual_value != null) {
            dimFail = !(lastFail.actual_value >= lim.min && lastFail.actual_value <= lim.max);
        }

        const inspCode = lastFail.codes_id
            ? param.codes?.find(c => c.id == lastFail.codes_id && c.finding_context === 'inspection')
            : null;
        const inspName = inspCode?.name || null;

        let ruleName = null;
        if (lastFail.manual_parameter_repair_rule_id) {
            const rule = (param.repair_rules || []).find(r => r.id === lastFail.manual_parameter_repair_rule_id);
            if (rule) {
                ruleName = rule.name || '';
                if (!ruleName && rule.processes?.length)
                    ruleName = rule.processes.map(p => p.label).filter(Boolean).slice(0, 2).join(', ');
                if (!ruleName) ruleName = rule.order_replacement ? 'Order New' : null;
            }
        }

        const sep = '<span class="ms-hint-sep">·</span>';
        const tokens = [];

        if (hasDimLimits && lastFail.actual_value != null) {
            if (dimFail) {
                const wornCode = param.codes?.find(c => c.finding_context === 'measurement');
                tokens.push(`<span class="ms-hint-worn">${esc(wornCode?.name || 'Worn')}</span>`);
            } else if (inspName) {
                tokens.push(`<span class="ms-hint-ok">OK</span>`);
            }
        }

        if (inspName) tokens.push(`<span class="ms-hint-insp">${esc(inspName)}</span>`);
        if (ruleName) tokens.push(`<span class="ms-hint-rule">${esc(ruleName)}</span>`);

        if (!tokens.length) return '';

        if (dimFail && lastFail.actual_value != null)
            tokens.push(`<span class="ms-hint-val">${fmtDim(lastFail.actual_value)}</span>`);

        return `<span class="ms-acc-rule-hint">${tokens.join(sep)}</span>`;
    }

    function partStatus(part) {
        const req=part.params.filter(p=>p.is_required);
        if(!req.length) return 'none';
        const sts=req.map(p=>paramStatus(p));
        if(sts.includes('fail')) return 'fail';
        if(sts.some(s=>s==='none'||s==='partial')) return 'partial';
        return 'pass';
    }

    /* ── Left panel ───────────────────────────────────────────── */
    function renderPartsList() {
        partsList.innerHTML = '';
        if (!partsTree.length) {
            partsList.innerHTML = '<div class="px-3 py-2 text-secondary" style="font-size:11px">No parts defined</div>';
            return;
        }
        partsTree.forEach(part => {
            const isActive = activePartId === part.id;
            const isMissing = icsMissingTdr.has(part.id) || (MISSING_CODE_ID && part.params.some(p =>
                paramMeasurements(p).some(m => m.codes_id == MISSING_CODE_ID)
            ));
            const fcDone = isMissing && missingPartFcVerified(part);
            const tdrLabel = icsTdrLabel.get(part.id); // e.g. 'Worn, Order New' | 'missing' | undefined
            const tdrLabelColor = !tdrLabel ? '#6c757d'
                : tdrLabel === 'missing' ? '#dc3545'
                : /order new/i.test(tdrLabel) ? '#dc3545'
                : /repair/i.test(tdrLabel) ? '#fd7e14'
                : '#6c757d';
            const total = part.params.length;
            const done  = part.params.filter(p => paramStatus(p) !== 'none').length;
            let pSt, progHtml;
            if (fcDone) {
                pSt = 'pass';
                progHtml = `<span class="ms-part-prog" style="color:#198754">✓</span>`;
            } else if (isMissing) {
                pSt = 'missing';
                progHtml = `<span class="ms-missing-label">missing</span>`;
            } else if (tdrLabel && tdrLabel !== 'missing') {
                pSt = partStatus(part);
                progHtml = `<span style="font-size:9px;color:${tdrLabelColor};font-weight:600;flex-shrink:0">${esc(tdrLabel)}</span>`;
            } else {
                pSt = partStatus(part);
                progHtml = total > 0
                    ? (done === total
                        ? `<span class="ms-part-prog" style="color:#198754">✓</span>`
                        : `<span class="ms-part-prog">${done}/${total}</span>`)
                    : '';
            }
            const el = document.createElement('div');
            el.className = 'ms-tab-param-item' + (isActive ? ' active' : '');
            el.style.cssText = 'padding:6px 10px;border-left-width:3px';
            el.innerHTML = `<span class="ms-pdot ${pSt}"></span><span class="ms-tab-param-desc" style="font-size:12px;font-weight:600">${esc(part.label)}</span>${progHtml}`;
            el.addEventListener('click', () => selectComponent(part));
            partsList.appendChild(el);
        });
    }

    /* ── Viewer ───────────────────────────────────────────────── */
    function uniqueFigures(param) {
        const seen=new Set(), res=[];
        param.locations.forEach(({fig})=>{ if(!seen.has(fig.id)){ seen.add(fig.id); res.push(fig); } });
        return res;
    }

    function renderFigureNav(param, currentFig) {
        figNav.innerHTML='';
        if (!param) { figNav.classList.remove('visible'); return; }
        const figs=uniqueFigures(param);
        if(figs.length<=1){ figNav.classList.remove('visible'); return; }
        figNav.classList.add('visible');
        figs.forEach(fig=>{
            const btn=document.createElement('button');
            btn.className='ms-fig-btn'+(currentFig?.id===fig.id?' active':'');
            btn.textContent=fig.title||('Fig '+fig.id);
            btn.addEventListener('click',()=>showFigure(fig));
            figNav.appendChild(btn);
        });
    }

    function showFigure(fig) {
        activeFigure=fig;
        renderFigureNav(activeParam, fig);
        figLabel.textContent=fig.title||'—';
        const part=partsTree.find(p=>p.id===activePartId);
        if(!fig.image_path){ emptyViewer.style.display=''; figContainer.style.display='none'; overlay.innerHTML=''; return; }
        emptyViewer.style.display='none'; figContainer.style.display='';
        if(figImg.src!==fig.image_path){
            figContainer.style.transition='opacity 0.2s ease';
            figContainer.style.opacity='0';
            figImg.onload=()=>{
                fitImage();
                if(part) renderMarkers(part, activeParam, fig);
                const finalScale=scale, finalTx=tx, finalTy=ty;
                const rect=canvas.getBoundingClientRect();
                const iw=figImg.naturalWidth, ih=figImg.naturalHeight;
                scale=0.02; tx=rect.width/2-(iw/2)*0.02; ty=rect.height/2-(ih/2)*0.02;
                figContainer.style.transition='none'; figContainer.style.opacity='0';
                applyTransform(); void figContainer.offsetWidth;
                figContainer.style.transition='transform 0.5s cubic-bezier(0.2,0,0,1), opacity 0.4s ease';
                figContainer.style.opacity='1';
                scale=finalScale; tx=finalTx; ty=finalTy; applyTransform();
                setTimeout(()=>{ figContainer.style.transition='none'; },600);
            };
            figImg.src=fig.image_path;
        } else {
            if(part) renderMarkers(part, activeParam, fig);
        }
    }

    const STATUS_COLORS={pass:'#198754',fail:'#dc3545',partial:'#ffc107',none:'#ffc107'};

    function renderMarkers(part, activeParam, fig) {
        overlay.innerHTML='';
        callouts=[];
        const iw=figImg.naturalWidth, ih=figImg.naturalHeight;
        if(!iw||!ih) return;

        // Collect point IDs already handled by parameters (to avoid double-rendering)
        const linkedPointIds = new Set();
        part.params.forEach(param=>{
            param.locations.filter(l=>l.fig.id===fig.id).forEach(({pt})=>linkedPointIds.add(pt.id));
        });

        // Render decorative / unlinked points (text labels and unlinked measurement dots)
        (fig.points||[]).forEach(pt=>{
            if(linkedPointIds.has(pt.id)) return;
            if(pt.x_pct==null||pt.y_pct==null) return;
            if(pt.point_type==='text' && pt.label_x_pct!=null && pt.label_y_pct!=null){
                const ic = inspComponents.find(c=>c.id===pt.child_ic_id);
                const lbl = document.createElement('div');
                lbl.className='ms-text-label';
                lbl.textContent = ic ? ic.label : (pt.description||pt.code);
                lbl.dataset.xPct=pt.label_x_pct; lbl.dataset.yPct=pt.label_y_pct;
                lbl.style.left=(tx+pt.label_x_pct/100*iw*scale)+'px';
                lbl.style.top =(ty+pt.label_y_pct/100*ih*scale)+'px';
                overlay.appendChild(lbl);
                callouts.push({x_pct:pt.x_pct, y_pct:pt.y_pct, lx_pct:pt.label_x_pct, ly_pct:pt.label_y_pct, color:'#14b8a6', dim:false});
            } else if(pt.point_type==='measurement'){
                const isLine = pt.x2_pct!=null && pt.y2_pct!=null;
                if(isLine){
                    // unlinked line measurement — just push to callouts (SVG only, no label div)
                    callouts.push({type:'line', x_pct:pt.x_pct, y_pct:pt.y_pct, x2_pct:pt.x2_pct, y2_pct:pt.y2_pct, lx_pct:pt.label_x_pct, ly_pct:pt.label_y_pct, color:'#6c757d', dim:true});
                } else {
                    const m = document.createElement('div');
                    m.className='ms-dim-marker';
                    m.textContent=pt.code&&pt.code.length<=3?pt.code:pt.code.slice(0,3);
                    m.dataset.xPct=pt.x_pct; m.dataset.yPct=pt.y_pct;
                    m.style.left=(tx+pt.x_pct/100*iw*scale)+'px';
                    m.style.top =(ty+pt.y_pct/100*ih*scale)+'px';
                    m.title=pt.description||pt.code;
                    overlay.appendChild(m);
                }
            }
        });

        // Render parameter-linked points with status colors
        part.params.forEach(param=>{
            const isActiveParam = activeParam && param.id===activeParam.id;
            const st=paramStatus(param);
            const color=STATUS_COLORS[st]||STATUS_COLORS.none;
            param.locations.filter(l=>l.fig.id===fig.id).forEach(({pt})=>{
                if(pt.x_pct==null||pt.y_pct==null) return;
                const isLine = pt.x2_pct!=null && pt.y2_pct!=null;
                const isCallout = !isLine && pt.label_x_pct!=null && pt.label_y_pct!=null;
                if(isLine){
                    // line measurement: label at label_x/y (or midpoint if not set)
                    const midXpct = (parseFloat(pt.x_pct)+parseFloat(pt.x2_pct))/2;
                    const midYpct = (parseFloat(pt.y_pct)+parseFloat(pt.y2_pct))/2;
                    const hasExtLabel = pt.label_x_pct!=null && pt.label_y_pct!=null;
                    const lxp = hasExtLabel ? pt.label_x_pct : midXpct;
                    const lyp = hasExtLabel ? pt.label_y_pct : midYpct;
                    const m=document.createElement('div');
                    m.className='ms-tab-label st-'+(st||'none')+(isActiveParam?' active':' dim');
                    m.textContent=pt.code;
                    m.dataset.xPct=lxp; m.dataset.yPct=lyp;
                    m.style.left=(tx+lxp/100*iw*scale)+'px';
                    m.style.top =(ty+lyp/100*ih*scale)+'px';
                    m.title=param.description+(pt.code?' · '+pt.code:'');
                    m.addEventListener('click',e=>{ e.stopPropagation(); const p=partsTree.find(p=>p.id===part.id); if(!p) return; if(activePartId!==p.id){activePartId=p.id;activeParam=null;renderPartsList();renderComponentPanel(p);} expandAccordionRow(p,param); });
                    overlay.appendChild(m);
                    callouts.push({type:'line', x_pct:pt.x_pct, y_pct:pt.y_pct, x2_pct:pt.x2_pct, y2_pct:pt.y2_pct, lx_pct:hasExtLabel?pt.label_x_pct:null, ly_pct:hasExtLabel?pt.label_y_pct:null, color, dim:!isActiveParam});
                    return;
                }
                const xp = isCallout ? pt.label_x_pct : pt.x_pct;
                const yp = isCallout ? pt.label_y_pct : pt.y_pct;
                const m=document.createElement('div');
                if(isCallout){
                    m.className='ms-tab-label st-'+(st||'none')+(isActiveParam?' active':' dim');
                    m.textContent=pt.code;
                    callouts.push({x_pct:pt.x_pct, y_pct:pt.y_pct, lx_pct:pt.label_x_pct, ly_pct:pt.label_y_pct, color, dim:!isActiveParam});
                } else {
                    m.className='ms-tab-marker status-'+(st||'none')+(isActiveParam?' active':' dim');
                    m.textContent=pt.code&&pt.code.length<=3?pt.code:pt.code.slice(0,3);
                }
                m.dataset.xPct=xp; m.dataset.yPct=yp;
                m.style.left=(tx+xp/100*iw*scale)+'px';
                m.style.top =(ty+yp/100*ih*scale)+'px';
                m.title=param.description+(pt.code?' · '+pt.code:'');
                m.addEventListener('click',e=>{
                    e.stopPropagation();
                    const p=partsTree.find(p=>p.id===part.id);
                    if(!p) return;
                    if(activePartId!==p.id){activePartId=p.id;activeParam=null;renderPartsList();renderComponentPanel(p);}
                    expandAccordionRow(p,param);
                });
                overlay.appendChild(m);
            });
        });
        updateCalloutLines();
    }

    /* ── Select component ─────────────────────────────────────── */
    function selectComponent(part) {
        activePartId = part.id;
        activeParam  = null;   // no accordion auto-expanded on part selection
        renderPartsList();
        renderComponentPanel(part);
        // Show the figure using the first param's location (markers all dim, none active)
        const firstParam = part.params[0] || null;
        if (firstParam) {
            const figs = uniqueFigures(firstParam);
            const fig = figs[0] || null;
            activeFigure = fig;
            if (fig) {
                figLabel.textContent = fig.title || '—';
                if (!fig.image_path) {
                    emptyViewer.style.display = ''; figContainer.style.display = 'none';
                    overlay.innerHTML = ''; return;
                }
                emptyViewer.style.display = 'none'; figContainer.style.display = '';
                if (figImg.src !== fig.image_path) {
                    figImg.onload = () => { fitImage(); renderMarkers(part, null, fig); };
                    figImg.src = fig.image_path;
                } else {
                    renderMarkers(part, null, fig);
                }
            } else {
                emptyViewer.style.display = '';
                figContainer.style.display = 'none';
                overlay.innerHTML = ''; svgEl.innerHTML = ''; callouts = [];
                figNav.classList.remove('visible');
                figLabel.textContent = '— no figure —';
            }
        }
    }

    /* ── Component panel (accordion) ─────────────────────────── */
    function renderComponentPanel(part) {
        compHdr.style.display = '';
        entryEmpty.style.display = 'none';
        entryBody.style.display  = '';

        document.getElementById('ms-comp-title').textContent = part.label;
        const ic = inspComponents.find(c => c.id === part.id);
        const ipl = (ic?.ipl_nums || [])[0] || '';
        const pn  = (ic?.part_numbers || [])[0] || '';
        const subParts = [];
        if (ipl) subParts.push('IPL# ' + ipl);
        if (pn)  subParts.push('P/N ' + pn);
        document.getElementById('ms-comp-sub').textContent = subParts.join('  ·  ');

        updateTdrBtnState(part);
        updateMissingPartBtnState(part);

        accWrap.innerHTML = '';
        part.params.forEach(param => accWrap.appendChild(buildAccordionRow(part, param)));
    }

    function updateMissingPartBtnState(part) {
        const btn = document.getElementById('ms-missing-part-btn');
        if (!btn) return;
        if (!part || part.is_bush) { btn.classList.add('d-none'); btn.disabled = true; return; }
        btn.classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-question-circle"></i> Missing Part';
        const alreadyMissing = MISSING_CODE_ID && part.params.some(p =>
            paramMeasurements(p).some(m => m.codes_id == MISSING_CODE_ID)
        );
        const hasAnyMeasurements = part.params.some(p => paramMeasurements(p).length > 0);
        const hasTdr = icsWithTdr.has(part.id);
        const enable = MISSING_CODE_ID && !alreadyMissing && !hasAnyMeasurements && !hasTdr;
        btn.disabled = !enable;
        btn.classList.toggle('btn-outline-warning',   !!enable);
        btn.classList.toggle('btn-outline-secondary', !enable);
    }

    document.getElementById('ms-missing-part-btn').addEventListener('click', async function () {
        const part = partsTree.find(p => p.id === activePartId);
        if (!part || !MISSING_CODE_ID) return;

        this.disabled = true;
        const ic = inspComponents.find(c => c.id === part.id);
        const pn = (ic?.ipl_nums || [])[0] || '';
        if (!pn) { alert('No IPL# for this part — cannot create TDR.'); this.disabled = false; return; }
        const targetParam = part.params.find(p => p.orig_dim_min !== null || p.orig_dim_max !== null)
            || part.params[0];
        if (!targetParam) { this.disabled = false; return; }
        try {
            const saved = await apiFetch('/workorders/' + WO_ID + '/measurements', {
                method: 'POST',
                body: JSON.stringify({
                    manual_parameter_id: targetParam.id,
                    stage: 'initial',
                    replaces_id: null,
                    actual_value: null,
                    codes_id: MISSING_CODE_ID,
                    notes: null,
                }),
            });
            measurements.push(saved);
            await apiFetch('/workorders/' + WO_ID + '/tdr-from-measurement', {
                method: 'POST',
                body: JSON.stringify({
                    wo_measurement_id: saved.id,
                    missing_meas_id:   saved.id,
                    pn, sn: null, qty: 1, rule_ids: [],
                }),
            });
            icsWithTdr.add(activePartId);
            if (typeof showNotification === 'function') showNotification('Missing Part — Order New TDR created', 'success');
            document.dispatchEvent(new CustomEvent('tdr-created-from-measurements'));
            refreshActive();
        } catch (e) { alert(e.message); this.disabled = false; }
    });

    function missingPartFcVerified(part) {
        const fcParams = part.params.filter(p => p.fc_mating_param_id);
        if (!fcParams.length) return true; // no F&C — nothing to verify
        return fcParams.every(p => paramMeasurements(p).some(m => m.stage === 'final'));
    }

    function updateTdrBtnState(part) {
        const btn = document.getElementById('ms-add-tdr-btn');
        const revertBtn = document.getElementById('ms-revert-tdr-btn');
        if (!btn) return;
        if (icsWithTdr.has(part.id)) {
            btn.disabled = true;
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-outline-success');
            // Missing Part: hide Change decision once F&C fit is verified
            const fcDone = icsMissingTdr.has(part.id) && missingPartFcVerified(part);
            if (revertBtn) revertBtn.classList.toggle('d-none', fcDone);
            return;
        }
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-outline-danger');
        if (revertBtn) revertBtn.classList.add('d-none');
        const hasAnyFail = part.params.some(p =>
            paramMeasurements(p).some(m => m.result === 'FAIL')
        );
        btn.disabled = !hasAnyFail;
    }

    function buildAccordionRow(part, param) {
        const isActive = activeParam?.id === param.id;
        const st   = paramStatus(param);
        const ms   = paramMeasurements(param);
        const last = ms[ms.length - 1] || null;
        const ptCodes = [...new Set(param.locations.map(l => l.pt.code))].join(', ');

        let lastHtml = '';
        if (last) {
            const rc = last.result === 'PASS' ? 'ms-rpass' : last.result === 'FAIL' ? 'ms-rfail' : 'ms-rnull';
            const codeName = last.codes_id
                ? (param.codes?.find(c => c.id == last.codes_id)?.name || allCodes.find(c => c.id == last.codes_id)?.name || 'Finding')
                : null;
            const val = last.actual_value != null ? fmtDim(last.actual_value) : (codeName || '—');
            lastHtml = `<span class="ms-acc-last ${rc}">${val}</span>`;
        }

        const row = document.createElement('div');
        row.className = 'ms-acc-row';
        row.dataset.paramId = param.id;

        const hdr = document.createElement('div');
        hdr.className = 'ms-acc-hdr' + (isActive ? ' active' : '');
        hdr.innerHTML = `<span class="ms-sdot ${st}"></span>
            <span class="ms-tab-param-desc">${esc(param.description)}</span>
            ${param.fc_mating_param_id ? '<span class="ms-fc-badge">F&amp;C</span>' : ''}
            ${buildParamHintHtml(param)}
            ${ptCodes ? `<span class="ms-pt-code">${esc(ptCodes)}</span>` : ''}
            ${lastHtml}`;
        hdr.addEventListener('click', () => expandAccordionRow(part, param));

        const body = document.createElement('div');
        body.className = 'ms-acc-body' + (isActive ? ' open' : '');
        body.id = 'ms-acc-body-' + param.id;
        if (isActive) fillAccordionBody(body, param);

        row.appendChild(hdr);
        row.appendChild(body);
        return row;
    }

    function expandAccordionRow(part, param) {
        // toggle: second click on open row collapses it
        if (activeParam?.id === param.id) {
            activeParam = null;
            accWrap.querySelectorAll('.ms-acc-hdr').forEach(h => h.classList.remove('active'));
            accWrap.querySelectorAll('.ms-acc-body').forEach(b => b.classList.remove('open'));
            return;
        }

        activeParam = param;

        // collapse all
        accWrap.querySelectorAll('.ms-acc-hdr').forEach(h => h.classList.remove('active'));
        accWrap.querySelectorAll('.ms-acc-body').forEach(b => b.classList.remove('open'));

        // expand this
        const row = accWrap.querySelector(`[data-param-id="${param.id}"]`);
        if (row) {
            row.querySelector('.ms-acc-hdr').classList.add('active');
            const body = row.querySelector('.ms-acc-body');
            body.classList.add('open');
            fillAccordionBody(body, param);
        }

        // update viewer
        const figs = uniqueFigures(param);
        const fig = (activeFigure && param.locations.some(l => l.fig.id === activeFigure.id))
            ? activeFigure : (figs[0] || null);
        if (fig) showFigure(fig);
        else {
            emptyViewer.style.display = '';
            figContainer.style.display = 'none';
            overlay.innerHTML = ''; svgEl.innerHTML = ''; callouts = [];
            figNav.classList.remove('visible');
            figLabel.textContent = '— no figure —';
        }
    }

    function fillAccordionBody(body, param) {
        body.innerHTML = '';
        const lim    = effectiveLimits(param);
        const hasLim = lim.min !== null || lim.max !== null;

        if (hasLim) {
            // Required step from mating part (F&C pair)
            let reqStepHtml = '';
            if (param.fc_mating_param_id && (param.repair_steps || []).length > 0) {
                const matingParam = parameters.find(p => p.id === param.fc_mating_param_id);
                if (matingParam) {
                    const matingFinals = paramMeasurements(matingParam).filter(m => m.stage === 'final');
                    const stepNo = matingFinals.length ? matingFinals[matingFinals.length - 1].repair_step_no : null;
                    if (stepNo) {
                        const step = param.repair_steps.find(s => s.step_no === stepNo);
                        if (step) {
                            reqStepHtml = `
                                <div class="ms-lim-cell" style="background:rgba(13,110,253,.1);border-left:2px solid #0d6efd">
                                    <div class="ms-lim-lbl" style="color:#0d6efd">req min (${esc(stepNo)})</div>
                                    <div class="ms-lim-val" style="color:#0d6efd">${fmtDim(step.dim_min)}</div>
                                </div>
                                <div class="ms-lim-cell" style="background:rgba(13,110,253,.1)">
                                    <div class="ms-lim-lbl" style="color:#0d6efd">req max (${esc(stepNo)})</div>
                                    <div class="ms-lim-val" style="color:#0d6efd">${fmtDim(step.dim_max)}</div>
                                </div>`;
                        }
                    }
                }
            }

            const limDiv = document.createElement('div'); limDiv.className = 'ms-spec-lims';
            limDiv.innerHTML = `
                <div class="ms-lim-cell"><div class="ms-lim-lbl">orig min</div><div class="ms-lim-val">${fmtDim(param.orig_dim_min)}</div></div>
                <div class="ms-lim-cell"><div class="ms-lim-lbl">orig max</div><div class="ms-lim-val">${fmtDim(param.orig_dim_max)}</div></div>
                ${param.wear_dim_min != null ? `<div class="ms-lim-cell ms-wear-cell"><div class="ms-lim-lbl">wear min</div><div class="ms-lim-val">${fmtDim(param.wear_dim_min)}</div></div><div class="ms-lim-cell ms-wear-cell"><div class="ms-lim-lbl">wear max</div><div class="ms-lim-val">${fmtDim(param.wear_dim_max)}</div></div>` : ''}
                ${reqStepHtml}`;
            body.appendChild(limDiv);
        }

        const recDiv = document.createElement('div'); recDiv.id = 'ms-prec-' + param.id;
        const frmDiv = document.createElement('div'); frmDiv.id = 'ms-pfrm-' + param.id;
        body.appendChild(recDiv);
        body.appendChild(frmDiv);
        renderParamRows(param, paramMeasurements(param), recDiv, frmDiv);
    }

    function renderParamRows(param, ms, rec, frm) {
        if (!rec) rec = document.getElementById('ms-prec-'+param.id);
        if (!frm) frm = document.getElementById('ms-pfrm-'+param.id);
        if(!rec||!frm) return;
        rec.innerHTML=''; frm.innerHTML='';
        const inits=ms.filter(m=>m.stage==='initial'), fins=ms.filter(m=>m.stage==='final');
        const lastInit=inits[inits.length-1]||null, lastFin=fins[fins.length-1]||null;
        ms.forEach(m=>rec.appendChild(buildMeasRow(m,param)));

        const part = partsTree.find(p => p.id === param.inspection_component_id);
        const partIsMissing = MISSING_CODE_ID && part?.params.some(p =>
            paramMeasurements(p).some(m => m.codes_id == MISSING_CODE_ID)
        );
        if (partIsMissing) {
            if (!param.fc_mating_param_id) return; // new part — no standalone measurements needed
            if (!lastFin) {
                const w = document.createElement('div'); w.className = 'mt-2';
                w.innerHTML = `<button class="btn btn-outline-info btn-sm w-100" style="font-size:11px"><i class="bi bi-plus-circle"></i> Add Final measurement (new part installed)</button>`;
                w.querySelector('button').addEventListener('click', () => w.replaceWith(buildForm(param, 'final', null)));
                frm.appendChild(w);
            }
            return;
        }

        if(!lastInit){ frm.appendChild(buildForm(param,'initial',null)); }
        else if(lastInit.result==='FAIL'&&!lastFin){
            const w=document.createElement('div'); w.className='mt-2';
            w.innerHTML=`<button class="btn btn-outline-warning btn-sm w-100" style="font-size:11px"><i class="bi bi-plus-circle"></i> Add Final measurement (after repair)</button>`;
            w.querySelector('button').addEventListener('click',()=>w.replaceWith(buildForm(param,'final',lastInit.id)));
            frm.appendChild(w);
        }
    }

    function buildMeasRow(m, param) {
        const rc=m.result==='PASS'?'ms-rpass':m.result==='FAIL'?'ms-rfail':'ms-rnull';
        const isMissingPart = MISSING_CODE_ID && m.codes_id == MISSING_CODE_ID;

        const codeName = m.codes_id && !isMissingPart
            ? (param.codes?.find(c => c.id == m.codes_id)?.name
               || allCodes.find(c => c.id == m.codes_id)?.name || '')
            : '';
        const findingCtx = m.codes_id
            ? (param.codes?.find(c => c.id == m.codes_id)?.finding_context || '')
            : '';
        const findingBadgeHtml = codeName
            ? `<span class="ms-finding-badge ${findingCtx==='inspection'?'insp':''}">${esc(codeName)}</span>`
            : '';

        let ruleChipHtml = '';
        if (m.result === 'FAIL' && m.manual_parameter_repair_rule_id) {
            const rule = (param.repair_rules || []).find(r => r.id === m.manual_parameter_repair_rule_id);
            if (rule) {
                let label = rule.name || '';
                if (!label && rule.processes?.length) {
                    label = rule.processes.map(p => p.label).filter(Boolean).slice(0, 2).join(', ');
                }
                if (!label) label = rule.order_replacement ? 'Order New' : '';
                if (label) ruleChipHtml = `<span class="ms-rule-chip w-100">→ ${esc(label)}</span>`;
            }
        }

        // Compute dimensional result client-side to detect "dim OK but finding FAIL"
        const lim = effectiveLimits(param);
        const hasDimLimits = lim.min !== null && lim.max !== null;
        let dimResult = null;
        if (hasDimLimits && m.actual_value != null) {
            dimResult = (m.actual_value >= lim.min && m.actual_value <= lim.max) ? 'PASS' : 'FAIL';
        }
        // Split display: dimension is in-tolerance but a finding makes it FAIL
        const splitDisplay = !isMissingPart && m.actual_value != null && codeName && dimResult === 'PASS';

        // final landed in an oversize repair step → PASS, show the step (RO5)
        const stepChip = m.repair_step_no ? `<span class="ms-rpass" style="font-weight:700;font-size:11px;margin-right:3px">${esc(m.repair_step_no)}</span>` : '';
        let valueHtml = '';
        if (m.actual_value != null) {
            if (splitDisplay) {
                valueHtml = `<span class="ms-mval ms-rpass">${fmtDim(m.actual_value)}</span><span class="ms-rpass" style="font-weight:700;font-size:12px">OK</span>`;
            } else {
                valueHtml = `<span class="ms-mval ${rc}">${fmtDim(m.actual_value)}</span>${stepChip}<span class="${rc}" style="font-weight:700;font-size:12px">${m.result||'—'}</span>`;
            }
        } else if (m.result) {
            valueHtml = `<span class="${rc}" style="font-weight:700;font-size:12px">${m.result}</span>`;
        }
        const splitFailHtml = splitDisplay ? `<span class="ms-rfail" style="font-weight:700;font-size:12px">FAIL</span>` : '';

        const d=document.createElement('div'); d.className='ms-meas-row flex-wrap';
        d.innerHTML=`<span class="ms-stage-badge">${m.stage}</span>
            ${isMissingPart?`<span class="badge bg-danger ms-1" style="font-size:10px">Missing Part</span>`:''}
            ${valueHtml}
            ${findingBadgeHtml}
            ${splitFailHtml}
            ${m.notes?'<span class="ms-meta text-truncate">'+esc(m.notes)+'</span>':''}
            <span class="ms-meta ms-auto">${m.user?.name?'by '+m.user.name:''}</span>
            <button class="btn btn-link btn-sm p-0 ms-1 text-danger ms-del-btn" style="font-size:11px" title="Delete"><i class="bi bi-x-lg"></i></button>
            ${ruleChipHtml}`;
        d.querySelector('.ms-del-btn').addEventListener('click',async()=>{
            if(!confirm('Delete this measurement?')) return;
            try {
                await apiFetch('/measurements/'+m.id,{method:'DELETE'});
                measurements=measurements.filter(x=>x.id!==m.id);
                refreshActive();
            } catch(e){ alert(e.message); }
        });
        return d;
    }

    function buildForm(param, stage, replacesId) {
        const uid = param.id+'_'+stage;
        const hasMeas = param.orig_dim_min !== null || param.orig_dim_max !== null;
        const inspCodes = (param.codes||[]).filter(c => c.finding_context !== 'measurement');
        const hasInsp = inspCodes.length > 0;
        const codesOpts = inspCodes.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('');

        const d=document.createElement('div'); d.className='ms-form-wrap mt-2';
        d.innerHTML=`
            <div style="font-size:10px;font-weight:600;color:var(--bs-secondary-color);margin-bottom:6px">${stage==='final'?'Final measurement (after repair)':'Record measurement'}</div>
            ${hasMeas?`
            <div class="ms-frow">
                <div class="ms-flabel">Actual value</div>
                <input type="number" class="form-control form-control-sm" step="0.0001" id="mst-val-${uid}" placeholder="0.0000" style="font-family:monospace;font-size:13px">
            </div>`:''}
            ${hasInsp?`
            <div class="ms-frow"><div class="ms-flabel">Finding</div>
                <select class="form-select form-select-sm" id="mst-code-${uid}"><option value="">— None —</option>${codesOpts}</select></div>`:''}
            <div class="ms-frow"><div class="ms-flabel">Notes</div>
                <textarea class="form-control form-control-sm" id="mst-notes-${uid}" rows="2"></textarea></div>
            <div class="text-danger small d-none mb-1" id="mst-err-${uid}"></div>
            <button class="btn btn-primary btn-sm w-100" style="font-size:12px" id="mst-save-${uid}"><i class="bi bi-check2"></i> Save</button>`;

        d.querySelector('#mst-save-'+uid).addEventListener('click',async()=>{
            const err=d.querySelector('#mst-err-'+uid); err.classList.add('d-none');
            const btn=d.querySelector('#mst-save-'+uid); btn.disabled=true;
            const valRaw=hasMeas?(d.querySelector('#mst-val-'+uid)?.value||''):'';
            const body={
                manual_parameter_id: param.id,
                stage,
                replaces_id: replacesId||null,
                actual_value: hasMeas?(valRaw!==''?parseFloat(valRaw):null):null,
                codes_id: hasInsp?(d.querySelector('#mst-code-'+uid)?.value||null):null,
                notes: d.querySelector('#mst-notes-'+uid)?.value.trim()||null,
            };
            try {
                const saved=await apiFetch('/workorders/'+WO_ID+'/measurements',{method:'POST',body:JSON.stringify(body)});
                measurements.push(saved); refreshActive();
                if (stage === 'final') maybeOpenGate(param);
            } catch(e){ err.textContent=e.message; err.classList.remove('d-none'); btn.disabled=false; }
        });
        return d;
    }

    function effectiveLimits(param) {
        if(USE_WEAR&&param.wear_dim_min!=null) return {source:'wear',min:param.wear_dim_min,max:param.wear_dim_max};
        return {source:'orig',min:param.orig_dim_min,max:param.orig_dim_max};
    }

    // ── EC gate (Path A) — after Main / final measurements ────────────────
    let gateModal = null, gateState = null;

    async function maybeOpenGate(param) {
        const partId = param.inspection_component_id;
        if (!partId || !icsWithTdr.has(partId)) return; // gate only when the part has a (repair) TDR
        if (icsMissingTdr.has(partId)) return; // Missing Part — F&C final is a fit check, not a repair gate
        try {
            const res = await apiFetch('/workorders/' + WO_ID + '/gate/evaluate', {
                method: 'POST', body: JSON.stringify({ inspection_component_id: partId }),
            });
            if (res.ready) openGateModal(partId, res);
        } catch (e) { console.error('gate evaluate', e); }
    }

    function openGateModal(partId, res) {
        gateState = { partId: partId, allPass: res.all_pass };
        const part = partsTree.find(p => p.id === partId);
        document.getElementById('msGatePartLabel').textContent =
            (part ? part.label + ' — ' : '') + 'final results (' + res.points.length + ' point' + (res.points.length === 1 ? '' : 's') + ')';
        document.getElementById('msGatePoints').innerHTML = res.points.map(function (p) {
            const cls = p.pass ? 'text-success' : 'text-danger';
            return `<div class="d-flex align-items-center gap-2 py-1 border-bottom">
                ${p.pt_codes ? '<span class="badge bg-secondary" style="font-size:9px;min-width:34px">' + esc(p.pt_codes) + '</span>' : '<span style="min-width:34px"></span>'}
                <span class="flex-grow-1">${esc(p.description || '')}</span>
                <span class="font-monospace">${p.final_value != null ? fmtDim(p.final_value) : '—'}</span>
                <span class="fw-semibold ${cls}" style="min-width:38px;text-align:right">${p.pass ? 'PASS' : 'FAIL'}</span>
            </div>`;
        }).join('');
        document.getElementById('msGateNdt').checked = false;
        document.getElementById('msGateErr').classList.add('d-none');
        renderGateButtons();
        if (!gateModal) gateModal = new bootstrap.Modal(document.getElementById('msGateModal'));
        gateModal.show();
    }

    function renderGateButtons() {
        const ndt  = document.getElementById('msGateNdt').checked;
        const wrap = document.getElementById('msGateButtons');
        const hint = document.getElementById('msGateHint');
        let opts;
        if (!ndt) {
            opts = [];
            hint.textContent = 'NDT not passed — go to TDR tab to Order New';
        } else if (gateState.allPass) {
            opts = [{ k: 'finish', label: 'Finish', cls: 'btn-success' }];
            hint.textContent = 'All dimensions PASS';
        } else {
            opts = [{ k: 'ec', label: 'EC', cls: 'btn-warning' }];
            hint.textContent = 'Some dimensions FAIL → EC';
        }
        // "Typical EC" only matters when EC is on the table.
        const ecOnTable = opts.some(o => o.k === 'ec');
        document.getElementById('msGateEcTypicalWrap').classList.toggle('d-none', !ecOnTable);
        if (!ecOnTable) document.getElementById('msGateEcTypical').checked = false;
        wrap.innerHTML = opts.map(function (o) {
            return `<button type="button" class="btn btn-sm ${o.cls} ms-gate-btn" data-outcome="${o.k}" style="font-size:12px">${o.label}</button>`;
        }).join('');
        wrap.querySelectorAll('.ms-gate-btn').forEach(function (b) {
            b.addEventListener('click', function () { applyGateOutcome(b.dataset.outcome); });
        });
    }

    document.getElementById('msGateNdt').addEventListener('change', renderGateButtons);

    async function applyGateOutcome(outcome) {
        const ndt = document.getElementById('msGateNdt').checked;
        const ecTypical = document.getElementById('msGateEcTypical').checked;
        const err = document.getElementById('msGateErr');
        err.classList.add('d-none');
        try {
            const res = await apiFetch('/workorders/' + WO_ID + '/gate/apply', {
                method: 'POST',
                body: JSON.stringify({ inspection_component_id: gateState.partId, outcome: outcome, ndt_pass: ndt, ec_typical: ecTypical }),
            });
            if (res && res.ok === false) throw new Error(res.message || 'Not applied');
            if (typeof showNotification === 'function') {
                const msg = outcome === 'ec'
                            ? (ecTypical ? 'EC (typical): Machining (EC) set, work continues' : 'EC: post-NDT held, Machining (EC) set')
                          : outcome === 'finish' ? 'Finish: plan proceeds'
                          : 'Order New';
                showNotification(msg, 'success');
            }
            if (gateModal) gateModal.hide();
            refreshActive();
        } catch (e) {
            err.textContent = e.message;
            err.classList.remove('d-none');
        }
    }

    function refreshActive() {
        const part = partsTree.find(p => p.id === activePartId);
        if (!part) return;
        renderPartsList();
        updateTdrBtnState(part);
        updateMissingPartBtnState(part);
        if (!activeParam) return;
        const freshParam = part.params.find(p => p.id === activeParam.id) || activeParam;
        activeParam = freshParam;
        const row = accWrap.querySelector(`[data-param-id="${freshParam.id}"]`);
        if (row) {
            const st = paramStatus(freshParam);
            const ms = paramMeasurements(freshParam);
            const last = ms[ms.length - 1] || null;
            const ptCodes = [...new Set(freshParam.locations.map(l => l.pt.code))].join(', ');
            let lastHtml = '';
            if (last) {
                const rc = last.result === 'PASS' ? 'ms-rpass' : last.result === 'FAIL' ? 'ms-rfail' : 'ms-rnull';
                const codeName = last.codes_id
                    ? (freshParam.codes?.find(c => c.id == last.codes_id)?.name || allCodes.find(c => c.id == last.codes_id)?.name || 'Finding')
                    : null;
                const val = last.actual_value != null ? fmtDim(last.actual_value) : (codeName || '—');
                lastHtml = `<span class="ms-acc-last ${rc}">${val}</span>`;
            }
            const hdr = row.querySelector('.ms-acc-hdr');
            hdr.innerHTML = `<span class="ms-sdot ${st}"></span>
                <span class="ms-tab-param-desc">${esc(freshParam.description)}</span>
                ${freshParam.fc_mating_param_id ? '<span class="ms-fc-badge">F&amp;C</span>' : ''}
                ${buildParamHintHtml(freshParam)}
                ${ptCodes ? `<span class="ms-pt-code">${esc(ptCodes)}</span>` : ''}
                ${lastHtml}`;
            const body = row.querySelector('.ms-acc-body');
            if (body && body.classList.contains('open')) fillAccordionBody(body, freshParam);
        }
        if (activeFigure) renderMarkers(part, freshParam, activeFigure);
    }

    /* ── Load data ────────────────────────────────────────────── */
    async function loadData() {
        try {
            const data=await apiFetch('/workorders/'+WO_ID+'/measurements/data');
            USE_WEAR=data.use_wear;
            inspComponents=data.inspection_components;
            figures=data.figures;
            parameters=data.parameters;
            measurements=data.measurements;
            allCodes=data.codes||[];
            MISSING_CODE_ID=data.missing_code_id||null;
            icsWithTdr=new Set(data.ics_with_tdr||[]);
            icsMissingTdr=new Set(data.ics_missing_tdr||[]);
            icsTdrLabel=new Map(Object.entries(data.ics_tdr_label||{}).map(([k,v])=>[parseInt(k),v]));
            partsTree=buildPartsTree();
            if(loadingEl) loadingEl.style.display='none';
            renderPartsList();
        } catch(e) {
            if(loadingEl){ loadingEl.style.display=''; loadingEl.textContent='Failed to load: '+e.message; }
        }
    }

    /* ── TDR Modal ────────────────────────────────────────────── */
    let tdrModal = null;
    let activeTdrMeasurement = null;
    let activeTdrParam = null;
    let autoMissingMeasId = null;

    // All repair rules of a param whose triggers match this failed measurement.
    // Mirrors the backend resolveRepairRule but returns EVERY match, so the modal
    // can offer Repair / Order New / EC side by side for the technician to choose.
    function tdrMatchingRules(param, m) {
        const rules = param.repair_rules || [];
        if (!rules.length) return [];
        const lim  = effectiveLimits(param);
        const wear = lim.source === 'wear';
        const hasLimits = lim.min != null && lim.max != null;
        const av = m.actual_value;
        const dimFail = hasLimits && av != null && !(av >= lim.min && av <= lim.max);
        const codesId = m.codes_id || null;
        const findingCtx = codesId
            ? (param.codes?.find(c => c.codes_id == codesId)?.finding_context || 'inspection')
            : null;
        const failTriggers = wear ? ['below_wear', 'above_wear'] : ['below_orig', 'above_orig'];

        return rules.filter(rule => {
            const trigs = rule.triggers || [];
            if (codesId) {
                if (findingCtx === 'measurement') {
                    if (trigs.some(t => t.trigger === 'finding_measurement' && (t.codes_id == codesId || t.codes_id == null))) return true;
                } else if (trigs.some(t => (t.trigger === 'finding_inspection' || t.trigger === 'finding') && (t.codes_id == codesId || t.codes_id == null))) {
                    return true;
                }
            }
            return dimFail && trigs.some(t => failTriggers.includes(t.trigger));
        });
    }

    function openTdrModal(param, failMeas) {
        activeTdrParam = param;
        activeTdrMeasurement = failMeas[0];
        autoMissingMeasId = null;

        const part = partsTree.find(p => p.id === activePartId);
        document.getElementById('msTdrParamLabel').textContent =
            (part ? part.label + ' — ' : '') + param.description;
        document.getElementById('msTdrSn').value = '';
        document.getElementById('msTdrQty').value = '1';
        document.getElementById('msTdrComponentInfo').textContent = '';
        document.getElementById('msTdrCompList').style.display = 'none';
        document.getElementById('msTdrErr').classList.add('d-none');
        document.getElementById('msTdrRuleNote').classList.add('d-none');

        // Auto-fill IPL from inspection component
        const ic = inspComponents.find(c => c.id === param.inspection_component_id);
        const iplNums = ic?.ipl_nums || [];
        document.getElementById('msTdrPn').value = iplNums.length > 0 ? iplNums[0] : '';

        // Build decisions across ALL failed parameters of the part (one per failed param).
        // Each decision = current fail measurement + its parameter + outcome (Order New / Repair).
        const decisions = [];
        part.params.forEach(dParam => {
            const fails = paramMeasurements(dParam).filter(m => m.result === 'FAIL');
            if (!fails.length) return;
            const m = fails[fails.length - 1]; // current fail
            const isMissing = MISSING_CODE_ID && m.codes_id == MISSING_CODE_ID;
            const codeName = m.codes_id
                ? (dParam.codes?.find(c => c.id == m.codes_id)?.name || allCodes.find(c => c.id == m.codes_id)?.name || '')
                : '';
            const ptCodes = [...new Set(dParam.locations.map(l => l.pt.code))].join(', ');
            if (isMissing) {
                decisions.push({ measId: m.id, paramDesc: dParam.description, codeName, ptCodes, group: 'order_new', missing: true, ruleId: '', procCount: 0, noRule: false });
                return;
            }
            // Show EVERY matching rule as its own option (Repair / Order New / EC).
            const matched = tdrMatchingRules(dParam, m);
            if (!matched.length) {
                decisions.push({
                    measId: m.id, paramDesc: dParam.description, codeName, ptCodes, missing: false,
                    ruleId: '', procCount: 0, group: 'repair', noRule: true,
                });
            } else {
                matched.forEach(rule => {
                    decisions.push({
                        measId: m.id, paramDesc: dParam.description, codeName, ptCodes, missing: false,
                        ruleId: rule.id,
                        procCount: (rule.processes || []).length,
                        group: rule.action || (rule.order_replacement ? 'order_new' : 'repair'),
                        noRule: false,
                    });
                });
            }
        });

        const ruleWrap = document.getElementById('msTdrRuleWrap');
        const ruleList = document.getElementById('msTdrRuleList');
        ruleList.innerHTML = '';

        const missingDecisions = decisions.filter(d => d.missing);
        const nonMissing       = decisions.filter(d => !d.missing);
        const onlyMissing      = missingDecisions.length > 0 && nonMissing.length === 0;
        if (onlyMissing) {
            autoMissingMeasId = missingDecisions[0].measId;
            ruleWrap.classList.add('d-none');
        }

        if (!onlyMissing && decisions.length > 0) {
            ruleWrap.classList.remove('d-none');

            // One RADIO per decision (single choice), each on its own full-width row:
            //   [ptCode]  param · defect            → outcome
            decisions.forEach((d, i) => {
                const outcome = d.group === 'order_new' ? 'Order New'
                    : d.group === 'ec' ? ('EC' + (d.procCount ? ' · ' + d.procCount + ' proc.' : ''))
                    : ('Repair' + (d.procCount ? ' · ' + d.procCount + ' proc.' : (d.noRule ? ' (no rule)' : '')));
                const color = d.group === 'order_new' ? '#dc3545' : d.group === 'ec' ? '#fd7e14' : '#0d6efd';
                const item = document.createElement('div');
                item.className = 'form-check d-flex align-items-center gap-2 py-1 px-1';
                item.style.borderBottom = '1px solid var(--bs-border-color)';
                item.innerHTML = `<input class="form-check-input ms-tdr-decision m-0" type="radio" name="msTdrDecision"
                    data-group="${d.group}" data-rule-id="${d.missing ? '' : (d.ruleId || '')}" ${d.missing ? 'data-missing="1"' : ''}
                    data-no-rule="${(!d.missing && d.noRule) ? '1' : ''}" data-meas-id="${d.measId}"
                    id="msTdrDec-${i}">
                    <label class="form-check-label flex-grow-1 d-flex align-items-center gap-2" for="msTdrDec-${i}" style="font-size:12px;cursor:pointer">
                        ${d.ptCodes ? '<span class="badge bg-secondary" style="font-size:9px;min-width:34px">' + esc(d.ptCodes) + '</span>' : '<span style="min-width:34px"></span>'}
                        <span class="fw-semibold flex-grow-1">${esc(d.paramDesc || '')}${d.codeName ? ' <span class="text-warning fw-normal">· ' + esc(d.codeName) + '</span>' : ''}${d.missing ? ' <span class="badge bg-danger" style="font-size:9px">Missing</span>' : ''}</span>
                        <span class="pdw-outcome flex-shrink-0" style="font-weight:600">→ ${esc(outcome)}</span>
                    </label>`;
                item.querySelector('.pdw-outcome').style.color = color;
                ruleList.appendChild(item);
            });

            // Update SN visibility on selection change
            ruleList.addEventListener('change', function(e) {
                if (e.target.classList.contains('ms-tdr-decision')) updateTdrSnVisibility();
            });

            // Single choice (radio): nothing pre-selected — technician must pick one.
            const note = document.getElementById('msTdrRuleNote');
            note.textContent = 'Select one decision';
            note.classList.remove('d-none');
        } else {
            ruleWrap.classList.add('d-none');
        }

        updateTdrSnVisibility();
        if (!tdrModal) tdrModal = new bootstrap.Modal(document.getElementById('msTdrModal'));
        tdrModal.show();

        // Auto-trigger IPL search if pre-filled
        const prefilledIpl = document.getElementById('msTdrPn').value.trim();
        if (prefilledIpl) runIplSearch(prefilledIpl);
    }

    let iplLookupTimer = null;

    function selectTdrComponent(comp) {
        document.getElementById('msTdrPn').value        = comp.ipl_num;
        document.getElementById('msTdrQty').value       = comp.units_assy ?? 1;
        document.getElementById('msTdrComponentInfo').textContent = comp.part_number + (comp.name ? ' — ' + comp.name : '');
        document.getElementById('msTdrCompList').style.display = 'none';
        document.getElementById('msTdrCompList').innerHTML = '';
    }

    function updateTdrSnVisibility() {
        const snRow = document.getElementById('msTdrSnRow');
        if (!snRow) return;
        if (autoMissingMeasId) { snRow.style.display = 'none'; return; }
        const ruleWrap = document.getElementById('msTdrRuleWrap');
        if (!ruleWrap || ruleWrap.classList.contains('d-none')) {
            snRow.style.display = '';
            return;
        }
        const sel = document.querySelector('#msTdrRuleList .ms-tdr-decision:checked');
        const hasRepair = sel && sel.dataset.group === 'repair';
        snRow.style.display = hasRepair ? '' : 'none';
    }

    async function runIplSearch(val) {
        const info = document.getElementById('msTdrComponentInfo');
        const list = document.getElementById('msTdrCompList');
        if (!val) { info.textContent = ''; list.style.display = 'none'; list.innerHTML = ''; return; }
        try {
            const items = await apiFetch('/workorders/' + WO_ID + '/component-by-ipl?ipl_num=' + encodeURIComponent(val));
            list.innerHTML = '';
            if (!items || items.length === 0) {
                info.textContent = 'Not found';
                list.style.display = 'none';
                document.getElementById('msTdrQty').value = '1';
            } else if (items.length === 1) {
                selectTdrComponent(items[0]);
            } else {
                info.textContent = '';
                items.forEach(comp => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action py-1 px-2';
                    btn.innerHTML = '<span class="fw-semibold">' + esc(comp.ipl_num) + '</span>'
                        + ' <span class="text-secondary">' + esc(comp.part_number) + '</span>'
                        + (comp.name ? ' <span class="text-muted fst-italic">' + esc(comp.name) + '</span>' : '');
                    btn.addEventListener('click', () => selectTdrComponent(comp));
                    list.appendChild(btn);
                });
                list.style.display = 'block';
            }
        } catch { info.textContent = ''; }
    }

    document.getElementById('msTdrPn')?.addEventListener('input', function () {
        clearTimeout(iplLookupTimer);
        const val = this.value.trim();
        if (!val) {
            document.getElementById('msTdrComponentInfo').textContent = '';
            document.getElementById('msTdrCompList').style.display = 'none';
            document.getElementById('msTdrCompList').innerHTML = '';
            return;
        }
        iplLookupTimer = setTimeout(() => runIplSearch(val), 400);
    });

    document.getElementById('msTdrSaveBtn')?.addEventListener('click', async function () {
        const err = document.getElementById('msTdrErr');
        err.classList.add('d-none');
        const pn  = document.getElementById('msTdrPn').value.trim();
        const sn  = document.getElementById('msTdrSn').value.trim();
        const qty = parseInt(document.getElementById('msTdrQty').value, 10) || 1;

        // Single selected decision (radio). autoMissingMeasId covers the only-missing case.
        const sel = document.querySelector('#msTdrRuleList .ms-tdr-decision:checked');
        if (!autoMissingMeasId && !sel) {
            err.textContent = 'Select a decision.'; err.classList.remove('d-none'); return;
        }
        const isMissing = sel?.dataset.missing === '1';
        const missingMeasId = autoMissingMeasId
            ?? (isMissing ? parseInt(sel.dataset.measId, 10) : null);
        const ruleIds = (sel && !isMissing && sel.dataset.ruleId)
            ? [parseInt(sel.dataset.ruleId, 10)] : [];
        const hasNoRuleChecked    = !!(sel && !isMissing && sel.dataset.noRule === '1' && sel.dataset.group === 'repair');
        const hasOrderNewOverride = !!(sel && !isMissing && !sel.dataset.ruleId && sel.dataset.noRule !== '1' && sel.dataset.group === 'order_new');

        // Base measurement = the SELECTED decision (codes_id from the chosen defect).
        const baseMeasId = sel ? parseInt(sel.dataset.measId, 10)
            : (autoMissingMeasId ?? (activeTdrMeasurement ? activeTdrMeasurement.id : null));

        if (!pn) { err.textContent = 'IPL# is required.'; err.classList.remove('d-none'); return; }
        this.disabled = true;
        try {
            await apiFetch('/workorders/' + WO_ID + '/tdr-from-measurement', {
                method: 'POST',
                body: JSON.stringify({
                    wo_measurement_id: baseMeasId,
                    missing_meas_id: missingMeasId,
                    pn,
                    sn: sn || null,
                    qty,
                    rule_ids: ruleIds,
                    no_rule: hasNoRuleChecked,
                    order_new_override: hasOrderNewOverride,
                }),
            });
            tdrModal.hide();
            icsWithTdr.add(activePartId);
            const createdPart = partsTree.find(p => p.id === activePartId);
            if (createdPart) updateTdrBtnState(createdPart);
            document.dispatchEvent(new CustomEvent('tdr-created-from-measurements'));
            if (typeof showNotification === 'function') showNotification('TDR record created', 'success');
        } catch (e) {
            err.textContent = e.message;
            err.classList.remove('d-none');
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('ms-add-tdr-btn')?.addEventListener('click', function () {
        const part = partsTree.find(p => p.id === activePartId);
        if (!part) return;
        const allFailMeas = [];
        let firstFailParam = null;
        part.params.forEach(param => {
            const fails = paramMeasurements(param).filter(m => m.result === 'FAIL');
            if (fails.length && !firstFailParam) firstFailParam = param;
            allFailMeas.push(...fails);
        });
        if (!firstFailParam || !allFailMeas.length) return;
        openTdrModal(firstFailParam, allFailMeas);
    });

    // B1 — change decision: revert TDR (only if work not started), then choose again
    document.getElementById('ms-revert-tdr-btn')?.addEventListener('click', async function () {
        const part = partsTree.find(p => p.id === activePartId);
        if (!part) return;
        if (!confirm('Revert the current TDR decision for "' + part.label + '" and choose again?\n(Allowed only if work has not started.)')) return;
        this.disabled = true;
        try {
            await apiFetch('/workorders/' + WO_ID + '/revert-part-tdr', {
                method: 'POST',
                body: JSON.stringify({ inspection_component_id: part.id }),
            });
            icsWithTdr.delete(part.id);
            icsMissingTdr.delete(part.id);
            document.dispatchEvent(new CustomEvent('tdr-created-from-measurements'));

            // For Missing Part: also delete the Missing measurements to fully restore state
            const missingIds = MISSING_CODE_ID
                ? part.params.flatMap(p =>
                    paramMeasurements(p).filter(m => m.codes_id == MISSING_CODE_ID).map(m => m.id))
                : [];
            for (const id of missingIds) {
                await apiFetch('/measurements/' + id, { method: 'DELETE' });
                measurements = measurements.filter(m => m.id !== id);
            }

            if (missingIds.length) {
                // Was a Missing Part TDR — state fully reset, no modal needed
                if (typeof showNotification === 'function') showNotification('Missing cancelled — part restored', 'success');
                renderPartsList();
                renderComponentPanel(part);  // full re-render so Missing badge clears
            } else {
                // Regular Repair/Order New — reopen modal to pick a new decision
                if (typeof showNotification === 'function') showNotification('TDR reverted — choose a new decision', 'success');
                const allFailMeas = [];
                let firstFailParam = null;
                part.params.forEach(param => {
                    const fails = paramMeasurements(param).filter(m => m.result === 'FAIL');
                    if (fails.length && !firstFailParam) firstFailParam = param;
                    allFailMeas.push(...fails);
                });
                if (firstFailParam && allFailMeas.length) openTdrModal(firstFailParam, allFailMeas);
                else refreshActive();
            }
        } catch (e) {
            alert(e.message);
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('tab-measurements')?.addEventListener('shown.bs.tab', function(){
        if(!loaded){ loaded=true; loadData(); }
        const w = document.getElementById('ms-fc-btn-wrap');
        if (w) { w.classList.remove('d-none'); w.classList.add('d-flex'); }
    });
    document.getElementById('tab-measurements')?.addEventListener('hide.bs.tab', function(){
        const w = document.getElementById('ms-fc-btn-wrap');
        if (w) { w.classList.remove('d-flex'); w.classList.add('d-none'); }
    });
    if(document.getElementById('content-measurements')?.classList.contains('active')){
        loaded=true; loadData();
        const w = document.getElementById('ms-fc-btn-wrap');
        if (w) { w.classList.remove('d-none'); w.classList.add('d-flex'); }
    }
})();
</script>
