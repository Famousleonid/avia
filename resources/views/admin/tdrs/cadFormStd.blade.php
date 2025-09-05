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
                <h2 class="mt-3 text-black"><strong>CAD PLATE PROCESS SHEET</strong></h2>
            </div>
        </div>
        <div class="row ">
            <div class="col-6">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>{{$current_wo->description}}</strong></div>
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
                       <h6 class="ps-4">
                           <strong class="">
                           {{__('Perform the CAD plate as specified under Process No. and in accordance with SMM No. ')}}
                            <span class="ms-5">
                                {{$manual->number}}
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
            <div class="col-2 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>DESCRIPTION</strong></h6></div>
            <div class="col-4 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PROCESS No.</strong></h6></div>
            <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>QTY</strong></h6></div>
            <div class="col-2 border-all pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>CMM No.</strong></h6></div>
        </div>
    </div>

    <div class="page data-page">
        @php
            $totalRows = 18;
            $dataRows = count($cad_components);
            $emptyRows = $totalRows - $dataRows;
        @endphp

        @foreach($cad_components as $component)
            <div class="row fs-85 data-row">
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 32px">
                    {{ $component->ipl_num }}
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 32px">
                    {{ $component->part_number }}
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 32px">
                    {{ $component->name }}
                </div>
                <div class="col-4 border-l-b details-cell text-center process-cell" style="min-height: 32px">
                    {{ $component->process_name }}
                </div>
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 32px">
                    {{ $component->qty }}
                </div>
                <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 32px">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h6 class="text-center mt-3">{{$manual->number}}</h6>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        @for ($i = 0; $i < $emptyRows; $i++)
            <div class="row empty-row">
                <div class="col-1 border-l-b text-center" style="height: 34px"></div>
                <div class="col-2 border-l-b text-center" style="height: 34px"></div>
                <div class="col-2 border-l-b text-center" style="height: 34px"></div>
                <div class="col-4 border-l-b text-center" style="height: 34px"></div>
                <div class="col-1 border-l-b text-center" style="height: 34px"></div>
                <div class="col-2 border-l-b-r text-center" style="height: 34px"></div>
            </div>
        @endfor
    </div>


    <footer>
        <div class="row fs-85" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__('Form # 014')}}
{{--                {{$form_number}}--}}
            </div>
            <div class="col-6 text-end pe-4">
                {{__('Rev#0, 15/Dec/2012   ')}}
                <br>
                {{'Total: '}} {{ $cadSum['total_qty'] }}
            </div>
        </div>
    </footer>
</div>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function() {
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
            emptyRows[i].remove();
        }
    });
</script>

</html>
