@extends('admin.master')

@section('content')
    <style>
        .container {
            /*max-width: 1200px;*/
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

        .parent {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(1, 1fr);
            gap: 8px;
        }
        .fs-75 {
            font-size: 0.75rem; /* или любое другое подходящее значение */
        }
        .fs-7 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <h4 class="text-primary mb-0">{{ __('Component Processes') }}</h4>

                    </div>
                    <h4 class="pe-3 mb-0">{{ __('W') }}{{ $current_tdr->workorder->number }}</h4>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <div>
                        {{ $current_tdr->component->name }}
                        <p>
                        PN: {{ $current_tdr->component->part_number }}
                        SN: {{ $current_tdr->serial_number }}</p>
                    </div>

                    <a href="{{ route('tdrs.processes', ['workorder_id'=>$current_tdr->workorder->id]) }}"
                       class="btn btn-outline-secondary " style="line-height: .9rem;width: 150px;height: 42px">
                        {{ __('All Components Processes') }}
                    </a>
                    <div class="ms-2 d-flex" style="width: 300px">
                        @if(isset($processGroups) && count($processGroups) > 0)
                            <div style="width: 150px">
                                <x-paper-button-multy
                                    text="Group Process Forms"
                                    color="outline-primary"
                                    size="landscape"
                                    width="100"
                                    ariaLabel="Group Process Forms"
                                    data-bs-toggle="modal"
                                    data-bs-target="#groupFormsModal"
                                />
                            </div>
                        @else
                            {{-- Отладка: проверяем наличие переменной --}}
                            {{-- @if(isset($processGroups)) Debug: processGroups exists but empty ({{ count($processGroups) }}) @else Debug: processGroups not set @endif --}}
                        @endif
                            <a href="{{ route('tdr-processes.traveler', ['tdrId' => $current_tdr->id]) }}"
                               class="btn btn-outline-info  me-2" style="height: 42px">
                                <i class="fas fa-file-alt"></i> Traveler
                            </a>
                    </div>
                    <div class="d-flex parent">
                        <div class="ms-5">


                            <a href="{{ route('tdr-processes.createProcesses', ['tdrId' => $current_tdr->id]) }}"
                               class="btn btn-outline-success mt-2 me-2">
                                <i class="fas fa-plus"></i> Add Process
                            </a>
                            <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal"
                                    data-bs-target="#addVendorModal">
                                <i class="fas fa-plus"></i> Add Vendor
                            </button>
                        </div>

                    </div>
                    <div class="me-4">
                        <button type="button"
                                class="btn btn-outline-secondary btn-sm"
                                onclick="window.history.back();">
                            <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                        </button>
                    </div>

                </div>
            </div>
            <div class="card-body">
                <div class="me-3">
                    <div class="table-wrapper me-3">
                        <table class="display table table-sm table-hover align-middle table-bordered bg-gradient sortable-table">
                            <thead>
                            <tr>
                                <th class="text-primary text-center">Process Name</th>
                                <th class="text-primary text-center" style="width: 350px;">Process</th>
                                <th class="text-primary text-center">Description</th>
                                <th class="text-primary text-center">Notes</th>
                                <th class="text-primary text-center">Action</th>
                                <th class="text-primary text-center">Form</th>
                            </tr>
                            </thead>
                            <tbody id="sortable-tbody">
                            @foreach($tdrProcesses as $processes)
                                @if($processes->tdrs_id == $current_tdr->id)
                                    @php
                                        // Декодируем JSON-поле processes
                                        $processData = json_decode($processes->processes, true);
                                        // Получаем имя процесса из связанной модели ProcessName
                                        $processName = $processes->processName->name;
                                    @endphp

                                    @foreach($processData as $process)
                                        <tr data-id="{{ $processes->id }}">
                                            <td class="text-center">{{ $processName }}</td>
                                            <td class="ps-2">

                                                @foreach($proces as $proc)
                                                    @if($proc->id ==$process  )
                                                        {{$proc->process}}@if($processes->ec) ( EC ) @endif
                                                    @endif
                                                @endforeach

                                            </td>
                                            <td class="text-center">
                                                {{$processes->description ?? ''}}
                                            </td>
                                            <td class="text-center">
                                                {{$processes->notes ?? ''}}
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
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <select class="form-select form-select-sm vendor-select"
                                                            style="width: 85px"
                                                            data-tdr-process-id="{{ $processes->id }}"
                                                            data-process="{{ $process }}">
                                                        <option value="">Select Vendor</option>
                                                        @foreach($vendors as $vendor)
                                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <a href="{{ route('tdr-processes.show', ['tdr_process' =>
                                                    $processes->id, 'process_id' => $process]) }}" class="btn btn-sm btn-outline-primary form-link"
                                                       style="width: 60px"
                                                       data-tdr-process-id="{{ $processes->id }}"
                                                       data-process="{{ $process }}"
                                                       target="_blank">{{__('Form')}}</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal - Group Process Forms -->
    @if(isset($processGroups) && count($processGroups) > 0)
        <div class="modal fade" id="groupFormsModal" tabindex="-1" aria-labelledby="groupFormsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="groupFormsModalLabel">
                            <i class="fas fa-print"></i> {{ __('Group Process Forms') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <p class="text-muted mb-3">
                                    <i class="fas fa-info-circle"></i>
                                    Select a process type to generate a grouped form. Each process type can have its own vendor and process selection.
                                </p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered bg-gradient shadow">
                                <thead>
                                <tr>
                                    <th class="text-primary text-center" style="width: 25%;">Process</th>
                                    <th class="text-primary text-center" style="width: 25%;">Processes</th>
                                    <th class="text-primary text-center" style="width: 25%;">Vendor</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($processGroups as $groupKey => $group)
                                    @php
                                        // Для группы NDT используем ID процесса из process_name, иначе используем groupKey
                                        $actualProcessNameId = ($groupKey == 'NDT_GROUP') ? $group['process_name']->id : $groupKey;
                                        // Для группы NDT отображаем "NDT", иначе название процесса
                                        $displayName = ($groupKey == 'NDT_GROUP') ? 'NDT' : $group['process_name']->name;
                                    @endphp
                                    <tr>
                                        <td class="align-middle ">
                                            <div class="position-relative d-inline-block ms-5">
                                                <x-paper-button
                                                    text="{{ $displayName }} "
                                                    size="landscape"
                                                    width="120px"
                                                    href="{{ route('tdrs.show_group_forms', ['id' => $current_tdr->workorder->id, 'processNameId' => $actualProcessNameId]) }}"
                                                    target="_blank"
                                                    class="group-form-button"
                                                    data-process-name-id="{{ $actualProcessNameId }}"
                                                > </x-paper-button>

                                                <span class="badge bg-success  mt-1 ms-1 process-qty-badge"
                                                      data-process-name-id="{{ $actualProcessNameId }}"
                                                      style="position: absolute; top: -5px; left: 5px; min-width: 20px;
                                                          height: 30px;
                                              display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">
                                                        {{$group['qty'] }} pcs</span>

                                            </div>

                                        </td>
                                        <td class="align-middle">
                                            <div class="process-checkboxes" data-process-name-id="{{ $actualProcessNameId }}">
                                                @if($group['count'] > 1)
                                                    @foreach($group['processes'] as $processItem)
                                                        <div class="form-check">
                                                            <input class="ms-1 form-check-input process-checkbox"
                                                                   type="checkbox"
                                                                   value="{{ $processItem['id'] }}"
                                                                   id="process_{{ $actualProcessNameId }}_{{ $processItem['id'] }}"
                                                                   data-process-name-id="{{ $actualProcessNameId }}"
                                                                   data-qty="{{ $processItem['qty'] }}"
                                                                   data-tdr-process-id="{{ $processItem['tdr_process_id'] }}"
                                                                   checked>
                                                            <label class="form-check-label fs-7" for="process_{{
                                                            $actualProcessNameId }}_{{ $processItem['id'] }}">
                                                                <strong>{{ $processItem['name'] }}</strong>@if($processItem['ec']) (EC)@endif
                                                                <span class="">Qty: {{ $processItem['qty'] }}</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    @foreach($group['processes'] as $processItem)
                                                        <div class="form-check">
                                                            <input class="ms-1 form-check-input process-checkbox"
                                                                   type="checkbox"
                                                                   value="{{ $processItem['id'] }}"
                                                                   id="process_{{ $actualProcessNameId }}_{{ $processItem['id'] }}"
                                                                   data-process-name-id="{{ $actualProcessNameId }}"
                                                                   data-qty="{{ $processItem['qty'] }}"
                                                                   data-tdr-process-id="{{ $processItem['tdr_process_id'] }}"
                                                                   checked
                                                                   disabled>
                                                            <label class="form-check-label fs-7" for="process_{{
                                                            $actualProcessNameId }}_{{ $processItem['id'] }}">
                                                                <strong>{{ $processItem['name'] }}</strong>@if($processItem['ec']) (EC)@endif
                                                                <span class="">Qty: {{ $processItem['qty'] }}</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <select class="form-select vendor-select"
                                                    data-process-name-id="{{ $actualProcessNameId }}"
                                                    style="font-size: 0.9rem;">
                                                <option value="">No vendor</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
            // Обработчик для кнопок "Group Process Forms"
            // Обрабатываем клики по кнопкам, которые должны открывать модальное окно Group Process Forms
            function openGroupFormsModal(e) {
                e.preventDefault();
                e.stopPropagation();
                const modalElement = document.getElementById('groupFormsModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modal.show();
                } else {
                    console.error('Modal #groupFormsModal not found!');
                }
            }

            // Вариант 1: По атрибуту data-bs-target
            document.querySelectorAll('[data-bs-target="#groupFormsModal"]').forEach(function(button) {
                button.addEventListener('click', openGroupFormsModal);
            });

            // Вариант 2: По классу paper-btn-multy и тексту (для компонента x-paper-button-multy)
            document.querySelectorAll('.paper-btn-multy').forEach(function(button) {
                // Проверяем, содержит ли кнопка или её родитель атрибут data-bs-target
                if (button.hasAttribute('data-bs-target') && button.getAttribute('data-bs-target') === '#groupFormsModal') {
                    button.addEventListener('click', openGroupFormsModal);
                } else {
                    // Проверяем по тексту внутри SVG
                    const svg = button.querySelector('svg');
                    if (svg) {
                        const foreignObject = svg.querySelector('foreignObject');
                        if (foreignObject) {
                            const text = foreignObject.textContent.trim();
                            if (text.includes('Group Process Forms')) {
                                button.addEventListener('click', openGroupFormsModal);
                            }
                        }
                    }
                }
            });

            // Вариант 3: Обработчик на уровне документа для любых кликов по элементам внутри кнопки
            document.addEventListener('click', function(e) {
                // Проверяем, кликнули ли по элементу внутри кнопки с data-bs-target="#groupFormsModal"
                const button = e.target.closest('[data-bs-target="#groupFormsModal"]');
                if (button) {
                    openGroupFormsModal(e);
                    return;
                }

                // Проверяем, кликнули ли по SVG или его содержимому внутри paper-btn-multy
                const clickedElement = e.target;
                const paperButton = clickedElement.closest('.paper-btn-multy');
                if (paperButton) {
                    // Проверяем атрибут или текст
                    if (paperButton.hasAttribute('data-bs-target') &&
                        paperButton.getAttribute('data-bs-target') === '#groupFormsModal') {
                        openGroupFormsModal(e);
                        return;
                    }

                    // Проверяем по тексту
                    const svg = paperButton.querySelector('svg');
                    if (svg) {
                        const foreignObject = svg.querySelector('foreignObject');
                        if (foreignObject) {
                            const text = foreignObject.textContent.trim();
                            if (text.includes('Group Process Forms')) {
                                openGroupFormsModal(e);
                            }
                        }
                    }
                }
            });

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
                    deleteForm.submit(); // Отправляем форму удаления
                }
            });

            // Обработчик для дропдауна vendors
            const vendorSelects = document.querySelectorAll('.vendor-select');
            vendorSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const tdrProcessId = this.getAttribute('data-tdr-process-id');
                    const process = this.getAttribute('data-process');
                    const vendorId = this.value;
                    const vendorName = this.options[this.selectedIndex].text;

                    if (vendorId) {
                        console.log('Selected vendor:', {
                            tdrProcessId: tdrProcessId,
                            process: process,
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
                    const tdrProcessId = this.getAttribute('data-tdr-process-id');
                    const process = this.getAttribute('data-process');
                    const processNameId = this.getAttribute('data-process-name-id');

                    let vendorSelect = null;
                    if (tdrProcessId && process) {
                        vendorSelect = document.querySelector(`select[data-tdr-process-id="${tdrProcessId}"][data-process="${process}"]`);
                    }
                    if (!vendorSelect && processNameId) {
                        vendorSelect = document.querySelector(`select.vendor-select[data-process-name-id="${processNameId}"]`);
                    }

                    if (vendorSelect && vendorSelect.value) {
                        const currentUrl = new URL(this.href, window.location.origin);
                        currentUrl.searchParams.set('vendor_id', vendorSelect.value);
                        this.href = currentUrl.toString();
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

                fetch('{{ route("tdr-processes.update-order") }}', {
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

            // Обработчики для Group Process Forms модального окна
            const groupVendorSelects = document.querySelectorAll('#groupFormsModal .vendor-select');
            const groupFormLinks = document.querySelectorAll('.group-form-link');
            const groupFormButtons = document.querySelectorAll('#groupFormsModal .group-form-button');
            const groupComponentCheckboxes = document.querySelectorAll('#groupFormsModal .component-checkbox');

            // Функция для обновления URL с учетом vendor и компонентов
            function updateGroupLinkUrl(processNameId) {
                // Пробуем найти ссылку или кнопку
                let link = document.querySelector(`.group-form-link[data-process-name-id="${processNameId}"]`);
                if (!link) {
                    link = document.querySelector(`.group-form-button[data-process-name-id="${processNameId}"]`);
                }
                if (!link) return;

                const originalUrl = link.getAttribute('href');
                if (!originalUrl) return;

                const url = new URL(originalUrl, window.location.origin);

                // Добавляем vendor_id если выбран
                const vendorSelect = document.querySelector(`#groupFormsModal .vendor-select[data-process-name-id="${processNameId}"]`);
                if (vendorSelect && vendorSelect.value) {
                    url.searchParams.set('vendor_id', vendorSelect.value);
                } else {
                    url.searchParams.delete('vendor_id');
                }

                // Добавляем component_ids из выбранных чекбоксов
                const checkedBoxes = document.querySelectorAll(
                    `#groupFormsModal .component-checkbox[data-process-name-id="${processNameId}"]:checked`
                );
                if (checkedBoxes.length > 0) {
                    const selectedComponents = Array.from(checkedBoxes).map(checkbox => checkbox.value);
                    url.searchParams.set('component_ids', selectedComponents.join(','));
                } else {
                    url.searchParams.delete('component_ids');
                }

                link.setAttribute('href', url.toString());
            }

            // Функция для обновления badge с количеством
            function updateGroupQuantityBadge(processNameId) {
                const checkedBoxes = document.querySelectorAll(
                    `#groupFormsModal .component-checkbox[data-process-name-id="${processNameId}"]:checked:not([disabled])`
                );
                const badge = document.querySelector(
                    `#groupFormsModal .process-qty-badge[data-process-name-id="${processNameId}"]`
                );

                if (badge && checkedBoxes.length > 0) {
                    let totalQty = 0;
                    checkedBoxes.forEach(checkbox => {
                        const qty = parseInt(checkbox.getAttribute('data-qty')) || 0;
                        totalQty += qty;
                    });
                    badge.textContent = `${totalQty} pcs`;
                }
            }

            // Обработчик изменения выбора vendor для каждого дропдауна
            groupVendorSelects.forEach(vendorSelect => {
                vendorSelect.addEventListener('change', function() {
                    const processNameId = this.getAttribute('data-process-name-id');
                    updateGroupLinkUrl(processNameId);
                });
            });

            // Обработчик изменения чекбоксов компонентов
            groupComponentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const processNameId = this.getAttribute('data-process-name-id');
                    updateGroupLinkUrl(processNameId);
                    updateGroupQuantityBadge(processNameId);
                });
            });

            // Обработчик клика по кнопкам форм
            groupFormLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const processNameId = this.getAttribute('data-process-name-id');
                    updateGroupLinkUrl(processNameId);
                });
            });

            // Обработчик клика по paper-button кнопкам форм
            groupFormButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const processNameId = this.getAttribute('data-process-name-id');
                    if (processNameId) {
                        // Обновляем URL перед переходом
                        updateGroupLinkUrl(processNameId);
                        // Получаем обновленный URL и устанавливаем его
                        const updatedUrl = this.getAttribute('href');
                        if (updatedUrl) {
                            this.setAttribute('href', updatedUrl);
                        }
                    }
                });
            });

            // Инициализация URL и badge при загрузке страницы
            document.querySelectorAll('#groupFormsModal .group-form-link, #groupFormsModal .group-form-button').forEach(link => {
                const processNameId = link.getAttribute('data-process-name-id');
                if (processNameId) {
                    updateGroupLinkUrl(processNameId);
                    updateGroupQuantityBadge(processNameId);
                }
            });

        });
    </script>
@endsection
