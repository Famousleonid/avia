<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Print Mark</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            align-items: stretch;
            background: #f4f6f8;
            color: #111827;
            display: flex;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
            padding: 10px;
        }

        .print-mark-page {
            background: #fff;
            border: 1px solid #d7dee7;
            border-radius: 10px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .10);
            max-width: 520px;
            overflow: hidden;
            width: 100%;
        }

        .print-mark-head {
            align-items: center;
            background: #050505;
            color: #fff;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 14px 18px;
        }

        .print-mark-logo {
            display: block;
            filter: brightness(0) invert(1);
            height: 32px;
            max-width: 210px;
            object-fit: contain;
        }

        .print-mark-info {
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: .04em;
            line-height: 1;
        }

        .print-mark-body {
            padding: 22px;
        }

        .print-mark-row {
            border-bottom: 1px solid #e5e9ef;
            display: block;
            padding: 18px 0;
        }

        .print-mark-row:first-child {
            padding-top: 0;
        }

        .print-mark-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .print-mark-label {
            color: #64748b;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .print-mark-value {
            color: #0f172a;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.08;
            margin-top: 6px;
            overflow-wrap: anywhere;
        }

        .print-mark-note {
            background: #f8fafc;
            border-top: 1px solid #e5e9ef;
            color: #475569;
            font-size: 15px;
            line-height: 1.35;
            padding: 16px 22px;
        }

        @media (min-width: 560px) {
            body {
                align-items: center;
                padding: 18px;
            }

            .print-mark-value {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <main class="print-mark-page" aria-label="System print mark information">
        <section class="print-mark-head">
            <img class="print-mark-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Aviatechnik">
            <div class="print-mark-info">INFO</div>
        </section>

        <section class="print-mark-body">
            <div class="print-mark-row">
                <div class="print-mark-label">Work Order</div>
                <div class="print-mark-value">{{ $workorder }}</div>
            </div>
            <div class="print-mark-row">
                <div class="print-mark-label">Form</div>
                <div class="print-mark-value">{{ $formName }}</div>
            </div>
            <div class="print-mark-row">
                <div class="print-mark-label">Printed By</div>
                <div class="print-mark-value">{{ $printedBy }}</div>
            </div>
            <div class="print-mark-row">
                <div class="print-mark-label">Printed Date</div>
                <div class="print-mark-value">{{ $printedDate }}</div>
            </div>
        </section>

        <section class="print-mark-note">
            This public page only displays the data encoded in the printed QR mark.
        </section>
    </main>
</body>
</html>
