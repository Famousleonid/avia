<div class="rm-reports-partial">
    <style>
        .table-scroll-rm-records {
            max-height: 40vh;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
        }
        .table-scroll-technical-notes {
            max-height: 18vh;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
        }
        .table-scroll-rm-records thead th,
        .table-scroll-technical-notes thead th {
            position: sticky;
            top: 0;
            background-color: #031e3a !important;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
            align-content: center;
            font-size: small;
        }
        .table-scroll-rm-records table,
        .table-scroll-technical-notes table { margin-bottom: 0; }
        .table-scroll-technical-notes table { border-collapse: separate; border-spacing: 0; }
        .table-scroll-technical-notes tbody tr td {
            padding: 0.25rem 0.5rem;
            line-height: 1.2;
            vertical-align: middle;
        }
        .preview-papyrus {
            color: #000;
            background-color: #f4e4bc;
            background-image:
                linear-gradient(rgba(139, 119, 101, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(139, 119, 101, 0.06) 1px, transparent 1px),
                linear-gradient(180deg, #faf0dc 0%, #f0e0c0 50%, #e8d5b0 100%);
            background-size: 22px 22px, 22px 22px, 100% 100%;
        }
        .preview-papyrus h4, .preview-papyrus h5, .preview-papyrus h6,
        .preview-papyrus td, .preview-papyrus th,
        .preview-papyrus #previewTechnicalNotes,
        .preview-papyrus .table-scroll-rm-records tbody td,
        .preview-papyrus .table-scroll-rm-records thead th { color: #000 !important; }
        .preview-papyrus .table, .preview-papyrus .table thead th,
        .preview-papyrus .table tbody, .preview-papyrus .table tbody tr,
        .preview-papyrus .table tbody td { background-color: transparent !important; }
        .preview-papyrus .table thead th,
        .preview-papyrus .table-scroll-rm-records thead th {
            background-color: #f0e0c0 !important;
            color: #000 !important;
        }
        .preview-papyrus .table tbody tr:nth-of-type(odd) td,
        .preview-papyrus .table tbody tr:nth-of-type(even) td,
        .preview-papyrus .table-scroll-rm-records tbody tr:nth-of-type(odd) td,
        .preview-papyrus .table-scroll-rm-records tbody tr:nth-of-type(even) td {
            background-color: transparent !important;
            color: #000 !important;
        }
        .preview-papyrus .table-scroll-rm-records table,
        .preview-papyrus .table-scroll-rm-records th,
        .preview-papyrus .table-scroll-rm-records td {
            border-color: #6c757d !important;
        }
    </style>

    <div class="row g-3">
        {{-- Card 1: Preview (papyrus) --}}
        <div class="col-md-6">
            <div class="card h-100 preview-papyrus border">
                <div class="card-body">
                    <h4 class="text-center">{{__('Repair and Modification Record WO')}}{{$current_wo->number}}</h4>
                    <div class="p-2">
                        <h6>{{__('Technical Notes')}}</h6>
                        <div id="previewTechnicalNotes" class="border rounded p-2" style="min-height: 50px; white-space: pre-line;"></div>
                    </div>
                    <div class="table mt-3 table-scroll-rm-records">
                        <table class="table table-striped text-center align-items-center dir-table" style="font-size: 12px">
                            <thead>
                            <tr>
                                <th class="border align-middle">{{ __('Item') }}</th>
                                <th class="border align-middle">{{ __('Part Description') }}</th>
                                <th class="border align-middle">{{ __('Modification or Repair #') }}</th>
                                <th class="border align-middle">{{ __('Description Of Modification or Repair') }}</th>
                                <th class="border align-middle">{{ __('Identification Method') }}</th>
                            </tr>
                            </thead>
                            <tbody id="previewRecordsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Technical Notes + Repair and Modification --}}
        <div class="col-md-6">
            <div class="card bg-gradient h-100">
                <div class="card-body">
                    {{-- Technical Notes section --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="text-primary mb-0">{{ __('Technical Notes') }}</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#technicalNoteModal">
                                {{ __('Add Notes') }}
                            </button>
                        </div>
                        <div class="table-responsive table-scroll-technical-notes">
                            <table class="table table-bordered dir-table">
                                <tbody id="technicalNotesTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Repair and Modification section --}}
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="text-primary mb-0">{{ __('Repair and Modification') }}</h5>
                            <div class="d-flex gap-2 align-items-center">
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#addRmRecordModal">
                                    {{ __('ADD')}}
                                </button>
                            </div>
                        </div>

                        @php
                                $savedData = $current_wo->rm_report ? json_decode($current_wo->rm_report, true) : null;
                                $savedRecordIds = $savedData['rm_records'] ?? [];
                                $savedRecordIds = collect($savedRecordIds)->pluck('id')->toArray();
                            @endphp

                            <div id="rmRecordsList">
                                @if($rm_reports->count() > 0)
                                    <div class="table-responsive mt-3" style="max-height: calc(100vh - 320px); overflow-y: auto;">
                                        <table class="table table-hover table-bordered dir-table align-middle bg-gradient">
                                            <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                                            <tr>
                                                <th class="text-primary text-center align-middle" style="width: 20%">{{ __('Part
                                                 Description') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 10%">{{ __
                                                ('Modification or Repair #') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 25%">{{ __
                                                ('Description') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 25%">{{ __
                                                ('Identification Method') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 10%">{{ __('Select Record') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 10%">{{ __('Actions') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rmRecordsTableBody">
                                            @foreach($rm_reports as $report)
                                                <tr data-record-id="{{ $report->id }}">
                                                    <td class="border align-middle">{{ $report->part_description }}</td>
                                                    <td class="border align-middle">{{ $report->mod_repair }}</td>
                                                    <td class="border align-middle">{{ $report->description }}</td>
                                                    <td class="border align-middle">{{ $report->ident_method }}</td>
                                                    <td class="border align-middle">
                                                        <div class="form-check">
                                                            <input class="form-check-input record-checkbox" type="checkbox"
                                                                   id="record_{{ $report->id }}" value="{{ $report->id }}"
                                                                   {{ in_array($report->id, $savedRecordIds) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="record_{{ $report->id }}">Select</label>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="window.rmPartialEditRecord({{ $report->id }})" data-bs-toggle="modal" data-bs-target="#editRmRecordModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="window.rmPartialDeleteRecord({{ $report->id }})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mt-3">
                                        {{ __('No R&M records found for this work order.') }}
                                        {{ __('Use "Add Repair OR Modification" to create the first record.') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addRmRecordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ADD Repair OR Modification') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addRmRecordForm">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="part_description">{{ __('Part Description') }}</label>
                            <input type="text" class="form-control" id="part_description" name="part_description" required>
                        </div>
                        <div class="form-group mt-3">
                            <label>{{ __('Modification or Repair') }}</label>
                            <div class="d-flex gap-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="mod_repair_mod" value="Mod" required>
                                    <label class="form-check-label" for="mod_repair_mod">Mod</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="mod_repair_repair" value="Repair" required>
                                    <label class="form-check-label" for="mod_repair_repair">Repair</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="mod_repair_sb" value="SB" required>
                                    <label class="form-check-label" for="mod_repair_sb">SB</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <label for="mod_repair_description">{{ __('Description of Modification or Repair') }}</label>
                            <input type="text" class="form-control" id="mod_repair_description" name="mod_repair_description" required>
                        </div>
                        <div class="form-group mt-3">
                            <label for="ident_method">{{ __('Identification Method') }}</label>
                            <input type="text" class="form-control" id="ident_method" name="ident_method">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRmRecordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Repair OR Modification') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editRmRecordForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                    <input type="hidden" name="record_id" id="edit_record_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_part_description">{{ __('Part Description') }}</label>
                            <input type="text" class="form-control" id="edit_part_description" name="part_description" required>
                        </div>
                        <div class="form-group mt-3">
                            <label>{{ __('Modification or Repair') }}</label>
                            <div class="d-flex gap-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="edit_mod_repair_mod" value="Mod" required>
                                    <label class="form-check-label" for="edit_mod_repair_mod">Mod</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="edit_mod_repair_repair" value="Repair" required>
                                    <label class="form-check-label" for="edit_mod_repair_repair">Repair</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="edit_mod_repair_sb" value="SB" required>
                                    <label class="form-check-label" for="edit_mod_repair_sb">SB</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <label for="edit_mod_repair_description">{{ __('Description of Modification or Repair') }}</label>
                            <input type="text" class="form-control" id="edit_mod_repair_description" name="mod_repair_description" required>
                        </div>
                        <div class="form-group mt-3">
                            <label for="edit_ident_method">{{ __('Identification Method') }}</label>
                            <input type="text" class="form-control" id="edit_ident_method" name="ident_method">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-outline-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="technicalNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Enter Note') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="technicalNoteInput" class="form-control" rows="3" placeholder="{{ __('Enter technical note') }}"></textarea>
                    <input type="hidden" id="technicalNoteIndex" value="-1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-outline-primary" id="rmPartialSaveTechnicalNoteBtn">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var workorderId = {{ $current_wo->id }};
    var technicalNotes = @json($savedData['technical_notes'] ?? []);
    if (!Array.isArray(technicalNotes)) technicalNotes = [];

    var DEBOUNCE_MS = 400;
    var saveTimeout = null;
    var storeUrl = '{{ route("rm_reports.store") }}';
    var updateUrl = '{{ route("rm_reports.update", $current_wo->id) }}';
    var getRecordUrl = '{{ route("rm_reports.getRecord", ":id") }}';
    var updateRecordUrl = '{{ route("rm_reports.updateRecord", ":id") }}';
    var destroyUrl = '{{ route("rm_reports.destroy", ":id") }}';
    var csrfToken = '{{ csrf_token() }}';

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function updatePreview() {
        var notesEl = document.getElementById('previewTechnicalNotes');
        if (notesEl) notesEl.textContent = technicalNotes.join('\n');

        var tbody = document.getElementById('previewRecordsTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        var itemNum = 1;
        var checkboxes = document.querySelectorAll('.record-checkbox:checked');
        checkboxes.forEach(function(cb) {
            var row = cb.closest('tr');
            if (!row) return;
            var cells = row.querySelectorAll('td');
            var partDesc = escapeHtml((cells[0] && cells[0].textContent) ? cells[0].textContent.trim() : '');
            var modRepair = escapeHtml((cells[1] && cells[1].textContent) ? cells[1].textContent.trim() : '');
            var desc = escapeHtml((cells[2] && cells[2].textContent) ? cells[2].textContent.trim() : '');
            var identMethod = escapeHtml((cells[3] && cells[3].textContent) ? cells[3].textContent.trim() : '');
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="border">' + itemNum + '</td><td class="border">' + partDesc + '</td><td class="border">' + modRepair + '</td><td class="border">' + desc + '</td><td class="border">' + identMethod + '</td>';
            tbody.appendChild(tr);
            itemNum++;
        });
    }

    function triggerDebouncedSave() {
        if (saveTimeout) clearTimeout(saveTimeout);
        saveTimeout = setTimeout(performSave, DEBOUNCE_MS);
    }

    function performSave() {
        saveTimeout = null;
        var selectedRecords = [];
        document.querySelectorAll('.record-checkbox:checked').forEach(function(cb) { selectedRecords.push(cb.value); });
        var formData = new FormData();
        formData.append('selected_records', JSON.stringify(selectedRecords));
        formData.append('workorder_id', workorderId);
        formData.append('_token', csrfToken);
        formData.append('_method', 'PUT');
        technicalNotes.forEach(function(note, i) { formData.append('notes[' + i + ']', note); });

        fetch(updateUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(data) {
            if (!data.success && data.message && typeof showNotification === 'function') {
                showNotification(data.message, 'error');
            }
        }).catch(function() {});
    }

    function renderTechnicalNotesTable() {
        var tbody = document.getElementById('technicalNotesTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        technicalNotes.forEach(function(note, index) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="align-middle">' + escapeHtml(note) + '</td>' +
                '<td class="text-end" style="width: 120px;">' +
                '<button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit-idx="' + index + '"><i class="fas fa-edit"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger" data-delete-idx="' + index + '"><i class="fas fa-trash"></i></button>' +
                '</td>';
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('[data-edit-idx]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var idx = parseInt(this.getAttribute('data-edit-idx'), 10);
                document.getElementById('technicalNoteInput').value = technicalNotes[idx] || '';
                document.getElementById('technicalNoteIndex').value = idx;
                new bootstrap.Modal(document.getElementById('technicalNoteModal')).show();
            });
        });
        tbody.querySelectorAll('[data-delete-idx]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var idx = parseInt(this.getAttribute('data-delete-idx'), 10);
                technicalNotes.splice(idx, 1);
                renderTechnicalNotesTable();
                triggerDebouncedSave();
            });
        });
        updatePreview();
    }

    document.getElementById('technicalNoteModal') && document.getElementById('rmPartialSaveTechnicalNoteBtn') && document.getElementById('rmPartialSaveTechnicalNoteBtn').addEventListener('click', function() {
        var noteText = (document.getElementById('technicalNoteInput') && document.getElementById('technicalNoteInput').value) ? document.getElementById('technicalNoteInput').value.trim() : '';
        var index = parseInt(document.getElementById('technicalNoteIndex') ? document.getElementById('technicalNoteIndex').value : -1, 10);
        if (!noteText && typeof showNotification === 'function') {
            showNotification('{{ __("Please enter a note text.") }}', 'warning');
            return;
        }
        if (!isNaN(index) && index >= 0 && index < technicalNotes.length) {
            technicalNotes[index] = noteText;
        } else {
            technicalNotes.push(noteText);
        }
        document.getElementById('technicalNoteInput').value = '';
        document.getElementById('technicalNoteIndex').value = '-1';
        var m = bootstrap.Modal.getInstance(document.getElementById('technicalNoteModal'));
        if (m) m.hide();
        renderTechnicalNotesTable();
        triggerDebouncedSave();
    });

    document.getElementById('technicalNoteModal') && document.getElementById('technicalNoteModal').addEventListener('show.bs.modal', function() {
        document.getElementById('technicalNoteInput').focus();
    });

    document.querySelectorAll('.record-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            updatePreview();
            triggerDebouncedSave();
        });
    });

    document.getElementById('addRmRecordForm') && document.getElementById('addRmRecordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var fd = new FormData(form);
        var submitBtn = form.querySelector('button[type="submit"]');
        var origHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
        fetch(storeUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
            if (res.success && res.data) {
                var d = res.data;
                var tbody = document.getElementById('rmRecordsTableBody');
                var rmRecordsList = document.getElementById('rmRecordsList');
                if (!tbody && rmRecordsList) {
                    rmRecordsList.innerHTML = '<div class="table-responsive table-scroll-rm-records mt-3"><table class="table table-striped text-center dir-table"><thead><tr><th>{{ __("Part Description") }}</th><th>{{ __("Modification or Repair #") }}</th><th>{{ __("Description") }}</th><th>{{ __("Identification Method") }}</th><th>{{ __("Select Record") }}</th><th>{{ __("Actions") }}</th></tr></thead><tbody id="rmRecordsTableBody"></tbody></table></div>';
                    tbody = document.getElementById('rmRecordsTableBody');
                }
                if (tbody) {
                    var tr = document.createElement('tr');
                    tr.setAttribute('data-record-id', d.id);
                    tr.innerHTML = '<td class="align-middle">' + escapeHtml(d.part_description) + '</td><td class="align-middle">' + escapeHtml(d.mod_repair) + '</td><td class="align-middle">' + escapeHtml(d.description) + '</td><td class="align-middle">' + escapeHtml(d.ident_method || '') + '</td><td class="align-middle"><div class="form-check"><input class="form-check-input record-checkbox" type="checkbox" id="record_' + d.id + '" value="' + d.id + '"><label class="form-check-label" for="record_' + d.id + '">Select</label></div></td><td class="align-middle"><button class="btn btn-sm btn-outline-primary me-1" onclick="window.rmPartialEditRecord(' + d.id + ')" data-bs-toggle="modal" data-bs-target="#editRmRecordModal"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-outline-danger" onclick="window.rmPartialDeleteRecord(' + d.id + ')"><i class="fas fa-trash"></i></button></td>';
                    tbody.appendChild(tr);
                    tr.querySelector('.record-checkbox').addEventListener('change', function() { updatePreview(); triggerDebouncedSave(); });
                    updatePreview();
                }
                var m = bootstrap.Modal.getInstance(document.getElementById('addRmRecordModal'));
                if (m) m.hide();
                form.reset();
            } else if (res.message && typeof showNotification === 'function') {
                showNotification(res.message || (res.errors ? JSON.stringify(res.errors) : '') || '{{ __("Error creating record.") }}', 'error');
            }
        })
        .catch(function() {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
        });
    });

    document.getElementById('editRmRecordForm') && document.getElementById('editRmRecordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var url = form.getAttribute('action');
        if (!url) return;
        var fd = new FormData(form);
        fd.append('_method', 'PUT');
        var submitBtn = form.querySelector('button[type="submit"]');
        var origHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
        fetch(url, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
            if (res.success && res.data) {
                var d = res.data;
                var row = document.querySelector('tr[data-record-id="' + d.id + '"]');
                if (row) {
                    var cells = row.querySelectorAll('td');
                    if (cells[0]) cells[0].textContent = d.part_description;
                    if (cells[1]) cells[1].textContent = d.mod_repair;
                    if (cells[2]) cells[2].textContent = d.description;
                    if (cells[3]) cells[3].textContent = d.ident_method || '';
                }
                updatePreview();
                triggerDebouncedSave();
                var m = bootstrap.Modal.getInstance(document.getElementById('editRmRecordModal'));
                if (m) m.hide();
            } else if (res.message && typeof showNotification === 'function') {
                showNotification(res.message, 'error');
            }
        })
        .catch(function() {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
        });
    });

    window.rmPartialEditRecord = function(recordId) {
        fetch(getRecordUrl.replace(':id', recordId), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data) {
                    var r = res.data;
                    document.getElementById('edit_record_id').value = r.id;
                    document.getElementById('edit_part_description').value = r.part_description || '';
                    document.getElementById('edit_mod_repair_description').value = r.description || '';
                    document.getElementById('edit_ident_method').value = r.ident_method || '';
                    document.querySelectorAll('#editRmRecordForm input[name="mod_repair"]').forEach(function(inp) { inp.checked = false; });
                    var modInput = document.getElementById('edit_mod_repair_' + (r.mod_repair || '').toLowerCase());
                    if (modInput) modInput.checked = true;
                    document.getElementById('editRmRecordForm').setAttribute('action', updateRecordUrl.replace(':id', r.id));
                }
            })
            .catch(function() { if (typeof showNotification === 'function') showNotification('{{ __("Error loading record.") }}', 'error'); });
    };

    window.rmPartialDeleteRecord = function(recordId) {
        if (!confirm('{{ __("Are you sure you want to delete this record?") }}')) return;
        var fd = new FormData();
        fd.append('_token', csrfToken);
        fd.append('_method', 'DELETE');
        fd.append('workorder_id', workorderId);
        fetch(destroyUrl.replace(':id', recordId), {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            if (res.success) {
                var row = document.querySelector('tr[data-record-id="' + recordId + '"]');
                if (row) row.remove();
                updatePreview();
                triggerDebouncedSave();
            } else if (res.message && typeof showNotification === 'function') {
                showNotification(res.message, 'error');
            }
        });
    };

    renderTechnicalNotesTable();
    updatePreview();
})();
</script>
