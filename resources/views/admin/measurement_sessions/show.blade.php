@extends('admin.master')

@section('style')
<style>
    /* ── Layout ────────────────────────────────────────────────── */
    #ms-wrap {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 56px); /* subtract navbar */
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
    #ms-body {
        display: flex;
        flex: 1 1 auto;
        overflow: hidden;
    }

    /* ── Left: points list ─────────────────────────────────────── */
    #ms-points-panel {
        width: 220px;
        min-width: 220px;
        border-right: 1px solid var(--bs-border-color);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #ms-points-list {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 4px 0;
    }
    .ms-point-item {
        padding: 6px 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        border-left: 3px solid transparent;
    }
    .ms-point-item:hover { background: rgba(13,110,253,.07); }
    .ms-point-item.active {
        border-left-color: #0d6efd;
        background: rgba(13,110,253,.12);
        font-weight: 600;
    }
    .ms-status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        border: 1.5px solid rgba(255,255,255,.3);
    }
    .ms-status-dot.pass    { background: #198754; border-color: #28a745; }
    .ms-status-dot.fail    { background: #dc3545; border-color: #e04657; }
    .ms-status-dot.partial { background: #ffc107; border-color: #ffca2c; }
    .ms-status-dot.none    { background: #6c757d; border-color: #868e96; }

    /* ── Center: figure viewer ─────────────────────────────────── */
    #ms-viewer-panel {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #ms-figure-canvas-wrap {
        flex: 1 1 auto;
        overflow: auto;
        position: relative;
        background: rgba(0,0,0,.04);
        display: flex;
        align-items: flex-start;
        justify-content: center;
    }
    #ms-figure-img-container {
        position: relative;
        display: inline-block;
        user-select: none;
        margin: auto;
    }
    #ms-figure-img { display: block; max-width: 100%; height: auto; }
    #ms-empty-viewer {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        width: 100%;
        color: var(--bs-secondary-color);
        font-size: 14px;
        flex-direction: column;
        gap: 8px;
    }

    /* Point markers on figure */
    .ms-point-marker {
        position: absolute;
        transform: translate(-50%, -50%);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        font-weight: 700;
        color: #fff;
        z-index: 10;
        box-shadow: 0 1px 4px rgba(0,0,0,.5);
        transition: transform .12s;
    }
    .ms-point-marker:hover { transform: translate(-50%, -50%) scale(1.2); }
    .ms-point-marker.active { transform: translate(-50%, -50%) scale(1.25); box-shadow: 0 0 0 3px rgba(255,255,255,.5), 0 2px 6px rgba(0,0,0,.5); }
    .ms-point-marker.status-pass    { background: #198754; }
    .ms-point-marker.status-fail    { background: #dc3545; }
    .ms-point-marker.status-partial { background: #ffc107; color: #000; }
    .ms-point-marker.status-none    { background: #6c757d; }

    /* ── Right: entry panel ────────────────────────────────────── */
    #ms-entry-panel {
        width: 440px;
        min-width: 320px;
        border-left: 1px solid var(--bs-border-color);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #ms-entry-header {
        padding: 8px 12px;
        border-bottom: 1px solid var(--bs-border-color);
        font-size: 13px;
        font-weight: 600;
        flex-shrink: 0;
    }
    #ms-entry-body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 8px;
    }

    /* Spec cards */
    .ms-spec-card {
        border: 1px solid var(--bs-border-color);
        border-radius: 6px;
        margin-bottom: 10px;
        overflow: hidden;
        font-size: 12px;
    }
    .ms-spec-head {
        padding: 6px 10px;
        background: rgba(0,0,0,.04);
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ms-spec-limits {
        padding: 5px 10px;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .ms-limit-cell {
        background: rgba(0,0,0,.05);
        border-radius: 3px;
        padding: 2px 6px;
        font-size: 11px;
    }
    .ms-limit-cell-lbl { color: var(--bs-secondary-color); font-size: 10px; }
    .ms-limit-cell-val { font-family: monospace; font-weight: 600; }
    .ms-wear-cell { background: rgba(255,193,7,.1); }

    /* Measurement records */
    .ms-meas-record {
        padding: 5px 10px;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .ms-meas-stage-badge {
        font-size: 10px;
        padding: 1px 5px;
        border-radius: 3px;
        background: rgba(108,117,125,.15);
        color: var(--bs-secondary-color);
        flex-shrink: 0;
    }
    .ms-meas-val {
        font-family: monospace;
        font-weight: 700;
        font-size: 13px;
    }
    .ms-meas-result-pass { color: #198754; }
    .ms-meas-result-fail { color: #dc3545; }
    .ms-meas-result-null { color: var(--bs-secondary-color); }
    .ms-meas-meta { font-size: 10px; color: var(--bs-secondary-color); }

    /* Entry form */
    .ms-entry-form {
        padding: 8px 10px;
    }
    .ms-form-row { margin-bottom: 7px; }
    .ms-form-label { font-size: 11px; color: var(--bs-secondary-color); margin-bottom: 2px; }
    .ms-form-hint  { font-size: 10px; color: var(--bs-secondary-color); margin-top: 2px; }

    /* Finalized overlay */
    .ms-finalized-note {
        font-size: 11px;
        color: var(--bs-secondary-color);
        padding: 6px 10px;
        border-top: 1px solid var(--bs-border-color);
        text-align: center;
    }
</style>
@endsection

@section('content')
@php
    $isFinalized = $session->status === 'finalized';
    $wo          = $session->workorder;
    $figure      = $session->figure;
    $points      = $figure ? ($figure->points ?? collect()) : collect();
@endphp

<div id="ms-wrap">

    {{-- Top bar ───────────────────────────────────────────────── --}}
    <div id="ms-topbar">
        <a href="{{ route('mains.show', $session->workorder_id) }}"
           class="btn btn-outline-secondary btn-sm" style="font-size:12px;padding:3px 10px">
            <i class="bi bi-arrow-left"></i> WO
        </a>

        <span class="fw-semibold">{{ $figure?->title ?? '—' }}</span>

        @if($session->tdr?->component)
            <span class="badge text-bg-secondary" style="font-size:11px">
                {{ $session->tdr->component->ipl_num }} {{ $session->tdr->component->name }}
            </span>
        @endif

        <span class="badge text-bg-info text-dark" style="font-size:11px">
            {{ $session->instruction?->name ?? '—' }}
        </span>

        @if($isFinalized)
            <span class="badge text-bg-success" style="font-size:11px">
                <i class="bi bi-check-circle"></i> Finalized
            </span>
        @else
            <span class="badge text-bg-warning text-dark" style="font-size:11px">Open</span>
            <button class="btn btn-success btn-sm ms-auto" id="msFinalizeBtn" style="font-size:12px;padding:3px 12px">
                <i class="bi bi-check-lg"></i> Finalize
            </button>
        @endif

        <span class="text-secondary" style="font-size:11px">
            by {{ $session->user?->name }} &bull; {{ $session->created_at?->format('d M Y') }}
        </span>
    </div>

    {{-- Body ──────────────────────────────────────────────────── --}}
    <div id="ms-body">

        {{-- Left: points list ─────────────────────────────────── --}}
        <div id="ms-points-panel">
            <div class="px-2 py-2 border-bottom" style="font-size:12px;font-weight:600;flex-shrink:0">
                Points
            </div>
            <div id="ms-points-list"></div>
        </div>

        {{-- Center: figure viewer ─────────────────────────────── --}}
        <div id="ms-viewer-panel">
            <div id="ms-figure-canvas-wrap">
                @if($figure?->image_path)
                    <div id="ms-figure-img-container">
                        <img id="ms-figure-img" src="{{ $figure->image_path }}" alt="{{ $figure->title }}">
                        <div id="ms-points-overlay"></div>
                    </div>
                @else
                    <div id="ms-empty-viewer">
                        <i class="bi bi-image" style="font-size:2.5rem;opacity:.25"></i>
                        <span>No figure image</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: entry panel ────────────────────────────────── --}}
        <div id="ms-entry-panel">
            <div id="ms-entry-header">
                <span id="ms-entry-title" style="color:var(--bs-secondary-color)">Select a point</span>
            </div>
            <div id="ms-entry-body">
                <div class="text-center text-secondary py-5" id="ms-entry-empty" style="font-size:12px">
                    <i class="bi bi-cursor" style="font-size:2rem;display:block;opacity:.25;margin-bottom:.5rem"></i>
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
    /* ── Constants & initial data ─────────────────────────────── */
    const SESSION_ID       = @json($session->id);
    const WORKORDER_ID     = @json($session->workorder_id);
    const CSRF             = @json(csrf_token());
    const IS_FINALIZED     = @json($isFinalized);
    const INSTRUCTION_NAME = @json($session->instruction?->name ?? '');

    let session     = @json($session);
    const codes     = @json($codes);
    const procs     = @json($repairProcedures);
    const figure    = session.figure;
    const points    = figure ? (figure.points || []) : [];

    /* measBySpec: specId → array sorted oldest→newest */
    let measBySpec = {};
    function rebuildMeasBySpec() {
        measBySpec = {};
        (session.measurements || []).forEach(m => {
            if (!measBySpec[m.manual_dimension_spec_id]) measBySpec[m.manual_dimension_spec_id] = [];
            measBySpec[m.manual_dimension_spec_id].push(m);
        });
        Object.keys(measBySpec).forEach(k => measBySpec[k].sort((a, b) => a.id - b.id));
    }
    rebuildMeasBySpec();

    let activePoint = null;

    /* ── DOM ─────────────────────────────────────────────────── */
    const pointsList    = document.getElementById('ms-points-list');
    const pointsOverlay = document.getElementById('ms-points-overlay');
    const entryTitle    = document.getElementById('ms-entry-title');
    const entryEmpty    = document.getElementById('ms-entry-empty');
    const specCards     = document.getElementById('ms-spec-cards');

    /* ── Helpers ─────────────────────────────────────────────── */
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

    /* useWear: Repair instruction uses wear limits when available */
    function useWear() { return INSTRUCTION_NAME === 'Repair'; }

    function effectiveLimits(spec) {
        if (useWear() && spec.wear_dim !== null) {
            return { source: 'wear', dim: spec.wear_dim, min: spec.wear_dim_min, max: spec.wear_dim_max };
        }
        return { source: 'orig', dim: spec.orig_dim, min: spec.orig_dim_min, max: spec.orig_dim_max };
    }

    /* ── Point status ────────────────────────────────────────── */
    function pointStatus(pt) {
        const reqSpecs = (pt.specs || []).filter(s => s.is_required);
        if (reqSpecs.length === 0) return 'none';

        let allPass = true, anyFail = false, anyMissing = false;
        reqSpecs.forEach(spec => {
            const ms = measBySpec[spec.id] || [];
            const finals   = ms.filter(m => m.stage === 'final');
            const initials = ms.filter(m => m.stage === 'initial');
            const current  = finals.length ? finals[finals.length - 1] : (initials.length ? initials[initials.length - 1] : null);
            if (!current) { anyMissing = true; allPass = false; }
            else if (current.result === 'FAIL') { anyFail = true; allPass = false; }
        });

        if (anyFail)    return 'fail';
        if (anyMissing) return 'partial';
        return 'pass';
    }

    function statusClass(s) {
        return { pass: 'status-pass', fail: 'status-fail', partial: 'status-partial', none: 'status-none' }[s] || 'status-none';
    }
    function dotClass(s) {
        return { pass: 'pass', fail: 'fail', partial: 'partial', none: 'none' }[s] || 'none';
    }

    /* ── Render points list ──────────────────────────────────── */
    function renderPointsList() {
        pointsList.innerHTML = '';
        points.forEach(pt => {
            const st  = pointStatus(pt);
            const el  = document.createElement('div');
            el.className = 'ms-point-item' + (activePoint && activePoint.id === pt.id ? ' active' : '');
            el.dataset.id = pt.id;
            el.innerHTML = `<span class="ms-status-dot ${dotClass(st)}"></span>
                            <span class="text-truncate"><strong>${esc(pt.code)}</strong>${pt.description ? ' <span class="text-secondary">' + esc(pt.description) + '</span>' : ''}</span>`;
            el.addEventListener('click', () => selectPoint(pt));
            pointsList.appendChild(el);
        });
    }

    /* ── Render figure markers ───────────────────────────────── */
    function renderFigureMarkers() {
        if (!pointsOverlay) return;
        pointsOverlay.innerHTML = '';
        points.forEach(pt => {
            const st  = pointStatus(pt);
            const m   = document.createElement('div');
            m.className = 'ms-point-marker ' + statusClass(st) + (activePoint && activePoint.id === pt.id ? ' active' : '');
            m.style.left = pt.x_pct + '%';
            m.style.top  = pt.y_pct + '%';
            m.title = pt.code + (pt.description ? ': ' + pt.description : '');
            m.textContent = pt.code.length <= 3 ? pt.code : pt.code.slice(0, 3);
            m.addEventListener('click', () => selectPoint(pt));
            pointsOverlay.appendChild(m);
        });
    }

    /* ── Select point ────────────────────────────────────────── */
    function selectPoint(pt) {
        activePoint = pt;
        renderPointsList();
        renderFigureMarkers();
        renderEntryPanel(pt);
    }

    /* ── Right panel: spec cards ─────────────────────────────── */
    function renderEntryPanel(pt) {
        entryTitle.textContent = pt.code + (pt.description ? ' — ' + pt.description : '');
        entryEmpty.classList.add('d-none');
        specCards.classList.remove('d-none');
        specCards.innerHTML = '';

        const specs = pt.specs || [];
        if (specs.length === 0) {
            specCards.innerHTML = '<div class="text-secondary text-center py-3" style="font-size:12px">No specs for this point</div>';
            return;
        }
        specs.forEach(spec => specCards.appendChild(buildSpecCard(spec)));
    }

    /* ── Build one spec card ─────────────────────────────────── */
    function buildSpecCard(spec) {
        const ms      = measBySpec[spec.id] || [];
        const lim     = effectiveLimits(spec);
        const compLbl = spec.component ? (spec.component.ipl_num + (spec.component.name ? ' ' + spec.component.name : '')) : '—';
        const fitsBadge = spec.is_fits_clearance ? '<span class="badge text-bg-success" style="font-size:9px">F&C</span>' : '';
        const reqBadge  = spec.is_required       ? '<span class="badge text-bg-secondary" style="font-size:9px">req</span>' : '';
        const srcBadge  = lim.source === 'wear'
            ? '<span class="badge text-bg-warning text-dark" style="font-size:9px">wear limits</span>'
            : '';

        const card = document.createElement('div');
        card.className = 'ms-spec-card';
        card.dataset.specId = spec.id;

        /* Header */
        card.innerHTML = `
            <div class="ms-spec-head">
                <span style="font-weight:600">${esc(spec.description)}</span>
                <span class="ms-1 d-flex gap-1">${fitsBadge}${reqBadge}${srcBadge}</span>
                <span class="ms-auto text-secondary" style="font-size:11px">${esc(compLbl)}</span>
            </div>
            <div class="ms-spec-limits" id="ms-limits-${spec.id}">
                <div class="ms-limit-cell">
                    <div class="ms-limit-cell-lbl">dim</div>
                    <div class="ms-limit-cell-val">${fmtDim(lim.dim)}</div>
                </div>
                <div class="ms-limit-cell">
                    <div class="ms-limit-cell-lbl">min</div>
                    <div class="ms-limit-cell-val">${fmtDim(lim.min)}</div>
                </div>
                <div class="ms-limit-cell">
                    <div class="ms-limit-cell-lbl">max</div>
                    <div class="ms-limit-cell-val">${fmtDim(lim.max)}</div>
                </div>
                ${spec.wear_dim !== null ? `
                <div class="ms-limit-cell ms-wear-cell">
                    <div class="ms-limit-cell-lbl">wear dim</div>
                    <div class="ms-limit-cell-val">${fmtDim(spec.wear_dim)}</div>
                </div>
                <div class="ms-limit-cell ms-wear-cell">
                    <div class="ms-limit-cell-lbl">w.min</div>
                    <div class="ms-limit-cell-val">${fmtDim(spec.wear_dim_min)}</div>
                </div>
                <div class="ms-limit-cell ms-wear-cell">
                    <div class="ms-limit-cell-lbl">w.max</div>
                    <div class="ms-limit-cell-val">${fmtDim(spec.wear_dim_max)}</div>
                </div>` : ''}
            </div>
            <div id="ms-meas-records-${spec.id}"></div>
            <div id="ms-meas-form-${spec.id}"></div>
        `;

        renderSpecMeasurements(spec, ms, card);
        return card;
    }

    /* ── Render measurements + form for one spec ─────────────── */
    function renderSpecMeasurements(spec, ms, card) {
        const recDiv  = card.querySelector('#ms-meas-records-' + spec.id);
        const formDiv = card.querySelector('#ms-meas-form-'    + spec.id);
        recDiv.innerHTML  = '';
        formDiv.innerHTML = '';

        const initials = ms.filter(m => m.stage === 'initial');
        const finals   = ms.filter(m => m.stage === 'final');
        const lastInit = initials[initials.length - 1] || null;
        const lastFin  = finals[finals.length - 1]     || null;

        /* --- Render existing measurement records --- */
        ms.forEach(m => {
            recDiv.appendChild(buildMeasRecord(m, spec));
        });

        if (IS_FINALIZED) {
            if (ms.length === 0) {
                formDiv.innerHTML = '<div class="ms-finalized-note">No measurement recorded</div>';
            }
            return;
        }

        /* --- Determine form state --- */
        if (!lastInit) {
            /* No measurement yet → show initial entry form */
            formDiv.appendChild(buildEntryForm(spec, 'initial', null));
        } else if (lastInit.result === 'FAIL' && !lastFin) {
            /* Initial FAIL, no final → show "Add Final" button */
            const btn = document.createElement('div');
            btn.style.cssText = 'padding:6px 10px;border-top:1px solid var(--bs-border-color)';
            btn.innerHTML = `<button class="btn btn-outline-warning btn-sm w-100" style="font-size:11px" data-add-final="${spec.id}" data-replaces="${lastInit.id}">
                <i class="bi bi-plus-circle"></i> Add Final Measurement
            </button>`;
            btn.querySelector('[data-add-final]').addEventListener('click', function () {
                this.closest('div').replaceWith(buildEntryForm(spec, 'final', parseInt(this.dataset.replaces)));
            });
            formDiv.appendChild(btn);
        }
        /* lastInit.result === 'PASS' OR lastFin exists → no form needed */
    }

    /* ── Single measurement record row ──────────────────────── */
    function buildMeasRecord(m, spec) {
        const resultCls = m.result === 'PASS' ? 'ms-meas-result-pass' : (m.result === 'FAIL' ? 'ms-meas-result-fail' : 'ms-meas-result-null');
        const resultTxt = m.result || (m.actual_value === null ? 'no value' : '—');
        const byLine    = m.user?.name ? 'by ' + m.user.name : '';
        const osLine    = m.calculated_oversize ? ' • oversize: ' + parseFloat(m.calculated_oversize).toFixed(4) : '';

        const div = document.createElement('div');
        div.className = 'ms-meas-record';
        div.dataset.measId = m.id;
        div.innerHTML = `
            <span class="ms-meas-stage-badge">${m.stage}</span>
            <span class="ms-meas-val ${resultCls}">
                ${m.actual_value !== null ? fmtDim(m.actual_value) : '<em class="fw-normal" style="font-size:11px">no value</em>'}
            </span>
            <span class="${resultCls}" style="font-weight:700;font-size:12px">${resultTxt}</span>
            ${m.finding_notes ? '<span class="ms-meas-meta">' + esc(m.finding_notes) + '</span>' : ''}
            ${m.repair_action ? '<span class="badge text-bg-secondary" style="font-size:9px">' + m.repair_action + '</span>' : ''}
            <span class="ms-meas-meta ms-auto">${byLine}${osLine}</span>
            ${!IS_FINALIZED ? `<button class="btn btn-link btn-sm p-0 ms-1 text-danger" style="font-size:11px" data-del-meas="${m.id}" data-spec-id="${spec.id}" title="Delete">
                <i class="bi bi-x-lg"></i>
            </button>` : ''}
        `;

        if (!IS_FINALIZED) {
            div.querySelector('[data-del-meas]').addEventListener('click', async function () {
                if (!confirm('Delete this measurement?')) return;
                await deleteMeasurement(parseInt(this.dataset.measId || m.id), parseInt(this.dataset.specId));
            });
        }
        return div;
    }

    /* ── Entry form ──────────────────────────────────────────── */
    function buildEntryForm(spec, stage, replacesId) {
        const codesOpts = codes.map(c => `<option value="${c.id}">${esc(c.code)} — ${esc(c.name)}</option>`).join('');
        const procsOpts = procs.map(p => `<option value="${p.id}">${esc(p.name)}</option>`).join('');
        const repairActions = ['replace','oversize','blend','machine','scrap','other'];

        const div = document.createElement('div');
        div.className = 'ms-entry-form';
        div.dataset.formSpec = spec.id;
        div.dataset.formStage = stage;
        if (replacesId) div.dataset.formReplaces = replacesId;

        div.innerHTML = `
            <div style="font-size:11px;font-weight:600;color:var(--bs-secondary-color);margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid var(--bs-border-color)">
                ${stage === 'final' ? '📏 Final measurement' : '📏 Record measurement'}
            </div>
            <div class="ms-form-row d-flex gap-2 align-items-end">
                <div style="flex:1">
                    <div class="ms-form-label">Actual value</div>
                    <input type="number" class="form-control form-control-sm" step="0.0001"
                           id="ms-val-${spec.id}" placeholder="0.0000"
                           style="font-family:monospace;font-size:13px">
                </div>
                <div class="form-check mb-1 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" id="ms-noval-${spec.id}">
                    <label class="form-check-label" for="ms-noval-${spec.id}" style="font-size:11px">No value</label>
                </div>
            </div>
            <div class="ms-form-row">
                <div class="ms-form-label">Finding code</div>
                <select class="form-select form-select-sm" id="ms-code-${spec.id}">
                    <option value="">— None —</option>
                    ${codesOpts}
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
            <div id="ms-repair-fields-${spec.id}" class="d-none">
                <div class="ms-form-row">
                    <div class="ms-form-label">Repair action</div>
                    <select class="form-select form-select-sm" id="ms-raction-${spec.id}">
                        <option value="">— Select —</option>
                        ${repairActions.map(a => `<option value="${a}">${a.charAt(0).toUpperCase() + a.slice(1)}</option>`).join('')}
                    </select>
                </div>
                <div class="ms-form-row">
                    <div class="ms-form-label">Repair procedure</div>
                    <select class="form-select form-select-sm" id="ms-proc-${spec.id}">
                        <option value="">— None —</option>
                        ${procsOpts}
                    </select>
                </div>
            </div>
            <div class="ms-form-row">
                <div class="ms-form-label">Notes</div>
                <textarea class="form-control form-control-sm" id="ms-notes-${spec.id}" rows="2" placeholder="Optional notes…"></textarea>
            </div>
            <div class="text-danger small d-none" id="ms-err-${spec.id}"></div>
            <button class="btn btn-primary btn-sm w-100 mt-1" style="font-size:12px" data-save-spec="${spec.id}">
                <i class="bi bi-check2"></i> Save
            </button>
        `;

        /* "No value" checkbox disables the value input */
        div.querySelector('#ms-noval-' + spec.id).addEventListener('change', function () {
            div.querySelector('#ms-val-' + spec.id).disabled = this.checked;
        });

        /* "Repair required" shows extra fields */
        div.querySelector('#ms-rreq-' + spec.id).addEventListener('change', function () {
            div.querySelector('#ms-repair-fields-' + spec.id).classList.toggle('d-none', !this.checked);
        });

        /* Save button */
        div.querySelector('[data-save-spec]').addEventListener('click', async function () {
            const errEl    = div.querySelector('#ms-err-' + spec.id);
            errEl.classList.add('d-none');
            const noVal    = div.querySelector('#ms-noval-' + spec.id).checked;
            const valRaw   = div.querySelector('#ms-val-'   + spec.id).value;
            const repReq   = div.querySelector('#ms-rreq-' + spec.id).checked;

            const body = {
                manual_dimension_spec_id:   spec.id,
                stage:                      stage,
                replaces_id:                replacesId || null,
                actual_value:               noVal ? null : (valRaw !== '' ? parseFloat(valRaw) : null),
                codes_id:                   div.querySelector('#ms-code-'   + spec.id).value || null,
                finding_notes:              div.querySelector('#ms-fnotes-' + spec.id).value.trim() || null,
                repair_required:            repReq,
                repair_action:              repReq ? (div.querySelector('#ms-raction-' + spec.id).value || null) : null,
                manual_repair_procedure_id: repReq ? (div.querySelector('#ms-proc-'   + spec.id).value || null) : null,
                notes:                      div.querySelector('#ms-notes-'  + spec.id).value.trim() || null,
            };

            try {
                const saved = await apiFetch('/measurement-sessions/' + SESSION_ID + '/measurements', {
                    method: 'POST',
                    body:   JSON.stringify(body),
                });
                /* Add to session.measurements and rebuild map */
                session.measurements.push(saved);
                rebuildMeasBySpec();
                /* Re-render this point's panel */
                renderEntryPanel(activePoint);
                renderPointsList();
                renderFigureMarkers();
            } catch (e) {
                errEl.textContent = e.message;
                errEl.classList.remove('d-none');
            }
        });

        return div;
    }

    /* ── Delete measurement ──────────────────────────────────── */
    async function deleteMeasurement(measId, specId) {
        try {
            await apiFetch('/measurements/' + measId, { method: 'DELETE' });
            session.measurements = session.measurements.filter(m => m.id !== measId);
            rebuildMeasBySpec();
            if (activePoint) renderEntryPanel(activePoint);
            renderPointsList();
            renderFigureMarkers();
        } catch (e) { alert(e.message); }
    }

    /* ── Finalize ────────────────────────────────────────────── */
    const finalizeBtn = document.getElementById('msFinalizeBtn');
    if (finalizeBtn) {
        finalizeBtn.addEventListener('click', async function () {
            if (!confirm('Finalize this session? No more measurements can be added after.')) return;
            try {
                await apiFetch('/measurement-sessions/' + SESSION_ID + '/finalize', { method: 'POST' });
                location.reload();
            } catch (e) { alert(e.message); }
        });
    }

    /* ── Init ────────────────────────────────────────────────── */
    renderPointsList();
    renderFigureMarkers();

    /* Auto-select first point */
    if (points.length > 0) selectPoint(points[0]);

})();
</script>
@endsection
