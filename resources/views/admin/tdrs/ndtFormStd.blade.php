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
                            {{--                            {{$current_wo->description}}--}}
                            <span @if(strlen($current_wo->description) > 30) class="description-text-long"
                                @endif>{{$current_wo->description}}</span>
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
                    <div class="row fs-85 data-row-ndt manual-row" data-row-index="{{ $rowIndex }}">
                        <div class="col-1 border-l-b fs-75 details-row text-center" style="height: 32px; font-weight: bold;">
                            <!-- Пустая ячейка -->
                        </div>
                        <div class="col-3 border-l-b details-row text-center" style="height: 32px; font-weight: bold;">
                            <!-- Пустая ячейка -->
                        </div>
                        <div class="col-3 border-l-b details-row text-center" style="height: 32px; font-weight: bold;">
                            <strong>{{ $currentManual }}</strong>
                        </div>
                        <div class="col-2 border-l-b details-row text-center" style="height: 32px; font-weight: bold;">
                            <!-- Пустая ячейка -->
                        </div>
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px; font-weight: bold;">
                            <!-- Пустая ячейка -->
                        </div>
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px; font-weight: bold;">
                            <!-- Пустая ячейка -->
                        </div>
                        <div class="col-1 border-l-b-r details-row text-center" style="height: 32px; font-weight: bold;">
                            <!-- Пустая ячейка -->
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

