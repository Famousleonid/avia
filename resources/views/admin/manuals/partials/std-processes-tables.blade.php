@php
    $stdLabels = ['ndt' => 'NDT', 'cad' => 'CAD', 'stress' => 'Stress', 'paint' => 'Paint'];
    $stdAddSourceManuals = $stdAddSourceManuals ?? collect([$cmm]);
    $stdProcessPicklists = $stdProcessPicklists ?? ['ndt' => [], 'cad' => [], 'stress' => [], 'paint' => []];
    $stdProcessPicklistOptions = $stdProcessPicklistOptions ?? [
        'ndt' => collect($stdProcessPicklists['ndt'] ?? [])->map(fn ($v) => ['value' => $v, 'label' => $v])->values()->all(),
        'cad' => collect($stdProcessPicklists['cad'] ?? [])->map(fn ($v) => ['value' => $v, 'label' => $v])->values()->all(),
        'stress' => collect($stdProcessPicklists['stress'] ?? [])->map(fn ($v) => ['value' => $v, 'label' => $v])->values()->all(),
        'paint' => collect($stdProcessPicklists['paint'] ?? [])->map(fn ($v) => ['value' => $v, 'label' => $v])->values()->all(),
    ];
    $stdExistingPartKeysByStd = $stdExistingPartKeysByStd ?? [];
    $stdActiveInner = request('std_inner');
    if (! in_array($stdActiveInner, \App\Models\StdProcess::validStdValues(), true)) {
        $stdActiveInner = \App\Models\StdProcess::STD_NDT;
    }
