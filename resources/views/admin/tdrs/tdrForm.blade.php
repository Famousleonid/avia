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
            max-width: 920px;
            height: 100%;
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
                height: 85%;
                width: 105%;
                margin-left: 10px;
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
                width: 920px;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 5px 10px;
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
            border: 3px solid black;
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
        .border-t-r {
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-b {
            border-top: 1px solid black;
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

    </style>
</head>

<body>
<!-- Кнопка для печати -->
<div class="text-start m-3">
    <button class="btn btn-primary no-print" onclick="window.print()">Печать
        формы
    </button>

</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-4">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 210px; margin: 6px 10px 0;">
        </div>
        <div class="col-8">
            <h4 class="pt-4 pb-2  text-black text-"><strong>WORK ORDER TEAR DOWN REPORT</strong></h4>
        </div>

    </div>

    <div class="row">
        <div class="col-6">
            <h5 class="text-end pt-1" >COMPONENT DESCRPTION:</h5>
        </div>
        <div class="col-4 border-all" style="height: 36px"></div>
        <div class="col-2 border-t-r-b" style="height: 36px">
            <h5 class="text-center pt-1">
               <strong> W{{$current_wo->number}}</strong>
            </h5>

        </div>
    </div>
    <div class="row">
        <div class="col-6">
            <h5 class="text-end pt-1">COMPONENT PART N0:</h5>
        </div>
        <div class="col-4 border-l-b-r" style="height: 36px"></div>
    </div>

        <div class="row mt-2 mb-2" style="height: 36px">
            <div class="col-6">
                <div class="row">
                    <div class="col-1"></div>
                    <div class="col-10 border-all-b">
                        <h6 class="pt-1">
                            <strong>TEARDOWN INSPECTION & CONDITION:</strong>
                        </h6>
                    </div>
                    <div class="col-1"></div>
                </div>
            </div>
            <div class="col-6 border-all-b">
                <h6 class="pt-1">
                    <strong>TEARDOWN INSPECTION & CONDITION:</strong>
                </h6>
            </div>
        </div>
        <div class="row  border-all-b" style="height: 36px">
            <div class="col-5">
                <p class="fs-6 text-end"><strong>ATTENTION PRODUCTION DEPARTMENT:</strong> </p>
            </div>
            <div class="col-7">
                <p class="fs-7  ">MAKE SURE TO ADD INFORMATION FROM WO COWER SHEET TO IDENTIFY PRELIMINARY INSPECTION
                    DETAILS FOR STRIP REPORT</p>
            </div>
        </div>
    </div>







    <footer >
        <div class="row" style="width: 900px">
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
