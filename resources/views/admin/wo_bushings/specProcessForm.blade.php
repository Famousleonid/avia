<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Process Form - Bushings</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        :root {
            --container-max-width: 980px;
            --container-padding: 3px;
            --container-margin-left: 10px;
            --container-margin-right: 10px;
            --container-scale: 0.97;
            --print-page-margin: 2mm;
            --print-body-margin-left: 3px;
            --table-font-size: 0.9rem;
            --part-no-font-size: 0.1rem;
            --print-footer-width: 1060px;
            --print-footer-font-size: 10px;
            --print-footer-padding: 2px 2px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: var(--container-max-width);
            height: auto;
            transform: scale(var(--container-scale));
            transform-origin: top;
            padding: var(--container-padding);
            margin-left: var(--container-margin-left);
            margin-right: var(--container-margin-right);
        }

        /* Применение font-size ко всей таблице */
        .container-fluid .row.g-0,
        .container-fluid .row.g-0 strong,
        .container-fluid .row.g-0 span,
        .container-fluid .row.g-0 div,
        .container-fluid .row.g-0 h6,
        .container-fluid .parent,
        .container-fluid .parent div,
        .container-fluid .d-flex,
        .container-fluid .d-flex strong,
        .container-fluid .d-flex span,
        .container-fluid .d-flex div {
            font-size: var(--table-font-size) !important;
        }

        /* Применение font-size к данным Part No. (только данные, не заголовок) */
        .part-no-data {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 2px !important;
            text-align: center !important;
            align-items: start !important;
        }
        /* Более специфичный селектор для переопределения общего правила .row.g-0 div */
        .container-fluid .row.g-0 .part-no-data,
        .container-fluid .row.g-0 .part-no-data div {
            font-size: var(--part-no-font-size) !important;
        }
        .part-no-data div {
            line-height: 1.2 !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }

        /* Заголовок "Part No." использует table-font-size */
        /* Заголовок уже получает table-font-size через общее правило .row.g-0 div */
        /* Но для явности: убеждаемся, что заголовок не переопределяется */
        .row.g-0 > .col-2 > div strong {
            font-size: var(--table-font-size) !important;
        }

        @media print {
            @page {
                size: 11in 8.5in;
                margin: var(--print-page-margin);
            }

            html, body {
                height: auto;
                width: auto;
                margin-left: var(--print-body-margin-left);
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
                width: var(--print-footer-width);
                text-align: center;
                font-size: var(--print-footer-font-size);
                background-color: #fff;
                padding: var(--print-footer-padding);
            }

            .container {
                max-height: 100vh;
                overflow: hidden;
            }
            .border-r {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Применение font-size ко всей таблице при печати */
            .container-fluid .row.g-0,
            .container-fluid .row.g-0 strong,
            .container-fluid .row.g-0 span,
            .container-fluid .row.g-0 div,
            .container-fluid .row.g-0 h6,
            .container-fluid .parent,
            .container-fluid .parent div,
            .container-fluid .d-flex,
            .container-fluid .d-flex strong,
            .container-fluid .d-flex span,
            .container-fluid .d-flex div {
                font-size: var(--table-font-size) !important;
            }

            /* Применение font-size к данным Part No. при печати (только данные, не заголовок) */
            .part-no-data {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 2px !important;
                text-align: center !important;
                align-items: start !important;
            }
            /* Более специфичный селектор для переопределения общего правила .row.g-0 div при печати */
            .container-fluid .row.g-0 .part-no-data,
            .container-fluid .row.g-0 .part-no-data div {
                font-size: var(--part-no-font-size) !important;
            }
            .part-no-data div {
                line-height: 1.2 !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
            }

            /* Заголовок "Part No." использует table-font-size при печати */
            .row.g-0 > .col-2 > div strong {
                font-size: var(--table-font-size) !important;
            }
        }

        .border-r {
            border-right: 1px solid black;
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
        .border-l-b-r {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
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
            border-top: 6px solid dimgrey;
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
        .fs-8 {
            font-size: 0.8rem;
        }
        .fs-7 {
            font-size: 0.7rem;
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
            width: 30px;
            height: auto;
            margin: 0 5px;
        }
        .page-break {
            page-break-after: always;
        }


        .parent {
            display: grid;
            grid-template-columns: 518px 60px 155px ;
            /*grid-template-columns: repeat(12, 1fr);*/
            /*grid-template-rows: repeat(3, 1fr);*/
            gap: 0px;
        }

        /*.div2 {*/
        /*    grid-column: span 6 / span 6;*/
        /*    grid-column-start: 1;*/
        /*    grid-row-start: 2;*/
        /*}*/

        /*.div3 {*/
        /*    grid-row: span 3 / span 3;*/
        /*    grid-column-start: 7;*/
        /*    grid-row-start: 1;*/
        /*}*/

        /*.div4 {*/
        /*    grid-column: span 2 / span 2;*/
        /*    grid-column-start: 8;*/
        /*    grid-row-start: 2;*/
        /*}*/
        /*.div5 {*/
        /*    grid-column: span 3 / span 3;*/
        /*    grid-column-start: 10;*/
        /*    grid-row-start: 2;*/
        /*}*/



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

@php
    $componentsPerPage = 6;
    // Создаем базовую структуру для отображения
    $componentChunks = collect([1])->chunk($componentsPerPage);
@endphp

@foreach($componentChunks as $chunk)
    <div class="container-fluid">
        <div class="row">
            <div class="col-1">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 160px; margin: 6px 10px 0;">
            </div>
            <div class="col-11">
                <h5 class="pt-1  text-black text-center"><strong>Special Process Form </strong></h5>
            </div>
        </div>
        <div>
            <div class="row">
                <div class="col-6">
                    <div class="d-flex" style="width: 435px">
                        <div style="width: 92px"></div>
                        <div class=" pt-3" style="width: 25px">qty</div>
                        <div class=" pt-2" style="width: 114px;height: 20px">MPI</div>
                        <div class=" pt-2" style="width: 20px">FPI</div>
                        <div class=" pt-3" style="width: 22px">qty</div>
                        <div class=" text-center " style="width: 20px;height: 20px"></div>
                        <div class=" pt-2 text-end" style="width: 95px">CAD</div>
                        <div class=" pt-3 text-center" style="width: 33px">qty</div>
                    </div>
                </div>
                <div class="col-2 pt-2 border-b text-center"> <strong> W{{$current_wo->number}}</strong></div>
                <div class="col-md-5"></div>
            </div>
            <div class="d-flex" style="width: 960px">
                <div class="text-end">
                    <h6 class="pt-1 " style="width: 60px;"><strong>Cat #1</strong></h6>
                </div>
                <div class=" " >
                    <img src="{{ asset('img/icons/icons8-right-arrow.gif')}}" alt="arrow"
                         style="width: 30px;height: 20px">
                </div>
                <div class="border-l-t-b text-center fs-9 pt-0 5" style="width: 30px;height: 25px">
                    N/A</div>
                <div class="border-l-t-b ps-2  " style="width: 130px;height: 25px; color: lightgray; font-style: italic" >RO
                    No.</div>
                <div class="border-all text-center fs-9 pt-0 5" style="width: 30px;height: 25px">
                    N/A</div>
                <div class=" text-center " style="width: 20px;height: 20px"></div>
                <div class="border-l-t-b ps-2  " style="width: 100px;height: 25px; color: lightgray; font-style: italic" >RO
                    No.</div>
                <div class="border-all text-center pt-0 5 fs-9" style="width: 30px;height: 25px">
                    N/A
                </div>
                <div class=" text-center " style="width: 305px;height: 18px"></div>
                <div class=" text-end pt-2 5" style="width: 75px;height: 18px">Technician</div>
                <div class="border-b " style="width: 120px"></div>
                <div class="border-l-t-r" style="width: 40px;height: 30px"></div>

            </div>
            <div class="d-flex">
                <div class="text-end  pe-3" style="width: 891px">Name</div>
                <div class=" " style="width: 29px"></div>
                <div class="border-l-b-r" style="width: 40px;height: 10px"></div>
            </div>

        </div>
        <div class="d-flex mb-1">
            <div class="" style="width: 80px"></div>
            <img src="{{ asset('img/icons/arrow_ld.png')}}" alt="arrow"
                 style="height: 10px;width: 60px" class="mt-2">
            <div class="border-b " style="width: 300px; height: 18px"><strong>Cat #2 (not included in NDT & Cad Cat #1)</strong>
            </div>
        </div>

        <div class="row g-0 ">
            <!-- Заголовок "Description" -->
            <div class="col-2 border-l-t ps-1">
                <div style="height: 28px"><strong>Description</strong> </div>
            </div>
            <!-- Основная часть таблицы -->
            <div class="col-10">
                <!-- Строка для имен компонентов -->
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px">
                                <span class="">Bushings </span>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px">
                                <span class="">
{{--                                    Component {{ $i + 1 }}--}}
                                </span>
                            </div>
                        @endfor
                    @else
                        <!-- Если нет данных о группах, показываем базовую структуру -->
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-t-r' }} text-center" style="height: 30px">
                                <span class="">
{{--                                    Component {{ $i + 1 }}--}}
                                </span>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка для Part No. -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="min-height: 28px"><strong> Part No.</strong></div>
            </div>
            <!-- Данные Part No. -->
            <div class="col-10">
                <div class="row g-0 ">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ ($groupIndex == count($processGroups) - 1 && count($processGroups) < 6) ?
                            'border-l-t-r' : 'border-l-t' }} text-center" style="min-height: 30px; padding: 2px;">
                                @php
                                    // Собираем уникальные part_number с количеством из всех компонентов группы
                                    $partNumbersWithQty = [];
                                    if (isset($group['components']) && is_array($group['components'])) {
                                        foreach ($group['components'] as $compItem) {
                                            if (isset($compItem['component']) && $compItem['component']->part_number) {
                                                $partNum = trim($compItem['component']->part_number);
                                                if (!empty($partNum)) {
                                                    $qty = isset($compItem['qty']) ? (int)$compItem['qty'] : 1;
                                                    if (!isset($partNumbersWithQty[$partNum])) {
                                                        $partNumbersWithQty[$partNum] = 0;
                                                    }
                                                    $partNumbersWithQty[$partNum] += $qty;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                @if(count($partNumbersWithQty) > 0)
                                    <div class="part-no-data">
                                        @foreach($partNumbersWithQty as $partNum => $qty)
                                            <div>{{ $partNum }}{{__(' : ')}}{{ $qty }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r'}} text-center" style="min-height: 30px;
                            padding: 2px;">
                                <span class="">
{{--                                    Part {{ $i + 1 }}--}}
                                </span>
                            </div>
                        @endfor
                    @else
                        <!-- Если нет данных о группах, показываем базовую структуру -->
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-t-r'}} text-center" style="min-height: 30px;
                            padding: 2px;">
                                <span class="">
{{--                                    Part {{ $i + 1 }}--}}
                                </span>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка для Serial No. -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 28px"><strong> Serial No</strong>.</div>
            </div>
            <!-- Данные Serial No. -->
            <div class="col-10">
                <div class="row g-0 ">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t'}} text-center" style="height: 30px">
                                <span class="">QTY: {{ $group['total_qty'] }}</span>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r'}} text-center" style="height: 30px">
                                <span class="">
{{--                                    Serial {{ $i + 1 }}--}}
                                </span>
                            </div>
                        @endfor
                    @else
                        <!-- Если нет данных о группах, показываем базовую структуру -->
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-t-r'}} text-center" style="height: 30px">
                                <span class="">
{{--                                    Serial {{ $i + 1 }}--}}
                                </span>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 border-tt-gr">
            <div class="col-2 " >
                <div class=" text-end mb-1" style="height: 15px"><strong>Steps sequence</strong>
                    <img src="{{ asset('img/icons/arrow_rd.png')}}" alt="arrow"
                         style="height: 15px; margin-right: -15px" class="mt-2 ">
                </div>
            </div>
            <div class="col-10" >
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col  text-center " style="height: 24px">
                                <strong>RO No.</strong>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col  text-center " style="height: 24px">
                                <strong>RO No.</strong>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col  text-center " style="height: 15px">
                                <strong>RO No.</strong>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- 15 строк второй таблицы -->
        <!-- Строка 1: N.D.T. -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong></strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('NDT', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['NDT'] }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('NDT', $group['processes']))
                                            <span class="">
{{--                                                {{ $group['process_numbers']['NDT'] }}--}}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка 2: N.D.T. -->
        <div class="row g-0 ">
            <div class="col-2 border-l ps-1">
                <div style="height: 30px"><strong> N.D.T.</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка 3: N.D.T. -->
        <div class="row g-0 ">
            <div class="col-2 border-l ps-1">
                <div style="height:30px"><strong></strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                           30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height:30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height:30px;">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height:30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height:30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height:30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height:30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка 4: Machining -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Machining</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Machining', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Machining'] }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Machining', $group['processes']))
                                            <span class="">
{{--                                                {{ $group['process_numbers']['Machining'] }}--}}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка 4.5: Stress Relief -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Stress Relief</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Bake (Stress relief)', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Bake (Stress relief)'] ?? '' }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Bake (Stress relief)', $group['processes']))
                                            <span class="">
 {{--                                                {{ $group['process_numbers']['Bake (Stress relief)'] ?? '' }}--}}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка 5: Passivation -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Passivation</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Passivation', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Passivation'] }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Passivation', $group['processes']))
                                            <span class="">
{{--                                                {{ $group['process_numbers']['Passivation'] }}--}}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка 6: CAD -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>CAD</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('CAD', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['CAD'] }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('CAD', $group['processes']))
                                            <span class="">
{{--                                                {{ $group['process_numbers']['CAD'] }}--}}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка 7: Anodizing -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Anodizing</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Anodizing', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Anodizing'] ?? '' }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Anodizing', $group['processes']))
                                            <span class="">
{{--                                                {{ $group['process_numbers']['Anodizing'] ?? '' }}--}}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строка 8: Xylan -->
        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Xylan</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Xylan', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Xylan'] ?? '' }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Xylan', $group['processes']))
                                            <span class="">
{{--                                                {{ $group['process_numbers']['Xylan'] ?? '' }}--}}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <!-- Строки 9-15: Пустые строки -->
        @for($row = 1; $row <= 1; $row++)
            <div class="row g-0 ">
                <div class="col-2 border-l-t ps-1">
                    <div style="height: 30px"></div>
                </div>
                <div class="col-10">
                    <div class="row g-0">
                        @if(isset($processGroups) && count($processGroups) > 0)
                            @foreach($processGroups as $groupIndex => $group)
                                <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                    <div class="row g-0">
                                        <div class="col-2 text-center  border-r" style="height: 30px;">
                                        </div>
                                        <div class="col-6 text-center" style="height: 30px;">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <!-- Дополняем до 6 столбцов пустыми -->
                            @for($i = count($processGroups); $i < 6; $i++)
                                <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                    <div class="row g-0">
                                        <div class="col-2 text-center border-r" style="height: 30px;">
                                        </div>
                                        <div class="col-6 text-center" style="height: 30px;">
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        @else
                            @for($i = 0; $i < 6; $i++)
                                <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                    <div class="row g-0">
                                        <div class="col-2 text-center" style="height: 30px;">
                                        </div>
                                        <div class="col-6 text-center" style="height: 30px;">
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        @endif
                    </div>
                </div>
            </div>
        @endfor
        <div class="row g-0 ">
            <div class="col-2 border-l-t-b ps-1">
                <div style="height: 22px"></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-all' : 'border-l-t-b' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="parent mt-1">
            <div class="div2 text-end pe-4 mt-3" style="height: 24px">Quality Assurance Acceptance</div>
            <div class="div3 border-all text-center  3" style="width: 60px; align-content: center; height: 60px; color: grey">Q
                .A.
                STAMP</div>
            <div class="div4 border-t-r-b mt-3 ps-1" style="height: 24px; color: grey">Data</div>
            {{--        <div class="div5">5</div>--}}
        </div>

    </div>



    <footer >
        <div class="row" style="width: 100%; padding: 5px 5px;">
            <div class="col-6 text-start">
                {{__("Form #012")}}
            </div>

            <div class="col-6 text-end pe-4 ">
                {{__('Rev#0, 15/Dec/2012   ')}}
            </div>
        </div>

    </footer>

    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif

