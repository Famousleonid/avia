@php
    $printMarkFormKey = 'traveler';
    $figureWarnings = \App\Services\TravelerProcessTemplates::figureWarningsForProcesses($tdrProcesses);
@endphp
@php
    $formConfig = $formConfig ?? config('process_forms.travel-form', config('process_forms.tdr-processes'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.user-scoped-storage')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        :root {
            --screen-container-max-width: {{ (int) ($formConfig['container_max_width'] ?? 960) }}px;
            --screen-container-padding: 5px;
            --screen-container-margin-left: 10px;
            --screen-container-margin-right: 10px;
            --print-page-margin: 8mm;
            --print-container-max-width: 100%;
            --print-container-padding: 0;
            --print-container-margin-left: 0;
            --print-container-margin-right: 0;
            --print-footer-font-size: 12px;
            --component-name-font-size: 16px;
            --other-table-data-font-size: 13px;
            --traveler-table-header-font-size: 12px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: var(--screen-container-max-width, 960px);
            height: var(--print-container-max-height, 99%);
            padding: var(--screen-container-padding, 5px);
            margin-left: var(--screen-container-margin-left, 10px);
            margin-right: var(--screen-container-margin-right, 10px);
        }

        footer {
            width: 100%;
            margin-top: 20px;
            font-size: var(--print-footer-font-size, 12px);
        }

        .parent {
            font-size: var(--component-name-font-size, 16px);
        }

        .table {
            font-size: var(--other-table-data-font-size, 13px);
        }

        @media print {
            /* Фиксированный размер страницы Letter в альбомной ориентации (11 x 8.5 дюймов = 279.4 x 215.9 мм) */
            @page {
                size: letter landscape;
                margin: var(--print-page-margin, 8mm) var(--print-page-margin, 8mm) 16mm;
                padding: 0;
            }

            /* Сброс всех отступов и полей для консистентности */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Let the printed document follow its content instead of reserving a full extra page height. */
            html, body {
                width: var(--print-body-width, 100%) !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 16px !important; /* Фиксированный размер шрифта */
                line-height: 1.2 !important;
            }

            /* Контейнер с фиксированной шириной */
            .container-fluid {
                width: 100% !important;
                max-width: var(--print-container-max-width, 100%) !important;
                margin-left: var(--print-container-margin-left, 0) !important;
                margin-right: var(--print-container-margin-right, 0) !important;
                padding: var(--print-container-padding, 0) !important;
                height: auto !important;
            }

            /* Отключаем разрывы страниц внутри элементов */
            h1, h2, h3, h4, h5, h6, p {
                page-break-inside: avoid !important;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print {
                display: none !important;
            }

            /* Keep the form revision in document flow; never cover a process row. */
            footer {
                position: static !important;
                break-inside: avoid;
                margin-top: 0 !important;
                width: var(--print-footer-width, 100%) !important;
                max-width: 27cm !important;
                text-align: center !important;
                font-size: var(--print-footer-font-size, 12px) !important;
                background-color: #fff !important;
                padding: var(--print-footer-padding, 2pt) !important;
            }

            /* Обрезка контента и размещение на одной странице */
            .container {
                max-height: none !important;
                overflow: visible !important;
            }

            /* Фиксированные размеры для таблиц */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: var(--other-table-data-font-size, 13px) !important;
            }

            /* Убираем градиенты и тени при печати */
            .bg-gradient {
                background: #fff !important;
            }

            /* Фиксированные размеры шрифтов */
            h1, h2, h3, h4, h5, h6 {
                font-size: 16px !important;
                margin: 5pt 0 !important;
            }

            p {
                font-size: 16px !important;
                margin: 3pt 0 !important;
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
        .fs-4 {
            font-size: 0.4rem; /* или любое другое подходящее значение */
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





        .parent {
            display: grid;
            grid-template-columns:
            6% 12% 12% 42% 6% 22%;
        }
        .table {
            text-align: center;
        }

        .traveler-title,
        .traveler-subtitle,
        .parent > div {
            font-size: var(--component-name-font-size, 16px);
        }

        .traveler-process-table td {
            font-size: var(--other-table-data-font-size, 13px) !important;
        }

        .traveler-process-table .traveler-table-header {
            font-size: var(--traveler-table-header-font-size, 12px) !important;
        }


        /* A real table paginates as whole rows and repeats its headings. */
        .traveler-process-table { display: table; table-layout: fixed; width: 100%; }
        .traveler-process-table td { height: 36px; vertical-align: middle; padding: 3px; overflow-wrap: anywhere; }
        .traveler-process-table td.div2 { font-size: 14px !important; }
        @media screen {
            .container-fluid > .row { margin-left: 0; margin-right: 0; }
            .traveler-document-header .parent > .div1 { white-space: nowrap; }
            /* The screen form is narrower than landscape paper. Reserve room for task names. */
            .traveler-process-table col:nth-child(2) { width: 16% !important; }
            .traveler-process-table col:nth-child(3) { width: 34% !important; }
        }
        .traveler-process-table td.div3,
        .traveler-process-table .process-description { font-size: 14px !important; line-height: 1.15; text-align: left; }
        .traveler-process-table tr { break-inside: avoid; page-break-inside: avoid; }
        .traveler-process-table thead { display: table-header-group; break-inside: avoid; }
        .traveler-process-table td.traveler-document-header {
            padding: 0 0 8px;
            border: 0;
            background: transparent;
            box-shadow: none;
        }
        .traveler-document-header > .row { margin: 0; }
        .traveler-process-table .traveler-table-header { font-size: 12px !important; }
        @media print {
            .container-fluid > .row { display: block; margin: 0; }
            .container-fluid > .row > .col-4 { float: left; }
            .container-fluid > .row > .col-8 { margin-left: 33.333%; }
            .traveler-document-header > .row { display: block; }
            .traveler-document-header > .row > .col-4 { float: left; }
            .traveler-document-header > .row > .col-8 { margin-left: 33.333%; }
            .traveler-process-table { break-inside: auto !important; page-break-inside: auto !important; margin-bottom: 0 !important; }
            .container-fluid { padding-bottom: 0 !important; }
        }
    </style>
</head>
<body>
@include('shared.print-mark.qr', [
    'printMarkWorkorder' => $current_wo ?? null,
    'printMarkLabel' => ($travelerGroup ?? 0) > 0 ? 'T' . (int) $travelerGroup : null,
    'printMarkQrTop' => '1mm',
    'printMarkQrRight' => '2mm',
    'printMarkQrPrintTop' => '0',
    'printMarkQrPrintRight' => '2mm',
    'printMarkQrScreenPlacement' => 'page',
    'printMarkQrHostSelector' => '.container-fluid',
    'printMarkQrPageRight' => '4mm',
])
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" type="button" data-traveler-print-button>Print Form</button>
    <button class="btn btn-secondary ms-2" type="button" data-bs-toggle="modal" data-bs-target="#printSettingsModal">⚙️ Print Settings</button>
    @if(!empty($figPagesHtml))
        <label class="ms-3" style="cursor:pointer">
            <input type="checkbox" class="form-check-input" id="includeFigChk" checked>
            {{ __('Print with Fig.') }} ({{ count($figPagesHtml) }})
        </label>
    @endif
</div>

@if($figureWarnings !== [])
    <div class="alert alert-warning m-3 no-print" role="status">
        <strong>Traveler can be printed. Please check the missing FIG references:</strong>
        <ul class="mb-0">
            @foreach($figureWarnings as $warning)
                <li>{{ $warning }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="container-fluid">
    <div class="row">
<table class="table mt-2 traveler-process-table"><colgroup><col style="width:4%"><col style="width:14%"><col style="width:36%"><col style="width:9%"><col style="width:10%"><col style="width:10%"><col style="width:8%"><col style="width:9%"></colgroup><thead>
    <tr><td colspan="8" class="traveler-document-header"><div class="row">
        <div class="col-4">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 180px; margin: 6px 10px 0;">
        </div>
        <div class="col-8 ">
            <h4 class="pt-4 ms-5 text-black traveler-title"><strong>PART TRAVELER</strong></h4>
        </div>
        <div class="text-center traveler-subtitle" style="margin-top: -10px">
            <h6>(This document must accompany the part and must be returned to the originator)</h6>
        </div>

        <div class="parent">
            <div class="div1" style="text-align: end"> <strong>WO No.</strong></div>
            <div class="div2 border-b" style="text-align: center">{{ filled($current_wo->number) ? 'W'.$current_wo->number : '' }}</div>
            <div class="div3" style="text-align: end"> <strong>Description</strong></div>
            <div class="div4 border-b ps-2"> {{ $current_tdr->component->name }} ( {{ $current_tdr->component->ipl_num }})</div>
            <div class="div5" style="text-align: end"> <strong>P/N</strong></div>
            <div class="div6 border-b ps-2">{{ $current_tdr->component->part_number }}</div>
            <div class="div7" style="text-align: end"> <strong>CMM</strong></div>
            <div class="div8 border-b" style="text-align: center">{{ format_cmm_number($manual->number) }}</div>
            <div class="div9" style="text-align: end"> <strong>Repair No.</strong></div>
            <div class="div10 border-b ps-2">{{ $repairNum ?? 'N/A' }}</div>
            <div class="div11" style="text-align: end"> <strong>S/N</strong></div>
            <div class="div12 border-b ps-2" >{{ $current_tdr->serial_number }} · QTY: {{ max(1, (int) $current_tdr->qty) }}</div>
        </div>

    </div></td></tr>
            <tr><td class="div1 border-all traveler-table-header" style="min-height: 36px;  align-content: center"></td>
            <td class="div2 border-t-r-b traveler-table-header" style="min-height: 36px;  align-content: center"> <strong>Task require</strong>d</td>
            <td class="div3 border-t-r-b traveler-table-header" style="min-height: 36px;  align-content: center"> <strong>Process</strong></td>
            <td class="div4 border-t-r-b traveler-table-header" style="min-height: 36px;  align-content: center"> <strong>RO No.</strong></td>
            <td class="div5 border-t-r-b traveler-table-header" style="min-height: 36px;  align-content: center"> <strong>Vendor Name</strong></td>
            <td class="div6 border-t-r-b traveler-table-header" style="min-height: 36px;  align-content: center"> <strong>Date
                    completed</strong></td>
            <td class="div7 border-t-r-b traveler-table-header" style="min-height: 36px;  align-content: center"> <strong>Vendor stamp</strong></td>
            <td class="div8 border-t-r-b traveler-table-header" style="min-height: 36px;  align-content: center"> <strong>Note</strong></td></tr></thead><tbody>

            @php
                $dateRows = 0;
                $processLines = [];
                foreach ($tdrProcesses as $tp) {
                    if (!$tp->processName) {
                        continue;
                    }
                    $processNameLine = $tp->processName->name;
                    $processData = \App\Models\TdrProcess::normalizeStoredProcessIds($tp->processes);
                    if (!is_array($processData)) {
                        $processData = [];
                    }
                    $inTravelerLine = (bool) $tp->in_traveler;
                    if (count($processData) === 0) {
                        $processLines[] = [
                            'tp' => $tp,
                            'processName' => $processNameLine,
                            'subId' => null,
                            'inTraveler' => $inTravelerLine,
                        ];
                        continue;
                    }
                    foreach ($processData as $subId) {
                        $processLines[] = [
                            'tp' => $tp,
                            'processName' => $processNameLine,
                            'subId' => $subId,
                            'inTraveler' => $inTravelerLine,
                        ];
                    }
                }
                $prevTraveler = null;
                $previousIsOutgoingInspection = false;
                $isOutgoingInspection = static function ($process): bool {
                    $names = array_map(static fn ($name) => strtolower(trim($name)), [
                        $process->processName->name, $process->processName->identityName(),
                    ]);
                    return count(array_intersect($names, ['outcoming inspection', 'outgoing inspection'])) > 0
                        || (in_array('inspect', $names, true)
                            && preg_match('/\binspection\s+stage\s*:\s*outgoing\b/i', (string) $process->description));
                };
                $receivingInspectionRenderedForBlock = false;
            @endphp

            @foreach($processLines as $line)
                @php
                    $tp = $line['tp'];
                    $processName = $line['processName'];
                    $inTraveler = $line['inTraveler'];
                    $subId = $line['subId'];
                    $subLabel = '';
                    if ($subId !== null) {
                        foreach ($proces as $proc) {
                            if ((int) $proc->id === (int) $subId) {
                                $subLabel = $proc->process;
                                break;
                            }
                        }
                    }
                    $normalizedProcessName = strtoupper($tp->processName->identityName());
                    $currentIsOutgoingInspection = $isOutgoingInspection($tp);
                    $isNdt6TravelerLine = $inTraveler && (
                        str_starts_with($normalizedProcessName, 'NDT-6')
                        || $normalizedProcessName === 'EDDY CURRENT TEST'
                    );
                @endphp

                @if($inTraveler && $prevTraveler !== true)
                    @php
                        $receivingInspectionRenderedForBlock = false;
                    @endphp
                @endif
                @if($inTraveler && $prevTraveler !== true && !$previousIsOutgoingInspection && !$currentIsOutgoingInspection)
                    @php
                        $dateRows++;
                    @endphp
                    <tr><td class="div1 border-l-b-r " style="min-height: 36px; align-content: center">{{ $dateRows }}</td>
                    <td class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong>{{ __('Outcoming inspection') }}</strong></td>
                    <td class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center">{{ __('Visual') }}</td>
                    <td class="div4 border-r-b " style="min-height: 36px; align-content: center">{{ __('N/A') }}</td>
                    <td class="div5 border-r-b " style="min-height: 36px; align-content: center">{{ __('AT') }}</td>
                    <td class="div6 border-r-b " style="min-height: 36px; align-content: center"></td>
                    <td class="div7 border-r-b" style="min-height: 36px; align-content: center"></td>
                    <td class="div8 border-r-b " style="min-height: 36px; align-content: center"></td></tr>
                @endif

                @if($isNdt6TravelerLine && !$receivingInspectionRenderedForBlock)
                    @php
                        $dateRows++;
                        $receivingInspectionRenderedForBlock = true;
                    @endphp
                    @include('admin.tdr-processes.partials.traveler-receiving-inspection-row', ['dateRows' => $dateRows])
                @endif

                @if(!$inTraveler && $prevTraveler === true && !$receivingInspectionRenderedForBlock)
                    @php
                        $dateRows++;
                        $receivingInspectionRenderedForBlock = true;
                    @endphp
                    @include('admin.tdr-processes.partials.traveler-receiving-inspection-row', ['dateRows' => $dateRows])
                @endif

                @php $dateRows++; @endphp
                <tr><td class="div1 border-l-b-r " style="min-height: 36px; align-content: center">{{ $dateRows }}</td>
                <td class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center">
                    <strong>
                        @if($tp->processName->hasIdentity(['NDT-1', 'NDT-4']))
                            {{ __('NDT') }}
                        @else
                            {{ $processName }}
                        @endif
                    </strong>
                </td>
                <td class="div3 border-r-b fs-75" style="min-height: 36px; align-content: center">
                    @if($subLabel !== '')
                        <span class="process-specification" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $subLabel }}</span>
                        @if($tp->ec) ( EC ) @endif
                    @endif
                    @php
                        $additionalDescription = \App\Services\TravelerPrintDescription::additional(
                            $tp->description, $subLabel,
                            [$processName, $tp->processName->name, $tp->processName->identityName(),
                                $tp->processName->hasIdentity(['NDT-1', 'NDT-4']) ? 'NDT' : '']
                        );
                    @endphp
                    @if($additionalDescription !== '')
                        <div class="fs-8 process-description" style="white-space: pre-line; overflow-wrap: anywhere;">{{ $additionalDescription }}</div>
                    @endif
                </td>
                <td class="div4 border-r-b " style="min-height: 36px; align-content: center">
                    @if(!empty($tp->repair_order))
                        {{ $tp->repair_order }}
                    @elseif(!$inTraveler)
                        {{ filled($current_tdr->workorder->number) ? 'W'.$current_tdr->workorder->number : '' }}
                    @elseif($tp->processName->hasIdentity('Paint'))
                        {{ filled($current_wo->number) ? 'W'.$current_wo->number : '' }}
                    @endif
                </td>
                <td class="div5 border-r-b " style="min-height: 36px; align-content: center">
                    @if($inTraveler)
                        {{ $vendorName ?: ($tp->vendor?->name ?? '') }}
                    @else
                        {{ __('AT') }}
                    @endif
                </td>
                <td class="div6 border-r-b " style="min-height: 36px; align-content: center"></td>
                <td class="div7 border-r-b" style="min-height: 36px; align-content: center"></td>
                <td class="div8 border-r-b fs-8" style="min-height: 36px; align-content: center">
                    @foreach(collect([$tp->notes, $travelerTemplateNotes->get($tp->process_names_id)])->filter(fn ($note) => filled($note))->unique() as $note)
                        <div style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $note }}</div>
                    @endforeach
                </td></tr>

                @php
                    $prevTraveler = $inTraveler;
                    $previousIsOutgoingInspection = $currentIsOutgoingInspection;
                @endphp
            @endforeach

            @if($prevTraveler === true && !$receivingInspectionRenderedForBlock)
                @php
                    $dateRows++;
                    $receivingInspectionRenderedForBlock = true;
                @endphp
                @include('admin.tdr-processes.partials.traveler-receiving-inspection-row', ['dateRows' => $dateRows])
            @endif
            @php
                $travelerMinTotal = (int) ($formConfig['traveler_table_total_rows'] ?? $formConfig['other_table_rows'] ?? 14);
                $totalRow = max($dateRows, $travelerMinTotal);
                $emptyRow = $totalRow - $dateRows;
            @endphp
            @for($i=0; $i < $emptyRow; $i++)
                <tr><td class="div1 border-l-b-r " style="min-height: 36px; align-content: center"></td>
                <td class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong></strong></td>
                <td class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center"></td>
                <td class="div4 border-r-b " style="min-height: 36px; align-content: center"></td>
                <td class="div5 border-r-b " style="min-height: 36px; align-content: center"></td>
                <td class="div6 border-r-b " style="min-height: 36px; align-content: center"></td>
                <td class="div7 border-r-b" style="min-height: 36px; align-content: center"></td>
                <td class="div8 border-r-b " style="min-height: 36px; align-content: center"></td></tr>
            @endfor</tbody></table>
        <div id="traveler-table-meta" class="d-none" data-date-rows="{{ $dateRows }}" data-total-rows="{{ $totalRow }}" aria-hidden="true"></div>

        <template id="traveler-empty-rows-fragment">
            <tr><td class="div1 border-l-b-r traveler-empty-line" style="min-height: 36px; align-content: center"></td>
            <td class="div2 border-r-b fs-9 traveler-empty-line" style="min-height: 36px; align-content: center"><strong></strong></td>
            <td class="div3 border-r-b fs-9 traveler-empty-line" style="min-height: 36px; align-content: center"></td>
            <td class="div4 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></td>
            <td class="div5 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></td>
            <td class="div6 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></td>
            <td class="div7 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></td>
            <td class="div8 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></td></tr>
        </template>





    </div>

    <footer >
        <div class="row" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__("Form #032")}}
            </div>
            <div class="col-6 text-end  ">
                {{__('Rev#0, 29 June 2020   ')}}
            </div>
        </div>

    </footer>
