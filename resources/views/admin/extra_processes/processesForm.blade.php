<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$process_name->process_sheet_name ?? $process_name->name ?? 'Extra Process'}} Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: var(--container-max-width, 920px);
            width: 100% !important;
            height: 98%;
            padding: var(--container-padding, 5px);
            margin-left: var(--container-margin-left, 10px);
            margin-right: var(--container-margin-right, 10px);
            position: relative; /* Для позиционирования футера */
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: letter;
                margin: var(--print-page-margin, 1mm);
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: var(--print-body-height, 99%);
                width: var(--print-body-width, 98%);
                margin-left: var(--print-body-margin-left, 2px);
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
                width: var(--print-footer-width, 800px);
                text-align: center;
                font-size: var(--print-footer-font-size, 10px);
                background-color: #fff;
                padding: var(--print-footer-padding, 1px 1px);
            }

            /* Обрезка контента и размещение на одной странице */
            .container {
                max-height: var(--print-container-max-height, 100vh);
                overflow: hidden;
            }

            /* Настройки для контейнера */
            .container-fluid {
                max-width: var(--print-container-max-width, 920px);
                width: 100% !important;
                padding: var(--print-container-padding, 5px);
                margin-left: var(--print-container-margin-left, 10px);
                margin-right: var(--print-container-margin-right, 10px);
            }

            /* Класс для скрытия строк при печати */
            .print-hide-row {
                display: none !important;
            }
        }

        /* Также скрываем строки в обычном режиме для предпросмотра */
        .print-hide-row {
            display: none !important;
        }

        /* Стили для модального окна настроек печати */
        .print-settings-modal .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .print-settings-modal .form-control {
            margin-bottom: 1rem;
        }
        .print-settings-modal .input-group-text {
            min-width: 60px;
            justify-content: center;
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

        /* Стили для длинного текста процесса */
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
        .fs-85 {
            font-size: 0.85rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-9 {
            font-size: 0.4rem; /* или любое другое подходящее значение */
        }

        .details-row {
            display: flex;
            justify-content: center;
            align-items: center; /* Выравнивание элементов по вертикали */
            height: 36px; /* Фиксированная высота строки */
        }
        .details-cell {
            /*flex-grow: 1; !* Позволяет колонкам растягиваться и занимать доступное пространство *!*/
            display: flex;
            justify-content: center; /* Центрирование содержимого по горизонтали */
            align-items: center; /* Центрирование содержимого по вертикали */
            /*border: 1px solid black; !* Границы для наглядности *!*/
        }
        .check-icon {
            width: 24px; /* Меньший размер изображения */
            height: auto;
            margin: 0 5px; /* Отступы вокруг изображения */
        }
    </style>
</head>
<body>
<!-- Кнопки для печати и настроек -->
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        ⚙️ Print Settings
    </button>
</div>

<!-- Модальное окно настроек печати -->
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printSettingsModalLabel">
                    ⚙️ Print Settings
                    <small class="text-muted d-block small mt-1">Настройки печати форм процессов</small>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="langToggleBtn" onclick="toggleTooltipLanguage()">
                        <span id="langToggleText">US</span>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <!-- Table Setting - Основная группа (не collapse) -->
                    <div class="mb-4">
                        <h5 class="mb-3" data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Настройки количества строк в таблицах. Строки сверх лимита скрываются при печати. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-ru="Настройки количества строк в таблицах. Строки сверх лимита скрываются при печати. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-en="Table row settings. Rows exceeding the limit are hidden when printing. Settings are applied automatically on page load.">
                            📊 Table Setting
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="ndtTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблице NDT (Non-Destructive Testing). По умолчанию: 16 строк. Используется для всех форм с типом процесса 'NDT'."
                                        data-tooltip-ru="Максимальное количество строк в таблице NDT (Non-Destructive Testing). По умолчанию: 16 строк. Используется для всех форм с типом процесса 'NDT'."
                                        data-tooltip-en="Maximum number of rows in NDT (Non-Destructive Testing) table. Default: 16 rows. Used for all forms with 'NDT' process type.">
                                    NDT Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="ndtTableRows" name="ndtTableRows"
                                           min="1" max="100" step="1" value="16">
                                    <span class="input-group-text">rows</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="stressTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблице Stress Relief (снятие напряжений). По умолчанию: 19 строк. Используется только для форм с типом процесса 'STRESS RELIEF'. Имеет отдельный лимит."
                                        data-tooltip-ru="Максимальное количество строк в таблице Stress Relief (снятие напряжений). По умолчанию: 19 строк. Используется только для форм с типом процесса 'STRESS RELIEF'. Имеет отдельный лимит."
                                        data-tooltip-en="Maximum number of rows in Stress Relief table. Default: 19 rows. Used only for forms with 'STRESS RELIEF' process type. Has a separate limit.">
                                    Stress Relief Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="stressTableRows" name="stressTableRows"
                                           min="1" max="100" step="1" value="19">
                                    <span class="input-group-text">rows</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="regularTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблицах других процессов (Machining, CAD, Anodizing и т.д.). По умолчанию: 19 строк. Используется для всех процессов, кроме NDT и Stress Relief."
                                        data-tooltip-ru="Максимальное количество строк в таблицах других процессов (Machining, CAD, Anodizing и т.д.). По умолчанию: 19 строк. Используется для всех процессов, кроме NDT и Stress Relief."
                                        data-tooltip-en="Maximum number of rows in other process tables (Machining, CAD, Anodizing, etc.). Default: 19 rows. Used for all processes except NDT and Stress Relief.">
                                    Other Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="regularTableRows" name="regularTableRows"
                                           min="1" max="100" step="1" value="19">
                                    <span class="input-group-text">rows</span>
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
                                              title="Дополнительные настройки таблицы: ширина, отступы и высота контейнера."
                                              data-tooltip-ru="Дополнительные настройки таблицы: ширина, отступы и высота контейнера."
                                              data-tooltip-en="Additional table settings: width, padding and container height.">
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
                                                        title="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 920px для extra processes. Увеличьте, если таблица слишком узкая."
                                                        data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 920px для extra processes. Увеличьте, если таблица слишком узкая."
                                                        data-tooltip-en="Maximum width of the table container in pixels. Recommended value: 920px for extra processes. Increase if the table is too narrow.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                           min="500" max="2000" step="10" value="920">
                                                    <span class="input-group-text">px</span>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerPadding" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Внутренние отступы контейнера (пространство между границей контейнера и содержимым). По умолчанию: 5px."
                                                        data-tooltip-ru="Внутренние отступы контейнера (пространство между границей контейнера и содержимым). По умолчанию: 5px."
                                                        data-tooltip-en="Container inner padding (space between container border and content). Default: 5px.">
                                                    Padding (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                           min="0" max="50" step="1" value="5">
                                                    <span class="input-group-text">px</span>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerMaxHeight" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Максимальная высота контейнера. 100vh - использует всю высоту экрана (рекомендуется). auto - автоматическая высота по содержимому."
                                                        data-tooltip-ru="Максимальная высота контейнера. 100vh - использует всю высоту экрана (рекомендуется). auto - автоматическая высота по содержимому."
                                                        data-tooltip-en="Maximum container height. 100vh - uses full screen height (recommended). auto - automatic height based on content.">
                                                    Max Height
                                                </label>
                                                <select class="form-control" id="containerMaxHeight" name="containerMaxHeight">
                                                    <option value="100vh">100vh (full height)</option>
                                                    <option value="90vh">90vh</option>
                                                    <option value="80vh">80vh</option>
                                                    <option value="70vh">70vh</option>
                                                    <option value="auto">auto (automatic)</option>
                                                </select>
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
                                              title="Настройки страницы: ширина, поля и отступы. Влияют на отступы при печати и позиционирование контента."
                                              data-tooltip-ru="Настройки страницы: ширина, поля и отступы. Влияют на отступы при печати и позиционирование контента."
                                              data-tooltip-en="Page settings: width, margins and padding. Affect print margins and content positioning.">
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
                                                        title="Ширина основного контента в процентах от ширины страницы. 98% - стандартное значение, оставляет небольшие поля по бокам."
                                                        data-tooltip-ru="Ширина основного контента в процентах от ширины страницы. 98% - стандартное значение, оставляет небольшие поля по бокам."
                                                        data-tooltip-en="Main content width as percentage of page width. 98% - standard value, leaves small margins on the sides.">
                                                    Width (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyWidth" name="bodyWidth"
                                                           min="50" max="100" step="1" value="98">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="pageMargin" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Отступ от краев страницы при печати. Рекомендуемое значение: 1mm. Увеличьте, если контент обрезается принтером."
                                                        data-tooltip-ru="Отступ от краев страницы при печати. Рекомендуемое значение: 1mm. Увеличьте, если контент обрезается принтером."
                                                        data-tooltip-en="Margin from page edges when printing. Recommended value: 1mm. Increase if content is cut off by the printer.">
                                                    Margin (mm)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="pageMargin" name="pageMargin"
                                                           min="0" max="50" step="0.5" value="1">
                                                    <span class="input-group-text">mm</span>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="bodyMarginLeft" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Горизонтальный отступ основного контента от левого края. Используется для точной настройки позиционирования."
                                                        data-tooltip-ru="Горизонтальный отступ основного контента от левого края. Используется для точной настройки позиционирования."
                                                        data-tooltip-en="Horizontal margin of main content from left edge. Used for precise positioning.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft"
                                                           min="0" max="50" step="1" value="2">
                                                    <span class="input-group-text">px</span>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginLeft" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Отступ контейнера с таблицей от левого края. Влияет на позиционирование таблиц на странице."
                                                        data-tooltip-ru="Отступ контейнера с таблицей от левого края. Влияет на позиционирование таблиц на странице."
                                                        data-tooltip-en="Table container margin from left edge. Affects table positioning on the page.">
                                                    Table Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMarginLeft" name="containerMarginLeft"
                                                           min="0" max="50" step="1" value="10">
                                                    <span class="input-group-text">px</span>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginRight" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Отступ контейнера с таблицей от правого края. Используется для симметричного выравнивания."
                                                        data-tooltip-ru="Отступ контейнера с таблицей от правого края. Используется для симметричного выравнивания."
                                                        data-tooltip-en="Table container margin from right edge. Used for symmetrical alignment.">
                                                    Table Right Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMarginRight" name="containerMarginRight"
                                                           min="0" max="50" step="1" value="10">
                                                    <span class="input-group-text">px</span>
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
                                              data-tooltip-ru="Настройки нижнего колонтитула формы. Колонтитул содержит номер формы, ревизию и общее количество компонентов."
                                              data-tooltip-en="Form footer settings. Footer contains form number, revision and total component count.">
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
                                                        data-tooltip-ru="Ширина колонтитула в пикселях. 800px - стандартное значение. Увеличьте, если текст в колонтитуле не помещается."
                                                        data-tooltip-en="Footer width in pixels. 800px - standard value. Increase if footer text doesn't fit.">
                                                    Width on pg (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="400" max="1200" step="10" value="800">
                                                    <span class="input-group-text">px</span>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerFontSize" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-tooltip-ru="Размер шрифта текста в колонтитуле. 10px - стандартное значение. Увеличьте для лучшей читаемости."
                                                        data-tooltip-en="Footer text font size. 10px - standard value. Increase for better readability.">
                                                    Font Size (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerFontSize" name="footerFontSize"
                                                           min="6" max="20" step="0.5" value="10">
                                                    <span class="input-group-text">px</span>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerPadding" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-tooltip-ru="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '3px 5px' означает 3px сверху/снизу и 5px слева/справа."
                                                        data-tooltip-en="Footer inner padding in CSS format (vertical horizontal). Example: '3px 5px' means 3px top/bottom and 5px left/right.">
                                                    Padding
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                           placeholder="1px 1px" value="1px 1px">
                                                    <span class="input-group-text">CSS</span>
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
<div class="container-fluid">
    <div class="header-page">
        <div class="row">
            <div class="col-3">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 140px; margin: 6px 10px 0;">
            </div>
            <div class="col-9">
                <h3 class="ms-4 mt-3 text-black text-">
                    <strong>{{$process_name->process_sheet_name ?? $process_name->name ??'EXTRA PROCESS'}} PROCESS SHEET</strong></h3>
            </div>
        </div>
        <div class="row">
            <div class="col-7">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 pt-2 border-b">
                        <strong>
                             <span @if(strlen($current_wo->description) > 20) class="description-text-long"
                                @endif>{{$current_wo->description}}</span>

{{--                            @if(isset($table_data) && count($table_data) > 1)--}}
{{--                                Multiple Components ({{ count($table_data) }} items)--}}
{{--                            @else--}}
{{--                                {{$component->name}}--}}
{{--                            @endif--}}
                        </strong>
                    </div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong> PART NUMBER:</strong></div>
                    <div class="col-6 pt-2 border-b">
                        <strong>
                            {{$current_wo->unit->part_number}}
{{--                            @if(isset($table_data) && count($table_data) > 1)--}}
{{--                                Various (see table below)--}}
{{--                            @else--}}
{{--                                {{$component->part_number}}--}}
{{--                            @endif--}}
                        </strong>
                    </div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>WORK ORDER No:</strong> </div>
                    <div class="col-6 pt-2 border-b"><strong>W{{$current_wo->number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>SERIAL No:</strong> </div>
                    <div class="col-6 pt-2 border-b"><strong>{{$current_wo->serial_number}}</strong></div>
                </div>
            </div>
            <div class="col-5">
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>DATE:</strong></div>
                    <div class="col-8 pt-2 border-b"></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>RO No:</strong></div>
                    <div class="col-8 pt-2 border-b"></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>VENDOR:</strong></div>
                    <div class="col-8 pt-2 border-b ">
                            <strong>
                                {{ $selectedVendor ? $selectedVendor->name : '' }}
                            </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($process_name->process_sheet_name == 'NDT')
        <div class="row mt-3">
            <div class="col-4">
                <div class="text-start "><strong>MAGNETIC PARTICLE AS PER:</strong></div>
                <div class="row " style="height: 26px">
                    <div class="col-1">#1</div>
                    <div class="col-11 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt1_name_id ?? null))
                                    <span @if(strlen($process->process) > 20) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="text-start"><strong>LIQUID/FLUID PENETRANT AS PER:</strong></div>
                <div class="row " style="height: 26px">
                    <div class="col-1">#4</div>
                    <div class="col-11 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt4_name_id ?? null))
                                    <span @if(strlen($process->process) > 20) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="text-start"><strong>ULTRASOUND AS PER:</strong></div>
                <div class="row " style="height: 26px">
                    <div class="col-1">#7</div>
                    <div class="col-11 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt7_name_id ?? null))
                                    <span @if(strlen($process->process) > 20) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-4 mt-3">
                <div class="row mt-2" style="height: 26px">
                    <div class="col-2">#2</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt2_name_id ?? null))
                                    <span @if(strlen($process->process) > 20) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="row mt-4" style="height: 26px">
                    <div class="col-2">#5</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt5_name_id ?? null))
                                    <span @if(strlen($process->process) > 25) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>

                </div>
                <div class="row mt-4" style="height: 26px">
                    <div class="col-2">#8</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt8_name_id ?? null))
                                    <span @if(strlen($process->process) > 25) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>

                </div>

            </div>
            <div class="col-4 mt-3">
                <div class="row mt-2" style="height: 26px">
                    <div class="col-2 text-end">#3</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt3_name_id ?? null))
                                    <span @if(strlen($process->process) > 20) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="text-start"><strong>EDDY CURRENT AS PER:</strong></div>
                <div class="row " style="height: 26px">
                    <div class="col-2 text-end">#6</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes))
                            @foreach($ndt_processes as $process)
                                @if($process->process_names_id == ($ndt6_name_id ?? null))
                                    <span @if(strlen($process->process) > 20) class="process-text-long"
                                        @endif>{{$process->process}}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="row  mt-2" style="height: 56px">
                    <div class=" col-4 text-end mt-4"><strong>CMM No:</strong></div>
                    <div class="col-8 border-all">
                        @foreach($manuals as $manual)
                            @if($manual->id == $current_wo->unit->manual_id)
                                <h6 class="text-center mt-3"><strong> {{substr($manual->number, 0, 8)}}</strong></h6>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="page table-header">
            <div class="row mt-2 ">
                <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-7">ITEM No.</h6></div>
                <div class="col-3 border-l-t-b details-row text-center"><h6  class="fs-7">Part No</h6> </div>
                <div class="col-3 border-l-t-b details-row text-center"><h6  class="fs-7">DESCRIPTION</h6></div>
                <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6  class="fs-75">PROCESS No.</h6> </div>
                <div class="col-1 border-l-t-b details-row  text-center"><h6  class="fs-7">QTY</h6> </div>
                <div class="col-1 border-l-t-b details-row  text-center"><h6  class="fs-7">ACCEPT</h6> </div>
                <div class="col-1 border-all details-row  text-center"><h6  class="fs-7">REJECT</h6> </div>
            </div>
        </div>
        <div class="page ndt-data-container">
            @php
                $totalRows = 16; // Общее количество строк
                $dataRows = isset($table_data) ? count($table_data) : 0; // Количество строк с данными
                $emptyRows = $totalRows - $dataRows; // Количество пустых строк
                $rowIndex = 1;
            @endphp

            @if(isset($table_data) && count($table_data) > 0)
                @foreach($table_data as $data)
                    <div class="row fs-85 data-row-ndt" data-row-index="{{ $rowIndex }}">
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                            {{ $data['component']->ipl_num }}
                        </div>
                        <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                            {{ $data['component']->part_number }}
                            @if($data['extra_process']->serial_num)
                                SN{{$data['extra_process']->serial_num}}
                            @endif
                        </div>
                        <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                            {{ $data['component']->name }}

                        </div>
                        <div class="col-2 border-l-b details-row text-center" style="height: 32px">
                            {{ substr($data['process_name']->name, -1) }}
                        </div>
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                            {{ $data['extra_process']->qty ?? 1 }}
                        </div>
                        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                            <!-- Пустая ячейка -->
                        </div>
                        <div class="col-1 border-l-b-r details-row text-center" style="height: 32px">
                        </div>
                    </div>
                    @php $rowIndex++; @endphp
                @endforeach
            @endif

            @for ($i = 0; $i < $emptyRows; $i++)
                <div class="row fs-85 data-row-ndt empty-row" data-row-index="{{ $rowIndex }}">
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b-r details-row text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                </div>
                @php $rowIndex++; @endphp
            @endfor
        </div>
    @else
        @if($process_name->process_sheet_name == 'STRESS RELIEF')

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

        @endif



                        <h6 class="mt-4 ms-3"><strong>
                    Perform the {{ ucwords(strtolower($process_name->process_sheet_name ?? $process_name->name ?? 'Extra Process')) }}
                    as the specified under Process No. and in
                    accordance with CMM No
                </strong>.</h6>



        <div class="page table-header">
            <div class="row mt-2 " >
                <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-7" ><strong> ITEM No.</strong></h6></div>
                <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6  class="fs-7" ><strong>PART No.</strong>
                    </h6>
                </div>
                <div class="col-3 border-l-t-b pt-2  details-row text-center"><h6  class="fs-7" ><strong>DESCRIPTION</strong>
                    </h6></div>
                <div class="col-3 border-l-t-b pt-2 details-row text-center"><h6  class="fs-7" ><strong>PROCESS No.</strong>
                    </h6> </div>
                <div class="col-1 border-l-t-b pt-2  details-row  text-center"><h6  class="fs-7" ><strong>QTY</strong> </h6>
                </div>

                @if($process_name->process_sheet_name == 'STRESS RELIEF')
                    <div class="col-2 border-all pt-2  details-row  text-center">
                        <h6  class="fs-7" ><strong>PERFORMED</strong> </h6>
                    </div>
                @else
                <div class="col-2 border-all pt-2  details-row  text-center"><h6  class="fs-7" ><strong>CMM No.</strong> </h6>

                </div>
                @endif
            </div>
        </div>

        <div class="page data-page">
            @php
                $totalRows = 19; // Общее количество строк
                $dataRows = isset($table_data) ? count($table_data) : 0; // Количество строк с данными
                $emptyRows = $totalRows - $dataRows; // Количество пустых строк
                $rowIndex = 1;
            @endphp

            @if(isset($table_data) && count($table_data) > 0)
                @foreach($table_data as $data)
                    <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}" @if($process_name->process_sheet_name == 'STRESS RELIEF') data-stress="true" @endif>
                        <div class="col-1 border-l-b details-cell text-center"  style="min-height: 34px">
                            {{ $data['component']->ipl_num }}
                        </div>
                        <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                            {{ $data['component']->part_number }}
                            @if($data['extra_process']->serial_num)
                                <br>S/N{{" "}}{{$data['extra_process']->serial_num}}
                            @endif
                        </div>
                        <div class="col-3 border-l-b details-cell text-center" style="min-height: 34px" >
                            {{ $data['component']->name }}
                        </div>
                        <div class="col-3 border-l-b details-cell text-center process-cell"  style="min-height: 34px">
                            @foreach($process_components as $component_process)
                                @if($component_process->id == ($data['process']->id ?? null))
                                    <span @if(strlen($component_process->process) > 40) class="process-text-long" @endif>{{$component_process->process}}</span>
                                @endif
                            @endforeach

                        </div>
                        <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px" >
                            {{ $data['extra_process']->qty ?? 1 }}
                        </div>
                        @if($process_name->process_sheet_name == 'STRESS RELIEF')
                            <div class="col-2 border-l-b-r details-cell text-center"  style="min-height: 34px"></div>
                        @else
                            <div class="col-2 border-l-b-r details-cell text-center"  style="min-height: 34px">
                                @if(isset($data['manual']) && $data['manual'])
                                    <h6 class="text-center mt-2">
                                        {{ substr($data['manual']->number, 0, 8) }}
                                    </h6>
                                @else
                                    @foreach($manuals as $manual)
                                        @if($manual->id == $current_wo->unit->manual_id)
                                            <h6 class="text-center mt-2">
                                                    {{substr($manual->number, 0, 8)}}
                                            </h6>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        @endif
                    </div>
                    @php $rowIndex++; @endphp
                @endforeach
            @endif

            @for ($i = 0; $i < $emptyRows; $i++)
                <div class="row empty-row data-row" data-row-index="{{ $rowIndex }}" @if($process_name->process_sheet_name == 'STRESS RELIEF') data-stress="true" @endif>
                    <div class="col-1 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-3 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b-r  text-center" style="height: 32px">
                        <!-- Пустая ячейка -->
                    </div>
                </div>
                @php $rowIndex++; @endphp
            @endfor
        </div>
    @endif
</div>
<footer>
    @php
        $totalQty = 0;
        if(isset($table_data)){
            foreach($table_data as $d){
                $totalQty += (int)($d['extra_process']->qty ?? 0);
            }
        }
    @endphp
    <div class="row fs-85" style="width: 100%; padding: 5px 0;">
        <div class="col-4 text-start">
            {{__('Form #')}} {{$process_name->form_number ?? 'EXTRA-001'}}
        </div>
        <div class="col-4 text-center">

        </div>
        <div class="col-4 text-end pe-4 ">
            {{__('Rev#0, 15/Dec/2012   ')}}
            <p>
            <strong>Total qty: {{ $totalQty }}</strong>
        </div>
    </div>
</footer>

<!-- Специфичные модули для extra_processes -->
<!-- Примечание: table-height-adjuster.js отключен для extra_processes -->
<!-- Управление количеством строк осуществляется через Print Settings -->
<script src="{{ asset('js/extra-processes/processes-form/empty-row-processor.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/extra-processes/processes-form/processes-form-main.js') }}?v={{ time() }}"></script>

<!-- Скрипт для настроек печати -->
<script>
    // Проверка на множественное выполнение
    if (typeof window.extraProcessesFormScriptInitialized === 'undefined') {
        window.extraProcessesFormScriptInitialized = true;

        // Ключ для сохранения настроек в localStorage
        const PRINT_SETTINGS_KEY = 'extraProcessesForm_print_settings';

        // Значения по умолчанию
        const defaultSettings = {
            pageMargin: '1mm',
            bodyWidth: '98%',
            bodyHeight: '99%',
            bodyMarginLeft: '2px',
            containerMaxWidth: '920px',
            containerPadding: '5px',
            containerMarginLeft: '10px',
            containerMarginRight: '10px',
            containerMaxHeight: '100vh',
            footerWidth: '800px',
            footerFontSize: '10px',
            footerPadding: '1px 1px',
            ndtTableRows: '16',
            stressTableRows: '19',
            regularTableRows: '19'
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

        // Сохранение настроек в localStorage (глобальная функция)
        window.savePrintSettings = function() {
            try {
                // Вспомогательная функция для безопасного получения значения
                const getValue = function(id, defaultValue, suffix = '') {
                    const element = document.getElementById(id);
                    if (element) {
                        return element.value + suffix;
                    }
                    return defaultValue;
                };

                // Собираем настройки
                const settings = {
                    pageMargin: getValue('pageMargin', '1', 'mm'),
                    bodyWidth: getValue('bodyWidth', '98', '%'),
                    bodyHeight: '99%', // Удалено из интерфейса, используем значение по умолчанию
                    bodyMarginLeft: getValue('bodyMarginLeft', '2', 'px'),
                    containerMaxWidth: getValue('containerMaxWidth', '920', 'px'),
                    containerPadding: getValue('containerPadding', '5', 'px'),
                    containerMarginLeft: getValue('containerMarginLeft', '10', 'px'),
                    containerMarginRight: getValue('containerMarginRight', '10', 'px'),
                    containerMaxHeight: getValue('containerMaxHeight', '100vh', ''),
                    footerWidth: getValue('footerWidth', '800', 'px'),
                    footerFontSize: getValue('footerFontSize', '10', 'px'),
                    footerPadding: getValue('footerPadding', '1px 1px', ''),
                    ndtTableRows: getValue('ndtTableRows', '16', ''),
                    stressTableRows: getValue('stressTableRows', '19', ''),
                    regularTableRows: getValue('regularTableRows', '19', '')
                };

                // Сохраняем в localStorage
                localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));

                // Применяем CSS переменные (быстро, не блокирует)
                applyPrintSettings(settings);

                // Применяем ограничения строк сразу после сохранения
                // Используем небольшую задержку для применения перед перезагрузкой
                setTimeout(function() {
                    applyTableRowLimits(settings);
                }, 50);

                // Закрываем модальное окно и обновляем страницу после закрытия
                const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
                if (modal) {
                    // Ждем полного закрытия модального окна, затем обновляем страницу
                    const modalElement = document.getElementById('printSettingsModal');
                    modalElement.addEventListener('hidden.bs.modal', function reloadAfterClose() {
                        // Удаляем обработчик, чтобы он не срабатывал повторно
                        modalElement.removeEventListener('hidden.bs.modal', reloadAfterClose);
                        // Обновляем страницу после закрытия модального окна
                        setTimeout(function() {
                            window.location.reload();
                        }, 100);
                    }, { once: true });

                    // Закрываем модальное окно
                    modal.hide();
                } else {
                    // Если модальное окно не найдено, просто обновляем страницу
                    setTimeout(function() {
                        window.location.reload();
                    }, 100);
                }
            } catch (error) {
                console.error('Error saving print settings:', error);
                // Закрываем модальное окно даже при ошибке и обновляем страницу
                const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
                if (modal) {
                    const modalElement = document.getElementById('printSettingsModal');
                    modalElement.addEventListener('hidden.bs.modal', function reloadAfterClose() {
                        modalElement.removeEventListener('hidden.bs.modal', reloadAfterClose);
                        setTimeout(function() {
                            window.location.reload();
                        }, 100);
                    }, { once: true });
                    modal.hide();
                } else {
                    setTimeout(function() {
                        window.location.reload();
                    }, 100);
                }
            }
        };

        // Применение настроек к CSS переменным
        function applyPrintSettings(settings) {
            const root = document.documentElement;

            root.style.setProperty('--print-page-margin', settings.pageMargin || defaultSettings.pageMargin);
            root.style.setProperty('--print-body-width', settings.bodyWidth || defaultSettings.bodyWidth);
            root.style.setProperty('--print-body-height', settings.bodyHeight || defaultSettings.bodyHeight);
            root.style.setProperty('--print-body-margin-left', settings.bodyMarginLeft || defaultSettings.bodyMarginLeft);
            root.style.setProperty('--print-container-max-width', settings.containerMaxWidth || defaultSettings.containerMaxWidth);
            root.style.setProperty('--print-container-padding', settings.containerPadding || defaultSettings.containerPadding);
            root.style.setProperty('--print-container-margin-left', settings.containerMarginLeft || defaultSettings.containerMarginLeft);
            root.style.setProperty('--print-container-margin-right', settings.containerMarginRight || defaultSettings.containerMarginRight);
            root.style.setProperty('--print-container-max-height', settings.containerMaxHeight || defaultSettings.containerMaxHeight);
            root.style.setProperty('--print-footer-width', settings.footerWidth || defaultSettings.footerWidth);
            root.style.setProperty('--print-footer-font-size', settings.footerFontSize || defaultSettings.footerFontSize);
            root.style.setProperty('--print-footer-padding', settings.footerPadding || defaultSettings.footerPadding);
        }

        // Функции для добавления пустых строк (используются для ограничения строк)
        function addEmptyRowNDT(rowIndex, container) {
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'row fs-85 data-row-ndt empty-row';
            row.setAttribute('data-row-index', rowIndex);
            row.innerHTML = `
                <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
                <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
                <div class="col-2 border-l-b details-row text-center" style="height: 32px"></div>
                <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
            `;
            container.appendChild(row);
        }

        function addEmptyRowRegular(rowIndex, container, isStress = false) {
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'row empty-row data-row';
            row.setAttribute('data-row-index', rowIndex);
            if (isStress) {
                row.setAttribute('data-stress', 'true');
            }
            row.innerHTML = `
                <div class="col-1 border-l-b text-center" style="height: 32px"></div>
                <div class="col-2 border-l-b text-center" style="height: 32px"></div>
                <div class="col-3 border-l-b text-center" style="height: 32px"></div>
                <div class="col-3 border-l-b text-center" style="height: 32px"></div>
                <div class="col-1 border-l-b text-center" style="height: 32px"></div>
                <div class="col-2 border-l-b-r text-center" style="height: 32px"></div>
            `;
            container.appendChild(row);
        }

        // Функция для применения ограничений строк в таблицах
        function applyTableRowLimits(settings, container = null) {
            if (!settings) {
                settings = loadPrintSettings();
            }

            console.log('Применение ограничений строк:', settings);

            // Выполняем всю обработку асинхронно, чтобы не блокировать UI
            setTimeout(function() {
                try {
                    // Если передан контейнер, ищем элементы только внутри него
                    const searchContainer = container || document;

                    const ndtMaxRows = parseInt(settings.ndtTableRows) || 16;
                    const stressMaxRows = parseInt(settings.stressTableRows) || 19;
                    const regularMaxRows = parseInt(settings.regularTableRows) || 19;

                    console.log(`Лимиты строк: NDT=${ndtMaxRows}, Stress=${stressMaxRows}, Regular=${regularMaxRows}`);

                    // Обработка строк - асинхронно батчами
                    const processRows = function(rows, maxRows, callback) {
                        if (!rows || rows.length === 0) {
                            if (callback) setTimeout(function() { callback(0); }, 0);
                            return;
                        }

                        const rowsArray = Array.from(rows);
                        let maxIndex = 0;
                        const batchSize = 20; // Обрабатываем по 20 строк за раз для меньшей нагрузки
                        let currentIndex = 0;

                        function processBatch() {
                            const endIndex = Math.min(currentIndex + batchSize, rowsArray.length);

                            for (let i = currentIndex; i < endIndex; i++) {
                                const row = rowsArray[i];
                                const rowIndex = parseInt(row.getAttribute('data-row-index')) || 0;
                                if (rowIndex > maxIndex) maxIndex = rowIndex;

                                if (rowIndex > maxRows) {
                                    row.classList.add('print-hide-row');
                                } else {
                                    row.classList.remove('print-hide-row');
                                }
                            }

                            currentIndex = endIndex;

                            if (currentIndex < rowsArray.length) {
                                // Продолжаем обработку в следующем тайм-слоте
                                setTimeout(processBatch, 0);
                            } else if (callback) {
                                setTimeout(function() { callback(maxIndex); }, 0);
                            }
                        }

                        // Начинаем обработку
                        setTimeout(processBatch, 0);
                    };

                    // NDT таблицы - обрабатываем асинхронно
                    setTimeout(function() {
                        try {
                            const ndtContainer = searchContainer.querySelector('.ndt-data-container');
                            if (ndtContainer) {
                                const ndtRows = ndtContainer.querySelectorAll('.data-row-ndt[data-row-index]');
                                console.log(`NDT таблица: найдено ${ndtRows.length} строк, лимит: ${ndtMaxRows}`);
                                processRows(ndtRows, ndtMaxRows, function(maxIndex) {
                                    console.log(`NDT таблица: максимальный индекс ${maxIndex}, лимит ${ndtMaxRows}`);
                                    // Добавляем пустые строки асинхронно
                                    if (maxIndex < ndtMaxRows) {
                                        const rowsToAdd = ndtMaxRows - maxIndex;
                                        console.log(`NDT таблица: нужно добавить ${rowsToAdd} строк`);
                                        setTimeout(function() {
                                            let i = maxIndex + 1;
                                            function addNextRow() {
                                                if (i <= ndtMaxRows) {
                                                    addEmptyRowNDT(i, ndtContainer);
                                                    i++;
                                                    setTimeout(addNextRow, 10); // Небольшая задержка между строками
                                                } else {
                                                    console.log(`NDT таблица: добавлено ${rowsToAdd} строк, всего ${ndtMaxRows}`);
                                                }
                                            }
                                            setTimeout(addNextRow, 10);
                                        }, 0);
                                    } else {
                                        console.log(`NDT таблица: достаточно строк (${maxIndex} >= ${ndtMaxRows})`);
                                    }
                                });
                            } else {
                                console.log('NDT таблица: контейнер не найден');
                            }
                        } catch (e) {
                            console.error('Error processing NDT rows:', e);
                        }
                    }, 0);

                    // STRESS RELIEF таблицы - обрабатываем асинхронно
                    setTimeout(function() {
                        try {
                            const stressContainers = searchContainer.querySelectorAll('.data-page');
                            if (stressContainers.length > 0) {
                                stressContainers.forEach(function(stressContainer, containerIndex) {
                                    const stressRows = stressContainer.querySelectorAll('[data-stress="true"][data-row-index]');
                                    if (stressRows.length > 0) {
                                        console.log(`STRESS RELIEF таблица [контейнер ${containerIndex + 1}]: найдено ${stressRows.length} строк, лимит: ${stressMaxRows}`);
                                        processRows(stressRows, stressMaxRows, function(maxStressIndex) {
                                            console.log(`STRESS RELIEF таблица [контейнер ${containerIndex + 1}]: максимальный индекс ${maxStressIndex}, лимит ${stressMaxRows}`);
                                            // Добавляем пустые строки асинхронно
                                            if (maxStressIndex < stressMaxRows) {
                                                const rowsToAdd = stressMaxRows - maxStressIndex;
                                                console.log(`STRESS RELIEF таблица [контейнер ${containerIndex + 1}]: нужно добавить ${rowsToAdd} строк`);
                                                setTimeout(function() {
                                                    let i = maxStressIndex + 1;
                                                    function addNextRow() {
                                                        if (i <= stressMaxRows) {
                                                            addEmptyRowRegular(i, stressContainer, true);
                                                            i++;
                                                            setTimeout(addNextRow, 10);
                                                        } else {
                                                            console.log(`STRESS RELIEF таблица [контейнер ${containerIndex + 1}]: добавлено ${rowsToAdd} строк, всего ${stressMaxRows}`);
                                                        }
                                                    }
                                                    setTimeout(addNextRow, 10);
                                                }, 0);
                                            } else {
                                                console.log(`STRESS RELIEF таблица [контейнер ${containerIndex + 1}]: достаточно строк (${maxStressIndex} >= ${stressMaxRows})`);
                                            }
                                        });
                                    }
                                });
                            }
                        } catch (e) {
                            console.error('Error processing Stress rows:', e);
                        }
                    }, 50);

                    // Остальные таблицы (Machining, CAD и т.д.) - обрабатываем асинхронно
                    setTimeout(function() {
                        try {
                            // Ищем контейнер .data-page (может быть несколько на странице)
                            const regularContainers = searchContainer.querySelectorAll('.data-page');
                            console.log(`Обычная таблица: найдено контейнеров: ${regularContainers.length}`);

                            if (regularContainers.length > 0) {
                                // Обрабатываем каждый контейнер
                                regularContainers.forEach(function(regularContainer, containerIndex) {
                                    // Ищем все строки с data-row-index, но БЕЗ data-stress="true"
                                    const allRows = regularContainer.querySelectorAll('[data-row-index]');
                                    const regularRows = Array.from(allRows).filter(row => {
                                        const hasStress = row.hasAttribute('data-stress') && row.getAttribute('data-stress') === 'true';
                                        return !hasStress;
                                    });

                                    if (regularRows.length > 0) {
                                        console.log(`Обычная таблица [контейнер ${containerIndex + 1}]: найдено ${regularRows.length} строк, лимит: ${regularMaxRows}`);
                                        processRows(regularRows, regularMaxRows, function(maxRegularIndex) {
                                            console.log(`Обычная таблица [контейнер ${containerIndex + 1}]: максимальный индекс ${maxRegularIndex}, лимит ${regularMaxRows}`);
                                            // Добавляем пустые строки асинхронно
                                            if (maxRegularIndex < regularMaxRows) {
                                                const rowsToAdd = regularMaxRows - maxRegularIndex;
                                                console.log(`Обычная таблица [контейнер ${containerIndex + 1}]: нужно добавить ${rowsToAdd} строк`);
                                                setTimeout(function() {
                                                    let i = maxRegularIndex + 1;
                                                    function addNextRow() {
                                                        if (i <= regularMaxRows) {
                                                            addEmptyRowRegular(i, regularContainer, false);
                                                            i++;
                                                            setTimeout(addNextRow, 10);
                                                        } else {
                                                            console.log(`Обычная таблица [контейнер ${containerIndex + 1}]: добавлено ${rowsToAdd} строк, всего ${regularMaxRows}`);
                                                        }
                                                    }
                                                    setTimeout(addNextRow, 10);
                                                }, 0);
                                            } else {
                                                console.log(`Обычная таблица [контейнер ${containerIndex + 1}]: достаточно строк (${maxRegularIndex} >= ${regularMaxRows})`);
                                            }
                                        });
                                    }
                                });
                            } else {
                                console.log('Обычная таблица: контейнеры не найдены');
                                console.log('Поиск в:', searchContainer === document ? 'document' : searchContainer);
                            }
                        } catch (e) {
                            console.error('Error processing Regular rows:', e);
                        }
                    }, 100);
                } catch (e) {
                    console.error('Error in applyTableRowLimits:', e);
                }
            }, 0);
        }

        // Загрузка настроек в форму
        function loadSettingsToForm(settings) {
            // Извлекаем числовые значения из строк (убираем единицы измерения)
            // Используем безопасные проверки на существование элементов
            const setValue = function(id, value, isString = false) {
                const element = document.getElementById(id);
                if (element) {
                    element.value = isString ? value : (parseFloat(value) || 0);
                }
            };

            setValue('pageMargin', settings.pageMargin || 1);
            setValue('bodyWidth', settings.bodyWidth || 98);
            // bodyHeight удалено из интерфейса, но оставляем в настройках для обратной совместимости
            setValue('bodyMarginLeft', settings.bodyMarginLeft || 2);
            setValue('containerMaxWidth', settings.containerMaxWidth || 920);
            setValue('containerPadding', settings.containerPadding || 5);
            setValue('containerMarginLeft', settings.containerMarginLeft || 10);
            setValue('containerMarginRight', settings.containerMarginRight || 10);
            setValue('containerMaxHeight', settings.containerMaxHeight || '100vh', true);
            setValue('footerWidth', settings.footerWidth || 800);
            setValue('footerFontSize', settings.footerFontSize || 10);
            setValue('footerPadding', settings.footerPadding || '1px 1px', true);

            // Настройки для типов таблиц
            setValue('ndtTableRows', settings.ndtTableRows || 16);
            setValue('stressTableRows', settings.stressTableRows || 19);
            setValue('regularTableRows', settings.regularTableRows || 19);
        }

        // Сброс настроек к значениям по умолчанию
        window.resetPrintSettings = function() {
            if (confirm('Reset all print settings to default values?')) {
                localStorage.removeItem(PRINT_SETTINGS_KEY);
                loadSettingsToForm(defaultSettings);
                applyPrintSettings(defaultSettings);
                // Применяем ограничения строк после сброса
                setTimeout(function() {
                    applyTableRowLimits(defaultSettings);
                }, 50);
                alert('Settings reset to default values!');
            }
        };

        // Ключ для сохранения языка tooltips
        const TOOLTIP_LANG_KEY = 'extraProcessesForm_tooltip_lang';

        // Функция переключения языка tooltips
        window.toggleTooltipLanguage = function() {
            const modal = document.getElementById('printSettingsModal');
            if (!modal) return;

            // Получаем текущий язык из localStorage (по умолчанию 'ru')
            let currentLang = localStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';

            // Переключаем язык
            currentLang = currentLang === 'ru' ? 'en' : 'ru';

            // Сохраняем новый язык
            localStorage.setItem(TOOLTIP_LANG_KEY, currentLang);

            // Обновляем все tooltips
            updateTooltipsLanguage(modal, currentLang);

            // Обновляем текст кнопки
            const langBtn = document.getElementById('langToggleBtn');
            const langText = document.getElementById('langToggleText');
            if (langBtn && langText) {
                langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
            }
        };

        // Функция обновления языка всех tooltips
        function updateTooltipsLanguage(container, lang) {
            const tooltipElements = container.querySelectorAll('[data-bs-toggle="tooltip"]');

            tooltipElements.forEach(function(el) {
                // Уничтожаем существующий tooltip
                const existingTooltip = bootstrap.Tooltip.getInstance(el);
                if (existingTooltip) {
                    existingTooltip.dispose();
                }

                // Получаем текст для выбранного языка
                const ruText = el.getAttribute('data-tooltip-ru');
                const enText = el.getAttribute('data-tooltip-en');

                // Устанавливаем title в зависимости от языка
                if (lang === 'ru' && ruText) {
                    el.setAttribute('title', ruText);
                } else if (lang === 'en' && enText) {
                    el.setAttribute('title', enText);
                }

                // Создаем новый tooltip
                new bootstrap.Tooltip(el);
            });
        }

        // Функция инициализации языка tooltips
        function initTooltipLanguage(modal) {
            const currentLang = localStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
            const langText = document.getElementById('langToggleText');
            if (langText) {
                langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
            }

            // Устанавливаем начальные title для всех tooltips
            setTimeout(function() {
                updateTooltipsLanguage(modal, currentLang);
            }, 100);
        }

        // Инициализация при загрузке страницы (только один раз)
        if (!window.extraProcessesFormDOMInitialized) {
            window.extraProcessesFormDOMInitialized = true;

            document.addEventListener('DOMContentLoaded', function() {
                const settings = loadPrintSettings();
                applyPrintSettings(settings);
                loadSettingsToForm(settings);

                // Применяем ограничения строк при загрузке страницы
                // Используем задержку для обеспечения полного рендеринга
                setTimeout(function() {
                    applyTableRowLimits(settings);
                }, 300);

                // Загружаем настройки в форму при открытии модального окна
                const modal = document.getElementById('printSettingsModal');
                if (modal) {
                    modal.addEventListener('show.bs.modal', function() {
                        const currentSettings = loadPrintSettings();
                        loadSettingsToForm(currentSettings);

                        // Инициализируем язык tooltips
                        initTooltipLanguage(modal);
                    });

                    // Уничтожаем tooltips при закрытии модального окна
                    modal.addEventListener('hidden.bs.modal', function() {
                        const tooltips = modal.querySelectorAll('[data-bs-toggle="tooltip"]');
                        tooltips.forEach(function(el) {
                            const tooltip = bootstrap.Tooltip.getInstance(el);
                            if (tooltip) {
                                tooltip.dispose();
                            }
                        });
                    });
                }
            });

            // Применяем ограничения строк перед печатью
            window.addEventListener('beforeprint', function() {
                const settings = loadPrintSettings();
                // Применяем ограничения ко всем формам на странице
                document.querySelectorAll('.container-fluid').forEach(function(formContainer) {
                    const formSettings = loadPrintSettings();
                    // Применяем ограничения только к элементам внутри текущей формы
                    const ndtContainer = formContainer.querySelector('.ndt-data-container');
                    const dataPages = formContainer.querySelectorAll('.data-page');

                    if (ndtContainer || dataPages.length > 0) {
                        applyTableRowLimits(formSettings, formContainer);
                    }
                });
            });
        }

    } // Конец проверки на множественное выполнение
</script>

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
</div>
</body>
</html>
