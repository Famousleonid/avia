<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Process Form - Bushings</title>
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
            transform: scale(0.97);
            transform-origin: top ;
            padding: 3px;
            margin-left: 10px;
            margin-right: 10px;
        }

        @media print {
            @page {
                size: 11in 8.5in;
                margin: 2mm;
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
                padding: 2px 2px;
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
<!-- Кнопка для печати -->
<div class="text-start m-3">
    <button class="btn btn-primary no-print" onclick="window.print()">
        Print Form
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
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px">
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
                <div style="height: 28px"><strong> Part No.</strong></div>
            </div>
            <!-- Данные Part No. -->
            <div class="col-10">
                <div class="row g-0 ">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t'}} text-center" style="height:
                            30px">
                                <span class="">
{{--                                    Part Number--}}
                                </span>
                            </div>
                        @endforeach
                        <!-- Дополняем до 6 столбцов пустыми -->
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r'}} text-center" style="height: 30px">
                                <span class="">
{{--                                    Part {{ $i + 1 }}--}}
                                </span>
                            </div>
                        @endfor
                    @else
                        <!-- Если нет данных о группах, показываем базовую структуру -->
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r'}} text-center" style="height: 30px">
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
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r'}} text-center" style="height: 30px">
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
                <div style="height: 30px"><strong>N.D.T.</strong></div>
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
                <div style="height: 30px"><strong> </strong></div>
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
        @for($row = 9; $row <= 13; $row++)
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

<!-- Скрипт для печати -->
<script>
    function printForm() {
        window.print();
    }
</script>
</div>
</body>
</html>
