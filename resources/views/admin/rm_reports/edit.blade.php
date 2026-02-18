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
            <div class="card-header justify-content-between d-flex ">
                <div>
                    <h5 class="text-primary mb-0">{{__('WO')}} {{$current_wo->number}} </h5>
                   <h5 class="mb-0"> {{__('Edit WorkOrder R&M Record')}}</h5>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-outline-success btn-lg" style="width: 400px"
                            onclick="saveSelectedRecords()">
                        <i class="fas fa-save"></i> <h5 class="mb-0">{{ __('Update Technical Notes and Selected R&M Records to Work Order') }}</h5>
                    </button>
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

            @php
                // Получаем сохраненные данные из текущего workorder
                $savedData = $current_wo->rm_report ? json_decode($current_wo->rm_report, true) : null;
            @endphp

            <form id="editForm" class="editForm" role="form" method="POST" action="#"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
            </form>

                <!-- Здесь будет отображаться список созданных записей -->
                <div id="rmRecordsList">
                    @if($rm_reports->count() > 0)
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

                        <div class="table-responsive table-scroll-rm-records mt-1">
                            <table class="table table-striped text-center align-items-center">
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
                                @php
                                    $savedRecords = $savedData['rm_records'] ?? [];
                                    $savedRecordIds = collect($savedRecords)->pluck('id')->toArray();
                                @endphp
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
                                                       name="selected_records[]"
                                                    {{ in_array($report->id, $savedRecordIds) ? 'checked' : '' }}>
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

        <script>
        // Хранилище технических заметок (динамический массив)
        let technicalNotes = [];

        document.addEventListener('DOMContentLoaded', function() {
            // Обработка отправки формы - обычная отправка без AJAX
            const addForm = document.getElementById('addRmRecordForm');
            if (addForm) {
                addForm.addEventListener('submit', function() {
                    // стандартная отправка, ничего не делаем
                });
            }

            const editForm = document.getElementById('editRmRecordForm');
            if (editForm) {
                editForm.addEventListener('submit', function() {
                    // стандартная отправка, ничего не делаем
                });
            }

            // Инициализируем технические заметки из сохранённых данных
            @php
                $rawNotes = $savedData['technical_notes'] ?? [];
                $initialNotes = is_array($rawNotes) ? array_values($rawNotes) : [];
            @endphp
            technicalNotes = @json($initialNotes);
            renderTechnicalNotesTable();
        });

        // Рендер таблицы технических заметок
        function renderTechnicalNotesTable() {
            const tbody = $('#technicalNotesTableBody');
            tbody.empty();

            if (technicalNotes.length === 0) {
                return;
            }

            technicalNotes.forEach((note, index) => {
                const row = $(`
                    <tr>
                        <td class="align-middle">${note}</td>
                        <td class="text-end" style="width: 120px;">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editTechnicalNote(${index})">
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
        }

        // Открыть модал для редактирования заметки
        function editTechnicalNote(index) {
            const note = technicalNotes[index] || '';
            $('#technicalNoteInput').val(note);
            $('#technicalNoteIndex').val(index);
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
            const noteText = $('#technicalNoteInput').val().trim();
            const index = parseInt($('#technicalNoteIndex').val(), 10);

            if (noteText === '') {
                showNotification('Please enter a note text.', 'warning');
                return;
            }

            if (!isNaN(index) && index >= 0 && index < technicalNotes.length) {
                technicalNotes[index] = noteText;
            } else {
                technicalNotes.push(noteText);
            }

            $('#technicalNoteInput').val('');
            $('#technicalNoteIndex').val('-1');

            const modalEl = document.getElementById('technicalNoteModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();

            renderTechnicalNotesTable();
        }

                // Функция для выбора всех записей
        function selectAllRecords() {
            $('.record-checkbox').prop('checked', true);
        }

        // Функция для снятия выбора со всех записей
        function deselectAllRecords() {
            $('.record-checkbox').prop('checked', false);
        }

        // Функция для сохранения выбранных записей и технических заметок
        function saveSelectedRecords() {
            // Собираем выбранные записи (только ID)
            var selectedRecords = [];
            $('.record-checkbox:checked').each(function() {
                selectedRecords.push($(this).val());
            });

            // Создаем объект данных для отправки
            var formData = {
                selected_records: JSON.stringify(selectedRecords),
                workorder_id: {{ $current_wo->id }},
                _token: '{{ csrf_token() }}',
                _method: 'PUT'
            };

            // Добавляем технические заметки как массив notes[]
            technicalNotes.forEach(function(note, index) {
                formData['notes[' + index + ']'] = note;
            });

            // Отправляем данные на сервер
            $.ajax({
                url: '{{ route("rm_reports.update", $current_wo->id) }}',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Перенаправляем на страницу показа
                        window.location.href = '{{ route("rm_reports.show", $current_wo->id) }}';
                    } else {
                        showNotification('Error: ' + response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    showNotification('An error occurred while saving the data.', 'error');
                }
            });
        }

        // Функция для редактирования записи
        function editRecord(recordId) {
            // Получаем данные записи через AJAX
            $.ajax({
                url: '{{ route("rm_reports.getRecord", ":id") }}'.replace(':id', recordId),
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        var record = response.data;

                        // Заполняем форму данными
                        $('#edit_record_id').val(record.id);
                        $('#edit_part_description').val(record.part_description);
                        $('#edit_mod_repair_description').val(record.description);
                        $('#edit_ident_method').val(record.ident_method);

                        // Устанавливаем выбранную радиокнопку
                        var modRepairValue = record.mod_repair;
                        if (modRepairValue === 'Mod') {
                            $('#edit_mod_repair_mod').prop('checked', true);
                        } else if (modRepairValue === 'Repair') {
                            $('#edit_mod_repair_repair').prop('checked', true);
                        } else if (modRepairValue === 'SB') {
                            $('#edit_mod_repair_sb').prop('checked', true);
                        }

                        // Устанавливаем action для формы
                        $('#editRmRecordForm').attr('action', '{{ route("rm_reports.updateRecord", ":id") }}'.replace(':id', record.id));
                    } else {
                        showNotification('Error loading record data', 'error');
                    }
                },
                error: function() {
                    showNotification('Error loading record data', 'error');
                }
            });
        }

        // Функция для удаления записи
        function deleteRecord(recordId) {
            if (confirm('{{ __("Are you sure you want to delete this record?") }}')) {
                // Создаем форму для удаления
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("rm_reports.destroy", ":id") }}'.replace(':id', recordId);

                // Добавляем CSRF токен
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
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

                document.body.appendChild(form);
                form.submit();
            }
        }
        </script>
@endsection
