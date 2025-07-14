<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R&M Record</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 820px;
            height: auto;
            /*transform: scale(0.8);*/
            transform-origin: top left;
            padding: 3px;
            margin-left: 10px;
            margin-right: 10px;
        }


        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                /*size: letter ;*/
                size: Letter;
                margin: 2mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: auto;
                width: auto;
                margin-left: 3px;
                padding: 0;
            }


            .container-fluid {
                max-height: calc(100vh - 20px); /* Оставляем место для футера */
                overflow: hidden;
                margin: 0 !important;
                padding: 3px !important;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print{
                display: none;
            }
            /* Уменьшаем отступы между секциями */
            .row {
                margin-bottom: 0 !important;
            }
            /* Колонтитул внизу страницы */
            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 2px 0;
                margin: 0;
            }

            /*!* Уменьшаем отступы в таблицах *!*/
            /*.div1, .div2, .div3, .div4, .div31, .div32, .div33, .div34, .div35, .div36 {*/
            /*    padding-top: 2px !important;*/
            /*}*/

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
        .fs-8 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-7 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.75rem; /* или любое другое подходящее значение */
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



        .title {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(1, 1fr);
            gap: 0px;
        }


        .div2 {
            grid-column: span 3 / span 3;
        }

        .div3 {
            grid-column-start: 5;
        }


        .parent {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            /*grid-template-rows: repeat(5, .5fr);*/
            gap: 0;
        }


        .div12 {
            grid-column: span 2 / span 2;
        }

        .div13 {
            grid-column-start: 4;
        }

        .div14 {
            grid-column: span 3 / span 3;
            grid-column-start: 5;
        }

        .div15 {
            grid-column-start: 8;
        }

        .div16 {
            grid-column-start: 9;
        }

        .div17 {
            grid-column: span 3 / span 3;
            grid-column-start: 10;
        }



        .qc_stamp {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            grid-template-rows: repeat(1, 1fr);
            gap: 0px;
        }

        .div21 {
            grid-column: span 3 / span 3;
        }

        .div22 {
            grid-column: span 4 / span 4;
            grid-column-start: 4;
        }

        .div23 {
            grid-column-start: 8;
        }

        .div24 {
            grid-column: span 4 / span 4;
            grid-column-start: 9;
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


    <div class="title">

        <div class="div1">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 160px">
        </div>
        <div class="div2">
            <h5 class="pt-3  text-black text-center"><strong>Repair and Modification Record WO#</strong></h5>

        </div>
        <div class="div3 pt-3 border-all text-center mb-2">
            <h4>
                    <strong>W{{$current_wo->number}}</strong>
            </h4>
        </div>
    </div>

    <div class="row border-all-b  m-sm-0">
        <h5 class="ps-1 fs-9">Technical Notes:</h5>
        @for($i = 1; $i <= 7; $i++)
            @php
                $noteKey = 'note' . $i;
                $noteValue = $technicalNotes[$noteKey] ?? '';
            @endphp
            <div class="border-b pt-2" style="height: 30px">{{ $noteValue }}</div>
        @endfor
    </div>
<p></p>

    <div class="parent mt-3">
        <div class="div11 border-l-t-b text-center align-content-center fs-75" >Item</div>
        <div class="div12 border-l-t-b text-center align-content-center fs-75">Part Description</div>
        <div class="div13 border-l-t-b text-center align-content-center fs-75">Modification or Repair #</div>
        <div class="div14 border-l-t-b text-center align-content-center fs-75">Description of Modification  or
            Repair</div>
        <div class="div15 border-l-t-b text-center align-content-center fs-75">Previously Carried out</div>
        <div class="div16 border-l-t-b text-center align-content-center fs-75">Carried out by AT</div>
        <div class="div17 border-all text-center align-content-center fs-75">Identification Method</div>
        @for($i=1; $i<17; $i++)
            @php
                $rmRecord = $rmRecords->get($i-1);
            @endphp
            <div class="div11 border-l-b text-center align-content-center fs-75" style="height: 37px">{{$i}}</div>
            <div class="div12 border-l-b text-center align-content-center fs-75" >{{ $rmRecord ? $rmRecord->part_description : '' }}</div>
            <div class="div13 border-l-b text-center align-content-center fs-75" >{{ $rmRecord ? $rmRecord->mod_repair : '' }}</div>
            <div class="div14 border-l-b text-center align-content-center fs-75" >{{ $rmRecord ? $rmRecord->description : '' }}</div>
            <div class="div15 border-l-b text-center align-content-center fs-75" style="color: lightgray">tech stamp</div>
            <div class="div16 border-l-b text-center align-content-center fs-75" style="color: lightgray">tech stamp</div>
            <div class="div17 border-l-b-r text-center align-content-center fs-75" >{{ $rmRecord ? $rmRecord->ident_method : '' }}</div>
        @endfor

    </div>

    <div class="qc_stamp mt-1">
        <div class="div21" style="height: 37px"></div>
        <div class="div22 border-all text-end align-content-center pe-1 fs-8" >Quality Assurance Acceptance </div>
        <div class="div23 border-t-r-b text-center align-content-center fs-8" style="color: lightgray">Q.C. stamp</div>
        <div class="div24 border-t-r-b text-center  pt-4  fs-8" style="color: lightgray">Date</div>
    </div>


</div>

<footer >
    <div class="row" style="width: 100%; padding: 1px 1px;">
        <div class="col-6 text-start">
            {{__("Form #005")}}
        </div>

        <div class="col-6 text-end pe-4 ">
            {{__('Rev#0, 15/Dec/2012   ')}}
        </div>
    </div>
</footer>
</body>
</html>

