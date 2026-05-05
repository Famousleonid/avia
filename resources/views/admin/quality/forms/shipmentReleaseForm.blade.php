<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Shipment release process sheet') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    <style>
        body {
            margin: 0;
            padding: 8px 10px 14px;
            font-family: "Times New Roman", Times, serif;
            color: #000;
            background: #fff;
        }

        .shipment-sheet {
            width: 286mm;
            min-height: 198mm;
            margin: 0;
        }

        .no-print {
            margin-bottom: 8px;
        }

        .sheet-header {
            display: grid;
            grid-template-columns: 58mm 1fr 68mm;
            column-gap: 12mm;
            align-items: start;
            min-height: 32mm;
        }

        .sheet-logo {
            width: 50mm;
            margin-top: 1mm;
        }

        .sheet-title {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.05;
            margin-top: 4mm;
        }

        .shipper-box {
            margin-top: 14mm;
            font-size: 16px;
            justify-self: end;
            width: 60mm;
        }

        .shipper-line {
            display: inline-block;
            min-width: 38mm;
            border-bottom: 2px solid #000;
            text-align: center;
            padding: 0 4px 2px;
        }

        .shipper-caption {
            margin-left: 22mm;
            font-size: 16px;
            margin-top: -10px;
        }

        table.shipment-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 18px;
            line-height: 1.08;
        }

        .shipment-table th,
        .shipment-table td {
            border: 2px solid #000;
            padding: 3px 6px;
            vertical-align: middle;
        }

        .shipment-main-table tr:first-child {
            height: 12mm;
        }

        .shipment-main-table tr:nth-child(2),
        .shipment-main-table tr:nth-child(3) {
            height: 18mm;
        }

        .shipment-table .label-cell {
            font-weight: 700;
        }

        .shipment-table .center {
            text-align: center;
        }

        .shipment-table .right {
            text-align: right;
        }

        .muted-band {
            height: 8mm;
            border-left: 2px solid #000;
            border-right: 2px solid #000;
            border-bottom: 2px solid #000;
            background: #cfcfcf;
        }

        .mid-table {
            margin-top: 5mm;
        }

        .extra-title {
            border: 2px solid #000;
            border-bottom: 0;
            margin-top: 5mm;
            min-height: 16mm;
            padding: 3mm 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: 700;
        }

        .extra-table td {
            height: 13mm;
        }

        .stamp-cell {
            font-size: 14px;
            text-align: center;
        }

        .spacer-cell {
            border-top: 2px solid #000 !important;
            border-bottom: 2px solid #000 !important;
            background: #fff;
        }

        .qa-data-slot {
            color: #0057c8;
            font-weight: 700;
            background: #e7e7e7;
        }

        .qa-input-cell {
            background: #e7e7e7;
            color: #0057c8;
            font-weight: 700;
        }

        .qa-input-cell .qa-data-slot {
            background: transparent;
        }

        .copied-to-cell {
            text-align: left;
            padding-left: 10mm !important;
        }

        .shipper-name-label {
            font-size: 16px;
            font-weight: 400;
            margin-left: 30px;
        }

        .taken-by-label {
            margin-right: 4mm;
        }

        .qa-data-line {
            min-height: 1.15em;
            display: inline-block;
            min-width: 30mm;
            text-align: center;
        }

        .measure-label {
            background: #fff;
            color: #000;
        }

        .shipset-select {
            width: 28mm;
            border: 0;
            border-bottom: 2px solid #0057c8;
            color: #0057c8;
            font: inherit;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            background: transparent;
            padding: 0 2mm 1mm;
            appearance: auto;
        }

        .shipset-print-value {
            display: none;
            color: #0057c8;
            font-size: 26px;
            font-weight: 700;
            line-height: 1;
            padding: 0 3mm;
        }

        @page {
            size: A4 landscape;
            margin: 5mm;
        }

        @media print {
            html,
            body {
                width: 297mm;
                height: 210mm;
            }

            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .shipment-sheet {
                width: 287mm;
                min-height: 198mm;
            }

            .shipset-select {
                display: none !important;
            }

            .shipset-print-value {
                display: inline-block !important;
            }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">{{ __('Print Form') }}</button>
</div>

<main class="shipment-sheet">
    <header class="sheet-header mb-4">
        <div>
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Aviatechnik" class="sheet-logo">
        </div>
        <div class="text-center mb-3">
            <div class="sheet-title">{{ __('Shipment release process sheet') }}</div>
        </div>
        <div class="shipper-box">
            <span>{{ __('Shipper') }}</span>
            <span class="shipper-line qa-data-slot">{{ $shipperName ?? '' }}</span>
            <div class="shipper-caption ">{{ __('Name') }}</div>
        </div>
    </header>

    <table class="shipment-table shipment-main-table">
        <colgroup>
            <col style="width: 12.5%">
            <col style="width: 14.5%">
            <col style="width: 14.5%">
            <col style="width: 13%">
            <col style="width: 12.5%">
            <col style="width: 19%">
            <col style="width: 14%">
        </colgroup>
        <tbody>
        <tr>
            <td class="label-cell">{{ __('Work Order') }} :</td>
            <td class="center qa-data-slot">W{{ $current_wo->number }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td class="label-cell">{{ __('Weight') }}</td>
            <td colspan="2" class="right"><span class="qa-data-line">{{ $weight ?? '' }}</span> <span class="measure-label">lbs</span></td>
            <td class="label-cell center">{{ __('WO part of the') }}<br>{{ __('shipset') }}</td>
            <td class="center qa-input-cell">
                <select class="shipset-select" id="shipsetSelect" aria-label="{{ __('WO part of the shipset') }}">
                    <option value="Yes" selected>Yes</option>
                    <option value="No">No</option>
                </select>
                <span class="shipset-print-value" id="shipsetPrintValue">Yes</span>
            </td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td class="label-cell">{{ __('Dimensions') }}</td>
            <td colspan="2"><span class="measure-label">L=</span><span class="qa-data-line">{{ $dimensionL ?? '' }}</span></td>
            <td colspan="2"><span class="measure-label">W=</span><span class="qa-data-line">{{ $dimensionW ?? '' }}</span></td>
            <td colspan="2"><span class="measure-label">H=</span><span class="qa-data-line">{{ $dimensionH ?? '' }}</span></td>
        </tr>
        </tbody>
    </table>

    <div class="muted-band"></div>

    <table class="shipment-table mid-table">
        <colgroup>
            <col style="width: 27%">
            <col style="width: 29%">
            <col style="width: 44%">
        </colgroup>
        <tbody>
        <tr>
            <td class="label-cell center">{{ __('Pictures of components fastened') }}<br>{{ __('in crate') }}</td>
            <td class="center">
                <strong class="taken-by-label">{{ __('Taken by') }}</strong>
                <span class="shipper-line qa-data-slot">{{ $takenByName ?? '' }}</span>
                <br>
                <span class="shipper-name-label">{{ __('Shipper name') }}</span>
            </td>
            <td class="copied-to-cell">
                <strong class="d-inline-block" style="width: 30mm;">{{ __('Copied to') }}</strong><br>
                <strong>{{ __('WO Picture') }}</strong>
                <span class="shipper-line qa-data-slot ms-4">{{ $copiedByName ?? '' }}</span>
                <br>
                <strong >{{ __('Folder') }}</strong>
                <span class="shipper-name-label" style="margin-left: 60px">{{ __('Shipper Name') }}</span>
            </td>
        </tr>
        </tbody>
    </table>

    <div class="muted-band"></div>

    <section class="extra-title py-4">{{ __('Extra Parts check with Technician') }}</section>
    <table class="shipment-table extra-table">
        <colgroup>
            <col style="width: 15%">
            <col style="width: 12%">
            <col style="width: 13%">
            <col style="width: 5%">
            <col style="width: 8%">
            <col style="width: 12%">
            <col style="width: 12%">
            <col style="width: 14.5%">
            <col style="width: 8.5%">
        </colgroup>
        <tbody>
        <tr>
            <td class="label-cell center">{{ __('Work order') }}:</td>
            <td class="center qa-data-slot">W{{ $current_wo->number }}</td>
            <td class="label-cell center">{{ __('No Extra parts') }}</td>
            <td class="stamp-cell">{{ __('stamp') }}</td>
            <td rowspan="4" class="spacer-cell"></td>
            <td class="label-cell center">{{ __('Work order') }}:</td>
            <td class="center qa-data-slot">{{ $extraWorkorder2 ?? '' }}</td>
            <td class="label-cell center">{{ __('No Extra parts') }}</td>
            <td class="stamp-cell">{{ __('stamp') }}</td>
        </tr>
        <tr>
            <td class="label-cell center">{{ __('Technician') }}:</td>
            <td class="center qa-data-slot">{{ $current_wo->user?->name ?? '' }}</td>
            <td class="label-cell center">{{ __('Extra parts') }}</td>
            <td class="stamp-cell">{{ __('stamp') }}</td>
            <td class="label-cell center">{{ __('Technician') }}:</td>
            <td class="center qa-data-slot">{{ $extraTechnician2 ?? '' }}</td>
            <td class="label-cell center">{{ __('Extra parts') }}</td>
            <td class="stamp-cell">{{ __('stamp') }}</td>
        </tr>
        <tr>
            <td class="label-cell center">{{ __('Work order') }}:</td>
            <td class="center qa-data-slot">{{ $extraWorkorder3 ?? '' }}</td>
            <td class="label-cell center">{{ __('No Extra parts') }}</td>
            <td class="stamp-cell">{{ __('stamp') }}</td>
            <td class="label-cell center">{{ __('Work order') }}:</td>
            <td class="center qa-data-slot">{{ $extraWorkorder4 ?? '' }}</td>
            <td class="label-cell center">{{ __('No Extra parts') }}</td>
            <td class="stamp-cell">{{ __('stamp') }}</td>
        </tr>
        <tr>
            <td class="label-cell center">{{ __('Technician') }}:</td>
            <td class="center qa-data-slot">{{ $extraTechnician3 ?? '' }}</td>
            <td class="label-cell center">{{ __('Extra parts') }}</td>
            <td class="stamp-cell">{{ __('stamp') }}</td>
            <td class="label-cell center">{{ __('Technician') }}:</td>
            <td class="center qa-data-slot">{{ $extraTechnician4 ?? '' }}</td>
            <td class="label-cell center">{{ __('Extra parts') }}</td>
            <td class="stamp-cell">{{ __('stamp') }}</td>
        </tr>
        </tbody>
    </table>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var select = document.getElementById('shipsetSelect');
        var printValue = document.getElementById('shipsetPrintValue');

        if (!select || !printValue) {
            return;
        }

        var syncShipsetPrintValue = function () {
            printValue.textContent = select.value || 'Yes';
        };

        select.addEventListener('change', syncShipsetPrintValue);
        window.addEventListener('beforeprint', syncShipsetPrintValue);
        syncShipsetPrintValue();
    });
</script>
</body>
</html>
