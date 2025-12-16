<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PART RECERTIFICATION / TRANSFER SHEET</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 960px;
            height: 100%;
            padding: 5px;
            /*margin: 0 auto; !* Центрирование по горизонтали *!*/
            margin-left: 10px; margin-right: 10px;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: landscape;
                margin: 5mm;


            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: 100%;
                width: 100%;
                margin: 0 auto; /* Центрирование контента */
                padding: 0;
            }

            .container-fluid {
                margin: 0 auto; /* Центрирование контейнера при печати */
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
                font-size: 14px;
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
        .border-ll-rr {
            border-left: 2px solid black;
            border-right: 2px solid black;
        }
        .border-tt-rr {
            border-top: 2px solid black;
            border-right: 2px solid black;
        }
        .border-t-rr {
            border-top: 1px solid black;
            border-right: 2px solid black;
        }

        .border-bb-rr {
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

        .section-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 8px;
            padding: 4px 6px;
            /*background-color: #f0f0f0;*/
        }
        .section-title2 {
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 10px;
            padding-left: 6px;
            /*padding: 4px 6px;*/
            /*background-color: #f0f0f0;*/
        }

        .field-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 0.85rem;
        }

        .field-label {
            width: 120px;
            font-weight: bold;
            /*margin-bottom: 8px;*/
            padding-top: 8px;
            padding-left:  15px;
            /*padding-right: 4px;*/
        }
        .field-label-b {
            width: 190px;
            margin-top: 9px;
            /*padding-top: 8px;*/
            font-weight: bold;
            padding-left:  15px;
        }
        .field-value {
            flex: 1;
            padding-top: 8px;
            padding-left: 8px;
        }

        .note {
            font-size: 0.8rem;
            /*font-style: italic;*/
            font-weight: bold;
            /*margin: 8px ;*/
        }
        .stamp {
            font-size: 0.75rem;
            font-style: italic;
            color: lightgray;

        }

        .certification-text {
            font-size: 0.75rem;
            line-height: 1.4;
            margin: 12px 0;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(0, 0, 0, 0.05);
            z-index: -1;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
        }

        table th,
        table td {
            border: 1px solid black;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }

        table th {
            font-weight: bold;
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
<!-- Кнопка для печати -->
<div class="text-center m-3 no-print">
    <button class="btn btn-primary" onclick="window.print()">
        Print Form
    </button>
</div>

<div class="container-fluid" style="position: relative;">
    <!-- Watermark -->
{{--    <div class="watermark">Page 1</div>--}}

    <!-- Header -->
    <div class="row">
        <div class="col-3">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 120px; margin: 6px 10px 0;">
        </div>
        <div class="col-9">
            <h5 class="pt-4   text-black text-"><strong>PART RECERTIFICATION / TRANSFER SHEET</strong></h5>
        </div>
    </div>


    <!-- End Assembly Details Section -->
    <div class="field-row border-all-b">
        <div class="col-4">
            <div class="section-title border-bb-rr">
                End Assembly Details:
            </div>
            <div class="field-row">
                <div class="field-label ">*Part Number:</div>
                <div class="field-value border-b">{{ optional(optional($transfer->workorderSource)->unit)->part_number ?? '—'
                }}</div>
            </div>
            <div class="field-row">
                <div class="field-label ">*Serial Number:</div>
                <div class="field-value border-b">{{ optional($transfer->workorderSource)->serial_number ?? '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label ">*Description:</div>
                <div class="field-value border-b">{{ optional($transfer->workorderSource)->description ?? '—' }}</div>
            </div>
        </div>
        <div class="col-4 mt-2">
            <div class="field-row">
                <div class="field-label-b border-l text-end">*Transferred from WO No.:</div>
                <div class="field-value border-b ">W{{ optional($transfer->workorderSource)->number ?? '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label-b text-end">Transferred to WO No.:</div>
                <div class="field-value border-b">W{{ optional($transfer->workorder)->number ?? '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label-b text-end">Reason for Transfer:</div>
                <div class="field-value border-b">{{ optional($transfer->reasonCode)->name ?? '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label-b text-end">Unit Purchased on PO No.:</div>
                <div class="field-value border-b">{{ $transfer->unit_on_po ?? ' ' }}</div>
            </div>
        </div>
        <div class="col-4 mt-2 me-2">
            <div class="field-row">
                <div class="field-label">Manufacturer:</div>
                <div class="field-value border-b">{{ optional(optional(optional(optional($transfer->workorderSource)->unit)
                ->manuals)
                ->builder)->name ?? ' ' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Aircraft type:</div>
                <div class="field-value border-b">{{ optional(optional(optional(optional($transfer->workorderSource)->unit)
                ->manuals)
                ->plane)->type ?? ' ' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Library No:</div>
                <div class="field-value border-b">{{ optional(optional(optional($transfer->workorderSource)->unit)->manuals)->lib
                 ??
                '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">CMM No.:</div>
                <div class="field-value border-b">{{ optional(optional(optional($transfer->workorderSource)->unit)->manuals)
                ->number ??
                 '—' }}</div>
            </div>
        </div>
    </div>

    <div class=" note  ps-2" style="margin-top: 6px;">
         *P/N and S/N should reflect the unit these parts were transferred <u>from</u>.
    </div>




    <!-- Parts Recertification Details Section -->
    <div class="border-all-b">
        <div class="d-flex border-b justify-content-between">
            <div class="section-title2 me-5 ">
                Parts Recertification Details
            </div>
            <div class="note pt-3" style="padding: 4px 8px; margin: 0;">
                Note: (all tasks must be qualified by appropriate lead hand & stamped prior to carrying out)
            </div>
        </div>

        <div class="border-l border-r border-b">
            <table>
                <thead>
                <tr>
                    <th rowspan="2" style="width: 8%;">IPL Item #</th>
                    <th rowspan="2" style="width: 15%;">Description</th>
                    <th rowspan="2" style="width: 12%;">P/N and QTY</th>
                    <th rowspan="2" style="width: 12%;">Serial No./CSN/CSO</th>
                    <th colspan="5" style="width: 28%;">Inspection carried out on part—circle & attach applicable paperwork</th>
                    <th rowspan="2" style="width: 5%;">N.D.T.</th>
                    <th rowspan="2" style="width: 5%;">Cadmium Plating</th>
                    <th rowspan="2" style="width: 5%;">Misc.</th>
                    <th rowspan="2" style="width: 5%;">Misc.</th>
                </tr>

                </thead>
                <tbody>
                <tr>
                    <td style="height: 60px">{{ optional($transfer->component)->assy_ipl_num ?? '—' }}</td>
                    <td>{{ optional($transfer->component)->name ?? '—' }}</td>
                    <td>{{ optional($transfer->component)->assy_part_number ?? '—' }}</td>
                    <td>{{ $transfer->component_sn ?? '—' }}</td>
                    <td>VISUAL</td>
                    <td>FITS & CLEARANCES</td>
                    <td>TESTED</td>
                    <td class="stamp">Tech Stamp</td>
                    <td> </td>
                    <td class="stamp">Quality Stamp</td>
                    <td class="stamp">Quality Stamp</td>
                    <td class="stamp">Quality Stamp</td>
                    <td class="stamp">Quality Stamp</td>
                </tr>
                @for($i = 0; $i < 4; $i++)
                    <tr >
                        <td style="height: 60px"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>VISUAL</td>
                        <td>FITS & CLEARANCES</td>
                        <td>TESTED</td>
                        <td class="stamp">Tech Stamp</td>
                        <td> </td>
                        <td class="stamp">Quality Stamp</td>
                        <td class="stamp">Quality Stamp</td>
                        <td class="stamp">Quality Stamp</td>
                        <td class="stamp">Quality Stamp</td>
                    </tr>
                @endfor
                </tbody>
            </table>
        </div>
    </div>

    <!-- Certification Section -->
    <div>
        <div class="note ms-2 mt-2" style="">
            Note: (all appropriate paper work must be attached to this recertification report prior to recertifying signature!)
        </div>

    <div class="field-row border-all-b">
        <div class="row ">
            <div class="col-6">
                <div class=" fs-8 pt-3 ps-2" style="line-height: 1.1">
                    I certify that the work specified above was carried out in accordance with Canadian Airworthiness Regulations, and in respect of that work, the part(s) has(have) been determined to conform to the approved type design, or to be acceptable under section 571.13 of the CARS.
                </div>
            </div>
            <div class="col-1 border-ll-rr fs-9" style="font-weight: bold; align-content: center">
                For QC use only
            </div>
            <div class="col-5">
                <div class="row">
                    <div class="col-7 border-b" style="align-content: end;font-weight: bold">Signature:</div>
                    <div class="col-5 fs-8 pt-2" style="font-weight: bold">Approval No. 50-12</div>
                </div>
                <div class="row">
                    <div class="col-4 border-t-rr stamp text-center" style="height: 45px ; align-content: center">Quality
                        Stamp</div>
                    <div class="col-3"></div>
                    <div class="col-5 fs-9" style="align-content: end; font-weight: bold">Date:</div>
                </div>
            </div>
        </div>
    </div>



    </div>


    <footer >
        <div class="row" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__("Form #011")}}
            </div>
            <div class="col-6 text-end pe-1 ">
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
