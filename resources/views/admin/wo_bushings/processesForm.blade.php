<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$process_name->process_sheet_name ?? $process_name->name ?? 'Wo Bushing'}} Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 920px;
            height: 98%;
            padding: 5px;
            margin-left: 10px;
            margin-right: 10px;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: letter;
                margin: 1mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: 99%;
                width: 98%;
                margin-left: 2px;
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
                width: 800px;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 3px 3px;
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
<!-- Кнопка для печати -->
<div class="text-start m-3">
    <button class="btn btn-outline-primary no-print" onclick="window.print()">
        Print Form
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
                                    <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
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
                                    <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
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

<!-- Подключение библиотеки table-height-adjuster -->
<script src="{{ asset('js/table-height-adjuster.js') }}"></script>

<!-- Переиспользуемые модули из tdr-processes -->
<script src="{{ asset('js/tdr-processes/processes-form/height-calculator.js') }}"></script>
<script src="{{ asset('js/tdr-processes/processes-form/row-manager.js') }}"></script>
<script src="{{ asset('js/tdr-processes/processes-form/table-height-manager.js') }}"></script>

<!-- Переиспользуемые модули из extra-processes -->
<script src="{{ asset('js/extra-processes/processes-form/empty-row-processor.js') }}"></script>

<!-- Модули для Wo Bushings формы -->
<script src="{{ asset('js/wo-bushings/processes-form/processes-form-main.js') }}"></script>

</body>
</html>
