<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDR Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        :root {
            --container-max-width: 940px;
            --container-padding: 5px;
            --container-margin-left: 10px;
            --container-margin-right: 10px;
            --print-page-margin: 2mm;
            --print-body-height: 86%;
            --print-body-width: 98%;
            --print-body-margin-left: 3px;
            --print-footer-width: 800px;
            --print-footer-font-size: 10px;
            --print-footer-padding: 3px 3px;
            --tdr-form-rows: 19;
            /* Строки сетки TDR: только текст масштабируется; иконки как при ячейке 40px */
            --tdr-grid-font-size: 11px;
            --tdr-grid-line-height: 1.15;
            --tdr-grid-text-padding-left: 0px;
            --tdr-grid-text-padding-right: 0px;
        }

        .tdr-row p.tdr-grid-text {
            font-size: var(--tdr-grid-font-size);
            line-height: var(--tdr-grid-line-height);
            margin: 0;
            padding-left: var(--tdr-grid-text-padding-left);
            padding-right: var(--tdr-grid-text-padding-right);
            box-sizing: border-box;
        }

        .tdr-row .tdr-grid-reqs {
            height: 26px;
            width: auto;
        }

        .tdr-row .tdr-grid-reqs-bb {
            height: 40px;
            width: auto;
            object-fit: contain;
            margin-left: -16px;
        }

        /* Bootstrap img { max-width: 100% } иначе режет ширину до col-1 — width в CSS «не работает» */
        img.tdr-grid-check {
            width: 32px;
            height: auto;
            max-width: none;
            flex-shrink: 0;

        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: var(--container-max-width);
            height: 99%;
            padding: var(--container-padding);
            margin-left: var(--container-margin-left);
            margin-right: var(--container-margin-right);
        }

        /* Скрываем строки сверх лимита */
        .print-hide-row {
            display: none !important;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: letter;
                margin: var(--print-page-margin);
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: var(--print-body-height);
                width: var(--print-body-width);
                margin-left: var(--print-body-margin-left);
                padding: 0;
            }

            /* Отключаем разрывы страниц внутри элементов */
            table, h1, p {
                page-break-inside: avoid;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print {
                display: none;
            }

            /* Колонтитул внизу страницы */
            footer {
                position: fixed;
                bottom: 0;
                width: var(--print-footer-width);
                text-align: center;
                font-size: var(--print-footer-font-size);
                background-color: #fff;
                padding: var(--print-footer-padding);
            }

            /* Обрезка контента и размещение на одной странице */
            .container {
                max-height: 100vh;
                overflow: hidden;
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
            font-size: 0.9rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-9 {
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
    </style>
</head>

<body>
    <!-- Кнопка для печати -->
    <div class="text-start m-1 no-print">
        <button class="btn btn-outline-primary" onclick="window.print()">
            Print Form
        </button>
        <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
            ⚙️ Print Settings
        </button>
    </div>

<div class="container-fluid">
    <div class="row">
        <div class="col-4">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 120px; margin: 6px 10px 0;">
        </div>
        <div class="col-8">
            <h5 class="pt-3 text-black text-"><strong>WORK ORDER TEAR DOWN REPORT</strong></h5>
        </div>

    </div>

    <div class="row" style="height: 30px">
        <div class="col-5 pt-2">
            <p class="fs-6 text-end " >COMPONENT DESCRPTION:</p>
        </div>
        <div class="col-5 border-all pt-1" style="height: 32px">
            <h5 class="">
            <strong> {{$current_wo->description}}</strong>
            </h5>
        </div>
        <div class="col-2 border-t-r-b" style="height: 32px" >
            <h5 class="pt-1">
               <strong> W{{$current_wo->number}}</strong>
            </h5>
        </div>
    </div>

    <div class="row" style="height: 32px">
        <div class="col-5 pt-1" style="height: 32px">
            <p class="fs-6 text-end ">COMPONENT PART NO.:</p>
        </div>
        <div class="col-5 pt-1 border-l-b-r" style="height: 32px" >
            <h5 class="">
            <strong> {{$current_wo->unit->part_number}}</strong>
            </h5>
        </div>
    </div>

        <div class="row mt-2 mb-1" >
            <div class="col-6" style="height: 32px">
                <div class="row" >
                    <div class="col-1" style="height: 32px"></div>
                    <div class="col-10 border-all-b" style="height: 32px">
                        <p class="fs-7 pt-1">
                            <strong>TEARDOWN INSPECTION & CONDITION:</strong>
                        </p>
                    </div>
                    <div class="col-1"></div>
                </div>
            </div>
            <div class="col-6 border-all-b" style="height: 32px">
                <p class="fs-7 pt-1">
                    <strong>TEARDOWN INSPECTION & CONDITION:</strong>
                </p>
            </div>
        </div>
        <div class="row  border-all-b" style="height: 38px">
            <div class="col-5">
                <p class="fs-7 text-end"><strong>ATTENTION PRODUCTION DEPARTMENT:</strong> </p>
            </div>
            <div class="col-7">
                <p class="fs-8  ">MAKE SURE TO ADD INFORMATION FROM WO COWER SHEET TO IDENTIFY PRELIMINARY INSPECTION
                    DETAILS FOR STRIP REPORT</p>
            </div>
        </div>
    <div class="row " >
        <div class="col-6">
            <div class="row " >
                <div class="col-1 border-l-b" style="height: 36px">
                    <img class="pt-1 ps-1" src="{{ asset('img/icons/reqs.png') }}" alt="reqs" style="height: 28px; margin-left:
                            -10px" >
                </div>
                <div class="col-10 border-ll-bb">
                    <p class="fs-8" style="margin-left: -10px">CUSTOMER SNAG CONFIRMED ?</p>
                </div>
                <div class="col-1 border-bb"></div>
            </div>
        </div>
        <div class="col-6">
            <div class="row">
                <div class="col-11 border-bb" style="height: 36px">
                    <p class="fs-5"  style="text-transform: uppercase;"><strong>{{$current_wo->instruction->name}}</strong></p>
                </div>
                <div class="col-1 border-ll-bb-rr">
                    <img src="{{ asset('img/icons/check.svg') }}" alt="Check"
                         style="width: 32px; margin-left: -12px">
                </div>
            </div>
        </div>
    </div>
    <div class="row " >
        <div class="col-6">
            <div class="row " >
                <div class="col-1 border-l-b align-items-center justify-content-center" style="height: 36px">
                     <img class="pt-1 ps-1" src="{{ asset('img/icons/reqs.png') }}" alt="reqs" style="height: 28px; margin-left:
                            -10px" >
                </div>
                <div class="col-10 border-ll-bb">
                    <p class="fs-8" style="margin-left: -10px">CUSTOMER SNAG <strong>NOT</strong> CONFIRMED ?</p>
                </div>
                <div class="col-1 border-bb"></div>
            </div>
        </div>
        <div class="col-6">
            <div class="row">
                <div class="col-1 border-bb"></div>
                <div class="col-10 border-bb" style="height: 36px"></div>
                <div class="col-1 border-ll-bb-rr">
{{--                    {{count($tdrInspections)}}--}}
                </div>
            </div>
        </div>
    </div>
    @php
        // Количество строк для каждого столбца (по умолчанию 19, настраивается через Print Settings)
        $totalRows = 19;
        $totalInspections = count($tdrInspections);
        $maxRowsPerPage = $totalRows * 2; // Максимум элементов на странице (2 столбца)
    @endphp

    <div class="all-rows-container">
        @php
            // Создаём страницы только если элементов больше, чем помещается на одну страницу
            $pageNumber = 1;
            $currentIndex = 0;
            $totalPages = $totalInspections > $maxRowsPerPage ? ceil($totalInspections / $maxRowsPerPage) : 1;
        @endphp

        @while($currentIndex < $totalInspections)
            @if($pageNumber > 1)
                <div style="page-break-before: always;"></div>
            @endif

            <div class="page data-page" data-page-index="{{ $pageNumber }}">
                @php
                    $pageItems = array_slice($tdrInspections, $currentIndex, $maxRowsPerPage);
                    $firstColumn = array_slice($pageItems, 0, $totalRows);
                    $secondColumn = array_slice($pageItems, $totalRows, $totalRows);
                @endphp

                @for ($i = 0; $i < $totalRows; $i++)
                    <div class="row tdr-row" data-row-index="{{ $i }}" data-page="{{ $pageNumber }}">
                        <div class="col-6 first-column">
                            <div class="row">
                                <div class="col-1 border-l-b-r d-flex align-items-center" style="height: 40px">
                                    <img class=" tdr-grid-reqs" src="{{ asset('img/icons/reqs.png') }}" alt="reqs"
                                         >
                                </div>
                                <div class="col-10 border-b d-flex align-items-start" style="height: 40px">
                                    <p class="tdr-grid-text pt-1">{!! strtoupper($firstColumn[$i] ?? '') !!}</p>
                                </div>
                                <div class="col-1 border-l-b d-flex align-items-start">
                                    @if(isset($firstColumn[$i]) && $firstColumn[$i] !== '')
                                        <img class="tdr-grid-check pt-1" src="{{ asset('img/icons/check.svg') }}" alt="Check"
                                             style="margin-left: -16px;">
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6 second-column">
                            <div class="row">
                                <div class="d-flex col-1 border-b-r align-items-center" style="height: 40px">
                                    <img class="tdr-grid-reqs-bb" src="{{ asset('img/icons/reqs_bb.png') }}" alt="reqs">
                                    <img class=" tdr-grid-reqs" src="{{ asset('img/icons/reqs.png') }}" alt="reqs">
                                </div>
                                <div class="col-10 border-b d-flex align-items-start" style="height: 40px">
                                    <p class="tdr-grid-text pt-1">{!! strtoupper($secondColumn[$i] ?? '') !!}</p>
                                </div>
                                <div class="col-1 border-l-b-r d-flex align-items-start" style="height: 40px">
                                    @if(isset($secondColumn[$i]) && $secondColumn[$i] !== '')
                                        <img class="tdr-grid-check pt-1" src="{{ asset('img/icons/check.svg') }}" alt="Check" style="margin-left: -16px">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor

                <footer>
                    <div class="row" style="width: 100%; padding: 5px 0;">
                        <div class="col-6 text-start">
                            {{__("Form #003")}}
                        </div>
                        <div class="col-6 text-end pe-4">
                            {{__('Rev#0, 15/Dec/2012')}}@if($totalPages > 1) | Page {{ $pageNumber }} of {{ $totalPages }}@endif
                        </div>
                    </div>
                </footer>
            </div>

            @php
                $currentIndex += $maxRowsPerPage;
                $pageNumber++;
            @endphp
        @endwhile
    </div>

    <!-- Print Settings Modal -->
    <div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header justify-content-between">
                    <h5 class="modal-title" id="printSettingsModalLabel">⚙️ Print Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="printSettingsForm">
                        <!-- Tables - Основная группа -->
                        <div class="mb-4">
                            <h5 class="mb-3">📊 Tables</h5>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="tdrFormRows" class="form-label">
                                        TDR Form Table (row)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="tdrFormRows" name="tdrFormRows"
                                               min="1" max="100" step="1" value="19">
                                    </div>
                                    <small class="form-text text-muted">Количество строк на столбец. По умолчанию: 19</small>
                                </div>
                            </div>

                            <!-- Table Setting (collapse) -->
                            <div class="accordion mb-3" id="tableSettingsAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="tableSettingsHeading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#tableSettingsCollapse" aria-expanded="false"
                                                aria-controls="tableSettingsCollapse">
                                            Table Setting
                                        </button>
                                    </h2>
                                    <div id="tableSettingsCollapse" class="accordion-collapse collapse"
                                         aria-labelledby="tableSettingsHeading" data-bs-parent="#tableSettingsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="containerMaxWidth" class="form-label">Max Width (px)</label>
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                           min="500" max="2000" step="10" value="940">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="containerPadding" class="form-label">Padding (px)</label>
                                                    <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                           min="0" max="50" step="1" value="5">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="containerMarginLeft" class="form-label">Left Margin (px)</label>
                                                    <input type="number" class="form-control" id="containerMarginLeft" name="containerMarginLeft"
                                                           min="0" max="50" step="1" value="10">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="containerMarginRight" class="form-label">Right Margin (px)</label>
                                                    <input type="number" class="form-control" id="containerMarginRight" name="containerMarginRight"
                                                           min="0" max="50" step="1" value="10">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="tdrGridFontSize" class="form-label">TDR grid — font size (px)</label>
                                                    <input type="number" class="form-control" id="tdrGridFontSize" name="tdrGridFontSize"
                                                           min="6" max="16" step="0.5" value="11">
                                                    <small class="form-text text-muted">Только текст в ячейке; иконки и галочки фиксированы (36 / 40 / 36 px).</small>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="tdrGridLineHeight" class="form-label">TDR grid — line height</label>
                                                    <input type="number" class="form-control" id="tdrGridLineHeight" name="tdrGridLineHeight"
                                                           min="1" max="1.6" step="0.05" value="1.15">
                                                    <small class="form-text text-muted">Межстрочный интервал текста (множитель, без единиц).</small>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="tdrGridTextPaddingLeft" class="form-label">TDR text — padding left (px)</label>
                                                    <input type="number" class="form-control" id="tdrGridTextPaddingLeft" name="tdrGridTextPaddingLeft"
                                                           min="0" max="24" step="1" value="0">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="tdrGridTextPaddingRight" class="form-label">TDR text — padding right (px)</label>
                                                    <input type="number" class="form-control" id="tdrGridTextPaddingRight" name="tdrGridTextPaddingRight"
                                                           min="0" max="24" step="1" value="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Page Setting (collapse) -->
                        <div class="mb-4">
                            <div class="accordion" id="pageSettingsAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="pageSettingsHeading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#pageSettingsCollapse" aria-expanded="false"
                                                aria-controls="pageSettingsCollapse">
                                            Page Setting
                                        </button>
                                    </h2>
                                    <div id="pageSettingsCollapse" class="accordion-collapse collapse"
                                         aria-labelledby="pageSettingsHeading" data-bs-parent="#pageSettingsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="bodyWidth" class="form-label">Width (%)</label>
                                                    <input type="number" class="form-control" id="bodyWidth" name="bodyWidth"
                                                           min="50" max="100" step="1" value="98">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="bodyHeight" class="form-label">Height (%)</label>
                                                    <input type="number" class="form-control" id="bodyHeight" name="bodyHeight"
                                                           min="50" max="100" step="1" value="86">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="pageMargin" class="form-label">Margin (mm)</label>
                                                    <input type="number" class="form-control" id="pageMargin" name="pageMargin"
                                                           min="0" max="50" step="0.5" value="2">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="bodyMarginLeft" class="form-label">Left Margin (px)</label>
                                                    <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft"
                                                           min="0" max="50" step="1" value="3">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Setting (collapse) -->
                        <div class="mb-4">
                            <div class="accordion" id="footerSettingsAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="footerSettingsHeading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#footerSettingsCollapse" aria-expanded="false"
                                                aria-controls="footerSettingsCollapse">
                                            Footer Setting
                                        </button>
                                    </h2>
                                    <div id="footerSettingsCollapse" class="accordion-collapse collapse"
                                         aria-labelledby="footerSettingsHeading" data-bs-parent="#footerSettingsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="footerWidth" class="form-label">Width on pg (px)</label>
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="400" max="1200" step="10" value="800">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="footerFontSize" class="form-label">Font Size (px)</label>
                                                    <input type="number" class="form-control" id="footerFontSize" name="footerFontSize"
                                                           min="6" max="20" step="0.5" value="10">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="footerPadding" class="form-label">Padding</label>
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                           placeholder="3px 3px" value="3px 3px">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="resetPrintSettings()">Reset to Default</button>
                    <button type="button" class="btn btn-primary" onclick="savePrintSettings()">Save Settings</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Скрипт для печати и Print Settings -->
    <script>
        const PRINT_SETTINGS_KEY = 'tdrForm_print_settings';

        // Настройки по умолчанию
        const defaultSettings = {
            tdrFormRows: 19,
            pageMargin: '2mm',
            bodyWidth: '98%',
            bodyHeight: '86%',
            bodyMarginLeft: '3px',
            containerMaxWidth: '800px',
            containerPadding: '5px',
            containerMarginLeft: '10px',
            containerMarginRight: '10px',
            footerWidth: '800px',
            footerFontSize: '10px',
            footerPadding: '3px 3px',
            tdrGridFontSize: '9px',
            tdrGridLineHeight: '1.15',
            tdrGridTextPaddingLeft: '0px',
            tdrGridTextPaddingRight: '0px'
        };

        // Загрузка настроек из localStorage
        function loadPrintSettings() {
            const saved = localStorage.getItem(PRINT_SETTINGS_KEY);
            if (saved) {
                try {
                    return JSON.parse(saved);
                } catch (e) {
                    console.error('Ошибка загрузки настроек:', e);
                    return defaultSettings;
                }
            }
            return defaultSettings;
        }

        // Сохранение настроек в localStorage
        window.savePrintSettings = function() {
            try {
                const getValue = function(id, defaultValue, suffix = '') {
                    const element = document.getElementById(id);
                    if (element) {
                        return element.value + suffix;
                    }
                    return defaultValue;
                };

                const settings = {
                    tdrFormRows: getValue('tdrFormRows', '19', ''),
                    pageMargin: getValue('pageMargin', '2', 'mm'),
                    bodyWidth: getValue('bodyWidth', '98', '%'),
                    bodyHeight: getValue('bodyHeight', '86', '%'),
                    bodyMarginLeft: getValue('bodyMarginLeft', '3', 'px'),
                    containerMaxWidth: getValue('containerMaxWidth', '940', 'px'),
                    containerPadding: getValue('containerPadding', '5', 'px'),
                    containerMarginLeft: getValue('containerMarginLeft', '10', 'px'),
                    containerMarginRight: getValue('containerMarginRight', '10', 'px'),
                    footerWidth: getValue('footerWidth', '800', 'px'),
                    footerFontSize: getValue('footerFontSize', '10', 'px'),
                    footerPadding: getValue('footerPadding', '3px 3px', ''),
                    tdrGridFontSize: (function() {
                        const el = document.getElementById('tdrGridFontSize');
                        if (el && el.value !== '') {
                            return el.value + 'px';
                        }
                        return defaultSettings.tdrGridFontSize;
                    })(),
                    tdrGridLineHeight: (function() {
                        const el = document.getElementById('tdrGridLineHeight');
                        return el && el.value !== '' ? String(el.value) : defaultSettings.tdrGridLineHeight;
                    })(),
                    tdrGridTextPaddingLeft: (function() {
                        const el = document.getElementById('tdrGridTextPaddingLeft');
                        if (el && el.value !== '') {
                            return el.value + 'px';
                        }
                        return defaultSettings.tdrGridTextPaddingLeft;
                    })(),
                    tdrGridTextPaddingRight: (function() {
                        const el = document.getElementById('tdrGridTextPaddingRight');
                        if (el && el.value !== '') {
                            return el.value + 'px';
                        }
                        return defaultSettings.tdrGridTextPaddingRight;
                    })()
                };

                localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
                applyPrintSettings(settings);
                applyTdrFormRowLimits(settings);

                // Закрываем модальное окно
                const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
                if (modal) {
                    modal.hide();
                }

                showNotification('Settings saved successfully!', 'success');
            } catch (e) {
                console.error('Ошибка сохранения настроек:', e);
                showNotification('Error saving settings', 'error');
            }
        };

        // Применение CSS переменных
        function applyPrintSettings(settings) {
            const root = document.documentElement;
            root.style.setProperty('--print-page-margin', settings.pageMargin || defaultSettings.pageMargin);
            root.style.setProperty('--print-body-width', settings.bodyWidth || defaultSettings.bodyWidth);
            root.style.setProperty('--print-body-height', settings.bodyHeight || defaultSettings.bodyHeight);
            root.style.setProperty('--print-body-margin-left', settings.bodyMarginLeft || defaultSettings.bodyMarginLeft);
            root.style.setProperty('--container-max-width', settings.containerMaxWidth || defaultSettings.containerMaxWidth);
            root.style.setProperty('--container-padding', settings.containerPadding || defaultSettings.containerPadding);
            root.style.setProperty('--container-margin-left', settings.containerMarginLeft || defaultSettings.containerMarginLeft);
            root.style.setProperty('--container-margin-right', settings.containerMarginRight || defaultSettings.containerMarginRight);
            root.style.setProperty('--print-footer-width', settings.footerWidth || defaultSettings.footerWidth);
            root.style.setProperty('--print-footer-font-size', settings.footerFontSize || defaultSettings.footerFontSize);
            root.style.setProperty('--print-footer-padding', settings.footerPadding || defaultSettings.footerPadding);
            root.style.setProperty('--tdr-grid-font-size', settings.tdrGridFontSize || defaultSettings.tdrGridFontSize);
            root.style.setProperty('--tdr-grid-line-height', settings.tdrGridLineHeight || defaultSettings.tdrGridLineHeight);
            root.style.setProperty('--tdr-grid-text-padding-left', settings.tdrGridTextPaddingLeft || defaultSettings.tdrGridTextPaddingLeft);
            root.style.setProperty('--tdr-grid-text-padding-right', settings.tdrGridTextPaddingRight || defaultSettings.tdrGridTextPaddingRight);
        }

        // Применение лимитов строк для TDR формы с поддержкой многостраничности
        function applyTdrFormRowLimits(settings) {
            const tdrFormRows = parseInt(settings.tdrFormRows) || 19;
            const maxRowsPerPage = tdrFormRows * 2; // Максимум элементов на странице

            // Получаем все элементы из Blade
            const allInspections = @json($tdrInspections);
            const totalInspections = allInspections.length;

            // Удаляем все существующие страницы
            const container = document.querySelector('.all-rows-container');
            container.innerHTML = '';

            // Создаём страницы
            let currentIndex = 0;
            let pageNumber = 1;

            while (currentIndex < totalInspections) {
                if (pageNumber > 1) {
                    const pageBreak = document.createElement('div');
                    pageBreak.style.pageBreakBefore = 'always';
                    container.appendChild(pageBreak);
                }

                const page = document.createElement('div');
                page.className = 'page data-page';
                page.setAttribute('data-page-index', pageNumber);

                const pageItems = allInspections.slice(currentIndex, currentIndex + maxRowsPerPage);
                const firstColumn = pageItems.slice(0, tdrFormRows);
                const secondColumn = pageItems.slice(tdrFormRows, tdrFormRows * 2);

                // Создаём строки для страницы
                for (let i = 0; i < tdrFormRows; i++) {
                    const row = createTdrRow(i, pageNumber, firstColumn[i], secondColumn[i]);
                    page.appendChild(row);
                }

                // Добавляем footer для страницы
                const totalPages = Math.ceil(totalInspections / maxRowsPerPage);
                const footer = createTdrFooter(pageNumber, totalPages);
                page.appendChild(footer);

                container.appendChild(page);

                currentIndex += maxRowsPerPage;
                pageNumber++;
            }
        }

        // Создание строки TDR
        function createTdrRow(index, pageNumber, firstItem, secondItem) {
            const row = document.createElement('div');
            row.className = 'row tdr-row';
            row.setAttribute('data-row-index', index);
            row.setAttribute('data-page', pageNumber);

            row.innerHTML = `
                <div class="col-6 first-column">
                    <div class="row">
                        <div class="col-1 border-l-b-r d-flex align-items-center" style="height: 40px">
                            <img class="pt-1 ps-1 tdr-grid-reqs" src="{{ asset('img/icons/reqs.png') }}" alt="reqs" style="margin-left: -10px">
                        </div>
                        <div class="col-10 border-b d-flex align-items-start" style="height: 40px">
                            <p class="tdr-grid-text pt-1">${(firstItem || '').toUpperCase()}</p>
                        </div>
                        <div class="col-1 border-l-b d-flex align-items-start">
                            ${firstItem ? '<img class="tdr-grid-check pt-1" src="{{ asset("img/icons/check.svg") }}" alt="Check" style="margin-left: -14px;">' : ''}
                        </div>
                    </div>
                </div>
                <div class="col-6 second-column">
                    <div class="row">
                        <div class="d-flex col-1 border-b-r align-items-center" style="height: 40px">
                            <img class="tdr-grid-reqs-bb" src="{{ asset('img/icons/reqs_bb.png') }}" alt="reqs">
                            <img class="pt-1 ps-1 tdr-grid-reqs" src="{{ asset('img/icons/reqs.png') }}" alt="reqs">
                        </div>
                        <div class="col-10 border-b d-flex align-items-start" style="height: 40px">
                            <p class="tdr-grid-text pt-1">${(secondItem || '').toUpperCase()}</p>
                        </div>
                        <div class="col-1 border-l-b-r d-flex align-items-start" style="height: 40px">
                            ${secondItem ? '<img class="tdr-grid-check pt-1" src="{{ asset("img/icons/check.svg") }}" alt="Check" style="margin-left: -16px">' : ''}
                        </div>
                    </div>
                </div>
            `;

            return row;
        }

        // Обновление строки TDR
        function updateTdrRow(row, firstItem, secondItem) {
            const firstColText = row.querySelector('.first-column .col-10 p');
            const firstColCheck = row.querySelector('.first-column .col-1');
            const secondColText = row.querySelector('.second-column .col-10 p');
            const secondColCheck = row.querySelector('.second-column .col-1');

            if (firstColText) {
                firstColText.textContent = (firstItem || '').toUpperCase();
            }
            if (firstColCheck) {
                firstColCheck.innerHTML = firstItem ? '<img class="tdr-grid-check pt-1" src="{{ asset("img/icons/check.svg") }}" alt="Check" style="margin-left: -14px;">' : '';
            }

            if (secondColText) {
                secondColText.textContent = (secondItem || '').toUpperCase();
            }
            if (secondColCheck) {
                secondColCheck.innerHTML = secondItem ? '<img class="tdr-grid-check pt-1" src="{{ asset("img/icons/check.svg") }}" alt="Check" style="margin-left: -16px">' : '';
            }
        }

        // Создание footer для страницы
        function createTdrFooter(pageNumber, totalPages) {
            const footer = document.createElement('footer');
            footer.innerHTML = `
                <div class="row" style="width: 100%; padding: 5px 0;">
                    <div class="col-6 text-start">Form #003</div>
                    <div class="col-6 text-end pe-4">Rev#0, 15/Dec/2012 | Page ${pageNumber}${totalPages > 1 ? ' of ' + totalPages : ''}</div>
                </div>
            `;
            return footer;
        }

        // Загрузка настроек в форму
        function loadSettingsToForm(settings) {
            const elements = {
                'tdrFormRows': { suffix: '', default: '19' },
                'pageMargin': { suffix: '', default: '2' },
                'bodyWidth': { suffix: '', default: '98' },
                'bodyHeight': { suffix: '', default: '86' },
                'bodyMarginLeft': { suffix: '', default: '3' },
                'containerMaxWidth': { suffix: '', default: '940' },
                'containerPadding': { suffix: '', default: '5' },
                'containerMarginLeft': { suffix: '', default: '10' },
                'containerMarginRight': { suffix: '', default: '10' },
                'footerWidth': { suffix: '', default: '800' },
                'footerFontSize': { suffix: '', default: '10' },
                'footerPadding': { suffix: '', default: '3px 3px' },
                'tdrGridFontSize': { suffix: '', default: '11' },
                'tdrGridLineHeight': { suffix: '', default: '1.15' },
                'tdrGridTextPaddingLeft': { suffix: '', default: '0' },
                'tdrGridTextPaddingRight': { suffix: '', default: '0' }
            };

            Object.keys(elements).forEach(function(id) {
                const element = document.getElementById(id);
                if (element) {
                    const value = settings[id] || elements[id].default;
                    if (id === 'pageMargin') {
                        element.value = value.replace('mm', '');
                    } else if (id === 'bodyWidth') {
                        element.value = value.replace('%', '');
                    } else if (id === 'bodyHeight') {
                        element.value = value.replace('%', '');
                    } else if (id.includes('Margin') || id.includes('Width') || id.includes('Padding') || id.includes('FontSize') || id === 'tdrGridFontSize' || id === 'tdrGridTextPaddingLeft' || id === 'tdrGridTextPaddingRight') {
                        element.value = value.replace('px', '').replace('mm', '');
                    } else if (id === 'tdrGridLineHeight') {
                        element.value = String(value);
                    } else {
                        element.value = value;
                    }
                }
            });
        }

        // Сброс настроек
        window.resetPrintSettings = function() {
            if (confirm('Reset all print settings to default values?')) {
                localStorage.removeItem(PRINT_SETTINGS_KEY);
                applyPrintSettings(defaultSettings);
                applyTdrFormRowLimits(defaultSettings);
                loadSettingsToForm(defaultSettings);
                showNotification('Settings reset to default!', 'success');
            }
        };

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const settings = loadPrintSettings();
            applyPrintSettings(settings);
            applyTdrFormRowLimits(settings);
            loadSettingsToForm(settings);

            // Загрузка настроек в форму при открытии модального окна
            const modal = document.getElementById('printSettingsModal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function() {
                    const currentSettings = loadPrintSettings();
                    loadSettingsToForm(currentSettings);
                });
            }
        });

        function printForm() {
            window.print();
        }
    </script>

<!-- Bootstrap JS для работы модального окна -->
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>
</div>
</body>
</html>
