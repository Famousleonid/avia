@php
    $formConfig = $formConfig ?? config('process_forms.travel-form', config('process_forms.tdr-processes'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: var(--print-container-max-width, 960px);
            height: var(--print-container-max-height, 99%);
            padding: var(--print-container-padding, 5px);
            margin-left: var(--print-container-margin-left, 10px);
            margin-right: var(--print-container-margin-right, 10px);
        }

        .parent {
            font-size: var(--component-name-font-size, 12px);
        }

        .table {
            font-size: var(--other-table-data-font-size, 10px);
        }

        @media print {
            /* Фиксированный размер страницы Letter в альбомной ориентации (11 x 8.5 дюймов = 279.4 x 215.9 мм) */
            @page {
                size: letter landscape;
                margin: var(--print-page-margin, 0.5cm);
                padding: 0;
            }

            /* Сброс всех отступов и полей для консистентности */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Фиксированные размеры для body и html */
            html, body {
                width: var(--print-body-width, 100%) !important;
                height: var(--print-body-height, 100%) !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 12pt !important; /* Фиксированный размер шрифта */
                line-height: 1.2 !important;
            }

            /* Контейнер с фиксированной шириной */
            .container-fluid {
                width: 100% !important;
                max-width: var(--print-container-max-width, 100%) !important;
                margin-left: var(--print-container-margin-left, 0) !important;
                margin-right: var(--print-container-margin-right, 0) !important;
                padding: var(--print-container-padding, 0.3cm) !important;
                height: auto !important;
            }

            /* Отключаем разрывы страниц внутри элементов */
            table, h1, h2, h3, h4, h5, h6, p {
                page-break-inside: avoid !important;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print {
                display: none !important;
            }

            /* Колонтитул внизу страницы с фиксированными размерами */
            footer {
                position: fixed !important;
                bottom: var(--print-footer-bottom, 0.3cm) !important;
                width: var(--print-footer-width, 100%) !important;
                max-width: 27cm !important;
                text-align: center !important;
                font-size: var(--print-footer-font-size, 8pt) !important;
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
                font-size: 10pt !important;
            }

            /* Убираем градиенты и тени при печати */
            .bg-gradient {
                background: #fff !important;
            }

            /* Фиксированные размеры шрифтов */
            h1, h2, h3, h4, h5, h6 {
                font-size: 14pt !important;
                margin: 5pt 0 !important;
            }

            p {
                font-size: 11pt !important;
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
            60px 100px   /* WO No. */
            280px 350px   /* Description - больше места */
            60px 150px    /* P/N */
        }
        .table {
            display: grid;
            text-align: center;

            grid-template-columns:
        40px 160px
        340px 100px
        120px 100px
        60px 100px;
            /*min-height: 42px; !* Опционально: для единой высоты строк *!*/
        }

    </style>
</head>
<body>
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" type="button" onclick="window.print()">Print Form</button>
    <button class="btn btn-secondary ms-2" type="button" data-bs-toggle="modal" data-bs-target="#printSettingsModal">⚙️ Print Settings</button>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-4">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 180px; margin: 6px 10px 0;">
        </div>
        <div class="col-8 ">
            <h4 class="pt-4   ms-5 text-black text-"><strong>PART TRAVELER</strong></h4>
        </div>
        <div class="text-center" style="margin-top: -10px">
            <h6>(This document must accompany the part and must be returned to the originator)</h6>
        </div>

        <div class="parent">
            <div class="div1" style="text-align: end"> <strong>WO No.</strong></div>
            <div class="div2 border-b" style="text-align: center">W{{$current_wo->number}}</div>
            <div class="div3" style="text-align: end"> <strong>Description</strong></div>
            <div class="div4 border-b ps-2"> {{ $current_tdr->component->name }} ( {{ $current_tdr->component->ipl_num }})</div>
            <div class="div5" style="text-align: end"> <strong>P/N</strong></div>
            <div class="div6 border-b ps-2">{{ $current_tdr->component->part_number }}</div>
            <div class="div7" style="text-align: end"> <strong>CMM</strong></div>
            <div class="div8 border-b" style="text-align: center">{{substr($manual->number, 0, 8)}}</div>
            <div class="div9" style="text-align: end"> <strong>Repair No.</strong></div>
            <div class="div10 border-b ps-2">{{ $repairNum ?? 'N/A' }}</div>
            <div class="div11" style="text-align: end"> <strong>S/N</strong></div>
            <div class="div12 border-b ps-2" >{{ $current_tdr->serial_number }}</div>
        </div>

        <div class="table mt-2 traveler-process-table">
            <div class="div1 border-all " style="min-height: 36px;  align-content: center"></div>
            <div class="div2 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Task require</strong>d</div>
            <div class="div3 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Process</strong></div>
            <div class="div4 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>RO No.</strong></div>
            <div class="div5 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Vendor Name</strong></div>
            <div class="div6 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Date
                    completed</strong></div>
            <div class="div7 border-t-r-b" style="min-height: 36px;  align-content: center"> <strong>Vendor stamp</strong></div>
            <div class="div8 border-t-r-b " style="min-height: 36px;  align-content: center"> <strong>Note</strong></div>

            @php
                $dateRows = 0;
                $processLines = [];
                foreach ($tdrProcesses as $tp) {
                    if (!$tp->processName) {
                        continue;
                    }
                    $processNameLine = $tp->processName->name;
                    $processData = json_decode($tp->processes, true);
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
                @endphp

                @if($inTraveler && $prevTraveler !== true)
                    @php $dateRows++; @endphp
                    <div class="div1 border-l-b-r " style="min-height: 36px; align-content: center">{{ $dateRows }}</div>
                    <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong>{{ __('Outcoming inspection') }}</strong></div>
                    <div class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center">{{ __('Visual') }}</div>
                    <div class="div4 border-r-b " style="min-height: 36px; align-content: center">{{ __('N/A') }}</div>
                    <div class="div5 border-r-b " style="min-height: 36px; align-content: center">{{ __('AT') }}</div>
                    <div class="div6 border-r-b " style="min-height: 36px; align-content: center"></div>
                    <div class="div7 border-r-b" style="min-height: 36px; align-content: center"></div>
                    <div class="div8 border-r-b " style="min-height: 36px; align-content: center"></div>
                @endif

                @if(!$inTraveler && $prevTraveler === true)
                    @php $dateRows++; @endphp
                    <div class="div1 border-l-b-r" style="min-height: 36px; align-content: center">{{ $dateRows }}</div>
                    <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong>{{ __('Receiving inspection') }}</strong></div>
                    <div class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center">{{ __('Visual') }}</div>
                    <div class="div4 border-r-b" style="min-height: 36px; align-content: center">{{ __('N/A') }}</div>
                    <div class="div5 border-r-b" style="min-height: 36px; align-content: center">{{ __('AT') }}</div>
                    <div class="div6 border-r-b" style="min-height: 36px; align-content: center"></div>
                    <div class="div7 border-r-b" style="min-height: 36px; align-content: center"></div>
                    <div class="div8 border-r-b" style="min-height: 36px; align-content: center"></div>
                @endif

                @php $dateRows++; @endphp
                <div class="div1 border-l-b-r " style="min-height: 36px; align-content: center">{{ $dateRows }}</div>
                <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center">
                    <strong>
                        @if($processName == 'NDT-1' || $processName == 'NDT-4')
                            {{ __('NDT') }}
                        @else
                            {{ $processName }}
                        @endif
                    </strong>
                </div>
                <div class="div3 border-r-b fs-75" style="min-height: 36px; align-content: center">
                    @if($subLabel !== '')
                        {{ $subLabel }}
                        @if($tp->ec) ( EC ) @endif
                    @endif
                    <div class="fs-8">
                        @if($tp->description)
                            {{ $tp->description }}
                        @endif
                    </div>
                </div>
                <div class="div4 border-r-b " style="min-height: 36px; align-content: center">
                    @if(!empty($tp->repair_order))
                        {{ $tp->repair_order }}
                    @elseif(!$inTraveler)
                        W{{ $current_tdr->workorder->number }}
                    @elseif($processName == 'Paint ')
                        W{{ $current_wo->number }}
                    @endif
                </div>
                <div class="div5 border-r-b " style="min-height: 36px; align-content: center">
                    @if($inTraveler)
                        {{ $vendorName ?: ($tp->vendor?->name ?? '') }}
                    @else
                        {{ __('AT') }}
                    @endif
                </div>
                <div class="div6 border-r-b " style="min-height: 36px; align-content: center"></div>
                <div class="div7 border-r-b" style="min-height: 36px; align-content: center"></div>
                <div class="div8 border-r-b fs-8" style="min-height: 36px; align-content: center">
                    @if($tp->notes)
                        {{ $tp->notes }}
                    @endif
                </div>

                @php $prevTraveler = $inTraveler; @endphp
            @endforeach

            @if($prevTraveler === true)
                @php $dateRows++; @endphp
                <div class="div1 border-l-b-r" style="min-height: 36px; align-content: center">{{ $dateRows }}</div>
                <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong>{{ __('Receiving inspection') }}</strong></div>
                <div class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center">{{ __('Visual') }}</div>
                <div class="div4 border-r-b" style="min-height: 36px; align-content: center">{{ __('N/A') }}</div>
                <div class="div5 border-r-b" style="min-height: 36px; align-content: center">{{ __('AT') }}</div>
                <div class="div6 border-r-b" style="min-height: 36px; align-content: center"></div>
                <div class="div7 border-r-b" style="min-height: 36px; align-content: center"></div>
                <div class="div8 border-r-b" style="min-height: 36px; align-content: center"></div>
            @endif
            @php
                $travelerMinTotal = (int) ($formConfig['traveler_table_total_rows'] ?? $formConfig['other_table_rows'] ?? 14);
                $totalRow = max($dateRows, $travelerMinTotal);
                $emptyRow = $totalRow - $dateRows;
            @endphp
            @for($i=0; $i < $emptyRow; $i++)
                <div class="div1 border-l-b-r " style="min-height: 36px; align-content: center"></div>
                <div class="div2 border-r-b fs-9" style="min-height: 36px; align-content: center"><strong></strong></div>
                <div class="div3 border-r-b fs-9" style="min-height: 36px; align-content: center"></div>
                <div class="div4 border-r-b " style="min-height: 36px; align-content: center"></div>
                <div class="div5 border-r-b " style="min-height: 36px; align-content: center"></div>
                <div class="div6 border-r-b " style="min-height: 36px; align-content: center"></div>
                <div class="div7 border-r-b" style="min-height: 36px; align-content: center"></div>
                <div class="div8 border-r-b " style="min-height: 36px; align-content: center"></div>
            @endfor

        </div>
        <div id="traveler-table-meta" class="d-none" data-date-rows="{{ $dateRows }}" data-total-rows="{{ $totalRow }}" aria-hidden="true"></div>

        <template id="traveler-empty-rows-fragment">
            <div class="div1 border-l-b-r traveler-empty-line" style="min-height: 36px; align-content: center"></div>
            <div class="div2 border-r-b fs-9 traveler-empty-line" style="min-height: 36px; align-content: center"><strong></strong></div>
            <div class="div3 border-r-b fs-9 traveler-empty-line" style="min-height: 36px; align-content: center"></div>
            <div class="div4 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></div>
            <div class="div5 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></div>
            <div class="div6 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></div>
            <div class="div7 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></div>
            <div class="div8 border-r-b traveler-empty-line" style="min-height: 36px; align-content: center"></div>
        </template>
        <script>
            (function () {
                function applyTravelerRowsFromPrintSettings() {
                    var table = document.querySelector('.traveler-process-table');
                    var meta = document.getElementById('traveler-table-meta');
                    var tpl = document.getElementById('traveler-empty-rows-fragment');
                    if (!table || !meta || !tpl || !tpl.content) return;
                    var dateRows = parseInt(meta.getAttribute('data-date-rows'), 10) || 0;
                    var serverTotal = parseInt(meta.getAttribute('data-total-rows'), 10) || 0;
                    var target = serverTotal;
                    try {
                        var key = @json($formConfig['storage_key'] ?? 'travelForm_print_settings');
                        var raw = localStorage.getItem(key);
                        if (raw) {
                            var saved = JSON.parse(raw);
                            var w = parseInt(String(saved.otherTableRows != null ? saved.otherTableRows : '').replace(/\D/g, ''), 10);
                            if (w > 0) {
                                target = Math.max(serverTotal, dateRows, w);
                            }
                        }
                    } catch (e) {}
                    var extra = target - serverTotal;
                    for (var i = 0; i < extra; i++) {
                        table.appendChild(tpl.content.cloneNode(true));
                    }
                    meta.setAttribute('data-total-rows', String(serverTotal + extra));
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', applyTravelerRowsFromPrintSettings);
                } else {
                    applyTravelerRowsFromPrintSettings();
                }
            })();
        </script>




    </div>

    <footer >
        <div class="row" style="width: 105%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__("Form #032")}}
            </div>
            <div class="col-6 text-end  ">
                {{__('Rev#0, 29 June 2020   ')}}
            </div>
        </div>

    </footer>
</div>

@include('shared.process-forms._print-settings-modal', ['module' => 'travel-form', 'formConfig' => $formConfig, 'showFormTypes' => ['other']])
@include('shared.process-forms._scripts', ['module' => 'travel-form', 'formConfig' => $formConfig])
<script>
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
