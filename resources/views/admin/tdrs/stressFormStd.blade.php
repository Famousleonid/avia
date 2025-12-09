<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STRESS RELIEF PROCESS SHEET</title>
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

        .process-text-long {
            font-size: 0.9em;
            line-height: 1;
            letter-spacing: -0.3px;
            display: inline-block;
            transform-origin: left;

        }
        .description-text-long {
            font-size: 0.9rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: top;
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
        .details-cell {
            display: flex;
            justify-content: center;
            align-items: center;
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
                <h2 class="mt-3 text-black"><strong>STRESS RELIEF PROCESS SHEET</strong></h2>
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
                    <div class="col-8 pt-2 border-b">INTERNAL</div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>VENDOR:</strong></div>
                    <div class="col-8 pt-2 border-b"><strong> AVIATECHNIK</strong></div>
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
           <h5 class="ps-3 mt-2 mb-2 ">
               @foreach($manuals as $manual)
                   @if($manual->id == $current_wo->unit->manual_id)
                       <h6 class="ps-4">
                           <strong class="">
                           {{__('Perform the Stress Relief as specified under Process No. and in accordance with SMM No. ')}}
{{--                            <span class="ms-5">--}}
{{--                                {{$manual->number}}--}}
{{--                            </span>--}}
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
            <div class="col-2 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>DESCRIPTION</strong></h6></div>
            <div class="col-4 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PROCESS No.</strong></h6></div>
            <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>QTY</strong></h6></div>
            <div class="col-2 border-all pt-2  details-row  text-center" style="height: 42px">
                <h6  class="fs-7" ><strong>PERFORMED</strong> </h6>
            </div>
        </div>
    </div>

    <div class="page data-page">
        @php
            $totalRows = 17;
            $dataRows = count($stress_components);
            $emptyRows = $totalRows - $dataRows;
            $rowIndex = 1;
        @endphp

        @foreach($stress_components as $component)
            <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->ipl_num }}
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->part_number }}
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->name }}
                </div>
                <div class="col-4 border-l-b details-cell text-center process-cell" style="min-height: 34px">
                    {{ $component->process_name }}
                </div>
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->qty }}
                </div>
                <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px">
{{--                    @foreach($manuals as $manual)--}}
{{--                        @if($manual->id == $current_wo->unit->manual_id)--}}
{{--                            <h6 class="text-center mt-3">{{$manual->number}}</h6>--}}
{{--                        @endif--}}
{{--                    @endforeach--}}
                </div>
            </div>
            @php $rowIndex++; @endphp
        @endforeach

        @for ($i = 0; $i < $emptyRows; $i++)
            <div class="row empty-row" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b text-center" style="height: 32px"></div>
                <div class="col-2 border-l-b text-center" style="height: 32px"></div>
                <div class="col-2 border-l-b text-center" style="height: 32px"></div>
                <div class="col-4 border-l-b text-center" style="height: 32px"></div>
                <div class="col-1 border-l-b text-center" style="height: 32px"></div>
                <div class="col-2 border-l-b-r text-center" style="height: 32px"></div>
            </div>
            @php $rowIndex++; @endphp
        @endfor
    </div>


    <footer>
        <div class="row fs-85" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__('Form # 015')}}
{{--                {{$form_number}}--}}
            </div>
            <div class="col-6 text-end pe-4">
                {{__('Rev#0, 15/Dec/2012   ')}}
                <br>
                {{'Total: '}} {{ $stressSum['total_qty'] }}
            </div>
        </div>
    </footer>
</div>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Функция для добавления пустой строки
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

        // Функция для удаления строки
        function removeRowRegular(rowIndex, tableElement) {
            const container = typeof tableElement === 'string'
                ? document.querySelector(tableElement)
                : tableElement;
            if (!container) return;

            const row = container.querySelector(`[data-row-index="${rowIndex}"]`);
            if (row) row.remove();
        }

        // Настройка высоты таблицы
        setTimeout(function() {
            const regularTableContainer = document.querySelector('.data-page');
            const regularRows = document.querySelectorAll('.data-page .data-row');
            if (regularTableContainer && regularRows.length > 0) {
                adjustTableHeightToRange({
                    min_height_tab: 650,
                    max_height_tab: 700,
                    tab_name: '.data-page',
                    row_height: 34,
                    row_selector: '.data-page [data-row-index]',
                    addRowCallback: addEmptyRowRegular,
                    removeRowCallback: removeRowRegular,
                    getRowIndexCallback: function(rowElement) {
                        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
                    },
                    max_iterations: 50,
                    onComplete: function(currentHeight, rowCount) {
                        console.log(`Stress Relief таблица настроена: высота ${currentHeight}px, строк ${rowCount}`);
                    }
                });
            }

            // Старый код для удаления пустых строк на основе высоты ячеек процесса
            var processCells = document.querySelectorAll('.data-row .process-cell');
            var totalExtraLines = 0;

            processCells.forEach(function(cell) {
                var cellHeight = cell.offsetHeight;
                if(cellHeight > 32) {
                    var extraLines = Math.floor((cellHeight - 32) / 16);
                    totalExtraLines += extraLines;
                }
            });

            var emptyRowsToRemove = Math.floor(totalExtraLines / 2);
            var emptyRows = document.querySelectorAll('.empty-row');
            for (var i = 0; i < emptyRowsToRemove && i < emptyRows.length; i++) {
                if (emptyRows[i] && !emptyRows[i].hasAttribute('data-keep')) {
                    emptyRows[i].remove();
                }
            }
        }, 200);
    });
</script>

</html>
