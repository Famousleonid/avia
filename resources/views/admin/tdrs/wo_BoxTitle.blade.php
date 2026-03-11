<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Box Title Sheet</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Calibri", serif;
        }

        .container-fluid {
            max-width: 960px;
            height: auto;
            padding: 5px;
            margin-left: 10px;
            margin-right: 10px;
        }

        @media print {
            @page {
                size: letter;
                margin: 2mm;

            }

            /*html, body {*/
            /*    height: auto;*/
            /*    width: 100%;*/
            /*    margin:30px;*/
            /*    padding: 0;*/
            /*    font-size: 12px;*/
            /*}*/

            .container {
               width: 100%;
                margin-left:  100px;
            }

            .no-print {
                display: none;
            }

            /*footer {*/
            /*    position: relative;*/
            /*    bottom: 0;*/
            /*    width: 100%;*/
            /*    text-align: center;*/
            /*    font-size: 8px;*/
            /*    background-color: #fff;*/
            /*    padding: 2px;*/
            /*    margin-top: 10px;*/
            /*}*/

            /*.section-header {*/
            /*    padding: 6px;*/
            /*    font-size: 14px;*/
            /*}*/

            /*.work-item {*/
            /*    padding: 5px;*/
            /*    font-size: 12px;*/
            /*}*/

            /*.stamp-box {*/
            /*    width: 80px;*/
            /*    height: 35px;*/
            /*    font-size: 11px;*/
            /*}*/

            /*.date-box {*/
            /*    width: 110px;*/
            /*    height: 35px;*/
            /*    font-size: 11px;*/
            /*}*/






            /*h5 {*/
            /*    font-size: 14px;*/
            /*    margin: 5px 0;*/
            /*}*/

            /*.table {*/
            /*    margin-bottom: 8px;*/
            /*}*/

            /*.table td, .table th {*/
            /*    padding: 4px;*/
            /*}*/

            /* Предотвращение пустых страниц */
            /*@page {*/
            /*    size: letter;*/
            /*    margin: 2mm;*/
            /*}*/

            /* Убираем лишнее пространство */
            /*.container-fluid {*/
            /*    page-break-after: avoid;*/
            /*}*/

            /*footer {*/
            /*    page-break-before: avoid;*/
            /*}*/

            /*!* Обеспечиваем единообразные границы для последней строки *!*/
            /*.table tr:last-child td {*/
            /*    border-bottom: 2px solid black !important;*/
            /*}*/

            /* Стили для последней строки таблицы при печати */
            /*.table tr:last-child td {*/
            /*    border-bottom: 2px solid black !important;*/
            /*}*/

            /* Гарантируем отображение границ при печати */
            /*.table td {*/
            /*    border: 1px solid black !important;*/
            /*}*/
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
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
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
        .border-t-r-bb{
            border-top: 2px solid black;
            border-right: 2px solid black;
            border-bottom: 2px solid black;
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
        .text-bold {
            font-weight: bold;
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
        .fs-50 {
            font-size: 120px;

        }
        .fs-4 {
            font-size: 0.4rem;
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

<div class="container " style="margin-top: 40px">
    @for ($i = 0; $i < 2; $i++)
<div class="" style="margin-top: 80px">
    <div class="row " style="width: 600px; ">
        <div class="col text-bold border-all text-center " style="height: 150px;padding: -50px; font-size: 100px">
            W{{ $current_wo->number }}
        </div>
    </div>

    <div class="row" style="width: 600px">
        <div class="col border-l-b  pt-2"> <h4 class="text-bold ps-2"> P/N:</h4> </div>
        <div class="col border-l-b-r  pt-2">
            @foreach($units as $unit)
              @if($unit->id == $current_wo->unit_id)
                 <h4 class="text-bold ps-2">   {{$unit->part_number}}</h4>
             @endif
            @endforeach
        </div>
    </div>

    <div class="row" style="width: 600px">
        <div class="col border-l-b  pt-2"> <h4 class="text-bold ps-2"> Customer:</h4> </div>
        <div class="col border-l-b-r pt-2">
            @foreach($customers as $customer)
                @if($customer->id == $current_wo->customer_id)
                    <h4 class="text-bold ps-2 ">   {{$customer->name}}</h4>
                @endif
            @endforeach
        </div>
    </div>

    <div class="row" style="width: 600px">
        <div class="col border-l-b  pt-2"> <h4 class="text-bold ps-2"> Completion date: </h4> </div>
        <div class="col border-l-b-r text-center  pt-2" style="align-content: end; color: lightgray">
            dd/mm/yy
        </div>
    </div>

    <div class="row " style="width: 600px">
        <div class="col border-l-b  pt-2"  > <h4 class="text-bold ps-2"> TECH NAME:</h4> </div>
        <div class="col border-l-b-r pt-2" >
            @foreach($users as $user)
                @if($user->id == $current_wo->user_id)
                    <h4 class="text-bold ps-2 ">   {{$user->name}}</h4>
                @endif
            @endforeach
        </div>
    </div>

</div>
    <div class="" style="margin-top: 80px">

            <div class="d-flex">
                @for ($l = 0; $l < 70; $l++)
               <h3>-</h3>
                @endfor
            </div>

    </div>
    @endfor
</div>



<footer></footer>

<!-- Скрипт для печати -->
<script>
    function printForm() {
        window.print();
    }
</script>
</body>
</html>
