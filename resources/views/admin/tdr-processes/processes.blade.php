@extends('admin.master')

@section('content')
    <style>
        .container {
            /*max-width: 1200px;*/
        }

        /* Ограничение высоты card-body */
        .card-body {
            height: 75vh;
            overflow-y: auto;
        }

        /* Ограничение высоты таблицы и фиксация шапки */
        .table-wrapper {
            max-height: 60vh;
            overflow-y: auto;
            overflow-x: auto;
        }

        .table-wrapper table thead th {
            position: sticky;
            top: 0;
            z-index: 1020;
            background-color: var(--bs-body-bg, #fff);
        }

        html[data-bs-theme="dark"] .table-wrapper table thead th {
            background-color: var(--bs-body-bg, #212529);
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

        /* Стили для модального окна Group Process Forms */
        .group-form-link {
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .group-form-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .vendor-select {
            transition: all 0.3s ease;
        }

        .vendor-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Стили для модального окна Group Process Forms */
        #groupFormsModal .table {
            margin-bottom: 0;
        }

        #groupFormsModal .table th {
            font-weight: 600;
        }

        #groupFormsModal .table td {
            vertical-align: middle;
            padding: 1rem;
        }

        .process-checkboxes {
            max-height: 200px;
            overflow-y: auto;
            padding: 0.5rem;
            color: inherit;
        }

        .process-checkbox:checked + .form-check-label {
            font-weight: 500;
            color: inherit;
        }

        .process-checkboxes .form-check {
            margin-bottom: 0.5rem;
            padding: 0.25rem;
            border-radius: 4px;
            transition: background-color 0.2s;
            color: inherit;
        }

        .process-checkboxes .form-check:hover {
            background-color: rgba(128, 128, 128, 0.1);
        }

        .process-checkboxes .form-check-label {
            font-size: 0.9rem;
            cursor: pointer;
            margin-left: 0.5rem;
            color: inherit;
        }

        .process-checkboxes strong {
            color: inherit;
        }

        /* Оптимизированные стили для печати */
        @media print {
            /* Фиксированный размер страницы Letter (8.5 x 11 дюймов = 215.9 x 279.4 мм) */
            @page {
                size: letter portrait;
                margin: 0.5cm 0.5cm 0.5cm 0.5cm; /* Фиксированные поля для всех браузеров */
                padding: 0;
            }

            /* Сброс всех отступов и полей для консистентности */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Фиксированные размеры для body и html */
            html, body {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 12pt !important; /* Фиксированный размер шрифта */
                line-height: 1.2 !important;
            }

            /* Контейнер с фиксированной шириной */
            .container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Скрываем элементы, которые не нужны при печати */
            .btn,
            .modal,
            .card-header,
            .d-flex:has(.btn),
            button,
            .no-print,
            .select2-container,
            .select2-selection,
            .sortable-table tbody tr:hover,
            .sortable-table tbody tr.dragging {
                display: none !important;
            }

            /* Стили для таблицы при печати */
            .table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10pt !important;
                page-break-inside: auto !important;
            }

            .table thead {
                display: table-header-group !important;
            }

            .table tbody {
                display: table-row-group !important;
            }

            .table tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            .table th,
            .table td {
                padding: 4pt !important;
                border: 1pt solid #000 !important;
                vertical-align: top !important;
            }

            .table th {
                background-color: #f0f0f0 !important;
                font-weight: bold !important;
                text-align: center !important;
            }

            /* Фиксированные размеры для карточек */
            .card {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .card-body {
                padding: 0 !important;
            }

            /* Убираем градиенты и тени при печати */
            .bg-gradient {
                background: #fff !important;
            }

            /* Фиксированные размеры шрифтов */
            h1, h2, h3, h4, h5, h6 {
                font-size: 14pt !important;
                margin: 5pt 0 !important;
            }

            p {
                font-size: 11pt !important;
                margin: 3pt 0 !important;
            }

            /* Стили для селектов - показываем только выбранное значение */
            .vendor-select,
            .form-select {
                border: none !important;
                background: transparent !important;
                padding: 0 !important;
                font-size: 10pt !important;
            }

            /* Убираем разрывы страниц внутри важных элементов */
            .table-wrapper {
                page-break-inside: avoid !important;
            }

            /* Фиксированная ширина колонок */
            .table th[style*="width"],
            .table td[style*="width"] {
                /* Сохраняем указанные ширины, но конвертируем в pt */
            }

            /* Убираем скролл и overflow */
            .table-responsive,
            .table-wrapper {
                overflow: visible !important;
                max-height: none !important;
            }

            /* Стили для текста */
            .text-primary {
                color: #000 !important;
            }

            /* Убираем интерактивные элементы */
            a[href]:after {
                content: "" !important;
            }

            /* Фиксированные размеры для модальных окон (если они должны печататься) */
            .modal-content {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>

    @php
        // Определяем ID для EC (один раз в начале файла для оптимизации)
        $ecProcessNameId = \App\Models\ProcessName::where('name', 'EC')->value('id');
    @endphp

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h5>{{ __('Part Processes') }}</h5>
                    <h5 class="text-primary me-5">{{__('Work Order: ')}} {{$current_tdr->workorder->number}}</h5>
                </div>
                <div class="d-flex justify-content-between ">
                    <div>
                         ITEM: {{ $current_tdr->component->name }}
                        <p>
                        PN: {{ $current_tdr->component->part_number }}
                        SN: {{ $current_tdr->serial_number }}</p>
                    </div>
                    <a href="{{ route('tdrs.processes', ['workorder_id'=>$current_tdr->workorder->id]) }}"
                       class="btn btn-outline-secondary" style="width: 120px;height: 50px; line-height: 1.2rem;align-content:
                       center">{{ __('All
                               Parts Processes')}} </a>

                    <div class="d-flex " style="width: 250px">
                        @if(isset($processGroups) && count($processGroups) > 0)
                            <div class="me-2">
                                <x-paper-button-multy
                                    text="Group Process Forms"
                                    color="outline-primary"
                                    size="landscape"
                                    width="80"
                                    ariaLabel="Group Process Forms"
                                    data-bs-toggle="modal"
                                    data-bs-target="#groupFormsModal"
                                />
                            </div>
                            <div class="me-2">
{{--                                <button type="button"--}}
{{--                                        class="btn btn-outline-primary"--}}
{{--                                        style="height: 40px; width: 100px;"--}}
{{--                                        data-bs-toggle="modal"--}}
{{--                                        data-bs-target="#packageModal">--}}
{{--                                    Package--}}
{{--                                </button>--}}
                                <x-paper-button-multy
                                    text="Package"
                                    color="outline-primary"
                                    size="landscape"
                                    width="80"
                                    ariaLabel="Group Process Forms"
                                    data-bs-toggle="modal"
                                    data-bs-target="#packageModal"
                                />
                            </div>
                        @endif

{{--                            <a href="{{ route('tdr-processes.traveler', ['tdrId' => $current_tdr->id]) }}"--}}
{{--                               class="btn btn-outline-info  ms-2" style="height: 50px">--}}
{{--                                <i class="fas fa-file-alt"></i> Traveler--}}
{{--                            </a>--}}
                            <x-paper-button
                                text="Traveler"
                                color="outline-primary"
                                size="landscape"
                                width="80"
                                href="{{ route('tdr-processes.traveler', ['tdrId' => $current_tdr->id]) }}"
                            />
                    </div>



                    <div class="d-flex parent">
                        <div class="ms-5">


                            <a href="{{ route('tdr-processes.createProcesses', ['tdrId' => $current_tdr->id]) }}"
                               class="btn btn-outline-success  me-2">
                                <i class="fas fa-plus"></i> Add Process
                            </a>
                            <button type="button" class="btn btn-outline-primary " data-bs-toggle="modal"
                                    data-bs-target="#addVendorModal">
                                <i class="fas fa-plus"></i> Add Vendor
                            </button>
                        </div>

                    </div>



                    <div class="me-4">
{{--                        <button type="button"--}}
{{--                                class="btn btn-outline-secondary btn-sm"--}}
{{--                                onclick="window.history.back();">--}}
{{--                            <i class="bi bi-arrow-left"></i> {{ __('Back o tdr') }}--}}
{{--                        </button>--}}
                        <a href="{{ route('tdrs.show', ['id'=>$current_wo ? $current_wo->id : '']) }}"
                           class="btn btn-outline-secondary " style="height:50px; width: 110px;line-height: 1.2rem;
                           align-content: center">
                            {{ __('Back to TDR') }} </a>
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
                            @php
                                // Определяем ID для EC (один раз в начале)
                                $ecProcessNameId = \App\Models\ProcessName::where('name', 'EC')->value('id');
                            @endphp
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
                                        // Проверяем, является ли это EC
                                        $isEc = ($ecProcessNameId !== null && (int)$processes->process_names_id === (int)$ecProcessNameId);

                                        // Проверяем, является ли это NDT процесс с дополнительными процессами
                                        $isNdtWithPlus = false;
                                        $combinedProcessNames = [];
                                        $combinedProcessDescriptions = [];

                                        if (strpos($processName, 'NDT-') === 0 && !empty($processes->plus_process)) {
                                            $isNdtWithPlus = true;
                                            // Добавляем основной NDT процесс
                                            $combinedProcessNames[] = $processName;

                                            // Получаем дополнительные NDT процессы
                                            $plusProcessIds = explode(',', $processes->plus_process);
                                            foreach ($plusProcessIds as $plusProcessId) {
                                                $plusProcessName = \App\Models\ProcessName::find(trim($plusProcessId));
                                                if ($plusProcessName && strpos($plusProcessName->name, 'NDT-') === 0) {
                                                    $combinedProcessNames[] = $plusProcessName->name;
                                                }
                                            }

                                            // Получаем процессы для каждого NDT
                                            // Процессы для основного NDT
                                            if (is_array($processData) && !empty($processData)) {
                                                $mainProcesses = [];
                                                foreach($processData as $processId) {
                                                    $proc = $proces->firstWhere('id', $processId);
                                                    if ($proc) {
                                                        $mainProcesses[] = $proc->process;
                                                    }
                                                }
                                                if (!empty($mainProcesses)) {
                                                    $combinedProcessDescriptions[] = implode(', ', $mainProcesses);
                                                }
                                            }
                                        }
                                    @endphp

                                    @if(!$processes->processName)
                                        @continue
                                    @endif

                                    @if($isEc)
                                        {{-- Для EC: одна строка со всеми процессами --}}
                                        <tr data-id="{{ $processes->id }}">
                                            <td class="text-center">{{ $processName }}</td>
                                            <td class="ps-2">
                                                @php
                                                    $processNames = [];
                                                    if (is_array($processData) && !empty($processData)) {
                                                        foreach($processData as $processId) {
                                                            $proc = $proces->firstWhere('id', $processId);
                                                            if ($proc) {
                                                                $processNames[] = $proc->process;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                {{ !empty($processNames) ? implode(', ', $processNames) : 'No processes' }}
                                            </td>
                                            <td class="text-center">
                                                {{$processes->description ?? ''}}
                                            </td>
                                            <td class="text-center">
                                                {{$processes->notes ?? ''}}
                                            </td>
                                            <td class="text-center">
                                                {{-- НЕАКТИВНЫЕ кнопки --}}
{{--                                                <a href="#" --}}
{{--                                                   class="btn btn-sm btn-outline-primary disabled" --}}
{{--                                                   style="pointer-events: none; opacity: 0.5; cursor: not-allowed;"--}}
{{--                                                   tabindex="-1"--}}
{{--                                                   aria-disabled="true">--}}
{{--                                                    {{__('Edit')}}--}}
{{--                                                </a>--}}
                                                <a href="#"
                                                   class="btn btn-outline-primary btn-sm me-2 disabled">
                                                    <i class="bi bi-pencil-square" title=" Process Edit"></i>
                                                </a>
                                                <button type="submit" class="btn btn-outline-danger btn-sm disabled ">
                                                    <i class="bi bi-trash"  title=" Process Delete"></i>
                                                </button>
{{--                                                <button class="btn btn-sm btn-outline-danger disabled" --}}
{{--                                                        type="button"--}}
{{--                                                        disabled--}}
{{--                                                        style="pointer-events: none; opacity: 0.5; cursor: not-allowed;"--}}
{{--                                                        tabindex="-1"--}}
{{--                                                        aria-disabled="true">--}}
{{--                                                    {{__('Delete')}}--}}
{{--                                                </button>--}}
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-2 justify-content-center">
{{--                                                    <select class="form-select form-select-sm vendor-select disabled" --}}
{{--                                                            disabled--}}
{{--                                                            style="pointer-events: none; opacity: 0.5; cursor: not-allowed;"--}}
{{--                                                            tabindex="-1"--}}
{{--                                                            aria-disabled="true"--}}
{{--                                                            data-tdr-process-id="{{ $processes->id }}">--}}
{{--                                                        <option value="">Select Vendor</option>--}}
{{--                                                        @foreach($vendors as $vendor)--}}
{{--                                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>--}}
{{--                                                        @endforeach--}}
{{--                                                    </select>--}}
{{--                                                    <a href="#"--}}
{{--                                                       class="btn btn-sm btn-outline-primary form-link disabled"--}}
{{--                                                       style="pointer-events: none; opacity: 0.5; cursor: not-allowed;"--}}
{{--                                                       tabindex="-1"--}}
{{--                                                       aria-disabled="true"--}}
{{--                                                       target="_blank">{{__('Form')}}</a>--}}
                                                </div>
                                            </td>
                                        </tr>
                                    @elseif($isNdtWithPlus)
                                        {{-- Для NDT с дополнительными процессами: одна объединенная строка --}}
                                        <tr data-id="{{ $processes->id }}">
                                            <td class="text-center">{{ implode(' / ', $combinedProcessNames) }}</td>
                                            <td class="ps-2">
                                                @php
                                                    // Получаем все процессы из массива processes
                                                    $allProcesses = [];
                                                    if (is_array($processData) && !empty($processData)) {
                                                        foreach($processData as $processId) {
                                                            $proc = $proces->firstWhere('id', $processId);
                                                            if ($proc) {
                                                                $allProcesses[] = $proc->process;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                {{ !empty($allProcesses) ? implode(' / ', $allProcesses) : 'No processes' }}@if($processes->ec) ( EC ) @endif
                                            </td>
                                            <td class="text-center">
                                                {{$processes->description ?? ''}}
                                            </td>
                                            <td class="text-center">
                                                {{$processes->notes ?? ''}}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('tdr-processes.edit', ['tdr_process' => $processes->id]) }}"
                                                   class="btn btn-outline-primary btn-sm me-2">
                                                    <i class="bi bi-pencil-square" title=" Process Edit"></i>
                                                </a>
                                                <form id="deleteForm_{{ $processes->id }}" action="{{ route('tdr-processes.destroy', ['tdr_process' => $processes->id]) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="tdrId" value="{{ $current_tdr->id }}">
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm ">
                                                        <i class="bi bi-trash"  title=" Process Delete"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <select class="form-select form-select-sm vendor-select"
                                                            style="width: 85px"
                                                            data-tdr-process-id="{{ $processes->id }}">
                                                        <option value="">Select Vendor</option>
                                                        @foreach($vendors as $vendor)
                                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <a href="{{ route('tdr-processes.show', ['tdr_process' => $processes->id]) }}"
                                                       class="btn btn-sm btn-outline-primary form-link"
                                                       style="width: 60px"
                                                       data-tdr-process-id="{{ $processes->id }}"
                                                       target="_blank">{{__('Form')}}</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        {{-- Для остальных: как раньше, по одной строке на процесс --}}
                                        {{-- Дополнительные NDT-процессы могут отображаться отдельно, если они созданы как отдельные записи --}}
                                        @if(is_array($processData) && !empty($processData))
                                            @foreach($processData as $process)
                                                <tr data-id="{{ $processes->id }}">
                                                    <td class="text-center">{{ $processName }}</td>
                                                    <td class="ps-2">
                                                        @php
                                                            $proc = $proces->firstWhere('id', $process);
                                                        @endphp
                                                        @if($proc)
                                                            {{$proc->process}}@if($processes->ec) ( EC ) @endif
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        {{$processes->description ?? ''}}
                                                    </td>
                                                    <td class="text-center">
                                                        {{$processes->notes ?? ''}}
                                                    </td>
                                                    <td class="text-center">
{{--                                                        <a href="{{ route('tdr-processes.edit', ['tdr_process' =>--}}
{{--                                                        $processes->id]) }}" class="btn btn-sm btn-outline-primary">{{__('Edit')}}</a>--}}
                                                        <a href="{{ route('tdr-processes.edit', ['tdr_process' =>
                                                        $processes->id]) }}"
                                                           class="btn btn-outline-primary btn-sm me-2">
                                                            <i class="bi bi-pencil-square" title=" Process Edit"></i>
                                                        </a>

                                                        <form id="deleteForm_{{ $processes->id }}" action="{{ route('tdr-processes.destroy', ['tdr_process' => $processes->id]) }}" method="POST" style="display:inline;">
                                                            @csrf
                                                            <input type="hidden" name="tdrId" value="{{ $current_tdr->id }}">
                                                            <input type="hidden" name="process" value="{{ $process }}">
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm ">
                                                                <i class="bi bi-trash"  title=" Process Delete"></i>
                                                            </button>
{{--                                                            <button class="btn btn-sm btn-outline-danger" type="button"--}}
{{--                                                                    name="btn_delete" data-bs-toggle="modal"--}}
{{--                                                                    data-bs-target="#useConfirmDelete" data-title="Delete--}}
{{--                                                                    Confirmation:  {{ $processes->processName ? $processes->processName->name : 'N/A' }}">--}}
{{--                                                                {{__('Delete')}}--}}
{{--                                                            </button>--}}
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
                                    @endif
                                @endif
                            @endforeach
                            </tbody>
                        </table>
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
                                        <th class="text-primary ps-2" style="width: 15%;">Process</th>
                                        <th class="text-primary text-center" style="width: 45%;">Processes</th>
                                        <th class="text-primary text-center" style="width: 20%;">Vendor</th>
                                    </tr>
                                </thead>
                                <tbody>
                            @foreach($processGroups as $groupKey => $group)
                                        @php
                                            // Используем processNameId как ключ для всех процессов
                                            $actualProcessNameId = $groupKey;
                                            // Отображаем название процесса
                                            $displayName = $group['process_name']->name;
                                        @endphp
                                        <tr>
                                            <td class="align-middle ">
                                                <div class="position-relative d-inline-block ms-5">
                                                <x-paper-button
                                                    text="{{ $displayName }} "
                                                    size="landscape"
                                                    width="120px"
                                                    href="{{ route('tdrs.show_group_forms', ['id' => $current_tdr->workorder->id, 'processNameId' => $actualProcessNameId, 'tdrId' => $current_tdr->id]) }}"
                                                    target="_blank"
                                                    fontSize="30px"
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
                                                                <label class="form-check-label" for="process_{{ $actualProcessNameId }}_{{ $processItem['id'] }}">
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
                                                                <label class="form-check-label" for="process_{{ $actualProcessNameId }}_{{ $processItem['id'] }}">
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

    <!-- Modal - Package Process Forms -->
    <div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center;">
                    <h5 class="modal-title mb-0" id="packageModalLabel">
                        <i class="fas fa-box"></i> Package processes forms for  {{ $current_tdr->component->name ?? 'Component' }}
                    </h5>
                    <button type="button" class="btn btn-primary" id="packageButton" style="justify-self: center;">
                        <i class="fas fa-box"></i> Create Package
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="justify-self: end;"></button>
                </div>
                <div class="modal-body">
                    <div class=" mb-3">
                        <div class="col-md-6">
                            <strong>PN:</strong> <span id="packagePartNumber">{{ $current_tdr->component->part_number ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>SN:</strong> <span id="packageSerialNumber">{{ $current_tdr->serial_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered bg-gradient shadow" id="packageProcessesTable">
                            <thead>
                                <tr>
                                    <th class="text-primary text-center" style="width: 20%;">Process Name</th>
                                    <th class="text-primary text-center" style="width: 30%;">Process</th>
                                    <th class="text-primary text-center" style="width: 30%;">Vendor</th>
                                    <th class="text-primary text-center" style="width: 20%;">Select</th>
                                </tr>
                            </thead>
                            <tbody id="packageProcessesTableBody">
                                <!-- Данные будут загружены через JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
{{--                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>--}}
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

    <!-- Подключение библиотеки SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Модульные JavaScript файлы -->
    <script src="{{ asset('js/tdr-processes/notification-handler.js') }}"></script>
    <script src="{{ asset('js/tdr-processes/group-forms-modal-handler.js') }}"></script>
    <script src="{{ asset('js/tdr-processes/sortable-handler.js') }}"></script>
    <script src="{{ asset('js/tdr-processes/delete-modal-handler.js') }}"></script>
    <script src="{{ asset('js/tdr-processes/vendor-handler.js') }}"></script>
    <script src="{{ asset('js/tdr-processes/form-link-handler.js') }}"></script>
    <script src="{{ asset('js/tdr-processes/group-process-forms-handler.js') }}"></script>
    <script src="{{ asset('js/tdr-processes/package-modal-handler.js') }}"></script>

    <!-- Главный файл инициализации с конфигурацией -->
    <script src="{{ asset('js/tdr-processes/processes-main.js') }}"></script>
    <script>
        // Конфигурация для модулей (устанавливается после загрузки processes-main.js)
        @php
            try {
                $updateOrderUrl = route("tdr-processes.update-order");
            } catch (\Exception $e) {
                $updateOrderUrl = null;
                \Log::error('Route tdr-processes.update-order not found: ' . $e->getMessage());
            }
        @endphp
        if (typeof ProcessesConfig !== 'undefined') {
            ProcessesConfig.updateOrderUrl = @if($updateOrderUrl)'{{ $updateOrderUrl }}'@else null @endif;
            ProcessesConfig.storeVendorUrl = '{{ route("vendors.store") }}';
        } else {
            console.error('ProcessesConfig is not defined. Check that processes-main.js is loaded correctly.');
        }
    </script>
@endsection
