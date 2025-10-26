@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 1200px;
        }

    </style>
    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header justify-content-between d-flex">
                <div>
                    <h4 class="text-primary">{{__('WO')}} {{$current_wo->number}} </h4>
                   <h4> {{__('Create WorkOrder R&M Record')}}</h4>
                </div>
                <div class="d-flex gap-2">
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

            <!-- Save Button -->
            <div class="row mt-3 mb-3">
                <div class="col-12 text-center">
                    <button type="button" class="btn btn-outline-success btn-lg" style="width: 400px"
                            onclick="saveSelectedRecords()">
                        <i class="fas fa-save"></i> <h5>{{ __('Save Technical Notes and Selected R&M Records to Work Order') }}</h5>
                    </button>
                </div>
            </div>

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

                        <div class="table-responsive mt-3">
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


            <!-- Technical Notes Table -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="text-primary">{{ __('Technical Notes') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                @for($i = 1; $i <= 7; $i++)
                                    <tr>
{{--                                        <td style="width: 150px; background-color: #f8f9fa;">--}}
{{--                                            <strong>{{ __('Note') }} {{ $i }}</strong>--}}
{{--                                        </td>--}}
                                        <td>
                                            <input type="text"
                                                   class="form-control technical-note"
                                                   id="note{{ $i }}"
                                                   name="note{{ $i }}"
{{--                                                   value="{{ old('note' . $i, $current_wo->rm_report ? json_decode($current_wo->rm_report, true)['technical_notes']['note' . $i] ?? '' : '') }}"--}}
                                                   placeholder="{{ __('Enter technical note') }} {{ $i }}">
                                        </td>
                                    </tr>
                                @endfor
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

        <script>
        $(document).ready(function() {
            // Обработка отправки формы - обычная отправка без AJAX
            $('#addRmRecordForm').on('submit', function() {
                // Форма будет отправлена обычным способом
                // После успешной отправки страница перезагрузится с сообщением об успехе
            });

            // Обработка отправки формы редактирования
            $('#editRmRecordForm').on('submit', function() {
                // Форма будет отправлена обычным способом
                // После успешной отправки страница перезагрузится с сообщением об успехе
            });

            // Записи загружаются при загрузке страницы через Blade
        });

                // Функция для выбора всех записей
        function selectAllRecords() {
            $('.record-checkbox').prop('checked', true);
        }

        // Функция для снятия выбора со всех записей
        function deselectAllRecords() {
            $('.record-checkbox').prop('checked', false);
        }

        // Функция для сохранения выбранных записей в workorder
        function saveSelectedRecords() {
            var selectedRecords = $('.record-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selectedRecords.length === 0) {
                alert('Please select at least one record to save.');
                return;
            }

            if (confirm('Are you sure you want to save ' + selectedRecords.length + ' selected record(s) and technical notes to this work order?')) {
                // Создаем форму для отправки POST запроса
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("rm_reports.save.to.workorder") }}';

                // Добавляем CSRF токен
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = $('meta[name="csrf-token"]').attr('content');
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

                // Добавляем технические заметки
                for (var i = 1; i <= 7; i++) {
                    var noteValue = $('#note' + i).val();
                    var noteField = document.createElement('input');
                    noteField.type = 'hidden';
                    noteField.name = 'note' + i;
                    noteField.value = noteValue;
                    form.appendChild(noteField);
                }

                // Добавляем форму в документ и отправляем
                document.body.appendChild(form);
                form.submit();
            }
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
                        alert('Error loading record data');
                    }
                },
                error: function() {
                    alert('Error loading record data');
                }
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
