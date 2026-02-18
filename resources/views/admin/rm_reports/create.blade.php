@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 1200px;
        }

        /* Стили для таблиц с скроллингом */
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
        .table-scroll-technical-notes table {
            margin-bottom: 0;
        }

        /* Для таблицы Technical Notes без thead */
        .table-scroll-technical-notes table {
            border-collapse: separate;
            border-spacing: 0;
        }

    </style>
    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header justify-content-between d-flex align-items-center">
                <div>
                    <h4 class="text-primary mb-0">{{__('WO')}} {{$current_wo->number}} </h4>
                   <h4 class="mb-0"> {{__('Create WorkOrder R&M Record')}}</h4>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-outline-success btn-lg" style="width: 400px"
                            onclick="saveSelectedRecords()">
                        <i class="fas fa-save"></i> <h5 class="mb-0">{{ __('Save Technical Notes and Selected R&M Records to Work Order') }}</h5>
                    </button>
                    @if($current_wo->rm_report)
                        <a href="{{ route('rm_reports.edit', $current_wo->id) }}" class="btn btn-outline-warning btn-sm" style="height: 60px; width: 150px">
                            <i class="fas fa-edit"></i> {{ __('Edit Existing') }}
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-info btn-sm" style="height: 60px; width: 120px" data-bs-toggle="modal"
                            data-bs-target="#addRmRecordModal">{{ __('ADD Repair OR Modification') }}</button>
                        <a href="{{ route('rm_reports.show', $current_wo->id) }}" class="btn btn-outline-secondary align-content-center btn-sm"
                           style="height: 60px; width: 100px">
                            <i class="fas fa-eye"></i> {{ __('Back to R&M Record') }}
                        </a>
                </div>
            </div>
        </div>
        <div class="card-body">
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

            <form id="createForm" class="createForm" role="form" method="POST" action="#"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
            </form>

                <!-- Здесь будет отображаться список созданных записей -->
                <div id="rmRecordsList">
                    @if($rm_reports->count() > 0)
                        {{-- Отображение текущих сохраненных записей - скрыто --}}
                        {{-- @if($current_wo->rm_report)
                            @php
                                $savedData = json_decode($current_wo->rm_report, true);
                            @endphp
                            @if($savedData)
                                <div class="alert alert-info mt-3">
                                    <h6>{{ __('Currently Saved Data:') }}</h6>

                                    @if(isset($savedData['rm_records']) && !empty($savedData['rm_records']))
                                        <div class="mb-2">
                                            <strong>{{ __('R&M Records:') }}</strong>
                                            <ul class="mb-0">
                                                @foreach($savedData['rm_records'] as $record)
                                                    @php
                                                        $rmRecord = \App\Models\RmReport::find($record['id']);
                                                    @endphp
                                                    @if($rmRecord)
                                                        <li>{{ $rmRecord->part_description }} - {{ $rmRecord->mod_repair }}</li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if(isset($savedData['technical_notes']))
                                        <div>
                                            <strong>{{ __('Technical Notes:') }}</strong>
                                            <ul class="mb-0">
                                                @foreach($savedData['technical_notes'] as $noteKey => $noteValue)
                                                    @if(!empty($noteValue))
                                                        <li>{{ ucfirst(str_replace('note', 'Note ', $noteKey)) }}: {{ $noteValue }}</li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif --}}

                        <!-- Кнопки для массовых операций -->
                        <div class="row mt-3 mb-2">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllRecords()">
                                    <i class="fas fa-check-square"></i> {{ __('Select All') }}
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAllRecords()">
                                    <i class="fas fa-square"></i> {{ __('Deselect All') }}
                                </button>
                            </div>
                            <div class="col-md-6 text-end">
                                <!-- Кнопка перенесена перед Technical Notes -->
                            </div>
                        </div>

                        <div class="table-responsive table-scroll-rm-records mt-3">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{ __('Part Description') }}</th>
                                    <th>{{ __('Modification or Repair #') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Identification Method') }}</th>
                                    <th>{{ __('Select Record') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($rm_reports as $report)
                                    <tr>
                                        <td>{{ $report->part_description }}</td>
                                        <td>{{ $report->mod_repair }}</td>
                                        <td>{{ $report->description }}</td>
                                        <td>{{ $report->ident_method }}</td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input record-checkbox" type="checkbox"
                                                       id="record_{{ $report->id }}"
                                                       value="{{ $report->id }}"
                                                       name="selected_records[]">
                                                <label class="form-check-label" for="record_{{ $report->id }}">
                                                    Select
                                                </label>
                                            </div>
                                        </td>
                                        <td>
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
                        </div>
                    @endif
                </div>


            <!-- Technical Notes (dynamic, через модальное окно) -->
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="text-primary mb-0">{{ __('Technical Notes') }}</h5>
                    <button type="button"
                            class="btn btn-outline-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#technicalNoteModal">
                        {{ __('Add Notes') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-scroll-technical-notes">
                        <table class="table table-bordered">
                            <tbody id="technicalNotesTableBody">
                                <!-- Строки заметок будут добавляться через JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


        </div>


        <!-- Модальное окно для R&M Record -->
        <div class="modal fade" id="addRmRecordModal" tabindex="-1" aria-labelledby="addRmRecordlLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRmRecordlLabel">{{ __('ADD Repair OR Modification') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <form method="POST" id="addRmRecordForm" action="{{ route('rm_reports.store') }}">
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
                                        <label class="form-check-label" for="mod_repair_mod">
                                            Mod
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mod_repair" id="mod_repair_repair" value="Repair" required>
                                        <label class="form-check-label" for="mod_repair_repair">
                                            Repair
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mod_repair" id="mod_repair_sb" value="SB" required>
                                        <label class="form-check-label" for="mod_repair_sb">
                                            SB
                                        </label>
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

        <!-- Модальное окно для добавления/редактирования Technical Note -->
        <div class="modal fade" id="technicalNoteModal" tabindex="-1" aria-labelledby="technicalNoteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title" id="technicalNoteModalLabel">{{ __('Enter Note') }}</h5>
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

        <!-- Модальное окно для редактирования R&M Record -->
        <div class="modal fade" id="editRmRecordModal" tabindex="-1" aria-labelledby="editRmRecordLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRmRecordLabel">{{ __('Edit Repair OR Modification') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <form method="POST" id="editRmRecordForm" action="">
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
                                        <label class="form-check-label" for="edit_mod_repair_mod">
                                            Mod
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mod_repair" id="edit_mod_repair_repair" value="Repair" required>
                                        <label class="form-check-label" for="edit_mod_repair_repair">
                                            Repair
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mod_repair" id="edit_mod_repair_sb" value="SB" required>
                                        <label class="form-check-label" for="edit_mod_repair_sb">
                                            SB
                                        </label>
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

    </div>

        <script>
        // Хранилище технических заметок (динамический массив)
        let technicalNotes = [];

        // Инициализация после загрузки DOM
        document.addEventListener('DOMContentLoaded', function() {
            // Обработка отправки формы - обычная отправка без AJAX
            const addForm = document.getElementById('addRmRecordForm');
            if (addForm) {
                addForm.addEventListener('submit', function() {
                    // стандартная отправка
                });
            }

            const editForm = document.getElementById('editRmRecordForm');
            if (editForm) {
                editForm.addEventListener('submit', function() {
                    // стандартная отправка
                });
            }
        });

        // Функция для выбора всех записей
        function selectAllRecords() {
            document.querySelectorAll('.record-checkbox').forEach(function(cb) {
                cb.checked = true;
            });
        }

        // Функция для снятия выбора со всех записей
        function deselectAllRecords() {
            document.querySelectorAll('.record-checkbox').forEach(function(cb) {
                cb.checked = false;
            });
        }

        // Рендер таблицы технических заметок
        function renderTechnicalNotesTable() {
            const tbody = document.getElementById('technicalNotesTableBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            if (technicalNotes.length === 0) {
                return;
            }

            technicalNotes.forEach((note, index) => {
                const tr = document.createElement('tr');

                const tdText = document.createElement('td');
                tdText.className = 'align-middle';
                tdText.textContent = note;

                const tdActions = document.createElement('td');
                tdActions.className = 'text-end';
                tdActions.style.width = '120px';

                const editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'btn btn-sm btn-outline-primary me-1';
                editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                editBtn.onclick = function() { editTechnicalNote(index); };

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'btn btn-sm btn-outline-danger';
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                deleteBtn.onclick = function() { deleteTechnicalNote(index); };

                tdActions.appendChild(editBtn);
                tdActions.appendChild(deleteBtn);

                tr.appendChild(tdText);
                tr.appendChild(tdActions);

                tbody.appendChild(tr);
            });
        }

        // Открыть модал для редактирования заметки
        function editTechnicalNote(index) {
            const note = technicalNotes[index] || '';
            const input = document.getElementById('technicalNoteInput');
            const idxInput = document.getElementById('technicalNoteIndex');
            if (!input || !idxInput) return;

            input.value = note;
            idxInput.value = index;
            const modal = new bootstrap.Modal(document.getElementById('technicalNoteModal'));
            modal.show();
        }

        // Удалить заметку
        function deleteTechnicalNote(index) {
            technicalNotes.splice(index, 1);
            renderTechnicalNotesTable();
        }

        // Сохранить заметку из модального окна (новую или отредактированную)
        function saveTechnicalNote() {
            const input = document.getElementById('technicalNoteInput');
            const idxInput = document.getElementById('technicalNoteIndex');
            if (!input || !idxInput) return;

            const noteText = input.value.trim();
            const index = parseInt(idxInput.value, 10);

            if (noteText === '') {
                showNotification('Please enter a note text.', 'warning');
                return;
            }

            if (!isNaN(index) && index >= 0 && index < technicalNotes.length) {
                technicalNotes[index] = noteText;
            } else {
                technicalNotes.push(noteText);
            }

            input.value = '';
            idxInput.value = '-1';

            const modalEl = document.getElementById('technicalNoteModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            renderTechnicalNotesTable();
        }

        // Функция для сохранения выбранных записей в workorder
        function saveSelectedRecords() {
            const selectedRecords = Array.from(document.querySelectorAll('.record-checkbox:checked'))
                .map(cb => cb.value);

            // Проверяем, есть ли хотя бы одна заметка
            var hasNotes = technicalNotes.length > 0;

            // Проверяем, что есть либо выбранные записи, либо заметки
            if (selectedRecords.length === 0 && !hasNotes) {
                showNotification('Please select at least one R&M record or enter at least one technical note to save.', 'warning');
                return;
            }

            // Формируем сообщение подтверждения
            var confirmMessage = 'Are you sure you want to save ';
            if (selectedRecords.length > 0) {
                confirmMessage += selectedRecords.length + ' selected record(s)';
                if (hasNotes) {
                    confirmMessage += ' and technical notes';
                }
            } else {
                confirmMessage += 'technical notes';
            }
            confirmMessage += ' to this work order?';

            if (confirm(confirmMessage)) {
                // Создаем форму для отправки POST запроса
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("rm_reports.save.to.workorder") }}';

                // Добавляем CSRF токен
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                var meta = document.querySelector('meta[name="csrf-token"]');
                csrfToken.value = meta ? meta.getAttribute('content') : '';
                form.appendChild(csrfToken);

                // Добавляем workorder_id
                var workorderField = document.createElement('input');
                workorderField.type = 'hidden';
                workorderField.name = 'workorder_id';
                workorderField.value = '{{ $current_wo->id }}';
                form.appendChild(workorderField);

                // Добавляем выбранные записи
                var recordsField = document.createElement('input');
                recordsField.type = 'hidden';
                recordsField.name = 'selected_records';
                recordsField.value = JSON.stringify(selectedRecords);
                form.appendChild(recordsField);

                // Добавляем технические заметки как notes[]
                technicalNotes.forEach(function(note) {
                    var noteField = document.createElement('input');
                    noteField.type = 'hidden';
                    noteField.name = 'notes[]';
                    noteField.value = note;
                    form.appendChild(noteField);
                });

                // Добавляем форму в документ и отправляем
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Функция для редактирования записи
        function editRecord(recordId) {
            const url = '{{ route("rm_reports.getRecord", ":id") }}'.replace(':id', recordId);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const record = data.data;

                        document.getElementById('edit_record_id').value = record.id;
                        document.getElementById('edit_part_description').value = record.part_description;
                        document.getElementById('edit_mod_repair_description').value = record.description;
                        document.getElementById('edit_ident_method').value = record.ident_method;

                        const modRepairValue = record.mod_repair;
                        document.getElementById('edit_mod_repair_mod').checked = (modRepairValue === 'Mod');
                        document.getElementById('edit_mod_repair_repair').checked = (modRepairValue === 'Repair');
                        document.getElementById('edit_mod_repair_sb').checked = (modRepairValue === 'SB');

                        document.getElementById('editRmRecordForm').setAttribute(
                            'action',
                            '{{ route("rm_reports.updateRecord", ":id") }}'.replace(':id', record.id)
                        );
                    } else {
                        showNotification('Error loading record data', 'error');
                    }
                })
                .catch(() => {
                    showNotification('Error loading record data', 'error');
                });
        }

        // Функция для удаления одной записи
        function deleteRecord(recordId) {
            if (confirm('Are you sure you want to delete this record?')) {
                // Создаем форму для отправки DELETE запроса
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("rm_reports.destroy", ":id") }}'.replace(':id', recordId);

                // Добавляем CSRF токен
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = $('meta[name="csrf-token"]').attr('content');
                form.appendChild(csrfToken);

                // Добавляем метод DELETE
                var methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);

                // Добавляем workorder_id
                var workorderField = document.createElement('input');
                workorderField.type = 'hidden';
                workorderField.name = 'workorder_id';
                workorderField.value = '{{ $current_wo->id }}';
                form.appendChild(workorderField);

                // Добавляем форму в документ и отправляем
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

@endsection
