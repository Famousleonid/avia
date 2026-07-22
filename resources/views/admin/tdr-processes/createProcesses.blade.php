@extends(request()->query('modal') ? 'admin.master-embed' : 'admin.master')

@section('content')

    <style>
        .container {
            max-width: 1080px;
        }

        /* Стили для Select2 (темная и светлая темы) */
        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: var(--avia-input) !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid var(--avia-border) !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: var(--avia-surface-raised) !important;
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
            background-color: var(--avia-input) !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
            background-color: #6ea8fe;
            color: #000000;
        }

        /* Увеличенный размер для дополнительного NDT селекта */
        .select2-ndt-plus + .select2-container .select2-selection--multiple {
            min-height: 70px !important;
            padding: 12px !important;
            font-size: 16px !important;
            line-height: 1.5 !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-ndt-plus + .select2-container .select2-selection__rendered {
            padding: 8px 12px !important;
            min-height: 60px !important;
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: flex-end !important;
            align-items: center !important;
            flex-grow: 1 !important;
        }

        .select2-ndt-plus + .select2-container .select2-selection__choice {
            margin: 6px 6px 6px 0 !important;
            padding: 8px 12px !important;
            font-size: 15px !important;
            line-height: 1.4 !important;
        }

        .select2-ndt-plus + .select2-container .select2-search--inline {
            order: -1 !important;
            flex-grow: 0 !important;
            margin-right: auto !important;
        }

        .select2-ndt-plus + .select2-container .select2-search--inline .select2-search__field {
            padding: 8px 12px !important;
            font-size: 16px !important;
            min-height: 40px !important;
            width: auto !important;
            min-width: 200px !important;
        }

        .select2-container .select2-selection__clear {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 1;
        }

        .card-body {
            max-height: 80vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Стили для скроллбара в card-body */
        .card-body::-webkit-scrollbar {
            width: 8px;
        }

        .card-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .card-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .card-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Стили для скроллбара в темной теме */
        html[data-bs-theme="dark"] .card-body::-webkit-scrollbar-track {
            background: var(--avia-surface-raised);
        }

        html[data-bs-theme="dark"] .card-body::-webkit-scrollbar-thumb {
            background: #555;
        }

        html[data-bs-theme="dark"] .card-body::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center position-relative">
                    <div>
                        <h4 class="text-primary mb-0">{{ __('Add Part Processes') }}</h4>
                        <div class="mt-2">
                            {{ $current_tdr->component->name }}
                            <div>
                                PN: {{ $current_tdr->component->part_number }}
                                SN: {{ $current_tdr->serial_number }}
                            </div>
                        </div>
                    </div>
                    <div class="position-absolute start-50 translate-middle-x d-flex gap-2">
                        <button class="btn btn-outline-success" type="button" style="width: 120px" id="add-process">
                            Add Process
                        </button>
                        @if(!empty($ecProcessNameId))
                        <button class="btn btn-outline-secondary" type="button" id="add-ec-only" title="{{ __('EC only — separate line in Special Process Form') }}">
                            {{ __('EC only') }}
                        </button>
                        @endif
                    </div>
                    <div class="align-items-center">
                        <h4 class="pe-3 mb-3">{{ __('W') }}{{ $current_tdr->workorder->number }}</h4>
                        <div>
                            <button type="submit" form="createCPForm" class="btn btn-outline-primary">{{ __('Save') }}</button>
                            @if(request()->query('modal'))
                                <button type="button" class="btn btn-outline-secondary" id="createProcessCancelBtn">{{ __('Cancel') }}</button>
                            @else
                                <a href="{{ route('tdr-processes.processes', ['tdrId' => $current_tdr->id]) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                            @endif
                        </div>
                        </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tdr-processes.store', $current_tdr->id) }}" enctype="multipart/form-data" id="createCPForm">
                    @csrf
                    <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">

                    <!-- Контейнер для строк с процессами -->
                    <div id="processes-container" data-manual-id="{{ $manual_id }}">
                        <!-- Начальная строка -->
                        <div class="process-row mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process" required
                                            data-process-data='@json($processNames->keyBy('id'))'>
                                        <option value=""></option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}"
                                                    data-process-id="{{ $processName->id }}"
                                                    {{-- Примеры использования id в Blade @if --}}
                                                    @if($processName->id == 1)
                                                        data-is-special="true"
                                                    @elseif($processName->id >= 5 && $processName->id <= 10)
                                                        data-is-range="true"
                                                    @endif
                                                    @if(in_array($processName->id, [2, 3, 4]))
                                                        data-is-group="true"
                                                    @endif>
                                                {{ $processName->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-5">
                                    <label for="process">Processes (Specification):</label>
                                    <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal"
                                            data-bs-target="#addProcessModal">
                                        <img src="{{ asset('img/plus.png')}}" alt="arrow"
                                             style="width: 20px;" class="" >
                                    </button>
                                    <div class="process-options">
                                        <!-- Здесь будут чекбоксы для выбранного имени процесса -->
                                    </div>

                                    <!-- Дополнительный селект для NDT процессов (скрыт по умолчанию) -->
                                    <div class="ndt-plus-process-container mt-3" style="display: none; visibility: visible;">
                                        <label for="ndt_plus_process_0">Additional NDT Process(es):</label>
                                        <select name="processes[0][ndt_plus_process][]"
                                                class="form-control select2-ndt-plus"
                                                id="ndt_plus_process_0"
                                                data-row-index="0"
                                                multiple
                                                style="width: 100%; min-height: 70px; padding: 12px; font-size: 16px;">
                                            @foreach ($ndtProcessNames as $ndtProcessName)
                                                <option value="{{ $ndtProcessName->id }}"
                                                        data-process-name="{{ $ndtProcessName->name }}">
                                                    {{ $ndtProcessName->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="ndt-plus-process-options mt-2">
                                            <!-- Здесь будут чекбоксы для дополнительных NDT процессов -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
{{--                                    <label for="ec">EC:</label>--}}
{{--                                    <div class="mb-2">--}}
{{--                                        <small class="text-muted">Process ID: <span id="process-name-id-0" class="fw-bold">-</span></small>--}}
{{--                                    </div>--}}

                                    <div class="form-check mt-2 standalone-ec-only-wrap" style="display: none;">
                                        <input type="checkbox" name="processes[0][standalone_ec_only]" value="1" class="form-check-input" id="standalone_ec_0">
                                        <label class="form-check-label" for="standalone_ec_0">{{ __('EC only (separate line in SP Form)') }}</label>
                                    </div>
                                    <div class="form-check mt-2 ec-machining-wrap" style="display: none;">
                                        <input type="checkbox" name="processes[0][ec]" value="1" class="form-check-input" id="ec_0">
                                        <label class="form-check-label" for="ec_0">EC</label>
                                    </div>
                                    <div>
                                        <label for="description" class="form-label" style="margin-bottom:
                                        -5px">Description</label>
                                        <input type="text" class="form-control" id="description_0"
                                               name="processes[0][description]" placeholder="CMM fig.___ pg. ___" >

                                        <label for="notes" class="form-label" style="margin-bottom: -5px">Notes</label>
                                        <input type="text" class="form-control" id="notes" name="processes[0][notes]"
                                               placeholder="Enter Notes">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="modal fade" id="descriptionRequirementModal" tabindex="-1" aria-labelledby="descriptionRequirementModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="descriptionRequirementModalLabel">FIG / ZONE required</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-2">The Description field is missing required information:</p>
                                <p class="mb-0 text-danger" id="descriptionRequirementModalMessage"></p>
                                <p class="mt-3 mb-0">Slava S will not accept the printed form without it.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" id="descriptionRequirementReturn">Return and fill</button>
                                <button type="button" class="btn btn-outline-danger" id="descriptionRequirementSaveIncomplete">Save without FIG / ZONE</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Модальное окно для добавления процесса -->
            <div class="modal fade" id="addProcessModal" tabindex="-1" aria-labelledby="addProcessModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <!-- Заголовок модального окна -->
                        <div class="modal-header">
                            <h5 class="modal-title" id="addProcessModalLabel">
                                Enter Process (<span id="modalProcessName"></span>)
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <!-- Тело модального окна -->
                        <div class="modal-body">
                            <!-- Поле для ввода нового процесса -->

                            <div class="mb-3">
                                <label for="newProcessInput" class="form-label">New Process</label>
                                <input type="text" class="form-control" id="newProcessInput" placeholder="Enter new process">
                                <div id="processCreateRestriction" class="small text-info mt-2 d-none"></div>
                            </div>
                            <!-- Скрытое поле для хранения выбранного process_name_id -->
                            <input type="hidden" id="modalProcessNameId">
                        </div>
                        <!-- Футер модального окна -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveProcessModal">Save Process</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        var CREATE_PROCESS_IN_MODAL = window.location.search.indexOf('modal=1') >= 0;
        if (CREATE_PROCESS_IN_MODAL) {
            document.addEventListener('DOMContentLoaded', function() {
                var cancelBtn = document.getElementById('createProcessCancelBtn');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function() {
                        if (window.parent !== window) {
                            window.parent.postMessage({ type: 'createProcessCancel' }, '*');
                        }
                    });
                }
            });
        }
        // Универсальная функция для получения id выбранного Process Name
        // Параметр: element - элемент select или строка process-row
        function getSelectedProcessNameId(element) {
            let select;

            // Если передан select элемент напрямую
            if (element.classList && element.classList.contains('select2-process')) {
                select = element;
            }
            // Если передан элемент строки (process-row)
            else if (element.classList && element.classList.contains('process-row')) {
                select = element.querySelector('.select2-process');
            }
            // Если передан event объект
            else if (element.target) {
                select = element.target;
            }

            if (select) {
                return select.value; // Возвращает id выбранного Process Name
            }

            return null;
        }

        // Данные процессов для использования в динамически создаваемых строках
        const processNamesData = @json($processNames->keyBy('id'));

        function isMachiningEcProcessName(processNameId) {
            if (processNameId === undefined || processNameId === null || processNameId === '') {
                return false;
            }
            const p = processNamesData[String(processNameId)] || processNamesData[processNameId];
            return p && p.name === 'Machining (EC)';
        }

        // Получаем все NDT process_names_id для проверки
        const ndtProcessNames = @json($ndtProcessNames->pluck('id')->toArray());
        const ndtProcessNamesData = @json($ndtProcessNames->keyBy('id'));

        // Функция для проверки, является ли процесс NDT
        function isNdtProcess(processNameId) {
            return ndtProcessNames.includes(parseInt(processNameId));
        }

        // ID процессов с EC checkbox: Machining (EC)/Machining/Machining (Blend), RIL
        const ecEligibleProcessNameIds = @json($ecEligibleProcessNameIds ?? []);
        const ecProcessNameId = @json($ecProcessNameId ?? null);

        function isEcEligibleProcess(processNameId) {
            return ecEligibleProcessNameIds.includes(parseInt(processNameId));
        }

        /** Имя EC: «только EC» всегда on (чекбокс скрыт). Machining/RIL: отдельный чекбокс EC. Machining (EC): EC скрыт, всегда on. */
        function updateEcCheckboxesForProcessRow(processRow, processNameId) {
            if (!processRow || processNameId === undefined || processNameId === null || processNameId === '') {
                return;
            }
            const pid = String(processNameId);
            const isEcName = ecProcessNameId && parseInt(pid, 10) === parseInt(String(ecProcessNameId), 10);
            const standaloneWrap = processRow.querySelector('.standalone-ec-only-wrap');
            if (standaloneWrap) {
                const c = standaloneWrap.querySelector('input[type="checkbox"]');
                if (isEcName) {
                    // «Только EC» (отдельная строка) для имени EC всегда on — чекбокс не показываем, лишний шаг
                    if (c) c.checked = true;
                    standaloneWrap.style.display = 'none';
                } else {
                    standaloneWrap.style.display = 'none';
                    if (c) c.checked = false;
                }
            }
            const ecMachiningWrap = processRow.querySelector('.ec-machining-wrap');
            if (ecMachiningWrap) {
                const ecInput = ecMachiningWrap.querySelector('input.form-check-input');
                if (isEcEligibleProcess(pid)) {
                    if (isMachiningEcProcessName(pid)) {
                        ecMachiningWrap.style.display = 'none';
                        if (ecInput) {
                            ecInput.checked = true;
                        }
                    } else {
                        ecMachiningWrap.style.display = 'block';
                    }
                } else {
                    ecMachiningWrap.style.display = 'none';
                    if (ecInput) ecInput.checked = false;
                }
            }
        }

        // Строка «только EC» (отдельная нумерация в SP Form)
        const addEcOnlyBtn = document.getElementById('add-ec-only');
        if (addEcOnlyBtn && ecProcessNameId) {
            addEcOnlyBtn.addEventListener('click', function () {
                const container = document.getElementById('processes-container');
                const index = container.children.length;
                const newRow = document.createElement('div');
                newRow.classList.add('process-row', 'mb-3');
                newRow.setAttribute('data-standalone-ec', '1');
                newRow.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <label>Process Name:</label>
                        <div class="form-control bg-light">EC <span class="text-muted small">({{ __('EC only') }})</span></div>
                        <select name="processes[${index}][process_names_id]" class="form-control select2-process" required style="display:none" aria-hidden="true">
                            <option value="${ecProcessNameId}" selected>EC</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label>Processes (Specification):</label>
                        <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal" data-bs-target="#addProcessModal">
                            <img src="{{ asset('img/plus.png')}}" alt="+" style="width: 20px;">
                        </button>
                        <div class="process-options"></div>
                        <div class="ndt-plus-process-container mt-3" style="display: none; visibility: visible;">
                            <label>Additional NDT Process(es):</label>
                            <select name="processes[${index}][ndt_plus_process][]" class="form-control select2-ndt-plus" data-row-index="${index}" multiple style="width: 100%; min-height: 70px;">
                                @foreach ($ndtProcessNames as $ndtProcessName)
                                <option value="{{ $ndtProcessName->id }}">{{ $ndtProcessName->name }}</option>
                                @endforeach
                            </select>
                            <div class="ndt-plus-process-options mt-2"></div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-2" style="display: none;"><input type="checkbox" class="form-check-input" disabled></div>
                    </div>
                    <div class="col-md-2">
                        <label>Description</label>
                        <input type="text" class="form-control" name="processes[${index}][description]" placeholder="CMM fig.___">
                        <label class="mt-1">Notes</label>
                        <input type="text" class="form-control" name="processes[${index}][notes]" placeholder="Notes">
                    </div>
                </div>`;
                container.appendChild(newRow);
                const sel = newRow.querySelector('.select2-process');
                if (sel) {
                    loadProcessesForRow(sel);
                }
                newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        }

        // Динамическое добавление новых строк
        document.getElementById('add-process').addEventListener('click', function () {
            const container = document.getElementById('processes-container');
            const index = container.children.length;

            // Формируем опции для select (пустая опция для placeholder, скрыта в dropdown)
            let optionsHtml = '<option value=""></option>';
            @foreach ($processNames as $processName)
                optionsHtml += `<option value="{{ $processName->id }}"
                    data-process-id="{{ $processName->id }}"
                    @if($processName->id == 1)
                        data-is-special="true"
                    @elseif($processName->id >= 5 && $processName->id <= 10)
                        data-is-range="true"
                    @endif
                    @if(in_array($processName->id, [2, 3, 4]))
                        data-is-group="true"
                    @endif>{{ $processName->name }}</option>`;
            @endforeach

            const newRow = document.createElement('div');
            newRow.classList.add('process-row', 'mb-3');
            newRow.innerHTML = `
                <div class="row ">
                    <div class="col-md-3">
                        <label for="process_names">Process Name:</label>
                        <select name="processes[${index}][process_names_id]"
                                class="form-control select2-process"
                                required
                                data-process-data='@json($processNames->keyBy('id'))'>
                            ${optionsHtml}
                        </select>
        </div>
        <div class="col-md-5">
            <label for="process">Processes:</label>

             <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal"
                                            data-bs-target="#addProcessModal">
                                        <img src="{{ asset('img/plus.png')}}" alt="arrow"
                                             style="width: 20px;" class="" >
                                    </button>
            <div class="process-options">
                <!-- Здесь будут чекбоксы для выбранного имени процесса -->
            </div>

            <!-- Дополнительный селект для NDT процессов (скрыт по умолчанию) -->
            <div class="ndt-plus-process-container mt-3" style="display: none; visibility: visible;">
                <label for="ndt_plus_process_${index}">Additional NDT Process(es):</label>
                <select name="processes[${index}][ndt_plus_process][]"
                        class="form-control select2-ndt-plus"
                        id="ndt_plus_process_${index}"
                        data-row-index="${index}"
                        multiple
                        style="width: 100%; min-height: 70px; padding: 12px; font-size: 16px;">
                    @foreach ($ndtProcessNames as $ndtProcessName)
                        <option value="{{ $ndtProcessName->id }}"
                                data-process-name="{{ $ndtProcessName->name }}">
                            {{ $ndtProcessName->name }}
                        </option>
                    @endforeach
                </select>
                <div class="ndt-plus-process-options mt-2">
                    <!-- Здесь будут чекбоксы для дополнительных NDT процессов -->
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-check mt-2 standalone-ec-only-wrap" style="display: none;">
                <input type="checkbox" name="processes[${index}][standalone_ec_only]" value="1" class="form-check-input" id="standalone_ec_${index}">
                <label class="form-check-label" for="standalone_ec_${index}">{{ __('EC only (separate line in SP Form)') }}</label>
            </div>
            <div class="form-check mt-2 ec-machining-wrap" style="display: none;">
                <input type="checkbox" name="processes[${index}][ec]" value="1" class="form-check-input" id="ec_${index}">
                <label class="form-check-label" for="ec_${index}">EC</label>
            </div>
            <div>
                <label for="description_${index}" class="form-label" style="margin-bottom: -5px">Description</label>
                <input type="text" class="form-control" id="description_${index}" name="processes[${index}][description]" placeholder="CMM fig.___ pg. ___">
                <label for="notes_${index}" class="form-label" style="margin-bottom: -5px">Notes</label>
                <input type="text" class="form-control" id="notes_${index}" name="processes[${index}][notes]" placeholder="Enter Notes">
            </div>
        </div>
    </div>`;

            container.appendChild(newRow);

            // Инициализируем Select2 для нового select элемента
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $(newRow).find('.select2-process').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '{{ __("Select Process Name") }}',
                    allowClear: false,
                    templateResult: function(data) { if (!data.id) return null; return data.text; }
                }).on('select2:select', function (e) {
                    // Обработчик события Select2 для загрузки процессов
                    const selectElement = e.target;
                    const processNameId = selectElement.value;
                    const processRow = selectElement.closest('.process-row');

                    updateEcCheckboxesForProcessRow(processRow, processNameId);

                    loadProcessesForRow(selectElement);

                    // Если это NDT процесс, обновляем опции дополнительного селекта (исключаем выбранный)
                    if (isNdtProcess(processNameId)) {
                        const ndtPlusSelect = processRow.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            // Удаляем опцию с выбранным NDT процессом
                            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                            $(ndtPlusSelect).trigger('change');
                        }
                    }
                });

                // Инициализируем Select2 для дополнительного селекта NDT (множественный выбор)
                $(newRow).find('.select2-ndt-plus').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    multiple: true,
                    placeholder: 'Select Additional NDT Process(es)'
                });

                // Исключаем уже выбранный NDT процесс из дополнительного селекта при инициализации
                const processNameSelect = newRow.querySelector('.select2-process');
                if (processNameSelect && processNameSelect.value) {
                    const processNameId = processNameSelect.value;
                    if (isNdtProcess(processNameId)) {
                        const ndtPlusSelect = newRow.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                            $(ndtPlusSelect).trigger('change');
                        }
                    }
                }

                // Прокрутка к новой группе и фокус на Process Name
                newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                setTimeout(function() {
                    $(newRow).find('.select2-process').select2('open');
                }, 100);
            }
        });



        // Обработка отправки формы
        function confirmIncompleteDescriptionRequirements(message) {
            const modalElement = document.getElementById('descriptionRequirementModal');
            const messageElement = document.getElementById('descriptionRequirementModalMessage');
            const returnButton = document.getElementById('descriptionRequirementReturn');
            const saveIncompleteButton = document.getElementById('descriptionRequirementSaveIncomplete');

            if (!modalElement || !messageElement || !returnButton || !saveIncompleteButton || !window.bootstrap?.Modal) {
                return Promise.resolve(window.confirm(
                    'Description is missing required ' + message + '.\n\n' +
                    'Slava S will not accept the printed form without this information. Save anyway?'
                ));
            }

            messageElement.textContent = message;

            return new Promise(function (resolve) {
                const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
                let settled = false;

                const finish = function (saveIncomplete) {
                    if (settled) return;
                    settled = true;
                    returnButton.removeEventListener('click', returnToDescription);
                    saveIncompleteButton.removeEventListener('click', saveWithoutDescription);
                    modalElement.removeEventListener('hidden.bs.modal', cancelOnClose);
                    modal.hide();
                    resolve(saveIncomplete);
                };
                const returnToDescription = function () { finish(false); };
                const saveWithoutDescription = function () { finish(true); };
                const cancelOnClose = function () { finish(false); };

                returnButton.addEventListener('click', returnToDescription);
                saveIncompleteButton.addEventListener('click', saveWithoutDescription);
                modalElement.addEventListener('hidden.bs.modal', cancelOnClose);
                modal.show();
            });
        }

        document.getElementById('createCPForm').addEventListener('submit', async function (event) {
            event.preventDefault();

            const tdrId = document.querySelector('input[name="tdrs_id"]').value;
            const processRows = document.querySelectorAll('.process-row');
            const processesData = [];
            const incompleteDescriptions = [];
            let hasCheckedCheckbox = false;

            processRows.forEach(row => {
                const isEcOnlyRow = row.getAttribute('data-standalone-ec') === '1';
                const processNameSelect = row.querySelector('.select2-process');
                let processNameId;
                if (isEcOnlyRow && processNameSelect) {
                    processNameId = (typeof $ !== 'undefined' && $(processNameSelect).hasClass('select2-hidden-accessible'))
                        ? $(processNameSelect).val() : processNameSelect.value;
                } else {
                    processNameId = (typeof $ !== 'undefined' && processNameSelect && $(processNameSelect).hasClass('select2-hidden-accessible'))
                    ? $(processNameSelect).val() : processNameSelect?.value;
                }
                if (!processNameId) return; // Пропускаем строки без выбранного Process Name
                const processName = processNameSelect?.options[processNameSelect.selectedIndex]?.text || '';

                const checkedRadio = row.querySelector('.process-options input[type="radio"]:checked');
                const selectedProcessIds = [];
                if (checkedRadio) {
                    selectedProcessIds.push(parseInt(checkedRadio.value));
                    hasCheckedCheckbox = true;
                }

                // Собираем данные о дополнительных NDT процессах (множественный выбор)
                const ndtPlusSelect = row.querySelector('.select2-ndt-plus');
                const ndtPlusProcessNameIds = [];
                const ndtPlusProcessIds = [];

                if (ndtPlusSelect) {
                    // Получаем все выбранные дополнительные NDT process_names_id из селекта
                    const selectedNdtPlusProcessNameIds = Array.from(ndtPlusSelect.selectedOptions).map(opt => opt.value);

                    // Получаем выбранные процессы для всех дополнительных NDT
                    const ndtPlusCheckboxes = row.querySelectorAll('.ndt-plus-process-checkbox:checked');
                    ndtPlusCheckboxes.forEach(checkbox => {
                        const ndtProcessNameId = checkbox.getAttribute('data-ndt-process-name-id');
                        // Проверяем, что это процессы для одного из выбранных дополнительных NDT
                        if (selectedNdtPlusProcessNameIds.includes(ndtProcessNameId)) {
                            ndtPlusProcessIds.push(parseInt(checkbox.value));
                        }
                    });

                    // Добавляем все выбранные process_names_id дополнительных NDT
                    selectedNdtPlusProcessNameIds.forEach(id => {
                        if (id && !ndtPlusProcessNameIds.includes(id)) {
                            ndtPlusProcessNameIds.push(id);
                        }
                    });
                }

                // Machining/RIL: чекбокс EC; «Machining (EC)» — EC всегда без чекбокса; «только EC» — из селекта или кнопки
                const standaloneCb = row.querySelector('input[name*="standalone_ec_only"]');
                const wantStandaloneEc = !isEcOnlyRow && standaloneCb && standaloneCb.checked
                    && ecProcessNameId && parseInt(String(processNameId), 10) === parseInt(String(ecProcessNameId), 10);
                const machEcCb = row.querySelector('.ec-machining-wrap input.form-check-input');
                let ecValue;
                if (isEcOnlyRow || wantStandaloneEc) {
                    ecValue = true;
                } else if (isMachiningEcProcessName(processNameId)) {
                    ecValue = true;
                } else if (machEcCb) {
                    ecValue = machEcCb.checked;
                } else {
                    ecValue = false;
                }

                // Получаем значение description
                const descriptionInput = row.querySelector('input[name*="[description]"]');
                const descriptionValue = descriptionInput ? descriptionInput.value.trim() : null;

                // Получаем значение notes
                const notesInput = row.querySelector('input[name*="[notes]"]');
                const notesValue = notesInput ? notesInput.value.trim() : null;

                const selectedSpecificationInputs = Array.from(row.querySelectorAll(
                    '.process-options input[type="radio"]:checked, .ndt-plus-process-checkbox:checked'
                ));
                const missing = [];
                if (selectedSpecificationInputs.some(input => input.dataset.requiresFig === '1') && !/FIG/i.test(descriptionValue || '')) {
                    missing.push('FIG');
                }
                if (selectedSpecificationInputs.some(input => input.dataset.requiresZone === '1') && !/ZONE/i.test(descriptionValue || '')) {
                    missing.push('ZONE');
                }
                if (missing.length > 0) {
                    incompleteDescriptions.push(`${processName || 'Process'}: ${missing.join(', ')}`);
                }

                if (selectedProcessIds.length > 0) {
                    // Объединяем все процессы (основные + дополнительные NDT)
                    const allProcessIds = [...selectedProcessIds, ...ndtPlusProcessIds];

                    // Формируем строку plus_process из всех выбранных дополнительных NDT process_names_id
                    const plusProcessString = ndtPlusProcessNameIds.length > 0
                        ? ndtPlusProcessNameIds.sort((a, b) => parseInt(a) - parseInt(b)).join(',')
                        : null;

                    const rowPayload = {
                        process_names_id: processNameId,
                        plus_process: plusProcessString, // Дополнительные NDT process_names_id через запятую (отсортированные)
                        processes: allProcessIds, // Объединенный массив ID процессов
                        ec: ecValue, // Добавляем значение EC
                        description: descriptionValue || null, // Добавляем значение description
                        notes: notesValue || null // Добавляем значение notes
                    };
                    if (isEcOnlyRow || wantStandaloneEc) {
                        rowPayload.standalone_ec_only = true;
                    }
                    processesData.push(rowPayload);
                }
            });

            if (!hasCheckedCheckbox) {
                showNotification('{{ __("Process not added because no specification is selected.") }}', 'warning');
                return; // Прерываем выполнение
            }

            if (incompleteDescriptions.length > 0
                && !await confirmIncompleteDescriptionRequirements(incompleteDescriptions.join('; '))) {
                return;
            }

            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            formData.append('tdrs_id', tdrId);
            formData.append('processes', JSON.stringify(processesData));

            const saveProcesses = function (confirmIncompleteRequirements) {
                return fetch(`{{ route('tdr-processes.store') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tdrs_id: tdrId,
                        processes: processesData,
                        confirm_incomplete_requirements: confirmIncompleteRequirements
                    })
                });
            };

            saveProcesses(incompleteDescriptions.length > 0)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            if (response.status === 422 && Array.isArray(err.incomplete_requirements)) {
                                const missing = err.incomplete_requirements
                                    .map(item => `Process row ${item.row}: ${item.missing.join(', ')}`)
                                    .join('; ');

                                return confirmIncompleteDescriptionRequirements(missing)
                                    .then(function (saveIncomplete) {
                                        if (!saveIncomplete) return null;

                                        return saveProcesses(true).then(function (retryResponse) {
                                            if (!retryResponse.ok) {
                                                return retryResponse.json().then(function (retryError) {
                                                    throw new Error(retryError.error || retryError.message || 'Network response was not ok');
                                                });
                                            }

                                            return retryResponse.json();
                                        });
                                    });
                            }

                            throw new Error(err.error || err.message || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data) return;
                    if (data.message) {
                        showNotification(data.message, 'success');
                    }
                    var tdrId = document.querySelector('input[name="tdrs_id"]')?.value;
                    if (CREATE_PROCESS_IN_MODAL && window.parent !== window && tdrId) {
                        window.parent.postMessage({
                            type: 'createProcessSuccess',
                            tdrId: tdrId,
                            message: data.message || '{{ __("Process added successfully.") }}'
                        }, '*');
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                })
                .catch(error => {
                    const errorMessage = error.message || 'Error saving processes.';
                    showNotification('Error while saving processes: ' + errorMessage, 'error');
                });
        });

        // Функция для загрузки и отображения процессов
        function loadProcessesForRow(selectElement) {
            const processNameId = selectElement.value;
            const processRow = selectElement.closest('.process-row');
            const processOptionsContainer = processRow.querySelector('.process-options');
            const manualId = document.getElementById('processes-container').dataset.manualId;
            const saveButton = document.querySelector('button[type="submit"]');

            if (!processNameId || !processOptionsContainer) {
                return;
            }

            // Получаем индекс строки для правильного именования чекбоксов
            const selectName = selectElement.name;
            const match = selectName.match(/processes\[(\d+)\]/);
            const rowIndex = match ? match[1] : '0';

            // Показываем индикатор загрузки
            processOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            // Используем processes.getProcesses для получения existingProcesses и availableProcesses
            fetch(`{{ route('processes.getProcesses') }}?processNameId=${processNameId}&manualId=${manualId}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || err.message || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    processRow.dataset.canCreateProcess = data.canCreateProcess ? '1' : '0';
                    processRow.dataset.createProcessMessage = data.createProcessMessage || '';
                    processOptionsContainer.innerHTML = ''; // Очищаем контейнер

                    let hasProcesses = false;

                    // Отображаем только existingProcesses (уже связанные с manual_id)
                    // Не отмечены, без пометки "(existing)"
                    if (data.existingProcesses && data.existingProcesses.length > 0) {
                        data.existingProcesses.forEach(process => {
                            const radio = document.createElement('div');
                            radio.classList.add('form-check');
                            radio.innerHTML = `
                                <input type="radio" name="processes[${rowIndex}][process]" value="${process.id}" class="form-check-input" id="process_${rowIndex}_${process.id}"
                                       data-requires-fig="${process.requires_fig ? '1' : '0'}"
                                       data-requires-zone="${process.requires_zone ? '1' : '0'}">
                                <label class="form-check-label" for="process_${rowIndex}_${process.id}">${process.process}</label>
                            `;
                            processOptionsContainer.appendChild(radio);
                            hasProcesses = true;
                        });
                    }

                    // availableProcesses не отображаем на странице, только в модальном окне

                    if (hasProcesses) {
                        saveButton.disabled = false;

                        // Если это NDT процесс и есть выбранные процессы, показываем дополнительный селект
                        if (isNdtProcess(processNameId)) {
                            const ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                            if (ndtPlusContainer) {
                                // Проверяем, есть ли уже выбранные процессы
                                const checkedRadio = processRow.querySelector('.process-options input[type="radio"]:checked');
                                if (checkedRadio) {
                                    // Убеждаемся, что контейнер виден
                                    ndtPlusContainer.style.display = 'block';
                                    ndtPlusContainer.style.visibility = 'visible';
                                    ndtPlusContainer.style.opacity = '1';

                                    // Инициализируем Select2, если еще не инициализирован
                                    const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                                    if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                                        if (!$(ndtPlusSelect).hasClass('select2-hidden-accessible')) {
                                            setTimeout(function() {
                                                $(ndtPlusSelect).select2({
                                                    theme: 'bootstrap-5',
                                                    width: '100%',
                                                    multiple: true,
                                                    placeholder: 'Select Additional NDT Process(es)',
                                                    dropdownParent: $(ndtPlusContainer)
                                                }).on('select2:select select2:unselect', function (e) {
                                                    const selectElement = e.target;
                                                    const selectedValues = $(selectElement).val() || [];
                                                    if (selectedValues.length > 0) {
                                                        loadNdtPlusProcesses(selectElement);
                                                    } else {
                                                        const processRow = selectElement.closest('.process-row');
                                                        const ndtPlusOptionsContainer = processRow ? processRow.querySelector('.ndt-plus-process-options') : null;
                                                        if (ndtPlusOptionsContainer) {
                                                            ndtPlusOptionsContainer.innerHTML = '';
                                                        }
                                                    }
                                                });

                                                // Исключаем выбранный основной NDT процесс из опций
                                                $(ndtPlusSelect).find('option').each(function() {
                                                    if ($(this).val() === processNameId) {
                                                        $(this).prop('disabled', true);
                                                    } else {
                                                        $(this).prop('disabled', false);
                                                    }
                                                });
                                            }, 100);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        const noSpecLabel = document.createElement('div');
                        noSpecLabel.classList.add('text-muted', 'mt-2');
                        noSpecLabel.innerHTML = 'No specification. Add specification for this process.';
                        processOptionsContainer.appendChild(noSpecLabel);
                        saveButton.disabled = true;
                    }
                })
                .catch(error => {
                    processOptionsContainer.innerHTML = `<div class="text-danger">Error loading processes: ${error.message}</div>`;
                    saveButton.disabled = true;
                showNotification('Error while loading processes: ' + error.message, 'error');
                });
        }

        // Функция для загрузки процессов дополнительных NDT (для множественного выбора)
        function loadNdtPlusProcesses(selectElement) {
            // Получаем выбранные значения через Select2 API или напрямую
            let selectedNdtProcessNameIds = [];
            if (typeof $ !== 'undefined' && $(selectElement).hasClass('select2-hidden-accessible')) {
                // Если Select2 инициализирован, используем его API
                selectedNdtProcessNameIds = $(selectElement).val() || [];
            } else {
                // Если Select2 не инициализирован, используем обычный способ
                selectedNdtProcessNameIds = Array.from(selectElement.selectedOptions || selectElement.options).filter(opt => opt.selected).map(opt => opt.value);
            }
            const processRow = selectElement.closest('.process-row');
            const ndtPlusOptionsContainer = processRow.querySelector('.ndt-plus-process-options');
            const manualId = document.getElementById('processes-container').dataset.manualId;
            const rowIndex = selectElement.getAttribute('data-row-index') || '0';

            if (!selectedNdtProcessNameIds.length || !ndtPlusOptionsContainer) {
                ndtPlusOptionsContainer.innerHTML = '';
                return;
            }

            ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            // Загружаем процессы для всех выбранных дополнительных NDT
            const loadPromises = selectedNdtProcessNameIds.map(processNameId => {
                return fetch(`{{ route('processes.getProcesses') }}?processNameId=${processNameId}&manualId=${manualId}`)
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.error || err.message || 'Network response was not ok');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        return { processNameId, processes: data.existingProcesses || [] };
                    });
            });

            Promise.all(loadPromises)
                .then(results => {
                    ndtPlusOptionsContainer.innerHTML = '';

                    results.forEach(({ processNameId, processes }) => {
                        if (processes.length > 0) {
                            // Получаем название процесса для заголовка
                            let processName = `NDT-${processNameId}`;
                            if (typeof $ !== 'undefined' && $(selectElement).hasClass('select2-hidden-accessible')) {
                                const processNameOption = $(selectElement).find(`option[value="${processNameId}"]`);
                                if (processNameOption.length > 0) {
                                    processName = processNameOption.text();
                                }
                            } else {
                                const processNameOption = selectElement.querySelector(`option[value="${processNameId}"]`);
                                if (processNameOption) {
                                    processName = processNameOption.textContent;
                                }
                            }

                            // Добавляем заголовок для группы процессов
                            const header = document.createElement('div');
                            header.classList.add('mt-2', 'mb-1');
                            header.innerHTML = `<strong>${processName}:</strong>`;
                            ndtPlusOptionsContainer.appendChild(header);

                            // Добавляем чекбоксы для процессов
                            processes.forEach(process => {
                                const checkbox = document.createElement('div');
                                checkbox.classList.add('form-check');
                                checkbox.innerHTML = `
                                    <input type="checkbox" name="processes[${rowIndex}][ndt_plus_processes][]"
                                           value="${process.id}"
                                            class="form-check-input ndt-plus-process-checkbox"
                                            data-ndt-process-name-id="${processNameId}"
                                            data-requires-fig="${process.requires_fig ? '1' : '0'}"
                                            data-requires-zone="${process.requires_zone ? '1' : '0'}">
                                    <label class="form-check-label">${process.process}</label>
                                `;
                                ndtPlusOptionsContainer.appendChild(checkbox);
                            });
                        }
                    });

                    if (ndtPlusOptionsContainer.children.length === 0) {
                        ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">No processes available</div>';
                    }
                })
                .catch(error => {
                    ndtPlusOptionsContainer.innerHTML = `<div class="text-danger">Error loading processes: ${error.message}</div>`;
                });
        }

        // Обработчик изменения чекбоксов процессов - показываем/скрываем дополнительный селект NDT
        document.addEventListener('change', function(event) {
            if (event.target.matches('.process-options input[type="radio"]')) {
                const radio = event.target;
                const processRow = radio.closest('.process-row');

                if (!processRow) {
                    return;
                }

                const processNameSelect = processRow.querySelector('.select2-process');
                const processNameId = processNameSelect ? processNameSelect.value : null;

                // Ищем контейнер разными способами
                let ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                if (!ndtPlusContainer) {
                    // Пробуем найти в col-md-5
                    const colMd5 = processRow.querySelector('.col-md-5');
                    if (colMd5) {
                        ndtPlusContainer = colMd5.querySelector('.ndt-plus-process-container');
                    }
                }
                if (!ndtPlusContainer) {
                    // Пробуем найти по ID
                    const rowIndex = processRow.querySelector('.select2-process')?.name?.match(/processes\[(\d+)\]/)?.[1] || '0';
                    ndtPlusContainer = document.getElementById(`ndt_plus_process_${rowIndex}`)?.closest('.ndt-plus-process-container');
                }

                if (processNameId && isNdtProcess(processNameId)) {
                    if (!ndtPlusContainer) return;

                    const checkedRadio = processRow.querySelector('.process-options input[type="radio"]:checked');
                    if (checkedRadio) {
                        // Используем requestAnimationFrame для гарантии, что изменения применятся после всех других обработчиков
                        requestAnimationFrame(function() {
                        // Принудительно показываем контейнер
                        ndtPlusContainer.style.display = 'block';
                        ndtPlusContainer.style.visibility = 'visible';
                        ndtPlusContainer.style.opacity = '1';
                        ndtPlusContainer.style.height = 'auto';
                        ndtPlusContainer.style.overflow = 'visible';

                        // Убеждаемся, что все дочерние элементы видимы
                        const allChildren = ndtPlusContainer.querySelectorAll('*');
                        allChildren.forEach(child => {
                            if (child.style) {
                                if (child.style.display === 'none') {
                                    child.style.display = '';
                                }
                                if (child.style.visibility === 'hidden') {
                                    child.style.visibility = '';
                                }
                            }
                        });

                        // Устанавливаем z-index и position для контейнера, чтобы он не был перекрыт
                        ndtPlusContainer.style.setProperty('position', 'relative', 'important');
                        ndtPlusContainer.style.setProperty('z-index', '1000', 'important');

                        // Инициализируем Select2 для дополнительного селекта, если еще не инициализирован
                        const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            // Исключаем уже выбранный NDT процесс из опций
                            if (processNameId) {
                                const optionToRemove = ndtPlusSelect.querySelector(`option[value="${processNameId}"]`);
                                if (optionToRemove) {
                                    optionToRemove.remove();
                                }
                            }

                            if (typeof $ !== 'undefined' && $.fn.select2) {
                                // Используем setTimeout для инициализации после показа контейнера
                                setTimeout(function() {
                                    // Проверяем, инициализирован ли уже Select2
                                    const isSelect2Initialized = $(ndtPlusSelect).hasClass('select2-hidden-accessible');

                                    if (!isSelect2Initialized) {
                                        $(ndtPlusSelect).select2({
                                            theme: 'bootstrap-5',
                                            width: '100%',
                                            multiple: true,
                                            placeholder: 'Select Additional NDT Process(es)'
                                        }).on('select2:select select2:unselect', function (e) {
                                            // Обработчик изменения выбора дополнительных NDT процессов
                                            const selectElement = e.target;
                                            const selectedValues = $(selectElement).val() || [];
                                            if (selectedValues.length > 0) {
                                                loadNdtPlusProcesses(selectElement);
                                            } else {
                                                const processRow = selectElement.closest('.process-row');
                                                const ndtPlusOptionsContainer = processRow ? processRow.querySelector('.ndt-plus-process-options') : null;
                                                if (ndtPlusOptionsContainer) {
                                                    ndtPlusOptionsContainer.innerHTML = '';
                                                }
                                            }
                                        });

                                        // Принудительно показываем контейнер и Select2 элементы после инициализации
                                        setTimeout(function() {
                                            // Проверяем и устанавливаем стили для контейнера
                                            const computedStyle = window.getComputedStyle(ndtPlusContainer);
                                            if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                                ndtPlusContainer.style.setProperty('display', 'block', 'important');
                                                ndtPlusContainer.style.setProperty('visibility', 'visible', 'important');
                                                ndtPlusContainer.style.setProperty('opacity', '1', 'important');
                                                ndtPlusContainer.style.setProperty('position', 'relative', 'important');
                                                ndtPlusContainer.style.setProperty('z-index', '1000', 'important');
                                            }

                                            // Проверяем и устанавливаем стили для Select2 контейнера
                                            const select2Container = $(ndtPlusSelect).next('.select2-container');
                                            if (select2Container.length > 0) {
                                                const select2ComputedStyle = window.getComputedStyle(select2Container[0]);
                                                if (select2ComputedStyle.display === 'none' || select2ComputedStyle.visibility === 'hidden') {
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important',
                                                        'position': 'relative !important',
                                                        'z-index': '1001 !important'
                                                    });
                                                }

                                                // Проверяем видимость через getBoundingClientRect
                                                const rect = select2Container[0].getBoundingClientRect();
                                                if (rect.width === 0 || rect.height === 0) {
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important',
                                                        'width': '100% !important',
                                                        'min-height': '38px !important'
                                                    });
                                                }
                                            }
                                        }, 100);
                                    } else {
                                        // Если Select2 уже инициализирован, обновляем видимость визуального контейнера
                                        // Сначала уничтожаем старый экземпляр
                                        $(ndtPlusSelect).select2('destroy');

                                        // Затем инициализируем заново
                                        $(ndtPlusSelect).select2({
                                            theme: 'bootstrap-5',
                                            width: '100%',
                                            multiple: true,
                                            placeholder: 'Select Additional NDT Process(es)'
                                        }).on('select2:select select2:unselect', function (e) {
                                            // Обработчик изменения выбора дополнительных NDT процессов
                                            const selectElement = e.target;
                                            const selectedValues = $(selectElement).val() || [];
                                            if (selectedValues.length > 0) {
                                                loadNdtPlusProcesses(selectElement);
                                            } else {
                                                const processRow = selectElement.closest('.process-row');
                                                const ndtPlusOptionsContainer = processRow ? processRow.querySelector('.ndt-plus-process-options') : null;
                                                if (ndtPlusOptionsContainer) {
                                                    ndtPlusOptionsContainer.innerHTML = '';
                                                }
                                            }
                                        });

                                        // Принудительно показываем контейнер и Select2 элементы после переинициализации
                                        setTimeout(function() {
                                            // Проверяем и устанавливаем стили для контейнера
                                            const computedStyle = window.getComputedStyle(ndtPlusContainer);
                                            if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                                ndtPlusContainer.style.setProperty('display', 'block', 'important');
                                                ndtPlusContainer.style.setProperty('visibility', 'visible', 'important');
                                                ndtPlusContainer.style.setProperty('opacity', '1', 'important');
                                                ndtPlusContainer.style.setProperty('position', 'relative', 'important');
                                                ndtPlusContainer.style.setProperty('z-index', '1000', 'important');
                                            }

                                            // Проверяем и устанавливаем стили для Select2 контейнера
                                            const select2Container = $(ndtPlusSelect).next('.select2-container');
                                            if (select2Container.length > 0) {
                                                const select2ComputedStyle = window.getComputedStyle(select2Container[0]);
                                                if (select2ComputedStyle.display === 'none' || select2ComputedStyle.visibility === 'hidden') {
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important',
                                                        'position': 'relative !important',
                                                        'z-index': '1001 !important'
                                                    });
                                                }

                                                const rect = select2Container[0].getBoundingClientRect();
                                                if (rect.width === 0 || rect.height === 0) {
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important',
                                                        'width': '100% !important',
                                                        'min-height': '38px !important'
                                                    });
                                                }
                                            }
                                        }, 100);
                                    }

                                    // Исключаем выбранный основной NDT процесс из опций
                                    $(ndtPlusSelect).find('option').each(function() {
                                        if ($(this).val() === processNameId) {
                                            $(this).prop('disabled', true);
                                        } else {
                                            $(this).prop('disabled', false);
                                        }
                                    });

                                    // Обновляем Select2 для применения изменений
                                    $(ndtPlusSelect).trigger('change');

                                    // Убеждаемся, что оригинальный select скрыт (это нормально для Select2)
                                    $(ndtPlusSelect).css('display', 'none');

                                    // Убеждаемся, что визуальный контейнер Select2 виден
                                    // Select2 создает контейнер после оригинального select
                                    setTimeout(function() {
                                        const select2Container = $(ndtPlusSelect).next('.select2-container');
                                        if (select2Container.length > 0) {
                                            // Принудительно показываем контейнер и все его элементы
                                            select2Container.css({
                                                'display': 'inline-block',
                                                'visibility': 'visible',
                                                'opacity': '1',
                                                'height': 'auto',
                                                'width': '100%',
                                                'min-height': '38px'
                                            });

                                            // Проверяем и показываем внутренние элементы
                                            const select2Selection = select2Container.find('.select2-selection');
                                            if (select2Selection.length > 0) {
                                                select2Selection.css({
                                                    'display': 'flex',
                                                    'visibility': 'visible',
                                                    'opacity': '1',
                                                    'min-height': '38px'
                                                });
                                            }

                                            const select2SelectionRendered = select2Container.find('.select2-selection__rendered');
                                            if (select2SelectionRendered.length > 0) {
                                                select2SelectionRendered.css({
                                                    'display': 'block',
                                                    'visibility': 'visible'
                                                });
                                            }

                                            // Убеждаемся, что контейнер находится в правильном месте в DOM
                                            const selectParent = $(ndtPlusSelect).parent();
                                            const containerParent = select2Container.parent();

                                            // Убеждаемся, что контейнер находится сразу после select
                                            const selectNextSibling = ndtPlusSelect.nextElementSibling;
                                            if (!selectNextSibling || !selectNextSibling.classList.contains('select2-container')) {
                                                // Если контейнер уже существует, перемещаем его
                                                if (select2Container.length > 0) {
                                                    select2Container.detach();
                                                    $(ndtPlusSelect).after(select2Container);
                                                }
                                            }

                                            // Убеждаемся, что контейнер имеет правильную ширину
                                            const containerWidth = ndtPlusContainer.offsetWidth || $(ndtPlusContainer).width();
                                            if (containerWidth > 0) {
                                                select2Container.css('width', containerWidth + 'px');
                                            }

                                            // Дополнительная проверка через небольшую задержку
                                            setTimeout(function() {
                                                const computedStyle = window.getComputedStyle(select2Container[0]);
                                                if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important'
                                                    });
                                                }

                                            }, 100);
                                        } else {
                                            // Пробуем найти контейнер другим способом
                                            const allSelect2Containers = $('.select2-container');
                                            // Ищем контейнер, который связан с нашим селектом
                                            const select2Id = $(ndtPlusSelect).attr('data-select2-id');
                                            if (select2Id) {
                                                const containerById = $(`.select2-container[data-select2-id="${select2Id}"]`);
                                                if (containerById.length > 0) {
                                                    containerById.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important'
                                                    });
                                                }
                                            }
                                        }
                                    }, 200);
                                }, 200);
                            }
                        }

                        });

                        // Защита от скрытия контейнера - используем MutationObserver для отслеживания изменений
                        if (checkedRadio && ndtPlusContainer) {
                            // Функция для принудительного показа контейнера
                            const forceShowContainer = function() {
                                const currentCheckedRadio = processRow.querySelector('.process-options input[type="radio"]:checked');
                                if (currentCheckedRadio) {
                                    const computedStyle = window.getComputedStyle(ndtPlusContainer);
                                    if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                        ndtPlusContainer.style.setProperty('display', 'block', 'important');
                                        ndtPlusContainer.style.setProperty('visibility', 'visible', 'important');
                                        ndtPlusContainer.style.setProperty('opacity', '1', 'important');
                                        ndtPlusContainer.style.setProperty('height', 'auto', 'important');

                                        // Также проверяем Select2 контейнер
                                        const ndtPlusSelectAfter = ndtPlusContainer.querySelector('.select2-ndt-plus');
                                        if (ndtPlusSelectAfter && typeof $ !== 'undefined' && $.fn.select2) {
                                            const select2Container = $(ndtPlusSelectAfter).next('.select2-container');
                                            if (select2Container.length > 0) {
                                                select2Container.css({
                                                    'display': 'inline-block !important',
                                                    'visibility': 'visible !important',
                                                    'opacity': '1 !important'
                                                });
                                            }
                                        }
                                    }
                                }
                            };

                            // Создаем наблюдатель за изменениями стилей контейнера
                            const observer = new MutationObserver(function(mutations) {
                                mutations.forEach(function(mutation) {
                                    if (mutation.type === 'attributes' && (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                                        setTimeout(forceShowContainer, 10);
                                    }
                                });
                            });

                            // Начинаем наблюдение за изменениями атрибутов
                            observer.observe(ndtPlusContainer, {
                                attributes: true,
                                attributeFilter: ['style', 'class'],
                                attributeOldValue: true
                            });

                            // Также наблюдаем за родительскими элементами
                            const parentObserver = new MutationObserver(function(mutations) {
                                setTimeout(forceShowContainer, 10);
                            });

                            // Наблюдаем за родительским элементом
                            const parentElement = ndtPlusContainer.parentElement;
                            if (parentElement) {
                                parentObserver.observe(parentElement, {
                                    attributes: true,
                                    attributeFilter: ['style', 'class'],
                                    childList: true,
                                    subtree: true
                                });
                            }

                            // Сохраняем наблюдатели для последующей очистки
                            ndtPlusContainer._visibilityObserver = observer;
                            ndtPlusContainer._parentObserver = parentObserver;

                            // Также проверяем через задержку периодически
                            const checkInterval = setInterval(function() {
                                const currentCheckedRadio = processRow.querySelector('.process-options input[type="radio"]:checked');
                                if (!currentCheckedRadio) {
                                    clearInterval(checkInterval);
                                    if (ndtPlusContainer._visibilityObserver) {
                                        ndtPlusContainer._visibilityObserver.disconnect();
                                    }
                                    if (ndtPlusContainer._parentObserver) {
                                        ndtPlusContainer._parentObserver.disconnect();
                                    }
                                } else {
                                    forceShowContainer();
                                }
                            }, 200);

                            // Очищаем интервал через 30 секунд (защита от утечек памяти)
                            setTimeout(function() {
                                clearInterval(checkInterval);
                            }, 30000);
                        }
                    } else {
                        ndtPlusContainer.style.display = 'none';
                        // Очищаем выбранные дополнительные NDT процессы
                        const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            if (typeof $ !== 'undefined' && $.fn.select2) {
                                $(ndtPlusSelect).val(null).trigger('change');
                            } else {
                                ndtPlusSelect.value = '';
                            }
                        }
                        const ndtPlusOptions = ndtPlusContainer.querySelector('.ndt-plus-process-options');
                        if (ndtPlusOptions) {
                            ndtPlusOptions.innerHTML = '';
                        }
                    }
                }
            }

            // Обработчик выбора дополнительных NDT процессов (множественный выбор)
            // Работает как для обычного select, так и для Select2
            if (event.target.matches('.select2-ndt-plus') || event.target.closest('.select2-ndt-plus')) {
                const selectElement = event.target.matches('.select2-ndt-plus')
                    ? event.target
                    : document.querySelector('.select2-ndt-plus');

                if (selectElement) {
                    const selectedValues = Array.from(selectElement.selectedOptions || selectElement.options).filter(opt => opt.selected).map(opt => opt.value);
                    if (selectedValues.length > 0) {
                        loadNdtPlusProcesses(selectElement);
                    } else {
                        const processRow = selectElement.closest('.process-row');
                        const ndtPlusOptionsContainer = processRow ? processRow.querySelector('.ndt-plus-process-options') : null;
                        if (ndtPlusOptionsContainer) {
                            ndtPlusOptionsContainer.innerHTML = '';
                        }
                    }
                }
            }
        });

        // Обновление чекбоксов при изменении выбранного имени процесса
        // Обработчик для обычного события change
        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('select2-process')) {
                const processNameId = event.target.value;
                const processRow = event.target.closest('.process-row');
                const processOptionsContainer = processRow.querySelector('.process-options');
                const manualId = document.getElementById('processes-container').dataset.manualId; // Получаем manual_id
                const saveButton = document.querySelector('button[type="submit"]');

                // Обновление отображения id выбранного Process Name
                // Получаем индекс строки из name атрибута select
                const selectName = event.target.name;
                const match = selectName.match(/processes\[(\d+)\]/);
                if (match) {
                    const rowIndex = match[1];
                    const processIdElement = document.getElementById(`process-name-id-${rowIndex}`);
                    if (processIdElement) {
                        processIdElement.textContent = processNameId || '-';
                    }
                }

                // ============================================
                // ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ ID ДЛЯ УСЛОВНОЙ ЛОГИКИ
                // ============================================

                // // СПОСОБ 1: Использование id для сравнения в JavaScript
                // if (processNameId) {
                //     // Пример: если id равен определенному значению
                //     if (processNameId == '1') {
                //         console.log('Выбран Process Name с id = 1');
                //         // Здесь можно выполнить какие-то действия
                //     }
                //
                //     // Пример: если id в определенном диапазоне
                //     if (processNameId >= 1 && processNameId <= 10) {
                //         console.log('ID в диапазоне от 1 до 10');
                //     }
                //
                //     // Пример: проверка на несколько значений
                //     if ([1, 5, 10].includes(parseInt(processNameId))) {
                //         console.log('ID равен 1, 5 или 10');
                //     }
                // }

                // // СПОСОБ 2: Использование data-атрибутов для получения дополнительных данных
                // const selectedOption = event.target.options[event.target.selectedIndex];
                // if (selectedOption) {
                //     const isSpecial = selectedOption.getAttribute('data-is-special') === 'true';
                //     if (isSpecial) {
                //         console.log('Выбран специальный процесс');
                //         // Показать/скрыть дополнительные элементы
                //     }
                // }
                //
                // // СПОСОБ 3: Использование данных из data-process-data атрибута
                // const processDataStr = event.target.getAttribute('data-process-data');
                // if (processDataStr) {
                //     try {
                //         const processData = JSON.parse(processDataStr);
                //         const selectedProcess = processData[processNameId];
                //         if (selectedProcess) {
                //             // Теперь можно использовать данные процесса для условий
                //             // Например: if (selectedProcess.type === 'special') { ... }
                //             console.log('Данные процесса:', selectedProcess);
                //         }
                //     } catch (e) {
                //         console.error('Ошибка парсинга данных процесса:', e);
                //     }
                // }

                // СПОСОБ 4: Условное отображение элементов на основе id

                updateEcCheckboxesForProcessRow(processRow, processNameId);

                // Используем функцию для загрузки процессов
                loadProcessesForRow(event.target);

                // Если это NDT процесс, обновляем опции дополнительного селекта (исключаем выбранный)
                if (isNdtProcess(processNameId)) {
                    const processRow = event.target.closest('.process-row');
                    const ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                    const ndtPlusSelect = ndtPlusContainer ? ndtPlusContainer.querySelector('.select2-ndt-plus') : null;

                    // НЕ скрываем контейнер, если уже есть выбранные процессы
                    const checkedRadio = processRow.querySelector('.process-options input[type="radio"]:checked');
                    if (checkedRadio && ndtPlusContainer) {
                        // Убеждаемся, что контейнер виден
                        ndtPlusContainer.style.display = 'block';
                        ndtPlusContainer.style.visibility = 'visible';
                    }

                    if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                        // Исключаем выбранный NDT процесс из опций
                        $(ndtPlusSelect).find('option').each(function() {
                            if ($(this).val() === processNameId) {
                                $(this).prop('disabled', true);
                                // Удаляем из выбранных, если был выбран
                                if ($(ndtPlusSelect).val() && Array.isArray($(ndtPlusSelect).val()) && $(ndtPlusSelect).val().includes($(this).val())) {
                                    const newValues = $(ndtPlusSelect).val().filter(v => v !== $(this).val());
                                    $(ndtPlusSelect).val(newValues).trigger('change');
                                }
                            } else {
                                $(this).prop('disabled', false);
                            }
                        });
                        $(ndtPlusSelect).trigger('change');
                    }
                } else {
                    // Если не NDT, скрываем дополнительный селект
                    const processRow = event.target.closest('.process-row');
                    const ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                    if (ndtPlusContainer) {
                        ndtPlusContainer.style.display = 'none';
                        // Очищаем выбранные значения
                        const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                            $(ndtPlusSelect).val(null).trigger('change');
                        }
                    }
                }
            }
        });

        // Глобальная переменная для хранения ссылки на текущую строку (например, process-row)
        let currentRow = null;

        function updateTdrProcessCreateState(canCreate, message) {
            const input = document.getElementById('newProcessInput');
            const note = document.getElementById('processCreateRestriction');
            const saveBtn = document.getElementById('saveProcessModal');

            if (!input || !note || !saveBtn) {
                return;
            }

            input.disabled = !canCreate;
            if (!canCreate) {
                input.value = '';
                input.placeholder = 'New subprocess is locked for this process group.';
                note.textContent = message || 'Only existing processes can be selected.';
                note.classList.remove('d-none');
                saveBtn.disabled = true;
            } else {
                input.placeholder = 'Enter new process';
                note.textContent = '';
                note.classList.add('d-none');
                saveBtn.disabled = false;
            }
        }

        // При попытке открыть модальное окно добавления спецификации — проверяем, что выбран Process Name
        document.getElementById('addProcessModal').addEventListener('show.bs.modal', function(e) {
            const triggerBtn = e.relatedTarget;
            if (!triggerBtn) return;
            const row = triggerBtn.closest('.process-row');
            const select = row?.querySelector('.select2-process');
            const processNameId = (typeof $ !== 'undefined' && select && $(select).hasClass('select2-hidden-accessible'))
                ? $(select).val() : select?.value;
            if (!processNameId || processNameId === '') {
                e.preventDefault();
                showNotification('{{ __("Please select Process Name before adding specification.") }}', 'warning');
                return;
            }

            const canCreate = row?.dataset.canCreateProcess === '1';
            const message = row?.dataset.createProcessMessage || '';
            updateTdrProcessCreateState(canCreate, message);
        });

        // Делегирование: при клике на "+" сохраняем текущую строку и данные модального окна
        document.getElementById('processes-container').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn[data-bs-target="#addProcessModal"]');
            if (!btn) return;

            currentRow = btn.closest('.process-row');
            const select = currentRow.querySelector('.select2-process');
            const processNameId = (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible'))
                ? $(select).val() : select?.value;
            if (!processNameId || processNameId === '') return; // Проверка в show.bs.modal покажет сообщение
            const processNameText = select.options[select.selectedIndex]?.text || '';
            document.getElementById('modalProcessName').innerText = processNameText;
            document.getElementById('modalProcessNameId').value = processNameId;

            // Очищаем поле ввода нового процесса
            document.getElementById('newProcessInput').value = '';
            updateTdrProcessCreateState(currentRow?.dataset.canCreateProcess === '1', currentRow?.dataset.createProcessMessage || '');
        });


        // Обработка нажатия кнопки "Save Process" в модальном окне
        document.getElementById('saveProcessModal').addEventListener('click', function() {
            const processNameId = document.getElementById('modalProcessNameId').value;
            const newProcessInput = document.getElementById('newProcessInput');
            const newProcess = newProcessInput.value.trim();
            const restrictionMessage = document.getElementById('processCreateRestriction')?.textContent || '';

            if (newProcessInput.disabled) {
                showNotification(restrictionMessage || '{{ __("Creating new process is not allowed.") }}', 'warning');
                return;
            }

            // Требуем ввод нового процесса вручную
            if (newProcess === '') {
                showNotification('{{ __("Please enter the new process name.") }}', 'warning');
                return;
            }

            // Получаем manual_id через data-атрибут
            const manualId = document.getElementById('processes-container').dataset.manualId;

            if (currentRow) {
                const processOptionsContainer = currentRow.querySelector('.process-options');
                const saveButton = document.querySelector('button[type="submit"]');

                // Получаем индекс строки для правильного именования чекбоксов
                const select = currentRow.querySelector('.select2-process');
                const selectName = select ? select.name : '';
                const match = selectName.match(/processes\[(\d+)\]/);
                const rowIndex = match ? match[1] : '0';

                // Если введён новый процесс – отправляем AJAX-запрос для его создания
                if (newProcess !== '') {
                    // Показываем индикатор загрузки
                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'text-muted';
                    loadingDiv.textContent = 'Saving process...';
                    processOptionsContainer.appendChild(loadingDiv);

                    fetch("{{ route('processes.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            process_names_id: processNameId,
                            process: newProcess,
                            manual_id: manualId
                        })
                    })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw new Error(err.error || err.message || 'Network response was not ok');
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Удаляем индикатор загрузки
                            if (loadingDiv.parentNode) {
                                loadingDiv.remove();
                            }

                            if (data.success) {
                                // Добавляем созданный процесс в виде radio в контейнер текущей строки
                                const div = document.createElement('div');
                                div.classList.add('form-check');
                                div.innerHTML = `
                            <input type="radio" class="form-check-input" name="processes[${rowIndex}][process]" value="${data.process.id}" id="process_${rowIndex}_${data.process.id}" checked>
                            <label class="form-check-label" for="process_${rowIndex}_${data.process.id}">${data.process.process}</label>
                        `;
                                processOptionsContainer.appendChild(div);
                                saveButton.disabled = false; // Активируем кнопку Save

                                // Очищаем сообщение "No specification"
                                const noSpecLabel = processOptionsContainer.querySelector('.text-muted');
                                if (noSpecLabel) {
                                    noSpecLabel.remove();
                                }

                                // Очищаем поле ввода нового процесса
                                document.getElementById('newProcessInput').value = '';

            showNotification('Process added successfully!', 'success');

                                // Закрываем модальное окно
                                const modalEl = document.getElementById('addProcessModal');
                                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                            } else {
            showNotification(data.message || "Error while adding a new process.", 'error');
                            }
                        })
                        .catch(error => {
                            // Удаляем индикатор загрузки при ошибке
                            if (loadingDiv.parentNode) {
                                loadingDiv.remove();
                            }

                            showNotification("Error adding new process: " + (error.message || 'Unknown error'), 'error');
                        });
                }
            } else {
                // Если currentRow не определен, просто закрываем модальное окно
                const modalEl = document.getElementById('addProcessModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });

        // Инициализация Select2 и отображения id и чекбокса EC для всех существующих строк при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализируем Select2 для всех существующих select элементов
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('.select2-process').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '{{ __("Select Process Name") }}',
                    allowClear: false,
                    templateResult: function(data) { if (!data.id) return null; return data.text; }
                }).on('select2:select', function (e) {
                    // Обработчик события Select2 для загрузки процессов
                    const selectElement = e.target;
                    const processNameId = selectElement.value;
                    const processRow = selectElement.closest('.process-row');

                    updateEcCheckboxesForProcessRow(processRow, processNameId);

                    loadProcessesForRow(selectElement);

                    // Если это NDT процесс, обновляем опции дополнительного селекта (исключаем выбранный)
                    if (isNdtProcess(processNameId)) {
                        const ndtPlusSelect = processRow.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            // Удаляем опцию с выбранным NDT процессом
                            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                            $(ndtPlusSelect).trigger('change');
                        }
                    }
                });

                // НЕ инициализируем Select2 для скрытых дополнительных селектов NDT при загрузке страницы
                // Они будут инициализированы динамически при показе контейнера
                // Это предотвращает проблемы с видимостью визуального элемента Select2
            }

            const processRows = document.querySelectorAll('.process-row');
            processRows.forEach(row => {
                const select = row.querySelector('.select2-process');
                if (select) {
                    const processNameId = select.value;
                    updateEcCheckboxesForProcessRow(row, processNameId);

                    // Если это NDT процесс, исключаем его из дополнительного селекта
                    if (isNdtProcess(processNameId)) {
                        const ndtPlusSelect = row.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            // Удаляем опцию с выбранным NDT процессом
                            const optionToRemove = ndtPlusSelect.querySelector(`option[value="${processNameId}"]`);
                            if (optionToRemove) {
                                optionToRemove.remove();
                            }
                            // Если Select2 уже инициализирован, обновляем его
                            if (typeof $ !== 'undefined' && $.fn.select2 && $(ndtPlusSelect).hasClass('select2-hidden-accessible')) {
                                $(ndtPlusSelect).trigger('change');
                            }
                        }
                    }

                    const selectName = select.name;
                    const match = selectName.match(/processes\[(\d+)\]/);
                    if (match) {
                        const rowIndex = match[1];
                        const processIdElement = document.getElementById(`process-name-id-${rowIndex}`);
                        if (processIdElement) {
                            processIdElement.textContent = processNameId || '-';
                        }
                    }
                }
            });
        });


    </script>
@endsection
