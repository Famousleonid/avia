<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$process_name->process_sheet_name ?? $process_name->name ?? 'Wo Bushing'}} Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        :root {
            --container-max-width: 920px;
            --container-padding: 5px;
            --container-margin-left: 10px;
            --container-margin-right: 10px;
            --print-page-margin: 1mm;
            --print-body-height: 99%;
            --print-body-width: 98%;
            --print-body-margin-left: 2px;
            --table-font-size: 0.85rem;
            --print-footer-width: 800px;
            --print-footer-font-size: 10px;
            --print-footer-padding: 3px 3px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: var(--container-max-width);
            height: 98%;
            padding: var(--container-padding);
            margin-left: var(--container-margin-left);
            margin-right: var(--container-margin-right);
        }

        /* Применение font-size ко всей таблице */
        .table-header,
        .table-header h6,
        .ndt-data-container,
        .ndt-data-container .data-row-ndt,
        .ndt-data-container .data-row-ndt h6,
        .data-page,
        .data-page .data-row,
        .data-page .data-row h6 {
            font-size: var(--table-font-size) !important;
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

            /* Скрываем строки сверх лимита */
            .print-hide-row {
                display: none !important;
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

            /* Применение font-size ко всей таблице при печати */
            .table-header,
            .table-header h6,
            .ndt-data-container,
            .ndt-data-container .data-row-ndt,
            .ndt-data-container .data-row-ndt h6,
            .data-page,
            .data-page .data-row,
            .data-page .data-row h6 {
                font-size: var(--table-font-size) !important;
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

        /* Стили для длинного текста процесса */
        .process-text-long {
            font-size: 0.85em;
            line-height: 1.1;
            letter-spacing: -0.5px;
            /*transform: scale(0.9);*/
            transform-origin: left;
            display: inline-block;
            vertical-align: middle;
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

        .details-row {
            display: flex;
            justify-content: center;
            align-items: center; /* Выравнивание элементов по вертикали */
            height: 36px; /* Фиксированная высота строки */
        }
        .details-cell {
            /*flex-grow: 1; !* Позволяет колонкам растягиваться и занимать доступное пространство *!*/
            display: flex;
            justify-content: center; /* Центрирование содержимого по горизонтали */
            align-items: center; /* Центрирование содержимого по вертикали */
            /*border: 1px solid black; !* Границы для наглядности *!*/
        }
        .check-icon {
            width: 24px; /* Меньший размер изображения */
            height: auto;
            margin: 0 5px; /* Отступы вокруг изображения */
        }
    </style>
</head>
<body>
<!-- Кнопки для печати и настроек -->
<div class="text-start m-3 no-print">
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
            <div class="col-3">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 180px; margin: 6px 10px 0;">
            </div>
            <div class="col-9">
                <h2 class=" mt-3 text-black text-"><strong>{{$process_name->process_sheet_name ?? $process_name->name ?? 'WO BUSHING'}} PROCESS SHEET</strong></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-7">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 pt-2 border-b">
                        <strong>
                            {{$current_wo->description}}
{{--                            @if(isset($table_data) && count($table_data) > 1)--}}
{{--                                Multiple Components ({{ count($table_data) }} items)--}}
{{--                            @else--}}
{{--                                {{$table_data[0]['component']->name ?? 'Bushings'}}--}}
{{--                            @endif--}}
                        </strong>
                    </div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong> PART NUMBER:</strong></div>
                    <div class="col-6 pt-2 border-b">
                        <strong>
                            {{$current_wo->unit->part_number}}
{{--                            @if(isset($table_data) && count($table_data) > 1)--}}
{{--                                Various (see table below)--}}
{{--                            @else--}}
{{--                                {{$table_data[0]['component']->part_number ?? 'Bushings'}}--}}
{{--                            @endif--}}
                        </strong>
                    </div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>WORK ORDER No:</strong> </div>
                    <div class="col-6 pt-2 border-b"><strong>W{{$current_wo->number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>SERIAL No:</strong> </div>
                    <div class="col-6 pt-2 border-b"><strong>{{$current_wo->serial_number}}</strong></div>
                </div>
            </div>
            <div class="col-5">
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
                    <div class="col-8 pt-2 border-b"><strong>{{ $selectedVendor ? $selectedVendor->name : '' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    @if($process_name->process_sheet_name == 'NDT')
        <div class="row mt-3">
            <div class="col-5">
                <div class="text-start"><strong>MAGNETIC PARTICLE AS PER:</strong></div>
                <div class="row " style="height: 26px">
                    <div class="col-1">#1</div>
                    <div class="col-11 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt1_name_id ?? null))
                                    <span @if(strlen($process->process) > 30) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="text-start"><strong>LIQUID/FLUID PENETRANT AS PER:</strong></div>
                <div class="row " style="height: 26px">
                    <div class="col-1">#4</div>
                    <div class="col-11 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt4_name_id ?? null))
                                    <span @if(strlen($process->process) > 30) class="process-text-long"
                                        @endif>{{$process->process}}</span>
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
                    </div>
                </div>
                <div class="row mt-4" style="height: 26px">
                    <div class="col-2">#5</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt5_name_id ?? null))
                                    <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
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
                    </div>
                </div>
                <div class="text-start"><strong>EDDY CURRENT AS PER:</strong></div>
                <div class="row " style="height: 26px">
                    <div class="col-2 text-end">#6</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt6_name_id ?? null))
                                    <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
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
        <div class="page ndt-data-container">
            @php
                $totalRows = 20; // Общее количество строк
                $dataRows = isset($table_data) ? count($table_data) : 0; // Количество строк с данными
                $emptyRows = $totalRows - $dataRows; // Количество пустых строк
            @endphp

            @php
                $rowIndex = 1;
            @endphp
            @if(isset($table_data) && count($table_data) > 0)
                @foreach($table_data as $data)
                    <div class="row fs-85 data-row-ndt" data-row-index="{{ $rowIndex }}">
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                            {{ $data['component']->ipl_num }}
                        </div>
                        <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                            {{ $data['component']->part_number }}
                        </div>
                        <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                            {{ $data['component']->name }}
                        </div>
                        <div class="col-2 border-l-b details-row text-center" style="height: 32px">
                            @if(strpos($data['process_name']->name, 'NDT-') === 0)
                                {{ substr($data['process_name']->name, 4) }}
                            @elseif($data['process_name']->name === 'Eddy Current Test')
                                6
                            @elseif($data['process_name']->name === 'BNI')
                                5
                            @else
                                {{ substr($data['process_name']->name, -1) }}
                            @endif
                        </div>
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                            {{ $data['qty'] ?? 1 }}
                        </div>
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                            <!-- Пустая ячейка -->
                        </div>
                        <div class="col-1 border-l-b-r details-row text-center" style="height: 32px">
                        </div>
                    </div>
                    @php $rowIndex++; @endphp
                @endforeach
            @endif

            @for ($i = 0; $i < $emptyRows; $i++)
                <div class="row fs-85 data-row-ndt empty-row" data-row-index="{{ $rowIndex }}">
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b-r details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                </div>
                @php $rowIndex++; @endphp
            @endfor
        </div>
    @else
                        <h6 class="mt-3 ms-3"><strong>
                    Perform the {{ ucwords(strtolower($process_name->process_sheet_name ?? $process_name->name ?? 'Wo Bushing Process')) }}
                    as the specified under Process No. and in
                    accordance with CMM No
                </strong>.</h6>

        <div class="page table-header">
            <div class="row mt-2 " >
                <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-7" ><strong> ITEM No.</strong></h6></div>
                <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6  class="fs-7" ><strong>PART No.</strong>
                    </h6>
                </div>
                <div class="col-3 border-l-t-b pt-2  details-row text-center"><h6  class="fs-7" ><strong>DESCRIPTION</strong>
                    </h6></div>
                <div class="col-3 border-l-t-b pt-2 details-row text-center"><h6  class="fs-7" ><strong>PROCESS</strong>
                    </h6> </div>
                <div class="col-1 border-l-t-b pt-2  details-row  text-center"><h6  class="fs-7" ><strong>QTY</strong> </h6>
                </div>
                <div class="col-2 border-all pt-2  details-row  text-center"><h6  class="fs-7" ><strong>CMM No.</strong> </h6>
                </div>
            </div>
        </div>
        <div class="page data-page">
            @php
                $totalRows = 20; // Общее количество строк
                $dataRows = isset($table_data) ? count($table_data) : 0; // Количество строк с данными
                $emptyRows = $totalRows - $dataRows; // Количество пустых строк
            @endphp

            @php
                $rowIndex = 1;
            @endphp
            @if(isset($table_data) && count($table_data) > 0)
                @foreach($table_data as $data)
                    <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}">
                        <div class="col-1 border-l-b details-cell text-center"  style="min-height: 34px">
                            {{ $data['component']->ipl_num }}
                        </div>
                        <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                            {{ $data['component']->part_number }}
                        </div>
                        <div class="col-3 border-l-b details-cell text-center" style="min-height: 34px" >
                            {{ $data['component']->name }}
                        </div>
                        <div class="col-3 border-l-b details-cell text-center process-cell"  style="min-height: 34px">
                            <span @if(strlen($data['process']->process) > 40) class="process-text-long" @endif>{{$data['process']->process}}</span>

{{--                            {{ $data['process']->process }}--}}
                        </div>
                        <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px" >
                            {{ $data['qty'] ?? 1 }}
                        </div>
                        <div class="col-2 border-l-b-r details-cell text-center"  style="min-height: 34px">
                            @foreach($manuals as $manual)
                                @if($manual->id == $current_wo->unit->manual_id)
                                    <h6 class="text-center mt-3"> {{substr($manual->number, 0, 8)}}</h6>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @php $rowIndex++; @endphp
                @endforeach
            @endif

            @for ($i = 0; $i < $emptyRows; $i++)
                <div class="row empty-row" data-row-index="{{ $rowIndex }}">
                    <div class="col-1 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b-r  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                </div>
                @php $rowIndex++; @endphp
            @endfor
        </div>
    @endif
</div>
<footer>
    <div class="row fs-85" style="width: 100%; padding: 5px 0;">
        <div class="col-6 text-start">
            {{__('Form #')}} {{$process_name->form_number ?? 'WO-BUSHING-001'}}
        </div>
        <div class="col-6 text-end pe-4 ">
            {{__('Rev#0, 15/Dec/2012   ')}}
        </div>
    </div>
</footer>

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

<!-- Модальное окно настроек печати -->
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">
                    ⚙️ Print Settings
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="langToggleBtn" onclick="toggleTooltipLanguage()">
                        <span id="langToggleText">US</span>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <!-- Table Setting - Основная группа -->
                    <div class="mb-4">
                        <h5 class="mb-3" data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Настройки количества строк в таблицах. Строки сверх лимита скрываются при печати."
                            data-tooltip-ru="Настройки количества строк в таблицах. Строки сверх лимита скрываются при печати."
                            data-tooltip-en="Table row settings. Rows exceeding the limit are hidden when printing.">
                            📊 Tables
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="ndtTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблице NDT. По умолчанию: 20 строк."
                                        data-tooltip-ru="Максимальное количество строк в таблице NDT. По умолчанию: 20 строк."
                                        data-tooltip-en="Maximum number of rows in NDT table. Default: 20 rows.">
                                    NDT Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="ndtTableRows" name="ndtTableRows"
                                           min="1" max="100" step="1" value="20">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="otherTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблицах других процессов. По умолчанию: 20 строк."
                                        data-tooltip-ru="Максимальное количество строк в таблицах других процессов. По умолчанию: 20 строк."
                                        data-tooltip-en="Maximum number of rows in other process tables. Default: 20 rows.">
                                    Other Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="otherTableRows" name="otherTableRows"
                                           min="1" max="100" step="1" value="20">
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
                                              title="Дополнительные настройки таблицы: ширина, отступы и размер шрифта."
                                              data-tooltip-ru="Дополнительные настройки таблицы: ширина, отступы и размер шрифта."
                                              data-tooltip-en="Additional table settings: width, padding and font size.">
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
                                                        title="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 920px."
                                                        data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 920px."
                                                        data-tooltip-en="Maximum width of the table container in pixels. Recommended value: 920px.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                           min="500" max="2000" step="10" value="920">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="tableFontSize" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Размер шрифта текста в таблице. Рекомендуемое значение: 0.85rem (13.6px). Увеличьте для лучшей читаемости."
                                                        data-tooltip-ru="Размер шрифта текста в таблице. Рекомендуемое значение: 0.85rem (13.6px). Увеличьте для лучшей читаемости."
                                                        data-tooltip-en="Font size for table text. Recommended value: 0.85rem (13.6px). Increase for better readability.">
                                                    Font Size (rem)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="tableFontSize" name="tableFontSize"
                                                           min="0.5" max="2" step="0.05" value="0.85">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerPadding" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Внутренние отступы контейнера. По умолчанию: 5px."
                                                        data-tooltip-ru="Внутренние отступы контейнера. По умолчанию: 5px."
                                                        data-tooltip-en="Container inner padding. Default: 5px.">
                                                    Padding (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                           min="0" max="50" step="1" value="5">
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
                                              title="Настройки страницы: ширина, высота, поля и отступы."
                                              data-tooltip-ru="Настройки страницы: ширина, высота, поля и отступы."
                                              data-tooltip-en="Page settings: width, height, margins and padding.">
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
                                                        title="Ширина основного контента в процентах. 98% - стандартное значение."
                                                        data-tooltip-ru="Ширина основного контента в процентах. 98% - стандартное значение."
                                                        data-tooltip-en="Main content width as percentage. 98% - standard value.">
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
                                                        title="Высота основного контента в процентах. 99% - стандартное значение."
                                                        data-tooltip-ru="Высота основного контента в процентах. 99% - стандартное значение."
                                                        data-tooltip-en="Main content height as percentage. 99% - standard value.">
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
                                                        title="Отступ от краев страницы при печати. Рекомендуемое значение: 1mm."
                                                        data-tooltip-ru="Отступ от краев страницы при печати. Рекомендуемое значение: 1mm."
                                                        data-tooltip-en="Margin from page edges when printing. Recommended value: 1mm.">
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
                                                        title="Горизонтальный отступ основного контента от левого края. По умолчанию: 2px."
                                                        data-tooltip-ru="Горизонтальный отступ основного контента от левого края. По умолчанию: 2px."
                                                        data-tooltip-en="Horizontal margin of main content from left edge. Default: 2px.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft"
                                                           min="0" max="50" step="1" value="2">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginLeft" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Отступ контейнера с таблицей от левого края. По умолчанию: 10px."
                                                        data-tooltip-ru="Отступ контейнера с таблицей от левого края. По умолчанию: 10px."
                                                        data-tooltip-en="Table container margin from left edge. Default: 10px.">
                                                    Table Left Margin (px)
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
                                                    Table Right Margin (px)
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

                    <!-- Footer Setting (collapse) -->
                    <div class="mb-4">
                        <div class="accordion" id="footerSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="footerSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#footerSettingsCollapse" aria-expanded="false"
                                            aria-controls="footerSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="Настройки нижнего колонтитула формы."
                                              data-tooltip-ru="Настройки нижнего колонтитула формы."
                                              data-tooltip-en="Form footer settings.">
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
                                                        title="Ширина колонтитула в пикселях. 800px - стандартное значение."
                                                        data-tooltip-ru="Ширина колонтитула в пикселях. 800px - стандартное значение."
                                                        data-tooltip-en="Footer width in pixels. 800px - standard value.">
                                                    Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="400" max="1200" step="10" value="800">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerFontSize" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Размер шрифта текста в колонтитуле. 10px - стандартное значение."
                                                        data-tooltip-ru="Размер шрифта текста в колонтитуле. 10px - стандартное значение."
                                                        data-tooltip-en="Footer text font size. 10px - standard value.">
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
                                                        title="Внутренние отступы колонтитула. Например: '3px 3px'."
                                                        data-tooltip-ru="Внутренние отступы колонтитула. Например: '3px 3px'."
                                                        data-tooltip-en="Footer inner padding. Example: '3px 3px'.">
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

<script>
    // Ключ для сохранения настроек печати
    const PRINT_SETTINGS_KEY = 'woBushingsProcessesForm_print_settings';
    const TOOLTIP_LANG_KEY = 'woBushingsProcessesForm_tooltip_lang';

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
        tableFontSize: '0.85rem',
        footerWidth: '800px',
        footerFontSize: '10px',
        footerPadding: '3px 3px',
        ndtTableRows: '20',
        otherTableRows: '20'
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
                    return element.value + (suffix ? suffix : '');
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
                tableFontSize: getValue('tableFontSize', '0.85', 'rem'),
                footerWidth: getValue('footerWidth', '800', 'px'),
                footerFontSize: getValue('footerFontSize', '10', 'px'),
                footerPadding: getValue('footerPadding', '3px 3px', ''),
                ndtTableRows: getValue('ndtTableRows', '20', ''),
                otherTableRows: getValue('otherTableRows', '20', '')
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
        root.style.setProperty('--table-font-size', settings.tableFontSize || defaultSettings.tableFontSize);
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
            'tableFontSize': { suffix: '', default: '0.85' },
            'footerWidth': { suffix: '', default: '800' },
            'footerFontSize': { suffix: '', default: '10' },
            'footerPadding': { suffix: '', default: '3px 3px' },
            'ndtTableRows': { suffix: '', default: '20' },
            'otherTableRows': { suffix: '', default: '20' }
        };

        Object.keys(elements).forEach(function(id) {
            const element = document.getElementById(id);
            if (element) {
                const value = settings[id] || elements[id].default;
                if (id === 'footerPadding') {
                    element.value = value;
                } else if (id === 'tableFontSize') {
                    element.value = parseFloat(value) || parseFloat(elements[id].default);
                } else {
                    element.value = parseInt(value) || elements[id].default;
                }
            }
        });
    }

    // Функции для добавления пустых строк
    function addEmptyRowNDT(rowIndex, container) {
        if (!container) return;
        const row = document.createElement('div');
        row.className = 'row fs-85 data-row-ndt empty-row';
        row.setAttribute('data-row-index', rowIndex);
        row.innerHTML = `
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
        `;
        container.appendChild(row);
    }

    function addEmptyRowRegular(rowIndex, container) {
        if (!container) return;
        const row = document.createElement('div');
        row.className = 'row empty-row data-row';
        row.setAttribute('data-row-index', rowIndex);
        row.innerHTML = `
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b text-center" style="height: 32px"></div>
            <div class="col-3 border-l-b text-center" style="height: 32px"></div>
            <div class="col-3 border-l-b text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b-r text-center" style="height: 32px"></div>
        `;
        container.appendChild(row);
    }

    // Применение ограничений строк в таблицах
    function applyTableRowLimits(settings) {
        if (!settings) {
            settings = loadPrintSettings();
        }

        const ndtMaxRows = parseInt(settings.ndtTableRows) || 20;
        const otherMaxRows = parseInt(settings.otherTableRows) || 20;

        // Обработка NDT таблицы
        const ndtContainer = document.querySelector('.ndt-data-container');
        if (ndtContainer) {
            // Удаляем все пустые строки, созданные ранее
            ndtContainer.querySelectorAll('.empty-row').forEach(function(row) {
                row.remove();
            });

            const ndtRows = ndtContainer.querySelectorAll('.data-row-ndt[data-row-index]:not(.empty-row)');
            let maxIndex = 0;

            ndtRows.forEach(function(row) {
                const rowIndex = parseInt(row.getAttribute('data-row-index')) || 0;
                if (rowIndex > maxIndex) maxIndex = rowIndex;

                if (rowIndex > ndtMaxRows) {
                    row.classList.add('print-hide-row');
                } else {
                    row.classList.remove('print-hide-row');
                }
            });

            // Добавляем пустые строки, если нужно
            if (maxIndex < ndtMaxRows) {
                for (let i = maxIndex + 1; i <= ndtMaxRows; i++) {
                    addEmptyRowNDT(i, ndtContainer);
                }
            }
        }

        // Обработка обычной таблицы
        const dataPage = document.querySelector('.data-page');
        if (dataPage) {
            // Удаляем все пустые строки, созданные ранее
            dataPage.querySelectorAll('.empty-row').forEach(function(row) {
                row.remove();
            });

            const regularRows = dataPage.querySelectorAll('.data-row[data-row-index]:not(.empty-row)');
            let maxIndex = 0;

            regularRows.forEach(function(row) {
                const rowIndex = parseInt(row.getAttribute('data-row-index')) || 0;
                if (rowIndex > maxIndex) maxIndex = rowIndex;

                if (rowIndex > otherMaxRows) {
                    row.classList.add('print-hide-row');
                } else {
                    row.classList.remove('print-hide-row');
                }
            });

            // Добавляем пустые строки, если нужно
            if (maxIndex < otherMaxRows) {
                for (let i = maxIndex + 1; i <= otherMaxRows; i++) {
                    addEmptyRowRegular(i, dataPage);
                }
            }
        }
    }

    // Сброс настроек к значениям по умолчанию
    window.resetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
            applyTableRowLimits(defaultSettings);
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
        const settings = loadPrintSettings();
        applyTableRowLimits(settings);
    });
</script>

</body>
</html>