@endphp
<div class="std-processes-nested-wrap">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <ul class="nav nav-tabs flex-nowrap overflow-x-auto flex-grow-1 small mb-0" id="std-process-inner-tab" role="tablist" style="min-width: 0; overflow-y: hidden;">
            @foreach(\App\Models\StdProcess::validStdValues() as $std)
                @php $rows = ($stdProcessesByType ?? collect())->get($std, collect()); @endphp
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-2 px-3 @if($stdActiveInner === $std) active @endif"
                            id="std-process-inner-tab-{{ $std }}"
                            data-std="{{ $std }}"
                            data-bs-toggle="tab"
                            data-bs-target="#std-process-inner-pane-{{ $std }}"
                            type="button"
                            role="tab"
                            aria-controls="std-process-inner-pane-{{ $std }}"
                            aria-selected="{{ $stdActiveInner === $std ? 'true' : 'false' }}">
                        {{ $stdLabels[$std] ?? $std }}
                        <span class="badge bg-secondary ms-1">{{ $rows->count() }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="d-flex align-items-center gap-2 flex-shrink-0 pb-0 std-inner-toolbar-right">
            <button type="button"
                    class="btn btn-sm btn-primary text-nowrap d-none"
                    id="std-open-add-modal-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#addStdProcessModal">
                <i class="bi bi-plus-lg"></i> {{ __('Add a line') }}
            </button>
        </div>
    </div>
    <div class="tab-content pt-2" id="std-process-inner-tab-content">
        @foreach(\App\Models\StdProcess::validStdValues() as $std)
            @php $rows = ($stdProcessesByType ?? collect())->get($std, collect()); @endphp
            <div class="tab-pane fade @if($stdActiveInner === $std) show active @endif"
                 id="std-process-inner-pane-{{ $std }}"
                 role="tabpanel"
                 aria-labelledby="std-process-inner-tab-{{ $std }}">
                <div class="table-responsive std-inner-table">
                    <table class="table table-sm table-hover table-bordered dir-table mb-0">
                        <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 56px;">#</th>
                            <th class="text-center">IPL</th>
                            <th class="text-center">Part No.</th>
                            <th>Description</th>
                            <th class="text-center">Process</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">EFF</th>
                            <th class="text-center" style="width: 112px;">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            @php $part = $row->component; @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td class="text-center">{{ $part?->ipl_num }}</td>
                                <td class="text-center">{{ $part?->part_number }}</td>
                                <td class="small text-start">{{ \Illuminate\Support\Str::limit($part?->name ?? '', 96) }}</td>
                                <td class="text-center small">{{ $row->process }}</td>
                                <td class="text-center">{{ $row->qty }}</td>
                                <td class="text-center small text-muted">{{ $row->eff_code !== null && $row->eff_code !== '' ? $row->eff_code : '-' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-std-process-edit"
                                        data-std-process-id="{{ $row->id }}"
                                        data-std="{{ e($row->std) }}"
                                        data-process="{{ e($row->process) }}"
                                        data-qty="{{ (int) $row->qty }}"
                                        data-eff-code="{{ e($row->eff_code ?? '') }}"
                                    ><i class="bi bi-pencil-square"></i></button>
                                    <form action="{{ route('manuals.std-processes.destroy', ['manual' => $cmm, 'stdProcess' => $row->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete row?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted text-center">{{ __('No Parts have this STD flag yet.') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="modal fade" id="addStdProcessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="addStdProcessForm" method="post" action="{{ route('manuals.std-processes.store', $cmm) }}">
                @csrf
                <input type="hidden" name="std" id="add_std_std_field" value="">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __("Add a line") }} - <span id="addStdProcessModalLabelSuffix"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 px-3 small d-none" id="add_std_duplicate_warning" role="alert">
                        {{ __('This part is already in the current STD table with the same IPL and Part No. Choose another part or remove the existing row.') }}
                    </div>
                    <p class="text-muted small mb-2">{{ __('Parts are selected from the Parts tab only. If a part is missing, add it in Parts first, then return here.') }}
                        <a href="{{ route('manuals.show', ['manual' => $cmm->id, 'tab' => 'parts']) }}">{{ __('Open Parts') }}</a>
                    </p>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small mb-0">{{ __('Manual / CMM (part source)') }}</label>
                            <select class="form-select form-select-sm" id="add_std_source_manual_id" required>
                                @foreach($stdAddSourceManuals as $m)
                                    <option value="{{ $m->id }}" @selected((int) $m->id === (int) $cmm->id)>
                                        {{ $m->number }}@if(!empty($m->title)) - {{ \Illuminate\Support\Str::limit($m->title, 48) }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">{{ __('Part') }}</label>
                            <select name="component_id" id="add_std_component_id" class="form-select form-select-sm" required disabled>
                                <option value="">{{ __('Loading...') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">EFF Code</label>
                            <input type="text" name="eff_code" id="add_std_eff_code" class="form-control form-control-sm" placeholder="{{ __('A / A,B - blank = all') }}" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Qty</label>
                            <input type="number" name="qty" id="add_std_qty" class="form-control form-control-sm" value="1" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">{{ __('Process') }}</label>
                            <select class="form-select form-select-sm" id="add_std_process_select" name="process" required></select>
                            <div class="form-text small">{{ __('Values come from the current CMM Processes tab.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editStdProcessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editStdProcessForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit STD row') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Process</label>
                        <select name="process" id="edit_std_process" class="form-select" required></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Qty</label>
                        <input type="number" name="qty" id="edit_std_qty" class="form-control" min="1" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">EFF Code</label>
                        <input type="text" name="eff_code" id="edit_std_eff_code" class="form-control" placeholder="{{ __('Blank = all; A or A,B') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var baseUpdateUrl = @json(url('/manuals/'.$cmm->id.'/std-processes'));
    var stdProcessPicklistsForEdit = @json($stdProcessPicklistOptions);

    function fillEditProcessSelect(std, currentValue) {
        var select = document.getElementById('edit_std_process');
        if (!select) return;

        var isNdt = std === 'ndt';
        select.multiple = isNdt;
        select.name = isNdt ? 'process[]' : 'process';
        select.size = isNdt ? 6 : 1;
        select.innerHTML = '';
        var opts = (stdProcessPicklistsForEdit && stdProcessPicklistsForEdit[std]) ? stdProcessPicklistsForEdit[std] : [];
        if (!opts.length) {
            var empty = document.createElement('option');
            empty.value = currentValue || '';
            empty.textContent = currentValue || 'No process configured';
            select.appendChild(empty);
            select.value = empty.value;
            return;
        }

        opts.forEach(function (item) {
            var value = typeof item === 'object' ? String(item.value || '') : String(item || '');
            var o = document.createElement('option');
            o.value = value;
            o.textContent = typeof item === 'object' ? String(item.label || value) : value;
            select.appendChild(o);
        });

        if (isNdt) {
            var selectedValues = String(currentValue || '').split('/').map(function (v) { return v.trim(); }).filter(Boolean);
            if (!selectedValues.length && opts.length) {
                selectedValues = [String((typeof opts[0] === 'object' ? opts[0].value : opts[0]) || '')];
            }
            Array.from(select.options).forEach(function (option) {
                option.selected = selectedValues.indexOf(option.value) !== -1;
            });
        } else {
            var values = opts.map(function (item) { return typeof item === 'object' ? String(item.value || '') : String(item || ''); });
            select.value = values.indexOf(currentValue) !== -1 ? currentValue : values[0];
        }
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-std-process-edit');
        if (!btn) return;
        var id = btn.getAttribute('data-std-process-id');
        var form = document.getElementById('editStdProcessForm');
        if (!form || !id) return;
        form.action = baseUpdateUrl + '/' + id;
        fillEditProcessSelect(btn.getAttribute('data-std') || '', btn.getAttribute('data-process') || '');
        document.getElementById('edit_std_qty').value = btn.getAttribute('data-qty') || '1';
        var effEl = document.getElementById('edit_std_eff_code');
        if (effEl) effEl.value = btn.getAttribute('data-eff-code') || '';
        var modal = new bootstrap.Modal(document.getElementById('editStdProcessModal'));
        modal.show();
    });
})();

(function () {
    var tabList = document.getElementById('std-process-inner-tab');
    if (!tabList) return;

    var addBtn = document.getElementById('std-open-add-modal-btn');
    var addModalEl = document.getElementById('addStdProcessModal');
    var addForm = document.getElementById('addStdProcessForm');
    var stdLabels = @json($stdLabels);
    var stdProcessPicklists = @json($stdProcessPicklistOptions);
    var stdExistingPartKeysByStd = @json($stdExistingPartKeysByStd);
    var componentsForAddUrl = @json(route('manuals.std-processes.components-for-add', $cmm));
    var pageCmmId = @json((int) $cmm->id);
    var txtStdPartsLoading = @json(__('Loading...'));
    var txtStdChoosePart = @json(__('Choose a part...'));

    function stdPartDuplicateKey(ipl, pn) {
        return String(ipl == null ? '' : ipl).trim() + '\n' + String(pn == null ? '' : pn).trim();
    }

    function refreshDuplicateWarning() {
        var warn = document.getElementById('add_std_duplicate_warning');
        var btnAdd = addForm ? addForm.querySelector('button[type="submit"]') : null;
        var h = document.getElementById('add_std_std_field');
        var sel = document.getElementById('add_std_component_id');
        if (!warn || !h || !sel) return;
        var std = h.value;
        var opt = sel.selectedOptions[0];
        if (!opt || !opt.value || !std) {
            warn.classList.add('d-none');
            if (btnAdd) btnAdd.disabled = false;
            return;
        }
        var key = stdPartDuplicateKey(opt.getAttribute('data-ipl-num'), opt.getAttribute('data-part-number'));
        var list = (stdExistingPartKeysByStd && stdExistingPartKeysByStd[std]) ? stdExistingPartKeysByStd[std] : [];
        var dup = list.indexOf(key) !== -1;
        warn.classList.toggle('d-none', !dup);
        if (btnAdd) btnAdd.disabled = dup;
    }

    function currentInnerStd() {
        var active = tabList.querySelector('.nav-link.active');
        if (!active) return null;
        return active.getAttribute('data-std') || active.id.replace('std-process-inner-tab-', '');
    }

    function applyAddStdTargetFromActiveTab() {
        var std = currentInnerStd();
        var hidden = document.getElementById('add_std_std_field');
        var suffix = document.getElementById('addStdProcessModalLabelSuffix');
        if (hidden) hidden.value = std || '';
        if (suffix) suffix.textContent = std ? (stdLabels[std] || std) : '';
    }

    function syncProcessFieldsForStd(std) {
        var select = document.getElementById('add_std_process_select');
        if (!select) return;

        var isNdt = std === 'ndt';
        select.setAttribute('name', isNdt ? 'process[]' : 'process');
        select.multiple = isNdt;
        select.size = isNdt ? 6 : 1;
        select.disabled = false;
        select.innerHTML = '';

        var opts = (stdProcessPicklists && stdProcessPicklists[std]) ? stdProcessPicklists[std] : [];
        if (!isNdt) {
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = opts.length ? 'Choose a process...' : 'No process configured';
            select.appendChild(empty);
        } else if (!opts.length) {
            var emptyNdt = document.createElement('option');
            emptyNdt.value = '';
            emptyNdt.textContent = 'No process configured';
            select.appendChild(emptyNdt);
        }

        opts.forEach(function (item) {
            var value = typeof item === 'object' ? String(item.value || '') : String(item || '');
            var o = document.createElement('option');
            o.value = value;
            o.textContent = typeof item === 'object' ? String(item.label || value) : value;
            select.appendChild(o);
        });
        if (opts.length) {
            var firstValue = typeof opts[0] === 'object' ? String(opts[0].value || '') : String(opts[0] || '');
            select.value = firstValue;
        }
    }
    function loadPartsForSourceManual(manualId) {
        var sel = document.getElementById('add_std_component_id');
        if (!sel) return;
        sel.innerHTML = '<option value="">' + txtStdPartsLoading + '</option>';
        sel.disabled = true;
        var url = componentsForAddUrl + (componentsForAddUrl.indexOf('?') === -1 ? '?' : '&') + 'source_manual_id=' + encodeURIComponent(manualId);
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) {
            if (!r.ok) throw new Error('load parts');
            return r.json();
        }).then(function (rows) {
            sel.innerHTML = '<option value="">' + txtStdChoosePart + '</option>';
            (rows || []).forEach(function (row) {
                var opt = document.createElement('option');
                opt.value = row.id;
                var label = (row.ipl_num || '-') + ' - ' + (row.part_number || '') + ' - ' + (row.name || '');
                opt.textContent = label.length > 120 ? label.slice(0, 117) + '...' : label;
                if (row.units_assy != null && String(row.units_assy).trim() !== '') {
                    opt.setAttribute('data-units-assy', String(row.units_assy).trim());
                }
                opt.setAttribute('data-ipl-num', row.ipl_num != null ? String(row.ipl_num) : '');
                opt.setAttribute('data-part-number', row.part_number != null ? String(row.part_number) : '');
                sel.appendChild(opt);
            });
            sel.disabled = false;
            refreshDuplicateWarning();
        }).catch(function () {
            sel.innerHTML = '<option value="">' + txtStdChoosePart + '</option>';
            sel.disabled = false;
            refreshDuplicateWarning();
        });
    }

    function syncStdInnerToolbar() {
        if (addBtn) addBtn.classList.remove('d-none');
        applyAddStdTargetFromActiveTab();
    }

    tabList.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (trigger) {
        trigger.addEventListener('shown.bs.tab', syncStdInnerToolbar);
    });
    syncStdInnerToolbar();

    var srcManualSel = document.getElementById('add_std_source_manual_id');
    if (srcManualSel) {
        srcManualSel.addEventListener('change', function () {
            loadPartsForSourceManual(this.value);
        });
    }

    var componentSel = document.getElementById('add_std_component_id');
    if (componentSel) {
        componentSel.addEventListener('change', function () {
            var opt = this.selectedOptions[0];
            var q = document.getElementById('add_std_qty');
            if (!q) return;
            if (!opt || !opt.value) {
                refreshDuplicateWarning();
                return;
            }
            var ua = opt.getAttribute('data-units-assy');
            if (ua != null && ua !== '' && /^\d+$/.test(ua)) {
                q.value = ua;
            } else {
                q.value = '1';
            }
            refreshDuplicateWarning();
        });
    }

    if (addModalEl && addForm) {
        addModalEl.addEventListener('show.bs.modal', function () {
            addForm.reset();
            var std = currentInnerStd();
            var h = document.getElementById('add_std_std_field');
            if (h && std) h.value = std;
            var sfx = document.getElementById('addStdProcessModalLabelSuffix');
            if (sfx) sfx.textContent = std ? (stdLabels[std] || std) : '';
            if (srcManualSel) {
                srcManualSel.value = String(pageCmmId);
            }
            var q = document.getElementById('add_std_qty');
            if (q) q.value = '1';
            syncProcessFieldsForStd(std || 'ndt');
            var w = document.getElementById('add_std_duplicate_warning');
            if (w) w.classList.add('d-none');
            var btnA = addForm ? addForm.querySelector('button[type="submit"]') : null;
            if (btnA) btnA.disabled = false;
            loadPartsForSourceManual(srcManualSel ? srcManualSel.value : pageCmmId);
        });
    }

    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            var btnAdd = addForm.querySelector('button[type="submit"]');
            var warn = document.getElementById('add_std_duplicate_warning');
            if (btnAdd && btnAdd.disabled) {
                e.preventDefault();
                return false;
            }
            if (warn && !warn.classList.contains('d-none')) {
                e.preventDefault();
                return false;
            }
        });
    }
})();
</script>
