<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 960px;

            height: 99%;
            padding: 5px;
            margin-left: 10px;
            margin-right: 10px;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: landscape;
                margin: 2mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: 86%;
                width: 98%;
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
                bottom: 0;
                width: 92%;
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
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.75rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-9 {
            font-size: 0.9rem; /* или любое другое подходящее значение */
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





        .parent {
            display: grid;
            grid-template-columns:
            60px 100px   /* WO No. */
            280px 350px   /* Description - больше места */
            60px 150px    /* P/N */
        }
        .table {
            display: grid;
            text-align: center;

            grid-template-columns:
        40px 160px
        340px 100px
        120px 100px
        60px 100px;
            /*min-height: 42px; !* Опционально: для единой высоты строк *!*/
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
        <div class="col-4">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 180px; margin: 6px 10px 0;">
        </div>
        <div class="col-8 ">
            <h4 class="pt-4   ms-5 text-black text-"><strong>PART TRAVELER</strong></h4>
        </div>
        <div class="text-center" style="margin-top: -10px">
            <h6>(This document must accompany the part and must be returned to the originator)</h6>
        </div>

        <div class="parent">
            <div class="div1" style="text-align: end"> <strong>WO No.</strong></div>
            <div class="div2 border-b" style="text-align: center">W{{$current_wo->number}}</div>
            <div class="div3" style="text-align: end"> <strong>Description</strong></div>
            <div class="div4 border-b ps-2"> {{ $current_tdr->component->name }} ( {{ $current_tdr->component->ipl_num }})</div>
            <div class="div5" style="text-align: end"> <strong>P/N</strong></div>
            <div class="div6 border-b ps-2">{{ $current_tdr->component->part_number }}</div>
            <div class="div7" style="text-align: end"> <strong>CMM</strong></div>
            <div class="div8 border-b" style="text-align: center">{{substr($manual->number, 0, 8)}}</div>
            <div class="div9" style="text-align: end"> <strong>Repair No.</strong></div>
            <div class="div10 border-b ps-2">{{ $repairNum ?? 'N/A' }}</div>
            <div class="div11" style="text-align: end"> <strong>S/N</strong></div>
            <div class="div12 border-b ps-2" >{{ $current_tdr->serial_number }}</div>
        </div>

        <div class="table mt-2">
            <div class="div1 border-all " style="min-height: 36px;  align-content: center"></div>
            <div class="div2 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Task require</strong>d</div>
            <div class="div3 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Process</strong></div>
            <div class="div4 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>RO No.</strong></div>
            <div class="div5 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Vendor Name</strong></div>
            <div class="div6 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Date
                    completed</strong></div>
            <div class="div7 border-t-r-b" style="min-height: 36px;  align-content: center"> <strong>Vendor stamp</strong></div>
            <div class="div8 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Note</strong></div>

            @php
                $dateRows = 0;
                $totalRow = 14;

                // Находим первую строку с vendor по sort_order
                $firstVendorSortOrder = null;
                if(isset($vendorsData) && !empty($vendorsData)) {
                    foreach($tdrProcesses as $processes) {
                        if($processes->tdrs_id == $current_tdr->id && isset($vendorsData[$processes->id])) {
                            $processData = json_decode($processes->processes, true);
                            foreach($processData as $process) {
                                if(isset($vendorsData[$processes->id][$process]) && isset($vendorsData[$processes->id][$process]['vendor_name'])) {
                                    if($firstVendorSortOrder === null || $processes->sort_order < $firstVendorSortOrder) {
                                        $firstVendorSortOrder = $processes->sort_order;
                                    }
                                }
                            }
                        }
                    }
                }
            @endphp

            {{-- Формируем строки с отмеченными чекбоксами AT, которые идут ДО первого отмеченного vendor --}}
            @if(isset($atData) && !empty($atData))
                @foreach($tdrProcesses as $processes)
                    @if($processes->tdrs_id == $current_tdr->id && isset($atData[$processes->id]))
                        @php
                            $processData = json_decode($processes->processes, true);
                            $processName = $processes->processName->name;
                            // Показываем только те AT строки, которые идут до первого vendor
                            $showAtBeforeVendor = ($firstVendorSortOrder === null) || ($processes->sort_order < $firstVendorSortOrder);
                        @endphp

                        @if(strpos($processName, 'EC') === false && $showAtBeforeVendor)
                            @foreach($processData as $process)
                                @php
                                    $isAtChecked = in_array($process, $atData[$processes->id]);
                                @endphp

                                @if($isAtChecked)
                                    @php $dateRows++; @endphp
                                    <div class="div1 border-l-b-r " style="min-height: 36px; align-content: center">{{$dateRows}}</div>
                                    <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center">
                                        <strong>
                                            @if($processName == 'NDT-1' ||  $processName == 'NDT-4' )
                                                {{__('NDT')}}
                                            @else
                                                {{ $processName }}
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="div3 border-r-b fs-75" style="min-height: 36px; align-content: center">
                                        @foreach($proces as $proc)
                                            @if($proc->id == $process)
                                                {{$proc->process}}
                                            @endif
                                        @endforeach

                                    </div>
                                    <div class="div4 border-r-b " style="min-height: 36px; align-content: center">
                                        W{{$current_tdr->workorder->number}}
                                    </div>
                                    <div class="div5 border-r-b " style="min-height: 36px; align-content: center">
                                        {{__('AT')}}
                                    </div>
                                    <div class="div6 border-r-b " style="min-height: 36px; align-content: center"> </div>
                                    <div class="div7 border-r-b" style="min-height: 36px; align-content: center"> </div>
                                    <div class="div8 border-r-b fs-8" style="min-height: 36px; align-content: center">
                                        @if($processes->notes)
                                            {{ $processes->notes }}
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    @endif
                @endforeach
            @endif

            {{-- Строка "Outcoming inspection" --}}
            @php $dateRows++; @endphp
            <div class="div1 border-l-b-r " style="min-height: 36px; align-content: center">{{$dateRows}}</div>
            <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong>{{__('Outcoming
            inspection')}}</strong></div>
            <div class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center">{{__('Visual')}}</div>
            <div class="div4 border-r-b " style="min-height: 36px; align-content: center">{{__('N/A')}}</div>
            <div class="div5 border-r-b " style="min-height: 36px; align-content: center">{{__('AT')}}</div>
            <div class="div6 border-r-b " style="min-height: 36px; align-content: center"> </div>
            <div class="div7 border-r-b" style="min-height: 36px; align-content: center"> </div>
            <div class="div8 border-r-b " style="min-height: 36px; align-content: center"> </div>

            {{-- Формируем строки с отмеченными чекбоксами vendor после "Outcoming inspection" --}}
            @if(isset($vendorsData) && !empty($vendorsData))
                @foreach($tdrProcesses as $processes)
                    @if($processes->tdrs_id == $current_tdr->id && isset($vendorsData[$processes->id]))
                        @php
                            $processData = json_decode($processes->processes, true);
                            $processName = $processes->processName->name;
                        @endphp

                        @if(strpos($processName, 'EC') === false)
                            @foreach($processData as $process)
                                @if(isset($vendorsData[$processes->id][$process]) && isset($vendorsData[$processes->id][$process]['vendor_name']))
                                    @php $dateRows++; @endphp
                                    <div class="div1 border-l-b-r " style="min-height: 36px; align-content: center">{{$dateRows}}</div>
                                    <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center">
                                        <strong>
                                            @if($processName == 'NDT-1' ||  $processName == 'NDT-4' )
                                                {{__('NDT')}}
                                            @else
                                                {{ $processName }}
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="div3 border-r-b fs-75" style="min-height: 36px; align-content: center">
                                        @foreach($proces as $proc)
                                            @if($proc->id == $process)
                                                {{$proc->process}}
                                            @endif
                                        @endforeach
                                        <div class="fs-8">
                                            @if($processes->description)
                                                {{ $processes->description }}
                                            @endif
                                        </div>

                                    </div>
                                    <div class="div4 border-r-b " style="min-height: 36px; align-content: center">
                                        @if($processName == 'Paint ')
                                            W{{$current_wo->number}}
                                        @endif
                                    </div>
                                    <div class="div5 border-r-b " style="min-height: 36px; align-content: center">
{{--                                        @if($processName == 'Paint ')--}}
{{--                                            {{__('AT')}}--}}
{{--                                        @elseif($processName == 'Silver plate')--}}
{{--                                            {{__("")}}--}}
{{--                                        @else--}}
{{--                                            {{ $vendorsData[$processes->id][$process]['vendor_name'] }}--}}
{{--                                        @endif--}}

                                            {{ $vendorsData[$processes->id][$process]['vendor_name'] }}

                                    </div>
                                    <div class="div6 border-r-b " style="min-height: 36px; align-content: center"> </div>
                                    <div class="div7 border-r-b" style="min-height: 36px; align-content: center"> </div>
                                    <div class="div8 border-r-b  fs-8" style="min-height: 36px; align-content: center">
                                        @if($processes->notes)
                                            {{ $processes->notes }}
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    @endif
                @endforeach
            @endif

            {{-- Строка "Receiving inspection" после последней строки с vendor --}}
            @php $dateRows++; @endphp
            <div class="div1 border-l-b-r" style="min-height: 36px; align-content: center">{{ $dateRows }}</div>
            <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong>{{ __('Receiving
            inspection') }}</strong></div>
            <div class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center">{{__('Visual')}}</div>
            <div class="div4 border-r-b" style="min-height: 36px; align-content: center">{{__('N/A')}}</div>
            <div class="div5 border-r-b" style="min-height: 36px; align-content: center">{{__('AT')}}</div>
            <div class="div6 border-r-b" style="min-height: 36px; align-content: center"></div>
            <div class="div7 border-r-b" style="min-height: 36px; align-content: center"></div>
            <div class="div8 border-r-b" style="min-height: 36px; align-content: center"></div>

            @foreach($tdrProcesses as $processes)
                @if($processes->tdrs_id == $current_tdr->id)
                    @php
                        // Декодируем JSON-поле processes
                        $processData = json_decode($processes->processes, true);
                        // Получаем имя процесса из связанной модели ProcessName
                        $processName = $processes->processName->name;
                    @endphp

                    @if(strpos($processName, 'EC') === false)
                        @foreach($processData as $process)
                            @php
                                // Проверяем, не была ли эта строка уже обработана
                                $isAtChecked = isset($atData[$processes->id]) && in_array($process, $atData[$processes->id]);
                                $isVendorProcessed = isset($vendorsData[$processes->id]) && isset($vendorsData[$processes->id][$process]) && isset($vendorsData[$processes->id][$process]['vendor_name']);

                                // Исключаем строки с vendor (они обрабатываются после "Outcoming inspection")
                                // Исключаем строки с AT, которые были показаны до первого vendor
                                $wasAtShownBeforeVendor = $isAtChecked && (($firstVendorSortOrder === null) || ($processes->sort_order < $firstVendorSortOrder));
                            @endphp

                            @if(!$isVendorProcessed && !$wasAtShownBeforeVendor)
                                @php $dateRows++; @endphp
                                <div class="div1 border-l-b-r " style="min-height: 36px; align-content: center">{{$dateRows}}</div>
                                <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center">
                                    <strong>
                                        @if($processName == 'NDT-1' ||  $processName == 'NDT-4' )
                                            {{__('NDT')}}
                                        @else
                                            {{ $processName }}
                                        @endif
                                    </strong></div>
                                <div class="div3 border-r-b fs-8" style="min-height: 36px; align-content: center">
                                    @foreach($proces as $proc)
                                        @if($proc->id == $process)
                                        {{$proc->process}}
                                        @endif
                                    @endforeach
                                </div>
                                <div class="div4 border-r-b " style="min-height: 36px; align-content: center">

                                            @if($processName == 'Paint ')
                                        W{{$current_wo->number}}
                                            @endif
                                </div>
                                <div class="div5 border-r-b " style="min-height: 36px; align-content: center">
                                    @if($processName == 'Paint ')
                                       {{__('AT')}}
                                    @elseif($processName == 'Silver plate')
                                        {{__("")}}
                                    @elseif(isset($vendorName) && $vendorName)
                                       {{ $vendorName }}
                                    @endif
                                </div>
                                <div class="div6 border-r-b " style="min-height: 36px; align-content: center"> </div>
                                <div class="div7 border-r-b" style="min-height: 36px; align-content: center"> </div>
                                <div class="div8 border-r-b " style="min-height: 36px; align-content: center">
                                    @if($processes->description)
                                        {{ $processes->description }}
                                    @endif
                                </div>
                            @endif





                        @endforeach
                    @endif

                @endif

            @endforeach
            @php
                $emptyRow = $totalRow - $dateRows;
            @endphp
            @for($i=0; $i < $emptyRow; $i++)
                <div class="div1 border-l-b-r " style="min-height: 36px; align-content: center"></div>
                <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong></strong></div>
                <div class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center"></div>
                <div class="div4 border-r-b " style="min-height: 36px; align-content: center"></div>
                <div class="div5 border-r-b " style="min-height: 36px; align-content: center"></div>
                <div class="div6 border-r-b " style="min-height: 36px; align-content: center"></div>
                <div class="div7 border-r-b" style="min-height: 36px; align-content: center"></div>
                <div class="div8 border-r-b " style="min-height: 36px; align-content: center"></div>
            @endfor

        </div>




    </div>

    <footer >
        <div class="row" style="width: 105%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__("Form #032")}}
            </div>
            <div class="col-6 text-end  ">
                {{__('Rev#0, 29 June 2020   ')}}
            </div>
        </div>

    </footer>

    <!-- Скрипт для печати -->
    <script>
        function printForm() {
            window.print();
        }
    </script>
</div>
</body>
</html>
