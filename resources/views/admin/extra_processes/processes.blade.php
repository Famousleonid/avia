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
                    <h4 class="text-primary">{{ __('Component Extra Processes') }}</h4>
                    <h4 class="pe-3">{{ __('W') }}{{ $current_wo->number }}</h4>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>Component:</strong> {{ $component->name }}<br>
                        <strong>IPL:</strong> {{ $component->ipl_num }}<br>
                        <strong>Part Number:</strong> {{ $component->part_number }}
                    </div>
                    <div>
                        <a href="{{ route('extra_processes.create_processes', ['workorderId' => $current_wo->id, 'componentId' => $component->id]) }}"
                           class="btn btn-outline-success">
                            <i class="fas fa-plus"></i> Add Process
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($extra_process && $extra_process->processes)
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
                                @if(is_array($extra_process->processes) && array_keys($extra_process->processes) !== range(0, count($extra_process->processes) - 1))
                                    {{-- Старая структура: ассоциативный массив --}}
                                    @foreach($extra_process->processes as $processNameId => $processId)
                                        @php
                                            $processName = \App\Models\ProcessName::find($processNameId);
                                            $process = \App\Models\Process::find($processId);
                                        @endphp
                                        @if($processName && $process)
                                            <tr>
                                                <td class="text-center">{{ $processName->name }}</td>
                                                <td class="ps-2">{{ $process->process }}</td>
                                                <td class="text-center">
                                                    <a href="{{ route('extra_processes.edit', ['extra_process' => $extra_process->id]) }}" 
                                                       class="btn btn-sm btn-outline-primary">{{__('Edit')}}</a>
                                                    <form id="deleteForm_{{ $extra_process->id }}_{{ $processNameId }}" 
                                                          action="{{ route('extra_processes.destroy', ['extra_process' => $extra_process->id]) }}" 
                                                          method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="process_name_id" value="{{ $processNameId }}">
                                                        <button class="btn btn-sm btn-outline-danger" type="button"
                                                                name="btn_delete" data-bs-toggle="modal"
                                                                data-bs-target="#useConfirmDelete" 
                                                                data-title="Delete Confirmation: {{ $processName->name }}">
                                                            {{__('Delete')}}
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('extra_processes.show_form', ['id' => $extra_process->id, 'processNameId' => $processNameId]) }}" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">{{__('Form')}}</a>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @else
                                    {{-- Новая структура: массив объектов --}}
                                    @foreach($extra_process->processes as $index => $processItem)
                                        @php
                                            $processName = \App\Models\ProcessName::find($processItem['process_name_id']);
                                            $process = \App\Models\Process::find($processItem['process_id']);
                                        @endphp
                                        @if($processName && $process)
                                            <tr>
                                                <td class="text-center">{{ $processName->name }}</td>
                                                <td class="ps-2">{{ $process->process }}</td>
                                                <td class="text-center">
                                                    <a href="{{ route('extra_processes.edit', ['extra_process' => $extra_process->id]) }}" 
                                                       class="btn btn-sm btn-outline-primary">{{__('Edit')}}</a>
                                                    <form id="deleteForm_{{ $extra_process->id }}_{{ $index }}" 
                                                          action="{{ route('extra_processes.destroy', ['extra_process' => $extra_process->id]) }}" 
                                                          method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="process_index" value="{{ $index }}">
                                                        <button class="btn btn-sm btn-outline-danger" type="button"
                                                                name="btn_delete" data-bs-toggle="modal"
                                                                data-bs-target="#useConfirmDelete" 
                                                                data-title="Delete Confirmation: {{ $processName->name }}">
                                                            {{__('Delete')}}
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('extra_processes.show_form', ['id' => $extra_process->id, 'processNameId' => $processItem['process_name_id']]) }}" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">{{__('Form')}}</a>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <strong>No processes defined for this component.</strong><br>
                        Click "Add Process" to add processes to this component.
                    </div>
                @endif
                
                <a href="{{ route('extra_processes.show_all', ['id' => $current_wo->id]) }}"
                   class="btn btn-outline-secondary mt-3">{{ __('Back to All Components') }}</a>
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
                    // Отправляем форму через AJAX
                    const formData = new FormData(deleteForm);
                    
                    fetch(deleteForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                location.reload();
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting process');
                    });
                    
                    // Закрываем модальное окно
                    const modal = bootstrap.Modal.getInstance(deleteModal);
                    modal.hide();
                }
            });
        });
    </script>
@endsection 