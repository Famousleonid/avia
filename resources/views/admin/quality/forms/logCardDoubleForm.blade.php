<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QA Log Card</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    <style>
        :root {
            --qa-desk: #8f969d;
            --qa-frame: #b8bec4;
            --qa-paper: #fff;
            --qa-ink: #000;
            --qa-line: #000;
            --qa-print-mark: #d9d9d9;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: var(--qa-desk);
            color: var(--qa-ink);
            font-family: "Times New Roman", serif;
        }

        .qa-log-card-stage {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 8px;
            height: 100vh;
            padding: 6px;
            box-sizing: border-box;
            background: var(--qa-desk);
            overflow: hidden;
        }

        .qa-log-card-frame {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            overflow: hidden;
            border: 1px solid #5f666d;
            border-radius: 5px;
            background: transparent;
            padding: 0;
            box-shadow: none;
        }

        .qa-log-card-print {
            position: absolute;
            top: 14px;
            right: 34px;
            z-index: 4;
            font-family: Arial, sans-serif;
        }

        .qa-color-toolbar {
            position: fixed;
            left: 50%;
            bottom: 10px;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 9px;
            border: 1px solid #59626c;
            border-radius: 6px;
            background: rgba(30, 36, 42, .94);
            color: #fff;
            font-family: Arial, sans-serif;
            font-size: 12px;
            transform: translateX(-50%);
            box-shadow: 0 5px 18px rgba(0, 0, 0, .28);
        }

        .qa-color-swatch {
            box-sizing: border-box;
            flex: 0 0 auto;
            width: 22px;
            height: 22px;
            border: 1px solid rgba(255, 255, 255, .76);
            border-radius: 4px;
            padding: 0;
            appearance: none;
            cursor: pointer;
        }

        .qa-color-swatch.is-selected {
            box-shadow:
                0 0 0 2px #fff,
                0 0 0 4px rgba(13, 202, 240, .55);
        }

        .qa-color-picker {
            box-sizing: border-box;
            flex: 0 0 auto;
            width: 30px;
            height: 24px;
            border: 1px solid rgba(255, 255, 255, .76);
            border-radius: 4px;
            padding: 1px;
            background: transparent;
            cursor: pointer;
        }

        .qa-color-picker::-webkit-color-swatch-wrapper {
            padding: 0;
        }

        .qa-color-picker::-webkit-color-swatch {
            border: 0;
            border-radius: 2px;
        }

        .qa-color-clear {
            box-sizing: border-box;
            flex: 0 0 auto;
            border: 1px solid rgba(255, 255, 255, .65);
            border-radius: 4px;
            padding: 2px 7px;
            background: transparent;
            color: #fff;
            cursor: pointer;
        }

        .qa-color-picker.is-selected,
        .qa-color-clear.is-selected {
            box-shadow:
                0 0 0 2px #fff,
                0 0 0 4px rgba(13, 202, 240, .55);
        }

        .qa-color-swatch:focus-visible,
        .qa-color-picker:focus-visible,
        .qa-color-clear:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 2px;
        }

        .qa-card-side-label {
            display: block;
            width: min(172px, 58%);
            height: auto;
            margin: -12px 18px 0 auto;
            opacity: .88;
            transform: rotate(-12deg);
            mix-blend-mode: multiply;
            pointer-events: none;
        }

        .qa-log-card-frame[data-side="left"] .qa-card-side-label {
            margin-right: -108px;
        }

        .qa-log-card-frame[data-side="right"] .qa-card-side-label {
            width: min(145px, 52%);
            transform: rotate(7deg);
            margin-right: 32px;
        }

        .qa-log-card-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .qa-log-card-scroll::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .qa-log-card-pages {
            display: grid;
            gap: 10px;
        }

        .qa-log-card-page {
            width: 100%;
            min-height: calc(100vh - 12px);
            box-sizing: border-box;
            background: var(--qa-paper);
            padding: 9px;
            overflow: hidden;
        }

        .qa-log-card-top {
            display: grid;
            grid-template-columns: 1fr 1.25fr 1fr;
            gap: 8px;
            align-items: start;
            height: 86px;
        }

        .qa-log-card-top > div {
            min-height: 86px;
        }

        .qa-log-card-top > div:first-child,
        .qa-log-card-top > div:nth-child(2) {
            display: flex;
            flex-direction: column;
        }

        .qa-log-card-top > div:first-child .qa-field:first-of-type {
            margin-top: auto;
        }

        .qa-title-fields {
            margin-top: auto;
        }

        .qa-logo {
            width: min(145px, 80%);
            height: auto;
        }

        .qa-title {
            margin: 0 0 8px;
            text-align: center;
            font-size: clamp(1rem, 1.45vw, 1.45rem);
            font-weight: 700;
            line-height: 1.05;
        }

        .qa-small {
            font-size: clamp(.58rem, .72vw, .82rem);
            line-height: 1.15;
        }

        .qa-field {
            display: grid;
            grid-template-columns: max-content minmax(0, 1fr);
            gap: 6px;
            align-items: baseline;
        }

        .qa-manual-image {
            width: min(150px, 74%);
            max-height: 68px;
            object-fit: contain;
            display: block;
            margin-left: auto;
        }

        .qa-section-title {
            border: 1px solid var(--qa-line);
            border-bottom: 0;
            text-align: center;
            font-weight: 700;
            margin-top: 8px;
            padding: 4px 3px;
            font-size: clamp(.65rem, .82vw, .86rem);
        }

        .qa-section-title-grid {
            display: grid;
            grid-template-columns: 1fr max-content;
            border: 1px solid var(--qa-line);
            border-bottom: 0;
            font-weight: 700;
            font-size: clamp(.65rem, .82vw, .86rem);
        }

        .qa-section-title-grid > div {
            padding: 4px 6px;
        }

        .qa-section-title-grid > div:first-child {
            text-align: center;
        }

        .qa-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 13px;
            line-height: 1.12;
        }

        .qa-table th,
        .qa-table td {
            border: 1px solid var(--qa-line);
            padding: 3px 4px;
            vertical-align: middle;
            overflow-wrap: anywhere;
            white-space: pre-line;
        }

        .qa-table th {
            text-align: center;
            font-weight: 400;
        }

        .qa-table td {
            height: 22px;
        }

        .qa-center {
            text-align: center;
        }

        .qa-editable {
            background: transparent;
            outline: 0;
            transition: background-color .18s ease, box-shadow .18s ease;
        }

        .qa-color-cell {
            border-radius: 2px;
            transition: background-color .18s ease, box-shadow .18s ease;
        }

        .qa-editable:focus {
            background: #f3f8ff;
            box-shadow: inset 0 0 0 1px #5aa6ff;
        }

        .qa-edit-saving {
            background: #fff8df;
        }

        .qa-edit-saved {
            box-shadow: inset 0 0 0 1px #2fb36d;
        }

        .qa-edit-error {
            background: #ffb3b3;
        }

        .qa-colored-cell {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .qa-notes {
            display: grid;
            grid-template-columns: 70px minmax(0, 1fr);
            gap: 8px;
            margin-top: 9px;
            font-size: clamp(.58rem, .72vw, .8rem);
            line-height: 1.18;
        }

        .qa-note6 {
            display: grid;
            position: relative;
            grid-template-columns: max-content minmax(0, 1fr);
            gap: 5px;
            align-items: baseline;
        }

        .qa-note-print-toggle {
            width: 12px;
            height: 12px;
            margin: 0;
            position: absolute;
            left: -18px;
            top: .12em;
        }

        .qa-note6-text {
            display: inline-block;
            min-width: 100%;
        }

        .qa-header-edit {
            display: inline-block;
            min-width: 88px;
            cursor: text;
        }

        .qa-footer {
            display: grid;
            grid-template-columns: 1fr max-content 1fr;
            gap: 8px;
            margin-top: 6px;
            font-size: 12px;
        }

        .qa-footer div:nth-child(2) {
            text-align: center;
        }

        .qa-footer div:last-child {
            text-align: right;
        }

        .qa-page-notes {
            margin-top: 18px;
        }

        @media (max-width: 1200px) {
            .qa-log-card-stage {
                grid-template-columns: 1fr;
            }
        }

        @media screen {
            .qa-overhaul-life-field {
                gap: 4px;
                white-space: nowrap;
            }

            .qa-overhaul-life-field span:first-child {
                font-size: .88em;
            }

            .qa-overhaul-life-field span:last-child {
                font-size: .9em;
                line-height: 1.05;
            }
        }

        @media print {
            @page {
                size: Letter landscape;
                margin: 2mm;
            }

            body {
                height: auto;
                overflow: visible;
                background: #fff;
            }

            .qa-log-card-stage {
                display: block;
                height: auto;
                padding: 0;
                background: #fff;
                overflow: visible;
            }

            .qa-log-card-frame {
                display: none;
                position: static;
                min-height: 0;
                border: 0;
                border-radius: 0;
                padding: 0;
                overflow: visible;
                box-shadow: none;
                background: #fff;
            }

            .qa-log-card-scroll {
                display: block;
                height: auto;
                overflow: visible;
            }

            .qa-log-card-pages {
                display: block;
            }

            body.print-left #qaLogCardLeftWrap,
            body.print-right #qaLogCardRightWrap {
                display: block;
            }

            .qa-log-card-print {
                display: none;
            }

            .qa-color-toolbar {
                display: none;
            }

            .qa-card-side-label {
                display: none;
            }

            .qa-note-print-toggle {
                display: none;
            }

            .qa-note6[data-print-enabled="0"] {
                display: none;
            }

            .qa-section-title {
                margin-top: 0;
            }

            .qa-editable,
            .qa-edit-saving,
            .qa-edit-saved,
            .qa-edit-error {
                font-size: inherit;
                box-shadow: none;
            }

            .qa-colored-cell {
                background-color: var(--qa-print-mark) !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .qa-log-card-top .qa-small {
                font-size: calc(clamp(.58rem, .72vw, .82rem) * 1.2);
            }

            .qa-log-card-top .qa-title-fields .qa-small {
                font-size: calc(clamp(.58rem, .72vw, .82rem) * 1.32);
                line-height: 1.1;
            }

            .qa-title-fields {
                margin-bottom: 6px;
            }

            .qa-log-card-page {
                display: flex;
                flex-direction: column;
                width: 100%;
                height: calc(8.5in - 14mm);
                min-height: 0;
                box-sizing: border-box;
                overflow: hidden;
                padding: 2mm;
                break-after: page;
                page-break-after: always;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .qa-table td {
                height: 20px;
                padding-top: 2px;
                padding-bottom: 2px;
            }

            .qa-table {
                font-size: 14px;
            }

            .qa-table th {
                padding-top: 2px;
                padding-bottom: 2px;
            }

            .qa-notes {
                font-size: .768rem;
                line-height: 1.14;
            }

            .qa-footer {
                font-size: 12px;
                transform: translateY(1em);
            }

            .qa-log-card-page > .qa-footer {
                margin-top: auto;
            }

            .qa-log-card-page.is-last-page > .qa-page-notes {
                margin-top: 7mm;
            }

            .qa-log-card-page.is-last-page > .qa-footer {
                margin-top: 6px;
            }

            .qa-log-card-page:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
@php
    $manual = $manuals->firstWhere('id', $current_wo->unit->manual_id);
    $twoLineAssyValue = function (?string $value): string {
        $value = trim((string) $value);
        if ($value === '' || str_contains($value, "\n")) {
            return $value;
        }

        if (preg_match('/^(.+?)\s*\(([^()]*)\)$/', $value, $matches)) {
            return trim((string) $matches[1]) . "\n(" . trim((string) $matches[2]) . ')';
        }

        return $value;
    };
    $formatLogCardNumber = function ($value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $normalized = str_replace(',', '', $value);
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $normalized)) {
            return $value;
        }

        $decimals = str_contains($normalized, '.')
            ? strlen(rtrim(substr(strrchr($normalized, '.'), 1), '0'))
            : 0;

        return number_format((float) $normalized, $decimals, '.', ',');
    };
    $formatLogCardTextNumbers = function ($value) use ($formatLogCardNumber): string {
        return preg_replace_callback('/(?<![\w.,-])-?\d{4,}(?:\.\d+)?(?![\w.-])/', function ($matches) use ($formatLogCardNumber) {
            return $formatLogCardNumber($matches[0]);
        }, (string) $value) ?? (string) $value;
    };
    $formatLogCardDate = function ($value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $formatDateParts = static function (int $year, int $month, int $day): ?string {
            if (!checkdate($month, $day, $year)) {
                return null;
            }

            return \Carbon\Carbon::create($year, $month, $day)->format('d/M/Y');
        };
        $monthMap = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'sept' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        ];

        try {
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches)) {
                return $formatDateParts((int) $matches[1], (int) $matches[2], (int) $matches[3]) ?? $value;
            }

            if (preg_match('/^(\d{1,2})[\/.\-]([a-z]{3,9})[\/.\-](\d{4})$/i', $value, $matches)) {
                $month = $monthMap[strtolower($matches[2])] ?? null;

                return $month !== null
                    ? ($formatDateParts((int) $matches[3], $month, (int) $matches[1]) ?? $value)
                    : $value;
            }

            if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $value, $matches)) {
                return $formatDateParts((int) $matches[3], (int) $matches[2], (int) $matches[1]) ?? $value;
            }

            return \Carbon\Carbon::parse($value)->format('d/M/Y');
        } catch (\Throwable) {
            return $value;
        }
    };

    $buildRows = function (array $items) use ($components, $codes, $twoLineAssyValue, $formatLogCardDate) {
        return collect($items)->map(function ($item, $index) use ($components, $codes, $twoLineAssyValue, $formatLogCardDate) {
        $component = $components->firstWhere('id', $item['component_id'] ?? null);
        $hasSerial = !empty($item['serial_number']);
        $hasAssySerial = !empty($item['assy_serial_number']);
        $assyPartNumber = trim((string) ($item['assy_part_number'] ?? ''));
        if ($assyPartNumber === '') {
            $assyPartNumber = trim((string) ($component->assy_part_number ?? ''));
        }
        $hasAssyPartNumber = $assyPartNumber !== '';
        $reasonCode = $codes->firstWhere('id', $item['reason'] ?? null);
        $basePartNumber = trim((string) ($item['part_number'] ?? ($component->part_number ?? '')));
        $baseSerialNumber = trim((string) ($item['serial_number'] ?? ''));

        if ($hasAssyPartNumber && !$hasAssySerial) {
            $partNumber = $basePartNumber !== ''
                ? $basePartNumber . "\n(" . $assyPartNumber . ')'
                : $assyPartNumber;
            $serialNumber = $baseSerialNumber !== '' ? $baseSerialNumber . "\n\u{00A0}" : '';
        } elseif ($hasAssySerial && !$hasSerial) {
            $partNumber = $assyPartNumber;
            $serialNumber = $item['assy_serial_number'] ?? '';
        } elseif ($hasAssySerial && $hasSerial) {
            $partNumber = $basePartNumber;
            if (trim((string) $assyPartNumber) !== '') {
                $partNumber .= "\n(" . trim((string) $assyPartNumber) . ')';
            }

            $serialNumber = $baseSerialNumber;
            if (trim((string) ($item['assy_serial_number'] ?? '')) !== '') {
                $serialNumber .= "\n(" . trim((string) ($item['assy_serial_number'] ?? '')) . ')';
            }
        } else {
            $partNumber = $basePartNumber;
            $serialNumber = $baseSerialNumber;
        }

        $qaPartNumber = trim((string) ($item['qa_part_number'] ?? ''));
        $qaSerialNumber = trim((string) ($item['qa_serial_number'] ?? ''));

        return [
            'source_index' => $index,
            'description' => $item['qa_description'] ?? ($item['name'] ?? $item['description'] ?? ($component->name ?? '')),
            'part_number' => $twoLineAssyValue($qaPartNumber !== '' ? $qaPartNumber : $partNumber),
            'serial_number' => $twoLineAssyValue($qaSerialNumber !== '' ? $qaSerialNumber : $serialNumber),
            'fit_date' => $formatLogCardDate($item['qa_fit_date'] ?? ($item['fit_date'] ?? '')),
            'fit_cso' => $item['qa_fit_cso'] ?? ($item['fit_cso'] ?? ''),
            'fit_csn' => $item['qa_fit_csn'] ?? ($item['fit_csn'] ?? ''),
            'removed_date' => $formatLogCardDate($item['qa_removed_date'] ?? ($item['removed_date'] ?? '')),
            'removed_cso' => $item['qa_removed_cso'] ?? ($item['removed_cso'] ?? ''),
            'removed_csn' => $item['qa_removed_csn'] ?? ($item['removed_csn'] ?? ''),
            'reason' => $item['qa_reason'] ?? ($reasonCode?->name ?? ($item['reason'] ?? '')),
            'cell_colors' => is_array($item['qa_cell_colors'] ?? null) ? $item['qa_cell_colors'] : [],
        ];
        })->values();
    };

    $aircraftFields = [
        'fit_date',
        'fit_cso',
        'fit_csn',
        'fit_cycles',
        'removed_date',
        'removed_cso',
        'removed_csn',
        'removed_cycles',
        'reason',
    ];

    $aircraftNumberFields = [
        'fit_cso',
        'fit_csn',
        'fit_cycles',
        'removed_cso',
        'removed_csn',
        'removed_cycles',
    ];

    $aircraftDateFields = [
        'fit_date',
        'removed_date',
    ];

    $aircraftRowsFor = function (array $items) use ($aircraftFields, $aircraftNumberFields, $aircraftDateFields, $formatLogCardNumber, $formatLogCardDate) {
        $stored = $items[0]['qa_aircraft_records'] ?? [];
        return collect(range(0, 5))->map(function ($index) use ($stored, $aircraftFields, $aircraftNumberFields, $aircraftDateFields, $formatLogCardNumber, $formatLogCardDate) {
            $row = [];
            foreach ($aircraftFields as $field) {
                $value = $stored[$index][$field] ?? '';
                if (in_array($field, $aircraftNumberFields, true)) {
                    $row[$field] = $formatLogCardNumber($value);
                    continue;
                }

                $row[$field] = in_array($field, $aircraftDateFields, true)
                    ? $formatLogCardDate($value)
                    : $value;
            }
            return $row;
        });
    };

    $aircraftColorsFor = function (array $items) {
        $stored = $items[0]['qa_aircraft_cell_colors'] ?? [];
        return is_array($stored) ? $stored : [];
    };

    $headerColorsFor = function (array $items) {
        $stored = $items[0]['qa_header_cell_colors'] ?? [];
        return is_array($stored) ? $stored : [];
    };

    $cellStyle = function (?string $color) {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $color)
            ? 'background-color: ' . strtolower($color) . ';'
            : '';
    };

    $primaryCellFields = [
        'description' => false,
        'part_number' => true,
        'serial_number' => true,
        'fit_date' => true,
        'fit_cso' => true,
        'fit_csn' => true,
        'removed_date' => true,
        'removed_cso' => true,
        'removed_csn' => true,
        'reason' => true,
    ];

    $primaryRowUnits = function (array $row) {
        $lineCapacities = [
            'description' => 25,
            'part_number' => 18,
            'serial_number' => 18,
            'fit_date' => 12,
            'fit_cso' => 12,
            'fit_csn' => 12,
            'removed_date' => 12,
            'removed_cso' => 12,
            'removed_csn' => 12,
            'reason' => 20,
        ];

        $lines = collect($lineCapacities)->map(function ($capacity, $field) use ($row) {
            $value = trim((string) ($row[$field] ?? ''));
            $lines = preg_split('/\R/', $value) ?: [''];

            return collect($lines)
                ->map(fn (string $line): int => max(1, (int) ceil(mb_strlen(trim($line)) / $capacity)))
                ->sum();
        })->max();

        return max(1, min(4, $lines));
    };

    $paginatePrimaryRows = function ($rows) use ($primaryRowUnits) {
        $pages = collect();
        $pageRows = collect();
        $usedUnits = 0;
        $maxUnits = 14;

        foreach ($rows as $row) {
            $units = $primaryRowUnits($row);
            if ($pageRows->isNotEmpty() && $usedUnits + $units > $maxUnits) {
                $pages->push([
                    'rows' => $pageRows,
                    'blank_count' => max(0, $maxUnits - $usedUnits),
                ]);
                $pageRows = collect();
                $usedUnits = 0;
            }

            $pageRows->push($row);
            $usedUnits += $units;
        }

        $pages->push([
            'rows' => $pageRows,
            'blank_count' => max(0, $maxUnits - $usedUnits),
        ]);

        return $pages;
    };

    $cardSides = [
        'left' => [
            'label' => 'Left',
            'heading' => 'As Received',
            'stamp' => asset('img/quality/qa-stamp-as-received.svg'),
            'rows' => $buildRows($componentData),
            'aircraft_rows' => $aircraftRowsFor($componentData),
            'aircraft_colors' => $aircraftColorsFor($componentData),
            'header_colors' => $headerColorsFor($componentData),
            'note6_text' => $componentData[0]['qa_note6_text'] ?? "The Log Card was created refer to client's provided documents.",
            'note6_enabled' => $componentData[0]['qa_note6_enabled'] ?? false,
            'header_part_number' => $current_wo->unit->part_number,
        ],
        'right' => [
            'label' => 'Right',
            'heading' => 'As dispatched',
            'stamp' => asset('img/quality/qa-stamp-as-dispatched.svg'),
            'rows' => $buildRows($componentDataOut),
            'aircraft_rows' => $aircraftRowsFor($componentDataOut),
            'aircraft_colors' => $aircraftColorsFor($componentDataOut),
            'header_colors' => $headerColorsFor($componentDataOut),
            'note6_text' => $componentDataOut[0]['qa_note6_text'] ?? "The Log Card was created refer to client's provided documents.",
            'note6_enabled' => $componentDataOut[0]['qa_note6_enabled'] ?? false,
            'header_part_number' => $componentDataOut[0]['qa_header_part_number'] ?? $current_wo->unit->part_number,
        ],
    ];
