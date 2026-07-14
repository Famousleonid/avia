<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.user-scoped-storage')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Process Form </title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    @include('shared.spec-process-forms._styles')
    <script>
        window.addEventListener('error', function (e) {
            const message = e.message || '';
            if (message.includes('identifyDuplicates') || message.includes('statements is not iterable')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);
    </script>
    @php
        $technicianDisplayName = trim((string) optional($current_wo->user)->selection_name);
    @endphp

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 980px;
            height: auto;
            transform: scale(0.94);
            transform-origin: top;
            padding: 1px;
            margin-top: 50px;
            margin-left: 10px;
            margin-right: 10px;
        }

        @media print {
            @page {
                size: 11in 8.5in;
                margin: 1mm;
            }

            html, body {
                height: auto;
                width: auto;
                margin-left: 3px;
                padding: 0;
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
                width: 1060px;
                text-align: center;
                font-size: 12px;
                background-color: #fff;
                padding: 1px 1px;
            }

            .container {
                max-height: 100vh;
                overflow: hidden;
            }
            .border-r {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
            border-left: 8px solid lightgrey;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-r {
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
        .border-l-t {
            border-left: 1px solid black;
            border-top: 1px solid black;
        }
        .border-l {
            border-left: 1px solid black;
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
        .border-t {
            border-top: 1px solid black;
        }
        .border-tt-gr {
            border-top: 3px solid gray;
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
        .fs-9 {
            font-size: 0.9rem;
        }
        .fs-75 {
            font-size: 0.75rem;
        }
        .fs-7 {
            font-size: 0.7rem;
        }
        .fs-8 {
            font-size: 0.8rem;
        }
        .fs-6 {
            font-size: 0.6rem;
        }
        .fs-4 {
            font-size: 0.4rem;
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
        .page-break {
            page-break-after: always;
        }

        .parent {
            display: grid;
            grid-template-columns: 525px 45px 170px;
            gap: 0px;
        }

        /* Стили для заполненных данных */
        .spec-component-description {
            height: 32px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 2px;
            overflow: hidden;
        }

        .spec-component-description-text {
            display: block;
            max-width: 100%;
            font-size: 1em;
            font-weight: 700;
            line-height: 1.05;
            text-align: center;
            white-space: normal;
            overflow-wrap: normal;
            word-break: normal;
        }

        .spec-component-description-name {
            display: inline;
            overflow: visible;
        }

        .spec-component-description-ipl {
            display: inline-block;
            font-size: 0.62rem;
            font-weight: 400;
            white-space: nowrap;
            vertical-align: baseline;
        }

        .spec-form-title {
            font-size: 1.35rem;
            line-height: 1;
            margin-bottom: 2px;
        }

        .spec-ro-cell {
            height: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            font-weight: 700;
        }

        .spec-process-ro-value {
            position: absolute;
            left: 30px;
            right: 0;
            top: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            line-height: 1;
        }

        .spec-process-row-cell {
            position: relative;
        }

        .spec-top-count-cell,
        .spec-process-row-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .spec-cat-one-label {
            font-size: 1rem;
            line-height: 1;
            margin: 0;
            text-decoration: underline;
        }

        .spec-technician-block {
            position: absolute;
            right: 0;
            top: 0;
            width: 285px;
        }

        .spec-technician-name-row {
            position: absolute;
            right: 0;
            top: 28px;
            width: 285px;
        }

        .spec-technician-label {
            flex: 0 0 70px;
            height: 10px;
        }

        .spec-technician-name-line {
            flex: 0 0 175px;
            min-width: 0;
            white-space: nowrap;
        }

        .spec-technician-name-line.is-long {
            font-size: 0.75rem;
        }

        .spec-technician-stamp-cell {
            flex: 0 0 40px;
            height: 28px;
        }

        .spec-technician-name-caption {
            flex: 0 0 245px;
            height: 15px;
        }

        .spec-technician-sign-cell {
            flex: 0 0 40px;
            height: 12px;
        }

        .spec-top-count-cell {
            font-size: 0.9rem;
            font-weight: 700;
            padding-top: 0 !important;
        }

        .spec-process-table-body .spec-process-row-inner {
            font-size: var(--spec-process-name-font-size);
            font-weight: 700;
        }

        .filled-data {
            background-color: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>

<body>
<!-- Кнопки печати -->
<div class="text-start m-3 no-print">
    <button class="btn btn-primary" onclick="window.print()">Print Form</button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">⚙️ Print Settings</button>
</div>
@include('shared.spec-process-forms._print-settings-modal', ['formConfig' => config('process_forms.spec_process_form', [])])

@foreach($componentChunks as $chunk)
    @php
        $maxColumnsPerPage = 6;
        $isFirstPage = $loop->first;
        $columnSlots = [];
        $cadTopQty = (int) ($cadSum['total_qty'] ?? 0) + (int) ($cadSum_ex ?? 0);
        foreach ($chunk as $item) {
            if ($item->hasQuarantine) {
                $columnSlots[] = ['slot' => 'left', 'item' => $item];
                $columnSlots[] = ['slot' => 'right', 'item' => $item];
            } else {
                $columnSlots[] = ['slot' => 'single', 'item' => $item];
            }
        }
        while (count($columnSlots) < $maxColumnsPerPage) {
            $columnSlots[] = ['slot' => 'empty', 'item' => null];
        }
    @endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-1">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 160px; margin: 0px 6px 0;">
        </div>
        <div class="col-11">
            <h5 class="text-black text-center spec-form-title"><strong>Special Process Form</strong></h5>
        </div>
    </div>

    <div>
        <div class="row">
            <div class="col-6">
                <div class="d-flex" style="width: 415px">
                    <div style="width: 90px"></div>
                    <div class="fs-8 pt-3" style="width: 20px">qty</div>
                    <div class="fs-8 pt-2" style="width: 115px;height: 20px">MPI</div>
                    <div class="fs-8 pt-2" style="width: 20px">FPI</div>
                    <div class="fs-8 pt-3" style="width: 20px">qty</div>
                    <div class="text-center fs-8" style="width: 20px;height: 20px"></div>
                    <div class="fs-8 pt-2 text-end" style="width: 95px">CAD</div>
                    <div class="fs-8 pt-3 text-center" style="width: 30px">qty</div>
                </div>
            </div>
            <div class="col-2 pt-2 border-b text-center filled-data">
                <strong>W{{ $current_wo->number }}</strong>
            </div>
            <div class="col-md-5"></div>
        </div>

        <div class="d-flex" style="width: 100%; min-height: 43px; position: relative; padding-right: 285px;">

            <div class="text-end">
                <h6 class="pt-1 spec-cat-one-label" style="width: 60px;"><strong>Cat #1</strong></h6>
            </div>
            <div class="fs-8">
                <img src="{{ asset('img/icons/icons8-right-arrow.gif')}}" alt="arrow"
                     style="width: 24px;height: 20px">
            </div>
            <div class="border-l-t-b text-center pt-0 fs-75 spec-top-count-cell {{ $isFirstPage ? '' : 'spec-top-count-cell--crossed' }}" style="width: 25px;height: 20px">
                @if($isFirstPage && $current_wo->instruction_id == 1)
                    {{ !isset($ndtSums['mpi']) || $ndtSums['mpi'] === null ? ' ' : $ndtSums['mpi'] }}
                @endif
            </div>
            <div class="border-l-t-b ps-2 fs-8" style="width: 130px;height: 20px; color: lightgray; font-style: italic">
                RO No.
            </div>
            <div class="border-all text-center pt-0 fs-75 spec-top-count-cell {{ $isFirstPage ? '' : 'spec-top-count-cell--crossed' }}" style="width: 25px;height: 20px">
                @if($isFirstPage && $current_wo->instruction_id == 1)
                    {{ !isset($ndtSums['fpi']) || $ndtSums['fpi'] === null || $ndtSums['fpi'] === 0 ? ' ' : $ndtSums['fpi'] }}
                @endif
            </div>
            <div class="text-center fs-8" style="width: 20px;height: 20px"></div>
            <div class="border-l-t-b ps-2 fs-8" style="width: 100px;height: 20px; color: lightgray; font-style: italic">
                RO No.
            </div>
            <div class="border-all text-center pt-0 fs-75 spec-top-count-cell {{ $isFirstPage ? '' : 'spec-top-count-cell--crossed' }}" style="width: 25px;height: 20px">
                @if($isFirstPage && $current_wo->instruction_id == 1)
                    {{ $cadTopQty > 0 ? $cadTopQty : ' ' }}
                @elseif($isFirstPage)
                    {{ ((int) ($cadSum_ex ?? 0)) > 0 ? $cadSum_ex : ' ' }}
                @endif
            </div>
            <div class="spec-technician-block d-flex">
                <div class="spec-technician-label text-end pt-2 fs-8">Technician</div>
                <div class="spec-technician-name-line border-b text-center {{ strlen($technicianDisplayName) > 20 ? 'is-long' : '' }}">{{ $technicianDisplayName }}</div>
                <div class="spec-technician-stamp-cell border-l-t-r"></div>
            </div>
            <div class="spec-technician-name-row d-flex">
                <div class="spec-technician-name-caption text-center fs-7">Name</div>
                <div class="spec-technician-sign-cell border-l-b-r"></div>
            </div>
        </div>
    </div>

    <div class="d-flex mb-1">
        <div class="" style="width: 80px"></div>
        <img src="{{ asset('img/icons/arrow_ld.png')}}" alt="arrow"
             style="height: 5px;width: 60px" class="mt-2">
        <div class="border-b fs-7" style="width: 300px; height: 15px">
            <strong>Cat #2 (not included in NDT & Cad Cat #1)</strong>
        </div>
    </div>

    <!-- Таблица компонентов -->
    <div class="row g-0 fs-7">
        <!-- Заголовок "Description" -->
        <div class="col-2 border-l-t-b ps-1">
            <div style="height: 20px"><strong>Description</strong></div>
        </div>
        <!-- Основная часть таблицы -->
        <div class="col-10">
            <!-- Строка для имен компонентов -->
            <div class="row g-0">
                @foreach($columnSlots as $slotData)
                    <div class="col {{ $loop->last ? 'border-all' : 'border-l-t-b' }} text-center spec-component-description">
                        @if($slotData['slot'] !== 'empty')
                            @php $component = $slotData['item']->component; @endphp
                            <span class="spec-component-description-text">
                                <span class="spec-component-description-name">{{ $component->component->name }}</span>
                                <span class="spec-component-description-ipl">({{ $component->component->ipl_num }})</span>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Строка для Part No. -->
    <div class="row g-0 fs-7">
        <div class="col-2 border-l-b ps-1">
            <div style="height: 20px"><strong>Part No.</strong></div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @foreach($columnSlots as $slotData)
                    <div class="col {{ $loop->last ? 'border-l-b-r' : 'border-l-b' }} text-center spec-component-part-no" style="height: 22px">
                        @if($slotData['slot'] !== 'empty')
                            {{ $slotData['item']->component->component->part_number }}
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Строка для Serial No. -->
    <div class="row g-0 fs-7">
        <div class="col-2 border-l-b ps-1">
            <div style="height: 20px"><strong>Serial No.</strong></div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @foreach($columnSlots as $slotData)
                    <div class="col {{ $loop->last ? 'border-l-b-r' : 'border-l-b' }} text-center spec-component-serial-no" style="height: 22px">
                        @if($slotData['slot'] !== 'empty')
                            {{ $slotData['item']->component->serial_number }}
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Секция процессов -->
    <div class="row g-0 border-tt-gr">
        <div class="col-2">
            <div class="fs-8 text-end mb-1" style="height: 15px"><strong>Steps sequence</strong>
                <img src="{{ asset('img/icons/arrow_rd.png')}}" alt="arrow"
                     style="height: 10px; margin-right: -15px" class="mt-2">
            </div>
        </div>
        <div class="col-10">
            <div class="row g-0">
                @foreach($columnSlots as $slotData)
                    <div class="col fs-8 text-center spec-ro-cell">
                        <strong>RO No.</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- NDT (3 объединённые строки) -->
    <div class="spec-process-table-body" data-column-count="{{ count($columnSlots) }}">
    <div class="row g-0 fs-7">
        <div class="col-2 border-l-t-b ps-1 d-flex align-items-center ps-2 spec-process-name-cell" style="min-height: calc(var(--spec-process-row-height, 22px) * 3);">
            <strong>NDT</strong>
        </div>
        <div class="col-10">
            @for($ndtRowIndex = 0; $ndtRowIndex < 3; $ndtRowIndex++)
            <div class="row g-0">
                @foreach($columnSlots as $slotData)
                        @php
                            $showValue = false;
                            $ndtEntry = null;
                            $ndtNumberLine = '';
                            $ndtRepairOrder = '';
                            if ($slotData['slot'] !== 'empty') {
                            $component = $slotData['item']->component;
                            $ndtForCurrentTdr = collect($ndt_processes)->where('tdrs_id', $component->id)->values();
                            $quarantineNumberLine = $slotData['item']->quarantineNumberLine;
                            $slot = $slotData['slot'];
                            $ndtEntry = $ndtForCurrentTdr[$ndtRowIndex] ?? null;
                            if ($ndtEntry && $ndtEntry['number_line'] !== null) {
                                if ($slot === 'single') { $showValue = true; }
                                elseif ($slot === 'left' && $quarantineNumberLine !== null && $ndtEntry['number_line'] <= $quarantineNumberLine) { $showValue = true; }
                                elseif ($slot === 'right' && $quarantineNumberLine !== null && $ndtEntry['number_line'] > $quarantineNumberLine) { $showValue = true; }
                            }
                            if ($showValue && $ndtEntry) {
                                $ndtNumberLine = (string) $ndtEntry['number_line'];
                                $ndtRepairOrder = trim((string) ($ndtEntry['repair_order'] ?? ''));
                            }
                        }
                    @endphp
                    <div class="col {{ $loop->last ? ($ndtRowIndex === 0 ? 'border-all' : 'border-l-b-r') : ($ndtRowIndex === 0 ? 'border-l-t-b' : 'border-l-b') }} text-center spec-process-row-cell" style="{{ $slotData['slot'] === 'empty' ? 'position: relative' : '' }}">
                        @if($showValue)
                            <div class="border-r spec-process-row-inner filled-data">{{ $ndtNumberLine }}</div>
                            @if($ndtRepairOrder !== '')
                                <div class="spec-process-ro-value">{{ $ndtRepairOrder }}</div>
                            @endif
                        @else
                            <div class="border-r spec-process-row-inner"></div>
                            @if($slotData['slot'] === 'empty')
                                <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
            @endfor
        </div>
    </div>

    <!-- Другие процессы -->
    @foreach($processNames as $name)
        <div class="row g-0 fs-7 spec-process-data-row spec-process-name-row">
            <div class="col-2 border-l-b ps-1 spec-process-name-cell">
                <div class="spec-process-name-inner"><strong>{{ $name->name }}</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @foreach($columnSlots as $slotData)
                        @php
                            $component = $slotData['item']?->component ?? null;
                            $currentTdrId = $component ? $component->id : null;
                            $processForCurrentTdr = $processes
                                ->where('process_name_id', $name->id)
                                ->where('tdrs_id', $currentTdrId)
                                ->values();
                            $entries = $processForCurrentTdr->filter(fn($p) => $p['number_line'] !== null);
                            $numberLines = $entries->pluck('number_line')->unique()->implode(',');
                            $repairOrderText = $entries->pluck('repair_order')->filter(fn($value) => trim((string) $value) !== '')->unique()->implode(', ');
                        @endphp
                        <div class="col {{ $loop->last ? 'border-l-b-r' : 'border-l-b' }} text-center spec-process-row-cell">
                            @if($numberLines)
                                <div class="border-r spec-process-row-inner filled-data">
                                    {{ $numberLines }}
                                </div>
                                @if($repairOrderText !== '')
                                    <div class="spec-process-ro-value">{{ $repairOrderText }}</div>
                                @endif
                            @else
                                <div class="border-r spec-process-row-inner"></div>
                            @endif
                        </div>
                    @endforeach
                    @for($i = count($columnSlots); $i < 6; $i++)
                        <div class="col {{ $i < 5 ? 'border-l-b' : 'border-l-b-r' }} text-center" style="height: 22px; position: relative;">
                            <div style="position: absolute; left: 29px; top: 0; bottom: 0; width: 1px; border-left: 1px solid black;"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endforeach
    </div>

    <!-- Quality Assurance -->
    <div class="parent mt-1">
        <div class="div2 text-end pe-4 mt-2" style="height: 24px">Quality Assurance Acceptance</div>
        <div class="div3 border-all text-center fs-75" style="width: 45px; align-content: center; height: 42px; color: grey">Q.A. STAMP</div>
        <div class="div4 border-t-r-b fs-75 mt-2 ps-2 pt-1" style="height: 24px; color: grey">Data</div>
    </div>
</div>

<footer>
    <div class="row" style="width: 100%; padding: 5px 5px;">
        <div class="col-6 text-start">
            {{__("Form #012")}}
        </div>
        <div class="col-6 text-end pe-4">
            {{__('Rev#0, 15/Dec/2012')}}
        </div>
    </div>
</footer>

    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach

<script src="{{ asset('assets/Bootstrap 5/bootstrap.bundle.min.js') }}"></script>
@include('shared.spec-process-forms._scripts', ['formConfig' => config('process_forms.spec_process_form', [])])
</body>
</html>
