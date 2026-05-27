@extends('admin.master')

@section('style')
<style>
    #ms-wrap {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 56px);
        overflow: hidden;
    }
    #ms-topbar {
        padding: 6px 14px;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        flex-shrink: 0;
        font-size: 13px;
    }
    #ms-body { display: flex; flex: 1 1 auto; overflow: hidden; }

    /* Parts panel */
    #ms-parts-panel {
        width: 220px; min-width: 220px;
        border-right: 1px solid var(--bs-border-color);
        display: flex; flex-direction: column; overflow: hidden;
    }
    #ms-parts-list { flex: 1 1 auto; overflow-y: auto; padding: 4px 0; }
    .ms-part-group { border-bottom: 1px solid var(--bs-border-color); }
    .ms-part-header {
        padding: 5px 8px 5px 10px;
        display: flex; align-items: center; gap: 6px;
        cursor: pointer; font-size: 11px; font-weight: 600;
        color: #5ee3ff; user-select: none;
    }
    .ms-part-header:hover { background: rgba(94,227,255,.06); }
    .ms-part-chevron { font-size: 9px; margin-left: auto; opacity: .6; }
    .ms-part-points { display: none; }
    .ms-part-points.open { display: block; }
    .ms-point-item {
        padding: 4px 10px 4px 20px;
        cursor: pointer; display: flex; align-items: center; gap: 6px;
        font-size: 11px; border-left: 3px solid transparent;
    }
    .ms-point-item:hover { background: rgba(13,110,253,.07); }
    .ms-point-item.active { border-left-color: #0d6efd; background: rgba(13,110,253,.12); font-weight: 600; }
    .ms-status-dot {
        width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        border: 1.5px solid rgba(255,255,255,.3);
    }
    .ms-status-dot.pass    { background: #198754; border-color: #28a745; }
    .ms-status-dot.fail    { background: #dc3545; border-color: #e04657; }
    .ms-status-dot.partial { background: #ffc107; border-color: #ffca2c; }
    .ms-status-dot.none    { background: #6c757d; border-color: #868e96; }
    .ms-part-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .ms-part-dot.pass    { background: #198754; }
    .ms-part-dot.fail    { background: #dc3545; }
    .ms-part-dot.partial { background: #ffc107; }
    .ms-part-dot.none    { background: #6c757d; }

    /* Figure viewer */
    #ms-viewer-panel { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }
    #ms-figure-label {
        padding: 4px 10px; font-size: 11px;
        color: var(--bs-secondary-color);
        border-bottom: 1px solid var(--bs-border-color); flex-shrink: 0;
    }
    #ms-figure-canvas-wrap {
        flex: 1 1 auto; overflow: auto; position: relative;
        background: rgba(0,0,0,.04);
        display: flex; align-items: flex-start; justify-content: center;
    }
    #ms-figure-img-container { position: relative; display: inline-block; user-select: none; margin: auto; }
    #ms-figure-img { display: block; max-width: 100%; height: auto; }
    #ms-empty-viewer {
        display: flex; align-items: center; justify-content: center;
        height: 100%; width: 100%;
        color: var(--bs-secondary-color); font-size: 14px;
        flex-direction: column; gap: 8px;
    }
    .ms-point-marker {
        position: absolute; transform: translate(-50%, -50%);
        width: 22px; height: 22px; border-radius: 50%;
        border: 2px solid #fff; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 8px; font-weight: 700; color: #fff;
        z-index: 10; box-shadow: 0 1px 4px rgba(0,0,0,.5); transition: transform .12s;
    }
    .ms-point-marker:hover { transform: translate(-50%, -50%) scale(1.2); }
    .ms-point-marker.active { transform: translate(-50%, -50%) scale(1.3); box-shadow: 0 0 0 3px rgba(255,255,255,.5),0 2px 6px rgba(0,0,0,.5); }
    .ms-point-marker.status-pass    { background: #198754; }
    .ms-point-marker.status-fail    { background: #dc3545; }
    .ms-point-marker.status-partial { background: #ffc107; color: #000; }
    .ms-point-marker.status-none    { background: #6c757d; }

    /* Entry panel */
    #ms-entry-panel {
        width: 440px; min-width: 320px;
        border-left: 1px solid var(--bs-border-color);
        display: flex; flex-direction: column; overflow: hidden;
    }
    #ms-entry-header {
        padding: 7px 12px; border-bottom: 1px solid var(--bs-border-color);
        font-size: 12px; font-weight: 600; flex-shrink: 0;
    }
    #ms-entry-body { flex: 1 1 auto; overflow-y: auto; padding: 8px; }

    /* Spec cards */
    .ms-spec-card { border: 1px solid var(--bs-border-color); border-radius: 6px; margin-bottom: 10px; overflow: hidden; font-size: 12px; }
    .ms-spec-head { padding: 5px 10px; background: rgba(0,0,0,.04); border-bottom: 1px solid var(--bs-border-color); display: flex; align-items: center; gap: 6px; }
    .ms-spec-limits { padding: 4px 10px; border-bottom: 1px solid var(--bs-border-color); display: flex; flex-wrap: nowrap; gap: 4px; }
    .ms-limit-cell { flex: 1 1 0; min-width: 0; background: rgba(0,0,0,.05); border-radius: 3px; padding: 2px 5px; }
    .ms-limit-cell-lbl { color: var(--bs-secondary-color); font-size: 9px; white-space: nowrap; }
    .ms-limit-cell-val { font-family: monospace; font-weight: 600; font-size: 11px; white-space: nowrap; }
    .ms-wear-cell { background: rgba(255,193,7,.1); }

    /* Measurement rows */
    .ms-meas-record { padding: 5px 10px; border-bottom: 1px solid var(--bs-border-color); display: flex; align-items: center; gap: 8px; }
    .ms-meas-stage-badge { font-size: 10px; padding: 1px 5px; border-radius: 3px; background: rgba(108,117,125,.15); color: var(--bs-secondary-color); flex-shrink: 0; }
    .ms-meas-val { font-family: monospace; font-weight: 700; font-size: 13px; }
    .ms-meas-result-pass { color: #198754; }
    .ms-meas-result-fail { color: #dc3545; }
    .ms-meas-result-null { color: var(--bs-secondary-color); }
    .ms-meas-meta { font-size: 10px; color: var(--bs-secondary-color); }

    /* Entry form */
    .ms-entry-form { padding: 8px 10px; }
    .ms-form-row { margin-bottom: 7px; }
    .ms-form-label { font-size: 11px; color: var(--bs-secondary-color); margin-bottom: 2px; }
</style>
@endsection

@section('content')
@php
    $manual = $workorder->unit->manuals;
    $useWear = $workorder->instruction?->name === 'Repair';
@endphp

<div id="ms-wrap">
    <div id="ms-topbar">
        <a href="{{ route('mains.show', $workorder->id) }}"
           class="btn btn-outline-secondary btn-sm" style="font-size:12px;padding:3px 10px">
            <i class="bi bi-arrow-left"></i> WO
        </a>
        <span class="fw-semibold">{{ $manual->number ?? '—' }}</span>
        <span class="badge text-bg-info text-dark" style="font-size:11px">
            {{ $workorder->instruction?->name ?? '—' }}
        </span>
        @if($useWear)
            <span class="badge text-bg-warning text-dark" style="font-size:11px">Wear limits</span>
        @endif
        <span class="text-secondary ms-auto" style="font-size:11px">WO #{{ $workorder->id }}</span>
    </div>

    <div id="ms-body">
        <div id="ms-parts-panel">
            <div class="px-2 py-2 border-bottom" style="font-size:11px;font-weight:600;flex-shrink:0;color:var(--bs-secondary-color)">PARTS</div>
            <div id="ms-parts-list"></div>
        </div>

        <div id="ms-viewer-panel">
            <div id="ms-figure-label">— select a point —</div>
            <div id="ms-figure-canvas-wrap">
                <div id="ms-empty-viewer">
                    <i class="bi bi-cursor" style="font-size:2.5rem;opacity:.25"></i>
                    <span style="font-size:13px">Select a point from the list</span>
                </div>
                <div id="ms-figure-img-container" style="display:none">
                    <img id="ms-figure-img" src="" alt="">
                    <div id="ms-points-overlay"></div>
                </div>
            </div>
        </div>

        <div id="ms-entry-panel">
            <div id="ms-entry-header">
                <span id="ms-entry-title" style="color:var(--bs-secondary-color);font-weight:400">Select a point</span>
            </div>
            <div id="ms-entry-body">
                <div class="text-center text-secondary py-5" id="ms-entry-empty" style="font-size:12px">
                    <i class="bi bi-rulers" style="font-size:2rem;display:block;opacity:.25;margin-bottom:.5rem"></i>
                    Click a point to record measurements
                </div>
                <div id="ms-spec-cards" class="d-none"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const WO_ID       = @json($workorder->id);
    const CSRF        = @json(csrf_token());
    const USE_WEAR    = @json($useWear);

    @php
        $meas = $measurements->map(fn($m) => [
            'id'                      => $m->id,
            'manual_dimension_spec_id'=> $m->manual_dimension_spec_id,
            'stage'                   => $m->stage,
            'replaces_id'             => $m->replaces_id,
            'actual_value'            => $m->actual_value,
            'limits_source'           => $m->limits_source,
            'result'                  => $m->result,
            'finding_notes'           => $m->finding_notes,
            'repair_required'         => $m->repair_required,
            'repair_action'           => $m->repair_action,
            'user'                    => $m->user ? ['name' => $m->user->name] : null,
            'notes'                   => $m->notes,
        ])->values();

        $icForJs = $inspectionComponents->map(fn($ic) => [
            'id'    => $ic->id,
            'label' => $ic->label,
        ])->values();

        $figuresForJs = $figures->map(fn($fig) => [
            'id'         => $fig->id,
            'title'      => $fig->title,
            'image_path' => $fig->image_path ?? null,
            'points'     => $fig->points->map(fn($pt) => [
                'id'          => $pt->id,
                'code'        => $pt->code,
                'description' => $pt->description,
                'x_pct'       => $pt->x_pct,
                'y_pct'       => $pt->y_pct,
                'specs'       => $pt->specs->map(fn($s) => [
                    'id'                      => $s->id,
                    'description'             => $s->description,
                    'spec_type'               => $s->spec_type,
                    'inspection_component_id' => $s->inspection_component_id,
                    'is_required'             => $s->is_required,
                    'orig_dim_min'            => $s->orig_dim_min,
                    'orig_dim_max'            => $s->orig_dim_max,
                    'wear_dim_min'            => $s->wear_dim_min,
                    'wear_dim_max'            => $s->wear_dim_max,
                    'inspection'              => $s->inspection,
                ])->values(),
            ])->values(),
        ])->values();
    @endphp

    let measurements    = @json($meas);
    const inspComponents = @json($icForJs);
    const figures        = @json($figuresForJs);
    const codes          = @json($codes);
    const procs          = [];

    /* measBySpec: specId → array sorted by id */
    let measBySpec = {};
    function rebuildMeasBySpec() {
        measBySpec = {};
        measurements.forEach(m => {
            if (!measBySpec[m.manual_dimension_spec_id]) measBySpec[m.manual_dimension_spec_id] = [];
            measBySpec[m.manual_dimension_spec_id].push(m);
        });
        Object.keys(measBySpec).forEach(k => measBySpec[k].sort((a, b) => a.id - b.id));
    }
    rebuildMeasBySpec();

    /* Build Parts → Points tree */
    function buildPartsTree() {
        return inspComponents.map(ic => {
            const pts = [];
            const seen = new Set();
            figures.forEach(fig => {
                (fig.points || []).forEach(pt => {
                    const ptSpecs = (pt.specs || []).filter(s => s.inspection_component_id === ic.id);
                    if (ptSpecs.length > 0 && !seen.has(pt.id)) {
                        seen.add(pt.id);
                        pts.push({ ...pt, figure: fig, specs: ptSpecs });
                    }
                });
            });
            return { ...ic, points: pts };
        }).filter(ic => ic.points.length > 0);
    }

    const partsTree = buildPartsTree();
    let expandedPartIds = new Set();
    let activePartId    = null;
    let activePoint     = null;

    const partsList       = document.getElementById('ms-parts-list');
    const figureLabel     = document.getElementById('ms-figure-label');
    const emptyViewer     = document.getElementById('ms-empty-viewer');
    const figImgContainer = document.getElementById('ms-figure-img-container');
    const figImg          = document.getElementById('ms-figure-img');
    const pointsOverlay   = document.getElementById('ms-points-overlay');
    const entryTitle      = document.getElementById('ms-entry-title');
    const entryEmpty      = document.getElementById('ms-entry-empty');
    const specCards       = document.getElementById('ms-spec-cards');

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmtDim(v) { return v !== null && v !== undefined ? parseFloat(v).toFixed(4) : '—'; }

    async function apiFetch(url, opts = {}) {
        const res = await fetch(url, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json', ...(opts.headers || {}) },
            ...opts,
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || json.error || 'Request failed');
        return json;
    }

    function effectiveLimits(spec) {
        if (USE_WEAR && spec.wear_dim_min !== null) {
            return { source: 'wear', min: spec.wear_dim_min, max: spec.wear_dim_max };
        }
        return { source: 'orig', min: spec.orig_dim_min, max: spec.orig_dim_max };
    }

    /* Status helpers */
    function specStatus(spec) {
        const ms = measBySpec[spec.id] || [];
        const finals   = ms.filter(m => m.stage === 'final');
        const initials = ms.filter(m => m.stage === 'initial');
        const current  = finals.length ? finals[finals.length - 1] : (initials.length ? initials[initials.length - 1] : null);
        if (!current) return 'none';
        if (current.result === 'PASS') return 'pass';
        if (current.result === 'FAIL') return 'fail';
        return 'partial';
    }

    function pointStatus(pt) {
        const reqSpecs = (pt.specs || []).filter(s => s.is_required);
        if (reqSpecs.length === 0) return 'none';
        let allPass = true, anyFail = false, anyMissing = false;
        reqSpecs.forEach(s => {
            const st = specStatus(s);
            if (st === 'fail')             { anyFail = true; allPass = false; }
            if (st === 'none' || st === 'partial') { anyMissing = true; allPass = false; }
        });
        if (anyFail) return 'fail';
        if (anyMissing) return 'partial';
        return 'pass';
    }

    function partStatus(part) {
        const sts = part.points.map(pt => pointStatus(pt));
        if (sts.includes('fail'))    return 'fail';
        if (sts.includes('partial')) return 'partial';
        if (sts.every(s => s === 'pass')) return 'pass';
        return 'none';
    }

    /* Render Parts list */
    function renderPartsList() {
        partsList.innerHTML = '';
        if (partsTree.length === 0) {
            partsList.innerHTML = '<div class="px-3 py-2 text-secondary" style="font-size:11px">No parts defined in manual</div>';
            return;
        }
        partsTree.forEach(part => {
            const isOpen = expandedPartIds.has(part.id);
            const pSt    = partStatus(part);

            const group = document.createElement('div');
            group.className = 'ms-part-group';

            const hdr = document.createElement('div');
            hdr.className = 'ms-part-header';
            hdr.innerHTML = `<span class="ms-part-dot ${pSt}"></span>
                <span>${esc(part.label)}</span>
                <i class="bi bi-chevron-${isOpen ? 'up' : 'down'} ms-part-chevron"></i>`;
            hdr.addEventListener('click', () => {
                expandedPartIds.has(part.id) ? expandedPartIds.delete(part.id) : expandedPartIds.add(part.id);
                renderPartsList();
            });
            group.appendChild(hdr);

            const ptList = document.createElement('div');
            ptList.className = 'ms-part-points' + (isOpen ? ' open' : '');
            part.points.forEach(pt => {
                const st = pointStatus(pt);
                const el = document.createElement('div');
                el.className = 'ms-point-item' + (activePoint?.id === pt.id && activePartId === part.id ? ' active' : '');
                el.innerHTML = `<span class="ms-status-dot ${st}"></span>
                    <span class="text-truncate"><strong>${esc(pt.code)}</strong>${pt.description ? ' <span class="text-secondary">' + esc(pt.description) + '</span>' : ''}</span>`;
                el.addEventListener('click', () => selectPoint(part, pt));
                ptList.appendChild(el);
            });
            group.appendChild(ptList);
            partsList.appendChild(group);
        });
    }

    function selectPoint(part, pt) {
        activePartId = part.id;
        activePoint  = pt;
        expandedPartIds.add(part.id);
        renderPartsList();

        const fig = pt.figure;
        if (fig?.image_path) {
            emptyViewer.style.display = 'none';
            figImgContainer.style.display = '';
            figImg.src = fig.image_path;
            figureLabel.textContent = fig.title;
            renderFigureMarkers(part, pt, fig);
        } else {
            emptyViewer.style.display = '';
            figImgContainer.style.display = 'none';
            figureLabel.textContent = '— no figure image —';
        }

        renderEntryPanel(part, pt);
    }

    function renderFigureMarkers(activePart, activePt, fig) {
        pointsOverlay.innerHTML = '';
        const partPtIds = new Set(activePart.points.map(p => p.id));
        fig.points.forEach(pt => {
            if (!partPtIds.has(pt.id)) return;
            const ptFull = activePart.points.find(p => p.id === pt.id) || pt;
            const st  = pointStatus(ptFull);
            const cls = { pass:'status-pass', fail:'status-fail', partial:'status-partial', none:'status-none' }[st] || 'status-none';
            const m = document.createElement('div');
            m.className = 'ms-point-marker ' + cls + (activePt?.id === pt.id ? ' active' : '');
            m.style.left = pt.x_pct + '%';
            m.style.top  = pt.y_pct + '%';
            m.title = pt.code + (pt.description ? ': ' + pt.description : '');
            m.textContent = pt.code.length <= 3 ? pt.code : pt.code.slice(0,3);
            m.addEventListener('click', () => selectPoint(activePart, ptFull));
            pointsOverlay.appendChild(m);
        });
    }

    function renderEntryPanel(part, pt) {
        entryTitle.textContent = pt.code + (pt.description ? ' — ' + pt.description : '');
        entryTitle.style.fontWeight = '600';
        entryTitle.style.color = '';
        entryEmpty.classList.add('d-none');
        specCards.classList.remove('d-none');
        specCards.innerHTML = '';
        (pt.specs || []).forEach(spec => specCards.appendChild(buildSpecCard(spec)));
    }

    function buildSpecCard(spec) {
        const ms  = measBySpec[spec.id] || [];
        const lim = effectiveLimits(spec);
        const reqBadge = spec.is_required ? '<span class="badge text-bg-secondary" style="font-size:9px">req</span>' : '';
        const srcBadge = lim.source === 'wear' ? '<span class="badge text-bg-warning text-dark" style="font-size:9px">wear</span>' : '';
        const hasLimits = lim.min !== null || lim.max !== null;

        const card = document.createElement('div');
        card.className = 'ms-spec-card';
        card.dataset.specId = spec.id;
        card.innerHTML = `
            <div class="ms-spec-head">
                <span style="font-weight:600">${esc(spec.description)}</span>
                <span class="ms-1 d-flex gap-1">${reqBadge}${srcBadge}</span>
            </div>
            ${hasLimits ? `<div class="ms-spec-limits">
                <div class="ms-limit-cell"><div class="ms-limit-cell-lbl">orig min</div><div class="ms-limit-cell-val">${fmtDim(spec.orig_dim_min)}</div></div>
                <div class="ms-limit-cell"><div class="ms-limit-cell-lbl">orig max</div><div class="ms-limit-cell-val">${fmtDim(spec.orig_dim_max)}</div></div>
                ${spec.wear_dim_min !== null ? `
                <div class="ms-limit-cell ms-wear-cell"><div class="ms-limit-cell-lbl">wear min</div><div class="ms-limit-cell-val">${fmtDim(spec.wear_dim_min)}</div></div>
                <div class="ms-limit-cell ms-wear-cell"><div class="ms-limit-cell-lbl">wear max</div><div class="ms-limit-cell-val">${fmtDim(spec.wear_dim_max)}</div></div>` : ''}
            </div>` : ''}
            <div id="ms-meas-records-${spec.id}"></div>
            <div id="ms-meas-form-${spec.id}"></div>
        `;

        renderSpecMeasurements(spec, ms, card);
        return card;
    }

    function renderSpecMeasurements(spec, ms, card) {
        const recDiv  = card.querySelector('#ms-meas-records-' + spec.id);
        const formDiv = card.querySelector('#ms-meas-form-'    + spec.id);
        recDiv.innerHTML = formDiv.innerHTML = '';

        const initials = ms.filter(m => m.stage === 'initial');
        const finals   = ms.filter(m => m.stage === 'final');
        const lastInit = initials[initials.length - 1] || null;
        const lastFin  = finals[finals.length - 1]     || null;

        ms.forEach(m => recDiv.appendChild(buildMeasRecord(m, spec)));

        if (!lastInit) {
            formDiv.appendChild(buildEntryForm(spec, 'initial', null));
        } else if (lastInit.result === 'FAIL' && !lastFin) {
            const wrap = document.createElement('div');
            wrap.style.cssText = 'padding:6px 10px;border-top:1px solid var(--bs-border-color)';
            wrap.innerHTML = `<button class="btn btn-outline-warning btn-sm w-100" style="font-size:11px">
                <i class="bi bi-plus-circle"></i> Add Final Measurement (after repair)
            </button>`;
            wrap.querySelector('button').addEventListener('click', function () {
                wrap.replaceWith(buildEntryForm(spec, 'final', lastInit.id));
            });
            formDiv.appendChild(wrap);
        }
    }

    function buildMeasRecord(m, spec) {
        const rc  = m.result === 'PASS' ? 'ms-meas-result-pass' : (m.result === 'FAIL' ? 'ms-meas-result-fail' : 'ms-meas-result-null');
        const div = document.createElement('div');
        div.className = 'ms-meas-record';
        div.innerHTML = `
            <span class="ms-meas-stage-badge">${m.stage}</span>
            <span class="ms-meas-val ${rc}">${m.actual_value !== null ? fmtDim(m.actual_value) : '<em class="fw-normal" style="font-size:11px">no value</em>'}</span>
            <span class="${rc}" style="font-weight:700;font-size:12px">${m.result || (m.actual_value === null ? 'no value' : '—')}</span>
            ${m.finding_notes ? '<span class="ms-meas-meta">' + esc(m.finding_notes) + '</span>' : ''}
            ${m.repair_action ? '<span class="badge text-bg-secondary" style="font-size:9px">' + m.repair_action + '</span>' : ''}
            <span class="ms-meas-meta ms-auto">${m.user?.name ? 'by ' + m.user.name : ''}</span>
            <button class="btn btn-link btn-sm p-0 ms-1 text-danger" style="font-size:11px" title="Delete"><i class="bi bi-x-lg"></i></button>
        `;
        div.querySelector('button').addEventListener('click', async function () {
            if (!confirm('Delete this measurement?')) return;
            try {
                await apiFetch('/measurements/' + m.id, { method: 'DELETE' });
                measurements = measurements.filter(x => x.id !== m.id);
                rebuildMeasBySpec();
                refreshActivePanel();
            } catch (e) { alert(e.message); }
        });
        return div;
    }

    function buildEntryForm(spec, stage, replacesId) {
        const codesOpts = codes.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');

        const div = document.createElement('div');
        div.className = 'ms-entry-form';
        div.innerHTML = `
            <div style="font-size:11px;font-weight:600;color:var(--bs-secondary-color);margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid var(--bs-border-color)">
                ${stage === 'final' ? '📏 Final measurement (after repair)' : '📏 Record measurement'}
            </div>
            <div class="ms-form-row d-flex gap-2 align-items-end">
                <div style="flex:1">
                    <div class="ms-form-label">Actual value</div>
                    <input type="number" class="form-control form-control-sm" step="0.0001" id="ms-val-${spec.id}" placeholder="0.0000" style="font-family:monospace;font-size:13px">
                </div>
                <div class="form-check mb-1 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" id="ms-noval-${spec.id}">
                    <label class="form-check-label" for="ms-noval-${spec.id}" style="font-size:11px">No value</label>
                </div>
            </div>
            <div class="ms-form-row">
                <div class="ms-form-label">Finding code</div>
                <select class="form-select form-select-sm" id="ms-code-${spec.id}">
                    <option value="">— None —</option>${codesOpts}
                </select>
            </div>
            <div class="ms-form-row">
                <div class="ms-form-label">Finding notes</div>
                <input type="text" class="form-control form-control-sm" id="ms-fnotes-${spec.id}" placeholder="Corrosion, wear, damage…">
            </div>
            <div class="ms-form-row">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="ms-rreq-${spec.id}">
                    <label class="form-check-label" for="ms-rreq-${spec.id}" style="font-size:12px">Repair required</label>
                </div>
            </div>
            <div id="ms-repair-${spec.id}" class="d-none">
                <div class="ms-form-row">
                    <div class="ms-form-label">Repair action</div>
                    <select class="form-select form-select-sm" id="ms-raction-${spec.id}">
                        <option value="">— Select —</option>
                        ${['replace','oversize','blend','machine','scrap','other'].map(a => `<option value="${a}">${a[0].toUpperCase()+a.slice(1)}</option>`).join('')}
                    </select>
                </div>
            </div>
            <div class="ms-form-row">
                <div class="ms-form-label">Notes</div>
                <textarea class="form-control form-control-sm" id="ms-notes-${spec.id}" rows="2" placeholder="Optional…"></textarea>
            </div>
            <div class="text-danger small d-none" id="ms-err-${spec.id}"></div>
            <button class="btn btn-primary btn-sm w-100 mt-1" style="font-size:12px" id="ms-save-${spec.id}">
                <i class="bi bi-check2"></i> Save
            </button>
        `;

        div.querySelector('#ms-noval-' + spec.id).addEventListener('change', function () {
            div.querySelector('#ms-val-' + spec.id).disabled = this.checked;
        });
        div.querySelector('#ms-rreq-' + spec.id).addEventListener('change', function () {
            div.querySelector('#ms-repair-' + spec.id).classList.toggle('d-none', !this.checked);
        });

        div.querySelector('#ms-save-' + spec.id).addEventListener('click', async function () {
            const errEl = div.querySelector('#ms-err-' + spec.id);
            errEl.classList.add('d-none');
            const noVal  = div.querySelector('#ms-noval-'  + spec.id).checked;
            const valRaw = div.querySelector('#ms-val-'    + spec.id).value;
            const repReq = div.querySelector('#ms-rreq-'   + spec.id).checked;

            const body = {
                manual_dimension_spec_id:   spec.id,
                stage,
                replaces_id:                replacesId || null,
                actual_value:               noVal ? null : (valRaw !== '' ? parseFloat(valRaw) : null),
                codes_id:                   div.querySelector('#ms-code-'   + spec.id).value || null,
                finding_notes:              div.querySelector('#ms-fnotes-' + spec.id).value.trim() || null,
                repair_required:            repReq,
                repair_action:              repReq ? (div.querySelector('#ms-raction-' + spec.id).value || null) : null,
                notes:                      div.querySelector('#ms-notes-'  + spec.id).value.trim() || null,
            };

            try {
                const saved = await apiFetch('/workorders/' + WO_ID + '/measurements', { method: 'POST', body: JSON.stringify(body) });
                measurements.push(saved);
                rebuildMeasBySpec();
                refreshActivePanel();
            } catch (e) {
                errEl.textContent = e.message;
                errEl.classList.remove('d-none');
            }
        });

        return div;
    }

    function refreshActivePanel() {
        const activePart = partsTree.find(p => p.id === activePartId);
        if (activePart && activePoint) {
            renderEntryPanel(activePart, activePoint);
            renderPartsList();
            if (activePoint.figure?.image_path) {
                renderFigureMarkers(activePart, activePoint, activePoint.figure);
            }
        }
    }

    /* Init */
    if (partsTree.length > 0) expandedPartIds.add(partsTree[0].id);
    renderPartsList();
})();
</script>
@endsection
