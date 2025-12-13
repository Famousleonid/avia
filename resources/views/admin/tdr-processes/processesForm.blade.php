<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$process_name->process_sheet_name}}</title>
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
            font-size: 0.9em;
            /*line-height: 0.9;*/
            letter-spacing: -0.5px;
            transform: scale(0.95);
            transform-origin: left;
        }
        .description-text-long {
            font-size: 0.9rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: top;
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
                <h2 class=" mt-3 text-black text-"><strong>{{$process_name->process_sheet_name}} PROCESS SHEET</strong></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-7">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 pt-2 border-b"> <strong>
                      <span @if(strlen($current_wo->description) > 30) class="description-text-long"
                                @endif>{{$current_wo->description}}</span>
{{--                            {{$current_wo->description}}--}}
                        </strong> </div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong> PART NUMBER:</strong></div>
                    <div class="col-6 pt-2 border-b"> <strong>{{$current_wo->unit->part_number}}</strong> </div>
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
                    <div class="col-8 pt-2 border-b">
                        <strong>
                            {{ $selectedVendor ? $selectedVendor->name : '' }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

        @if($process_name->process_sheet_name =='NDT')
        <div class="row mt-3">
            <div class="col-5">
                <div class="text-start"><strong>MAGNETIC PARTICLE AS PER:</strong></div>
                <div class="row " style="min-height: 26px ; line-height: 1">

                    <div class="col-1">#1</div>
                    <div class="col-11 border-b">
                        @foreach($ndt_processes as $process)
                            @if($process->process_names_id == $ndt1_name_id)
                                <span @if(strlen($process->process) > 30) class="process-text-long"
                                    @endif>{{$process->process}}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="text-start"><strong>LIQUID/FLUID PENETRANT AS PER:</strong></div>

                <div class="row " style="min-height: 26px; line-height: 1">
                    <div class="col-1">#4</div>
                    <div class="col-11 border-b">
                        @foreach($ndt_processes as $process)
                            @if($process->process_names_id == $ndt4_name_id)
                                <span @if(strlen($process->process) > 30) class="process-text-long"
                                    @endif>{{$process->process}}</span>
                            @endif
                        @endforeach
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
                        @foreach($ndt_processes as $process)
                            @if($process->process_names_id == $ndt5_name_id)
                                <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
                            @endif
                        @endforeach
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
                        @foreach($ndt_processes as $process)
                            @if($process->process_names_id == $ndt6_name_id)
                                <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
                            @endif
                        @endforeach
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
            $totalRows = 17; // Общее количество строк
            $dataRows = count($ndt_components); // Количество строк с данными
            $emptyRows = $totalRows - $dataRows; // Количество пустых строк
            $rowIndex = 1;
        @endphp

        @foreach($ndt_components as $component)
            <div class="row fs-85 data-row-ndt" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                    {{ $component->tdr->component->ipl_num }}
                </div>
                <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                    {{ $component->tdr->component->part_number }}
                    @if($component->tdr->serial_number)
            SN {{$component->tdr->serial_number}}
                    @endif
                </div>
        <div class="col-3 border-l-b details-row text-center" style="height: 32px">
        {{ $component->tdr->component->name }}
        </div>
        <div class="col-2 border-l-b details-row text-center" style="height: 32px">
        {{ substr($component->processName->name, -1) }}
        </div>
        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
        {{ $component->tdr->qty }}
        </div>
        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
        <!-- Пустая ячейка -->
        </div>
        <div class="col-1 border-l-b-r details-row text-center" style="height: 32px">

        </div>
    </div>
@php $rowIndex++; @endphp
@endforeach

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


@else

            @if($process_name->process_sheet_name == 'STRESS RELIEF')

                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-3 text-end pe-2 pt-3">
                        <strong>
                            MANUAL REF:
                        </strong>

                    </div>
                    <div class="col-3 border-all text-center" style="height: 55px">
                        @foreach($manuals as $manual)
                            @if($manual->id == $current_wo->unit->manual_id)
                                <h6 class="text-center mt-3"> <strong> {{substr($manual->number, 0, 8)}} </strong></h6>
                            @endif
                        @endforeach
                    </div>
                </div>

            @endif


            <h6 class="mt-3 ms-3">
                <strong>
                    Perform the {{ ucwords(strtolower($process_name->process_sheet_name)) }}
                    as the specified under Process No. and in
                    accordance with CMM No.
                </strong>
            </h6>

    <div class="page table-header">
    <div class="row mt-2 " >
    <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-7" ><strong> ITEM No.</strong></h6></div>
    <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6  class="fs-7" ><strong>PART No.</strong>
        </h6>
    </div>
    <div class="col-2 border-l-t-b pt-2  details-row text-center"><h6  class="fs-7" ><strong>DESCRIPTION</strong>
        </h6></div>
    <div class="col-4 border-l-t-b pt-2 details-row text-center"><h6  class="fs-7" ><strong>PROCESS No.</strong>
        </h6> </div>
    <div class="col-1 border-l-t-b pt-2  details-row  text-center"><h6  class="fs-7" ><strong>QTY</strong> </h6>
    </div>

        @if($process_name->process_sheet_name == 'STRESS RELIEF')
            <div class="col-2 border-all pt-2  details-row  text-center"><h6  class="fs-7" ><strong>PERFORMED</strong>
                </h6>
        @else
             <div class="col-2 border-all pt-2  details-row  text-center"><h6  class="fs-7" ><strong>CMM No.</strong> </h6>
        @endif
            </div>
    </div>
    </div>
    <div class="page data-page">

    @php
    $totalRows = 18; // Общее количество строк
    $dataRows = count($process_tdr_components); // Количество строк с данными
    $emptyRows = $totalRows - $dataRows; // Количество пустых строк
    $rowIndex = 1;
    @endphp

    @foreach($process_tdr_components as $component)
    @php
        $processData = json_decode($component->processes, true);
                            // Получаем имя процесса из связанной модели ProcessName
        $processesName = $component->processName->name;
    @endphp

    @foreach($processData as $process)

    <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}">
        <div class="col-1 border-l-b details-cell text-center"  style="min-height: 34px">
            {{ $component->tdr->component->ipl_num }}
        </div>
        <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
            {{ $component->tdr->component->part_number }}
            @if($component->tdr->serial_number)
                <br>SN {{$component->tdr->serial_number}}
            @endif
        </div>
        <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px" >
            {{ $component->tdr->component->name }}
        </div>
        <div class="col-4 border-l-b details-cell text-center process-cell"  style="min-height: 34px">
            @foreach($process_components as $component_process)
                @if($component_process->id == $process)
                    <span @if(strlen($component_process->process) > 25) class="process-text-long"
                        @endif>
                        {{$component_process->process}}
                        @if($component->description)
                            <br><span>{{$component->description}}
                            </span>
                        @endif
                        </span>
                @endif
            @endforeach
        </div>
        <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px" >
            {{ $component->tdr->qty }}
        </div>
        @if($process_name->process_sheet_name == 'STRESS RELIEF')
            <div class="col-2 border-l-b-r details-cell text-center"  style="min-height: 34px"></div>
        @else
            <div class="col-2 border-l-b-r details-cell text-center"  style="min-height: 34px">
                @foreach($manuals as $manual)
                    @if($manual->id == $current_wo->unit->manual_id)
                        <h6 class="text-center mt-2">
                            {{substr($manual->number, 0, 8)}}
                        </h6>
                    @endif
                @endforeach
            </div>
        @endif

    </div>
    @php $rowIndex++; @endphp
    @endforeach
    @endforeach

    @for ($i = 0; $i < $emptyRows; $i++)
    <div class="row empty-row" data-row-index="{{ $rowIndex }}">
        <div class="col-1 border-l-b  text-center" style="height: 32px">
            <!-- Пустая ячейка -->
        </div>
        <div class="col-2 border-l-b  text-center" style="height: 32px">
            <!-- Пустая ячейка -->
        </div>
        <div class="col-2 border-l-b  text-center" style="height: 32px">
            <!-- Пустая ячейка -->
        </div>
        <div class="col-4 border-l-b  text-center" style="height: 32px">
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
{{__('Form #')}} {{$process_name->form_number}}
</div>
<div class="col-6 text-end pe-4 ">
{{__('Rev#0, 15/Dec/2012   ')}}
</div>
</div>
</footer>

<script src="{{ asset('js/table-height-adjuster.js') }}"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Функция для добавления пустой строки NDT таблицы
    function addEmptyRowNDT(rowIndex, tableElement) {
        const container = typeof tableElement === 'string'
            ? document.querySelector(tableElement)
            : tableElement;
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

    // Функция для удаления строки NDT таблицы
    function removeRowNDT(rowIndex, tableElement) {
        const container = typeof tableElement === 'string'
            ? document.querySelector(tableElement)
            : tableElement;
        if (!container) return;

        const row = container.querySelector(`.data-row-ndt[data-row-index="${rowIndex}"]`);
        if (row) row.remove();
    }

    // Функция для добавления пустой строки обычной таблицы
    function addEmptyRowRegular(rowIndex, tableElement) {
        const container = typeof tableElement === 'string'
            ? document.querySelector(tableElement)
            : tableElement;
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'row empty-row';
        row.setAttribute('data-row-index', rowIndex);
        row.innerHTML = `
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b text-center" style="height: 32px"></div>
            <div class="col-4 border-l-b text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b-r text-center" style="height: 32px"></div>
        `;
        container.appendChild(row);
    }

    // Функция для удаления строки обычной таблицы
    function removeRowRegular(rowIndex, tableElement) {
        const container = typeof tableElement === 'string'
            ? document.querySelector(tableElement)
            : tableElement;
        if (!container) return;

        const row = container.querySelector(`[data-row-index="${rowIndex}"]`);
        if (row) row.remove();
    }

    // Функция для вычисления реальной средней высоты строк
    function calculateAverageRowHeight(rows) {
        if (!rows || rows.length === 0) return 32; // Значение по умолчанию

        let totalHeight = 0;
        let count = 0;

        rows.forEach(function(row) {
            const rowHeight = row.offsetHeight;
            if (rowHeight > 0) {
                totalHeight += rowHeight;
                count++;
            }
        });

        const averageHeight = count > 0 ? Math.round(totalHeight / count) : 32;
        console.log(`Средняя высота строк: ${averageHeight}px (из ${count} строк)`);
        return averageHeight;
    }

    // Настройка высоты таблиц после загрузки
    setTimeout(function() {
        // Проверяем, что функция adjustTableHeightToRange загружена
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена. Проверьте подключение скрипта table-height-adjuster.js');
            return;
        }

        // Настройка таблицы NDT (если она есть)
        const ndtRows = document.querySelectorAll('.data-row-ndt');
        if (ndtRows.length > 0) {
            const ndtDataContainer = document.querySelector('.ndt-data-container');
            if (ndtDataContainer) {
                // Вычисляем реальную среднюю высоту строк NDT
                const ndtRowHeight = calculateAverageRowHeight(ndtRows);

                adjustTableHeightToRange({
                    min_height_tab: 570,
                    max_height_tab: 620,
                    tab_name: '.ndt-data-container',
                    row_height: ndtRowHeight,
                    row_selector: '.data-row-ndt[data-row-index]',
                    addRowCallback: addEmptyRowNDT,
                    removeRowCallback: removeRowNDT,
                    getRowIndexCallback: function(rowElement) {
                        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
                    },
                    max_iterations: 50,
                    onComplete: function(currentHeight, rowCount) {
                        console.log(`NDT таблица настроена: высота ${currentHeight}px, строк ${rowCount}, средняя высота строки ${ndtRowHeight}px`);
                    }
                });
            }
        }

        // Настройка обычной таблицы (если она есть)
        const regularTableContainer = document.querySelector('.data-page');
        const regularRows = document.querySelectorAll('.data-page .data-row:not(.data-row-ndt)');
        if (regularTableContainer && regularRows.length > 0) {
            // Вычисляем реальную среднюю высоту строк с данными
            const dataRowHeight = calculateAverageRowHeight(regularRows);

            // Также учитываем пустые строки для более точного расчета
            const emptyRows = document.querySelectorAll('.data-page .empty-row');
            const emptyRowHeight = emptyRows.length > 0 ? calculateAverageRowHeight(emptyRows) : 32;

            // Используем среднее значение между высотой строк с данными и пустых строк
            // или высоту строк с данными, если она больше (так как пустые строки могут быть меньше)
            const avgRowHeight = Math.max(dataRowHeight, emptyRowHeight);

            // Проверяем текущую высоту таблицы перед настройкой
            const initialHeight = regularTableContainer.offsetHeight;
            const initialRowCount = regularTableContainer.querySelectorAll('[data-row-index]').length;

            console.log(`Обычная таблица: высота строк с данными ${dataRowHeight}px, пустых строк ${emptyRowHeight}px, используется ${avgRowHeight}px`);
            console.log(`Текущая высота таблицы: ${initialHeight}px, текущее количество строк: ${initialRowCount}`);
            console.log(`Целевой диапазон: 700-750px`);

            adjustTableHeightToRange({
                min_height_tab: 720,
                max_height_tab: 770,
                tab_name: '.data-page',
                row_height: avgRowHeight,
                row_selector: '.data-page [data-row-index]',
                addRowCallback: addEmptyRowRegular,
                removeRowCallback: removeRowRegular,
                getRowIndexCallback: function(rowElement) {
                    return parseInt(rowElement.getAttribute('data-row-index')) || 0;
                },
                max_iterations: 50,
                onComplete: function(currentHeight, rowCount) {
                    console.log(`Обычная таблица настроена: высота ${currentHeight}px, строк ${rowCount}, средняя высота строки ${avgRowHeight}px`);
                    console.log(`Изменение высоты: ${currentHeight - initialHeight}px (было ${initialHeight}px, стало ${currentHeight}px)`);

                    // Если высота не изменилась, выводим предупреждение
                    if (Math.abs(currentHeight - initialHeight) < 5) {
                        console.warn('ВНИМАНИЕ: Высота таблицы практически не изменилась!');
                        console.warn(`Возможные причины:`);
                        console.warn(`1. Таблица уже находится в целевом диапазоне (${currentHeight}px между 700-750px)`);
                        console.warn(`2. Недостаточно строк для достижения целевой высоты`);
                        console.warn(`3. CSS ограничения (max-height, overflow)`);
                    }
                }
            });
        }

        // Дополнительная проверка и корректировка на основе реальных высот строк
        // После того как adjustTableHeightToRange завершил работу, проверяем реальную высоту таблицы
        setTimeout(function() {
            const regularTableContainer = document.querySelector('.data-page');
            if (regularTableContainer) {
                const allRows = regularTableContainer.querySelectorAll('[data-row-index]');
                let totalActualHeight = 0;

                allRows.forEach(function(row) {
                    totalActualHeight += row.offsetHeight;
                });

                const containerHeight = regularTableContainer.offsetHeight;
                const actualRowsHeight = totalActualHeight;

                console.log(`Проверка высоты обычной таблицы: контейнер ${containerHeight}px, сумма высот строк ${actualRowsHeight}px`);

                // Если реальная высота строк значительно отличается от высоты контейнера,
                // это может указывать на проблемы с расчетом
                if (Math.abs(containerHeight - actualRowsHeight) > 50) {
                    console.warn(`Внимание: разница между высотой контейнера и суммой высот строк составляет ${Math.abs(containerHeight - actualRowsHeight)}px`);
                }
            }

            const ndtContainer = document.querySelector('.ndt-data-container');
            if (ndtContainer) {
                const ndtAllRows = ndtContainer.querySelectorAll('[data-row-index]');
                let ndtTotalActualHeight = 0;

                ndtAllRows.forEach(function(row) {
                    ndtTotalActualHeight += row.offsetHeight;
                });

                const ndtContainerHeight = ndtContainer.offsetHeight;
                console.log(`Проверка высоты NDT таблицы: контейнер ${ndtContainerHeight}px, сумма высот строк ${ndtTotalActualHeight}px`);
            }
        }, 300);
    }, 200);
});
</script>
</div>
</body>
</html>
