<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $blankIcon = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22180%22 height=%22180%22 viewBox=%220 0 180 180%22%3E%3C/svg%3E';
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050505">
    <link rel="icon" href="{{ $blankIcon }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ $blankIcon }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ $blankIcon }}">
    <meta property="og:image" content="{{ $blankIcon }}">
    <meta name="twitter:image" content="{{ $blankIcon }}">
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

        .print-mark-warning {
            background: #fff1f2;
            border-bottom: 1px solid #fecdd3;
            border-top: 1px solid #fecdd3;
            color: #dc2626;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.3;
            padding: 16px 22px;
            text-align: center;
            text-transform: uppercase;
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

        @if(!empty($requirementWarnings))
            <section class="print-mark-warning" role="alert">
                Missing required {{ implode(' and ', $requirementWarnings) }}
            </section>
        @endif

        <section class="print-mark-note">
            This public page displays the data registered for the printed QR mark.
        </section>
    </main>
</body>
</html>
