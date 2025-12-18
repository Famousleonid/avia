<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Process Form - Employee</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 980px;
            height: auto;
            transform: scale(0.94);
            transform-origin: top;
            padding: 1px;
            margin-left: 10px;
            margin-right: 10px;
        }

        @media print {
            @page {
                size: 11in 8.5in;
                margin: 1mm;
            }

            html, body {
                height: auto;
                width: auto;
                margin-left: 3px;
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
                width: 1060px;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 1px 1px;
            }

            .container {
                max-height: 100vh;
                overflow: hidden;
            }
            .border-r {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
            border-left: 8px solid lightgrey;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-r {
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
        .border-l-t {
            border-left: 1px solid black;
            border-top: 1px solid black;
        }
        .border-l {
            border-left: 1px solid black;
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
        .border-t {
            border-top: 1px solid black;
        }
        .border-tt-gr {
            border-top: 3px solid gray;
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
        .fs-9 {
            font-size: 0.9rem;
        }
        .fs-75 {
            font-size: 0.75rem;
        }
        .fs-7 {
            font-size: 0.7rem;
        }
        .fs-8 {
            font-size: 0.8rem;
        }
        .fs-6 {
            font-size: 0.6rem;
        }
        .fs-4 {
            font-size: 0.4rem;
        }

        .details-row {
            display: flex;
            align-items: center;
            height: 36px;
        }
        .details-cell {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid black;
        }
        .check-icon {
            width: 24px;
            height: auto;
            margin: 0 5px;
        }
        .page-break {
            page-break-after: always;
        }

        .parent {
            display: grid;
            grid-template-columns: 525px 45px 170px;
            gap: 0px;
        }

        /* Стили для заполненных данных */
        .filled-data {
            background-color: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>

<body>
<!-- Кнопка для печати -->
<div class="text-start m-3">
    <button class="btn btn-primary no-print" onclick="window.print()">
        Print Form
    </button>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-1">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 160px; margin: 0px 6px 0;">
        </div>
        <div class="col-11">
            <h5 class="text-black text-center"><strong>Special Process Form</strong></h5>
        </div>
    </div>

    <div>
        <div class="row">
            <div class="col-6">
                <div class="d-flex" style="width: 415px">
                    <div style="width: 90px"></div>
                    <div class="fs-8 pt-3" style="width: 20px">qty</div>
                    <div class="fs-8 pt-2" style="width: 115px;height: 20px">MPI</div>
                    <div class="fs-8 pt-2" style="width: 20px">FPI</div>
                    <div class="fs-8 pt-3" style="width: 20px">qty</div>
                    <div class="text-center fs-8" style="width: 20px;height: 20px"></div>
                    <div class="fs-8 pt-2 text-end" style="width: 95px">CAD</div>
                    <div class="fs-8 pt-3 text-center" style="width: 30px">qty</div>
                </div>
            </div>
            <div class="col-2 pt-2 border-b text-center filled-data">
                <strong>W{{ $current_wo->number }}</strong>
            </div>
            <div class="col-md-5"></div>
        </div>

        <div class="d-flex" style="width: 960px">

            <div class="text-end">
                <h6 class="pt-1 fs-8" style="width: 60px;"><strong>Cat #1</strong></h6>
            </div>
            <div class="fs-8">
                <img src="{{ asset('img/icons/icons8-right-arrow.gif')}}" alt="arrow"
                     style="width: 24px;height: 20px">
            </div>
            <div class="border-l-t-b text-center pt-0 fs-75 filled-data" style="width: 25px;height: 20px">
                @if($current_wo->instruction_id==1)
                    {{ !isset($ndtSums['mpi']) || $ndtSums['mpi'] == null ? ' ' : $ndtSums['mpi'] }}
                @else
                    {{__(' ')}}
                @endif

            </div>
            <div class="border-l-t-b ps-2 fs-8" style="width: 130px;height: 20px; color: lightgray; font-style: italic">
                RO No.
            </div>
            <div class="border-all text-center pt-0 fs-75 filled-data" style="width: 25px;height: 20px">
                @if($current_wo->instruction_id==1)
                {{ !isset($ndtSums['fpi']) || $ndtSums['fpi'] == null || $ndtSums['fpi'] === 0 ? ' ' : $ndtSums['fpi'] }}
                @else
                    {{__(' ')}}
                @endif

            </div>
            <div class="text-center fs-8" style="width: 20px;height: 20px"></div>
            <div class="border-l-t-b ps-2 fs-8" style="width: 100px;height: 20px; color: lightgray; font-style: italic">
                RO No.
            </div>
            <div class="border-all text-center pt-0 fs-75 filled-data" style="width: 25px;height: 20px">
                @php
                    $a = $cadSum['total_qty'] ?? null;
                    $b = $cadSum_ex ?? null;
                    $hasA = isset($a) && $a !== '' && $a !== 0;
                    $hasB = isset($b) && $b !== '' && $b !== 0;
                    $result = ($hasA && $hasB) ? ((int)$a + (int)$b) : (($hasA ? (int)$a : ($hasB ? (int)$b : null)));
                @endphp
                @if($current_wo->instruction_id==1)
                     {{ ($result !== null && $result > 0) ? $result : ' ' }}
                @else
                    {{($cadSum_ex >0) ? $cadSum_ex : ' ' }}
                @endif
            </div>
            <div class="text-center fs-7" style="width: 305px;height: 20px"></div>
            <div class="text-end pt-2 fs-8" style="width: 75px;height: 10px">Technician</div>
            <div class="border-b" style="width: 120px"></div>
            <div class="border-l-t-r" style="width: 40px;height: 28px"></div>
        </div>

        <div class="d-flex">
            <div class="text-end fs-7 pe-4" style="width: 880px; height: 15px">Name</div>
            <div class="" style="width: 29px"></div>
            <div class="border-l-b-r" style="width: 40px;height: 12px"></div>
        </div>
    </div>

    <div class="d-flex mb-1">
        <div class="" style="width: 80px"></div>
        <img src="{{ asset('img/icons/arrow_ld.png')}}" alt="arrow"
             style="height: 5px;width: 60px" class="mt-2">
        <div class="border-b fs-7" style="width: 300px; height: 15px">
            <strong>Cat #2 (not included in NDT & Cad Cat #1)</strong>
        </div>
    </div>

    <!-- Таблица компонентов -->
    <div class="row g-0 fs-7">
        <!-- Заголовок "Description" -->
        <div class="col-2 border-l-t-b ps-1">
            <div style="height: 20px"><strong>Description</strong></div>
        </div>
        <!-- Основная часть таблицы -->
        <div class="col-10">
            <!-- Строка для имен компонентов -->
            <div class="row g-0">
                @foreach($tdr_ws as $index => $component)
                    <div class="col {{ $index < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 22px">
                        @php
                            $nameLength = mb_strlen($component->component->name);
                            $fontSize = $nameLength > 20 ? round(20 / $nameLength, 2) . 'em' : '1em';
                        @endphp
                        <span style="font-size: {{ $fontSize }};">
                            {{ $component->component->name }}
                        </span>
                    </div>
                @endforeach

                @for($i = count($tdr_ws); $i < 6; $i++)
                    <div class="col {{ $i < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 22px">
                        {{ __(' ') }}
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Строка для Part No. -->
    <div class="row g-0 fs-7">
        <div class="col-2 border-l-b ps-1">
            <div style="height: 20px"><strong>Part No.</strong></div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @foreach($tdr_ws as $index => $component)
                    <div class="col {{ $index < 5 ? 'border-l-b' : 'border-l-b-r'}} text-center" style="height: 22px">
                        {{ $component->component->part_number }}
                    </div>
                @endforeach
                @for($i = count($tdr_ws); $i < 6; $i++)
                    <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r'}} text-center" style="height: 22px">
                        {{ __(' ') }}
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Строка для Serial No. -->
    <div class="row g-0 fs-7">
        <div class="col-2 border-l-b ps-1">
            <div style="height: 20px"><strong>Serial No.</strong></div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @foreach($tdr_ws as $index => $component)
                    <div class="col {{ $index < 5 ? 'border-l-b' : 'border-l-b-r'}} text-center" style="height: 22px">
                        {{ $component->serial_number }}
                    </div>
                @endforeach
                @for($i = count($tdr_ws); $i < 6; $i++)
                    <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r'}} text-center" style="height: 22px">
                        {{ __(' ') }}
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Секция процессов -->
    <div class="row g-0 border-tt-gr">
        <div class="col-2">
            <div class="fs-8 text-end mb-1" style="height: 15px"><strong>Steps sequence</strong>
                <img src="{{ asset('img/icons/arrow_rd.png')}}" alt="arrow"
                     style="height: 10px; margin-right: -15px" class="mt-2">
            </div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @for($i = 0; $i < 6; $i++)
                    <div class="col fs-8 text-center" style="height: 15px">
                        <strong>RO No.</strong>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- NDT процессы -->
    <div class="row g-0 fs-7">
        <div class="col-2 border-l-t ps-1">
            <div style="height: 18px"><strong></strong></div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @foreach($tdr_ws as $index => $component)
                    @php
                        $currentTdrId = $component->id;
                        $ndtForCurrentTdr = collect($ndt_processes)
                            ->where('tdrs_id', $currentTdrId)
                            ->values();
                    @endphp
                    <div class="col {{ $index < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 20px">
                        @if(isset($ndtForCurrentTdr[0]))
                            <div class="border-r filled-data" style="height: 20px; width: 30px">
                                {{ $ndtForCurrentTdr[0]['number_line'] }}
                            </div>
                        @else
                            <div class="border-r" style="height: 20px; width: 30px"></div>
                        @endif
                    </div>
                @endforeach
                @for($i = count($tdr_ws); $i < 6; $i++)
                    <div class="col {{ $i < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 20px; position: relative;">
                        <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- NDT -->
    <div class="row g-0 fs-7">
        <div class="col-2 border-l ps-1">
            <div style="height: 18px"><strong>N.D.T.</strong></div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @foreach($tdr_ws as $index => $component)
                    @php
                        $currentTdrId = $component->id;
                        $ndtForCurrentTdr = collect($ndt_processes)
                            ->where('tdrs_id', $currentTdrId)
                            ->values();
                    @endphp
                    <div class="col {{ $index < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 20px">
                        @if(isset($ndtForCurrentTdr[1]))
                            <div class="border-r filled-data" style="height: 20px; width: 30px">
                                {{ $ndtForCurrentTdr[1]['number_line'] }}
                            </div>
                        @else
                            <div class="border-r" style="height: 20px; width: 30px"></div>
                        @endif
                    </div>
                @endforeach
                @for($i = count($tdr_ws); $i < 6; $i++)
                    <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 20px; position: relative;">
                        <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Дополнительные NDT процессы -->
    <div class="row g-0 fs-7">
        <div class="col-2 border-l-b ps-1">
            <div style="height: 18px"><strong></strong></div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @foreach($tdr_ws as $index => $component)
                    @php
                        $currentTdrId = $component->id;
                        $ndtForCurrentTdr = collect($ndt_processes)
                            ->where('tdrs_id', $currentTdrId)
                            ->values();
                    @endphp
                    <div class="col {{ $index < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 20px">
                        @if(isset($ndtForCurrentTdr[2]))
                            <div class="border-r filled-data" style="height: 20px; width: 30px">
                                {{ $ndtForCurrentTdr[2]['number_line'] }}
                            </div>
                        @else
                            <div class="border-r" style="height: 20px; width: 30px"></div>
                        @endif
                    </div>
                @endforeach
                @for($i = count($tdr_ws); $i < 6; $i++)
                    <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 20px; position: relative;">
                        <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Другие процессы -->
    @foreach($processNames as $name)
        <div class="row g-0 fs-7">
            <div class="col-2 border-l-b ps-1">
                <div style="height: 17px"><strong>{{ $name->name }}</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @foreach($tdr_ws as $index => $component)
                        @php
                            $currentTdrId = $component->id;
                            $processForCurrentTdr = $processes
                                ->where('process_name_id', $name->id)
                                ->where('tdrs_id', $currentTdrId)
                                ->values();
                            $numberLines = $processForCurrentTdr->pluck('number_line')->implode(',');
                        @endphp
                        <div class="col {{ $index < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 20px">
                            @if($numberLines)
                                <div class="border-r filled-data" style="height: 20px; width: 30px">
                                    {{ $numberLines }}
                                </div>
                            @else
                                <div class="border-r" style="height: 20px; width: 30px"></div>
                            @endif
                        </div>
                    @endforeach
                    @for($i = count($tdr_ws); $i < 6; $i++)
                        <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 20px; position: relative;">
                            <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endforeach

    <!-- Quality Assurance -->
    <div class="parent mt-1">
        <div class="div2 text-end pe-4 mt-2" style="height: 24px">Quality Assurance Acceptance</div>
        <div class="div3 border-all text-center fs-75" style="width: 45px; align-content: center; height: 42px; color: grey">Q.A. STAMP</div>
        <div class="div4 border-t-r-b fs-75 mt-2 ps-2 pt-1" style="height: 24px; color: grey">Data</div>
    </div>
</div>

<footer>
    <div class="row" style="width: 100%; padding: 5px 5px;">
        <div class="col-6 text-start">
            {{__("Form #012")}}
        </div>
        <div class="col-6 text-end pe-4">
            {{__('Rev#0, 15/Dec/2012')}}
        </div>
    </div>
</footer>

<!-- Скрипт для печати -->
<script>
    function printForm() {
        window.print();
    }
</script>
</body>
</html>
