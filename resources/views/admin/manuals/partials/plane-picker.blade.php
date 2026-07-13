{{-- AirCraft multi-picker (same pattern as COMPONENTS): an Add button opens a
     small modal; picked planes render as chip rows with × below the label.
     Each chip carries a hidden planes[] input — the form posts the set as-is.

     Usage on a form:
       <button type="button" data-plane-picker="#BOX_ID">Add</button>
       <div id="BOX_ID" class="plane-chip-box" data-init='@json([ids])'></div>
     Requires $planes (id, type) in scope. Include ONCE per page. --}}

<div class="modal fade" id="planePickerModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content bg-gradient">
            <div class="modal-header py-2">
                <h6 class="modal-title text-info">{{ __('Add AirCraft Type') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select id="planePickerSelect" class="form-select form-select-sm"></select>
                <div id="planePickerEmpty" class="text-secondary small mt-1 d-none">{{ __('All aircraft types are already added.') }}</div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-sm btn-primary" id="planePickerAddBtn">{{ __('Add') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
window.PLANE_TYPES = @json($planes->pluck('type', 'id'));
(function () {
    let targetBox = null;
    const modalEl = document.getElementById('planePickerModal');
    const sel     = document.getElementById('planePickerSelect');
    const addBtn  = document.getElementById('planePickerAddBtn');
    const emptyEl = document.getElementById('planePickerEmpty');

    window.planeChipsAdd = function (box, id, type) {
        id = String(id);
        if (!box || box.querySelector('input[name="planes[]"][value="' + id + '"]')) return;
        const chip = document.createElement('div');
        chip.className = 'd-flex align-items-center gap-2 border rounded px-2 py-1';
        const span = document.createElement('span');
        span.textContent = type || (window.PLANE_TYPES[id] ?? id);
        const del = document.createElement('button');
        del.type = 'button'; del.className = 'btn-close ms-auto'; del.style.fontSize = '9px';
        del.title = '{{ __('Remove') }}';
        del.addEventListener('click', () => chip.remove());
        const hid = document.createElement('input');
        hid.type = 'hidden'; hid.name = 'planes[]'; hid.value = id;
        chip.append(span, del, hid);
        box.appendChild(chip);
    };

    window.planeChipsSet = function (box, ids) {
        if (!box) return;
        box.innerHTML = '';
        (ids || []).filter(v => v != null).forEach(id => window.planeChipsAdd(box, id));
    };

    document.querySelectorAll('[data-plane-picker]').forEach(btn => btn.addEventListener('click', function () {
        targetBox = document.querySelector(this.dataset.planePicker);
        if (!targetBox) return;
        const picked = new Set([...targetBox.querySelectorAll('input[name="planes[]"]')].map(i => i.value));
        const options = Object.entries(window.PLANE_TYPES).filter(([id]) => !picked.has(String(id)));
        sel.innerHTML = options.map(([id, t]) => '<option value="' + id + '"></option>').join('');
        [...sel.options].forEach((o, i) => { o.textContent = options[i][1]; }); // text via DOM — no HTML injection
        sel.classList.toggle('d-none', options.length === 0);
        addBtn.disabled = options.length === 0;
        emptyEl.classList.toggle('d-none', options.length !== 0);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }));

    addBtn?.addEventListener('click', function () {
        if (targetBox && sel.value) window.planeChipsAdd(targetBox, sel.value);
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });

    // initial sets (edit forms / old() re-fill)
    document.querySelectorAll('.plane-chip-box[data-init]').forEach(box => {
        try { window.planeChipsSet(box, JSON.parse(box.dataset.init || '[]')); } catch (e) { /* noop */ }
    });
})();
</script>
