{{-- Fits & Clearances — full-width view: Table 8001 reproduction + authoring.
     Client-rendered from /manuals/{id}/fits (ManualFit). One fit → two member
     rows (ID then OD) + shared clearance bracket; clearances are stored
     (manual) or derived, mismatch flagged. Add/edit/delete inline. --}}
<div class="p-3" id="fc-view" data-manual-id="{{ $cmm->id }}">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0">Fits and Clearances</h5>
        <button type="button" class="btn btn-outline-primary btn-sm ms-auto" id="fcAddBtn">
            <i class="bi bi-plus-lg"></i> Add fit
        </button>
    </div>

    {{-- Add / edit form (hidden until used) --}}
    <div id="fc-form" class="border rounded p-2 mb-3 d-none" style="font-size:12px">
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

    <div id="fc-empty" class="text-secondary d-none">No Fits &amp; Clearances pairs yet — add one above.</div>

    <div class="table-responsive" id="fc-table-wrap">
        <table class="table table-bordered table-sm align-middle" style="font-size:12px;white-space:nowrap">
            <thead class="table-light">
                <tr>
                    <th rowspan="3" class="text-center align-middle">Ref.<br>No.</th>
                    <th rowspan="3" class="text-center align-middle">Mating IPL<br>Item / Member</th>
                    <th colspan="4" class="text-center">Original Manufacturer Limits</th>
                    <th colspan="3" class="text-center">In-Service Wear Limits</th>
                    <th rowspan="3" class="text-center align-middle">Actions</th>
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
            <tbody id="fc-tbody"></tbody>
        </table>
    </div>
    <p class="text-secondary mt-2" style="font-size:11px">
        Muted clearance values are derived from the member limits; fill the manual values to override.
        Rows flagged <span class="text-danger">⚠</span> have a stored clearance that disagrees with the derived one.
    </p>
</div>

<script>
(function () {
    const root = document.getElementById('fc-view');
    if (!root) return;
    const MANUAL_ID = root.dataset.manualId;
    const CSRF = '{{ csrf_token() }}';

    const tbody   = document.getElementById('fc-tbody');
    const emptyEl = document.getElementById('fc-empty');
    const wrapEl  = document.getElementById('fc-table-wrap');
    const formEl  = document.getElementById('fc-form');
    const odSel   = document.getElementById('fcOdSelect');
    const idSel   = document.getElementById('fcIdSelect');
    const refNo   = document.getElementById('fcRefNo');
    const asmMin  = document.getElementById('fcAsmMin');
    const asmMax  = document.getElementById('fcAsmMax');
    const perm    = document.getElementById('fcPerm');
    const editId  = document.getElementById('fcEditId');
    const formErr = document.getElementById('fcFormErr');

    let params = [], icLabels = {}, fits = [];

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

    function render() {
        if (!fits.length) { wrapEl.classList.add('d-none'); emptyEl.classList.remove('d-none'); tbody.innerHTML = ''; return; }
        emptyEl.classList.add('d-none'); wrapEl.classList.remove('d-none');
        tbody.innerHTML = fits.map(f => {
            const odm = f.od || {}, idm = f.id || {};
            const warn = f.mismatch ? ' <span class="text-danger" title="stored clearance differs from derived">⚠</span>' : '';
            const asmMinCls = f.assembly_clearance_min == null ? 'text-secondary' : '';
            const asmMaxCls = f.assembly_clearance_max == null ? 'text-secondary' : '';
            const permCls   = f.permitted_clearance    == null ? 'text-secondary' : '';
            return ''
                + '<tr>'
                +   '<td rowspan="2" class="text-center align-middle fw-semibold">' + esc(f.ref_no || '—') + warn + '</td>'
                +   '<td>' + memberCell(idm) + '</td>'
                +   '<td class="text-end">' + fmt(idm.orig_min) + '</td>'
                +   '<td class="text-end">' + fmt(idm.orig_max) + '</td>'
                +   '<td rowspan="2" class="text-end align-middle ' + asmMinCls + '">' + fmt(f.eff_assembly_min) + '</td>'
                +   '<td rowspan="2" class="text-end align-middle ' + asmMaxCls + '">' + fmt(f.eff_assembly_max) + '</td>'
                +   '<td class="text-end">' + fmt(idm.wear_min) + '</td>'
                +   '<td class="text-end">' + fmt(idm.wear_max) + '</td>'
                +   '<td rowspan="2" class="text-end align-middle ' + permCls + '">' + fmt(f.eff_permitted) + '</td>'
                +   '<td rowspan="2" class="text-center align-middle">'
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
        } catch (e) {
            formErr.textContent = e.message;
            formErr.classList.remove('d-none');
        }
    }

    async function loadAll() {
        const [ics, ps, fs] = await Promise.all([
            api('/manuals/' + MANUAL_ID + '/inspection-components'),
            api('/manuals/' + MANUAL_ID + '/parameters'),
            api('/manuals/' + MANUAL_ID + '/fits'),
        ]);
        icLabels = {};
        (ics || []).forEach(ic => { icLabels[ic.id] = ic.label || ic.name || ('IC ' + ic.id); });
        params = ps || [];
        fits = fs || [];
        populateSelects();
        render();
    }

    document.getElementById('fcAddBtn').addEventListener('click', () => showForm(null));
    document.getElementById('fcCancelBtn').addEventListener('click', hideForm);
    document.getElementById('fcSaveBtn').addEventListener('click', save);
    tbody.addEventListener('click', async (e) => {
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

    function init() { loadAll().catch(e => { emptyEl.classList.remove('d-none'); emptyEl.innerHTML = '<span class="text-danger">' + esc(e.message) + '</span>'; }); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
