@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 900px;
        }

        /* Стили для Select2 (темная и светлая темы) */
        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: #121212 !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid #495057 !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: #343A40 !important;
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 25px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
            border: 1px solid #ccc !important;
            border-radius: 8px;
            color: white;
            background-color: #121212 !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
            background-color: #6ea8fe;
            color: #000000;
        }

        .select2-container .select2-selection__clear {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 1;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{ __('Component Processes') }}</h4>
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
                <div class="me-3">
                    <div class="table-wrapper me-3">
                        <table class="display table table-sm table-hover align-middle table-bordered bg-gradient">
                            <thead>
                            <tr>
                                <th class="text-primary text-center">Process Name</th>
                                <th class="text-primary text-center" style="width: 450px;">Process</th>
                                <th class="text-primary text-center">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tdrProcesses as $processes)
                                @if($processes->tdrs_id == $current_tdr->id)
                                    @php
                                        // Декодируем JSON-поле processes
                                        $processData = json_decode($processes->processes, true);
                                        // Получаем имя процесса из связанной модели ProcessName
                                        $processName = $processes->processName->name;
                                    @endphp

                                    @foreach($processData as $process)
                                        <tr>
                                            <td class="text-center">{{ $processName }}</td>
                                            <td class="ps-2">

                                                @foreach($proces as $proc)
                                                    @if($proc->id ==$process  )
                                                        {{$proc->process}}
                                                    @endif
                                                @endforeach

                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('tdr-processes.edit', ['tdr_process' =>
                                                $processes->id]) }}" class="btn btn-sm btn-outline-primary">{{__('Edit')}}</a>
                                                <form id="deleteForm_{{ $processes->id }}" action="{{ route('tdr-processes.destroy', ['tdr_process' => $processes->id]) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="tdrId" value="{{ $current_tdr->id }}">
                                                    <input type="hidden" name="process" value="{{ $process }}">
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="button"
                                                            name="btn_delete" data-bs-toggle="modal"
                                                            data-bs-target="#useConfirmDelete" data-title="Delete
                                                            Confirmation:  {{ $processes->processName->name }}">
                                                        {{__('Delete')}}
                                                    </button>
                                                </form>
                                                <a href="{{ route('tdr-processes.show', ['tdr_process' =>
                                                $processes->id]) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{__('Form')}}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <a href="{{ route('tdrs.processes', ['workorder_id'=>$current_tdr->workorder->id]) }}"
                   class="btn btn-outline-secondary mt-3">{{ __('All Components Processes') }} </a>
            </div>
        </div>
    </div>
    <!-- Модальное окно для подтверждения удаления -->
    <div class="modal fade" id="useConfirmDelete" tabindex="-1" aria-labelledby="useConfirmDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="useConfirmDeleteLabel">Delete Confirmation:</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this process?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Скрипт для обработки подтверждения удаления -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteModal = document.getElementById('useConfirmDelete');
            const confirmDeleteButton = document.getElementById('confirmDeleteButton');
            let deleteForm = null;

            // Обработчик открытия модального окна
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Кнопка, которая вызвала модальное окно
                const title = button.getAttribute('data-title'); // Заголовок модального окна
                deleteForm = button.closest('form'); // Находим форму удаления

                // Устанавливаем заголовок модального окна
                deleteModal.querySelector('.modal-title').textContent = title;
            });

            // Обработчик нажатия на кнопку "Delete" в модальном окне
            confirmDeleteButton.addEventListener('click', function () {
                if (deleteForm) {
                    deleteForm.submit(); // Отправляем форму удаления
                }
            });
        });
    </script>
@endsection
