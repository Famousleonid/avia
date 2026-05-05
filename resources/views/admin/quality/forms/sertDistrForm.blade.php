<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Certificate of Destruction') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    <style>
        body {
            margin: 0;
            padding: 12px 16px 24px;
            font-family: Times , Helvetica, sans-serif;
            font-size: 14pt;
            color: #000;
            box-sizing: border-box;
        }
        .cert-wrap {
            max-width: 900px;
            margin: 0 auto;
        }
        .cert-title-box {
            border: 3px solid #000;
            text-align: center;
            padding: 3px 8px;
            margin: 12px 0 16px;
        }
        .cert-title-box h1 {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            margin: 0;
            text-transform: uppercase;
        }
        .cert-field-label {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.95rem;
        }
        .cert-field-value {
            font-style: italic;
            border-bottom: 1px solid #000;
            min-height: 1.35em;
            display: inline-block;
            padding: 0 6px 2px;
        }
        .cert-table {
            width: 500px;
            max-width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
            font-size: 0.9rem;
        }
        .cert-table th,
        .cert-table td {
            border: 1px solid #000;
            padding: 3px 8px;
            vertical-align: top;
            line-height: 1.2;
        }
        .cert-table th {
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            background: #f3f3f3;
        }
        .cert-table td.sn-cell,
        .cert-table td.pn-cell {
            white-space: pre-line;
            text-align: center;
        }
        .cert-statement {
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.78rem;
            line-height: 1.45;
            margin: 24px 0;
            padding: 0 8px;
        }
        .cert-sig-block {
            margin-top: 28px;
            font-size: 0.9rem;
        }
        .cert-sig-line {
            border-bottom: 1px solid #000;
            min-height: 1.5em;
            /*margin-top: 4px;*/
        }
        .cert-date-input {
            border: none;
            border-bottom: 1px solid #000;
            font-style: italic;
            font-family: inherit;
            font-size: inherit;
            padding: 0 6px 2px;
            background: transparent;
            min-width: 160px;
        }
        .cert-footer {
            width: 100%;
            font-size: 0.75rem;
            color: #000;
            padding: 8px 12px 4px;
            margin-top: 2rem;
            background: #fff;
            box-sizing: border-box;
        }
        .cert-footer .row {
            margin: 0;
        }
        @media print {
            @page {
                size: Letter portrait;
                margin: 8mm;
            }
            .no-print {
                display: none !important;
            }
            body {
                padding: 80px 25px 32px 50px;
            }
            .cert-footer {
                position: fixed;
                bottom: 0;
                left: 50px;
                right: calc(8mm + 20px);
                width: auto;
                max-width: none;
                margin-top: 0;
                padding: 6px 4px 6px 0;
                box-sizing: border-box;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .cert-footer .row {
                margin-left: 0;
                margin-right: 0;
                --bs-gutter-x: 0.5rem;
            }
            .cert-footer .col-6.text-end {
                padding-right: 0.25rem !important;
            }
        }
    </style>
</head>
<body>
<div class="no-print text-start mb-3">
    <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('Print Form') }}</button>
</div>

<div class="cert-wrap ">
    <div class="row align-items-start mb-4">
        <div class="col-auto">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo" style="width: 160px;">
        </div>
        <div class="col"></div>
    </div>

    <div class="cert-title-box mb-4">
        <h1 style="font-size: 32px">{{ __('Certificate of Destruction') }}</h1>
    </div>

    <div class="d-flex mb-3 mt-2 justify-content-between">
        <span class="cert-field-label">{{ __('AVIATECHNIK CORPORATION WORK ORDER / PURCHASE ORDER') }}:</span>
        <span class="cert-field-value me-1" style="font-weight: bold">W{{ $current_wo->number }}</span>
    </div>

    <div class="mb-5">
        <div class="cert-field-label mb-0">{{ __('CUSTOMER/VENDOR NAME') }}:</div>
        <div class="border-bottom border-dark text-center" style="height: 1.5em; font-size: 20px; font-weight: bold">
            <span class="fst-italic ">{{ $current_wo->customer->name ?? '—' }}</span>
        </div>
    </div>

    <div class="mb-5">
        <span class="cert-field-label">{{ __('CUSTOMER PURCHASE ORDER No') }}:</span>
        <span class="cert-field-value text-end" style="width: 350px; font-weight: bold">{{ $current_wo->customer_po ?: '—'
        }}</span>
    </div>

    <table class="cert-table text-center mt-3">
        <thead>
        <tr>
            <th style="width: 26%;">{{ __('PART NUMBER') }}</th>
            <th style="width: 42%;">{{ __('DESCRIPTION') }}</th>
            <th style="width: 32%;">{{ __('SERIAL NUMBER') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td class="pn-cell text-center" style="line-height: 1.1">{{ $row['part_number'] }}</td>
                <td class="align-middle">{{ $row['description'] }}</td>
                <td class="sn-cell text-center" style="line-height: 1.1">{{ $row['serial_number'] }}</td>
            </tr>
        @endforeach
        @for($i = count($rows); $i < 6; $i++)
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endfor
        </tbody>
    </table>

    <p class="cert-statement mt-5" style="width: 650px; font-size: 16px">
        {{ __('AVIATECHNIK CORPORATION, HEREBY, CERTIFIES THAT THE ABOVE CIVIL AIRCRAFT PARTS, MATERIAL AND/OR EQUIPMENT HAS BEEN DULY REDUCED TO SCRAP AND THE DATA PLATE(S) AND SERIAL NUMBER(S) HAVE BEEN REMOVED IN ACCORDANCE WITH APPROVED QUALITY ASSURANCE POLICIES AND PROCEDURES.') }}
    </p>

    <div class="cert-sig-block ">

        <div class="mb-3 mt-5">
            <span class="cert-field-label">{{ __('AVIATECHNIK CORPORATION AUTHORIZED REPRESENTATIVE') }}:</span>
            <span class="cert-field-value " style="width: 200px;"></span>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <span class="cert-field-label">{{ __('TITLE') }}:</span>
                <span class="cert-field-value text-center" style="width: 250px; font-size: 18px; font-style: normal">Quality
                    Inspector</span>
            </div>
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center flex-wrap gap-2 gap-md-3">
                    <span class="cert-field-label mb-0 flex-shrink-0">{{ __('DATE') }}:</span>
                    <input type="text"
                           class="cert-date-input flex-grow-1"
                           id="certDestructionDate"
                           style="width: auto; min-width: 10rem; max-width: 220px; font-size: 18px"
                           value="{{ now()->format('d/M/Y') }}"
                           placeholder="29/Apr/2025"
                           inputmode="text"
                           autocomplete="off"
                           aria-label="{{ __('Date') }}">
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="cert-footer">
    <div class="row" style="width: 100%; padding: 1px 1px;">
        <div class="col-6 text-start">
            {{ __('Form #117') }}
        </div>
        <div class="col-6 text-end pe-4">
            {{ __('Rev#0, 15/Dec/2012') }}
        </div>
    </div>
</footer>
</body>
</html>
