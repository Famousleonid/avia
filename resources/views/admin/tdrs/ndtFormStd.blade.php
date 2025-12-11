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
<div class="text-start m-1">
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
        // componentChunks уже рассчитан в контроллере
        $previousChunkLastManual = null;
    @endphp

    @foreach($componentChunks as $chunkInfo)
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
                    <div class="col-6">
                        <div class="row" style="height: 32px">
                            <div class="col-6 pt-2 text-end"> <strong>COMPONENT NAME</strong> :</div>
                            <div class="col-6 fs-7 pt-2 border-b"> <strong>{{$current_wo->description}}</strong> </div>
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
                    <div class="col-2 border-l-t-b details-row text-center"><h6  class="fs-75">PROCESS No.</h6> </div>
                    <div class="col-1 border-l-t-b details-row  text-center"><h6  class="fs-7">QTY</h6> </div>
                    <div class="col-1 border-l-t-b details-row  text-center"><h6  class="fs-7">ACCEPT</h6> </div>
                    <div class="col-1 border-all details-row  text-center"><h6  class="fs-7">REJECT</h6> </div>
                </div>
            </div>
        @endif

        <div class="page data-page" data-page-index="{{ $loop->iteration }}">
            @php
                // Используем данные из chunkInfo, рассчитанные на бэкенде
                $chunk = isset($chunkInfo['components']) ? $chunkInfo['components'] : [];
                $previousManual = $previousChunkLastManual;
                $chunkLastManual = null;
                $rowIndex = 1;
                $isLastPage = $loop->last;
            @endphp

            @foreach($chunk as $component)
                @php
                    $currentManual = $component->manual ?? null;
                    // Если manual изменился и не пустой, вставляем строку с manual
                    $shouldInsertManualRow = ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);
                    // Сохраняем последний manual в chunk
                    if ($currentManual !== null && $currentManual !== '') {
                        $chunkLastManual = $currentManual;
                    }
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
                    if ($currentManual !== null && $currentManual !== '') {
                        $chunkLastManual = $currentManual;
                    }
                @endphp
            @endforeach

            {{-- Генерируем пустые строки на бэкенде --}}
            @if(isset($chunkInfo['empty_rows']) && $chunkInfo['empty_rows'] > 0)
                @for($i = 0; $i < $chunkInfo['empty_rows']; $i++)
                    <div class="row fs-85 data-row-ndt empty-row" data-row-index="{{ $rowIndex }}">
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                        <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
                        <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
                        <div class="col-2 border-l-b details-row text-center" style="height: 32px"></div>
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                        <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
                    </div>
                    @php $rowIndex++; @endphp
                @endfor
            @endif

            @php
                // Сохраняем последний manual для следующего chunk
                $previousChunkLastManual = $chunkLastManual ?? $previousManual;
            @endphp
        </div>

        <footer>
            <div class="row fs-85" style="width: 100%; padding: 5px 0;">
                <div class="col-3 text-start">
                    {{__('Form #016')}}
                </div>
                <div class="col-3 text-center">
                    {{__('Page')}} {{ $loop->iteration }} {{__('of')}} {{ count($componentChunks) }}
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
<script src="{{ asset('js/table-height-adjuster.js') }}"></script>
<script>
// Предотвращаем ошибки Chart.js, если он загружен глобально
if (typeof Chart !== 'undefined') {
    // Переопределяем identifyDuplicates для предотвращения ошибок
    const originalIdentifyDuplicates = Chart.helpers.identifyDuplicates;
    if (originalIdentifyDuplicates) {
        Chart.helpers.identifyDuplicates = function(statements) {
            if (!statements || !Array.isArray(statements)) {
                return [];
            }
            try {
                return originalIdentifyDuplicates.call(this, statements);
            } catch (e) {
                console.warn('Chart.js identifyDuplicates error:', e);
                return [];
            }
        };
    }
}

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

    // Настройка высоты всех таблиц после загрузки (только визуальная настройка)
    // Пустые строки уже сгенерированы на бэкенде
    setTimeout(function() {
        const dataPages = document.querySelectorAll('.data-page');
        
        dataPages.forEach(function(pageContainer, pageIndex) {
            const ndtRows = pageContainer.querySelectorAll('.data-row-ndt');
            
            if (ndtRows.length > 0) {
                // Только визуальная настройка высоты таблицы
                // Не добавляем/удаляем строки - это уже сделано на бэкенде
                adjustTableHeightToRange({
                    min_height_tab: 500,
                    max_height_tab: 600,
                    tab_name: pageContainer,
                    row_height: 32,
                    row_selector: '.data-row-ndt[data-row-index]',
                    addRowCallback: function() {}, // Не добавляем строки - они уже на бэкенде
                    removeRowCallback: function() {}, // Не удаляем строки - только пустые можно удалить
                    getRowIndexCallback: function(rowElement) {
                        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
                    },
                    max_iterations: 50,
                    onComplete: function(currentHeight, rowCount) {
                        console.log(`NDT страница ${pageIndex + 1}: высота настроена - ${currentHeight}px, строк ${rowCount}`);
                    }
                });
            }
        });
    }, 200);
});
</script>
</body>
</html>
