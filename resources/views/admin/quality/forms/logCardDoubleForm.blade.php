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
            width: 22px;
            height: 22px;
            border: 1px solid rgba(255, 255, 255, .76);
            border-radius: 4px;
            padding: 0;
            appearance: none;
            cursor: pointer;
        }

        .qa-color-swatch.is-selected {
            outline: 2px solid #fff;
            outline-offset: 2px;
        }

        .qa-color-picker {
            width: 30px;
            height: 24px;
            border: 1px solid rgba(255, 255, 255, .76);
            border-radius: 4px;
            padding: 1px;
            background: transparent;
            cursor: pointer;
        }

        .qa-color-clear {
            border: 1px solid rgba(255, 255, 255, .65);
            border-radius: 4px;
            padding: 2px 7px;
            background: transparent;
            color: #fff;
            cursor: pointer;
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
            font-size: clamp(.55rem, .68vw, .78rem);
            line-height: 1.12;
        }

        .qa-table th,
        .qa-table td {
            border: 1px solid var(--qa-line);
            padding: 3px 4px;
            vertical-align: middle;
            overflow-wrap: anywhere;
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
            cursor: text;
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

        .qa-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-top: 6px;
            font-size: clamp(.55rem, .7vw, .76rem);
        }

        .qa-footer div:last-child {
            text-align: right;
        }

        @media (max-width: 1200px) {
            .qa-log-card-stage {
                grid-template-columns: 1fr;
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
                background: transparent !important;
                font-size: calc(1em + 2px);
                box-shadow: none;
            }

            .qa-colored-cell {
                background-color: transparent !important;
                print-color-adjust: economy;
                -webkit-print-color-adjust: economy;
            }

            .qa-log-card-page {
                display: block;
                width: 100%;
                min-height: auto;
                overflow: visible;
                padding: 2mm;
                break-after: page;
                page-break-after: always;
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
    $buildRows = function (array $items) use ($components, $codes) {
        return collect($items)->map(function ($item, $index) use ($components, $codes) {
        $component = $components->firstWhere('id', $item['component_id'] ?? null);
        $hasSerial = !empty($item['serial_number']);
        $hasAssySerial = !empty($item['assy_serial_number']);
        $assyPartNumber = $item['assy_part_number'] ?? ($component->assy_part_number ?? '');
        $reasonCode = $codes->firstWhere('id', $item['reason'] ?? null);

        if ($hasAssySerial && !$hasSerial) {
            $partNumber = $assyPartNumber;
            $serialNumber = $item['assy_serial_number'] ?? '';
        } elseif ($hasAssySerial && $hasSerial) {
            $partNumber = trim(($component->part_number ?? '') . ' (' . $assyPartNumber . ')');
            $serialNumber = trim(($item['serial_number'] ?? '') . ' (' . ($item['assy_serial_number'] ?? '') . ')');
        } else {
            $partNumber = $component->part_number ?? '';
            $serialNumber = $item['serial_number'] ?? '';
        }

        return [
            'source_index' => $index,
            'description' => $item['qa_description'] ?? ($item['name'] ?? $item['description'] ?? ($component->name ?? '')),
            'part_number' => $item['qa_part_number'] ?? ($item['part_number'] ?? $partNumber),
            'serial_number' => $item['qa_serial_number'] ?? $serialNumber,
            'fit_date' => $item['qa_fit_date'] ?? ($item['fit_date'] ?? ''),
            'fit_cso' => $item['qa_fit_cso'] ?? ($item['fit_cso'] ?? ''),
            'fit_csn' => $item['qa_fit_csn'] ?? ($item['fit_csn'] ?? ''),
            'removed_date' => $item['qa_removed_date'] ?? ($item['removed_date'] ?? ''),
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

    $aircraftRowsFor = function (array $items) use ($aircraftFields) {
        $stored = $items[0]['qa_aircraft_records'] ?? [];
        return collect(range(0, 5))->map(function ($index) use ($stored, $aircraftFields) {
            $row = [];
            foreach ($aircraftFields as $field) {
                $row[$field] = $stored[$index][$field] ?? '';
            }
            return $row;
        });
    };

    $aircraftColorsFor = function (array $items) {
        $stored = $items[0]['qa_aircraft_cell_colors'] ?? [];
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
            $length = mb_strlen(trim((string) ($row[$field] ?? '')));
            return max(1, (int) ceil($length / $capacity));
        })->max();

        return max(1, min(4, $lines));
    };

    $paginatePrimaryRows = function ($rows) use ($primaryRowUnits) {
        $pages = collect();
        $pageRows = collect();
        $usedUnits = 0;
        $maxUnits = 12;

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
            'note6_text' => $componentData[0]['qa_note6_text'] ?? "The Log Card was created refer to client's provided documents.",
            'note6_enabled' => $componentData[0]['qa_note6_enabled'] ?? true,
        ],
        'right' => [
            'label' => 'Right',
            'heading' => 'As dispached',
            'stamp' => asset('img/quality/qa-stamp-as-dispach.svg'),
            'rows' => $buildRows($componentDataOut),
            'aircraft_rows' => $aircraftRowsFor($componentDataOut),
            'aircraft_colors' => $aircraftColorsFor($componentDataOut),
            'note6_text' => $componentDataOut[0]['qa_note6_text'] ?? "The Log Card was created refer to client's provided documents.",
            'note6_enabled' => $componentDataOut[0]['qa_note6_enabled'] ?? true,
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
    <button type="button" class="qa-color-clear" data-qa-color="">Clear</button>
</div>

<main class="qa-log-card-stage">
@foreach($cardSides as $side => $card)
    @php
        $label = $card['label'];
        $pages = $paginatePrimaryRows($card['rows']);
    @endphp
    <section id="qaLogCard{{ $label }}Wrap" class="qa-log-card-frame" data-side="{{ $side }}">
        <button class="btn btn-outline-info btn-sm qa-log-card-print" type="button" data-print-side="{{ $side }}">Print {{ $label }}</button>
        <div class="qa-log-card-scroll">
    <div class="qa-log-card-pages">
        @foreach($pages as $pageIndex => $page)
            @php
                $pageRows = $page['rows'];
                $blankCount = $page['blank_count'];
            @endphp
            <article class="qa-log-card-page">
                <header class="qa-log-card-top">
                    <div>
                        <img class="qa-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo">
                        <div class="qa-small qa-field"><span>UNIT:</span><span>{{ $manual->title ?? '' }}</span></div>
                        <div class="qa-small qa-field"><span>AUTHORIZED OVERHAUL LIFE:</span><span>{{ $manual->ovh_life ?? '' }}</span></div>
                    </div>
                    <div>
                        <h1 class="qa-title">LANDING GEAR LOG CARD</h1>
                        <div class="qa-title-fields">
                            <div class="qa-small qa-field"><span>PART NO:</span><span>{{ $current_wo->unit->part_number }}</span></div>
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
                                    data-side="{{ $side }}"
                                    data-section="aircraft"
                                    data-row="{{ $aircraftIndex }}"
                                    data-field="{{ $field }}">{{ $aircraftRow[$field] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>

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

                <section class="qa-notes">
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

                <footer class="qa-footer">
                    <div>Form #008</div>
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
        const swatches = Array.from(document.querySelectorAll('[data-qa-color]'));
        const customColor = document.querySelector('.qa-color-picker');
        let selectedColor = swatches[0]?.dataset.qaColor || '#fff3bf';

        const setSelectedColor = (color) => {
            selectedColor = color;
            swatches.forEach((swatch) => {
                swatch.classList.toggle('is-selected', swatch.dataset.qaColor === color);
            });
            if (customColor && color) {
                customColor.value = color;
            }
        };

        swatches.forEach((swatch) => {
            swatch.addEventListener('click', () => setSelectedColor(swatch.dataset.qaColor || ''));
        });

        customColor?.addEventListener('input', () => setSelectedColor(customColor.value));
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

        const saveCell = async (cell) => {
            const value = cell.innerText.replace(/\s+/g, ' ').trim();

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

        document.querySelectorAll('[data-qa-edit]').forEach((cell) => {
            cell.addEventListener('click', (event) => {
                if (!event.ctrlKey || event.button !== 0 || cell.tagName !== 'TD') {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(timers.get(cell));
                saveCellBackground(cell, selectedColor);
                cell.blur();
            });

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
