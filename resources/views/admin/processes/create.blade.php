@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 860px;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">Add Process for Manual {{$manual->first()->number}} ({{$manual->first()->title}})</h4>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('processes.store') }}" enctype="multipart/form-data"
                      id="createCMMForm">
                @csrf
                    <input type="hidden" name="manual_id" value="{{ $manual->first()->id }}">

                    <div class="form-group d-flex">
                        <div class="form-group ">
                            <div class="d-flex">
                                <div class="me-3">
                                    <label for="process_name_id">{{ __('Process Name') }}</label>
                                    <select id="process_name_id" name="process_name_id" class="form-control mt-2" required
                                            style="width: 350px">
                                        <option value="">{{ __('Select Process Name') }}</option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                            data-bs-target="#addProcessNameModal">{{ __('Add Process Name') }}</button>
                                </div>
                                <div class=" me-3"><h6 class="ms-3 p-4"> {{__( '        ')}}</h6></div>
                                <div>
                                    <h6 class="p-1" style="width: 350px">{{ __('Existing Processes') }}</h6>
                                    <div id="ex_process-list" class="flex-grow-1 ps-2 border">
                                        <!-- Динамический список existing процессов -->
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex mt-3">
                                <div class="me-4 ">
                                    <label for="process">{{ __('Enter Process') }}</label>
                                    <input id="process" style="width: 350px" type="text" class="form-control mt-2" name="process"
                                           placeholder="Enter a New process">
                                </div>
                                <div class=" me-2"><h6 class="p-4"> {{__( ' Or ')}}</h6></div>
                                <div>

                                    <div>
                                        <h6 class="p-1" style="width: 350px">{{ __('Select Process') }}</h6>
                                        <div id="process-list" class="flex-grow-1 ps-2 border">
                                            <!-- Динамический список процессов -->
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <input type="hidden" id="selected_process_id" name="selected_process_id">
                            </div>

                        </div>

                    <div class="text-end m-3">
                        <button type="submit" class="btn btn-outline-primary mt-3 ">{{ __('Save') }}</button>
                        <a href="{{ route('processes.index') }}" class="btn btn-outline-secondary mt-3">{{ __('Back') }}
                        </a>
                    </div>
                </form>
            </div>

            <!-- Модальное окно для добавления названия процесса -->
            <div class="modal fade" id="addProcessNameModal" tabindex="-1" aria-labelledby="addProcessNameModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addProcessNameModalLabel">{{ __('Add Process Name') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <form action="{{ route('process-names.store') }}" method="POST" id="addProcessName">
                            @csrf
                        <div class="modal-body">
                            <input type="hidden" name="manual_id" value="{{ $manual->first()->id }}">

                            <!-- Форма для добавления нового названия процесса -->
                                <div class="form-group">
                                    <label for="name">{{ __('Process name') }}</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="process_sheet_name">{{ __('Process Sheet Name') }}</label>
                                    <input type="text" class="form-control" id="process_sheet_name" name="process_sheet_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="form_number">{{ __('Form Number') }}</label>
                                    <input type="text" class="form-control" id="form_number" name="form_number" required>
                                </div>

                        </div>
                        <div class="modal-footer ">
                            <button type="submit" class="btn btn-outline-primary " >
                                {{ __('Save') }}
                            </button>

                            <button type="button" class="btn btn-outline-secondary " data-bs-dismiss="modal">
                                {{ __('Cansel') }}
                            </button>

                        </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const processNameSelect = document.getElementById('process_name_id');
            const processList = document.getElementById('process-list'); // Список доступных процессов
            const exProcessList = document.getElementById('ex_process-list'); // Список существующих процессов
            const manualId = document.querySelector('input[name="manual_id"]').value; // Получаем manual_id из скрытого поля

            if (processNameSelect && processList && exProcessList) {
                processNameSelect.addEventListener('change', function () {
                    const processNameId = this.value;

                    console.log('Process Name ID changed:', processNameId); // Проверка, что событие срабатывает

                    if (processNameId) {
                        fetch(`/admin/get-processes?processNameId=${processNameId}&manualId=${manualId}`) // Добавляем manualId в запрос
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Ошибка загрузки данных');
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log('Data received:', data); // Проверка полученных данных

                                // Очистка списков
                                processList.innerHTML = '';
                                exProcessList.innerHTML = '';

                                // Отображение существующих процессов
                                if (data.existingProcesses.length > 0) {
                                    data.existingProcesses.forEach(process => {
                                        const processItem = document.createElement('div');
                                        processItem.className = 'process-item';
                                        processItem.textContent = process.process; // Дисплей процесса
                                        processItem.dataset.id = process.id;
                                        processItem.style.cursor = 'pointer';
                                        processItem.style.marginBottom = '5px';

                                        exProcessList.appendChild(processItem);
                                    });
                                } else {
                                    exProcessList.innerHTML = '<div>There are no existing processes</div>';
                                }

                                // Отображение доступных процессов
                                if (data.availableProcesses.length > 0) {
                                    data.availableProcesses.forEach(process => {
                                        const processItem = document.createElement('div');
                                        processItem.className = 'process-item';
                                        processItem.textContent = process.process; // Дисплей процесса
                                        processItem.dataset.id = process.id;
                                        processItem.style.cursor = 'pointer';
                                        processItem.style.marginBottom = '5px';

                                        // Обработка выбора процесса из списка
                                        processItem.addEventListener('click', function () {
                                            document.getElementById('selected_process_id').value = process.id;
                                            document.querySelectorAll('.process-item').forEach(item => {
                                                item.style.backgroundColor = '';
                                            });
                                            this.style.backgroundColor = '#2c74de';
                                        });

                                        processList.appendChild(processItem);
                                    });
                                } else {
                                    processList.innerHTML = '<div>No processes available</div>';
                                }
                            })
                            .catch(error => {
                                console.error('Ошибка:', error);
                                processList.innerHTML = '<div>Failed to load processes</div>';
                                exProcessList.innerHTML = '<div>Failed to load processes</div>';
                            });
                    } else {
                        processList.innerHTML = ''; // Очистка списка, если ничего не выбрано
                        exProcessList.innerHTML = ''; // Очистка списка, если ничего не выбрано
                    }
                });
            }
        });

    </script>

@endsection
