<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAD PLATE PROCESS SHEET</title>
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
            @page {
                size: letter;
                margin: 1mm;
            }

            html, body {
                height: 99%;
                width: 98%;
                margin-left: 2px;
                padding: 0;
            }

            table, h1, p {
                page-break-inside: avoid;
            }

            .no-print {
                display: none;
            }

            footer {
                position: fixed;
                bottom: 0;
                width: 800px;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 2px 2px;
            }

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
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
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

        .fs-7 {
            font-size: 0.9rem;
        }
        .fs-75 {
            font-size: 0.8rem;
        }
        .fs-85 {
            font-size: 0.85rem;
        }
        .fs-8 {
            font-size: 0.7rem;
        }

        .details-row {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 36px;
        }
        .description-text-long {
            font-size: 0.9rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: top;
        }
        .data-row {
            min-height: 32px;
            max-height: 32px;
        }
        .data-row .details-cell {
            height: 32px !important;
            max-height: 32px !important;
            min-height: 32px;
            overflow: hidden;
            box-sizing: border-box;
        }
        .details-cell {
            display: flex;
            justify-content: center;
            align-items: center;
            line-height: 1.1;
            padding: 2px 4px;
            word-wrap: break-word;
            word-break: break-word;
        }
        .data-row {
            min-height: 32px;
            max-height: 32px;
            box-sizing: border-box;
        }
        .data-row .details-cell {
            height: 32px !important;
            max-height: 32px !important;
            min-height: 32px;
            overflow: hidden;
            box-sizing: border-box;
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
            <div class="col-3">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 180px; margin: 6px 10px 0;">
            </div>
            <div class="col-9">
                <h2 class="mt-3 text-black"><strong>CAD PLATE PROCESS SHEET</strong></h2>
            </div>
        </div>
        <div class="row ">
            <div class="col-6">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>
{{--                            {{$current_wo->description}}--}}
                            <span @if(strlen($current_wo->description) > 30) class="description-text-long"
                                @endif>{{$current_wo->description}}</span>
                        </strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>PART NUMBER:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>{{$current_wo->unit->part_number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>WORK ORDER No:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>W{{$current_wo->number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>SERIAL No:</strong></div>
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
                    <div class="col-8 pt-2 border-b"><strong> Micro Custom</strong></div>
                </div>
                <div class="row" style="height: 32px">
{{--                    <div class="col-4 pt-2 text-end"><strong>TOTAL QTY:</strong></div>--}}
{{--                    <div class="col-8 pt-2 border-b">--}}
{{--                        @if(isset($total_quantities['total_qty']))--}}
{{--                            {{ $total_quantities['total_qty'] }}--}}
{{--                        @endif--}}
{{--                    </div>--}}
{{--                </div>--}}
            </div>

        </div>
           <h5 class="ps-3 mt-2 mb-2 ">
               @foreach($manuals as $manual)
                   @if($manual->id == $current_wo->unit->manual_id)
                       <h6 class="mt-2 ps-4">
                           <strong class="">
                           {{__('Perform the CAD plate as specified under Process No. and in accordance with SMM No. ')}}
                            <span class="ms-5">
                                {{substr($manual->number, 0, 8)}}
                            </span>
                          </strong>
                       </h6>
                   @endif
               @endforeach
           </h5>
    </div>

    <div class="page table-header">
        <div class="row mt-2">
            <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7">
                    <strong>ITEM No.</strong></h6></div>
            <div class="col-2 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PART No.</strong></h6></div>
            <div class="col-3 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6
                    class="fs-7"><strong>DESCRIPTION</strong></h6></div>
            <div class="col-3 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PROCESS
                        No.</strong></h6></div>
            <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>QTY</strong></h6></div>
            <div class="col-2 border-all pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>CMM No.</strong></h6></div>
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
                    <div class="col-3">
                        <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                             style="width: 180px; margin: 6px 10px 0;">
                    </div>
                    <div class="col-9">
                        <h2 class="mt-3 text-black"><strong>CAD PLATE PROCESS SHEET</strong></h2>
                    </div>
                </div>
                <div class="row ">
                    <div class="col-6">
                        <div class="row" style="height: 32px">
                            <div class="col-6 pt-2 text-end"><strong>COMPONENT NAME</strong> :</div>
                            <div class="col-6 fs-7 pt-2 border-b"><strong>
                                    <span @if(strlen($current_wo->description) > 30) class="description-text-long"
                                        @endif>{{$current_wo->description}}</span>
                                </strong></div>
                        </div>
                        <div class="row" style="height: 32px">
                            <div class="col-6 pt-2 text-end"><strong>PART NUMBER:</strong></div>
                            <div class="col-6 fs-7 pt-2 border-b"><strong>{{$current_wo->unit->part_number}}</strong></div>
                        </div>
                        <div class="row" style="height: 32px">
                            <div class="col-6 pt-2 text-end"><strong>WORK ORDER No:</strong></div>
                            <div class="col-6 fs-7 pt-2 border-b"><strong>W{{$current_wo->number}}</strong></div>
                        </div>
                        <div class="row" style="height: 32px">
                            <div class="col-6 pt-2 text-end"><strong>SERIAL No:</strong></div>
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
                            <div class="col-8 pt-2 border-b"><strong> Micro Custom</strong></div>
                        </div>
                    </div>
                </div>
                <h5 class="ps-3 mt-2 mb-2 ">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h6 class="mt-4 ps-4">
                                <strong class="">
                                {{__('Perform the CAD plate as specified under Process No. and in accordance with SMM No. ')}}
                                <span class="ms-5">
                                    {{substr($manual->number, 0, 8)}}
                                </span>
                              </strong>
                           </h6>
                        @endif
                    @endforeach
                </h5>
            </div>
            <div class="page table-header">
                <div class="row mt-2">
                    <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7">
                            <strong>ITEM No.</strong></h6></div>
                    <div class="col-2 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PART No.</strong></h6></div>
                    <div class="col-3 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6
                            class="fs-7"><strong>DESCRIPTION</strong></h6></div>
                    <div class="col-3 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PROCESS
                                No.</strong></h6></div>
                    <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>QTY</strong></h6></div>
                    <div class="col-2 border-all pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>CMM No.</strong></h6></div>
                </div>
            </div>
        @endif

        <div class="page data-page" data-page-index="{{ $loop->iteration }}">
            @php
                // Используем данные из chunkInfo, рассчитанные на бэкенде
                $chunk = $chunkInfo['components'];
                $previousManual = $previousChunkLastManual;
                $chunkLastManual = null;
                $rowIndex = 1;
                $isLastPage = $loop->last;
            @endphp

            @foreach($chunk as $component)
            @php
                $currentManual = $component->manual ?? null;
                $shouldInsertManualRow = ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);

                // Логирование для отладки
                if (in_array($component->ipl_num ?? '', ['2-260', '4-100'])) {
                    \Log::info('CAD FormStd - Rendering component', [
                        'chunk_index' => $loop->parent->iteration,
                        'component_index' => $loop->iteration,
                        'ipl_num' => $component->ipl_num ?? '',
                        'qty' => $component->qty ?? 0,
                        'row_index' => $rowIndex
                    ]);
                }
            @endphp

            @if($shouldInsertManualRow)
                {{-- Строка с Manual --}}
                <div class="row fs-85 data-row manual-row" data-row-index="{{ $rowIndex }}">
                    <div class="col-1 border-l-b details-cell text-center" style="height: 32px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b details-cell text-center" style="height: 32px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b details-cell text-center" style="height: 32px; font-weight: bold;">
                        <strong>{{ $currentManual }}</strong>
                    </div>
                    <div class="col-3 border-l-b details-cell text-center" style="height: 32px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b details-cell text-center" style="height: 32px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b-r details-cell text-center" style="height: 32px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                </div>
                @php $rowIndex++; @endphp
            @endif

            <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-cell text-center" style="height: 32px !important; max-height: 32px; line-height: 1.1; overflow: hidden; box-sizing: border-box;">
                    {{ $component->ipl_num }}
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="height: 32px !important; max-height: 32px; line-height: 1.1; overflow: hidden; box-sizing: border-box;">
                    {{ $component->part_number }}
                </div>
                <div class="col-3 border-l-b details-cell text-center" style="height: 32px !important; max-height: 32px; line-height: 1.1; overflow: hidden; box-sizing: border-box;">
                    {{ $component->name }}
                </div>
                <div class="col-3 border-l-b details-cell text-center process-cell" style="height: 32px !important; max-height: 32px; line-height: 1.1; overflow: hidden; box-sizing: border-box;">
                    {{ $component->process_name }}
                </div>
                <div class="col-1 border-l-b details-cell text-center" style="height: 32px !important; max-height: 32px; line-height: 1.1; overflow: hidden; box-sizing: border-box;">
                    {{ $component->qty }}
                </div>
                <div class="col-2 border-l-b-r details-cell text-center" style="height: 32px !important; max-height: 32px; line-height: 1.1; overflow: hidden; box-sizing: border-box;">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <span style="font-size: 0.85rem;">{{substr($manual->number, 0, 8)}}</span>
                        @endif
                    @endforeach
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
                    <div class="row fs-85 data-row empty-row" data-row-index="{{ $rowIndex }}">
                        <div class="col-1 border-l-b details-cell text-center" style="height: 32px"></div>
                        <div class="col-2 border-l-b details-cell text-center" style="height: 32px"></div>
                        <div class="col-3 border-l-b details-cell text-center" style="height: 32px"></div>
                        <div class="col-3 border-l-b details-cell text-center" style="height: 32px"></div>
                        <div class="col-1 border-l-b details-cell text-center" style="height: 32px"></div>
                        <div class="col-2 border-l-b-r details-cell text-center" style="height: 32px"></div>
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
                <div class="col-6 text-start">
                    {{__('Form # 014')}}
                </div>
                <div class="col-3 text-center">
                    {{__('Page')}} {{ $loop->iteration }} {{__('of')}} {{ count($componentChunks) }}
                </div>
                <div class="col-3 text-end pe-4">
                    {{__('Rev#0, 15/Dec/2012   ')}}
                    <br>
                    {{'Total: '}} {{ $cadSum['total_qty'] }}
                </div>
            </div>
        </footer>

        @php
            // Сохраняем последний manual для следующего chunk
            $previousChunkLastManual = $chunkLastManual ?? $previousManual;
        @endphp

        @if(!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</div>
<!-- Подключение библиотеки table-height-adjuster -->
<script src="{{ asset('js/table-height-adjuster.js') }}"></script>

<!-- Общие модули -->
<script src="{{ asset('js/tdrs/forms/common/multi-page-handler.js') }}"></script>

<!-- Модули для CAD формы -->
<script src="{{ asset('js/tdrs/forms/cad/cad-row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/cad/cad-form-main.js') }}"></script>

</body>
</html>