</div>

@include('admin.tdr-processes.partials.traveler-template-images')

{{-- страница traveler — letter landscape: чертёж разворачиваем на листе на 90° --}}
@include('shared.process-forms._fig-attachment', ['figRotatePrint' => true])

@include('shared.process-forms._print-settings-modal', ['module' => 'travel-form', 'formConfig' => $formConfig, 'showFormTypes' => ['other']])
@include('shared.process-forms._scripts', ['module' => 'travel-form', 'formConfig' => $formConfig])
<script>
    document.querySelector('[data-traveler-print-button]').addEventListener('click', async function () {
        this.disabled = true;
        const error = document.querySelector('[data-traveler-image-error]');
        let timeout;
        try {
            await Promise.race([
                Promise.resolve(window.travelerAttachmentsReady).then(() =>
                    Promise.all([...document.querySelectorAll('[data-traveler-print-image]')].map(image => image.decode()))),
                new Promise((_, reject) => { timeout = setTimeout(() => reject(new Error('Image loading timed out')), 30000); })
            ]);
            if (error) error.hidden = true;
            window.print();
        } catch (_) {
            if (error) {
                error.hidden = false;
                error.textContent = 'Traveler attachments have not loaded. Check the images and PDFs, then reload before printing.';
                error.scrollIntoView({block: 'center'});
            }
        } finally { clearTimeout(timeout); this.disabled = false; }
    });
    if (typeof window.bootstrapLoaded === 'undefined') {
        window.bootstrapLoaded = true;
        const script = document.createElement('script');
        script.src = "{{ asset('assets/Bootstrap 5/bootstrap.bundle.min.js') }}";
        script.async = true;
        document.head.appendChild(script);
    }
</script>
</body>
</html>
