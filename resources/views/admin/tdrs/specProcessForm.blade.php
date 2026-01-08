<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Process Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 1080px;
            height: auto;
            transform: scale(0.94);
            transform-origin: top ;
            padding: 1px;
            margin-left: 10px;
            margin-right: 10px;
        }


        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                /*size: letter landscape;*/
                size: 11in 8.5in;
                margin: 1mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: auto;
                width: auto;
                margin-left: 3px;
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
                bottom: 10px;
                width: 1060px;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 5px 5px;
            }

            /* Обрезка контента и размещение на одной странице */
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
            border-left: 8px  solid lightgrey;
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
        .fs-9 {
            font-size: 0.9rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.75rem; /* или любое другое подходящее значение */
        }
        .fs-7 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-6 {
            font-size: 0.6rem; /* или любое другое подходящее значение */
        }
        .fs-4 {
            font-size: 0.4rem; /* или любое другое подходящее значение */
        }

        .details-row {
            display: flex;
            align-items: center; /* Выравнивание элементов по вертикали */
            height: 36px; /* Фиксированная высота строки */
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
        .page-break {
            page-break-after: always;
        }


        .parent {
            display: grid;
            grid-template-columns: 525px 45px 170px ;
            /*grid-template-columns: repeat(12, 1fr);*/
            /*grid-template-rows: repeat(3, 1fr);*/
            gap: 0px;
        }
    </style>
</head>

<body>
<!-- Кнопка для печати -->
<div class="text-start m-2">
    <button class="btn btn-primary no-print" onclick="window.print()">
        Print Form
    </button>

</div>

@php
    $componentsPerPage = 6;
    // Если $tdr_ws — коллекция, можно воспользоваться chunk:
    $componentChunks = $tdr_ws->chunk($componentsPerPage);
@endphp

@foreach($componentChunks as $chunk)
    <div class="container-fluid ">
        <div class="row">
            <div class="col-1">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 120px; margin: 0px 4px 0;">
            </div>
            <div class="col-11">
                <h5 class="  text-black text-center"><strong>Special Process Form</strong></h5>
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
                        <div class=" text-center fs-8" style="width: 20px;height: 20px"></div>
                        <div class="fs-8 pt-2 text-end" style="width: 95px">CAD</div>
                        <div class="fs-8 pt-3 text-center" style="width: 30px">qty</div>
                    </div>
                </div>
                <div class="col-2 pt-2 border-b text-center"> <strong> W{{$current_wo->number}}</strong></div>
                <div class="col-md-5"></div>
            </div>
            <div class="d-flex" style="width: 960px">
                <div class="text-end">
                    <h6 class="pt-1 fs-8" style="width: 60px;"><strong>Cat #1</strong></h6>
                </div>
                <div class=" fs-8" >
                    <img src="{{ asset('img/icons/icons8-right-arrow.gif')}}" alt="arrow"
                         style="width: 24px;height: 20px">
                </div>
                <div class="border-l-t-b text-center pt-0 fs-75" style="width: 25px;height: 20px">
                    @if($current_wo->instruction_id ==1)
                        {{ !isset($ndtSums['mpi']) || $ndtSums['mpi'] === null ? ' ' : $ndtSums['mpi'] }}
                    @else
                        {{__(' ')}}
                    @endif
                </div>
                <div class="border-l-t-b ps-2 fs-8 " style="width: 130px;height: 20px; color: lightgray; font-style: italic" >RO
                    No.</div>
                <div class="border-all text-center pt-0 fs-75" style="width: 25px;height: 20px">
                    @if($current_wo->instruction_id ==1)
                         {{ !isset($ndtSums['fpi']) || $ndtSums['fpi'] === null || $ndtSums['fpi'] === 0 ? ' ' : $ndtSums['fpi'] }}
                    @else
                        {{__(' ')}}
                    @endif
                </div>
                <div class=" text-center fs-8" style="width: 20px;height: 20px"></div>
                <div class="border-l-t-b ps-2 fs-8 " style="width: 100px;height: 20px; color: lightgray; font-style:
                italic" >RO No.</div>
                <div class="border-all text-center pt-0 fs-75" style="width: 25px;height: 20px">

{{--                    {{ empty($cadSum['total_qty']) ? 'N/A' : $cadSum['total_qty']  }}--}}
                    @php
                        $a = $cadSum['total_qty'] ?? null;
                        $b = $cadSum_ex ?? null;
                        $hasA = isset($a) && $a !== '' && $a !== 0;
                        $hasB = isset($b) && $b !== '' && $b !== 0;
                        $result = ($hasA && $hasB) ? ((int)$a + (int)$b) : (($hasA ? (int)$a : ($hasB ? (int)$b : null)));
                    @endphp
                    @if($current_wo->instruction_id==1)
                        {{ ($result !== null && $result > 0) ? $result : ' rr' }}
                    @else
                        {{ ($cadSum_ex > 0) ? $cadSum_ex : ' '}}
                    @endif


                </div>
                <div class=" text-center fs-7" style="width: 305px;height: 10px"></div>
                <div class=" text-end pt-2 fs-8" style="width: 75px;height: 6px">Technician</div>
                <div class="border-b " style="width: 120px"></div>
                <div class="border-l-t-r" style="width: 40px;height: 28px"></div>

            </div>
            <div class="d-flex">
                <div class="text-end fs-7 pe-4" style="width: 880px; height: 15px">Name</div>
                <div class=" " style="width: 29px"></div>
                <div class="border-l-b-r" style="width: 40px;height: 6px"></div>
            </div>

        </div>
        <div class="d-flex mb-0">
            <div class="" style="width: 80px"></div>
            <img src="{{ asset('img/icons/arrow_ld.png')}}" alt="arrow"
                 style="height: 5px;width: 60px" class="mt-2">
            <div class="border-b fs-7" style="width: 300px; height: 15px"><strong>Cat #2 (not included in NDT & Cad Cat #1)
                </strong>
            </div>
        </div>


        <div class="row g-0 fs-7">
            <!-- Заголовок "Description" -->
            <div class="col-2 border-l-t-b ps-1">
                <div style="height: 20px"><strong>Description</strong> </div>
            </div>
            <!-- Основная часть таблицы -->
            <div class="col-10">
                <!-- Строка для имен компонентов -->
                <div class="row g-0">
                    @foreach($chunk as $index => $component)
                        @php
                            $localIndex = $index % 6;
                        @endphp
                        <div class="col {{ $localIndex < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height:
                        22px">
                            @php
                                $nameLength = mb_strlen($component->component->name);
                                $fontSize = $nameLength > 20 ? round(20 / $nameLength, 2) . 'em' : '1em';
                            @endphp
                            <span style="font-size: {{ $fontSize }};font-weight: bold">
                                {{ $component->component->name }}
                            </span>
                        </div>
                    @endforeach

                    @for($i = count($chunk); $i < $componentsPerPage; $i++)
                        <div class="col {{ $i < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 22px">
                            {{__(' ')}}</div>
                    @endfor
                </div>

            </div>
        </div>

        <!-- Строка для Part No. -->
        <div class="row g-0 fs-7">

            <div class="col-2 border-l-b ps-1">
                <div style="height: 20px"><strong> Part No.</strong></div>
            </div>
            <!-- Данные Part No. -->
            <div class="col-10">
                <div class="row g-0 ">
                    @foreach($chunk as $index => $component)
                        @php
                            $localIndex = $index % 6;
                        @endphp
                        <div class="col {{ $localIndex < 5 ? 'border-l-b' : 'border-l-b-r'}} text-center" style="height: 22px;font-weight: bold">
                            {{ $component->component->part_number }}
                        </div>
                    @endforeach
                    @for($i = count($chunk); $i < $componentsPerPage; $i++)
                        <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r'}} text-center" style="height: 22px">
                            {{__(' ')}}</div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Строка для Serial No. -->
        <div class="row g-0 fs-7">

            <div class="col-2 border-l-b ps-1">
                <div style="height: 20px"><strong> Serial No</strong>.</div>
            </div>
            <!-- Данные Serial No. -->
            <div class="col-10">
                <div class="row g-0 ">
                    @foreach($chunk as $index => $component)
                        @php $localIndex = $index % 6; @endphp
                        <div class="col {{ $localIndex < 5 ? 'border-l-b' : 'border-l-b-r'}} text-center" style="height: 22px;font-weight: bold">
                            {{ $component->serial_number }}
                        </div>
                    @endforeach
                    @for($i = count($chunk); $i < $componentsPerPage; $i++)
                        <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r'}} text-center" style="height: 22px">
                            {{__(' ')}}</div>
                    @endfor
                </div>
            </div>
        </div>
        <div class="row g-0 border-tt-gr">
            <div class="col-2 " >
                <div class="fs-8 text-end mb-1" style="height: 15px"><strong>Steps sequence</strong>
                    <img src="{{ asset('img/icons/arrow_rd.png')}}" alt="arrow"
                         style="height: 10px; margin-right: -15px" class="mt-2 ">
                </div>
            </div>
            <div class="col-10" >
                <div class="row g-0">
                    @for($i = 0; $i < 6; $i++)
                        <div class="col fs-8 text-center " style="height: 15px">
                            <strong>RO No.</strong></div>
                    @endfor
                </div>
            </div>
        </div>
        <div class="row g-0 fs-7">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 18px"><strong></strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @php
                        $componentIndex = 0;
                    @endphp

                    @foreach($chunk as $index => $component)
                        @php
                            $localIndex = $index % 6;
                            $currentTdrId = $component->id;
                            $ndtForCurrentTdr = collect($ndt_processes)
                                ->where('tdrs_id', $currentTdrId)
                                ->values();
                        @endphp

                        <div class="col {{ $localIndex < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height:
                     20px">
                            @if($componentIndex < count($chunk) && isset($ndtForCurrentTdr[0]))
                                <div class="border-r" style="height: 20px; width: 30px">
                                    {{ $ndtForCurrentTdr[0]['number_line'] }}
                                </div>
                            @else
                                <div class="border-r" style="height: 20px; width: 30px"></div>
                            @endif
                        </div>

                        @php $componentIndex++; @endphp
                    @endforeach

                    @for($i = count($chunk); $i < $componentsPerPage; $i++)
                        <div class="col {{ $i < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 20px;
                    position: relative;">
                            {{ __(' ') }}
                            <!-- Граница внутри ячейки, отступ 30px от левого края -->
                            <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>

                        </div>
                    @endfor


                </div>
            </div>

        </div>
        <div class="row g-0 fs-7">
            <div class="col-2 border-l ps-1">
                <div style="height: 18px"><strong>N.D.T.</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @php
                        $componentIndex = 0;
                    @endphp

                    @foreach($chunk as $index => $component)
                        @php
                            $localIndex = $index % 6;
                            $currentTdrId = $component->id;
                            $ndtForCurrentTdr = collect($ndt_processes)
                                ->where('tdrs_id', $currentTdrId)
                                ->values();
                        @endphp

                        <div class="col {{ $localIndex < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height:
                     20px">
                            @if($componentIndex < count($chunk) && isset($ndtForCurrentTdr[1]))
                                <div class="border-r" style="height: 20px; width: 30px">
                                    {{ $ndtForCurrentTdr[1]['number_line'] }}
                                </div>
                            @else
                                <div class="border-r" style="height: 20px; width: 30px"></div>
                            @endif
                        </div>

                        @php $componentIndex++; @endphp
                    @endforeach

                    @for($i = count($chunk); $i < $componentsPerPage; $i++)
                        <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 20px;
                    position: relative;">
                            {{ __(' ') }}
                            <!-- Граница внутри ячейки, отступ 30px от левого края -->
                            <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>

                        </div>
                    @endfor


                </div>
            </div>
        </div>
        <div class="row g-0 fs-7">
            <div class="col-2 border-l-b ps-1">
                <div style="height: 18px"><strong></strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @php
                        $componentIndex = 0;
                    @endphp

                    @foreach($chunk as $index => $component)
                        @php
                            $localIndex = $index % 6;
                            $currentTdrId = $component->id;
                            $ndtForCurrentTdr = collect($ndt_processes)
                                ->where('tdrs_id', $currentTdrId)
                                ->values();
                        @endphp

                        <div class="col {{ $localIndex < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height:
                     20px">
                            @if($componentIndex < count($chunk) && isset($ndtForCurrentTdr[2]))
                                <div class="border-r" style="height: 20px; width: 30px">
                                    {{ $ndtForCurrentTdr[2]['number_line'] }}
                                </div>
                            @else
                                <div class="border-r" style="height: 20px; width: 30px"></div>
                            @endif
                        </div>

                        @php $componentIndex++; @endphp
                    @endforeach

                    @for($i = count($chunk); $i < $componentsPerPage; $i++)
                        <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 20px;
                    position: relative;">
                            {{ __(' ') }}
                            <!-- Граница внутри ячейки, отступ 30px от левого края -->
                            <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>

                        </div>
                    @endfor


                </div>

            </div>
        </div>
        @foreach($processNames as $name)
            <div class="row g-0 fs-7">
                <div class="col-2 border-l-b ps-1">
                    <div style="height: 20px"><strong>{{ $name->name }}</strong></div>
                </div>
                <div class="col-10">
                    <div class="row g-0">
                        @php
                            $componentIndex = 0;
                        @endphp

                        @foreach($chunk as $index => $component)
                            @php
                                $localIndex = $index % 6;
                                $currentTdrId = isset($component) && isset($component->id) ? $component->id : null;

                                // Проверяем наличие необходимых переменных
                                if (isset($processes) && isset($name) && isset($name->id) && $currentTdrId) {
                                    // Фильтруем только процессы данного компонента с данным именем процесса
                                    $processForCurrentTdr = $processes
                                        ->where('process_name_id', $name->id)
                                        ->where('tdrs_id', $currentTdrId)
                                        ->values();


                                    // Собираем все number_line через запятую с проверкой
                                    $numberLines = '';
                                    if ($processForCurrentTdr->isNotEmpty()) {
                                        $numberLines = $processForCurrentTdr->pluck('number_line')->filter()->implode(',');
                                    }
                                } else {
                                    $processForCurrentTdr = collect();
                                    $numberLines = '';

                                }
                            @endphp
                            <div class="col {{ $localIndex < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 22px; position: relative;">
                                {{--                      Выводим все number_line через запятую --}}
                                @if($numberLines)
                                    <div class="border-r" style="height: 22px; width: 30px">
                                        {{ $numberLines }}
                                    </div>
                                @else
                                    <div class="border-r" style="height: 22px; width: 30px"></div>
                                @endif
{{--                                место для ЕС для компанента--}}
                                {{-- Проверяем, есть ли процессы с ec = 1 для данного компонента и имени процесса --}}
                                @php
                                    $hasEcProcess = false;
                                    if (isset($processForCurrentTdr) && $processForCurrentTdr->isNotEmpty()) {
                                        $hasEcProcess = $processForCurrentTdr->where('ec', 1)->isNotEmpty();

                                    }
                                @endphp
                                @if($hasEcProcess)
                                    <div class="" style="height: 22px; width: 30px;
                                    position: absolute; right: 45px; top: 0;"
                                    >
                                        EC
                                    </div>
                                @endif
                                @php $componentIndex++; @endphp
                            </div>
                        @endforeach
                        @for($i = count($chunk); $i < $componentsPerPage; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 22px;
                    position: relative;"> {{ __(' ') }}
                                <!-- Граница внутри ячейки, отступ 30px от левого края -->
                                <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px
                                solid black;">

                                </div>
                            </div>
                        @endfor

                    </div>
                </div>
            </div>
        @endforeach


        <div class="parent mt-1">
            <div class="div2 text-end pe-4 mt-2" style="height: 24px">Quality Assurance Acceptance</div>
            <div class="div3 border-all text-center  fs-75" style="width: 45px; align-content: center; height: 42px;
            color: grey">Q.A. STAMP</div>
            <div class="div4 border-t-r-b fs-75 mt-2 ps-2 pt-1" style="height: 24px; color: grey">Data</div>
            {{--        <div class="div5">5</div>--}}
        </div>
    </div>








    <footer >
        <div class="row" style="width: 100%; padding: 10px 10px;">
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
