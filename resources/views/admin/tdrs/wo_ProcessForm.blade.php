<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Process Sheet</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
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

            html, body {
                height: auto;
                width: 100%;
                margin: 0;
                padding: 0;
                font-size: 12px;
            }

            .container-fluid {
                max-width: 98%;
                height: auto;
                padding: 2px;
                margin: 0;
                transform: scale(0.98);
                /*transform-origin: top ;*/
            }

            table, h1, p {
                page-break-inside: avoid;
            }

            .no-print {
                display: none;
            }

            footer {
                position: relative;
                bottom: 0;
                width: 100%;
                text-align: center;
                font-size: 8px;
                background-color: #fff;
                padding: 2px;
                margin-top: 10px;
            }

            .section-header {
                padding: 6px;
                font-size: 14px;
            }

            .work-item {
                padding: 5px;
                font-size: 12px;
            }

            .stamp-box {
                width: 80px;
                height: 35px;
                font-size: 11px;
            }

            .date-box {
                width: 110px;
                height: 35px;
                font-size: 11px;
            }



            .fs-7 {
                font-size: 12px;
            }

            .fs-8 {
                font-size: 11px;
            }

            .fs-9 {
                font-size: 10px;
            }

            /*h5 {*/
            /*    font-size: 14px;*/
            /*    margin: 5px 0;*/
            /*}*/

            .table {
                margin-bottom: 8px;
            }

            .table td, .table th {
                padding: 4px;
            }

            /* Предотвращение пустых страниц */
            @page {
                size: letter;
                margin: 2mm;
            }

            /* Убираем лишнее пространство */
            .container-fluid {
                page-break-after: avoid;
            }

            footer {
                page-break-before: avoid;
            }

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
        .fs-4 {
            font-size: 0.4rem;
        }

        .stamp-box {
            width: 60px;
            height: 30px;
            /*border: 1px solid black;*/
            text-align: center;
            vertical-align: middle;
            /*background-color: #f8f9fa;*/
        }

        .date-box {
            width: 100px;
            height: 30px;
            /*border: 1px solid black;*/
            text-align: center;
            vertical-align: middle;
        }

        .work-item {
            padding: 6px;
            /*border: 1px solid black;*/
            vertical-align: middle;
            /*font-size: 13px;*/
        }

        .section-header {
            /*background-color: #e9ecef;*/
            font-weight: bold;
            text-align: center;
            padding: 10px;

            /*border: 2px solid black;*/
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
    <!-- Заголовок -->
    <div class="row">
        <div class="col-4">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 180px; margin: 6px 10px 0;">
        </div>
        <div class="col-4 text-center pt-4">
            <h5 class="text-black text-bold">Work Order Process Sheet for</h5>
        </div>
        <div class="col-4 pt-4">
            <div class="text-bold "><h5><strong>W{{ $current_wo->number }}</strong></h5></div>
        </div>
    </div>

{{--    <div style="margin-bottom: 12px;">--}}

    <!-- Section A: Work to be carried out -->

    <div class="section-header mt-3 ">

        <div class="row " style="height: 50px">
            <div class="col-1 align-content-center pt-1 " style="border: 2px solid black;"><h5><strong>A</strong></h5></div>
            <div class="col-10 align-content-center pt-1" style="border: 2px solid black; border-left: none;">
                <h5><strong>Work to be
                        carried out</strong></h5></div>
            <div class="col-1"></div>
        </div>

    </div>
    <div class="text-center  mb-1" style="font-style: italic">
                <h5><strong>Select as many as required of the following</strong></h5>
    </div>


    <div class="row">
        <div class="col-6">
            <table class="table " style="margin-bottom: 0;">
                <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">1.</td>
                    <td class="work-item border-l-t-b">FITS & CLEARANCES CARRIED OUT</td>
                    <td class="stamp-box border-all" style="color: grey; width: 70px">TECH STAMP</td>
                </tr>
                <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">2.</td>
                    <td class="work-item border-l-t-b"> SPECIAL PROCESSES to be CARRIED OUT (reference form #012)</td>
                    <td class="stamp-box border-all" style="color: grey; width: 70px">TECH STAMP</td>
                </tr>
                <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">3.</td>
                    <td class="work-item border-l-t-b">SB's and AD's to be CARRIED OUT (reference form #007)</td>
                    <td class="stamp-box border-all" style="color: grey; width: 70px">TECH STAMP</td>
                </tr>
                <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">4.</td>
                    <td class="work-item border-l-t-b">INSPECTED IN ACCORDANCE WITH CMM</td>
                    <td class="stamp-box border-all" style="color: grey; width: 70px">TECH STAMP</td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <table class="table " style="margin-bottom: 0;">
                <tr>
                      <td class="work-item  text-center border-l-t-b" style="width: 70px">5.</td>
                    <td class="work-item border-l-t-b">PRE-TESTED IN ACCORDANCE WITH CMM</td>
                    <td class="stamp-box border-all" style="width: 70px; color: grey">TECH STAMP</td>
                </tr>
                <tr>
                      <td class="work-item  text-center border-l-t-b" style="width: 70px">6.</td>
                    <td class="work-item border-l-t-b">MODIFIED IN ACCORDANCE WITH APPROVED DATA</td>
                    <td class="stamp-box border-all" style="width: 70px; color: grey">TECH STAMP</td>
                </tr>
                <tr>
                      <td class="work-item  text-center border-l-t-b" style="width: 70px">7.</td>
                    <td class="work-item border-l-t-b">REPAIRED IN ACCORDANCE WITH CMM</td>
                    <td class="stamp-box border-all" style="width: 70px; color: grey">TECH STAMP</td>
                </tr>
                <tr>
                      <td class="work-item  text-center border-l-t-b" style="width: 70px">8.</td>
                    <td class="work-item border-l-t-b">FINAL TESTED IN ACCORDANCE WITH CMM</td>
                    <td class="stamp-box border-all" style="width: 70px; color: grey">TECH STAMP</td>
                </tr>
                <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">9.</td>
                    <td class="work-item border-l-t-b">OVERHAULED IN ACCORDANCE WITH CMM</td>
                    <td class="stamp-box border-all" style="width: 70px; color: grey">TECH STAMP</td>
                </tr>
            </table>
        </div>
    </div>

{{--    <div style="margin-bottom: 50px;">--}}

    <!-- Section B: Actual Work Carried out on Component -->

    <div class="section-header mt-3 mb-3">

        <div class="row " style="height: 50px">
            <div class="col-1 align-content-center pt-1 " style="border: 2px solid black;"><h5><strong>B</strong></h5></div>
            <div class="col-10 align-content-center pt-1" style="border: 2px solid black; border-left: none;">
                <h5><strong>Actual Work Carried out on Component</strong></h5></div>
            <div class="col-1"></div>
        </div>

    </div>
    <div class="row">
             <div class="col-8">

            <table class="table ">
                <tbody>
                    <tr>
                        <td class="work-item  text-center border-l-t-b" style="width: 72px">1.</td>
                        <td class="work-item border-l-t-b">Preliminary Testing Carried out in accordance with CMM</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                    <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">2.</td>
                        <td class="work-item border-l-t-b">Teardown Carried out (Reference form #003)</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                    <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">3.</td>
                        <td class="work-item border-l-t-b">Fits & Clearances Carried out in accordance with CMM</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                    <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">4.</td>
                        <td class="work-item border-l-t-b">SPECIAL PROCESSES CARRIED OUT (Reference form #012)</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                    <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">5.</td>
                        <td class="work-item border-l-t-b">SB's and AD's CARRIED OUT (Reference form #007)</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                    <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">6.</td>
                        <td class="work-item border-l-t-b">Unit Modified</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                    <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">7.</td>
                        <td class="work-item border-l-t-b">Unit Assembled</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                    <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">8.</td>
                        <td class="work-item border-l-t-b">Unit Tested</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                    <tr>
                    <td class="work-item  text-center border-l-t-b" style="width: 70px">9.</td>
                        <td class="work-item border-l-t-b">Unit Completed</td>
                        <td class="stamp-box border-l-t-b" style="color: grey">TECH STAMP</td>
                        <td class="date-box border-all" style="width: 150px"></td>
                    </tr>
                </tbody>
            </table>

            <table class="table mb-4 mt-3">
                <tr>
                    <td class="work-item text-center" style="width: 70px; border: 1px solid black;">10.</td>
                    <td class="work-item" style="border: 2px solid black;">Quality Assurance Final Acceptance</td>
                    <td class="stamp-box text-center" style="border: 1px solid black;">Q.A. STAMP</td>
                    <td class="date-box" style="width: 150px; border: 1px solid black;"></td>
                </tr>
            </table>

             </div>

        <!-- TCCA Form One Information -->
        <div class="col-4">

            <div class="row border-all-b">
                <div class="col-8 text-center">
                    <strong>TCCA Form One </strong>
                    <div>(block 11) Information</div>
                </div>
                <div class="col-4  text-decoration-underline" style="font-style: italic;">
                    <h6>choose one only</h6>
                </div>
            </div>
            <div class="row ">
                <div class="col-8 border-l-b align-content-center text-center">
                    INSPECTED/TESTED
                </div>
                <div class="col-4 border-l-b-r align-content-center text-center" style="color: grey">
                    TECH STAMP
                </div>
            </div>
            <div class="row ">
                <div class="col-8 border-l-b align-content-center text-center">
                    MODIFIED
                </div>
                <div class="col-4 border-l-b-r align-content-center text-center" style="color: grey">
                    TECH STAMP
                </div>
            </div>
            <div class="row ">
                <div class="col-8 border-l-b align-content-center text-center">
                    REPAIRED
                </div>
                <div class="col-4 border-l-b-r align-content-center text-center" style="color: grey">
                    TECH STAMP
                </div>
            </div>
            <div class="row ">
                <div class="col-8 border-l-b align-content-center text-center">
                    OVERHAULED
                </div>
                <div class="col-4 border-l-b-r align-content-center text-center" style="color: grey">
                    TECH STAMP
                </div>
            </div>





        </div>
    </div>
    </div>

    <footer>
        <div class="row mt-2" style="width: 100%; padding: 5px 0;">
            <div class="col-4 text-start">
                {{__("Form # 002")}}
            </div>
            <div class="col-4 text-center">
                {{__("1 of 1")}}
            </div>
            <div class="col-4 text-end pe-4">
                {{__('Rev # 0, 15/Dec/2012')}}
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
