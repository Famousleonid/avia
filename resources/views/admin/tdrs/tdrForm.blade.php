<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDR Form</title>
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
            <h5 class="pt-1   text-black text-"><strong>WORK ORDER TEAR DOWN REPORT</strong></h5>
        </div>

    </div>

    <div class="row" style="height: 30px">
        <div class="col-5 pt-1">
            <p class="fs-6 text-end " >COMPONENT DESCRPTION:</p>
        </div>
        <div class="col-5 border-all pt-1" style="height: 32px">
            <h5 class="">
            <strong> {{$current_wo->description}}</strong>
            </h5>
        </div>
        <div class="col-2 border-t-r-b" style="height: 32px" >
            <h5 class="pt-1">
               <strong> W{{$current_wo->number}}</strong>
            </h5>
        </div>
    </div>

    <div class="row" style="height: 32px">
        <div class="col-5 pt-1" style="height: 32px">
            <p class="fs-6 text-end ">COMPONENT PART NO.:</p>
        </div>
        <div class="col-5 pt-1 border-l-b-r" style="height: 32px" >
            <h5 class="">
            <strong> {{$current_wo->unit->part_number}}</strong>
            </h5>
        </div>
    </div>

        <div class="row mt-2 mb-1" >
            <div class="col-6" style="height: 32px">
                <div class="row" >
                    <div class="col-1" style="height: 32px"></div>
                    <div class="col-10 border-all-b" style="height: 32px">
                        <p class="fs-7 pt-1">
                            <strong>TEARDOWN INSPECTION & CONDITION:</strong>
                        </p>
                    </div>
                    <div class="col-1"></div>
                </div>
            </div>
            <div class="col-6 border-all-b" style="height: 32px">
                <p class="fs-7 pt-1">
                    <strong>TEARDOWN INSPECTION & CONDITION:</strong>
                </p>
            </div>
        </div>
        <div class="row  border-all-b" style="height: 38px">
            <div class="col-5">
                <p class="fs-7 text-end"><strong>ATTENTION PRODUCTION DEPARTMENT:</strong> </p>
            </div>
            <div class="col-7">
                <p class="fs-8  ">MAKE SURE TO ADD INFORMATION FROM WO COWER SHEET TO IDENTIFY PRELIMINARY INSPECTION
                    DETAILS FOR STRIP REPORT</p>
            </div>
        </div>
    <div class="row " >
        <div class="col-6">
            <div class="row " >
                <div class="col-1 border-l-b" style="height: 36px">
                    <img class="pt-1 ps-1" src="{{ asset('img/icons/reqs.png') }}" alt="reqs" style="height: 24px; margin-left:
                            -10px" >
                </div>
                <div class="col-10 border-ll-bb">
                    <p class="fs-8" style="margin-left: -10px">CUSTOMER SNAG CONFIRMED ?</p>
                </div>
                <div class="col-1 border-bb"></div>
            </div>
        </div>
        <div class="col-6">
            <div class="row">
                <div class="col-11 border-bb" style="height: 36px">
                    <p class="fs-5"  style="text-transform: uppercase;"><strong>{{$current_wo->instruction->name}}</strong></p>
                </div>
                <div class="col-1 border-ll-bb-rr">
                    <img src="{{ asset('img/icons/check.svg') }}" alt="Check"
                         style="width: 32px; margin-left: -12px">
                </div>
            </div>
        </div>
    </div>
    <div class="row " >
        <div class="col-6">
            <div class="row " >
                <div class="col-1 border-l-b align-items-center justify-content-center" style="height: 36px">
                     <img class="pt-1 ps-1" src="{{ asset('img/icons/reqs.png') }}" alt="reqs" style="height: 24px; margin-left:
                            -10px" >
                </div>
                <div class="col-10 border-ll-bb">
                    <p class="fs-8" style="margin-left: -10px">CUSTOMER SNAG <strong>NOT</strong> CONFIRMED ?</p>
                </div>
                <div class="col-1 border-bb"></div>
            </div>
        </div>
        <div class="col-6">
            <div class="row">
                <div class="col-1 border-bb"></div>
                <div class="col-10 border-bb" style="height: 36px"></div>
                <div class="col-1 border-ll-bb-rr">
{{--                    {{count($tdrInspections)}}--}}
                </div>
            </div>
        </div>
    </div>
    @php
        // Количество строк для каждого столбца
        $totalRows = 20;
        // Разделяем значения массива на два столбца
        $firstColumn = array_slice($tdrInspections, 0, $totalRows);
        $secondColumn = array_slice($tdrInspections, $totalRows, $totalRows);
    @endphp

    @for ($i = 0; $i < $totalRows; $i++)
        <div class="row">
            <div class="col-6"> <!-- первый столбец -->
                <div class="row">
                    <div class="col-1 border-l-b-r" style="height: 36px">
                        <img class="pt-1 ps-1" src="{{ asset('img/icons/reqs.png') }}" alt="reqs" style="height: 24px;
                        margin-left:
                            -10px" >
                    </div>
                    <div class="col-10 border-b" style="height: 36px">
                        <p class="fs-75">
                            <!-- Заполняем значением из первого столбца, если оно есть -->
{{--                            {!! isset($firstColumn[$i]) ? $firstColumn[$i] : '' !!}--}}
                            {!! strtoupper($firstColumn[$i] ?? '') !!}
                        </p>
                    </div>
                    <div class="col-1 border-l-b">
                        @if(isset($firstColumn[$i]) && $firstColumn[$i] !== '')
                            <img src="{{ asset('img/icons/check.svg') }}" alt="Check"
                                 style="width: 32px; margin-left: -14px">
                        @endif
                    </div>

                </div>
            </div>
            <div class="col-6"> <!-- второй столбец -->
                <div class="row">
                    <div class="d-flex  col-1 border-b-r" style="height: 36px ">
                        <img src="{{ asset('img/icons/reqs_bb.png') }}" alt="reqs" style="height: 36px; margin-left:
                            -16px" >
                        <img class="pt-1 ps-1" src="{{ asset('img/icons/reqs.png') }}" alt="reqs" style="height: 24px;" >

                    </div>
                    <div class="col-10 border-b" style="height: 36px">
                        <p class="fs-75">
                        <!-- Заполняем значением из второго столбца, если оно есть -->
                        {!! strtoupper($secondColumn[$i] ?? '') !!}
                        </p>
                    </div>
                    <div class="col-1 border-l-b-r" style="height: 36px">
                        @if(isset($secondColumn[$i]) && $secondColumn[$i] !== '')
                            <img src="{{ asset('img/icons/check.svg') }}" alt="Check"
                                 style="width: 32px; margin-left: -16px">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endfor


    <footer >
        <div class="row" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__("Form #003")}}
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
