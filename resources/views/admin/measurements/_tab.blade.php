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
    .ms-pdot.pass { background:#198754; } .ms-pdot.fail { background:#dc3545; } .ms-pdot.partial { background:#ffc107; } .ms-pdot.none { background:#6c757d; }

    #ms-tab-viewer { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }
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
    .ms-tab-marker.status-partial { background:#ffc107; color:#000; } .ms-tab-marker.status-none { background:#6c757d; }
    .ms-tab-label { position: absolute; transform: translate(-50%,-50%); background: #fff; color: #222; border: 1.5px solid #0d6efd; border-radius: 3px; font-size: 10px; font-weight: 700; padding: 1px 5px; white-space: nowrap; cursor: pointer; z-index: 10; box-shadow: 0 1px 3px rgba(0,0,0,.3); pointer-events: all; transition: box-shadow .12s; }
    .ms-tab-label:hover { box-shadow: 0 0 0 2px rgba(13,110,253,.35), 0 1px 4px rgba(0,0,0,.3); }
    .ms-tab-label.active { border-color: #dc3545; color: #dc3545; box-shadow: 0 0 0 2px rgba(220,53,69,.3), 0 1px 4px rgba(0,0,0,.3); }
    .ms-tab-label.dim { opacity: .45; }
    .ms-tab-label.st-pass { border-color:#198754; color:#198754; }
    .ms-tab-label.st-fail { border-color:#dc3545; color:#dc3545; }
    .ms-tab-label.st-partial { border-color:#ffc107; color:#856404; }
    .ms-text-label { position: absolute; transform: translate(-50%,-50%); background: rgba(20,184,166,.1); border: 1.5px solid #14b8a6; border-radius: 8px; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #0d9488; white-space: nowrap; z-index: 9; pointer-events: none; }
    .ms-dim-marker { position: absolute; transform: translate(-50%,-50%); width: 16px; height: 16px; border-radius: 50%; border: 1.5px solid #6c757d; background: rgba(108,117,125,.2); font-size: 7px; color: #6c757d; display: flex; align-items: center; justify-content: center; z-index: 8; pointer-events: none; }

    #ms-tab-entry { width: 420px; min-width: 300px; border-left: 1px solid var(--bs-border-color); display: flex; flex-direction: column; overflow: hidden; }
    #ms-tab-entry-hdr { padding: 6px 10px; border-bottom: 1px solid var(--bs-border-color); flex-shrink: 0; }
    #ms-tab-entry-body { flex: 1 1 auto; overflow-y: auto; padding: 8px; }

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
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Add to TDR</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-secondary mb-2" style="font-size:11px" id="msTdrParamLabel"></div>
                    <div class="mb-2 d-none" id="msTdrRuleWrap">
                        <label class="form-label form-label-sm mb-1">Repair Rules</label>
                        <div id="msTdrRuleList" class="border rounded p-2" style="font-size:12px;max-height:160px;overflow-y:auto"></div>
                        <div class="text-warning small mt-1 d-none" id="msTdrRuleNote"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm mb-1">IPL# <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="msTdrPn" placeholder="e.g. 6-50" autocomplete="off">
                        <div id="msTdrCompList" class="list-group mt-1" style="display:none;max-height:140px;overflow-y:auto;font-size:12px"></div>
                        <div class="text-secondary mt-1" style="font-size:11px;min-height:14px" id="msTdrComponentInfo"></div>
                    </div>
                    <div class="mb-2">
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

    {{-- Entry panel --}}
    <div id="ms-tab-entry">
        <div id="ms-tab-entry-hdr">
            <div id="ms-tab-entry-title" style="font-size:13px;font-weight:600;color:var(--bs-secondary-color)">Select a parameter</div>
            <div id="ms-tab-entry-sub" style="font-size:10px;color:var(--bs-secondary-color);display:none"></div>
        </div>
        <div id="ms-tab-entry-body">
            <div id="ms-tab-entry-empty" class="text-center text-secondary py-4" style="font-size:11px">
                <i class="bi bi-rulers" style="font-size:1.8rem;display:block;opacity:.2;margin-bottom:.4rem"></i>
                Select a parameter to record measurements
            </div>
            <div id="ms-tab-param-panel" class="d-none"></div>
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
    let expandedPartIds = new Set(), activePartId = null, activeParam = null, activeFigure = null;
    let callouts = [];
    let loaded = false;

    const partsList   = document.getElementById('ms-tab-parts-list');
    const loadingEl   = document.getElementById('ms-tab-loading');
    const figLabel    = document.getElementById('ms-tab-fig-label');
    const figNav      = document.getElementById('ms-tab-fig-nav');
    const emptyViewer = document.getElementById('ms-tab-empty-viewer');
    const figContainer= document.getElementById('ms-tab-fig-container');
    const figImg      = document.getElementById('ms-tab-fig-img');
    const overlay     = document.getElementById('ms-tab-overlay');
    const entryTitle  = document.getElementById('ms-tab-entry-title');
    const entrySub    = document.getElementById('ms-tab-entry-sub');
    const entryEmpty  = document.getElementById('ms-tab-entry-empty');
    const paramPanel  = document.getElementById('ms-tab-param-panel');

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
        return 'partial';
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
        partsList.innerHTML='';
        if(!partsTree.length){ partsList.innerHTML='<div class="px-3 py-2 text-secondary" style="font-size:11px">No parts defined</div>'; return; }
        partsTree.forEach(part=>{
            const isOpen=expandedPartIds.has(part.id), pSt=partStatus(part);
            const g=document.createElement('div'); g.className='ms-part-group';
            const h=document.createElement('div'); h.className='ms-part-header';
            h.innerHTML=`<span class="ms-pdot ${pSt}"></span><span>${esc(part.label)}</span><i class="bi bi-chevron-${isOpen?'up':'down'} ms-part-chevron"></i>`;
            h.addEventListener('click',()=>{ expandedPartIds.has(part.id)?expandedPartIds.delete(part.id):expandedPartIds.add(part.id); renderPartsList(); });
            g.appendChild(h);
            const pl=document.createElement('div'); pl.className='ms-part-params'+(isOpen?' open':'');
            part.params.forEach(param=>{
                const st=paramStatus(param), isActive=activeParam?.id===param.id&&activePartId===part.id;
                const el=document.createElement('div'); el.className='ms-tab-param-item'+(isActive?' active':'');
                const ptCodes=[...new Set(param.locations.map(l=>l.pt.code))].join(', ');
                el.innerHTML=`<span class="ms-sdot ${st}"></span><span class="ms-tab-param-desc">${esc(param.description)}</span>${ptCodes?`<span class="ms-pt-code">${esc(ptCodes)}</span>`:''}`;
                el.addEventListener('click',()=>selectParam(part,param));
                pl.appendChild(el);
            });
            g.appendChild(pl); partsList.appendChild(g);
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

    const STATUS_COLORS={pass:'#198754',fail:'#dc3545',partial:'#ffc107',none:'#6c757d'};

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
                    m.addEventListener('click',e=>{ e.stopPropagation(); const p=partsTree.find(p=>p.id===part.id); if(p) selectParam(p,param); });
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
                    if(p) selectParam(p, param);
                });
                overlay.appendChild(m);
            });
        });
        updateCalloutLines();
    }

    /* ── Select param ─────────────────────────────────────────── */
    function selectParam(part, param) {
        activePartId=part.id; activeParam=param;
        expandedPartIds.add(part.id);
        renderPartsList();

        const figs=uniqueFigures(param);
        const fig = (activeFigure && param.locations.some(l=>l.fig.id===activeFigure.id))
            ? activeFigure : figs[0];

        if(fig) { showFigure(fig); }
        else { emptyViewer.style.display=''; figContainer.style.display='none'; overlay.innerHTML=''; svgEl.innerHTML=''; callouts=[]; figNav.classList.remove('visible'); figLabel.textContent='— no figure —'; }

        renderEntryPanel(part, param);
    }

    /* ── Entry panel ──────────────────────────────────────────── */
    function renderEntryPanel(part, param) {
        entryTitle.innerHTML=`<span style="color:var(--bs-secondary-color);font-weight:400;font-size:11px">${esc(part.label)}</span><br>${esc(param.description)}`;
        entryTitle.style.color='';
        const ptCodes=[...new Set(param.locations.map(l=>l.pt.code))].join(' · ');
        entrySub.textContent=ptCodes ? 'Points: '+ptCodes : '';
        entrySub.style.display=ptCodes?'':'none';

        entryEmpty.classList.add('d-none');
        paramPanel.classList.remove('d-none');
        paramPanel.innerHTML='';

        const lim=effectiveLimits(param);
        const hasLim=lim.min!==null||lim.max!==null;

        if(hasLim){
            const limDiv=document.createElement('div'); limDiv.className='ms-spec-lims';
            limDiv.innerHTML=`
                <div class="ms-lim-cell"><div class="ms-lim-lbl">orig min</div><div class="ms-lim-val">${fmtDim(param.orig_dim_min)}</div></div>
                <div class="ms-lim-cell"><div class="ms-lim-lbl">orig max</div><div class="ms-lim-val">${fmtDim(param.orig_dim_max)}</div></div>
                ${param.wear_dim_min!=null?`<div class="ms-lim-cell ms-wear-cell"><div class="ms-lim-lbl">wear min</div><div class="ms-lim-val">${fmtDim(param.wear_dim_min)}</div></div><div class="ms-lim-cell ms-wear-cell"><div class="ms-lim-lbl">wear max</div><div class="ms-lim-val">${fmtDim(param.wear_dim_max)}</div></div>`:''}`;
            paramPanel.appendChild(limDiv);
        }

        const recDiv=document.createElement('div'); recDiv.id='ms-prec-'+param.id;
        const frmDiv=document.createElement('div'); frmDiv.id='ms-pfrm-'+param.id;
        paramPanel.appendChild(recDiv); paramPanel.appendChild(frmDiv);
        renderParamRows(param, paramMeasurements(param));
    }

    function renderParamRows(param, ms) {
        const rec=document.getElementById('ms-prec-'+param.id);
        const frm=document.getElementById('ms-pfrm-'+param.id);
        if(!rec||!frm) return;
        rec.innerHTML=''; frm.innerHTML='';
        const inits=ms.filter(m=>m.stage==='initial'), fins=ms.filter(m=>m.stage==='final');
        const lastInit=inits[inits.length-1]||null, lastFin=fins[fins.length-1]||null;
        ms.forEach(m=>rec.appendChild(buildMeasRow(m,param)));

        const failMeas = ms.filter(m=>m.result==='FAIL');
        if(failMeas.length){
            const tdrBtn=document.createElement('div'); tdrBtn.className='mt-1';
            tdrBtn.innerHTML=`<button class="btn btn-outline-danger btn-sm w-100" style="font-size:11px"><i class="bi bi-plus-circle"></i> Add to TDR</button>`;
            tdrBtn.querySelector('button').addEventListener('click',()=>openTdrModal(param, failMeas));
            rec.appendChild(tdrBtn);
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
        const d=document.createElement('div'); d.className='ms-meas-row flex-wrap';
        d.innerHTML=`<span class="ms-stage-badge">${m.stage}</span>
            ${isMissingPart?`<span class="badge bg-danger ms-1" style="font-size:10px">Missing Part</span>`:''}
            ${m.actual_value!=null?`<span class="ms-mval ${rc}">${fmtDim(m.actual_value)}</span><span class="${rc}" style="font-weight:700;font-size:12px">${m.result||'—'}</span>`:''}
            ${m.notes?'<span class="ms-meta text-truncate">'+esc(m.notes)+'</span>':''}
            <span class="ms-meta ms-auto">${m.user?.name?'by '+m.user.name:''}</span>
            <button class="btn btn-link btn-sm p-0 ms-1 text-danger ms-del-btn" style="font-size:11px" title="Delete"><i class="bi bi-x-lg"></i></button>`;
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
        const hasInsp = (param.codes||[]).length > 0;
        const codesOpts = (param.codes||[]).map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('');

        const d=document.createElement('div'); d.className='ms-form-wrap mt-2';
        d.innerHTML=`
            <div style="font-size:10px;font-weight:600;color:var(--bs-secondary-color);margin-bottom:6px">${stage==='final'?'Final measurement (after repair)':'Record measurement'}</div>
            ${hasMeas?`
            <div class="ms-frow d-flex gap-2 align-items-end">
                <div style="flex:1"><div class="ms-flabel">Actual value</div>
                <input type="number" class="form-control form-control-sm" step="0.0001" id="mst-val-${uid}" placeholder="0.0000" style="font-family:monospace;font-size:13px"></div>
                <div class="form-check mb-1 flex-shrink-0"><input class="form-check-input" type="checkbox" id="mst-missing-${uid}"><label class="form-check-label text-danger" for="mst-missing-${uid}" style="font-size:11px">Missing Part</label></div>
            </div>`:''}
            ${hasInsp?`
            <div class="ms-frow"><div class="ms-flabel">Finding</div>
                <select class="form-select form-select-sm" id="mst-code-${uid}"><option value="">— None —</option>${codesOpts}</select></div>`:''}
            <div class="ms-frow"><div class="ms-flabel">Notes</div>
                <textarea class="form-control form-control-sm" id="mst-notes-${uid}" rows="2"></textarea></div>
            <div class="text-danger small d-none mb-1" id="mst-err-${uid}"></div>
            <button class="btn btn-primary btn-sm w-100" style="font-size:12px" id="mst-save-${uid}"><i class="bi bi-check2"></i> Save</button>`;

        if(hasMeas) d.querySelector('#mst-missing-'+uid)?.addEventListener('change',function(){
            const isMissing = this.checked;
            d.querySelector('#mst-val-'+uid).disabled = isMissing;
            if(isMissing) d.querySelector('#mst-val-'+uid).value = '';
            const codeEl = d.querySelector('#mst-code-'+uid);
            if(codeEl) codeEl.disabled = isMissing;
        });
        d.querySelector('#mst-save-'+uid).addEventListener('click',async()=>{
            const err=d.querySelector('#mst-err-'+uid); err.classList.add('d-none');
            const btn=d.querySelector('#mst-save-'+uid); btn.disabled=true;
            const isMissing=hasMeas&&(d.querySelector('#mst-missing-'+uid)?.checked);
            const valRaw=hasMeas?(d.querySelector('#mst-val-'+uid)?.value||''):'';
            const body={
                manual_parameter_id: param.id,
                stage,
                replaces_id: replacesId||null,
                actual_value: hasMeas?(isMissing?null:(valRaw!==''?parseFloat(valRaw):null)):null,
                codes_id: isMissing ? MISSING_CODE_ID : (hasInsp?(d.querySelector('#mst-code-'+uid)?.value||null):null),
                notes: d.querySelector('#mst-notes-'+uid)?.value.trim()||null,
            };
            try {
                const saved=await apiFetch('/workorders/'+WO_ID+'/measurements',{method:'POST',body:JSON.stringify(body)});
                measurements.push(saved); refreshActive();
            } catch(e){ err.textContent=e.message; err.classList.remove('d-none'); btn.disabled=false; }
        });
        return d;
    }

    function effectiveLimits(param) {
        if(USE_WEAR&&param.wear_dim_min!=null) return {source:'wear',min:param.wear_dim_min,max:param.wear_dim_max};
        return {source:'orig',min:param.orig_dim_min,max:param.orig_dim_max};
    }

    function refreshActive() {
        const part=partsTree.find(p=>p.id===activePartId);
        if(part&&activeParam){
            const freshParam=part.params.find(p=>p.id===activeParam.id)||activeParam;
            activeParam=freshParam;
            renderParamRows(freshParam, paramMeasurements(freshParam));
            renderPartsList();
            if(activeFigure) renderMarkers(part, freshParam, activeFigure);
        }
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
            MISSING_CODE_ID=(allCodes.find(c=>c.name==='Missing')||{}).id||null;
            partsTree=buildPartsTree();
            if(loadingEl) loadingEl.style.display='none';
            if(partsTree.length) expandedPartIds.add(partsTree[0].id);
            renderPartsList();
        } catch(e) {
            if(loadingEl){ loadingEl.style.display=''; loadingEl.textContent='Failed to load: '+e.message; }
        }
    }

    /* ── TDR Modal ────────────────────────────────────────────── */
    let tdrModal = null;
    let activeTdrMeasurement = null;
    let activeTdrParam = null;

    function openTdrModal(param, failMeas) {
        activeTdrParam = param;
        activeTdrMeasurement = failMeas[0]; // fallback для isMissing check

        const part = partsTree.find(p => p.id === activePartId);
        document.getElementById('msTdrParamLabel').textContent =
            (part ? part.label + ' — ' : '') + param.description;
        document.getElementById('msTdrPn').value = '';
        document.getElementById('msTdrSn').value = '';
        document.getElementById('msTdrQty').value = '1';
        document.getElementById('msTdrComponentInfo').textContent = '';
        document.getElementById('msTdrCompList').style.display = 'none';
        document.getElementById('msTdrErr').classList.add('d-none');
        document.getElementById('msTdrRuleNote').classList.add('d-none');

        // Collect unique matched rules from fail measurements
        const matchedRuleIds = new Set(
            failMeas.map(m => m.manual_parameter_repair_rule_id).filter(Boolean)
        );
        const allRules = param.repair_rules || [];
        const matchedRules = allRules.filter(r => matchedRuleIds.has(r.id));
        const hasNoRuleFail = failMeas.some(m => !m.manual_parameter_repair_rule_id &&
            !(MISSING_CODE_ID && m.codes_id == MISSING_CODE_ID));
        const missingFails = failMeas.filter(m => MISSING_CODE_ID && m.codes_id == MISSING_CODE_ID);

        const ruleWrap = document.getElementById('msTdrRuleWrap');
        const ruleList = document.getElementById('msTdrRuleList');
        ruleList.innerHTML = '';

        const repairRules  = matchedRules.filter(r => !r.order_replacement);
        const orderNewRules = matchedRules.filter(r => r.order_replacement);
        const totalChoices = repairRules.length + orderNewRules.length
            + (hasNoRuleFail ? 1 : 0) + missingFails.length;

        if (totalChoices > 0) {
            ruleWrap.classList.remove('d-none');

            // Missing Part entries (always order_new, no rule)
            missingFails.forEach(m => {
                const item = document.createElement('div');
                item.className = 'form-check mb-1';
                item.innerHTML = `<input class="form-check-input ms-tdr-rule-cb" type="checkbox"
                    data-group="order_new" data-missing="1" data-meas-id="${m.id}"
                    id="msTdrRuleMissing-${m.id}" checked>
                    <label class="form-check-label" for="msTdrRuleMissing-${m.id}">
                        <span class="badge bg-danger me-1" style="font-size:10px">Missing Part</span>
                        <span class="text-secondary">Order New</span>
                    </label>`;
                ruleList.appendChild(item);
            });

            // Order New rules
            orderNewRules.forEach(r => {
                const item = document.createElement('div');
                item.className = 'form-check mb-1';
                item.innerHTML = `<input class="form-check-input ms-tdr-rule-cb" type="checkbox"
                    data-group="order_new" data-rule-id="${r.id}"
                    id="msTdrRule-${r.id}" checked>
                    <label class="form-check-label" for="msTdrRule-${r.id}">
                        <span class="fw-semibold">${escHtml(r.name || 'Rule #' + r.id)}</span>
                        <span class="text-secondary ms-1">Order New</span>
                    </label>`;
                ruleList.appendChild(item);
            });

            // Repair rules
            repairRules.forEach(r => {
                const procCount = (r.processes || []).length;
                const item = document.createElement('div');
                item.className = 'form-check mb-1';
                item.innerHTML = `<input class="form-check-input ms-tdr-rule-cb" type="checkbox"
                    data-group="repair" data-rule-id="${r.id}"
                    id="msTdrRule-${r.id}" checked>
                    <label class="form-check-label" for="msTdrRule-${r.id}">
                        <span class="fw-semibold">${escHtml(r.name || 'Rule #' + r.id)}</span>
                        <span class="text-secondary ms-1">Repair · ${procCount} proc.</span>
                    </label>`;
                ruleList.appendChild(item);
            });

            // No-rule FAIL
            if (hasNoRuleFail) {
                const item = document.createElement('div');
                item.className = 'form-check mb-1';
                item.innerHTML = `<input class="form-check-input ms-tdr-rule-cb" type="checkbox"
                    data-group="repair" data-rule-id=""
                    id="msTdrRuleNone" checked>
                    <label class="form-check-label" for="msTdrRuleNone">
                        <span class="text-secondary fst-italic">Basic repair (no rule)</span>
                    </label>`;
                ruleList.appendChild(item);
            }

            // Mutual exclusion: order_new ↔ repair
            ruleList.addEventListener('change', function(e) {
                const cb = e.target;
                if (!cb.classList.contains('ms-tdr-rule-cb')) return;
                const clickedGroup = cb.dataset.group;
                const oppositeGroup = clickedGroup === 'order_new' ? 'repair' : 'order_new';
                if (cb.checked) {
                    ruleList.querySelectorAll(`.ms-tdr-rule-cb[data-group="${oppositeGroup}"]`)
                        .forEach(el => { el.checked = false; el.disabled = true; });
                    const note = document.getElementById('msTdrRuleNote');
                    if (ruleList.querySelector(`.ms-tdr-rule-cb[data-group="${oppositeGroup}"]`)) {
                        note.textContent = clickedGroup === 'order_new'
                            ? 'Order New selected — Repair rules disabled'
                            : 'Repair selected — Order New rules disabled';
                        note.classList.remove('d-none');
                    }
                } else {
                    // Re-enable opposite if nothing in clicked group is checked
                    const anyChecked = [...ruleList.querySelectorAll(`.ms-tdr-rule-cb[data-group="${clickedGroup}"]`)]
                        .some(el => el.checked);
                    if (!anyChecked) {
                        ruleList.querySelectorAll(`.ms-tdr-rule-cb[data-group="${oppositeGroup}"]`)
                            .forEach(el => { el.disabled = false; });
                        document.getElementById('msTdrRuleNote').classList.add('d-none');
                    }
                }
            });

            // Add override options if only one group present
            const hasRepairItems   = repairRules.length > 0 || hasNoRuleFail;
            const hasOrderNewItems = orderNewRules.length > 0 || missingFails.length > 0;

            if (hasOrderNewItems && !hasRepairItems) {
                // Only Order New matched → offer Repair override
                const sep = document.createElement('div');
                sep.className = 'text-secondary mt-2 mb-1'; sep.style.fontSize='10px';
                sep.textContent = '— or override —';
                ruleList.appendChild(sep);
                const item = document.createElement('div');
                item.className = 'form-check mb-1';
                item.innerHTML = `<input class="form-check-input ms-tdr-rule-cb" type="checkbox"
                    data-group="repair" data-rule-id="" data-override="1"
                    id="msTdrOverrideRepair">
                    <label class="form-check-label text-secondary fst-italic" for="msTdrOverrideRepair">
                        Repair (override — no rule)
                    </label>`;
                ruleList.appendChild(item);
            } else if (hasRepairItems && !hasOrderNewItems) {
                // Only Repair matched → offer Order New override
                const sep = document.createElement('div');
                sep.className = 'text-secondary mt-2 mb-1'; sep.style.fontSize='10px';
                sep.textContent = '— or override —';
                ruleList.appendChild(sep);
                const item = document.createElement('div');
                item.className = 'form-check mb-1';
                item.innerHTML = `<input class="form-check-input ms-tdr-rule-cb" type="checkbox"
                    data-group="order_new" data-rule-id="" data-override="1"
                    id="msTdrOverrideOrderNew">
                    <label class="form-check-label text-secondary fst-italic" for="msTdrOverrideOrderNew">
                        Order New (override)
                    </label>`;
                ruleList.appendChild(item);
            }

            // Init: if both groups present → uncheck Order New, keep Repair checked, user must choose
            const orderNewEls = [...ruleList.querySelectorAll('.ms-tdr-rule-cb[data-group="order_new"]')];
            const repairEls   = [...ruleList.querySelectorAll('.ms-tdr-rule-cb[data-group="repair"]')];
            if (orderNewEls.length && repairEls.length) {
                orderNewEls.forEach(el => { el.checked = false; });
                const note = document.getElementById('msTdrRuleNote');
                note.textContent = 'Select: Repair rules OR Order New — not both';
                note.classList.remove('d-none');
            }
        } else {
            ruleWrap.classList.add('d-none');
        }

        if (!tdrModal) tdrModal = new bootstrap.Modal(document.getElementById('msTdrModal'));
        tdrModal.show();
    }

    let iplLookupTimer = null;

    function selectTdrComponent(comp) {
        document.getElementById('msTdrPn').value        = comp.ipl_num;
        document.getElementById('msTdrQty').value       = comp.units_assy ?? 1;
        document.getElementById('msTdrComponentInfo').textContent = comp.part_number + (comp.name ? ' — ' + comp.name : '');
        document.getElementById('msTdrCompList').style.display = 'none';
        document.getElementById('msTdrCompList').innerHTML = '';
    }

    document.getElementById('msTdrPn')?.addEventListener('input', function () {
        clearTimeout(iplLookupTimer);
        const val = this.value.trim();
        const info = document.getElementById('msTdrComponentInfo');
        const list = document.getElementById('msTdrCompList');
        if (!val) {
            info.textContent = '';
            list.style.display = 'none';
            list.innerHTML = '';
            return;
        }
        iplLookupTimer = setTimeout(async () => {
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
                        btn.innerHTML = '<span class="fw-semibold">' + escHtml(comp.ipl_num) + '</span>'
                            + ' <span class="text-secondary">' + escHtml(comp.part_number) + '</span>'
                            + (comp.name ? ' <span class="text-muted fst-italic">' + escHtml(comp.name) + '</span>' : '');
                        btn.addEventListener('click', () => selectTdrComponent(comp));
                        list.appendChild(btn);
                    });
                    list.style.display = 'block';
                }
            } catch { info.textContent = ''; }
        }, 400);
    });

    document.getElementById('msTdrSaveBtn')?.addEventListener('click', async function () {
        const err = document.getElementById('msTdrErr');
        err.classList.add('d-none');
        const pn  = document.getElementById('msTdrPn').value.trim();
        const sn  = document.getElementById('msTdrSn').value.trim();
        const qty = parseInt(document.getElementById('msTdrQty').value, 10) || 1;

        // Collect checked rules
        const checkedCbs = [...document.querySelectorAll('#msTdrRuleList .ms-tdr-rule-cb:checked')];
        const isMissingSelected = checkedCbs.some(cb => cb.dataset.missing === '1');
        const missingMeasId = isMissingSelected
            ? parseInt(checkedCbs.find(cb => cb.dataset.missing === '1').dataset.measId, 10) : null;
        const ruleIds = checkedCbs
            .filter(cb => !cb.dataset.missing && cb.dataset.ruleId)
            .map(cb => parseInt(cb.dataset.ruleId, 10));
        const hasNoRuleChecked   = checkedCbs.some(cb => !cb.dataset.missing && cb.dataset.ruleId === '' && cb.dataset.group === 'repair');
        const hasOrderNewOverride = checkedCbs.some(cb => !cb.dataset.missing && cb.dataset.ruleId === '' && cb.dataset.group === 'order_new');

        if (!pn) { err.textContent = 'IPL# is required.'; err.classList.remove('d-none'); return; }
        this.disabled = true;
        try {
            await apiFetch('/workorders/' + WO_ID + '/tdr-from-measurement', {
                method: 'POST',
                body: JSON.stringify({
                    wo_measurement_id: activeTdrMeasurement.id,
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
        } catch (e) {
            err.textContent = e.message;
            err.classList.remove('d-none');
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('tab-measurements')?.addEventListener('shown.bs.tab', function(){
        if(!loaded){ loaded=true; loadData(); }
    });
    if(document.getElementById('content-measurements')?.classList.contains('active')){
        loaded=true; loadData();
    }
})();
</script>
