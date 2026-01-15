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

        .parent {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(1, 1fr);
            gap: 8px;
        }

        /* Стили для чекбоксов в колонках */
        .vendor-checkbox-column,
        .at-checkbox-column {
            text-align: center;
        }

        .vendor-checkbox-column input[type="checkbox"],
        .at-checkbox-column input[type="checkbox"] {
            cursor: pointer;
        }

        /* Стили для дропдауна в заголовке */
        .vendor-header-dropdown {
            position: relative;
        }

        .vendor-header-dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
            min-width: 200px;
        }

        .vendor-header-dropdown-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }

        .vendor-header-dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .vendor-header-dropdown-item.active {
            background-color: #0d6efd;
            color: white;
        }

        .vendor-header-dropdown-item.active:hover {
            background-color: #0b5ed7;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <h4 class="text-primary mb-0">{{ __('Part Traveler') }}</h4>
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

                    <div class="d-flex ">
                        <div class="d-flex ">
                            <div class="" style="width: 250px">
                                <div class="d-flex ms-1 mt-1">
                                    <label for="repair_num" style="width: 100px; margin-top: 15px">Repair No.</label>
                                    <input type="text" style="height: 32px; width: 100px; margin-top: 15px" name="repair_num" id="repair_num"
                                           value=""
                                           class="form-control">
                                </div>
                            </div>
                            <div class="mt-2">
                                <x-paper-button
                                    text="Part Traveler"
                                    href="#"
                                    id="travelFormBtn"
                                    target="_blank"
                                    color="outline-primary"
                                    size="landscape"
                                />
                            </div>
                        </div>

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
                        <table class="display table table-sm table-hover align-middle table-bordered bg-gradient">
                            <thead>
                            <tr>
                                <th class="text-primary text-center">Process Name</th>
                                <th class="text-primary text-center" style="width: 450px;">Process</th>
                                <th class="text-primary text-center">Action</th>
                                <th class="text-primary text-center vendor-header-dropdown">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    type="button"
                                                    id="vendorHeaderDropdown"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                Select Vendor
                                            </button>
                                            <ul class="dropdown-menu vendor-header-dropdown-menu"
                                                aria-labelledby="vendorHeaderDropdown">
                                                @foreach($vendors as $vendor)
                                                    <li class="vendor-header-dropdown-item"
                                                        data-vendor-id="{{ $vendor->id }}"
                                                        data-vendor-name="{{ $vendor->name }}">
                                                        {{ $vendor->name }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <input type="checkbox"
                                                   id="unselectVendorCheckbox"
                                                   class="form-check-input">
                                            <label for="unselectVendorCheckbox"
                                                   class="form-check-label ms-1"
                                                   style="font-size: 0.875rem; cursor: pointer;">
                                                Un select
                                            </label>
                                        </div>
                                    </div>
                                </th>
                                <th class="text-primary text-center">AT</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tdrProcesses as $processes)
                                @if($processes->tdrs_id == $current_tdr->id)
                                    @php
                                        // Декодируем JSON-поле processes
                                        $processData = json_decode($processes->processes, true);
                                        // Проверяем, что $processData является массивом
                                        if (!is_array($processData)) {
                                            $processData = [];
                                        }
                                        // Получаем имя процесса из связанной модели ProcessName (с проверкой на null)
                                        $processName = $processes->processName ? $processes->processName->name : 'N/A';
                                    @endphp

                                    @if(!$processes->processName)
                                        @continue
                                    @endif

                                    @if(is_array($processData) && !empty($processData))
                                        @foreach($processData as $process)
                                        @if(strpos($processName, 'EC') === false)
                                        <tr data-id="{{ $processes->id }}">
                                            <td class="text-center">{{ $processName }}</td>
                                            <td class="ps-2">
                                                @foreach($proces as $proc)
                                                    @if($proc->id == $process)
                                                        {{ $proc->process }}@if($processes->ec) ( EC )@endif
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td class="text-center">

                                                <a href="{{ route('tdr-processes.edit', ['tdr_process' => $processes->id]) }}"
                                                   class="btn btn-outline-primary btn-sm me-2">
                                                    <i class="bi bi-pencil-square" title=" Process Edit"></i>
                                                </a>

                                                <form id="deleteForm_{{ $processes->id }}"
                                                      action="{{ route('tdr-processes.destroy', ['tdr_process' => $processes->id]) }}"
                                                      method="POST"
                                                      style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="tdrId" value="{{ $current_tdr->id }}">
                                                    <input type="hidden" name="process" value="{{ $process }}">
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm ">
                                                        <i class="bi bi-trash"  title=" Process Delete"></i>
                                                    </button>
{{--                                                    <button class="btn btn-sm btn-outline-danger"--}}
{{--                                                            type="button"--}}
{{--                                                            name="btn_delete"--}}
{{--                                                            data-bs-toggle="modal"--}}
{{--                                                            data-bs-target="#useConfirmDelete"--}}
{{--                                                            data-title="Delete Confirmation: {{ $processes->processName ? $processes->processName->name : 'N/A' }}">--}}
{{--                                                        {{__('Delete')}}--}}
{{--                                                    </button>--}}
                                                </form>
                                            </td>
                                            <td class="vendor-checkbox-column">
                                                <input type="checkbox"
                                                       class="vendor-row-checkbox"
                                                       data-tdr-process-id="{{ $processes->id }}"
                                                       data-process="{{ $process }}">
                                                <input type="hidden"
                                                       class="vendor-data-input"
                                                       data-tdr-process-id="{{ $processes->id }}"
                                                       data-process="{{ $process }}"
                                                       name="vendor_data[{{ $processes->id }}][{{ $process }}]"
                                                       value="">
                                            </td>
                                            <td class="at-checkbox-column">
                                                <input type="checkbox"
                                                       class="at-checkbox"
                                                       data-tdr-process-id="{{ $processes->id }}"
                                                       data-process="{{ $process }}"
                                                       value="1">
                                            </td>
                                        </tr>
                                        @endif
                                        @endforeach
                                    @endif
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteModal = document.getElementById('useConfirmDelete');
            const confirmDeleteButton = document.getElementById('confirmDeleteButton');
            let deleteForm = null;

            // Обработчик открытия модального окна
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const title = button.getAttribute('data-title');
                deleteForm = button.closest('form');

                deleteModal.querySelector('.modal-title').textContent = title;
            });

            // Обработчик нажатия на кнопку "Delete" в модальном окне
            confirmDeleteButton.addEventListener('click', function () {
                if (deleteForm) {
                    deleteForm.submit();
                }
            });

            // Обработчик для выбора vendor в заголовке и автоматического применения
            const vendorHeaderItems = document.querySelectorAll('.vendor-header-dropdown-item');
            const dropdownButton = document.getElementById('vendorHeaderDropdown');
            const vendorHeaderDropdown = document.getElementById('vendorHeaderDropdown');

            // Функция для применения vendor к отмеченным строкам
            function applyVendorToCheckedRows(vendorId, vendorName) {
                const checkedRowCheckboxes = Array.from(document.querySelectorAll('.vendor-row-checkbox')).filter(cb => cb.checked);

                if (checkedRowCheckboxes.length > 0) {
                    checkedRowCheckboxes.forEach((rowCheckbox, index) => {
                        const tdrProcessId = rowCheckbox.getAttribute('data-tdr-process-id');
                        const process = rowCheckbox.getAttribute('data-process');

                        const vendorDataInput = document.querySelector(
                            `.vendor-data-input[data-tdr-process-id="${tdrProcessId}"][data-process="${process}"]`
                        );

                        if (vendorDataInput) {
                            vendorDataInput.value = JSON.stringify({
                                vendor_id: vendorId,
                                vendor_name: vendorName
                            });

                            console.log(`Applied vendor to row ${index + 1}:`, {
                                tdrProcessId: tdrProcessId,
                                process: process,
                                vendorId: vendorId,
                                vendorName: vendorName
                            });
                        }
                    });
                }
            }

            vendorHeaderItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // Убираем активный класс со всех элементов
                    vendorHeaderItems.forEach(i => i.classList.remove('active'));

                    // Добавляем активный класс к выбранному элементу
                    this.classList.add('active');

                    // Получаем данные выбранного vendor
                    const vendorId = this.getAttribute('data-vendor-id');
                    const vendorName = this.getAttribute('data-vendor-name');

                    // Обновляем текст кнопки
                    dropdownButton.textContent = vendorName;

                    // Применяем выбранный vendor ко всем отмеченным строкам
                    applyVendorToCheckedRows(vendorId, vendorName);

                    // Закрываем дропдаун после выбора vendor
                    setTimeout(() => {
                        const dropdown = bootstrap.Dropdown.getInstance(vendorHeaderDropdown);
                        if (dropdown) {
                            dropdown.hide();
                        }
                    }, 100);
                });
            });

            // Обработчик для чекбокса "Un select"
            const unselectVendorCheckbox = document.getElementById('unselectVendorCheckbox');
            if (unselectVendorCheckbox) {
                unselectVendorCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        // Находим все отмеченные чекбоксы в строках
                        const checkedRowCheckboxes = Array.from(document.querySelectorAll('.vendor-row-checkbox')).filter(cb => cb.checked);

                        if (checkedRowCheckboxes.length > 0) {
                            // Очищаем данные vendor у всех отмеченных строк и снимаем отметки с чекбоксов
                            checkedRowCheckboxes.forEach(rowCheckbox => {
                                const tdrProcessId = rowCheckbox.getAttribute('data-tdr-process-id');
                                const process = rowCheckbox.getAttribute('data-process');

                                // Снимаем отметку с чекбокса
                                rowCheckbox.checked = false;

                                const vendorDataInput = document.querySelector(
                                    `.vendor-data-input[data-tdr-process-id="${tdrProcessId}"][data-process="${process}"]`
                                );

                                if (vendorDataInput) {
                                    vendorDataInput.value = '';
                                    console.log('Cleared vendor data and unchecked row:', {
                                        tdrProcessId: tdrProcessId,
                                        process: process
                                    });
                                }
                            });

                            // Снимаем активный класс с выбранного vendor
                            const activeVendorItem = document.querySelector('.vendor-header-dropdown-item.active');
                            if (activeVendorItem) {
                                activeVendorItem.classList.remove('active');
                            }

                            // Сбрасываем текст кнопки дропдауна
                            dropdownButton.textContent = 'Select Vendor';

                            // Автоматически снимаем отметку с чекбокса "Un select"
                            this.checked = false;

                            console.log(`Cleared vendor data and unchecked ${checkedRowCheckboxes.length} rows`);
                        }
                    }
                });
            }

            // Обработчик для чекбоксов vendor - применяет выбранный vendor при отметке
            const vendorRowCheckboxes = document.querySelectorAll('.vendor-row-checkbox');
            vendorRowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Если чекбокс отмечен, проверяем есть ли выбранный vendor
                    if (this.checked) {
                        const activeVendorItem = document.querySelector('.vendor-header-dropdown-item.active');

                        if (activeVendorItem) {
                            const vendorId = activeVendorItem.getAttribute('data-vendor-id');
                            const vendorName = activeVendorItem.getAttribute('data-vendor-name');
                            const tdrProcessId = this.getAttribute('data-tdr-process-id');
                            const process = this.getAttribute('data-process');

                            // Сохраняем vendor в скрытое поле для передачи в travelForm
                            const vendorDataInput = document.querySelector(
                                `.vendor-data-input[data-tdr-process-id="${tdrProcessId}"][data-process="${process}"]`
                            );

                            if (vendorDataInput) {
                                // Сохраняем данные vendor в формате JSON
                                vendorDataInput.value = JSON.stringify({
                                    vendor_id: vendorId,
                                    vendor_name: vendorName
                                });

                                console.log('Applied vendor to newly checked row:', {
                                    tdrProcessId: tdrProcessId,
                                    process: process,
                                    vendorId: vendorId,
                                    vendorName: vendorName
                                });
                            }
                        }
                    } else {
                        // Если чекбокс снят, очищаем данные vendor для этой строки
                        const tdrProcessId = this.getAttribute('data-tdr-process-id');
                        const process = this.getAttribute('data-process');

                        const vendorDataInput = document.querySelector(
                            `.vendor-data-input[data-tdr-process-id="${tdrProcessId}"][data-process="${process}"]`
                        );

                        if (vendorDataInput) {
                            vendorDataInput.value = '';
                            console.log('Cleared vendor data for unchecked row:', {
                                tdrProcessId: tdrProcessId,
                                process: process
                            });
                        }
                    }
                });
            });

            // Обработчик для чекбоксов AT
            const atCheckboxes = document.querySelectorAll('.at-checkbox');
            atCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const tdrProcessId = this.getAttribute('data-tdr-process-id');
                    const process = this.getAttribute('data-process');
                    const isChecked = this.checked;

                    console.log('AT checkbox changed:', {
                        tdrProcessId: tdrProcessId,
                        process: process,
                        isChecked: isChecked
                    });
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
                        // Добавляем новый vendor в дропдаун заголовка
                        const vendorHeaderMenu = document.querySelector('.vendor-header-dropdown-menu');
                        if (vendorHeaderMenu) {
                            const newItem = document.createElement('li');
                            newItem.className = 'vendor-header-dropdown-item';
                            newItem.setAttribute('data-vendor-id', data.vendor.id);
                            newItem.setAttribute('data-vendor-name', data.vendor.name);
                            newItem.textContent = data.vendor.name;

                            // Добавляем в конец списка
                            vendorHeaderMenu.appendChild(newItem);

                            // Добавляем обработчик для нового элемента (такой же, как для существующих)
                            newItem.addEventListener('click', function(e) {
                                e.stopPropagation();

                                // Убираем активный класс со всех элементов
                                document.querySelectorAll('.vendor-header-dropdown-item').forEach(i => i.classList.remove('active'));

                                // Добавляем активный класс к выбранному элементу
                                this.classList.add('active');

                                // Получаем данные выбранного vendor
                                const vendorId = this.getAttribute('data-vendor-id');
                                const vendorName = this.getAttribute('data-vendor-name');

                                // Обновляем текст кнопки
                                dropdownButton.textContent = vendorName;

                                // Применяем выбранный vendor ко всем отмеченным строкам
                                applyVendorToCheckedRows(vendorId, vendorName);
                            });
                        }

                        const modal = bootstrap.Modal.getInstance(document.getElementById('addVendorModal'));
                        modal.hide();

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

            // Обработчик клика на кнопку Part Traveler
            document.getElementById('travelFormBtn')?.addEventListener('click', function(e) {
                e.preventDefault();

                // Проверяем, выбран ли vendor
                const activeVendorItem = document.querySelector('.vendor-header-dropdown-item.active');
                if (!activeVendorItem) {
                    alert('Please select a vendor first');
                    return;
                }

                const repairNum = document.getElementById('repair_num')?.value || '';
                const baseUrl = '{{ route("tdr-processes.travelForm", ["id" => $current_tdr->id]) }}';
                const params = new URLSearchParams();

                if (repairNum) {
                    params.append('repair_num', repairNum);
                }

                // Собираем все данные о выбранных vendor
                const vendorDataInputs = document.querySelectorAll('.vendor-data-input');
                const vendorsData = {};
                let vendorDataCount = 0;

                vendorDataInputs.forEach(input => {
                    if (input.value) {
                        try {
                            const vendorData = JSON.parse(input.value);
                            const tdrProcessId = input.getAttribute('data-tdr-process-id');
                            const process = input.getAttribute('data-process');

                            if (!vendorsData[tdrProcessId]) {
                                vendorsData[tdrProcessId] = {};
                            }
                            vendorsData[tdrProcessId][process] = vendorData;
                            vendorDataCount++;

                            console.log('Collected vendor data:', {
                                tdrProcessId: tdrProcessId,
                                process: process,
                                vendorData: vendorData
                            });
                        } catch (e) {
                            console.error('Error parsing vendor data:', e);
                        }
                    }
                });

                console.log(`Total vendor data collected: ${vendorDataCount} rows`);

                // Собираем данные о отмеченных чекбоксах AT
                const atCheckboxes = document.querySelectorAll('.at-checkbox');
                const atData = {};
                let atDataCount = 0;

                atCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const tdrProcessId = checkbox.getAttribute('data-tdr-process-id');
                        const process = checkbox.getAttribute('data-process');

                        if (!atData[tdrProcessId]) {
                            atData[tdrProcessId] = [];
                        }
                        atData[tdrProcessId].push(process);
                        atDataCount++;

                        console.log('Collected AT checkbox data:', {
                            tdrProcessId: tdrProcessId,
                            process: process
                        });
                    }
                });

                console.log(`Total AT checkboxes collected: ${atDataCount} rows`);

                // Передаем данные о vendor через параметр
                if (Object.keys(vendorsData).length > 0) {
                    params.append('vendors_data', JSON.stringify(vendorsData));
                    console.log('Vendors data to send:', vendorsData);
                } else {
                    console.log('No vendor data to send');
                }

                // Передаем данные о AT чекбоксах через параметр
                if (Object.keys(atData).length > 0) {
                    params.append('at_data', JSON.stringify(atData));
                    console.log('AT data to send:', atData);
                } else {
                    console.log('No AT data to send');
                }

                const url = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
                window.open(url, '_blank');
            });
        });
    </script>
@endsection

