@extends('admin.master')

@section('content')
    <style>
        .container { max-width: 1200px; }
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
        /* Компактные строки для Technical Notes */
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
        .preview-papyrus h5, .preview-papyrus h6, .preview-papyrus td,
        .preview-papyrus #previewTechnicalNotes { color: #000 !important; }
        .preview-papyrus .table, .preview-papyrus .table thead th,
        .preview-papyrus .table tbody, .preview-papyrus .table tbody tr,
        .preview-papyrus .table tbody td { background-color: transparent !important; }
        .preview-papyrus .table thead th {
            background-color: #f0e0c0 !important;
            color: #000 !important;
        }
        .preview-papyrus .table tbody tr:nth-of-type(odd) td,
        .preview-papyrus .table tbody tr:nth-of-type(even) td {
            background-color: transparent !important;
        }
    </style>

    <div class="mt-3">
        <div class="card bg-gradient">
            <div class="card-header justify-content-between d-flex align-items-center">
                <div>
                    <h5 class="text-primary mb-0">{{__('WO')}} {{$current_wo->number}}</h5>
                    <h5 class="mb-0">{{__('Repair and Modification Record')}}</h5>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <x-paper-button
                        text="R&M Form"
                        href="{{ route('rm_reports.rmRecordForm', ['id'=> $current_wo->id]) }}"
                        target="_blank"
                    />