<!-- Модальное окно настроек печати -->
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">⚙️ Print Settings</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="langToggleBtn" onclick="toggleTooltipLanguage()">
                        <span id="langToggleText">US</span>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <!-- Tables - Основная группа (не collapse) -->
                    <div class="mb-4">
                        <h5 class="mb-3" data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Настройки количества строк в таблице NDT. Строки распределяются по страницам через page-break-after. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-ru="Настройки количества строк в таблице NDT. Строки распределяются по страницам через page-break-after. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-en="NDT table row settings. Rows are distributed across pages via page-break-after. Settings are applied automatically on page load.">
                            📊 Tables
                        </h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="ndtTableRows" class="form-label" data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="Максимальное количество строк в таблице NDT на одной странице. По умолчанию: 16 строк. Используется для всех страниц формы. Если все компоненты из одного manual, manual-row не учитывается. Если компоненты из разных manual, manual-row учитываются в лимите."
                                        data-tooltip-ru="Максимальное количество строк в таблице NDT на одной странице. По умолчанию: 16 строк. Используется для всех страниц формы. Если все компоненты из одного manual, manual-row не учитывается. Если компоненты из разных manual, manual-row учитываются в лимите."
                                        data-tooltip-en="Maximum number of rows in NDT table per page. Default: 16 rows. Used for all pages of the form. If all components are from one manual, manual-row is not counted. If components are from different manuals, manual-row is counted in the limit.">
                                    NDT Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="ndtTableRows" name="ndtTableRows"
                                           min="1" max="100" step="1" value="16">
                                </div>
                            </div>
                        </div>

                        <!-- Table Setting (collapse) -->
                        <div class="accordion mb-3" id="tableSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="tableSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#tableSettingsCollapse" aria-expanded="false" 
                                            aria-controls="tableSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right" 
                                              title="Дополнительные настройки таблицы: ширина, отступы контейнера."
                                              data-tooltip-ru="Дополнительные настройки таблицы: ширина, отступы контейнера."
                                              data-tooltip-en="Additional table settings: width, container padding and margins.">
                                            Table Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="tableSettingsCollapse" class="accordion-collapse collapse" 
                                     aria-labelledby="tableSettingsHeading" data-bs-parent="#tableSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMaxWidth" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 920px для NDT формы. Увеличьте, если таблица слишком узкая."
                                                        data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 920px для NDT формы. Увеличьте, если таблица слишком узкая."
                                                        data-tooltip-en="Maximum width of the table container in pixels. Recommended value: 920px for NDT form. Increase if the table is too narrow.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                           min="500" max="2000" step="10" value="920">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerPadding" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Внутренние отступы контейнера (пространство между границей контейнера и содержимым). По умолчанию: 5px."
                                                        data-tooltip-ru="Внутренние отступы контейнера (пространство между границей контейнера и содержимым). По умолчанию: 5px."
                                                        data-tooltip-en="Container inner padding (space between container border and content). Default: 5px.">
                                                    Padding (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                           min="0" max="50" step="1" value="5">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginLeft" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Отступ контейнера с таблицей от левого края. По умолчанию: 10px."
                                                        data-tooltip-ru="Отступ контейнера с таблицей от левого края. По умолчанию: 10px."
                                                        data-tooltip-en="Table container margin from left edge. Default: 10px.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMarginLeft" name="containerMarginLeft"
                                                           min="0" max="50" step="1" value="10">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginRight" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Отступ контейнера с таблицей от правого края. По умолчанию: 10px."
                                                        data-tooltip-ru="Отступ контейнера с таблицей от правого края. По умолчанию: 10px."
                                                        data-tooltip-en="Table container margin from right edge. Default: 10px.">
                                                    Right Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMarginRight" name="containerMarginRight"
                                                           min="0" max="50" step="1" value="10">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Page Setting (collapse) -->
                    <div class="mb-4">
                        <div class="accordion" id="pageSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="pageSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#pageSettingsCollapse" aria-expanded="false" 
                                            aria-controls="pageSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right" 
                                              title="Настройки страницы: ширина, высота, поля и отступы. Влияют на отступы при печати и позиционирование контента."
                                              data-tooltip-ru="Настройки страницы: ширина, высота, поля и отступы. Влияют на отступы при печати и позиционирование контента."
                                              data-tooltip-en="Page settings: width, height, margins and padding. Affect print margins and content positioning.">
                                            Page Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="pageSettingsCollapse" class="accordion-collapse collapse" 
                                     aria-labelledby="pageSettingsHeading" data-bs-parent="#pageSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="bodyWidth" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Ширина основного контента в процентах от ширины страницы. 98% - стандартное значение, оставляет небольшие поля по бокам."
                                                        data-tooltip-ru="Ширина основного контента в процентах от ширины страницы. 98% - стандартное значение, оставляет небольшие поля по бокам."
                                                        data-tooltip-en="Main content width as percentage of page width. 98% - standard value, leaves small margins on the sides.">
                                                    Width (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyWidth" name="bodyWidth"
                                                           min="50" max="100" step="1" value="98">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="bodyHeight" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Высота основного контента в процентах от высоты страницы. 99% - стандартное значение."
                                                        data-tooltip-ru="Высота основного контента в процентах от высоты страницы. 99% - стандартное значение."
                                                        data-tooltip-en="Main content height as percentage of page height. 99% - standard value.">
                                                    Height (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyHeight" name="bodyHeight"
                                                           min="50" max="100" step="1" value="99">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="pageMargin" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Отступ от краев страницы при печати. Рекомендуемое значение: 1mm. Увеличьте, если контент обрезается принтером."
                                                        data-tooltip-ru="Отступ от краев страницы при печати. Рекомендуемое значение: 1mm. Увеличьте, если контент обрезается принтером."
                                                        data-tooltip-en="Margin from page edges when printing. Recommended value: 1mm. Increase if content is cut off by the printer.">
                                                    Margin (mm)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="pageMargin" name="pageMargin"
                                                           min="0" max="50" step="0.5" value="1">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="bodyMarginLeft" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Горизонтальный отступ основного контента от левого края. Используется для точной настройки позиционирования."
                                                        data-tooltip-ru="Горизонтальный отступ основного контента от левого края. Используется для точной настройки позиционирования."
                                                        data-tooltip-en="Horizontal margin of main content from left edge. Used for precise positioning.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft"
                                                           min="0" max="50" step="1" value="2">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Setting (collapse) -->
                    <div class="mb-4">
                        <div class="accordion" id="footerSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="footerSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#footerSettingsCollapse" aria-expanded="false" 
                                            aria-controls="footerSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right" 
                                              title="Настройки нижнего колонтитула формы. Колонтитул содержит номер формы, ревизию и общее количество компонентов."
                                              data-tooltip-ru="Настройки нижнего колонтитула формы. Колонтитул содержит номер формы, ревизию и общее количество компонентов."
                                              data-tooltip-en="Form footer settings. Footer contains form number, revision and total component count.">
                                            Footer Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="footerSettingsCollapse" class="accordion-collapse collapse" 
                                     aria-labelledby="footerSettingsHeading" data-bs-parent="#footerSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="footerWidth" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Ширина колонтитула в пикселях. 800px - стандартное значение. Увеличьте, если текст в колонтитуле не помещается."
                                                        data-tooltip-ru="Ширина колонтитула в пикселях. 800px - стандартное значение. Увеличьте, если текст в колонтитуле не помещается."
                                                        data-tooltip-en="Footer width in pixels. 800px - standard value. Increase if footer text doesn't fit.">
                                                    Width on pg (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="400" max="1200" step="10" value="800">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerFontSize" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Размер шрифта текста в колонтитуле. 10px - стандартное значение. Увеличьте для лучшей читаемости."
                                                        data-tooltip-ru="Размер шрифта текста в колонтитуле. 10px - стандартное значение. Увеличьте для лучшей читаемости."
                                                        data-tooltip-en="Footer text font size. 10px - standard value. Increase for better readability.">
                                                    Font Size (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerFontSize" name="footerFontSize"
                                                           min="6" max="20" step="0.5" value="10">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerPadding" class="form-label" data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '3px 3px' означает 3px сверху/снизу и 3px слева/справа."
                                                        data-tooltip-ru="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '3px 3px' означает 3px сверху/снизу и 3px слева/справа."
                                                        data-tooltip-en="Footer inner padding in CSS format (vertical horizontal). Example: '3px 3px' means 3px top/bottom and 3px left/right.">
                                                    Padding
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                           placeholder="3px 3px" value="3px 3px">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="resetPrintSettings()">Reset to Default</button>
                <button type="button" class="btn btn-primary" onclick="savePrintSettings()">Save Settings</button>
            </div>
        </div>
    </div>
</div>

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
    // Ключ для сохранения настроек печати
    const PRINT_SETTINGS_KEY = 'ndtFormStd_print_settings';
    const TOOLTIP_LANG_KEY = 'ndtFormStd_tooltip_lang';
    
    // Настройки по умолчанию
    const defaultSettings = {
        pageMargin: '1mm',
        bodyWidth: '98%',
        bodyHeight: '99%',
        bodyMarginLeft: '2px',
        containerMaxWidth: '920px',
        containerPadding: '5px',
        containerMarginLeft: '10px',
        containerMarginRight: '10px',
        footerWidth: '800px',
        footerFontSize: '10px',
        footerPadding: '3px 3px',
        ndtTableRows: '16'
    };
    
    // Загрузка настроек из localStorage
    function loadPrintSettings() {
        const saved = localStorage.getItem(PRINT_SETTINGS_KEY);
        if (saved) {
            try {
                return JSON.parse(saved);
            } catch (e) {
                console.error('Ошибка загрузки настроек:', e);
                return defaultSettings;
            }
        }
        return defaultSettings;
    }
    
    // Сохранение настроек в localStorage
    window.savePrintSettings = function() {
        try {
            const getValue = function(id, defaultValue, suffix = '') {
                const element = document.getElementById(id);
                if (element) {
                    return element.value + suffix;
                }
                return defaultValue;
            };

            const settings = {
                pageMargin: getValue('pageMargin', '1', 'mm'),
                bodyWidth: getValue('bodyWidth', '98', '%'),
                bodyHeight: getValue('bodyHeight', '99', '%'),
                bodyMarginLeft: getValue('bodyMarginLeft', '2', 'px'),
                containerMaxWidth: getValue('containerMaxWidth', '920', 'px'),
                containerPadding: getValue('containerPadding', '5', 'px'),
                containerMarginLeft: getValue('containerMarginLeft', '10', 'px'),
                containerMarginRight: getValue('containerMarginRight', '10', 'px'),
                footerWidth: getValue('footerWidth', '800', 'px'),
                footerFontSize: getValue('footerFontSize', '10', 'px'),
                footerPadding: getValue('footerPadding', '3px 3px', ''),
                ndtTableRows: getValue('ndtTableRows', '16', '')
            };

            localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);
            applyTableRowLimits(settings);
            
            // Убираем фокус с активного элемента перед закрытием модального окна
            if (document.activeElement && document.activeElement.blur) {
                document.activeElement.blur();
            }
            
            // Закрываем модальное окно
            const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
            if (modal) {
                modal.hide();
            }
            
            alert('Settings saved successfully!');
        } catch (e) {
            console.error('Ошибка сохранения настроек:', e);
            alert('Error saving settings');
        }
    };
    
    // Применение CSS переменных
    function applyPrintSettings(settings) {
        const root = document.documentElement;
        root.style.setProperty('--print-page-margin', settings.pageMargin || defaultSettings.pageMargin);
        root.style.setProperty('--print-body-width', settings.bodyWidth || defaultSettings.bodyWidth);
        root.style.setProperty('--print-body-height', settings.bodyHeight || defaultSettings.bodyHeight);
        root.style.setProperty('--print-body-margin-left', settings.bodyMarginLeft || defaultSettings.bodyMarginLeft);
        root.style.setProperty('--container-max-width', settings.containerMaxWidth || defaultSettings.containerMaxWidth);
        root.style.setProperty('--container-padding', settings.containerPadding || defaultSettings.containerPadding);
        root.style.setProperty('--container-margin-left', settings.containerMarginLeft || defaultSettings.containerMarginLeft);
        root.style.setProperty('--container-margin-right', settings.containerMarginRight || defaultSettings.containerMarginRight);
        root.style.setProperty('--print-footer-width', settings.footerWidth || defaultSettings.footerWidth);
        root.style.setProperty('--print-footer-font-size', settings.footerFontSize || defaultSettings.footerFontSize);
        root.style.setProperty('--print-footer-padding', settings.footerPadding || defaultSettings.footerPadding);
    }
    
    // Загрузка настроек в форму
    function loadSettingsToForm(settings) {
        const elements = {
            'pageMargin': { suffix: '', default: '1' },
            'bodyWidth': { suffix: '', default: '98' },
            'bodyHeight': { suffix: '', default: '99' },
            'bodyMarginLeft': { suffix: '', default: '2' },
            'containerMaxWidth': { suffix: '', default: '920' },
            'containerPadding': { suffix: '', default: '5' },
            'containerMarginLeft': { suffix: '', default: '10' },
            'containerMarginRight': { suffix: '', default: '10' },
            'footerWidth': { suffix: '', default: '800' },
            'footerFontSize': { suffix: '', default: '10' },
            'footerPadding': { suffix: '', default: '3px 3px' },
            'ndtTableRows': { suffix: '', default: '16' }
        };

        Object.keys(elements).forEach(function(id) {
            const element = document.getElementById(id);
            if (element) {
                const value = settings[id] || elements[id].default;
                if (id === 'pageMargin') {
                    element.value = parseFloat(value) || elements[id].default;
                } else if (id === 'footerPadding') {
                    element.value = value;
                } else {
                    element.value = parseInt(value) || elements[id].default;
                }
            }
        });
    }
    
    // Применение ограничений строк таблицы - создание физических страниц
    function applyTableRowLimits(settings) {
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
    }
    
    // Сброс настроек к значениям по умолчанию
    window.resetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
            setTimeout(function() {
                applyTableRowLimits(defaultSettings);
            }, 50);
            alert('Settings reset to default values!');
        }
    };
    
    // Функция переключения языка tooltips
    window.toggleTooltipLanguage = function() {
        const modal = document.getElementById('printSettingsModal');
        if (!modal) return;
        
        let currentLang = localStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
        currentLang = currentLang === 'ru' ? 'en' : 'ru';
        localStorage.setItem(TOOLTIP_LANG_KEY, currentLang);
        
        updateTooltipsLanguage(modal, currentLang);
        
        const langText = document.getElementById('langToggleText');
        if (langText) {
            langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
        }
    };
    
    // Функция обновления языка всех tooltips
    function updateTooltipsLanguage(container, lang) {
        const tooltipElements = container.querySelectorAll('[data-bs-toggle="tooltip"]');
        
        tooltipElements.forEach(function(el) {
            const existingTooltip = bootstrap.Tooltip.getInstance(el);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            
            const ruText = el.getAttribute('data-tooltip-ru');
            const enText = el.getAttribute('data-tooltip-en');
            
            if (lang === 'ru' && ruText) {
                el.setAttribute('title', ruText);
            } else if (lang === 'en' && enText) {
                el.setAttribute('title', enText);
            }
            
            new bootstrap.Tooltip(el);
        });
    }
    
    // Функция инициализации языка tooltips
    function initTooltipLanguage(modal) {
        const currentLang = localStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
        const langText = document.getElementById('langToggleText');
        if (langText) {
            langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
        }
        
        setTimeout(function() {
            updateTooltipsLanguage(modal, currentLang);
        }, 100);
    }
    
    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        const settings = loadPrintSettings();
        applyPrintSettings(settings);
        loadSettingsToForm(settings);

        // Применяем ограничения строк при загрузке
        setTimeout(function() {
            applyTableRowLimits(settings);
        }, 300);

        // Загружаем настройки в форму при открытии модального окна
        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                const currentSettings = loadPrintSettings();
                loadSettingsToForm(currentSettings);
                initTooltipLanguage(modal);
            });
        }
    });
    
    // Применяем ограничения строк перед печатью
    window.addEventListener('beforeprint', function() {
        console.log('Событие beforeprint - применение ограничений строк перед печатью');
        const settings = loadPrintSettings();
        setTimeout(function() {
            applyTableRowLimits(settings);
        }, 10);
    });
</script>

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