@endforeach

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
                            title="Настройки таблицы Special Process Form."
                            data-tooltip-ru="Настройки таблицы Special Process Form."
                            data-tooltip-en="Special Process Form table settings.">
                            📊 Tables
                        </h5>

                        <!-- Table Setting (collapse) -->
                        <div class="accordion mb-3" id="tableSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="tableSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#tableSettingsCollapse" aria-expanded="false"
                                            aria-controls="tableSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="Дополнительные настройки таблицы: ширина, отступы, масштаб и размер шрифта."
                                              data-tooltip-ru="Дополнительные настройки таблицы: ширина, отступы, масштаб и размер шрифта."
                                              data-tooltip-en="Additional table settings: width, padding, scale and font size.">
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
                                                       title="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 980px."
                                                       data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 980px."
                                                       data-tooltip-en="Maximum width of the table container in pixels. Recommended value: 980px.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                           min="500" max="2000" step="10" value="980">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerScale" class="form-label" data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="Масштаб контейнера (transform: scale). 0.97 - стандартное значение. Уменьшите для более компактного отображения."
                                                       data-tooltip-ru="Масштаб контейнера (transform: scale). 0.97 - стандартное значение. Уменьшите для более компактного отображения."
                                                       data-tooltip-en="Container scale (transform: scale). 0.97 - standard value. Decrease for more compact display.">
                                                    Scale
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerScale" name="containerScale"
                                                           min="0.5" max="1.5" step="0.01" value="0.97">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="tableFontSize" class="form-label" data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="Размер шрифта текста в таблице. Рекомендуемое значение: 0.9rem (14.4px). Увеличьте для лучшей читаемости."
                                                       data-tooltip-ru="Размер шрифта текста в таблице. Рекомендуемое значение: 0.9rem (14.4px). Увеличьте для лучшей читаемости."
                                                       data-tooltip-en="Font size for table text. Recommended value: 0.9rem (14.4px). Increase for better readability.">
                                                    Font Size (rem)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="tableFontSize" name="tableFontSize"
                                                           min="0.5" max="2" step="0.05" value="0.9">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="partNoFontSize" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Размер шрифта для строки Part No. (где размещены данные part_number и количество). Диапазон: от 0.1rem до 1rem. Рекомендуемое значение: 0.1rem."
                                                        data-tooltip-ru="Размер шрифта для строки Part No. (где размещены данные part_number и количество). Диапазон: от 0.1rem до 1rem. Рекомендуемое значение: 0.1rem."
                                                        data-tooltip-en="Font size for Part No. row (where part_number and quantity data are displayed). Range: from 0.1rem to 1rem. Recommended value: 0.1rem.">
                                                    Part No. Font Size (rem)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="partNoFontSize" name="partNoFontSize"
                                                           min="0.1" max="1" step="0.1" value="0.1">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerPadding" class="form-label" data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="Внутренние отступы контейнера. По умолчанию: 3px."
                                                       data-tooltip-ru="Внутренние отступы контейнера. По умолчанию: 3px."
                                                       data-tooltip-en="Container inner padding. Default: 3px.">
                                                    Padding (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                           min="0" max="50" step="1" value="3">
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
                                              title="Настройки страницы: размер, поля и отступы."
                                              data-tooltip-ru="Настройки страницы: размер, поля и отступы."
                                              data-tooltip-en="Page settings: size, margins and padding.">
                                            Page Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="pageSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="pageSettingsHeading" data-bs-parent="#pageSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="pageMargin" class="form-label" data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="Отступ от краев страницы при печати. Рекомендуемое значение: 2mm."
                                                       data-tooltip-ru="Отступ от краев страницы при печати. Рекомендуемое значение: 2mm."
                                                       data-tooltip-en="Margin from page edges when printing. Recommended value: 2mm.">
                                                    Margin (mm)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="pageMargin" name="pageMargin"
                                                           min="0" max="50" step="0.5" value="2">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="bodyMarginLeft" class="form-label" data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="Горизонтальный отступ основного контента от левого края. По умолчанию: 3px."
                                                       data-tooltip-ru="Горизонтальный отступ основного контента от левого края. По умолчанию: 3px."
                                                       data-tooltip-en="Horizontal margin of main content from left edge. Default: 3px.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft"
                                                           min="0" max="50" step="1" value="3">
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
                                                       title="Ширина колонтитула в пикселях. 1060px - стандартное значение."
                                                       data-tooltip-ru="Ширина колонтитула в пикселях. 1060px - стандартное значение."
                                                       data-tooltip-en="Footer width in pixels. 1060px - standard value.">
                                                    Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="400" max="1200" step="10" value="1060">
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
                                                       title="Внутренние отступы колонтитула. Например: '2px 2px'."
                                                       data-tooltip-ru="Внутренние отступы колонтитула. Например: '2px 2px'."
                                                       data-tooltip-en="Footer inner padding. Example: '2px 2px'.">
                                                    Padding
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                           placeholder="2px 2px" value="2px 2px">
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
    const PRINT_SETTINGS_KEY = 'woBushingsSpecProcessForm_print_settings';
    const TOOLTIP_LANG_KEY = 'woBushingsSpecProcessForm_tooltip_lang';

    // Настройки по умолчанию
    const defaultSettings = {
        pageMargin: '2mm',
        bodyMarginLeft: '3px',
        containerMaxWidth: '980px',
        containerScale: '0.97',
        containerPadding: '3px',
        containerMarginLeft: '10px',
        containerMarginRight: '10px',
        tableFontSize: '0.9rem',
        partNoFontSize: '0.1rem',
        footerWidth: '1060px',
        footerFontSize: '10px',
        footerPadding: '2px 2px'
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
                pageMargin: getValue('pageMargin', '2', 'mm'),
                bodyMarginLeft: getValue('bodyMarginLeft', '3', 'px'),
                containerMaxWidth: getValue('containerMaxWidth', '980', 'px'),
                containerScale: getValue('containerScale', '0.97', ''),
                containerPadding: getValue('containerPadding', '3', 'px'),
                containerMarginLeft: getValue('containerMarginLeft', '10', 'px'),
                containerMarginRight: getValue('containerMarginRight', '10', 'px'),
                tableFontSize: getValue('tableFontSize', '0.9', 'rem'),
                partNoFontSize: getValue('partNoFontSize', '0.1', 'rem'),
                footerWidth: getValue('footerWidth', '1060', 'px'),
                footerFontSize: getValue('footerFontSize', '10', 'px'),
                footerPadding: getValue('footerPadding', '2px 2px', '')
            };

            localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);

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
        root.style.setProperty('--print-body-margin-left', settings.bodyMarginLeft || defaultSettings.bodyMarginLeft);
        root.style.setProperty('--container-max-width', settings.containerMaxWidth || defaultSettings.containerMaxWidth);
        root.style.setProperty('--container-scale', settings.containerScale || defaultSettings.containerScale);
        root.style.setProperty('--container-padding', settings.containerPadding || defaultSettings.containerPadding);
        root.style.setProperty('--container-margin-left', settings.containerMarginLeft || defaultSettings.containerMarginLeft);
        root.style.setProperty('--container-margin-right', settings.containerMarginRight || defaultSettings.containerMarginRight);
        root.style.setProperty('--table-font-size', settings.tableFontSize || defaultSettings.tableFontSize);
        root.style.setProperty('--part-no-font-size', settings.partNoFontSize || defaultSettings.partNoFontSize);
        root.style.setProperty('--print-footer-width', settings.footerWidth || defaultSettings.footerWidth);
        root.style.setProperty('--print-footer-font-size', settings.footerFontSize || defaultSettings.footerFontSize);
        root.style.setProperty('--print-footer-padding', settings.footerPadding || defaultSettings.footerPadding);
    }

    // Загрузка настроек в форму
    function loadSettingsToForm(settings) {
        const elements = {
            'pageMargin': { suffix: '', default: '2' },
            'bodyMarginLeft': { suffix: '', default: '3' },
            'containerMaxWidth': { suffix: '', default: '980' },
            'containerScale': { suffix: '', default: '0.97' },
            'containerPadding': { suffix: '', default: '3' },
            'containerMarginLeft': { suffix: '', default: '10' },
            'containerMarginRight': { suffix: '', default: '10' },
            'tableFontSize': { suffix: '', default: '0.9' },
            'partNoFontSize': { suffix: '', default: '0.1' },
            'footerWidth': { suffix: '', default: '1060' },
            'footerFontSize': { suffix: '', default: '10' },
            'footerPadding': { suffix: '', default: '2px 2px' }
        };

        Object.keys(elements).forEach(function(id) {
            const element = document.getElementById(id);
            if (element) {
                const value = settings[id] || elements[id].default;
                if (id === 'footerPadding') {
                    element.value = value;
                } else if (id === 'containerScale' || id === 'tableFontSize' || id === 'partNoFontSize') {
                    element.value = parseFloat(value) || parseFloat(elements[id].default);
                } else {
                    element.value = parseInt(value) || elements[id].default;
                }
            }
        });
    }

    // Сброс настроек к значениям по умолчанию
    window.resetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
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
</script>

</div>
</body>
</html>
