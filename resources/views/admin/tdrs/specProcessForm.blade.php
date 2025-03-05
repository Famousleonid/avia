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
            max-width: 980px;
            height: auto;
            /*transform: scale(0.8);*/
            transform-origin: top left;
            padding: 5px;
            margin-left: 10px;
            margin-right: 10px;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                /*size: letter landscape;*/
                size: 11in 8.5in;
                margin: 2mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: auto;
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
                width: 960px;
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
        .border-t {
            border-top: 1px solid black;

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
    <button class="btn btn-primary no-print" onclick="window.print()">
        Print Form
    </button>

</div>

<div class="container-fluid">

    <div class="row">
        <div class="col-1">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 180px; margin: 6px 10px 0;">
        </div>
        <div class="col-11">
            <h5 class="pt-1  text-black text-center"><strong>Special Process Form</strong></h5>
        </div>
    </div>
{{--    <div class="row mt-3">--}}
{{--        <div class="col-1"></div>--}}
{{--        <div class="col-2"></div>--}}
{{--        <div class="col-2"></div>--}}
{{--        <div class="col-2 border-b text-center"> <strong> W{{$current_wo->number}}</strong></div>--}}
{{--        <div class="col-4"></div>--}}
{{--    </div>--}}
{{--    <div class="row mt-3">--}}
{{--        <div class="col-2 ps-3 ">--}}
{{--            <div class="row" style="height: 32px">--}}
{{--                <div class="col-8 pt-1 text-end">--}}
{{--                    <h6><strong>Cat #1  </strong></h6>--}}
{{--                </div>--}}
{{--                <div class="col-2 text-end">--}}
{{--                    <img src="{{ asset('img/icons/icons8-right-arrow.gif') }}" alt="arrow"--}}
{{--                                        style="width: 32px;">--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="col-2">--}}
{{--            <div class="row" style="height: 32px">--}}
{{--                <div class="col-1 border-l-t-b" ></div>--}}
{{--                <div class="col-10 border-l-t-b fs-8 " style="color: gray; font-style: italic">RO No.</div>--}}
{{--                <div class="col-1 border-all"></div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="col-2">--}}
{{--            <div class="row" style="height: 32px">--}}
{{--                <div class="col-1 " ></div>--}}
{{--                <div class="col-10 border-l-t-b fs-8 " style="color: gray; font-style: italic">RO No.</div>--}}
{{--                <div class="col-1 border-all"></div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
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
            <div class="border-l-t-b text-center pt-1 fs-8" style="width: 25px;height: 25px">N/A</div>
            <div class="border-l-t-b ps-2 fs-8 " style="width: 130px;height: 25px; color: grey; font-style: italic" >RO
                No.</div>
            <div class="border-all text-center pt-1 fs-8" style="width: 25px;height: 25px">N/A</div>
            <div class=" text-center fs-8" style="width: 20px;height: 20px"></div>
            <div class="border-l-t-b ps-2 fs-8 " style="width: 100px;height: 25px; color: grey; font-style: italic" >RO
                No.</div>
            <div class="border-all text-center pt-1 fs-8" style="width: 25px;height: 25px">N/A</div>
            <div class=" text-center fs-8" style="width: 305px;height: 20px"></div>
            <div class=" text-end pt-2 fs-75" style="width: 75px;height: 20px">Technician</div>
            <div class="border-b " style="width: 120px"></div>
            <div class="border-l-t-r" style="width: 40px;height: 25px"></div>

        </div>
        <div class="d-flex">
            <div class="text-end fs-8 pe-4" style="width: 880px">Name</div>
            <div class=" " style="width: 29px"></div>
            <div class="border-l-b-r" style="width: 40px;height: 15px"></div>
        </div>


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

    <!-- Скрипт для печати -->
    <script>
        function printForm() {
            window.print();
        }
    </script>
</div>
</body>
</html>
