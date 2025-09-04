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
            font-size: 0.9em;
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
<div class="text-start m-3">
    <button class="btn btn-outline-primary no-print" onclick="window.print()">
        Print Form
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
            <div class="col-7">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 pt-2 border-b"> <strong>{{$current_wo->description}}</strong> </div>
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
                    <div class="col-8 pt-2 border-b"></div>
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
                                    <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
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
                                    <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
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
                            <h6 class="text-center mt-3"><strong> {{$manual->number}}</strong></h6>
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
        $componentsPerPage = 16; // Количество компонентов на страницу
        $componentChunks = collect($ndt_components)->chunk($componentsPerPage); // Разбиваем на группы
    @endphp

    @foreach($componentChunks as $chunk)
        @if($loop->iteration == 1)
            <!-- Первая страница - используем оригинальный header, данные будут показаны ниже -->
        @endif

        @if($loop->iteration > 1)
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
                    <div class="col-7">
                        <div class="row" style="height: 32px">
                            <div class="col-6 pt-2 text-end"> <strong>COMPONENT NAME</strong> :</div>
                            <div class="col-6 pt-2 border-b"> <strong>{{$current_wo->description}}</strong> </div>
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
                            <div class="col-8 pt-2 border-b"></div>
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
                                        @if($process->process_names_id == ($ndt1_name_id ?? null))
                                            <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{$process->process}}</span>
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
                                @if(!empty($ndt_processes) && count($ndt_processes))
                                    @foreach($ndt_processes as $process)
                                        @if($process->process_names_id == $ndt5_name_id)
                                            <span @if(strlen($process->process ?? '') > 40) class="process-text-long" @endif>{{ $process->process ?? '' }}</span>
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
                                @if(!empty($ndt_processes) && count($ndt_processes))
                                    @foreach($ndt_processes as $process)
                                        @if($process->process_names_id == $ndt6_name_id)
                                            <span @if(strlen($process->process ?? '') > 40) class="process-text-long" @endif>{{ $process->process ?? '' }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="row border-all mt-2" style="height: 56px">
                            @foreach($manuals as $manual)
                                @if($manual->id == $current_wo->unit->manual_id)
                                    <h6 class="text-center mt-3"><strong> {{$manual->number}}</strong></h6>
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
                    <div class="col-2 border-l-t-b details-row text-center"><h6  class="fs-75">PROCESS No.</h6> </div>
                    <div class="col-1 border-l-t-b details-row  text-center"><h6  class="fs-7">QTY</h6> </div>
                    <div class="col-1 border-l-t-b details-row  text-center"><h6  class="fs-7">ACCEPT</h6> </div>
                    <div class="col-1 border-all details-row  text-center"><h6  class="fs-7">REJECT</h6> </div>
                </div>
            </div>
        @endif

        <div class="page data-page">
            @php
                $totalRows = 16; // Общее количество строк
                $dataRows = count($chunk); // Количество строк с данными
                $emptyRows = $totalRows - $dataRows; // Количество пустых строк
            @endphp

            @foreach($chunk as $component)
                <div class="row fs-85">
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
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
            @endforeach

            @for ($i = 0; $i < $emptyRows; $i++)
                <div class="row fs-85">
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
            @endfor
        </div>

        <footer>
            <div class="row fs-85" style="width: 100%; padding: 5px 0;">
                <div class="col-3 text-start">
                    {{__('Form #016')}}
                </div>
                <div class="col-3 text-center">
                    {{__('Page')}} {{ $loop->iteration }} {{__('of')}} {{ $componentChunks->count() }}
                </div>
                <div class="col-6 text-end pe-4 ">
                    {{__('Rev#0, 15/Dec/2012   ')}}
<br>
                    {{__('Total QTY:')}} {{ array_sum(array_column($ndt_components, 'qty')) }}
                                            ( {{__('MPI:')}} {{ array_sum(array_column(array_filter($ndt_components, function($item) {
                                                return strpos($item->process_name, '1') !== false;
                                            }), 'qty')) }} {{__(' ; ')}}
                                            {{__('FPI:')}} {{ array_sum(array_column(array_filter($ndt_components, function($item) {
                                                return strpos($item->process_name, '1') === false;
                                            }), 'qty')) }} )
{{--                    @if($loop->iteration == 1)--}}
{{--                        <br class=" ">--}}

{{--                        {{__('Total QTY:')}} {{ array_sum(array_column($ndt_components, 'qty')) }}--}}
{{--                        ( {{__('MPI:')}} {{ array_sum(array_column(array_filter($ndt_components, function($item) {--}}
{{--                            return strpos($item->process_name, '1') !== false;--}}
{{--                        }), 'qty')) }} {{__(' ; ')}}--}}
{{--                        {{__('FPI:')}} {{ array_sum(array_column(array_filter($ndt_components, function($item) {--}}
{{--                            return strpos($item->process_name, '1') === false;--}}
{{--                        }), 'qty')) }} )--}}
{{--                    @endif--}}
                </div>
            </div>
        </footer>

        @if(!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</div>
</body>
</html>
