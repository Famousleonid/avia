<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NDT Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        :root {
            --container-max-width: 920px;
            --container-padding: 5px;
            --container-margin-left: 10px;
            --container-margin-right: 10px;
            --print-page-margin: 1mm;
            --print-body-height: 99%;
            --print-body-width: 98%;
            --print-body-margin-left: 2px;
            --print-footer-width: 800px;
            --print-footer-font-size: 10px;
            --print-footer-padding: 3px 3px;
        }

        .container-fluid {
            max-width: var(--container-max-width);
            height: 98%;
            padding: var(--container-padding);
            margin-left: var(--container-margin-left);
            margin-right: var(--container-margin-right);
        }

        /* Класс для разрыва страницы */
        .page-break-after {
            page-break-after: always !important;
            break-after: page !important; /* для современных браузеров */
        }

        /* Визуальный индикатор разрыва страницы на экране */
        .page-break-after::after {
            content: '';
            display: block;
            height: 2px;
            background: linear-gradient(to right, transparent, #ff0000 50%, transparent);
            margin: 10px 0;
            width: 100%;
        }

        /* Разделитель страниц - работает и на экране, и при печати */
        .page-break-divider {
            page-break-after: always !important;
            break-after: page !important;
            width: 100%;
            height: 0;
            margin: 20px 0;
            padding: 0;
            border-top: 2px dashed #ff0000;
            position: relative;
        }

        .page-break-divider::before {
            content: '--- PAGE BREAK ---';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: -10px;
            background: white;
            padding: 0 10px;
            color: #ff0000;
            font-size: 12px;
            font-weight: bold;
        }

        @media print {
            .page-break-after::after {
                display: none; /* Скрываем визуальный индикатор при печати */
            }
            .page-break-divider {
                border-top: none;
                margin: 0;
                height: 0;
            }
            .page-break-divider::before {
                display: none;
            }
        }

        /* Скрываем строки сверх лимита (видно на экране и при печати) */
        .print-hide-row {
            display: none !important;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: letter;
                margin: var(--print-page-margin);
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: var(--print-body-height);
                width: var(--print-body-width);
                margin-left: var(--print-body-margin-left);
                padding: 0;
            }

            /* Отключаем разрывы страниц внутри элементов */
            table, h1, p {
                page-break-inside: avoid;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print {
                display: none;
            }

            /* Колонтитул внизу страницы */
            footer {
                position: fixed;
                bottom: 0;
                width: var(--print-footer-width);
                text-align: center;
                font-size: var(--print-footer-font-size);
                background-color: #fff;
                padding: var(--print-footer-padding);
            }

            /* Обрезка контента и размещение на одной странице */
            .container {
                max-height: 100vh;
                overflow: hidden;
            }
        }

        .border-all {
            border: 1px solid black;
        }
        .border-all-b {
            border: 2px solid black;
        }

        .border-l-t-r {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b-r {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-lll-b-r {
            border-left: 8px  solid lightgrey;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b-rrr {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 5px solid black;
        }
        .border-l-b {
            border-left: 1px solid black;
            border-bottom: 1px solid black;

        }
        .border-t-r {
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-b {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-t-b {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-ll-bb {
            border-left: 2px solid black;
            border-bottom: 2px solid black;

        }
        .border-ll-bb-rr {
            border-left: 2px solid black;
            border-bottom: 2px solid black;
            border-right: 2px solid black;
        }
        .border-bb {
            border-bottom: 2px solid black;
        }
        .border-b {
            border-bottom: 1px solid black;
        }
        .border-t-r-b {
            border-top: 1px solid black;
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-r-b {

            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .text-center {
            text-align: center;

        }

        .text-black {
            color: #000;
        }

        /*.p-1, .p-2, .p-3, .p-4 {*/
        /*    padding: 0.25rem;*/
        /*    padding: 0.5rem;*/
        /*    padding: 0.75rem;*/
        /*    padding: 1rem;*/
        /*}*/

        .topic-header {
            width: 100px;
        }

        .topic-content {
            width: 600px;
        }

        .topic-content-2 {
            width: 701px;
        }

        .hrs-topic, .trainer-init {
            width: 100px;
        }
        .hrs-topic-1,.trainer-init-1 {
            width: 98px;
        }
        .trainer-init-1 {
            width: 99px;
        }
        .fs-7 {
            font-size: 0.9rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-85 {
            font-size: 0.85rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-9 {
            font-size: 0.4rem; /* или любое другое подходящее значение */
        }

        .process-text-long {
            font-size: 0.8rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: middle;
        }
        .description-text-long {
            font-size: 0.9rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: top;
        }
        .header-page .component-name-value { font-size: var(--component-name-font-size, 12px) !important; }
        .header-page .component-name-value[data-long="1"] { line-height: 1.1; letter-spacing: -0.3px; }
        /* ITEM No. — уменьшенный межстрочный интервал */
        .data-row-ndt > div:first-child,
        .table-header .row > div:first-child { line-height: 1.1; }
        .details-row {
            display: flex;
            justify-content: center;
            align-items: center; /* Выравнивание элементов по вертикали */
            /*height: 32px; !* Фиксированная высота строки *!*/
        }
        .details-cell {
            flex-grow: 1; /* Позволяет колонкам растягиваться и занимать доступное пространство */
            display: flex;
            justify-content: center; /* Центрирование содержимого по горизонтали */
            align-items: center; /* Центрирование содержимого по вертикали */
            border: 1px solid black; /* Границы для наглядности */
        }
        .check-icon {
            width: 24px; /* Меньший размер изображения */
            height: auto;
            margin: 0 5px; /* Отступы вокруг изображения */
        }
    </style>
</head>
<body>
<!-- Кнопка для печати -->
<div class="text-start m-1 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        ⚙️ Print Settings
    </button>
</div>
<div class="container-fluid">


    <div class="header-page">
        <div class="row">
            <div class="col-4">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 180px; margin: 6px 10px 0;">
            </div>
            <div class="col-8">
                <h2 class="p-2 mt-3 text-black text-"><strong>NDT PROCESS SHEET</strong></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 fs-7 pt-2 border-b"> <strong>
                            <span class="component-name-value" @if(strlen($current_wo->description) > 30) data-long="1" @endif>{{$current_wo->description}}</span>
                        </strong> </div>

                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong> PART NUMBER:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"> <strong>{{$current_wo->unit->part_number}}</strong> </div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>WORK ORDER No:</strong> </div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>W{{$current_wo->number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>SERIAL No:</strong> </div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>{{$current_wo->serial_number}}</strong></div>
                </div>

            </div>
            <div class="col-6">
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>DATE:</strong></div>
                    <div class="col-8 pt-2 border-b"></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>RO No:</strong></div>
                    <div class="col-8 pt-2 border-b"></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>VENDOR:</strong></div>
                    <div class="col-8 pt-2 border-b">Skyservice</div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-5">
                <div class="text-start"><strong>MAGNETIC PARTICLE AS PER:</strong></div>
                <div class="row " style="height: 26px">

                    <div class="col-1">#1</div>
                    <div class="col-11 border-b">
                        @if(!empty($ndt_processes) && count($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == $ndt1_name_id)
                                    <span @if(strlen($process->process) > 25) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                    {{--                                    {{ $process->process ?? '' }}--}}
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="text-start"><strong>LIQUID/FLUID PENETRANT AS PER:</strong></div>

                <div class="row " style="min-height: 26px">
                    <div class="col-1">#4</div>
                    <div class="col-11 border-b">
                        @if(!empty($ndt_processes) && count($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == $ndt4_name_id)
                                    <span @if(strlen($process->process) > 25) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                    {{--                                    {{ $process->process ?? '' }}--}}
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="text-start"><strong>ULTRASOUND AS PER:</strong></div>

                <div class="row " style="height: 26px">
                    <div class="col-1">#7</div>
                    <div class="col-11 border-b"></div>
                </div>
            </div>
            <div class="col-3 mt-3">
                <div class="row mt-2" style="height: 26px">
                    <div class="col-2">#2</div>
                    <div class="col-10 border-b">
                        {{--                        @foreach($ndt_processes as $process)--}}
                        {{--                            @if($process->process_names_id == $ndt2_name_id)--}}
                        {{--                                {{$process->process}}--}}
                        {{--                            @endif--}}
                        {{--                        @endforeach--}}
                    </div>
                </div>
                <div class="row mt-4" style="height: 26px">
                    <div class="col-2">#5</div>
                    <div class="col-10 border-b">
                        @if(!empty($ndt_processes) && count($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == $ndt5_name_id)
                                    {{ $process->process ?? '' }}
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="text-end mt-4"><strong>CMM No:</strong></div>

            </div>
            <div class="col-4 mt-3">
                <div class="row mt-2" style="height: 26px">
                    <div class="col-2 text-end">#3</div>
                    <div class="col-10 border-b">
                        {{--                        @foreach($ndt_processes as $process)--}}
                        {{--                            @if($process->process_names_id == $ndt3_name_id)--}}
                        {{--                                {{$process->process}}--}}
                        {{--                            @endif--}}
                        {{--                        @endforeach--}}
                    </div>
                </div>
                <div class="text-start"><strong>EDDY CURRENT AS PER:</strong></div>
                <div class="row " style="height: 26px">
                    <div class="col-2 text-end">#6</div>
                    <div class="col-10 border-b">
                        @if(!empty($ndt_processes) && count($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == $ndt6_name_id)
                                    {{ $process->process ?? '' }}
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="row border-all mt-2" style="height: 56px">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h6 class="text-center mt-3"><strong> {{substr($manual->number, 0, 8)}}</strong></h6>
                        @endif
                    @endforeach

                </div>
            </div>
        </div>
    </div>
    <div class="page table-header">
        <div class="row mt-2 ">
            <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-7">ITEM No.</h6></div>
            <div class="col-3 border-l-t-b details-row text-center"><h6  class="fs-7">Part No</h6> </div>
            <div class="col-3 border-l-t-b details-row text-center"><h6  class="fs-7">DESCRIPTION</h6></div>
            <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6  class="fs-75">PROCESS No.</h6> </div>
            <div class="col-1 border-l-t-b details-row  text-center"><h6  class="fs-7">QTY</h6> </div>
            <div class="col-1 border-l-t-b details-row  text-center"><h6  class="fs-7">ACCEPT</h6> </div>
            <div class="col-1 border-all details-row  text-center"><h6  class="fs-7">REJECT</h6> </div>
        </div>
    </div>

    @php
        // Все компоненты передаются без разбиения на страницы
        // Разбиение происходит на фронтенде через JavaScript
        $previousManual = null;
    @endphp

    {{-- Все компоненты выводятся в одном контейнере - разбиение на страницы через JavaScript --}}
    <div class="all-rows-container">
        @php
                $rowIndex = 1;
            @endphp

        @foreach($ndt_components as $component)
                @php
                    $currentManual = $component->manual ?? null;
                    // Если manual изменился и не пустой, вставляем строку с manual
                    $shouldInsertManualRow = ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);
                @endphp

                @if($shouldInsertManualRow)
                    {{-- Строка с Manual --}}
                    <div class="fs-85 data-row-ndt manual-row" data-row-index="{{ $rowIndex }}">
                        <div class=" border-l-b-r details-row text-center" style="height: 32px; font-weight: bold;">
                            <strong>{{ $currentManual }}</strong>
                        </div>
                    </div>
                    @php $rowIndex++; @endphp
                @endif

                <div class="row fs-85 data-row-ndt" data-row-index="{{ $rowIndex }}">
                    <div class="col-1 border-l-b fs-75 details-row text-center" style="height: 32px">
                        {{ $component->ipl_num }}
                    </div>
                    <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                        {{ $component->part_number }}
                    </div>
                    <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                        {{ $component->name }}
                    </div>
                    <div class="col-2 border-l-b details-row text-center" style="height: 32px">
                        {{ $component->process_name }}
                    </div>
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                        {{ $component->qty }}
                    </div>
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b-r details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                </div>
                @php
                    $rowIndex++;
                    $previousManual = $currentManual;
                @endphp
            @endforeach
                    </div>
    {{-- Пустые строки будут генерироваться на фронтенде через JavaScript --}}

        <footer>
            <div class="row fs-85" style="width: 100%; padding: 5px 0;">
                <div class="col-3 text-start">
                    {{__('Form #016')}}
                </div>
                <div class="col-3 text-center">
                {{__('Page')}} <span class="page-number">1</span> {{__('of')}} <span class="total-pages">1</span>
                </div>
                <div class="col-6 text-end pe-4 ">
                    {{__('Rev#0, 15/Dec/2012   ')}}
                    <br>
                    @php
                        $totalQty = array_sum(array_map(function($item) { return $item->qty ?? 0; }, $ndt_components));
                        $mpiQty = array_sum(array_map(function($item) { return $item->qty ?? 0; }, array_filter($ndt_components, function($item) {
                            return strpos($item->process_name ?? '', '1') !== false;
                        })));
                        $fpiQty = array_sum(array_map(function($item) { return $item->qty ?? 0; }, array_filter($ndt_components, function($item) {
                            return strpos($item->process_name ?? '', '1') === false;
                        })));
                    @endphp
                    {{__('Total QTY:')}} {{ $totalQty }}
                    ( {{__('MPI:')}} {{ $mpiQty }} {{__(' ; ')}}
                    {{__('FPI:')}} {{ $fpiQty }} )
                </div>
            </div>
        </footer>
</div>

@php $tdrFormConfig = config('tdr_forms.ndtFormStd'); @endphp
@include('shared.tdr-forms._print-settings-modal', ['formType' => 'ndtFormStd', 'formConfig' => $tdrFormConfig])

<!-- Bootstrap JS для работы модального окна -->
<script>
    if (typeof window.bootstrapLoaded === 'undefined') {
        window.bootstrapLoaded = true;
        const script = document.createElement('script');
        script.src = "{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}";
        script.async = true;
        document.head.appendChild(script);
    }
</script>

<script>
    // NDT-специфичная логика лимитов строк (используется shared scripts)
    window.tdrFormApplyTableRowLimits = function(settings) {
        const ndtMaxRows = parseInt(settings.ndtTableRows) || 16;
        console.log('Применение ограничений строк NDT:', { ndtMaxRows, settings });

        const allRowsContainer = document.querySelector('.all-rows-container');
        if (!allRowsContainer) {
            console.warn('Контейнер .all-rows-container не найден!');
            return;
        }

        // Удаляем все созданные ранее страницы (кроме первой)
        document.querySelectorAll('.data-page[data-page-index]').forEach(function(page) {
            const pageIndex = page.getAttribute('data-page-index');
            if (pageIndex && parseInt(pageIndex) > 1) {
                page.remove();
            }
        });

        // Удаляем все разделители и пустые строки
        document.querySelectorAll('.page-break-divider').forEach(function(el) {
            el.remove();
        });
        document.querySelectorAll('.page-break-after').forEach(function(el) {
            el.classList.remove('page-break-after');
        });
        document.querySelectorAll('.all-rows-container .data-row-ndt.empty-row').forEach(function(row) {
            row.remove();
        });

        // Собираем все строки из контейнера
        const allRows = Array.from(allRowsContainer.querySelectorAll('.data-row-ndt:not(.empty-row)'));

        // Разделяем на manual-row и data-rows
        const manualRows = allRows.filter(function(row) {
            return row.classList.contains('manual-row');
        });
        const dataRows = allRows.filter(function(row) {
            return !row.classList.contains('manual-row');
        });

        const hasManualRows = manualRows.length > 0;
        console.log('Найдено manual-row:', hasManualRows);
        console.log('Найдено строк с данными:', dataRows.length);

        let totalRows;
        let rowsToProcess;

        if (hasManualRows) {
            // Случай с manual-row: считаем все строки (manual + data)
            totalRows = allRows.length;
            rowsToProcess = allRows;
        } else {
            // Случай без manual-row: считаем только data-rows
            totalRows = dataRows.length;
            rowsToProcess = dataRows;
        }

        // Вычисляем количество страниц
        const totalPages = Math.max(1, Math.ceil(totalRows / ndtMaxRows));
        console.log('Всего строк:', totalRows, ', Лимит на странице:', ndtMaxRows, ', Создано страниц:', totalPages);

        // Находим элементы для копирования
        const originalHeader = document.querySelector('.header-page');
        const originalTableHeader = document.querySelector('.table-header');
        const originalFooter = document.querySelector('footer');
        const containerFluid = document.querySelector('.container-fluid');

        // Скрываем строки, которые не на первой странице
        rowsToProcess.forEach(function(row, index) {
            if (index < ndtMaxRows) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Создаём дополнительные страницы (начиная со второй)
        for (let pageIndex = 1; pageIndex < totalPages; pageIndex++) {
            const startIndex = pageIndex * ndtMaxRows;
            const endIndex = Math.min(startIndex + ndtMaxRows, rowsToProcess.length);
            const pageRows = rowsToProcess.slice(startIndex, endIndex);

            // Создаём контейнер для новой страницы (как container-fluid)
            const pageContainer = document.createElement('div');
            pageContainer.className = 'container-fluid';

            // Создаём новую страницу
            const pageDiv = document.createElement('div');
            pageDiv.className = 'page data-page';
            pageDiv.setAttribute('data-page-index', pageIndex + 1);
            pageDiv.style.pageBreakBefore = 'always';

            // Копируем header
            if (originalHeader) {
                const headerClone = originalHeader.cloneNode(true);
                pageDiv.appendChild(headerClone);
            }

            // Копируем table-header
            if (originalTableHeader) {
                const tableHeaderClone = originalTableHeader.cloneNode(true);
                pageDiv.appendChild(tableHeaderClone);
            }

            // Создаём контейнер для строк этой страницы (как all-rows-container)
            const rowsContainer = document.createElement('div');
            rowsContainer.className = 'all-rows-container';

            // Клонируем строки для этой страницы
            pageRows.forEach(function(row) {
                const rowClone = row.cloneNode(true);
                rowClone.style.display = '';
                rowsContainer.appendChild(rowClone);
            });

            // Добавляем пустые строки на последней странице, если нужно
            if (pageIndex === totalPages - 1) {
                const rowsOnLastPage = totalRows % ndtMaxRows;
                const emptyRowsNeeded = rowsOnLastPage === 0 ? 0 : (ndtMaxRows - rowsOnLastPage);

                if (emptyRowsNeeded > 0) {
                    for (let i = 0; i < emptyRowsNeeded; i++) {
                        const emptyRow = document.createElement('div');
                        emptyRow.className = 'row fs-85 data-row-ndt empty-row';
                        emptyRow.innerHTML = `
                            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-2 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
                        `;
                        rowsContainer.appendChild(emptyRow);
                    }
                    console.log('Добавлено пустых строк на последнюю страницу:', emptyRowsNeeded);
                }
            }

            pageDiv.appendChild(rowsContainer);

            // Копируем footer с правильной нумерацией
            if (originalFooter) {
                const footerClone = originalFooter.cloneNode(true);
                const pageNumberEl = footerClone.querySelector('.page-number');
                const totalPagesEl = footerClone.querySelector('.total-pages');
                if (pageNumberEl) {
                    pageNumberEl.textContent = pageIndex + 1;
                }
                if (totalPagesEl) {
                    totalPagesEl.textContent = totalPages;
                }
                pageDiv.appendChild(footerClone);
            }

            // Добавляем pageDiv в pageContainer
            pageContainer.appendChild(pageDiv);

            // Вставляем страницу после container-fluid
            if (containerFluid && containerFluid.parentNode) {
                containerFluid.parentNode.insertBefore(pageContainer, containerFluid.nextSibling);
            } else {
                document.body.appendChild(pageContainer);
            }
        }

        // Добавляем пустые строки на первую страницу, если это единственная страница и нужно
        if (totalPages === 1) {
            const rowsOnLastPage = totalRows % ndtMaxRows;
            const emptyRowsNeeded = rowsOnLastPage === 0 ? 0 : (ndtMaxRows - rowsOnLastPage);

            if (emptyRowsNeeded > 0 && allRowsContainer) {
                const lastDataRow = allRowsContainer.querySelector('.data-row-ndt:not(.empty-row):last-of-type');
                if (lastDataRow) {
                    for (let i = 0; i < emptyRowsNeeded; i++) {
                        const emptyRow = document.createElement('div');
                        emptyRow.className = 'row fs-85 data-row-ndt empty-row';
                        emptyRow.innerHTML = `
                            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-2 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                            <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
                        `;
                        allRowsContainer.appendChild(emptyRow);
                    }
                    console.log('Добавлено пустых строк на первую страницу:', emptyRowsNeeded);
                }
            }
        }

        // Обновляем нумерацию в оригинальном footer
        if (originalFooter) {
            const pageNumberEl = originalFooter.querySelector('.page-number');
            const totalPagesEl = originalFooter.querySelector('.total-pages');
            if (pageNumberEl) {
                pageNumberEl.textContent = '1';
            }
            if (totalPagesEl) {
                totalPagesEl.textContent = totalPages;
            }
        }

        console.log('Создано страниц:', totalPages);
    };
</script>
<script src="{{ asset('js/main.js') }}"></script>
@include('shared.tdr-forms._scripts', ['formType' => 'ndtFormStd', 'formConfig' => $tdrFormConfig])

<!-- Общие модули -->
<script src="{{ asset('js/tdrs/forms/common/multi-page-handler.js') }}"></script>

<!-- Переиспользуемые модули из tdr-processes -->
<script src="{{ asset('js/tdr-processes/processes-form/row-manager.js') }}"></script>

<!-- Модули для NDT Standard формы -->
<script src="{{ asset('js/tdrs/forms/ndt-std/chartjs-patcher.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/ndt-std/ndt-std-row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/ndt-std/ndt-std-form-main.js') }}"></script>
</body>
</html>
