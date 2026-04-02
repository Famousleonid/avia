@php
    $stdLabels = ['ndt' => 'NDT', 'cad' => 'CAD', 'stress' => 'Stress', 'paint' => 'Paint'];
    if (! isset($stdCsvFiles)) {
        $stdProcessTypes = ['ndt', 'cad', 'stress', 'paint'];
        $stdCsvFiles = $cmm->getMedia('csv_files')->filter(function ($m) use ($stdProcessTypes) {
            return in_array($m->getCustomProperty('process_type'), $stdProcessTypes, true);
        });
    }
@endphp
<div class="std-processes-nested-wrap">
{{--    <p class="text-muted small mb-3">{{ __('EFF Code в одном формате: через запятую с пробелами (напр. А, В, ав). Пусто у строки STD — универсальная строка. Пусто у юнита WO — в снимок попадают все строки; после заполнения EFF у юнита при «Load from STD» отбираются универсальные строки и те, у которых EFF совпадает с любым кодом юнита (без учёта регистра).') }}</p>--}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <ul class="nav nav-tabs flex-nowrap overflow-x-auto flex-grow-1 small mb-0" id="std-process-inner-tab" role="tablist" style="min-width: 0; overflow-y: hidden;">
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 px-3 active"
                        id="std-process-inner-tab-csv"
                        data-bs-toggle="tab"
                        data-bs-target="#std-process-inner-pane-csv"
                        type="button"
                        role="tab"
                        aria-controls="std-process-inner-pane-csv"
                        aria-selected="true">
                    {{ __('CSV') }}
                    <span class="badge bg-secondary ms-1">{{ $stdCsvFiles->count() }}</span>
                </button>
            </li>
            @foreach(\App\Models\StdProcess::validStdValues() as $std)
                @php $rows = ($stdProcessesByType ?? collect())->get($std, collect()); @endphp
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-2 px-3"
                            id="std-process-inner-tab-{{ $std }}"
                            data-std="{{ $std }}"
                            data-bs-toggle="tab"
                            data-bs-target="#std-process-inner-pane-{{ $std }}"
                            type="button"
                            role="tab"
                            aria-controls="std-process-inner-pane-{{ $std }}"
                            aria-selected="false">
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
            @if($stdCsvFiles->isNotEmpty())
                <div id="std-reimport-from-csv-actions" class="d-none">
                    <form method="post" action="{{ route('manuals.std-processes.reimport-from-csv', $cmm) }}" class="d-inline" onsubmit="return confirm(@json(__('Для каждого типа процесса, у которого есть CSV-файл, таблица строк на вкладке NDT/CAD/Stress/Paint будет полностью заменена данными из файла. Продолжить?')));">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-outline-primary text-nowrap"
                                title="{{ __('Перечитывает все прикреплённые STD CSV с диска в таблицу (типы без файла не трогаются).') }}">
                            {{ __('Update from CSV') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
    <div class="tab-content pt-2" id="std-process-inner-tab-content">
        <div class="tab-pane fade show active"
             id="std-process-inner-pane-csv"
             role="tabpanel"
             aria-labelledby="std-process-inner-tab-csv">
            <div class="table-responsive">
                <table class="table table-hover table-bordered dir-table mb-0">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-center bg-gradient" scope="col">#</th>
                        <th class="text-center bg-gradient" scope="col">{{ __('File name') }}</th>
                        <th class="text-center bg-gradient" scope="col">{{ __('Process Type') }}</th>
                        <th class="text-center bg-gradient" scope="col">{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody class="text-center" id="std-csv-tbody">
                    @php $stdIdx = 1; @endphp
                    @foreach($stdCsvFiles as $csvFile)
                        <tr data-process-type="{{ $csvFile->getCustomProperty('process_type') }}">
                            <td class="align-content-center">{{ $stdIdx++ }}</td>
                            <td class="align-content-center">{{ $csvFile->file_name }}</td>
                            <td class="align-content-center">
                                <span class="badge bg-secondary">{{ $csvFile->getCustomProperty('process_type') ?: '—' }}</span>
                            </td>
                            <td class="align-content-center">
                                <button type="button" class="btn btn-sm btn-outline-info me-1 std-csv-view-btn"
                                        data-file-id="{{ $csvFile->id }}"
                                        data-file-name="{{ $csvFile->file_name }}">
                                    <i class="bi bi-view-list"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="deleteStdCsvFile('{{ route('manuals.csv.delete', ['manual' => $cmm->id, 'file' => $csvFile->id]) }}', this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    @if($stdCsvFiles->isEmpty())
                        <tr id="std-csv-empty-row">
                            <td colspan="4" class="text-muted">{{ __('No STD process files. Use "Add CSV Files" to upload NDT, CAD, Stress Relief or Paint CSV.') }}</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
        @foreach(\App\Models\StdProcess::validStdValues() as $std)
            @php $rows = ($stdProcessesByType ?? collect())->get($std, collect()); @endphp
            <div class="tab-pane fade"
                 id="std-process-inner-pane-{{ $std }}"
                 role="tabpanel"
                 aria-labelledby="std-process-inner-tab-{{ $std }}">
                <div class="table-responsive std-inner-table">
                    <table class="table table-sm table-hover table-bordered dir-table mb-0">
                        <thead class="table-light">
                        <tr>
                            <th class="text-center">IPL</th>
                            <th class="text-center">Part №</th>
                            <th>Description</th>
                            <th class="text-center">EFF Code</th>
                            <th class="text-center">Process</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Manual</th>
                            <th class="text-center" style="width: 140px;">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="text-center">{{ $row->ipl_num }}</td>
                                <td class="text-center">{{ $row->part_number }}</td>
                                <td class="small text-start">{{ \Illuminate\Support\Str::limit($row->description ?? '', 80) }}</td>
                                <td class="text-center small text-muted">{{ $row->eff_code !== null && $row->eff_code !== '' ? $row->eff_code : '—' }}</td>
                                <td class="text-center small">{{ $row->process }}</td>
                                <td class="text-center">{{ $row->qty }}</td>
                                <td class="text-center small">{{ $row->manual ?? '—' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-std-process-edit"
                                        data-std-process-id="{{ $row->id }}"
                                        data-ipl-num="{{ e($row->ipl_num) }}"
                                        data-part-number="{{ e($row->part_number) }}"
                                        data-description="{{ e($row->description) }}"
                                        data-process="{{ e($row->process) }}"
                                        data-qty="{{ (int) $row->qty }}"
                                        data-manual-ref="{{ e($row->manual ?? '') }}"
                                        data-eff-code="{{ e($row->eff_code ?? '') }}"
                                    ><i class="bi bi-pencil-square"></i></button>
                                    <form action="{{ route('manuals.std-processes.destroy', ['manual' => $cmm, 'stdProcess' => $row->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Удалить строку?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted text-center">{{ __('No rows. Upload CSV or use the Add button above.') }}</td>
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
                    <h5 class="modal-title">{{ __('Add a line') }} — <span id="addStdProcessModalLabelSuffix"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-0">IPL</label>
                            <input type="text" name="ipl_num" id="add_std_ipl_num" class="form-control form-control-sm" required autocomplete="off">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small mb-0">Part №</label>
                            <input type="text" name="part_number" id="add_std_part_number" class="form-control form-control-sm" autocomplete="off">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Description</label>
                            <input type="text" name="description" id="add_std_description" class="form-control form-control-sm" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">EFF Code</label>
                            <input type="text" name="eff_code" id="add_std_eff_code" class="form-control form-control-sm" placeholder="{{ __('A / A,B — пусто = все') }}" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">{{ __('Proc.') }}</label>
                            <input type="text" name="process" id="add_std_process" class="form-control form-control-sm" value="1" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Qty</label>
                            <input type="number" name="qty" id="add_std_qty" class="form-control form-control-sm" value="1" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Manual</label>
                            <input type="text" name="manual" id="add_std_manual" class="form-control form-control-sm" autocomplete="off">
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
                        <label class="form-label">IPL</label>
                        <input type="text" name="ipl_num" id="edit_std_ipl_num" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Part №</label>
                        <input type="text" name="part_number" id="edit_std_part_number" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" id="edit_std_description" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Process</label>
                        <input type="text" name="process" id="edit_std_process" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Qty</label>
                        <input type="number" name="qty" id="edit_std_qty" class="form-control" min="1" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Manual</label>
                        <input type="text" name="manual" id="edit_std_manual" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">EFF Code</label>
                        <input type="text" name="eff_code" id="edit_std_eff_code" class="form-control" placeholder="{{ __('пусто — для всех; A или A,B') }}">
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
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-std-process-edit');
        if (!btn) return;
        var id = btn.getAttribute('data-std-process-id');
        var form = document.getElementById('editStdProcessForm');
        if (!form || !id) return;
        form.action = baseUpdateUrl + '/' + id;
        document.getElementById('edit_std_ipl_num').value = btn.getAttribute('data-ipl-num') || '';
        document.getElementById('edit_std_part_number').value = btn.getAttribute('data-part-number') || '';
        document.getElementById('edit_std_description').value = btn.getAttribute('data-description') || '';
        document.getElementById('edit_std_process').value = btn.getAttribute('data-process') || '';
        document.getElementById('edit_std_qty').value = btn.getAttribute('data-qty') || '1';
        document.getElementById('edit_std_manual').value = btn.getAttribute('data-manual-ref') || '';
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
    var reimportWrap = document.getElementById('std-reimport-from-csv-actions');
    var stdLabels = @json($stdLabels);

    function currentInnerStd() {
        var active = tabList.querySelector('.nav-link.active');
        if (!active || active.id === 'std-process-inner-tab-csv') return null;
        return active.getAttribute('data-std') || active.id.replace('std-process-inner-tab-', '');
    }

    function applyAddStdTargetFromActiveTab() {
        var std = currentInnerStd();
        var hidden = document.getElementById('add_std_std_field');
        var suffix = document.getElementById('addStdProcessModalLabelSuffix');
        if (hidden) hidden.value = std || '';
        if (suffix) suffix.textContent = std ? (stdLabels[std] || std) : '';
    }

    function syncStdInnerToolbar() {
        var active = tabList.querySelector('.nav-link.active');
        var onCsv = active && active.id === 'std-process-inner-tab-csv';
        if (reimportWrap) reimportWrap.classList.toggle('d-none', !onCsv);
        if (addBtn) addBtn.classList.toggle('d-none', onCsv);
        if (!onCsv) applyAddStdTargetFromActiveTab();
    }

    tabList.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (trigger) {
        trigger.addEventListener('shown.bs.tab', syncStdInnerToolbar);
    });
    syncStdInnerToolbar();

    var addModalEl = document.getElementById('addStdProcessModal');
    var addForm = document.getElementById('addStdProcessForm');
    if (addModalEl && addForm) {
        addModalEl.addEventListener('show.bs.modal', function () {
            addForm.reset();
            var std = currentInnerStd();
            var h = document.getElementById('add_std_std_field');
            if (h && std) h.value = std;
            var proc = document.getElementById('add_std_process');
            if (proc) proc.value = '1';
            var q = document.getElementById('add_std_qty');
            if (q) q.value = '1';
            var sfx = document.getElementById('addStdProcessModalLabelSuffix');
            if (sfx) sfx.textContent = std ? (stdLabels[std] || std) : '';
        });
    }
})();
</script>