{{--                    <button type="button" class="btn btn-outline-info btn-sm" style="height: 60px; width: 140px"--}}
{{--                            data-bs-toggle="modal" data-bs-target="#addRmRecordModal">--}}
{{--                        {{ __('ADD Repair OR Modification') }}--}}
{{--                    </button>--}}

                    <a href="{{ route('tdrs.show', ['id'=>$current_wo->id]) }}"
                       class="btn btn-outline-secondary  " style="height: 60px; width: 120px; align-content: center; font-size:
                        20px; line-height: 1.1">
                        {{ __('Back to TDR') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row" style="height: 90vh">
                {{-- Левая часть: предпросмотр --}}
                <div class="col-6">
                    <div class="m-3 border preview-papyrus rounded" style="height: 80vh">
                        <div class="m-2">
                            <h4 class="text-center">{{__('Repair and Modification Record WO')}}{{$current_wo->number}}</h4>
                            <div class="p-2">
                                <h6>{{__('Technical Notes')}}</h6>
                                <div id="previewTechnicalNotes" class="border rounded p-2" style="min-height: 50px;
                                white-space: pre-line;"></div>
                            </div>
                            <div class="table mt-3 table-scroll-rm-records">
                                <table class="table table-striped text-center align-items-center" style="font-size: 12px">
                                    <thead>
                                    <tr>
                                        <th class="border align-middle" >{{ __('Item') }}</th>
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

                {{-- Правая часть: редактирование --}}
                <div class="col-6">

                    <div class="m-3 border" style="height: 80vh">
                        {{-- Правая часть: Technical Notes --}}
                        <div class="card mt-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="text-primary mb-0">{{ __('Technical Notes') }}</h5>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#technicalNoteModal">
                                    {{ __('Add Notes') }}
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive table-scroll-technical-notes">
                                    <table class="table table-bordered">
                                        <tbody id="technicalNotesTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Правая часть: Repair and Modification Table --}}
                        <div>
                            <div class="card mt-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="text-primary mb-0">{{ __('Repair and Modification') }}</h5>
                                <button type="button" class="btn btn-outline-info btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#addRmRecordModal">
                                    {{ __('ADD')}}
                                </button>
{{--                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#technicalNoteModal">--}}
{{--                                    {{ __('Add') }}--}}
{{--                                </button>--}}
                            </div>

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @php
                                $savedData = $current_wo->rm_report ? json_decode($current_wo->rm_report, true) : null;
                                $savedRecordIds = $savedData['rm_records'] ?? [];
                                $savedRecordIds = collect($savedRecordIds)->pluck('id')->toArray();
                            @endphp

                            <div id="rmRecordsList">
                                @if($rm_reports->count() > 0)
                                    <div class="table-responsive table-scroll-rm-records mt-3" style="font-size: 12px">
                                        <table class="table table-striped text-center align-items-center">
                                            <thead>
                                            <tr>
                                                <th class="border align-middle" style="width: 10%">{{ __('Part Description') }}</th>
                                                <th class="border align-middle" style="width: 15%">{{ __('Modification or
                                                Repair #') }}</th>
                                                <th class="border align-middle" style="width: 45%">{{ __('Description') }}</th>
                                                <th class="border align-middle" style="width: 10%">{{ __('Identification Method') }}</th>
                                                <th class="border align-middle" style="width: 10%">{{ __('Select Record') }}</th>
                                                <th class="border align-middle" style="width: 10%">{{ __('Actions') }}</th>
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
                                                                   id="record_{{ $report->id }}"
                                                                   value="{{ $report->id }}"
                                                                {{ in_array($report->id, $savedRecordIds) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="record_{{ $report->id }}">Select</label>
                                                        </div>
                                                    </td>
                                                    <td class="border align-middle">
                                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editRecord({{ $report->id }})" data-bs-toggle="modal" data-bs-target="#editRmRecordModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord({{ $report->id }})">
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
        </div>

        {{-- Модальное окно Add R&M --}}
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

        {{-- Модальное окно Edit R&M --}}
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

        {{-- Модальное окно Technical Note --}}
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
                        <button type="button" class="btn btn-outline-primary" onclick="saveTechnicalNote()">{{ __('Save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let technicalNotes = [];
    const DEBOUNCE_MS = 400;
    let saveTimeout = null;

    document.addEventListener('DOMContentLoaded', function() {
        @php
            $rawNotes = $savedData['technical_notes'] ?? [];
            $initialNotes = is_array($rawNotes) ? array_values($rawNotes) : [];
        @endphp
        technicalNotes = @json($initialNotes);
        renderTechnicalNotesTable();
        updatePreview();

        $(document).on('change', '.record-checkbox', function() {
            updatePreview();
            triggerDebouncedSave();
        });

        $('#addRmRecordForm').on('submit', function(e) {
            e.preventDefault();
            addRmRecordAjax(this);
        });

        $('#editRmRecordForm').on('submit', function(e) {
            e.preventDefault();
            editRmRecordAjax(this);
        });
    });

    function updatePreview() {
        const notesEl = document.getElementById('previewTechnicalNotes');
        if (notesEl) notesEl.textContent = technicalNotes.join('\n');

        const tbody = document.getElementById('previewRecordsTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        let itemNum = 1;
        $('.record-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const cells = row.find('td');
            const partDesc = escapeHtml($(cells[0]).text().trim());
            const modRepair = escapeHtml($(cells[1]).text().trim());
            const desc = escapeHtml($(cells[2]).text().trim());
            const identMethod = escapeHtml($(cells[3]).text().trim());
            const tr = document.createElement('tr');
            tr.innerHTML = '<td class="border">' + itemNum + '</td><td class="border">' + partDesc + '</td><td class="border">' + modRepair + '</td><td class="border">' + desc + '</td><td class="border">' + identMethod + '</td>';
            tbody.appendChild(tr);
            itemNum++;
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function triggerDebouncedSave() {
        if (saveTimeout) clearTimeout(saveTimeout);
        saveTimeout = setTimeout(performSave, DEBOUNCE_MS);
    }

    function performSave() {
        saveTimeout = null;
        const selectedRecords = [];
        $('.record-checkbox:checked').each(function() { selectedRecords.push($(this).val()); });

        const formData = {
            selected_records: JSON.stringify(selectedRecords),
            workorder_id: {{ $current_wo->id }},
            _token: '{{ csrf_token() }}',
            _method: 'PUT'
        };
        technicalNotes.forEach(function(note, i) { formData['notes[' + i + ']'] = note; });

        $.ajax({
            url: '{{ route("rm_reports.update", $current_wo->id) }}',
            type: 'POST',
            data: formData,
            success: function() { /* silent */ },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || xhr.statusText || '{{ __("An error occurred while saving.") }}';
                showNotification(msg, 'error');
            }
        });
    }

    function renderTechnicalNotesTable() {
        const tbody = $('#technicalNotesTableBody');
        tbody.empty();
        if (technicalNotes.length === 0) {
            updatePreview();
            return;
        }
        technicalNotes.forEach((note, index) => {
            const row = $(`
                <tr >
                    <td class="align-middle " >${escapeHtml(note)}</td>
                    <td class="text-end" style="width: 120px;">
                        <button type="button" class="btn btn-sm btn-outline-primary  me-1" onclick="editTechnicalNote
                        (${index})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTechnicalNote(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });
        updatePreview();
    }

    function editTechnicalNote(index) {
        $('#technicalNoteInput').val(technicalNotes[index] || '');
        $('#technicalNoteIndex').val(index);
        new bootstrap.Modal(document.getElementById('technicalNoteModal')).show();
    }

    function deleteTechnicalNote(index) {
        technicalNotes.splice(index, 1);
        renderTechnicalNotesTable();
        triggerDebouncedSave();
    }

    function saveTechnicalNote() {
        const noteText = $('#technicalNoteInput').val().trim();
        const index = parseInt($('#technicalNoteIndex').val(), 10);
        if (noteText === '') {
            showNotification('{{ __("Please enter a note text.") }}', 'warning');
            return;
        }
        if (!isNaN(index) && index >= 0 && index < technicalNotes.length) {
            technicalNotes[index] = noteText;
        } else {
            technicalNotes.push(noteText);
        }
        $('#technicalNoteInput').val('');
        $('#technicalNoteIndex').val('-1');
        bootstrap.Modal.getInstance(document.getElementById('technicalNoteModal')).hide();
        renderTechnicalNotesTable();
        triggerDebouncedSave();
    }

    function addRmRecordAjax(form) {
        const $form = $(form);
        $.ajax({
            url: '{{ route("rm_reports.store") }}',
            type: 'POST',
            data: $form.serialize(),
            success: function(res) {
                if (res.success && res.data) {
                    const d = res.data;
                    const $tbody = $('#rmRecordsTableBody');
                    if ($tbody.length === 0) {
                        $('#rmRecordsList').html(`
                            <div class="table-responsive table-scroll-rm-records mt-3">
                                <table class="table table-striped text-center align-items-center">
                                    <thead><tr>
                                        <th>{{ __('Part Description') }}</th>
                                        <th>{{ __('Modification or Repair #') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Identification Method') }}</th>
                                        <th>{{ __('Select Record') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr></thead>
                                    <tbody id="rmRecordsTableBody"></tbody>
                                </table>
                            </div>
                        `);
                    }
                    const $tr = $(`
                        <tr data-record-id="${d.id}">
                            <td>${escapeHtml(d.part_description)}</td>
                            <td>${escapeHtml(d.mod_repair)}</td>
                            <td>${escapeHtml(d.description)}</td>
                            <td>${escapeHtml(d.ident_method || '')}</td>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input record-checkbox" type="checkbox" id="record_${d.id}" value="${d.id}">
                                    <label class="form-check-label" for="record_${d.id}">Select</label>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editRecord(${d.id})" data-bs-toggle="modal" data-bs-target="#editRmRecordModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord(${d.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                    $('#rmRecordsTableBody').append($tr);
                    $tr.find('.record-checkbox').on('change', function() {
                        updatePreview();
                        triggerDebouncedSave();
                    });
                    updatePreview();
                    bootstrap.Modal.getInstance(document.getElementById('addRmRecordModal')).hide();
                    $form[0].reset();
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || xhr.responseJSON?.errors ? JSON.stringify(xhr.responseJSON.errors) : '{{ __("Error creating record.") }}';
                showNotification(msg, 'error');
            }
        });
    }

    function editRecord(recordId) {
        $.get('{{ route("rm_reports.getRecord", ":id") }}'.replace(':id', recordId))
            .done(function(res) {
                if (res.success) {
                    const r = res.data;
                    $('#edit_record_id').val(r.id);
                    $('#edit_part_description').val(r.part_description);
                    $('#edit_mod_repair_description').val(r.description);
                    $('#edit_ident_method').val(r.ident_method);
                    $('#edit_mod_repair_mod, #edit_mod_repair_repair, #edit_mod_repair_sb').prop('checked', false);
                    $('#edit_mod_repair_' + r.mod_repair.toLowerCase()).prop('checked', true);
                    $('#editRmRecordForm').attr('action', '{{ route("rm_reports.updateRecord", ":id") }}'.replace(':id', r.id));
                }
            })
            .fail(function() { showNotification('{{ __("Error loading record.") }}', 'error'); });
    }

    function editRmRecordAjax(form) {
        const url = $(form).attr('action');
        const data = $(form).serialize() + '&_method=PUT';
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function(res) {
                if (res.success && res.data) {
                    const d = res.data;
                    const $row = $(`tr[data-record-id="${d.id}"]`);
                    if ($row.length) {
                        $row.find('td:eq(0)').text(d.part_description);
                        $row.find('td:eq(1)').text(d.mod_repair);
                        $row.find('td:eq(2)').text(d.description);
                        $row.find('td:eq(3)').text(d.ident_method || '');
                    }
                    updatePreview();
                    triggerDebouncedSave();
                    bootstrap.Modal.getInstance(document.getElementById('editRmRecordModal')).hide();
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || '{{ __("Error updating record.") }}';
                showNotification(msg, 'error');
            }
        });
    }

    function deleteRecord(recordId) {
        if (!confirm('{{ __("Are you sure you want to delete this record?") }}')) return;
        $.ajax({
            url: '{{ route("rm_reports.destroy", ":id") }}'.replace(':id', recordId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'DELETE',
                workorder_id: {{ $current_wo->id }}
            },
            success: function(res) {
                if (res.success) {
                    $(`tr[data-record-id="${recordId}"]`).remove();
                    updatePreview();
                    triggerDebouncedSave();
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || '{{ __("Error deleting record.") }}';
                showNotification(msg, 'error');
            }
        });
    }
    </script>
@endsection