@endphp

<div class="qa-color-toolbar" aria-label="Cell background color tools">
    <span>Ctrl + click</span>
    @foreach(['#fff3bf', '#d3f9d8', '#d0ebff', '#ffd8d8'] as $color)
        <button type="button"
                class="qa-color-swatch"
                data-qa-color="{{ $color }}"
                style="background-color: {{ $color }}"
                title="{{ $color }}"></button>
    @endforeach
    <input type="color" class="qa-color-picker" value="#fff3bf" title="Custom color">
    <button type="button" class="qa-color-clear" data-qa-clear-color>Clear</button>
</div>

<main class="qa-log-card-stage">
@foreach($cardSides as $side => $card)
    @php
        $label = $card['label'];
        $pages = $paginatePrimaryRows($card['rows']);
        $totalPages = $pages->count();
    @endphp
    <section id="qaLogCard{{ $label }}Wrap" class="qa-log-card-frame" data-side="{{ $side }}">
        <button class="btn btn-outline-info btn-sm qa-log-card-print" type="button" data-print-side="{{ $side }}">Print {{ $label }}</button>
        <div class="qa-log-card-scroll">
    <div class="qa-log-card-pages">
        @foreach($pages as $pageIndex => $page)
            @php
                $pageRows = $page['rows'];
                $blankCount = $page['blank_count'];
                $isFirstPage = $loop->first;
                $isLastPage = $loop->last;
            @endphp
            <article class="qa-log-card-page{{ $isFirstPage ? ' is-first-page' : '' }}{{ $isLastPage ? ' is-last-page' : '' }}">
                <header class="qa-log-card-top">
                    <div>
                        <img class="qa-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo">
                        <div class="qa-small qa-field"><span>UNIT:</span><span>{{ $manual->title ?? '' }}</span></div>
                        <div class="qa-small qa-field qa-overhaul-life-field"><span>AUTHORIZED OVERHAUL LIFE:</span><span>{{ $formatLogCardTextNumbers($manual->ovh_life ?? '') }}</span></div>
                    </div>
                    <div>
                        <h1 class="qa-title">LANDING GEAR LOG CARD</h1>
                        <div class="qa-title-fields">
                            <div class="qa-small qa-field">
                                <span>PART NO:</span>
                                @php
                                    $headerPartNumberColor = $card['header_colors']['part_number'] ?? '';
                                    $headerPartNumberStyle = $cellStyle($headerPartNumberColor);
                                @endphp
                                @if($side === 'right')
                                    <span class="qa-editable qa-header-edit qa-color-cell{{ $headerPartNumberStyle ? ' qa-colored-cell' : '' }}"
                                          style="{{ $headerPartNumberStyle }}"
                                          contenteditable="true"
                                          data-qa-edit
                                          data-qa-color-cell
                                          data-side="{{ $side }}"
                                          data-section="header"
                                          data-row="0"
                                          data-field="part_number">{{ $card['header_part_number'] }}</span>
                                @else
                                    <span class="qa-header-edit qa-color-cell{{ $headerPartNumberStyle ? ' qa-colored-cell' : '' }}"
                                          style="{{ $headerPartNumberStyle }}"
                                          data-qa-color-cell
                                          data-side="{{ $side }}"
                                          data-section="header"
                                          data-row="0"
                                          data-field="part_number">{{ $card['header_part_number'] }}</span>
                                @endif
                            </div>
                            <div class="qa-small qa-field"><span>SERIAL NO:</span><span>{{ $current_wo->serial_number }}</span></div>
                        </div>
                        <img class="qa-card-side-label" src="{{ $card['stamp'] }}" alt="{{ $card['heading'] }} stamp">
                    </div>
                    <div>
                        @if($manual && $manual->hasMedia('manuals_log'))
                            <img class="qa-manual-image" src="{{ $manual->getFirstMediaThumbnailUrl('manuals_log') }}" alt="Image Log">
                        @endif
                    </div>
                </header>

                @if($isFirstPage)
                    <div class="qa-section-title">AIRCRAFT INSTALLATION RECORDS</div>
                    <table class="qa-table">
                        <colgroup>
                            <col style="width: 14%">
                            <col span="4" style="width: 9%">
                            <col span="4" style="width: 9%">
                            <col style="width: 14%">
                        </colgroup>
                        <thead>
                        <tr>
                            <th rowspan="2">Aircraft Reg./Con.No.</th>
                            <th colspan="4">FITTED TO AIRCRAFT</th>
                            <th colspan="4">REMOVED FROM AIRCRAFT</th>
                            <th rowspan="2">REASON FOR REMOVAL</th>
                        </tr>
                        <tr>
                            <th>DATE</th><th>C.S.O.</th><th>C.S.N.</th><th>A/F CYCLES</th>
                            <th>DATE</th><th>C.S.O.</th><th>C.S.N.</th><th>A/F CYCLES</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($card['aircraft_rows'] as $aircraftIndex => $aircraftRow)
                            <tr>
                                <td></td>
                                @foreach(['fit_date', 'fit_cso', 'fit_csn', 'fit_cycles', 'removed_date', 'removed_cso', 'removed_csn', 'removed_cycles', 'reason'] as $field)
                                    @php
                                        $cellColor = $card['aircraft_colors'][$aircraftIndex][$field] ?? '';
                                        $style = $cellStyle($cellColor);
                                    @endphp
                                    <td class="qa-center qa-editable{{ $style ? ' qa-colored-cell' : '' }}"
                                        style="{{ $style }}"
                                        contenteditable="true"
                                        data-qa-edit
                                        data-qa-color-cell
                                        data-side="{{ $side }}"
                                        data-section="aircraft"
                                        data-row="{{ $aircraftIndex }}"
                                        data-field="{{ $field }}">{{ $aircraftRow[$field] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif

                <div class="qa-section-title-grid">
                    <div>PRIMARY MEMBER RECORDS</div>
                    <div>W{{ $current_wo->number }}</div>
                </div>
                <table class="qa-table">
                    <colgroup>
                        <col style="width: 18%">
                        <col style="width: 12%">
                        <col style="width: 12%">
                        <col span="3" style="width: 8%">
                        <col span="3" style="width: 8%">
                        <col style="width: 14%">
                    </colgroup>
                    <thead>
                    <tr>
                        <th rowspan="2">DESCRIPTION</th>
                        <th rowspan="2">PART NO.</th>
                        <th rowspan="2">SERIAL NO.</th>
                        <th colspan="3">FITTED TO GEAR</th>
                        <th colspan="3">REMOVED FROM GEAR</th>
                        <th rowspan="2">REASON FOR REMOVAL</th>
                    </tr>
                    <tr>
                        <th>DATE</th><th>C.S.O.</th><th>C.S.N.</th>
                        <th>DATE</th><th>C.S.O.</th><th>C.S.N.</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($pageRows as $row)
                        <tr>
                            @foreach($primaryCellFields as $field => $center)
                                @php
                                    $cellColor = $row['cell_colors'][$field] ?? '';
                                    $style = $cellStyle($cellColor);
                                @endphp
                                <td class="{{ $center ? 'qa-center ' : '' }}qa-editable{{ $style ? ' qa-colored-cell' : '' }}"
                                    style="{{ $style }}"
                                    contenteditable="true"
                                    data-qa-edit
                                    data-qa-color-cell
                                    data-side="{{ $side }}"
                                    data-section="primary"
                                    data-row="{{ $row['source_index'] }}"
                                    data-field="{{ $field }}">{{ $row[$field] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @for($i = 0; $i < $blankCount; $i++)
                        <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    @endfor
                    </tbody>
                </table>

                @if($isLastPage)
                    <section class="qa-notes qa-page-notes">
                        <div>NOTES:</div>
                        <div>
                            <div>1. For ultimate lives and/or inspection requirements, refer to Aircraft Airworthiness Data and to the appropriate {{ $manual->reg_sb ?? '' }} Service Bulletin.</div>
                            <div>2. It is the Operator's responsibility to ensure these records are fully and accurately maintained.</div>
                            <div>3. Lives of primary members shall be maintained. Failure to comply may result in premature scrap.</div>
                            <div>4. Should a primary member be removed from the unit it must be suitably tagged to indicate consumed life.</div>
                            <div>5. If the Part No. is changed a new Log Card must be completed, transferring relevant information from the previous Card.</div>
                            <div class="qa-note6" data-print-enabled="{{ $card['note6_enabled'] ? '1' : '0' }}">
                                <input class="qa-note-print-toggle"
                                       type="checkbox"
                                       data-qa-note-toggle
                                       data-side="{{ $side }}"
                                       @checked($card['note6_enabled'])>
                                <span>6.</span>
                                <span>
                                    <span class="qa-editable qa-note6-text"
                                          contenteditable="true"
                                          data-qa-edit
                                          data-side="{{ $side }}"
                                          data-section="note"
                                          data-row="0"
                                          data-field="note6_text">{{ $card['note6_text'] }}</span>
                                </span>
                            </div>
                        </div>
                    </section>
                @endif

                <footer class="qa-footer">
                    <div>Form #008</div>
                    <div>{{ $pageIndex + 1 }} of {{ $totalPages }}</div>
                    <div>Rev#0, 15/Dec/2012</div>
                </footer>
            </article>
        @endforeach
    </div>
        </div>
    </section>
@endforeach
</main>

<script>
    (() => {
        const saveUrl = @json(route('quality.forms.log_card.update', ['workorder' => $current_wo->id]));
        const csrfToken = @json(csrf_token());
        const timers = new WeakMap();
        const swatches = Array.from(document.querySelectorAll('.qa-color-swatch[data-qa-color]'));
        const customColor = document.querySelector('.qa-color-picker');
        const clearColorButton = document.querySelector('[data-qa-clear-color]');
        const colorControls = [...swatches, customColor, clearColorButton].filter(Boolean);
        let selectedColor = swatches[0]?.dataset.qaColor || '#fff3bf';

        const setSelectedColor = (color, selectedControl = null) => {
            selectedColor = color;
            const matchingPreset = swatches.find((swatch) => swatch.dataset.qaColor === color) || null;
            const activeControl = selectedControl || (color === '' ? clearColorButton : matchingPreset || customColor);
            colorControls.forEach((control) => {
                control.classList.toggle('is-selected', control === activeControl);
            });
            if (customColor && color) {
                customColor.value = color;
            }
        };

        swatches.forEach((swatch) => {
            swatch.addEventListener('click', () => setSelectedColor(swatch.dataset.qaColor || '', swatch));
        });

        customColor?.addEventListener('input', () => setSelectedColor(customColor.value, customColor));
        clearColorButton?.addEventListener('click', () => setSelectedColor('', clearColorButton));
        setSelectedColor(selectedColor);

        document.querySelectorAll('[data-print-side]').forEach((button) => {
            button.addEventListener('click', () => {
                const side = button.dataset.printSide === 'right' ? 'right' : 'left';
                document.body.classList.remove('print-left', 'print-right');
                document.body.classList.add(`print-${side}`);
                window.print();
            });
        });

        window.addEventListener('afterprint', () => {
            document.body.classList.remove('print-left', 'print-right');
        });

        const normalizeCellValue = (cell) => cell.innerText.replace(/\s+/g, ' ').trim();
        const aircraftNumberFields = new Set([
            'fit_cso',
            'fit_csn',
            'fit_cycles',
            'removed_cso',
            'removed_csn',
            'removed_cycles',
        ]);
        const dateFields = new Set([
            'fit_date',
            'removed_date',
        ]);
        const isAircraftNumberCell = (cell) => (
            cell.dataset.section === 'aircraft'
            && aircraftNumberFields.has(cell.dataset.field || '')
        );
        const isLogCardDateCell = (cell) => dateFields.has(cell.dataset.field || '');
        const formatLogCardNumber = (value) => {
            const text = String(value || '').replace(/,/g, '').trim();
            if (!/^-?\d+(?:\.\d+)?$/.test(text)) {
                return String(value || '').trim();
            }

            const [, decimal = ''] = text.split('.');
            return Number(text).toLocaleString('en-US', {
                minimumFractionDigits: decimal.replace(/0+$/, '').length,
                maximumFractionDigits: decimal.length,
            });
        };
        const logCardMonthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const logCardMonthMap = new Map([
            ['jan', 1], ['january', 1],
            ['feb', 2], ['february', 2],
            ['mar', 3], ['march', 3],
            ['apr', 4], ['april', 4],
            ['may', 5],
            ['jun', 6], ['june', 6],
            ['jul', 7], ['july', 7],
            ['aug', 8], ['august', 8],
            ['sep', 9], ['sept', 9], ['september', 9],
            ['oct', 10], ['october', 10],
            ['nov', 11], ['november', 11],
            ['dec', 12], ['december', 12],
        ]);
        const formatDateParts = (year, month, day) => {
            const date = new Date(Date.UTC(year, month - 1, day));
            if (
                date.getUTCFullYear() !== year
                || date.getUTCMonth() !== month - 1
                || date.getUTCDate() !== day
            ) {
                return null;
            }

            return `${String(day).padStart(2, '0')}/${logCardMonthNames[month - 1]}/${year}`;
        };
        const formatLogCardDate = (value) => {
            const text = String(value || '').replace(/\s+/g, ' ').trim();
            let match = text.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
            if (match) {
                return formatDateParts(Number(match[1]), Number(match[2]), Number(match[3])) || text;
            }

            match = text.match(/^(\d{1,2})[\/.\-]([A-Za-z]{3,9})[\/.\-](\d{4})$/);
            if (match) {
                const month = logCardMonthMap.get(match[2].toLowerCase());
                return month ? (formatDateParts(Number(match[3]), month, Number(match[1])) || text) : text;
            }

            match = text.match(/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/);
            if (match) {
                return formatDateParts(Number(match[3]), Number(match[2]), Number(match[1])) || text;
            }

            return text;
        };
        const normalizeEditableCell = (cell) => {
            if (isLogCardDateCell(cell)) {
                const value = normalizeCellValue(cell);
                const formatted = formatLogCardDate(value);
                if (formatted !== value) {
                    cell.innerText = formatted;
                }
                return;
            }

            if (!isAircraftNumberCell(cell)) {
                return;
            }

            const formatted = formatLogCardNumber(normalizeCellValue(cell));
            if (formatted !== normalizeCellValue(cell)) {
                cell.innerText = formatted;
            }
        };
        const cssEscape = (value) => {
            if (window.CSS && typeof window.CSS.escape === 'function') {
                return window.CSS.escape(String(value));
            }

            return String(value).replace(/["\\]/g, '\\$&');
        };

        const matchingRightCellFor = (cell) => {
            if (cell.dataset.side !== 'left' || cell.dataset.section === 'note') {
                return null;
            }

            return document.querySelector(
                `[data-qa-color-cell][data-side="right"][data-section="${cssEscape(cell.dataset.section || '')}"]` +
                `[data-row="${cssEscape(cell.dataset.row || '0')}"][data-field="${cssEscape(cell.dataset.field || '')}"]`
            );
        };

        const saveCell = async (cell) => {
            normalizeEditableCell(cell);
            const value = normalizeCellValue(cell);

            if (value === (cell.dataset.originalValue ?? '')) {
                cell.classList.remove('qa-edit-saving', 'qa-edit-saved', 'qa-edit-error');
                return;
            }

            cell.classList.remove('qa-edit-error', 'qa-edit-saved');
            cell.classList.add('qa-edit-saving');

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        side: cell.dataset.side,
                        section: cell.dataset.section,
                        row: Number(cell.dataset.row || 0),
                        field: cell.dataset.field,
                        value,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Save failed');
                }

                cell.classList.remove('qa-edit-saving');
                cell.classList.add('qa-edit-saved');
                cell.dataset.originalValue = value;
                window.setTimeout(() => {
                    cell.classList.remove('qa-edit-saved');
                }, 500);
            } catch (error) {
                cell.classList.remove('qa-edit-saving');
                cell.classList.add('qa-edit-error');
            }
        };

        const saveCellBackground = async (cell, color) => {
            cell.style.backgroundColor = color || '';
            cell.classList.toggle('qa-colored-cell', Boolean(color));
            cell.classList.remove('qa-edit-error', 'qa-edit-saved');
            cell.classList.add('qa-edit-saving');

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        side: cell.dataset.side,
                        section: cell.dataset.section,
                        row: Number(cell.dataset.row || 0),
                        field: cell.dataset.field,
                        style: 'background',
                        value: color || '',
                    }),
                });

                if (!response.ok) {
                    throw new Error('Save failed');
                }

                cell.classList.remove('qa-edit-saving');
                cell.classList.add('qa-edit-saved');
                window.setTimeout(() => {
                    cell.classList.remove('qa-edit-saved');
                }, 500);
            } catch (error) {
                cell.classList.remove('qa-edit-saving');
                cell.classList.add('qa-edit-error');
            }
        };

        const colorableCells = Array.from(document.querySelectorAll('[data-qa-color-cell]'));
        colorableCells.forEach((cell) => {
            cell.addEventListener('click', (event) => {
                if (!event.ctrlKey || event.button !== 0) {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(timers.get(cell));
                saveCellBackground(cell, selectedColor);
                if (selectedColor) {
                    const rightCell = matchingRightCellFor(cell);
                    if (rightCell) {
                        window.clearTimeout(timers.get(rightCell));
                        saveCellBackground(rightCell, selectedColor);
                    }
                }
                cell.blur();
            });
        });

        document.querySelectorAll('[data-qa-edit]').forEach((cell) => {
            cell.dataset.originalValue = normalizeCellValue(cell);

            cell.addEventListener('input', () => {
                window.clearTimeout(timers.get(cell));
                timers.set(cell, window.setTimeout(() => saveCell(cell), 650));
            });

            cell.addEventListener('blur', () => {
                window.clearTimeout(timers.get(cell));
                saveCell(cell);
            });

            cell.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    cell.blur();
                }
            });

            cell.addEventListener('paste', (event) => {
                event.preventDefault();
                const text = event.clipboardData?.getData('text/plain') ?? '';
                document.execCommand('insertText', false, text.replace(/\s+/g, ' ').trim());
            });
        });

        document.querySelectorAll('[data-qa-note-toggle]').forEach((checkbox) => {
            checkbox.addEventListener('change', async () => {
                const noteRow = checkbox.closest('.qa-note6');
                if (noteRow) {
                    noteRow.dataset.printEnabled = checkbox.checked ? '1' : '0';
                }

                try {
                    const response = await fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            side: checkbox.dataset.side,
                            section: 'note',
                            row: 0,
                            field: 'note6_enabled',
                            value: checkbox.checked ? '1' : '0',
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Save failed');
                    }
                } catch (error) {
                    checkbox.checked = !checkbox.checked;
                    if (noteRow) {
                        noteRow.dataset.printEnabled = checkbox.checked ? '1' : '0';
                    }
                }
            });
        });
    })();
</script>
</body>
</html>
