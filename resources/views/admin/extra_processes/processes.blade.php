@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 1080px;
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

        /* Стили для дропдауна vendors */
        .vendor-select {
            font-size: 0.875rem;
        }

        .d-flex.gap-2 {
            gap: 0.5rem !important;
            align-items: center;
        }

        /* Стили для drag & drop */
        .sortable-table tbody tr {
            cursor: move;
            transition: all 0.3s ease;
        }

        .sortable-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .sortable-table tbody tr.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .sortable-table tbody tr.drag-over {
            border-top: 3px solid #007bff;
            background-color: #e3f2fd;
        }

        .sortable-table tbody tr.drag-over-bottom {
            border-bottom: 3px solid #007bff;
            background-color: #e3f2fd;
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
                           class="btn btn-outline-success me-2">
                            <i class="fas fa-plus"></i> Add Process
                        </a>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                            <i class="fas fa-plus"></i> Add Vendor
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($extra_process && $extra_process->processes)
                    <div class="me-3">
                        <div class="table-wrapper me-3">
                            <table class="display table table-sm table-hover align-middle table-bordered bg-gradient sortable-table">
                                <thead>
                                <tr>
                                    <th class="text-primary text-center">Process Name</th>
                                    <th class="text-primary text-center" style="width: 450px;">Process</th>
                                    <th class="text-primary text-center">Action</th>
                                    <th class="text-primary text-center">Form</th>
                                </tr>
                                </thead>
                                <tbody id="sortable-tbody">
                                @if(is_array($extra_process->processes) && array_keys($extra_process->processes) !== range(0, count($extra_process->processes) - 1))
                                    {{-- Старая структура: ассоциативный массив --}}
                                    @foreach($extra_process->processes as $processNameId => $processId)
                                        @php
                                            $processName = \App\Models\ProcessName::find($processNameId);
                                            $process = \App\Models\Process::find($processId);
                                        @endphp
                                        @if($processName && $process)
                                            <tr data-id="{{ $extra_process->id }}">
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
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2">
                                                        <select class="form-select form-select-sm vendor-select"
                                                                style="width: 120px"
                                                                data-extra-process-id="{{ $extra_process->id }}"
                                                                data-process-name-id="{{ $processNameId }}">
                                                            <option value="">Select Vendor</option>
                                                            @foreach($vendors as $vendor)
                                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <a href="{{ route('extra_processes.show_form', ['id' => $extra_process->id, 'processNameId' => $processNameId]) }}"
                                                           class="btn btn-sm btn-outline-primary form-link"
                                                           style="width: 60px"
                                                           data-extra-process-id="{{ $extra_process->id }}"
                                                           data-process-name-id="{{ $processNameId }}"
                                                           target="_blank">{{__('Form')}}</a>
                                                    </div>
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
                                            <tr data-id="{{ $extra_process->id }}">
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
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <select class="form-select form-select-sm vendor-select"
                                                                style="width: 85px"
                                                                data-extra-process-id="{{ $extra_process->id }}"
                                                                data-process-name-id="{{ $processItem['process_name_id'] }}">
                                                            <option value="">Select Vendor</option>
                                                            @foreach($vendors as $vendor)
                                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <a href="{{ route('extra_processes.show_form', ['id' => $extra_process->id, 'processNameId' => $processItem['process_name_id']]) }}"
                                                           class="btn btn-sm btn-outline-primary form-link"
                                                           style="width: 60px"
                                                           data-extra-process-id="{{ $extra_process->id }}"
                                                           data-process-name-id="{{ $processItem['process_name_id'] }}"
                                                           target="_blank">{{__('Form')}}</a>
                                                    </div>
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

    <!-- Модальное окно для добавления vendor -->
    <div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVendorModalLabel">Add New Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addVendorForm">
                        @csrf
                        <div class="mb-3">
                            <label for="vendorName" class="form-label">Vendor Name</label>
                            <input type="text" class="form-control" id="vendorName" name="name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveVendorButton">Save Vendor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Подключение библиотеки SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Скрипт для обработки подтверждения удаления -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Инициализация drag & drop
            const sortable = Sortable.create(document.getElementById('sortable-tbody'), {
                animation: 150,
                ghostClass: 'dragging',
                dragClass: 'dragging',
                onEnd: function(evt) {
                    // Получаем новый порядок элементов
                    const newOrder = Array.from(sortable.el.children).map((row, index) => {
                        return {
                            id: row.getAttribute('data-id'),
                            sort_order: index + 1
                        };
                    });
                    
                    // Отправляем AJAX запрос для обновления порядка
                    updateProcessOrder(newOrder);
                }
            });

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

            // Обработчик для дропдауна vendors
            const vendorSelects = document.querySelectorAll('.vendor-select');
            vendorSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const extraProcessId = this.getAttribute('data-extra-process-id');
                    const processNameId = this.getAttribute('data-process-name-id');
                    const vendorId = this.value;
                    const vendorName = this.options[this.selectedIndex].text;

                    if (vendorId) {
                        // Здесь можно добавить логику для сохранения выбранного vendor
                        // Например, отправить AJAX запрос
                        console.log('Selected vendor:', {
                            extraProcessId: extraProcessId,
                            processNameId: processNameId,
                            vendorId: vendorId,
                            vendorName: vendorName
                        });
                    }
                });
            });

            // Обработчик для кнопок Form
            const formLinks = document.querySelectorAll('.form-link');
            formLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const extraProcessId = this.getAttribute('data-extra-process-id');
                    const processNameId = this.getAttribute('data-process-name-id');

                    // Находим соответствующий дропдаун vendor
                    const vendorSelect = document.querySelector(`select[data-extra-process-id="${extraProcessId}"][data-process-name-id="${processNameId}"]`);

                    if (vendorSelect && vendorSelect.value) {
                        // Добавляем vendor_id к URL
                        const currentUrl = this.href;
                        const separator = currentUrl.includes('?') ? '&' : '?';
                        this.href = currentUrl + separator + 'vendor_id=' + vendorSelect.value;
                    }
                });
            });

            // Обработчик для добавления vendor
            const saveVendorButton = document.getElementById('saveVendorButton');
            const addVendorForm = document.getElementById('addVendorForm');
            const vendorNameInput = document.getElementById('vendorName');

            saveVendorButton.addEventListener('click', function() {
                const vendorName = vendorNameInput.value.trim();
                
                if (!vendorName) {
                    alert('Please enter vendor name');
                    return;
                }

                // Отправляем AJAX запрос для создания vendor
                fetch('{{ route("vendors.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: vendorName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Добавляем новый vendor в дропдауны
                        const newOption = document.createElement('option');
                        newOption.value = data.vendor.id;
                        newOption.textContent = data.vendor.name;
                        
                        // Добавляем во все дропдауны
                        document.querySelectorAll('.vendor-select').forEach(select => {
                            select.appendChild(newOption.cloneNode(true));
                        });
                        
                        // Закрываем модальное окно
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addVendorModal'));
                        modal.hide();
                        
                        // Очищаем форму
                        addVendorForm.reset();
                        
                        alert('Vendor added successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding vendor');
                });
            });

            // Функция для обновления порядка процессов
            function updateProcessOrder(newOrder) {
                const processIds = newOrder.map(item => item.id);
                
                fetch('{{ route("extra_processes.update-order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        process_ids: processIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Order updated successfully');
                        // Показываем уведомление пользователю
                        showNotification('Порядок процессов обновлен', 'success');
                    } else {
                        console.error('Error updating order:', data.message);
                        showNotification('Ошибка обновления порядка: ' + data.message, 'error');
                        // Восстанавливаем предыдущий порядок
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Ошибка сети при обновлении порядка', 'error');
                    location.reload();
                });
            }

            // Функция для показа уведомлений
            function showNotification(message, type) {
                // Создаем уведомление
                const notification = document.createElement('div');
                notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                notification.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.body.appendChild(notification);
                
                // Автоматически убираем уведомление через 3 секунды
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 3000);
            }
        });
    </script>
@endsection
