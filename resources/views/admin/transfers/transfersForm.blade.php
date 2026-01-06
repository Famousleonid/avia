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
            margin-left: 10px; margin-right: 10px;
        }

        @media print {
            @page {
                size: landscape;
                margin: 5mm;
            }

            html, body {
                height: 100%;
                width: 100%;
                margin: 0 auto;
                padding: 0;
            }

            .container-fluid {
                margin: 0 auto;
            }

            table, h1, p {
                page-break-inside: avoid;
            }

            .no-print {
                display: none;
            }

            footer {
                position: fixed;
                bottom: 0;
                width: 960px;
                text-align: center;
                font-size: 14px;
                background-color: #fff;
                padding: 3px 3px;
            }

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
            font-size: 0.7rem;
        }
        .fs-75 {
            font-size: 0.75rem;
        }
        .fs-8 {
            font-size: 0.8rem;
        }
        .fs-9 {
            font-size: 0.9rem;
        }

        .details-row {
            display: flex;
            align-items: center;
            height: 36px;
        }
        .details-cell {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid black;
        }
        .check-icon {
            width: 24px;
            height: auto;
            margin: 0 5px;
        }

        .section-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 8px;
            padding: 4px 6px;
        }
        .section-title2 {
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 10px;
            padding-left: 6px;
        }

        .field-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 0.85rem;
        }

        .field-label {
            width: 120px;
            font-weight: bold;
            padding-top: 8px;
            padding-left:  15px;
        }
        .field-label-b {
            width: 190px;
            margin-top: 9px;
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
            font-weight: bold;
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
                <div class="field-value border-b">{{ optional($sourceWo->unit)->part_number ?? '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label ">*Serial Number:</div>
                <div class="field-value border-b">{{ $sourceWo->serial_number ?? '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label ">*Description:</div>
                <div class="field-value border-b">{{ $sourceWo->description ?? '—' }}</div>
            </div>
        </div>
        <div class="col-4 mt-2">
            <div class="field-row">
                <div class="field-label-b border-l text-end">*Transferred from WO No.:</div>
                <div class="field-value border-b ">W{{ $sourceWo->number ?? '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label-b text-end">Transferred to WO No.:</div>
                <div class="field-value border-b">
                    @if($transfers->isNotEmpty())
                        @php
                            $targetWos = $transfers->pluck('workorder.number')->unique()->implode(', W');
                        @endphp
                        W{{ $targetWos }}
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="field-row">
                <div class="field-label-b text-end">Reason for Transfer:</div>
                <div class="field-value border-b">
                    @if($transfers->isNotEmpty())
                        {{ optional($transfers->first()->reasonCode)->name ?? '—' }}
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="field-row">
                <div class="field-label-b text-end">Unit Purchased on PO No.:</div>
                <div class="field-value border-b">
                    @if($transfers->isNotEmpty())
                        {{ $transfers->first()->unit_on_po ?? ' ' }}
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
        <div class="col-4 mt-2 me-2">
            <div class="field-row">
                <div class="field-label">Manufacturer:</div>
                <div class="field-value border-b">{{ optional(optional(optional($sourceWo->unit)->manuals)->builder)->name ?? ' ' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Aircraft type:</div>
                <div class="field-value border-b">{{ optional(optional(optional($sourceWo->unit)->manuals)->plane)->type ?? ' ' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Library No:</div>
                <div class="field-value border-b">{{ optional(optional($sourceWo->unit)->manuals)->lib ?? '—' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">CMM No.:</div>
                <div class="field-value border-b">{{ optional(optional($sourceWo->unit)->manuals)->number ?? '—' }}</div>
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
                @foreach($transfers as $transfer)
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
                @endforeach
                @for($i = $transfers->count(); $i < 5; $i++)
                    <tr>
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

    <script>
        function printForm() {
            window.print();
        }
    </script>
</div>
</body>
</html>


