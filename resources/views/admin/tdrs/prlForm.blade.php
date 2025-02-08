<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRL Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 920px;

            height: 99%;
            padding: 5px;
            margin-left: 30px;
            margin-right: 5px;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: letter;
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
        .fs-8 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-9 {
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
    <div class="row">
        <div class="col-4">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 180px; margin: 6px 10px 0;">
        </div>
        <div class="col-8">
            <h2 class="p-2 mt-3 text-black text-"><strong>PART REPLACEMENT LIST</strong></h2>
        </div>

    </div>
    <div class="row">
        <div class="col-1 text-end"><h6><strong>P/N:</strong> </h6></div>
        <div class="col-5 ">
            <div class="border-b">
                <h6 class=""><strong> {{$current_wo->unit->part_number}}</strong></h6>
            </div>
        </div>
        <div class="col-3 ">
            <div class="row ">
                <div class="col-5 border-b">
                    <div class="d-flex ">
                        <h6 class=" "><strong>MFR: </strong></h6>
                        @foreach($manuals as $manual)
                            @if($manual->id == $current_wo->unit->manual_id)
                                @foreach ($builders as $builder)
                                    @if($builder->id == $manual->builders_id)
                                        <h6 class=" ms-2"><strong> {{$builder->name}}</strong></h6>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </div>

                </div>
                <div class="col-5 border-b"> </div>

            </div>
        </div>
        <div class="col-3">
            <h5 class="p-1 border-all text-center">
                <strong>{{__('WO No: W')}}{{$current_wo->number}}</strong>
            </h5>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-6 ">
            <div class="d-flex border-b">
                    <h6 class="ms-4 me-3"><strong>DESC: </strong></h6>
                   <div class="">
                       @foreach($manuals as $manual)
                           @if($manual->id == $current_wo->unit->manual_id)
                               <h6 class=""><strong> {{$manual->title}}</strong></h6>
                           @endif
                       @endforeach
                   </div>

            </div>
        </div>
        <div class="col-6 ">
            <div class="row">
                <div class="col-2 border-b">
                    <h6 class="" ><strong>CMM: </strong></h6>
                </div>
                <div class="col-3 border-b">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h6 class=""><strong> {{$manual->number}}</strong></h6>
                        @endif
                    @endforeach
                </div>
                <div class="col-6"></div>
            </div>
        </div>
    </div>

    <div class="row mt-4 " style="width: 1020px">
        <div class="col-5">
            <div class="row">
                <div class="col-1 border-l-t-b  align-content-center">
                    <h6 style="margin-left: -8px">FIG No.</h6>
                </div>
                <div class="col-2 border-l-t-b" >
                    <h6 >ITEM No.</h6></div>
                <div class="col-9 border-l-t-b align-content-center">DESCRIPTION</div>
            </div>
        </div>
        <div class="col-7" >
            <div class="row" style="height: 48px">
                <div class="col-4 border-l-t-b text-center align-content-center">
                    <h6>PART NUMBER</h6>
                </div>
                <div class="col-1 border-l-t-b  align-content-center">
                    <h6 style="margin-left: -7px">QTY</h6>

                </div>
                <div class="col-1 border-l-t-b  align-content-center">
                    <h6 style="margin-left: -10px">CODE</h6>
                </div>
                <div class="col-2 border-l-t-b text-center align-content-center">
                    <h6>PO No.</h6>
                </div>
                <div class="col-2 border-all align-content-center">
                    <h6>Notes</h6>
                </div>
            </div>
        </div>
    </div>

    @php
        $totalRows = 28; // Фиксированное количество строк
        $partsCount = count($ordersParts); // Количество данных
    @endphp

    @for($i = 0; $i < $totalRows; $i++)
        @php
            // Если данные существуют, разделяем ipl_num
            if ($i < $partsCount) {
                $ipl_num = $ordersParts[$i]->component->ipl_num ?? '';
                $ipl_parts = explode('-', $ipl_num); // Разделяем строку по "-"
                $first_part = $ipl_parts[0] ?? ''; // Первая часть (1)
                $second_part = $ipl_parts[1] ?? ''; // Вторая часть (330)
            } else {
                $first_part = '';
                $second_part = '';
            }
        @endphp

        <div class="row" style="width: 1020px">
            <div class="col-5">
                <div class="row" style="height: 36px">
                    <div class="col-1 border-l-b align-content-center">
                        <h6>{{ $first_part }}</h6> <!-- Первая часть (1) -->
                    </div>
                    <div class="col-2 border-l-b">
                        <h6>{{ $second_part }}</h6> <!-- Вторая часть (330) -->
                    </div>
                    <div class="col-9 border-l-b align-content-center">
                        {{ $i < $partsCount ? $ordersParts[$i]->component->name : '' }}
                    </div>
                </div>
            </div>
            <div class="col-7">
                <div class="row" style="height: 36px">
                    <div class="col-4 border-l-b text-center align-content-center">
                        <h6>{{ $i < $partsCount ? $ordersParts[$i]->component->part_number : '' }}</h6>
                    </div>
                    <div class="col-1 border-l-b align-content-center">
                        <h6 style="margin-left: -7px">{{ $i < $partsCount ? $ordersParts[$i]->quantity : '' }}</h6>
                    </div>
                    <div class="col-1 border-l-b align-content-center">
                        <h6 style="margin-left: -10px">{{ $i < $partsCount ? ($ordersParts[$i]->codes->code ?? '') : '' }}</h6>
                    </div>
                    <div class="col-2 border-l-b text-center align-content-center">
                        <h6>{{ $i < $partsCount ? $ordersParts[$i]->po_number : '' }}</h6>
                    </div>
                    <div class="col-2 border-l-b-r align-content-center">
                        <h6>{{ $i < $partsCount ? $ordersParts[$i]->notes : '' }}</h6>
                    </div>
                </div>
            </div>
        </div>
    @endfor




{{-- @foreach($ordersParts as $part)--}}
{{--        <p>{{$part->component->name}} {{$part->component->ipl_num}} {{$part->component->part_number}}</p>--}}

{{-- @endforeach--}}







    <footer >
        <div class="row" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__("Form #028")}}
            </div>
            <div class="col-6 text-end pe-4 ">
                {{__('Rev#0, 15/Dec/2012   ')}}
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
