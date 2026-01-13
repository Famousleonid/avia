<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R&M Record</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        :root {
            --print-page-margin: 2mm 2mm 2mm 2mm;
            --print-body-width: 100%;
            --print-body-height: 90%;
            --container-max-width: 820px;
            --print-footer-width: 100%;
            --print-footer-font-size: 10px;
            --print-footer-padding: 1px 1px;
        }

        .container-fluid {
            max-width: var(--container-max-width, 820px);
            height: auto;
            /*transform: scale(0.8);*/
            transform-origin: top left;
            padding: 3px;
            margin-left: 10px;
            margin-right: 10px;
        }


        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                /*size: letter ;*/
                size: Letter;
                margin: var(--print-page-margin, 2mm);
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: auto;
                width: auto;
                margin-left: 3px;
                padding: 0;
            }


            .container-fluid {
                max-height: calc(100vh - 20px); /* Оставляем место для футера */
                overflow: hidden;
                margin: 0 !important;
                padding: 3px !important;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print{
                display: none;
            }
            /* Уменьшаем отступы между секциями */
            .row {
                margin-bottom: 0 !important;
            }
            /* Колонтитул внизу страницы */
            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: var(--print-footer-width, 100%);
                text-align: center;
                font-size: var(--print-footer-font-size, 10px);
                background-color: #fff;
                padding: var(--print-footer-padding, 1px 1px);
                margin: 0;
            }

            /*!* Уменьшаем отступы в таблицах *!*/
            /*.div1, .div2, .div3, .div4, .div31, .div32, .div33, .div34, .div35, .div36 {*/
            /*    padding-top: 2px !important;*/
            /*}*/

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
            border-left: 8px  solid lightgrey;
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
        .fs-9 {
            font-size: 0.9rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-7 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.75rem; /* или любое другое подходящее значение */
        }
        .fs-4 {
            font-size: 0.4rem; /* или любое другое подходящее значение */
        }

        /* Минимальный межстрочный интервал в строках таблицы R&M */
        .parent .data-row {
            line-height: 1; /* можно уменьшить до 0.95, если визуально будет нормально */
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
        .page-break {
            /* Начинать новый лист перед элементом (для 2-й и последующих страниц) */
            page-break-before: always;
            break-before: page;
        }



        .title {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(1, 1fr);
            gap: 0px;
        }


        .div2 {
            grid-column: span 3 / span 3;
        }

        .div3 {
            grid-column-start: 5;
        }


        .parent {
            display: grid;
            /*grid-template-columns: repeat(12, 1fr);*/
            grid-template-columns: .6fr 2.7fr 1fr 3fr 1fr 1fr 3fr ;
            /*grid-template-rows: repeat(5, .5fr);*/
            gap: 0;
        }






        .qc_stamp {
            display: grid;
            grid-template-columns: 3.3fr 4fr 1fr 4fr;
            /*grid-template-columns: repeat(12, 1fr);*/
            grid-template-rows: repeat(1, 1fr);
            gap: 0px;
        }










    </style>
</head>
<body>
<!-- Кнопки для печати и настроек -->
<div class="text-start m-3">
    <button class="btn btn-outline-primary no-print" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary ms-2 no-print" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        ⚙️ Print Settings
    </button>
</div>
@php
    // Подготовка технических заметок (новый формат: простой список строк)
    $technicalNotesList = [];
    if (!empty($technicalNotes) && is_array($technicalNotes)) {
        // Если сохранены как ассоциативный массив note1..noteN — берём значения
        $technicalNotesList = array_values($technicalNotes);
    }

    // Пагинация только для Technical Notes: по 7 строк на страницу
    $notesPerPage = 7;
    $totalNotes = count($technicalNotesList);
    $totalNotesPages = max(1, (int)ceil($totalNotes / $notesPerPage));

    // Все записи rmRecords - используем значение по умолчанию из Print Settings (18 строк на страницу)
    $rmRecordsCollection = $rmRecords ?? collect();
    $totalDataCount = $rmRecordsCollection->count();
    $rmTableRowsPerPage = 18; // Значение по умолчанию из Print Settings

    // Распределяем rmRecords по страницам в зависимости от лимита строк
    // JavaScript потом может перераспределить в зависимости от настроек Print Settings
    $rmRecordsPages = [];
    $currentPageRecords = [];
    $currentPageRowCount = 0;

    foreach ($rmRecordsCollection as $record) {
        // Если текущая страница заполнена, начинаем новую
        if ($currentPageRowCount >= $rmTableRowsPerPage) {
            $rmRecordsPages[] = $currentPageRecords;
            $currentPageRecords = [];
            $currentPageRowCount = 0;
        }

        $currentPageRecords[] = $record;
        $currentPageRowCount++;
    }

    // Добавляем последнюю страницу, если есть записи
    if (!empty($currentPageRecords)) {
        $rmRecordsPages[] = $currentPageRecords;
    }

    // Если записей нет, создаем пустую страницу
    if (empty($rmRecordsPages)) {
        $rmRecordsPages = [[]];
    }

    // Общее количество страниц = максимум из страниц Notes и страниц rmRecords
    $totalPages = max($totalNotesPages, count($rmRecordsPages));

    // Глобальные индексы для нумерации строк
    $globalRowIndex = 1; // Индекс для строк с данными (1..N)
    $globalJsIndex = 1;  // JS индекс (data-row-index) для всех строк
@endphp

@for($pageIndex = 0; $pageIndex < $totalPages; $pageIndex++)
<div class="container-fluid {{ $pageIndex > 0 ? 'page-break' : '' }}">


    <div class="title">

        <div class="div1">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 140px">
        </div>
        <div class="div2">
            <h5 class="pt-3  text-black text-center"><strong>Repair and Modification Record WO#</strong></h5>

        </div>
        <div class="div3 pt-3 border-all text-center mb-2">
            <h4>
                    <strong>W{{$current_wo->number}}</strong>
            </h4>
        </div>
    </div>

    {{-- Технические заметки: по 7 строк на страницу --}}
    <div class="row border-all-b  m-sm-0">
        <h5 class="ps-1 fs-9">Technical Notes:</h5>
        @php
            $notesStartIndex = $pageIndex * $notesPerPage;
        @endphp
        @for($i = 0; $i < $notesPerPage; $i++)
            @php
                $noteValue = $technicalNotesList[$notesStartIndex + $i] ?? '';
            @endphp
            <div class="border-b pt-2" style="height: 30px">{{ $noteValue }}</div>
        @endfor
    </div>
    <p></p>

    {{-- Таблица с данными rmRecords: распределяем по страницам в зависимости от лимита строк --}}
    @php
        $pageRecords = $rmRecordsPages[$pageIndex] ?? [];
    @endphp

    @if(!empty($pageRecords) || $pageIndex === 0)
    <div class="parent mt-3" data-page-index="{{$pageIndex}}" data-rm-records-page="{{$pageIndex}}">
        <div class="div11 border-l-t-b text-center align-content-center fs-75" >Item</div>
        <div class="div12 border-l-t-b text-center align-content-center fs-75">Part Description</div>
        <div class="div13 border-l-t-b text-center align-content-center fs-75">Modification or Repair #</div>
        <div class="div14 border-l-t-b text-center align-content-center fs-75">Description of Modification  or
            Repair</div>
        <div class="div15 border-l-t-b text-center align-content-center fs-75">Previously Carried out</div>
        <div class="div16 border-l-t-b text-center align-content-center fs-75">Carried out by AT</div>
        <div class="div17 border-all text-center align-content-center fs-75">Identification Method</div>

        {{-- Отображаем записи rmRecords для текущей страницы --}}
        {{-- Пустые строки будут добавлены JavaScript в зависимости от Print Settings --}}
        @foreach($pageRecords as $rmRecord)
            @php
                $jsIndex = $globalJsIndex++;
                    $displayIndex = $globalRowIndex++;
            @endphp
            <div class="div11 border-l-b text-center align-content-center fs-75 data-row" style="min-height: 37px" data-row-index="{{$jsIndex}}">{{$displayIndex}}</div>
            <div class="div12 border-l-b text-center align-content-center fs-75 data-row" data-row-index="{{$jsIndex}}">{{ $rmRecord->part_description ?? '' }}</div>
            <div class="div13 border-l-b text-center align-content-center fs-75 data-row" data-row-index="{{$jsIndex}}">{{ $rmRecord->mod_repair ?? '' }}</div>
            <div class="div14 border-l-b text-center align-content-center fs-75 data-row" data-row-index="{{$jsIndex}}">{{ $rmRecord->description ?? '' }}</div>
            <div class="div15 border-l-b text-center align-content-center fs-75 data-row" style="color: lightgray" data-row-index="{{$jsIndex}}">tech stamp</div>
            <div class="div16 border-l-b text-center align-content-center fs-75 data-row" style="color: lightgray" data-row-index="{{$jsIndex}}">tech stamp</div>
            <div class="div17 border-l-b-r text-center align-content-center fs-75 data-row" data-row-index="{{$jsIndex}}">{{ $rmRecord->ident_method ?? '' }}</div>
        @endforeach
    </div>
    @endif

    {{-- QC Stamp блок только на последней странице --}}
    @if($pageIndex === $totalPages - 1)
    <div class="qc_stamp mt-1">
        <div class="div21" style="min-height: 37px"></div>
        <div class="div22 border-all text-end align-content-center pe-1 fs-8" >Quality Assurance Acceptance </div>
        <div class="div23 border-t-r-b text-center align-content-center fs-8" style="color: lightgray">Q.C. stamp</div>
        <div class="div24 border-t-r-b text-center  pt-4  fs-8" style="color: lightgray">Date</div>
    </div>
    @endif

    {{-- Футер на каждой странице --}}
    <footer>
    <div class="d-flex justify-content-between" style=" padding: 1px 1px;">
        <div class=" ms-1"
{{--             style="font-size: 10px"--}}
        >
            {{__("Form #005")}}
        </div>
        <div class=" text-end pe-4 ">
            {{__('Rev#0, 15/Dec/2012   ')}}
        </div>
    </div>
</footer>

</div>
@endfor


<!-- Модальное окно настроек печати -->
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">
                    ⚙️ Print Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <!-- Table Setting - Основная группа (не collapse) -->
                    <div class="mb-4">
                        <h5 class="mb-3" data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Настройки количества строк в таблице R&M Record. Строки сверх лимита скрываются при печати. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-ru="Настройки количества строк в таблице R&M Record. Строки сверх лимита скрываются при печати. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-en="R&M Record table row settings. Rows exceeding the limit are hidden when printing. Settings are applied automatically on page load.">
                            📊 Tables
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="rmTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблице R&M Record на одной странице. По умолчанию: 18 строк. Используется для всех страниц формы."
                                        data-tooltip-ru="Максимальное количество строк в таблице R&M Record на одной странице. По умолчанию: 18 строк. Используется для всех страниц формы."
                                        data-tooltip-en="Maximum number of rows in R&M Record table per page. Default: 18 rows. Used for all pages of the form.">
                                    RM Table Rows (per page)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="rmTableRows" name="rmTableRows"
                                           min="1" max="100" step="1" value="18">
                                </div>
                            </div>
                        </div>

                        <!-- Table Setting (collapse) -->
                        <div class="accordion mb-3" id="tableSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="tableSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#tableSettingsCollapse" aria-expanded="false"
                                            aria-controls="tableSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="Дополнительные настройки таблицы: ширина контейнера."
                                              data-tooltip-ru="Дополнительные настройки таблицы: ширина контейнера."
                                              data-tooltip-en="Additional table settings: container width.">
                                            Table Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="tableSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="tableSettingsHeading" data-bs-parent="#tableSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMaxWidth" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 820px для R&M Record формы."
                                                        data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 820px для R&M Record формы."
                                                        data-tooltip-en="Maximum width of the table container in pixels. Recommended value: 820px for R&M Record form.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                           min="500" max="2000" step="10" value="820">
                                                </div>
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
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="Настройки страницы: ширина, высота, поля и отступы. Влияют на отступы при печати и позиционирование контента."
                                              data-tooltip-ru="Настройки страницы: ширина, высота, поля и отступы. Влияют на отступы при печати и позиционирование контента."
                                              data-tooltip-en="Page settings: width, height, margins and padding. Affect print margins and content positioning.">
                                            Page Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="pageSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="pageSettingsHeading" data-bs-parent="#pageSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="bodyWidth" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Ширина основного контента в процентах от ширины страницы. 100% - стандартное значение для R&M Record формы."
                                                        data-tooltip-ru="Ширина основного контента в процентах от ширины страницы. 100% - стандартное значение для R&M Record формы."
                                                        data-tooltip-en="Main content width as percentage of page width. 100% - standard value for R&M Record form.">
                                                    Width (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyWidth" name="bodyWidth"
                                                           min="50" max="110" step="1" value="100">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="bodyHeight" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Высота основного контента в процентах от высоты страницы. 90% - стандартное значение для R&M Record формы."
                                                        data-tooltip-ru="Высота основного контента в процентах от высоты страницы. 90% - стандартное значение для R&M Record формы."
                                                        data-tooltip-en="Main content height as percentage of page height. 90% - standard value for R&M Record form.">
                                                    Height (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyHeight" name="bodyHeight"
                                                           min="50" max="100" step="1" value="90">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="pageMargin" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Отступ от краев страницы при печати. Рекомендуемое значение: 2mm для R&M Record формы. Увеличьте, если контент обрезается принтером."
                                                        data-tooltip-ru="Отступ от краев страницы при печати. Рекомендуемое значение: 2mm для R&M Record формы. Увеличьте, если контент обрезается принтером."
                                                        data-tooltip-en="Margin from page edges when printing. Recommended value: 2mm for R&M Record form. Increase if content is cut off by the printer.">
                                                    Margin (mm)
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="pageMargin" name="pageMargin"
                                                           placeholder="2mm 2mm 2mm 2mm" value="2mm 2mm 2mm 2mm">
                                                </div>
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
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="Настройки нижнего колонтитула формы. Колонтитул содержит номер формы и ревизию."
                                              data-tooltip-ru="Настройки нижнего колонтитула формы. Колонтитул содержит номер формы и ревизию."
                                              data-tooltip-en="Form footer settings. Footer contains form number and revision.">
                                            Footer Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="footerSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="footerSettingsHeading" data-bs-parent="#footerSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="footerWidth" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Ширина колонтитула в процентах. 100% - стандартное значение для R&M Record формы."
                                                        data-tooltip-ru="Ширина колонтитула в процентах. 100% - стандартное значение для R&M Record формы."
                                                        data-tooltip-en="Footer width as percentage. 100% - standard value for R&M Record form.">
                                                    Width (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="50" max="100" step="1" value="100">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerFontSize" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Размер шрифта текста в колонтитуле. 10px - стандартное значение. Увеличьте для лучшей читаемости."
                                                        data-tooltip-ru="Размер шрифта текста в колонтитуле. 10px - стандартное значение. Увеличьте для лучшей читаемости."
                                                        data-tooltip-en="Footer text font size. 10px - standard value. Increase for better readability.">
                                                    Font Size (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerFontSize" name="footerFontSize"
                                                           min="6" max="20" step="0.5" value="10">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerPadding" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '1px 1px' означает 1px сверху/снизу и 1px слева/справа."
                                                        data-tooltip-ru="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '1px 1px' означает 1px сверху/снизу и 1px слева/справа."
                                                        data-tooltip-en="Footer inner padding in CSS format (vertical horizontal). Example: '1px 1px' means 1px top/bottom and 1px left/right.">
                                                    Padding
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                           placeholder="1px 1px" value="1px 1px">
                                                </div>
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

<!-- Print Settings: Управление количеством строк осуществляется через Print Settings -->
<!-- table-height-adjuster.js отключен для rmRecordForm -->

<!-- JavaScript для Print Settings -->
<script>
    // Ключ для сохранения настроек печати
    const PRINT_SETTINGS_KEY = 'rmRecordForm_print_settings';

    // Настройки по умолчанию
    const defaultSettings = {
        pageMargin: '2mm 2mm 2mm 2mm',
        bodyWidth: '100%',
        bodyHeight: '90%',
        containerMaxWidth: '820px',
        footerWidth: '100%',
        footerFontSize: '10px',
        footerPadding: '1px 1px',
        rmTableRows: '18'
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
                    return element.value + (suffix ? suffix : '');
                }
                return defaultValue;
            };

            const settings = {
                pageMargin: getValue('pageMargin', '2mm 2mm 2mm 2mm', ''),
                bodyWidth: getValue('bodyWidth', '100', '%'),
                bodyHeight: getValue('bodyHeight', '90', '%'),
                containerMaxWidth: getValue('containerMaxWidth', '820', 'px'),
                footerWidth: getValue('footerWidth', '100', '%'),
                footerFontSize: getValue('footerFontSize', '10', 'px'),
                footerPadding: getValue('footerPadding', '1px 1px', ''),
                rmTableRows: getValue('rmTableRows', '18', '')
            };

            localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);

            // Убираем фокус с активного элемента перед закрытием модального окна
            if (document.activeElement && document.activeElement.blur) {
                document.activeElement.blur();
            }

            // Закрываем модальное окно
            const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
            if (modal) {
                modal.hide();
            }

            alert('Settings saved successfully!');
        } catch (e) {
            console.error('Ошибка сохранения настроек:', e);
            alert('Error saving settings');
        }
    };

    // Применение CSS переменных
    function applyPrintSettings(settings) {
        const root = document.documentElement;
        root.style.setProperty('--print-page-margin', settings.pageMargin || defaultSettings.pageMargin);
        root.style.setProperty('--print-body-width', settings.bodyWidth || defaultSettings.bodyWidth);
        root.style.setProperty('--print-body-height', settings.bodyHeight || defaultSettings.bodyHeight);
        root.style.setProperty('--container-max-width', settings.containerMaxWidth || defaultSettings.containerMaxWidth);
        root.style.setProperty('--print-footer-width', settings.footerWidth || defaultSettings.footerWidth);
        root.style.setProperty('--print-footer-font-size', settings.footerFontSize || defaultSettings.footerFontSize);
        root.style.setProperty('--print-footer-padding', settings.footerPadding || defaultSettings.footerPadding);

        const rmMaxRows = parseInt(settings.rmTableRows) || 18;

        // Перераспределяем строки по страницам в зависимости от настроек
        redistributeRowsToPages(rmMaxRows);
    }

    // Перераспределение строк по страницам в зависимости от настроек Print Settings
    function redistributeRowsToPages(rmMaxRows) {
        // Собираем все строки с данными из всех таблиц
        const allTables = document.querySelectorAll('.parent[data-rm-records-page]');
        const allDataRows = [];

        // Сначала собираем все строки с данными (не пустые)
        allTables.forEach(function(table) {
            const rows = Array.from(table.querySelectorAll('.data-row[data-row-index]'));
            const rowGroups = {};

            // Группируем ячейки по индексу строки
            rows.forEach(cell => {
                const index = parseInt(cell.getAttribute('data-row-index'));
                if (!isNaN(index) && index > 0) {
                    if (!rowGroups[index]) {
                        rowGroups[index] = [];
                    }
                    rowGroups[index].push(cell);
                }
            });

            // Проверяем, является ли строка пустой
            Object.keys(rowGroups).sort((a, b) => parseInt(a) - parseInt(b)).forEach(function(index) {
                const cells = rowGroups[index];
                const firstCell = cells[0];
                const isEmpty = firstCell && firstCell.classList.contains('empty-row');

                if (!isEmpty) {
                    // Это строка с данными
                    allDataRows.push({
                        index: parseInt(index),
                        cells: cells,
                        table: table
                    });
                }
            });
        });

        // Сортируем строки с данными по индексу
        allDataRows.sort((a, b) => a.index - b.index);

        // Удаляем все строки из всех таблиц (включая пустые)
        allTables.forEach(function(table) {
            const allRows = table.querySelectorAll('.data-row[data-row-index]');
            allRows.forEach(row => row.remove());
        });

        // Получаем все страницы
        const allPages = document.querySelectorAll('.container-fluid');

        // Распределяем строки с данными по страницам
        let currentPageIndex = 0;
        let currentPageDataRowCount = 0;
        let currentPageTable = null;

        allDataRows.forEach(function(rowData) {
            const isLastPage = (currentPageIndex === allPages.length - 1);
            // На последней странице лимит на 1 меньше (чтобы поместился QC Stamp)
            const pageMaxRows = isLastPage ? (rmMaxRows - 1) : rmMaxRows;

            // Если текущая страница заполнена (достигнут лимит), переходим на следующую
            if (currentPageDataRowCount >= pageMaxRows) {
                // Если есть еще страницы, переходим на следующую
                if (currentPageIndex < allPages.length - 1) {
                    currentPageIndex++;
                    currentPageDataRowCount = 0;
                    currentPageTable = null;
                } else {
                    // Если страниц больше нет, оставляем строки на последней странице
                    // В forEach нельзя использовать break, поэтому просто прекращаем обработку
                    return;
                }
            }

            // Находим или создаем таблицу на текущей странице
            if (!currentPageTable || currentPageDataRowCount === 0) {
                currentPageTable = allPages[currentPageIndex].querySelector('.parent[data-rm-records-page]');

                // Если таблицы нет, создаем её
                if (!currentPageTable) {
                    currentPageTable = createTableOnPage(allPages[currentPageIndex], currentPageIndex);
                }
            }

            // Перемещаем ячейки строки в целевую таблицу
            rowData.cells.forEach(function(cell) {
                currentPageTable.appendChild(cell);
            });

            currentPageDataRowCount++;
        });

        // Добавляем пустые строки до лимита ТОЛЬКО на последней странице
        // На остальных страницах пустые строки НЕ добавляются
        const lastPageIndex = allPages.length - 1;
        const lastPage = allPages[lastPageIndex];
        const lastPageTable = lastPage.querySelector('.parent[data-rm-records-page]');

        console.log('Last page index:', lastPageIndex);
        console.log('Last page table found:', !!lastPageTable);

        if (lastPageTable) {
            // Убеждаемся, что таблица видима
            if (lastPageTable.style.display === 'none') {
                lastPageTable.style.display = '';
            }

            const rows = lastPageTable.querySelectorAll('.data-row[data-row-index]');
            const rowGroups = {};

            // Группируем ячейки по индексу строки
            rows.forEach(cell => {
                const index = parseInt(cell.getAttribute('data-row-index'));
                if (!isNaN(index) && index > 0) {
                    if (!rowGroups[index]) {
                        rowGroups[index] = [];
                    }
                    rowGroups[index].push(cell);
                }
            });

            const currentRowCount = Object.keys(rowGroups).length;

            // На последней странице: rmMaxRows - 1 (чтобы поместился QC Stamp)
            const targetRowCount = rmMaxRows - 1;

            console.log('=== Last Page: Adding Empty Rows ===');
            console.log('Current data rows:', currentRowCount);
            console.log('Target rows (rmMaxRows - 1):', targetRowCount);
            console.log('Empty rows to add:', targetRowCount - currentRowCount);

            // Если строк меньше целевого количества, добавляем пустые строки
            if (currentRowCount < targetRowCount) {
                const maxItemNumber = getMaxItemNumberInTable(lastPageTable);
                const emptyRowsToAdd = targetRowCount - currentRowCount;
                console.log('Adding', emptyRowsToAdd, 'empty rows to last page. Max item number:', maxItemNumber);
                addEmptyRowsToTable(lastPageTable, emptyRowsToAdd, maxItemNumber);

                // Проверяем результат
                setTimeout(function() {
                    const finalRows = lastPageTable.querySelectorAll('.data-row[data-row-index]');
                    const finalRowGroups = {};
                    finalRows.forEach(cell => {
                        const index = parseInt(cell.getAttribute('data-row-index'));
                        if (!isNaN(index) && index > 0) {
                            if (!finalRowGroups[index]) {
                                finalRowGroups[index] = [];
                            }
                            finalRowGroups[index].push(cell);
                        }
                    });
                    const finalRowCount = Object.keys(finalRowGroups).length;
                    console.log('✅ Last page - Final rows:', finalRowCount, '(should be', targetRowCount + ')');
                }, 100);
            } else {
                console.log('Last page already has enough rows:', currentRowCount);
            }
        } else {
            console.error('❌ Last page table not found!');
            console.error('Last page element:', lastPage);
            console.error('All pages:', allPages.length);
        }

        // Скрываем пустые таблицы на страницах, где нет строк
        allTables.forEach(function(table) {
            const rows = table.querySelectorAll('.data-row[data-row-index]');
            if (rows.length === 0) {
                const pageIndex = parseInt(table.getAttribute('data-rm-records-page'));
                if (pageIndex > 0) {
                    table.style.display = 'none';
                }
            } else {
                table.style.display = '';
            }
        });

        console.log('=== Print Settings: Redistributing rows ===');
        console.log('RM Table Rows per page:', rmMaxRows);
        console.log('Total data rows:', allDataRows.length);
        console.log('Total pages:', allPages.length);

        // Проверяем количество строк на каждой странице ДО добавления пустых
        allPages.forEach(function(page, idx) {
            const pageTable = page.querySelector('.parent[data-rm-records-page]');
            if (pageTable) {
                const rows = pageTable.querySelectorAll('.data-row[data-row-index]');
                const rowGroups = {};
                rows.forEach(cell => {
                    const index = parseInt(cell.getAttribute('data-row-index'));
                    if (!isNaN(index) && index > 0) {
                        if (!rowGroups[index]) {
                            rowGroups[index] = [];
                        }
                        rowGroups[index].push(cell);
                    }
                });
                const rowCount = Object.keys(rowGroups).length;
                const isLast = (idx === allPages.length - 1);
                console.log('Page', idx + 1, (isLast ? '(LAST)' : ''), '- Data rows:', rowCount, 'Target:', (isLast ? rmMaxRows - 1 : rmMaxRows));
            }
        });
    }

    // Создание таблицы на странице, если её нет
    function createTableOnPage(pageElement, pageIndex) {
        // Создаем заголовок таблицы
        const table = document.createElement('div');
        table.className = 'parent mt-3';
        table.setAttribute('data-page-index', pageIndex);
        table.setAttribute('data-rm-records-page', pageIndex);

        // Создаем заголовки колонок
        const headers = [
            { class: 'div11', text: 'Item', borderClass: 'border-l-t-b' },
            { class: 'div12', text: 'Part Description', borderClass: 'border-l-t-b' },
            { class: 'div13', text: 'Modification or Repair #', borderClass: 'border-l-t-b' },
            { class: 'div14', text: 'Description of Modification  or Repair', borderClass: 'border-l-t-b' },
            { class: 'div15', text: 'Previously Carried out', borderClass: 'border-l-t-b' },
            { class: 'div16', text: 'Carried out by AT', borderClass: 'border-l-t-b' },
            { class: 'div17', text: 'Identification Method', borderClass: 'border-all' }
        ];

        headers.forEach(function(header) {
            const div = document.createElement('div');
            div.className = header.class + ' ' + header.borderClass + ' text-center align-content-center fs-75';
            div.textContent = header.text;
            table.appendChild(div);
        });

        // Вставляем таблицу после Technical Notes
        const notesDiv = pageElement.querySelector('.row.border-all-b');
        if (notesDiv) {
            // Вставляем после блока с Technical Notes и <p></p>
            const pTag = notesDiv.nextElementSibling;
            if (pTag && pTag.tagName === 'P') {
                pageElement.insertBefore(table, pTag.nextSibling);
            } else {
                pageElement.insertBefore(table, notesDiv.nextSibling);
            }
        } else {
            pageElement.appendChild(table);
        }

        return table;
    }

    // Добавление пустых строк в таблицу
    function addEmptyRowsToTable(table, count, startItemNumber) {
        for (let i = 0; i < count; i++) {
            const itemNumber = startItemNumber + i + 1;
            addEmptyRowToTable(table, itemNumber);
        }
    }

    // Функция для получения максимального номера Item в таблице
    function getMaxItemNumberInTable(table) {
        const itemCells = table.querySelectorAll('.div11.data-row');
        let maxItemNumber = 0;
        itemCells.forEach(cell => {
            const num = parseInt((cell.textContent || '').trim(), 10);
            if (!isNaN(num) && num > maxItemNumber) {
                maxItemNumber = num;
            }
        });
        return maxItemNumber;
    }

    // Функция для добавления пустой строки в таблицу
    function addEmptyRowToTable(table, itemNumber) {
        // Генерируем уникальный индекс для строки
        const allRows = table.querySelectorAll('.data-row[data-row-index]');
        let maxIndex = 0;
        allRows.forEach(row => {
            const index = parseInt(row.getAttribute('data-row-index'));
            if (!isNaN(index) && index > maxIndex) {
                maxIndex = index;
            }
        });
        const rowIndex = maxIndex + 1;

        const div11 = document.createElement('div');
        div11.className = 'div11 border-l-b text-center align-content-center fs-75 data-row empty-row';
        div11.style.minHeight = '37px';
        div11.setAttribute('data-row-index', rowIndex);
        div11.textContent = itemNumber;

        const div12 = document.createElement('div');
        div12.className = 'div12 border-l-b text-center align-content-center fs-75 data-row empty-row';
        div12.setAttribute('data-row-index', rowIndex);

        const div13 = document.createElement('div');
        div13.className = 'div13 border-l-b text-center align-content-center fs-75 data-row empty-row';
        div13.setAttribute('data-row-index', rowIndex);

        const div14 = document.createElement('div');
        div14.className = 'div14 border-l-b text-center align-content-center fs-75 data-row empty-row';
        div14.setAttribute('data-row-index', rowIndex);

        const div15 = document.createElement('div');
        div15.className = 'div15 border-l-b text-center align-content-center fs-75 data-row empty-row';
        div15.style.color = 'lightgray';
        div15.setAttribute('data-row-index', rowIndex);
        div15.textContent = 'tech stamp';

        const div16 = document.createElement('div');
        div16.className = 'div16 border-l-b text-center align-content-center fs-75 data-row empty-row';
        div16.style.color = 'lightgray';
        div16.setAttribute('data-row-index', rowIndex);
        div16.textContent = 'tech stamp';

        const div17 = document.createElement('div');
        div17.className = 'div17 border-l-b-r text-center align-content-center fs-75 data-row empty-row';
        div17.setAttribute('data-row-index', rowIndex);

        // Добавляем все ячейки в таблицу
        table.appendChild(div11);
        table.appendChild(div12);
        table.appendChild(div13);
        table.appendChild(div14);
        table.appendChild(div15);
        table.appendChild(div16);
        table.appendChild(div17);
    }


    // Сброс настроек к значениям по умолчанию
    window.resetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            const settings = defaultSettings;
            applyPrintSettings(settings);
            loadSettingsToForm(settings);

            const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
            if (modal) {
                modal.hide();
            }

            alert('Settings reset to default values!');
        }
    };

    // Загрузка настроек в форму
    function loadSettingsToForm(settings) {
        const elements = {
            'pageMargin': { suffix: '', default: '2mm 2mm 2mm 2mm' },
            'bodyWidth': { suffix: '', default: '100' },
            'bodyHeight': { suffix: '', default: '90' },
            'containerMaxWidth': { suffix: '', default: '820' },
            'footerWidth': { suffix: '', default: '100' },
            'footerFontSize': { suffix: '', default: '10' },
            'footerPadding': { suffix: '', default: '1px 1px' },
            'rmTableRows': { suffix: '', default: '18' }
        };

        Object.keys(elements).forEach(function(id) {
            const element = document.getElementById(id);
            if (element) {
                const value = settings[id] || elements[id].default;
                if (id === 'pageMargin' || id === 'footerPadding') {
                    element.value = value;
                } else {
                    element.value = parseInt(value) || elements[id].default;
                }
            }
        });
    }

    // Инициализация при загрузке страницы
    window.addEventListener('load', function() {
        // Небольшая задержка, чтобы убедиться, что DOM полностью загружен
        setTimeout(function() {
            const settings = loadPrintSettings();
            console.log('Initializing Print Settings:', settings);
            applyPrintSettings(settings);
            loadSettingsToForm(settings);
        }, 100);

        // Загружаем настройки в форму при открытии модального окна
        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                const currentSettings = loadPrintSettings();
                loadSettingsToForm(currentSettings);
            });
        }
    });
</script>


</body>
</html>

