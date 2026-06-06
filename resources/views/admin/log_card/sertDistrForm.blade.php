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
            margin: 0;
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
            width: 620px;
            max-width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
            font-size: 0.9rem;
        }
        .cert-table th,
        .cert-table td {
            border: 1px solid #000;
            padding: 3px 8px;
            vertical-align: middle;
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
        .cert-select-col {
            width: 36px;
        }
        .cert-row-check {
            width: 18px;
            height: 18px;
        }
        .cert-manual-input {
            width: 100%;
            border: 1px dotted #555;
            background: transparent;
            font-family: inherit;
            font-size: inherit;
            text-align: center;
            height: 1.2em;
            line-height: 1.2;
            min-height: 0;
            padding: 0 2px;
            box-sizing: border-box;
            vertical-align: middle;
            display: block;
        }
        .cert-manual-input:focus {
            outline: 1px solid #0d6efd;
        }
        .cert-manual-print-value {
            display: none;
        }
        .cert-print-only-row {
            display: none;
        }
        .cert-manual-row td {
            padding: 3px 8px;
            line-height: 1.2;
            vertical-align: middle;
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
            font-size: 12px;
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
            .cert-select-col,
            .cert-select-cell {
                display: none !important;
            }
            .cert-row-print-hidden {
                display: none !important;
            }
            .cert-screen-only-row {
                display: none !important;
            }
            .cert-print-only-row:not(.cert-row-print-hidden) {
                display: table-row !important;
            }
            .cert-print-only-row.cert-row-print-hidden {
                display: none !important;
            }
            .cert-manual-print-row td {
                height: auto !important;
                max-height: none !important;
                padding: 3px 8px !important;
                line-height: 1.2 !important;
                vertical-align: middle !important;
                overflow: visible !important;
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
@include('shared.print-mark.qr', ['printMarkWorkorder' => $current_wo ?? null])
<div class="no-print text-start mb-3">
    <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('Print Form') }}</button>
    <span class="ms-3 small text-muted" id="certSaveStatus"></span>
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
            <th class="cert-select-col no-print"></th>
            <th style="width: 26%;">{{ __('PART NUMBER') }}</th>
            <th style="width: 42%;">{{ __('DESCRIPTION') }}</th>
            <th style="width: 32%;">{{ __('SERIAL NUMBER') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr class="cert-data-row" data-row-key="{{ $row['key'] }}">
                <td class="cert-select-cell no-print">
                    <input type="checkbox"
                           class="cert-row-check"
                           value="{{ $row['key'] }}"
                           @checked($row['selected'])>
                </td>
                <td class="pn-cell text-center" style="line-height: 1.1">{{ $row['part_number'] }}</td>
                <td class="align-middle">{{ $row['description'] }}</td>
                <td class="sn-cell text-center" style="line-height: 1.1">{{ $row['serial_number'] }}</td>
            </tr>
        @endforeach
        <tr class="cert-manual-row cert-screen-only-row">
            <td class="cert-select-cell no-print">
                <input type="checkbox"
                       class="cert-row-check cert-manual-check"
                       @checked($manualSelected ?? false)>
            </td>
            <td class="pn-cell text-center" style="line-height: 1.1">
                <input type="text"
                       class="cert-manual-input"
                       data-manual-field="part_number"
                       value="{{ $manualRow['part_number'] ?? '' }}">
            </td>
            <td class="align-middle">
                <input type="text"
                       class="cert-manual-input"
                       data-manual-field="description"
                       value="{{ $manualRow['description'] ?? '' }}">
            </td>
            <td class="sn-cell text-center" style="line-height: 1.1">
                <input type="text"
                       class="cert-manual-input"
                       data-manual-field="serial_number"
                       value="{{ $manualRow['serial_number'] ?? '' }}">
            </td>
        </tr>
        <tr class="cert-manual-print-row cert-print-only-row">
            <td class="cert-select-cell no-print"></td>
            <td class="pn-cell text-center" style="line-height: 1.1" data-manual-print-field="part_number">
                {{ $manualRow['part_number'] ?? '' }}
            </td>
            <td class="align-middle" data-manual-print-field="description">
                {{ $manualRow['description'] ?? '' }}
            </td>
            <td class="sn-cell text-center" style="line-height: 1.1" data-manual-print-field="serial_number">
                {{ $manualRow['serial_number'] ?? '' }}
            </td>
        </tr>
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
                           value="{{ $certificateDate }}"
                           placeholder="05/May/2026"
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const saveUrl = @json($saveUrl);
        const csrf = @json(csrf_token());
        const status = document.getElementById('certSaveStatus');
        const table = document.querySelector('.cert-table');
        const certificateDateInput = document.getElementById('certDestructionDate');
        let saveTimer = null;

        function selectedKeys() {
            return Array.from(document.querySelectorAll('.cert-data-row .cert-row-check:checked'))
                .map((input) => input.value)
                .filter(Boolean);
        }

        function manualSelected() {
            return Boolean(document.querySelector('.cert-manual-check')?.checked);
        }

        function manualRowData() {
            const data = {};
            document.querySelectorAll('.cert-manual-input[data-manual-field]').forEach((input) => {
                data[input.dataset.manualField] = input.value || '';
            });
            return data;
        }

        function certificateDate() {
            return certificateDateInput?.value || '';
        }

        function hasManualRowData() {
            return Object.values(manualRowData()).some((value) => String(value).trim() !== '');
        }

        function updatePrintState() {
            document.querySelectorAll('.cert-manual-input[data-manual-field]').forEach((input) => {
                const printValue = document.querySelector(`[data-manual-print-field="${input.dataset.manualField}"]`);
                if (printValue) {
                    printValue.textContent = input.value || '';
                }
            });

            document.querySelectorAll('.cert-data-row').forEach((row) => {
                const check = row.querySelector('.cert-row-check');
                row.classList.toggle('cert-row-print-hidden', check && !check.checked);
            });

            const manualRow = document.querySelector('.cert-manual-row');
            if (manualRow) {
                const hideManualPrintRow = !hasManualRowData() || !manualSelected();
                manualRow.classList.toggle('cert-row-print-hidden', hideManualPrintRow);
                document.querySelector('.cert-manual-print-row')?.classList.toggle('cert-row-print-hidden', hideManualPrintRow);
            }
        }

        async function saveCertificateData() {
            updatePrintState();

            if (status) {
                status.textContent = 'Saving...';
            }

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        selected_keys: selectedKeys(),
                        certificate_date: certificateDate(),
                        manual_selected: manualSelected(),
                        manual_row: manualRowData(),
                    }),
                });

                if (!response.ok) {
                    throw new Error('Save failed');
                }

                if (status) {
                    status.textContent = 'Saved';
                }
            } catch (error) {
                if (status) {
                    status.textContent = 'Not saved';
                }
            }
        }

        function scheduleSave() {
            window.clearTimeout(saveTimer);
            saveTimer = window.setTimeout(saveCertificateData, 350);
        }

        if (table) {
            table.addEventListener('change', function () {
                updatePrintState();
                scheduleSave();
            });
            table.addEventListener('input', function () {
                updatePrintState();
                scheduleSave();
            });
        }

        if (certificateDateInput) {
            certificateDateInput.addEventListener('input', scheduleSave);
            certificateDateInput.addEventListener('change', scheduleSave);
        }

        window.addEventListener('beforeprint', updatePrintState);
        updatePrintState();
    });
</script>
</body>
</html>
