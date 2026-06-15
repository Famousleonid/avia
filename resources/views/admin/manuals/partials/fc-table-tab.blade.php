{{-- Fits & Clearances — unified view (all from the new manual_fit data):
     - flat Dimensions report (All / Extra) with an F&C badge driven by fit
       membership (not the legacy is_fits_clearance point flag);
     - F&C-only = the Table 8001 pairs view (clearances) with authoring;
     - Add/edit/delete fits; Print. Client-rendered from the fit endpoints. --}}
<style>
    @media print { #fc-view .fc-no-print { display: none !important; } }
</style>
<div class="p-3" id="fc-view" data-manual-id="{{ $cmm->id }}">
    <div class="d-flex align-items-center gap-2 mb-3 fc-no-print">
        <h5 class="mb-0 me-2">Fits and Clearances</h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary active" data-fc-filter="all">All</button>
            <button type="button" class="btn btn-outline-success" data-fc-filter="fc">F&amp;C only</button>
            <button type="button" class="btn btn-outline-secondary" data-fc-filter="std">Extra</button>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" id="fcAddBtn"><i class="bi bi-plus-lg"></i> Add fit</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="fcDetectBtn" title="Create fits for points that have both an OD and an ID parameter (existing pairs are kept)"><i class="bi bi-magic"></i> Detect from points</button>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" id="fcPrintBtn">&#128438; Print</button>
    </div>

    {{-- Add / edit form --}}
    <div id="fc-form" class="border rounded p-2 mb-3 d-none fc-no-print" style="font-size:12px">
        <input type="hidden" id="fcEditId">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1 text-secondary">OD member</label>
                <select id="fcOdSelect" class="form-select form-select-sm"></select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 text-secondary">ID member</label>
                <select id="fcIdSelect" class="form-select form-select-sm"></select>
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1 text-secondary">Ref. No</label>
                <input id="fcRefNo" class="form-control form-control-sm" placeholder="8001-1">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1 text-secondary">Asm min</label>
                <input id="fcAsmMin" class="form-control form-control-sm" placeholder="auto">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1 text-secondary">Asm max</label>
                <input id="fcAsmMax" class="form-control form-control-sm" placeholder="auto">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1 text-secondary">Perm</label>
                <input id="fcPerm" class="form-control form-control-sm" placeholder="auto">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="fcSaveBtn">Save</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="fcCancelBtn">Cancel</button>
            </div>
        </div>
        <div id="fcFormErr" class="text-danger mt-1 d-none"></div>
        <div class="text-secondary mt-1" style="font-size:11px">Leave clearances blank to derive from member limits (in).</div>
    </div>

    {{-- Flat dimensions report (All / Extra) --}}
    <div id="fc-simple-section">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle" style="font-size:12px;white-space:nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">Figure</th>
                        <th class="text-center">Ref. No.</th>
                        <th class="text-center">F&amp;C</th>
                        <th class="text-center">Component</th>
                        <th>Description</th>
                        <th colspan="2" class="text-center">Original Limits <span class="fw-normal text-secondary">in</span></th>
                        <th colspan="2" class="text-center">Wear Limits <span class="fw-normal text-secondary">in</span></th>
                    </tr>
                    <tr>
                        <th></th><th></th><th></th><th></th><th></th>
                        <th class="text-center">Min.</th><th class="text-center">Max.</th>
                        <th class="text-center">Min.</th><th class="text-center">Max.</th>
                    </tr>
                </thead>
                <tbody id="fc-simple-tbody"></tbody>
            </table>
        </div>
    </div>

    {{-- F&C pairs (Table 8001) — shown under "F&C only" --}}
    <div id="fc-pairs-section" style="display:none">
        <h6 class="mt-3 mb-2">Fits &amp; Clearances pairs</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle" style="font-size:12px;white-space:nowrap">
                <thead class="table-light">
                    <tr>
                        <th rowspan="3" class="text-center align-middle">Ref.<br>No.</th>
                        <th rowspan="3" class="text-center align-middle">Mating IPL<br>Item / Member</th>
                        <th colspan="4" class="text-center">Original Manufacturer Limits</th>
                        <th colspan="3" class="text-center">In-Service Wear Limits</th>
                        <th rowspan="3" class="text-center align-middle fc-no-print">Actions</th>
                    </tr>
                    <tr>
                        <th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">in</span></th>
                        <th colspan="2" class="text-center">Assembly<br>Clearance<br><span class="fw-normal text-secondary">in</span></th>
                        <th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">in</span></th>
                        <th class="text-center">Permitted<br>Clearance<br><span class="fw-normal text-secondary">in</span></th>
                    </tr>
                    <tr>
                        <th class="text-center">Min.</th><th class="text-center">Max.</th>
                        <th class="text-center">Min.</th><th class="text-center">Max.</th>
                        <th class="text-center">Min.</th><th class="text-center">Max.</th>
                        <th class="text-center">Max.</th>
                    </tr>
                </thead>
                <tbody id="fc-pairs-tbody"></tbody>
            </table>
        </div>
        <p class="text-secondary mt-2 fc-no-print" style="font-size:11px">
            Muted clearance values are derived from the member limits; fill the manual values to override.
            Rows flagged <span class="text-danger">⚠</span> have a stored clearance that disagrees with the derived one.
        </p>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('fc-view');
    if (!root) return;
    const MANUAL_ID = root.dataset.manualId;
    const CSRF = '{{ csrf_token() }}';

    const simpleTbody = document.getElementById('fc-simple-tbody');
    const pairsTbody   = document.getElementById('fc-pairs-tbody');
    const simpleSec    = document.getElementById('fc-simple-section');
    const pairsSec     = document.getElementById('fc-pairs-section');
    const formEl  = document.getElementById('fc-form');
    const odSel   = document.getElementById('fcOdSelect');
    const idSel   = document.getElementById('fcIdSelect');
    const refNo   = document.getElementById('fcRefNo');
    const asmMin  = document.getElementById('fcAsmMin');
    const asmMax  = document.getElementById('fcAsmMax');
    const perm    = document.getElementById('fcPerm');
    const editId  = document.getElementById('fcEditId');
    const formErr = document.getElementById('fcFormErr');

    let params = [], icLabels = {}, fits = [], reportRows = [], filter = 'all';

    async function api(url, opts = {}) {
        const res = await fetch(url, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json', ...(opts.headers || {}) },
            ...opts,
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || json.error || 'Request failed');
        return json;
    }
    function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function fmt(v) { return (v == null || v === '') ? '—' : Number(v).toFixed(4); }
    function paramLabel(p) { return (icLabels[p.inspection_component_id] || '?') + ' · ' + (p.description || ''); }
    function memberCell(m) {
        if (!m) return '—';
        return esc(m.description || '') + (m.ipl ? ' <span class="text-secondary">(' + esc(m.ipl) + ')</span>' : '');
    }

    function populateSelects() {
        const opts = '<option value="">— select —</option>' +
            params.map(p => '<option value="' + p.id + '">' + esc(paramLabel(p)) + '</option>').join('');
        odSel.innerHTML = opts;
        idSel.innerHTML = opts;
    }

    function renderSimple() {
        simpleTbody.innerHTML = reportRows.map(r => {
            const badge = r.is_fc ? '<span class="badge text-bg-success" style="font-size:10px">F&amp;C</span>' : '';
            return '<tr data-is-fc="' + (r.is_fc ? '1' : '0') + '">'
                + '<td class="text-center text-secondary" style="font-size:11px">' + esc(r.figure) + '</td>'
                + '<td class="text-center fw-semibold">' + esc(r.ref) + '</td>'
                + '<td class="text-center">' + badge + '</td>'
                + '<td>' + esc(r.component || '—') + '</td>'
                + '<td>' + esc(r.description || '') + '</td>'
                + '<td class="text-end">' + fmt(r.orig_min) + '</td>'
                + '<td class="text-end">' + fmt(r.orig_max) + '</td>'
                + '<td class="text-end">' + fmt(r.wear_min) + '</td>'
                + '<td class="text-end">' + fmt(r.wear_max) + '</td>'
                + '</tr>';
        }).join('') || '<tr><td colspan="9" class="text-secondary">No measurement parameters found.</td></tr>';
    }

    function renderPairs() {
        if (!fits.length) { pairsTbody.innerHTML = '<tr><td colspan="10" class="text-secondary">No fits yet — use “Add fit”.</td></tr>'; return; }
        pairsTbody.innerHTML = fits.map(f => {
            const odm = f.od_member || {}, idm = f.id_member || {};
            const warn = f.mismatch ? ' <span class="text-danger" title="stored clearance differs from derived">⚠</span>' : '';
            const aMinCls = f.assembly_clearance_min == null ? 'text-secondary' : '';
            const aMaxCls = f.assembly_clearance_max == null ? 'text-secondary' : '';
            const pCls    = f.permitted_clearance    == null ? 'text-secondary' : '';
            return ''
                + '<tr>'
                +   '<td rowspan="2" class="text-center align-middle fw-semibold">' + esc(f.ref_no || '—') + warn + '</td>'
                +   '<td>' + memberCell(idm) + '</td>'
                +   '<td class="text-end">' + fmt(idm.orig_min) + '</td>'
                +   '<td class="text-end">' + fmt(idm.orig_max) + '</td>'
                +   '<td rowspan="2" class="text-end align-middle ' + aMinCls + '">' + fmt(f.eff_assembly_min) + '</td>'
                +   '<td rowspan="2" class="text-end align-middle ' + aMaxCls + '">' + fmt(f.eff_assembly_max) + '</td>'
                +   '<td class="text-end">' + fmt(idm.wear_min) + '</td>'
                +   '<td class="text-end">' + fmt(idm.wear_max) + '</td>'
                +   '<td rowspan="2" class="text-end align-middle ' + pCls + '">' + fmt(f.eff_permitted) + '</td>'
                +   '<td rowspan="2" class="text-center align-middle fc-no-print">'
                +     '<button class="btn btn-outline-secondary btn-sm p-0 px-1 fc-edit" data-id="' + f.id + '" title="Edit"><i class="bi bi-pencil"></i></button> '
                +     '<button class="btn btn-outline-danger btn-sm p-0 px-1 fc-del" data-id="' + f.id + '" title="Delete"><i class="bi bi-trash"></i></button>'
                +   '</td>'
                + '</tr>'
                + '<tr>'
                +   '<td>' + memberCell(odm) + '</td>'
                +   '<td class="text-end">' + fmt(odm.orig_min) + '</td>'
                +   '<td class="text-end">' + fmt(odm.orig_max) + '</td>'
                +   '<td class="text-end">' + fmt(odm.wear_min) + '</td>'
                +   '<td class="text-end">' + fmt(odm.wear_max) + '</td>'
                + '</tr>';
        }).join('');
    }

    function applyFilter() {
        root.querySelectorAll('[data-fc-filter]').forEach(b => b.classList.toggle('active', b.dataset.fcFilter === filter));
        if (filter === 'fc') {
            simpleSec.style.display = 'none';
            pairsSec.style.display = '';
        } else {
            pairsSec.style.display = 'none';
            simpleSec.style.display = '';
            simpleTbody.querySelectorAll('tr[data-is-fc]').forEach(row => {
                row.style.display = (filter === 'std' && row.dataset.isFc === '1') ? 'none' : '';
            });
        }
    }

    function showForm(fit) {
        editId.value = fit ? fit.id : '';
        odSel.value  = fit ? fit.od_param_id : '';
        idSel.value  = fit ? fit.id_param_id : '';
        refNo.value  = fit ? (fit.ref_no || '') : '';
        asmMin.value = (fit && fit.assembly_clearance_min != null) ? fit.assembly_clearance_min : '';
        asmMax.value = (fit && fit.assembly_clearance_max != null) ? fit.assembly_clearance_max : '';
        perm.value   = (fit && fit.permitted_clearance != null) ? fit.permitted_clearance : '';
        formErr.classList.add('d-none');
        formEl.classList.remove('d-none');
    }
    function hideForm() { formEl.classList.add('d-none'); }

    async function save() {
        const numOrNull = (el) => el.value.trim() === '' ? null : el.value.trim();
        const payload = {
            od_param_id: odSel.value || null,
            id_param_id: idSel.value || null,
            ref_no: refNo.value.trim() || null,
            assembly_clearance_min: numOrNull(asmMin),
            assembly_clearance_max: numOrNull(asmMax),
            permitted_clearance: numOrNull(perm),
        };
        try {
            if (editId.value) await api('/fits/' + editId.value, { method: 'PATCH', body: JSON.stringify(payload) });
            else await api('/manuals/' + MANUAL_ID + '/fits', { method: 'POST', body: JSON.stringify(payload) });
            hideForm();
            await loadAll();
            filter = 'fc'; applyFilter();
        } catch (e) {
            formErr.textContent = e.message;
            formErr.classList.remove('d-none');
        }
    }

    async function loadAll() {
        const [ics, ps, fs, rep] = await Promise.all([
            api('/manuals/' + MANUAL_ID + '/inspection-components'),
            api('/manuals/' + MANUAL_ID + '/parameters'),
            api('/manuals/' + MANUAL_ID + '/fits'),
            api('/manuals/' + MANUAL_ID + '/fits-report'),
        ]);
        icLabels = {};
        (ics || []).forEach(ic => { icLabels[ic.id] = ic.label || ic.name || ('IC ' + ic.id); });
        params = ps || [];
        fits = fs || [];
        reportRows = rep || [];
        populateSelects();
        renderSimple();
        renderPairs();
        applyFilter();
    }
    window.fcReload = loadAll;

    root.querySelectorAll('[data-fc-filter]').forEach(b => b.addEventListener('click', () => { filter = b.dataset.fcFilter; applyFilter(); }));
    document.getElementById('fcAddBtn').addEventListener('click', () => { filter = 'fc'; applyFilter(); showForm(null); });
    document.getElementById('fcCancelBtn').addEventListener('click', hideForm);
    document.getElementById('fcSaveBtn').addEventListener('click', save);
    document.getElementById('fcPrintBtn').addEventListener('click', () => window.print());
    document.getElementById('fcDetectBtn').addEventListener('click', async () => {
        if (!confirm('Create fits for points that have both an OD and an ID parameter? Existing pairs are kept.')) return;
        try {
            const r = await api('/manuals/' + MANUAL_ID + '/fits/detect', { method: 'POST' });
            await loadAll();
            filter = 'fc'; applyFilter();
            alert('Detected: ' + r.created + ' new fit(s), ' + r.skipped + ' already existed.');
        } catch (e) { alert(e.message); }
    });
    pairsTbody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.fc-edit');
        const delBtn = e.target.closest('.fc-del');
        if (editBtn) {
            const fit = fits.find(f => String(f.id) === editBtn.dataset.id);
            if (fit) showForm(fit);
        } else if (delBtn) {
            if (!confirm('Delete this fit?')) return;
            try { await api('/fits/' + delBtn.dataset.id, { method: 'DELETE' }); await loadAll(); }
            catch (err) { alert(err.message); }
        }
    });

    function init() { loadAll().catch(e => { simpleTbody.innerHTML = '<tr><td colspan="9" class="text-danger">' + esc(e.message) + '</td></tr>'; }); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
