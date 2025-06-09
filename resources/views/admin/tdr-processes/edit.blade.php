@extends('admin.master')

@section('content')
    <style>
        /* Ваши стили */
    </style>

    <div class="container mt-3" style="width: 850px">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{ __('Edit Component Processes') }}</h4>
                    <h4 class="pe-3">{{ __('W') }}{{ $current_tdr->workorder->number }}</h4>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        {{ $current_tdr->component->name }}
                        PN: {{ $current_tdr->component->part_number }}
                        SN: {{ $current_tdr->serial_number }}
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tdr-processes.update', $current_tdr_processes->id) }}" enctype="multipart/form-data" id="editCPForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">

                    <!-- Контейнер для строк с процессами -->
                    <div id="processes-container">
                        <div class="process-row mb-3">
                            <div class="row" >
                                <div class="col-md-6" style="width: 200px">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process" required>
                                        <option value="">Select Process Name</option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}" {{ $current_tdr_processes->process_names_id == $processName->id ? 'selected' : '' }}>
                                                {{ $processName->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="process">Processes:</label>
                                    <div class="process-options">
                                        @foreach ($processes as $process)
                                            <div class="form-check" data-process-name-id="{{ $process->process_names_id }}">
                                                <input type="checkbox" name="processes[0][process][]" value="{{ $process->id }}" class="form-check-input"
                                                    {{ in_array($process->id, json_decode($current_tdr_processes->processes, true)) ? 'checked' : '' }}>
                                                <label class="form-check-label">{{ $process->process }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Update') }}</button>
                        <a href="{{ route('tdrs.processes', ['workorder_id' => $current_tdr->workorder->id]) }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const processNameSelect = document.querySelector('select[name="processes[0][process_names_id]"]');
            const processOptions = document.querySelectorAll('.process-options .form-check');
            const processCheckboxes = document.querySelectorAll('.process-options .form-check input[type="checkbox"]');
            let lastSelectedProcessNameId = processNameSelect.value; // Сохраняем начальное значение

            // Функция для фильтрации и условного сброса процессов
            function filterProcesses() {
                const selectedProcessNameId = processNameSelect.value;

                // Сбрасываем все чекбоксы только если было изменение в process_name
                if (lastSelectedProcessNameId !== selectedProcessNameId) {
                    processCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                }

                // Обновляем lastSelectedProcessNameId
                lastSelectedProcessNameId = selectedProcessNameId;

                // Фильтруем процессы для отображения
                processOptions.forEach(option => {
                    const processNameId = option.getAttribute('data-process-name-id');
                    if (processNameId === selectedProcessNameId) {
                        option.style.display = 'block'; // Показываем процесс
                    } else {
                        option.style.display = 'none'; // Скрываем процесс
                    }
                });
            }

            // Вызываем фильтрацию при изменении выбранного значения
            processNameSelect.addEventListener('change', filterProcesses);

            // Начальный вызов функции фильтрации для установки видимости процессов
            filterProcesses();
        });


    </script>
@endsection
