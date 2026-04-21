<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STRESS RELIEF PROCESS SHEET</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        :root {
            --container-max-width: 920px;
            --container-padding: 5px;
            --container-margin-left: 10px;
            --container-margin-right: 10px;
            --print-page-margin: 1mm;
            --print-body-height: 99%;
            --print-body-width: 98%;
            --print-body-margin-left: 2px;
            --print-footer-width: 800px;
            --print-footer-font-size: 10px;
            --print-footer-padding: 2px 2px;
        }

        .container-fluid {
            max-width: var(--container-max-width);
            height: 98%;
            padding: var(--container-padding);
            margin-left: var(--container-margin-left);
            margin-right: var(--container-margin-right);
        }

        @media print {
            @page {
                size: letter;
                margin: var(--print-page-margin);
            }

            /* Width/height из Print Settings (:root обновляется в shared.tdr-forms._scripts) */
            html, body {
                height: auto;
                min-height: var(--print-body-height);
                width: var(--print-body-width);
                max-width: var(--print-body-width);
                margin-left: 0;
                margin-right: 0;
                padding: 0;
            }

            h1, p {
                page-break-inside: avoid;
            }

            /* Фиксированная высота 98% + fixed footer ломали разбиение: лишние «листы», шапка на одном листе, таблица на другом */
            .container-fluid {
                height: auto !important;
                max-height: none !important;
            }

            /*
             * Лист 1: body + .container-fluid давали разный суммарный отступ, чем лист 2+ (только .container-fluid).
             * Весь горизонтальный отступ — на каждом блоке листа одинаково.
             */
            .container-fluid.tdr-primary-sheet,
            .container-fluid.dynamic-page-wrapper {
                margin-left: calc(var(--print-body-margin-left) + var(--container-margin-left)) !important;
                margin-right: var(--container-margin-right) !important;
                padding-left: var(--container-padding) !important;
                padding-right: var(--container-padding) !important;
                box-sizing: border-box;
                max-width: var(--container-max-width);
            }

            /*
             * Нельзя вешать break-inside: avoid на весь .data-page: блок выше листа → движок ломает разрыв
             * и на одном листе оказываются «хвост» 1-й страницы + начало 2-й (дублирование данных).
             * Разрыв только между логическими страницами; шапку формы не рвём по возможности.
             */
            .dynamic-page-wrapper {
                break-before: page !important;
                page-break-before: always !important;
                display: block !important;
            }

            .tdr-print-force-page-end {
                page-break-after: always !important;
                break-after: page !important;
            }

            .page.data-page .header-page,
            .page.data-page .table-header {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .no-print {
                display: none;
            }

            /* Скрываем строки сверх лимита */
            .print-hide-row {
                display: none !important;
            }

            /* Строки источника, не входящие в 1-ю логическую страницу — полностью убрать из потока печати */
            .tdr-source-row-off {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                min-height: 0 !important;
                max-height: 0 !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                border-width: 0 !important;
            }

            footer {
                position: static !important;
                width: 100%;
                max-width: var(--print-footer-width);
                margin-top: 0.5rem;
                text-align: center;
                font-size: var(--print-footer-font-size);
                background-color: #fff;
                padding: var(--print-footer-padding);
            }

            .container {
                max-height: none;
                overflow: visible;
            }
        }

        /* Скрываем строки сверх лимита на экране тоже */
        .print-hide-row {
            display: none !important;
        }

        /* Строки не для первой логической страницы (см. JS tdrFormApplyTableRowLimits) */
        .tdr-source-row-off {
            display: none !important;
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
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
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

        .process-text-long {
            font-size: 0.9em;
            line-height: 1;
            letter-spacing: -0.3px;
            display: inline-block;
            transform-origin: left;

        }
        .description-text-long {
            font-size: 0.9rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-black {
            color: #000;
        }

        .fs-7 {
            font-size: 0.9rem;
        }
        .fs-75 {
            font-size: 0.8rem;
        }
        .fs-85 {
            font-size: 0.85rem;
        }
        .fs-8 {
            font-size: 0.7rem;
        }

        .details-row {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 36px;
        }
        .description-text-long {
            font-size: 0.9rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: top;
        }
        .header-page .component-name-value { font-size: var(--component-name-font-size, 12px) !important; }
        .header-page .component-name-value[data-long="1"] { line-height: 1.1; letter-spacing: -0.3px; }
        /* ITEM No. — уменьшенный межстрочный интервал */
        .data-row > div:first-child,
        .table-header .row > div:first-child { line-height: 1.1; }
        .details-cell {
            display: flex;
            justify-content: center;
            align-items: center;
            line-height: 1.2;
        }
    </style>
</head>
<body>
<!-- Кнопки для печати и настроек -->
<div class="text-start m-1 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        ⚙️ Print Settings
    </button>
</div>
@php
    $stress_table_pages = $stress_table_pages ?? [[]];
    $stress_total_pages = max(1, count($stress_table_pages));
    $stressGlobalRowIndex = 1;
@endphp
@foreach($stress_table_pages as $stressPageIndex => $stressPageRows)
@php
    $stress_page_num = $stressPageIndex + 1;
@endphp
<div class="container-fluid {{ $stress_page_num === 1 ? 'tdr-primary-sheet' : 'dynamic-page-wrapper' }}">
    <div class="page data-page {{ $stress_page_num === 1 ? 'tdr-primary-logical-page' : '' }}" data-page-index="{{ $stress_page_num }}">
    <div class="header-page">
        <div class="row">
            <div class="col-3">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 180px; margin: 6px 10px 0;">
            </div>
            <div class="col-9">
                <h2 class="mt-3 text-black"><strong>STRESS RELIEF PROCESS SHEET</strong></h2>
            </div>
        </div>
        <div class="row ">
            <div class="col-6">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>
                            <span class="component-name-value" @if(strlen($current_wo->description) > 30) data-long="1" @endif>{{$current_wo->description}}</span>
                        </strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>PART NUMBER:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>{{$current_wo->unit->part_number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>WORK ORDER No:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>W{{$current_wo->number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>SERIAL No:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>{{$current_wo->serial_number}}</strong></div>
                </div>
            </div>
            <div class="col-6">
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>DATE:</strong></div>
                    <div class="col-8 pt-2 border-b"></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>RO No:</strong></div>
                    <div class="col-8 pt-2 border-b">INTERNAL</div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>VENDOR:</strong></div>
                    <div class="col-8 pt-2 border-b"><strong> AVIATECHNIK</strong></div>
                </div>
                {{-- TOTAL QTY: при раскомментировании вернуть полный .row с закрывающим </div> --}}
            </div>

        </div>
            <div class="row">
                <div class="col-6"></div>
                <div class="col-3 text-end pe-2 pt-3">
                    <strong>
                        MANUAL REF:
                    </strong>

                </div>
                <div class="col-3 border-all text-center" style="height: 55px">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h6 class="text-center mt-3"> <strong> {{substr($manual->number, 0, 8)}} </strong></h6>
                        @endif
                    @endforeach
                </div>
            </div>
           <h5 class="ps-3 mt-2 mb-2 ">
               @foreach($manuals as $manual)
                   @if($manual->id == $current_wo->unit->manual_id)
                       <h6 class="ps-4">
                           <strong class="">
                           {{__('Perform the Stress Relief as specified under Process No. and in accordance with SMM No. ')}}

                          </strong>
                       </h6>
                   @endif
               @endforeach
           </h5>
    </div>

    <div class="table-header">
        <div class="row mt-2">
            <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7">
                    <strong>ITEM No.</strong></h6></div>
            <div class="col-2 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PART No.</strong></h6></div>
            <div class="col-2 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>DESCRIPTION</strong></h6></div>
            <div class="col-4 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PROCESS No.</strong></h6></div>
            <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>QTY</strong></h6></div>
            <div class="col-2 border-all pt-2  details-row  text-center" style="height: 42px">
                <h6  class="fs-7" ><strong>PERFORMED</strong> </h6>
            </div>
        </div>
    </div>

    {{-- Страницы и строки на странице задаются на серверe ($stress_table_pages) --}}
    <div class="all-rows-container">
        @foreach($stressPageRows as $stressEntry)
            @if(($stressEntry['kind'] ?? '') === 'manual')
                <div class="row fs-85 data-row manual-row" data-row-index="{{ $stressGlobalRowIndex }}">
                    <div class="col-12 border-l-b-r details-cell text-center" style="min-height: 34px; font-weight: bold;">
                        <strong>{{ $stressEntry['text'] ?? '' }}</strong>
                    </div>
                </div>
                @php $stressGlobalRowIndex++; @endphp
            @elseif(($stressEntry['kind'] ?? '') === 'data')
                @php $component = $stressEntry['component']; @endphp
                <div class="row fs-85 data-row" data-row-index="{{ $stressGlobalRowIndex }}">
                    <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">
                        {{ $component->ipl_num }}
                    </div>
                    <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                        {{ $component->part_number }}
                    </div>
                    <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                        {{ $component->name }}
                    </div>
                    <div class="col-4 border-l-b details-cell text-center process-cell" style="min-height: 34px">
                        {{ $component->process_name }}
                    </div>
                    <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">
                        {{ $component->qty }}
                    </div>
                    <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px">
                    </div>
                </div>
                @php $stressGlobalRowIndex++; @endphp
            @else
                <div class="row fs-85 data-row empty-row">
                    <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px"></div>
                    <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px"></div>
                    <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px"></div>
                    <div class="col-4 border-l-b details-cell text-center" style="min-height: 34px"></div>
                    <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px"></div>
                    <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px"></div>
                </div>
            @endif
        @endforeach
    </div>

    <footer>
        <div class="row fs-85" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__('Form # 015')}}
            </div>
            <div class="col-3 text-center">
                {{__('Page')}} <span class="page-number" data-tdr-footer-page>{{ $stress_page_num }}</span> {{__('of')}} <span class="total-pages" data-tdr-footer-total>{{ $stress_total_pages }}</span>
            </div>
            <div class="col-3 text-end pe-4">
                {{__('Rev#0, 15/Dec/2012   ')}}
                <br>
                {{'Total: '}} {{ $stressSum['total_qty'] }}
            </div>
        </div>
    </footer>
    </div>
</div>
@endforeach

@php $tdrFormConfig = config('tdr_forms.stressFormStd'); @endphp
@include('shared.tdr-forms._print-settings-modal', ['formType' => 'stressFormStd', 'formConfig' => $tdrFormConfig])

<!-- Bootstrap JS для работы модального окна -->
<script>
    if (typeof window.bootstrapLoaded === 'undefined') {
        window.bootstrapLoaded = true;
        const script = document.createElement('script');
        script.src = "{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}";
        script.async = true;
        document.head.appendChild(script);
    }
</script>

<script>
    {{-- Лимит строк / страницы формируются в PHP ($stress_table_pages). Опционально: ?stress_table_rows=19 --}}
    window.tdrFormApplyTableRowLimits = function () {};
</script>
<script src="{{ asset('js/tdrs/forms/ndt-std/chartjs-patcher.js') }}"></script>
@include('components.session-heartbeat-config')
<script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
@include('shared.tdr-forms._scripts', ['formType' => 'stressFormStd', 'formConfig' => $tdrFormConfig])
<!-- table-height-adjuster.js отключен - управление строками через Print Settings -->

<!-- Общие модули -->
<script src="{{ asset('js/tdrs/forms/common/multi-page-handler.js') }}"></script>

<!-- Переиспользуемые модули из tdr-processes -->
<script src="{{ asset('js/tdr-processes/processes-form/row-manager.js') }}"></script>

<!-- Модули для Stress Relief формы -->
<script src="{{ asset('js/tdrs/forms/stress/stress-row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/stress/stress-form-main.js') }}"></script>
</body>
</html>
