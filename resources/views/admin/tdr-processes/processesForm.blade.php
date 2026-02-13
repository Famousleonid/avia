<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$process_name->process_sheet_name}}</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: var(--container-max-width, 960px);
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
            .parent {
                max-width: 100% !important;
                width: 100% !important;
                margin-right: 10px;
                box-sizing: border-box;
                /* Изменяем grid на проценты для адаптивности при печати, сохраняя пропорции */
                /* Оригинальные размеры: 315px 320px (каждая пара = ~635px, всего 3 пары) */
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }

            /* Отключаем разрывы страниц внутри элементов */
            table, h1, p {
                page-break-inside: avoid;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print {
                display: none;
            }

            /* Колонтитул внизу страницы - часть контента */
            .form-wrapper footer,
            .container-fluid footer {
                position: relative;
                width: var(--print-footer-width, 800px);
                margin: 20px auto 0 auto;
                text-align: center;
                font-size: var(--print-footer-font-size, 10px);
                background-color: #fff;
                padding: var(--print-footer-padding, 3px 3px);
                page-break-before: avoid;
                page-break-inside: avoid;
            }

            /* Для каждой формы футер должен быть уникальным */
            .form-wrapper {
                position: relative;
                min-height: 100vh;
                page-break-after: always;
            }

            .form-wrapper:last-child {
                page-break-after: auto;
            }

            /* Убеждаемся, что каждая форма начинается с новой страницы */
            .form-wrapper + .form-wrapper {
                page-break-before: always;
            }

            /* Предотвращаем разрыв страницы внутри таблиц и контента */
            .data-page {
                page-break-inside: avoid;
            }

            /* Шапка таблицы должна оставаться вместе с данными (предотвращает разрыв между header и data-page) */
            .table-header {
                page-break-after: avoid;
            }

            /* Для NDT контейнера - предотвращаем разрыв */
            .ndt-data-container {
                page-break-inside: avoid;
            }

            /* Убеждаемся, что последний контейнер данных и футер остаются вместе */
            .container-fluid .data-page:last-child,
            .container-fluid .ndt-data-container {
                page-break-after: avoid;
            }

            /* Предотвращаем разрыв перед футером */
            .container-fluid footer {
                page-break-before: avoid;
                page-break-inside: avoid;
            }

            /* Убеждаемся, что контент не разрывается перед футером */
            .container-fluid .data-page:last-child + footer,
            .container-fluid .ndt-data-container + footer {
                page-break-before: avoid;
                margin-top: 10px;
            }

            /* Группируем контент и футер - предотвращаем разрыв между ними */
            .container-fluid:has(footer) {
                display: flex;
                flex-direction: column;
            }

            /* Последний элемент перед футером не должен разрываться */
            .container-fluid > *:last-child:not(footer) {
                page-break-after: avoid;
            }


            /* Обрезка контента и размещение на одной странице */
            .container {
                max-height: var(--print-container-max-height, 100vh);
                overflow: hidden;
            }

            /* Настройки для контейнера */
            .container-fluid {
                max-width: var(--print-container-max-width, 1200px);
                width: 100% !important;
                padding: var(--print-container-padding, 5px);
                margin-left: var(--print-container-margin-left, 10px);
                margin-right: var(--print-container-margin-right, 10px);
            }

            /* Стили для таблиц при печати - используем всю доступную ширину */
            table {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 100% !important;
                table-layout: auto !important;
            }

            /* Убеждаемся, что ячейки таблицы используют доступное пространство */
            table td, table th {
                padding: 2px 4px !important;
            }

            /* Убираем любые ограничения ширины для строк таблицы */
            table tr {
                width: 100% !important;
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
            font-size: 0.8em;
            line-height: 1;
            letter-spacing: -0.5px;
            /*transform: scale(0.95);*/
            transform-origin: left;
            display: inline-block;
            vertical-align: middle;
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
        .fs-85 {
            font-size: 0.85rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
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
        /* Оптимизированный grid для .parent - используем адаптивные единицы */
        .parent {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0;
            width: 100%;
        }

        /* Стили для колонок внутри .parent */
        .parent > div {
            padding: 0 5px;
        }

        /* Общие стили для строк процесса NDT */
        .ndt-process-row {
            min-height: 26px;
            line-height: 1;
        }

        .ndt-process-row-tall {
            height: 30px;
        }

        .ndt-process-row-cmm {
            height: 56px;
        }

        .ndt-process-label {
            min-height: 26px;
        }


    </style>
</head>
<body>
<!-- Кнопки для печати и настроек -->
@if(!isset($hidePrintButton) || !$hidePrintButton)
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
            ⚙️ Print Settings
    </button>
</div>
@endif

<!-- Модальное окно настроек печати (показываем только если не скрыто) -->
@if(!isset($hidePrintSettingsModal) || !$hidePrintSettingsModal)
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title " id="printSettingsModalLabel">
                    ⚙️ Print Settings
{{--                    <small class="text-muted d-block small mt-1">Настройки печати форм процессов</small>--}}
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
                            📊 Tables
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="ndtTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблице NDT (Non-Destructive Testing). По умолчанию: 17 строк. Используется для всех форм с типом процесса 'NDT'."
                                        data-tooltip-ru="Максимальное количество строк в таблице NDT (Non-Destructive Testing). По умолчанию: 17 строк. Используется для всех форм с типом процесса 'NDT'."
                                        data-tooltip-en="Maximum number of rows in NDT (Non-Destructive Testing) table. Default: 17 rows. Used for all forms with 'NDT' process type.">
                                    NDT Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="ndtTableRows" name="ndtTableRows"
                                           min="1" max="100" step="1" value="17">
{{--                                    <span class="input-group-text">rows</span>--}}
                            </div>
                            </div>

                            <div class="col-md-4">
                                <label for="stressTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблице Stress Relief (снятие напряжений). По умолчанию: 21 строка. Используется только для форм с типом процесса 'STRESS RELIEF'. Имеет отдельный лимит."
                                        data-tooltip-ru="Максимальное количество строк в таблице Stress Relief (снятие напряжений). По умолчанию: 21 строка. Используется только для форм с типом процесса 'STRESS RELIEF'. Имеет отдельный лимит."
                                        data-tooltip-en="Maximum number of rows in Stress Relief table. Default: 21 rows. Used only for forms with 'STRESS RELIEF' process type. Has a separate limit.">
                                    Stress Relief Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="stressTableRows" name="stressTableRows"
                                           min="1" max="100" step="1" value="21">
{{--                                    <span class="input-group-text">rows</span>--}}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="otherTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблицах других процессов (Machining, CAD, Anodizing и т.д.). По умолчанию: 21 строка. Используется для всех процессов, кроме NDT и Stress Relief."
                                        data-tooltip-ru="Максимальное количество строк в таблицах других процессов (Machining, CAD, Anodizing и т.д.). По умолчанию: 21 строка. Используется для всех процессов, кроме NDT и Stress Relief."
                                        data-tooltip-en="Maximum number of rows in other process tables (Machining, CAD, Anodizing, etc.). Default: 21 rows. Used for all processes except NDT and Stress Relief.">
                                    Other Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="otherTableRows" name="otherTableRows"
                                           min="1" max="100" step="1" value="21">
{{--                                    <span class="input-group-text">rows</span>--}}
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
                                                        title="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 1200px для tdr-processes. Увеличьте, если таблица слишком узкая."
                                                        data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 1200px для tdr-processes. Увеличьте, если таблица слишком узкая."
                                                        data-tooltip-en="Maximum width of the table container in pixels. Recommended value: 1200px for tdr-processes. Increase if the table is too narrow.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                           min="500" max="2000" step="10" value="1200">
{{--                                                    <span class="input-group-text">px</span>--}}
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
{{--                                                    <span class="input-group-text">px</span>--}}
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
                                                        title="Ширина основного контента в процентах от ширины страницы. 98% - стандартное значение, оставляет небольшие поля по бокам."
                                                        data-tooltip-ru="Ширина основного контента в процентах от ширины страницы. 98% - стандартное значение, оставляет небольшие поля по бокам."
                                                        data-tooltip-en="Main content width as percentage of page width. 98% - standard value, leaves small margins on the sides.">
                                                    Width (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyWidth" name="bodyWidth"
                                                           min="50" max="100" step="1" value="98">
{{--                                                    <span class="input-group-text">%</span>--}}
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="bodyHeight" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Высота основного контента в процентах от высоты страницы. 99% - стандартное значение."
                                                        data-tooltip-ru="Высота основного контента в процентах от высоты страницы. 99% - стандартное значение."
                                                        data-tooltip-en="Main content height as percentage of page height. 99% - standard value.">
                                                    Height (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyHeight" name="bodyHeight"
                                                           min="50" max="100" step="1" value="99">
{{--                                                    <span class="input-group-text">%</span>--}}
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
{{--                                                    <span class="input-group-text">mm</span>--}}
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
{{--                                                    <span class="input-group-text">px</span>--}}
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
{{--                                                    <span class="input-group-text">px</span>--}}
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
{{--                                                    <span class="input-group-text">px</span>--}}
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
                                              title="Настройки нижнего колонтитула формы. Колонтитул содержит номер формы, ревизию и общее количество компонентов."
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
                                                        title="Ширина колонтитула в пикселях. 800px - стандартное значение. Увеличьте, если текст в колонтитуле не помещается."
                                                        data-tooltip-ru="Ширина колонтитула в пикселях. 800px - стандартное значение. Увеличьте, если текст в колонтитуле не помещается."
                                                        data-tooltip-en="Footer width in pixels. 800px - standard value. Increase if footer text doesn't fit.">
                                                    Width on pg (px)
                                                </label>
                                        <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="400" max="1200" step="10" value="800">
{{--                                                    <span class="input-group-text">px</span>--}}
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
{{--                                                    <span class="input-group-text">px</span>--}}
                                        </div>
                                    </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerPadding" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '3px 5px' означает 3px сверху/снизу и 5px слева/справа."
                                                        data-tooltip-ru="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '3px 5px' означает 3px сверху/снизу и 5px слева/справа."
                                                        data-tooltip-en="Footer inner padding in CSS format (vertical horizontal). Example: '3px 5px' means 3px top/bottom and 5px left/right.">
                                                    Padding
                                                </label>
                                        <div class="input-group">
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                           placeholder="3px 3px" value="3px 3px">
{{--                                                    <span class="input-group-text">CSS</span>--}}
                                        </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerBottom" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Нижний отступ колонтитула от края страницы. По умолчанию: 0px."
                                                        data-tooltip-ru="Нижний отступ колонтитула от края страницы. По умолчанию: 0px."
                                                        data-tooltip-en="Footer bottom margin from page edge. Default: 0px.">
                                                    Bottom Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerBottom" name="footerBottom"
                                                           min="0" max="50" step="1" value="0">
{{--                                                    <span class="input-group-text">px</span>--}}
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
@endif
<div class="container-fluid">
    <div class="header-page">
        <div class="row">
            <div class="col-3">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 120px; margin: 6px 10px 0;">
            </div>
            <div class="col-9">
                <h4 class=" mt-4 text-black text-"><strong>{{$process_name->process_sheet_name}} PROCESS SHEET</strong></h4>
            </div>
        </div>
        <div class="row">
            <div class="col-7">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 pt-2 border-b"> <strong>
                      <span @if(strlen($current_wo->description) > 30) class="description-text-long"
                                @endif>{{$current_wo->description}}</span>
{{--                            {{$current_wo->description}}--}}
                        </strong> </div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"> <strong> PART NUMBER:</strong></div>
                    <div class="col-6 pt-2 border-b"> <strong>{{$current_wo->unit->part_number}}</strong> </div>
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
                    <div class="col-8 pt-2 border-b">
                        <strong>
                            {{ $selectedVendor ? $selectedVendor->name : '' }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

        @if($process_name->process_sheet_name =='NDT')

        @php
            // Оптимизация: предварительная фильтрация процессов по ID для уменьшения количества циклов
            // Вместо 8 циклов по всему массиву, создаем индекс для O(1) доступа
            $ndt_processes_by_id = [];
            if(isset($ndt_processes) && is_iterable($ndt_processes)) {
                foreach($ndt_processes as $process) {
                    $ndt_processes_by_id[$process->process_names_id] = $process;
                }
            }
        @endphp

        <div class="parent mt-3">
            <div class="div1">
                <div class="text-start fs-7 ndt-process-label"><strong>MAGNETIC PARTICLE AS PER:</strong></div>
                <div class="row ndt-process-row">
                    <div class="col-1 fs-7">#1</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes_by_id[$ndt1_name_id ?? null]))
                            <span @if(strlen($ndt_processes_by_id[$ndt1_name_id]->process) > 20) class="process-text-long" @endif>
                                {{$ndt_processes_by_id[$ndt1_name_id]->process}}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="text-start fs-75 ndt-process-label"><strong>LIQUID/FLUID PENETRANT AS PER:</strong></div>
                <div class="row ndt-process-row">
                    <div class="col-1 fs-7">#4</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes_by_id[$ndt4_name_id ?? null]))
                            <span @if(strlen($ndt_processes_by_id[$ndt4_name_id]->process) > 20) class="process-text-long" @endif>
                                {{$ndt_processes_by_id[$ndt4_name_id]->process}}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="text-start fs-7 ndt-process-label"><strong>ULTRASOUND AS PER:</strong></div>
                <div class="row ndt-process-row">
                    <div class="col-1 fs-7">#7</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes_by_id[$ndt7_name_id ?? null]))
                            <span @if(strlen($ndt_processes_by_id[$ndt7_name_id]->process) > 20) class="process-text-long" @endif>
                                {{$ndt_processes_by_id[$ndt7_name_id]->process}}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="div2">
                <div class="row ndt-process-row-tall mt-4">
                    <div class="col-1 fs-7">#2</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes_by_id[$ndt2_name_id ?? null]))
                            <span @if(strlen($ndt_processes_by_id[$ndt2_name_id]->process) > 20) class="process-text-long" @endif>
                                {{$ndt_processes_by_id[$ndt2_name_id]->process}}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="row ndt-process-row-tall mt-4">
                    <div class="col-1 fs-7">#5</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes_by_id[$ndt5_name_id ?? null]))
                            <span @if(strlen($ndt_processes_by_id[$ndt5_name_id]->process) > 25) class="process-text-long" @endif>
                                {{$ndt_processes_by_id[$ndt5_name_id]->process}}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="row ndt-process-row mt-4">
                    <div class="col-1 fs-7">#8</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes_by_id[$ndt8_name_id ?? null]))
                            <span @if(strlen($ndt_processes_by_id[$ndt8_name_id]->process) > 25) class="process-text-long" @endif>
                                {{$ndt_processes_by_id[$ndt8_name_id]->process}}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="div3">
                <div class="row ndt-process-row-tall mt-4">
                    <div class="col-1 fs-7 ">#3</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes_by_id[$ndt3_name_id ?? null]))
                            <span @if(strlen($ndt_processes_by_id[$ndt3_name_id]->process) > 20) class="process-text-long" @endif>
                                {{$ndt_processes_by_id[$ndt3_name_id]->process}}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="text-start ms-3 fs-7 ndt-process-label"><strong>EDDY CURRENT AS PER:</strong></div>
                <div class="row ndt-process-row">
                    <div class="col-1 fs-7 text-end">#6</div>
                    <div class="col-10 border-b">
                        @if(isset($ndt_processes_by_id[$ndt6_name_id ?? null]))
                            <span @if(strlen($ndt_processes_by_id[$ndt6_name_id]->process) > 40) class="process-text-long" @endif>
                                {{$ndt_processes_by_id[$ndt6_name_id]->process}}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="row ndt-process-row-cmm mt-2">
                    <div class="col-4 fs-7 text-end mt-3"><strong>CMM No:</strong></div>
                    <div class="col-8 border-all">
                        @foreach($manuals as $manual)
                            @if($manual->id == $current_wo->unit->manual_id)
                                <h6 class="text-center mt-3"><strong>{{substr($manual->number, 0, 8)}}</strong></h6>
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
                $totalRows = 17; // Общее количество строк
            $dataRows = count($ndt_components); // Количество строк с данными
            $emptyRows = $totalRows - $dataRows; // Количество пустых строк
            $rowIndex = 1;
        @endphp

        @foreach($ndt_components as $component)
            <div class="row fs-8 data-row-ndt" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-row text-center" style="height: 32px">
                    {{ $component->tdr->component->ipl_num }}
                </div>
                <div class="col-3 border-l-b details-row text-center" style="height: 32px;line-height: 1">
                    {{ $component->tdr->component->part_number }}
                    <br>
                    @if($component->tdr->serial_number)
            SN {{$component->tdr->serial_number}}
                    @endif
                </div>
        <div class="col-3 border-l-b details-row text-center" style="height: 32px">
        {{ $component->tdr->component->name }}
        </div>
        <div class="col-2 border-l-b details-row text-center" style="height: 32px">
        @php
            // Получаем номер основного процесса
            $processNumbers = [substr($component->processName->name, -1)];

            // Если есть дополнительные NDT процессы, добавляем их номера
            if ($component->plus_process) {
                $plusProcessIds = explode(',', $component->plus_process);
                foreach ($plusProcessIds as $plusProcessId) {
                    $plusProcessName = \App\Models\ProcessName::find($plusProcessId);
                    if ($plusProcessName && strpos($plusProcessName->name, 'NDT-') === 0) {
                        $processNumbers[] = substr($plusProcessName->name, -1);
                    }
                }
            }

            // Сортируем номера и объединяем через ' & '
            sort($processNumbers);
            echo implode(' / ', $processNumbers);
        @endphp
        </div>
        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
        {{ $component->tdr->qty }}
        </div>
        <div class="col-1 border-l-b details-row text-center" style="height: 32px">
        <!-- Пустая ячейка -->
        </div>
        <div class="col-1 border-l-b-r details-row text-center" style="height: 32px">

        </div>
    </div>
@php $rowIndex++; @endphp
@endforeach

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


                <h6 class="mt-4 ms-3 "><strong>
                    Perform the {{ ucwords(strtolower($process_name->process_sheet_name)) }}
                    as the specified under Process No. and in
                        accordance with CMM No
                    </strong>.</h6>

    <div class="page table-header">
                    <div class="row mt-3 " >
                        <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-7" ><strong> ITEM No.</strong></h6></div>
                        <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6  class="fs-7" ><strong>PART No.</strong>
        </h6>
    </div>
                        <div class="col-2 border-l-t-b pt-2  details-row text-center"><h6  class="fs-7" ><strong>DESCRIPTION</strong>
        </h6></div>
                        <div class="col-4 border-l-t-b pt-2 details-row text-center"><h6  class="fs-7" ><strong>PROCESS No.</strong>
        </h6> </div>
                        <div class="col-1 border-l-t-b pt-2  details-row  text-center"><h6  class="fs-7" ><strong>QTY</strong> </h6>
    </div>

        @if($process_name->process_sheet_name == 'STRESS RELIEF')
                            <div class="col-2 border-all pt-2  details-row  text-center"><h6  class="fs-7" ><strong>PERFORMED</strong>
                </h6>
        @else
                                    <div class="col-2 border-all pt-2  details-row  text-center"><h6  class="fs-7" ><strong>CMM No.</strong> </h6>
        @endif
            </div>
    </div>
    </div>
    <div class="page data-page">

    @php
                            $totalRows = 21; // Общее количество строк
                            $isStress = $process_name->process_sheet_name == 'STRESS RELIEF';
                            // Подсчитываем реальное количество строк данных (с учетом вложенного цикла процессов)
                            $dataRows = 0;
                            foreach($process_tdr_components as $component) {
                                $processData = json_decode($component->processes, true);
                                // Если processes - массив, считаем количество процессов, иначе 1
                                $dataRows += is_array($processData) ? count($processData) : 1;
                            }
                            $emptyRows = max(0, $totalRows - $dataRows); // Количество пустых строк (не меньше 0)
    $rowIndex = 1;
    @endphp

    @foreach($process_tdr_components as $component)
    @php
        $processData = json_decode($component->processes, true);
                            // Получаем имя процесса из связанной модели ProcessName
        $processesName = $component->processName->name;
    @endphp

    @foreach($processData as $process)

                                <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}" @if($isStress) data-stress="true" @endif>
        <div class="col-1 border-l-b details-cell text-center"  style="min-height: 34px">
            {{ $component->tdr->component->ipl_num }}
        </div>
        <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
            {{ $component->tdr->component->part_number }}
            @if($component->tdr->serial_number)
                <br>SN {{$component->tdr->serial_number}}
            @endif
        </div>
        <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px" >
            {{ $component->tdr->component->name }}
        </div>
        <div class="col-4 border-l-b details-cell text-center process-cell"  style="min-height: 34px">
            @foreach($process_components as $component_process)
                @if($component_process->id == $process)
                    <span @if(strlen($component_process->process) > 25) class="process-text-long"
                        @endif>
                        {{$component_process->process}}
                        @if($component->description)
                            <br><span>{{$component->description}}
                            </span>
                        @endif
                        </span>
                @endif
            @endforeach
        </div>
        <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px" >
            {{ $component->tdr->qty }}
        </div>
        @if($process_name->process_sheet_name == 'STRESS RELIEF')
            <div class="col-2 border-l-b-r details-cell text-center"  style="min-height: 34px"></div>
        @else
            <div class="col-2 border-l-b-r details-cell text-center"  style="min-height: 34px">
                @foreach($manuals as $manual)
                    @if($manual->id == $current_wo->unit->manual_id)
                        <h6 class="text-center mt-2">
                            {{substr($manual->number, 0, 8)}}
                        </h6>
                    @endif
                @endforeach
            </div>
        @endif

    </div>
    @php $rowIndex++; @endphp
    @endforeach
    @endforeach

    @for ($i = 0; $i < $emptyRows; $i++)
                            <div class="row empty-row data-row" data-row-index="{{ $rowIndex }}" @if($isStress) data-stress="true" @endif>
        <div class="col-1 border-l-b  text-center" style="height: 32px">
            <!-- Пустая ячейка -->
        </div>
        <div class="col-2 border-l-b  text-center" style="height: 32px">
            <!-- Пустая ячейка -->
        </div>
        <div class="col-2 border-l-b  text-center" style="height: 32px">
            <!-- Пустая ячейка -->
        </div>
        <div class="col-4 border-l-b  text-center" style="height: 32px">
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

<footer>
<div class="row fs-85" style="width: 100%; padding: 5px 0;">
<div class="col-6 text-start">
{{__('Form #')}} {{$process_name->form_number}}
</div>
<div class="col-6 text-end pe-4 ">
{{__('Rev#0, 15/Dec/2012   ')}}
</div>
</div>
</footer>
    </div>
</div>


<!-- Скрипт для настроек печати -->
<script>
    // Проверка на множественное выполнение (для packageForms)
    if (typeof window.processesFormScriptInitialized === 'undefined') {
        window.processesFormScriptInitialized = true;

    // Ключ для сохранения настроек в localStorage
    const PRINT_SETTINGS_KEY = 'processesForm_print_settings';

    // Значения по умолчанию
    const defaultSettings = {
            pageMargin: '1mm',
            bodyWidth: '98%',
            bodyHeight: '99%',
            bodyMarginLeft: '2px',
            containerMaxWidth: '1200px',
            containerPadding: '5px',
            containerMarginLeft: '10px',
            containerMarginRight: '10px',
            containerMaxHeight: '100vh',
            footerWidth: '800px',
            footerFontSize: '10px',
            footerPadding: '3px 3px',
            footerBottom: '0px',
            ndtTableRows: '17',
            stressTableRows: '21',
            otherTableRows: '21'
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
                // Собираем настройки
        const settings = {
                    pageMargin: document.getElementById('pageMargin').value + 'mm',
                    bodyWidth: document.getElementById('bodyWidth').value + '%',
                    bodyHeight: document.getElementById('bodyHeight').value + '%',
                    bodyMarginLeft: document.getElementById('bodyMarginLeft').value + 'px',
                    containerMaxWidth: document.getElementById('containerMaxWidth').value + 'px',
                    containerPadding: document.getElementById('containerPadding').value + 'px',
                    containerMarginLeft: document.getElementById('containerMarginLeft').value + 'px',
                    containerMarginRight: document.getElementById('containerMarginRight').value + 'px',
                    containerMaxHeight: document.getElementById('containerMaxHeight').value,
                    footerWidth: document.getElementById('footerWidth').value + 'px',
                    footerFontSize: document.getElementById('footerFontSize').value + 'px',
                    footerPadding: document.getElementById('footerPadding').value,
                    footerBottom: document.getElementById('footerBottom').value + 'px',
                    ndtTableRows: document.getElementById('ndtTableRows').value,
                    stressTableRows: document.getElementById('stressTableRows').value,
                    otherTableRows: document.getElementById('otherTableRows').value
                };

                // Сохраняем в localStorage
        localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));

                // Применяем CSS переменные (быстро, не блокирует)
        applyPrintSettings(settings);

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
            root.style.setProperty('--print-footer-bottom', settings.footerBottom || defaultSettings.footerBottom);
        }

        // Функции для добавления пустых строк
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
            <div class="col-2 border-l-b text-center" style="height: 32px"></div>
            <div class="col-4 border-l-b text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b-r text-center" style="height: 32px"></div>
        `;
            container.appendChild(row);
        }

        // Функция для применения ограничений строк в таблицах (полностью асинхронная версия)
        function applyTableRowLimits(settings, container = null) {
            if (!settings) {
                settings = loadPrintSettings();
            }

            // Выполняем всю обработку асинхронно, чтобы не блокировать UI
            setTimeout(function() {
                try {
                    // Если передан контейнер, ищем элементы только внутри него
                    const searchContainer = container || document;

                    const ndtMaxRows = parseInt(settings.ndtTableRows) || 17;
                    const stressMaxRows = parseInt(settings.stressTableRows) || 21;
                    const otherMaxRows = parseInt(settings.otherTableRows) || 21;

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
                                processRows(ndtRows, ndtMaxRows, function(maxIndex) {
                                    // Добавляем пустые строки асинхронно
                                    if (maxIndex < ndtMaxRows) {
                                        setTimeout(function() {
                                            let i = maxIndex + 1;
                                            function addNextRow() {
                                                if (i <= ndtMaxRows) {
                                                    addEmptyRowNDT(i, ndtContainer);
                                                    i++;
                                                    setTimeout(addNextRow, 10); // Небольшая задержка между строками
                                                }
                                            }
                                            setTimeout(addNextRow, 10);
                                        }, 0);
                                    }
                                });
                            }
                        } catch (e) {
                            console.error('Error processing NDT rows:', e);
                        }
                    }, 0);

                    // STRESS RELIEF таблицы - обрабатываем асинхронно
                    setTimeout(function() {
                        try {
                            const stressRows = searchContainer.querySelectorAll('.data-page .data-row[data-stress="true"][data-row-index]');
                            const stressContainer = stressRows.length > 0 ? stressRows[0].closest('.data-page') : null;
                            if (stressContainer) {
                                processRows(stressRows, stressMaxRows, function(maxStressIndex) {
                                    // Добавляем пустые строки асинхронно
                                    if (maxStressIndex < stressMaxRows) {
                                        setTimeout(function() {
                                            let i = maxStressIndex + 1;
                                            function addNextRow() {
                                                if (i <= stressMaxRows) {
                                                    addEmptyRowRegular(i, stressContainer, true);
                                                    i++;
                                                    setTimeout(addNextRow, 10);
                                                }
                                            }
                                            setTimeout(addNextRow, 10);
                                        }, 0);
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
                            const allOtherRows = searchContainer.querySelectorAll('.data-page .data-row[data-row-index]');
                            // Фильтруем асинхронно
                            setTimeout(function() {
                                try {
                                    const otherRows = Array.from(allOtherRows).filter(row => {
                                        const hasStress = row.hasAttribute('data-stress') && row.getAttribute('data-stress') === 'true';
                                        return !hasStress;
                                    });

                                    if (otherRows.length > 0) {
                                        const otherContainer = otherRows[0].closest('.data-page');
                                        if (otherContainer) {
                                            processRows(otherRows, otherMaxRows, function(maxOtherIndex) {
                                                // Добавляем пустые строки асинхронно
                                                if (maxOtherIndex < otherMaxRows) {
                                                    setTimeout(function() {
                                                        let i = maxOtherIndex + 1;
                                                        function addNextRow() {
                                                            if (i <= otherMaxRows) {
                                                                addEmptyRowRegular(i, otherContainer, false);
                                                                i++;
                                                                setTimeout(addNextRow, 10);
                                                            }
                                                        }
                                                        setTimeout(addNextRow, 10);
                                                    }, 0);
                                                }
                                            });
                                        }
                                    }
                                } catch (e) {
                                    console.error('Error processing Other rows:', e);
                                }
                            }, 0);
                        } catch (e) {
                            console.error('Error querying Other rows:', e);
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
            document.getElementById('pageMargin').value = parseFloat(settings.pageMargin) || 1;
            document.getElementById('bodyWidth').value = parseFloat(settings.bodyWidth) || 98;
            document.getElementById('bodyHeight').value = parseFloat(settings.bodyHeight) || 99;
            document.getElementById('bodyMarginLeft').value = parseFloat(settings.bodyMarginLeft) || 2;
            document.getElementById('containerMaxWidth').value = parseFloat(settings.containerMaxWidth) || 1200;
            document.getElementById('containerPadding').value = parseFloat(settings.containerPadding) || 5;
            document.getElementById('containerMarginLeft').value = parseFloat(settings.containerMarginLeft) || 10;
            document.getElementById('containerMarginRight').value = parseFloat(settings.containerMarginRight) || 10;
            document.getElementById('containerMaxHeight').value = settings.containerMaxHeight || '100vh';
            document.getElementById('footerWidth').value = parseFloat(settings.footerWidth) || 800;
            document.getElementById('footerFontSize').value = parseFloat(settings.footerFontSize) || 10;
            document.getElementById('footerPadding').value = settings.footerPadding || '3px 3px';
            document.getElementById('footerBottom').value = parseFloat(settings.footerBottom) || 0;

            // Настройки для типов таблиц
            document.getElementById('ndtTableRows').value = settings.ndtTableRows || 17;
            document.getElementById('stressTableRows').value = settings.stressTableRows || 21;
            document.getElementById('otherTableRows').value = settings.otherTableRows || 21;
    }

    // Сброс настроек к значениям по умолчанию
    function resetPrintSettings() {
            if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
                // НЕ применяем ограничения строк сразу - они будут применены при печати
                alert('Settings reset to default values!');
        }
    }

        // Инициализация при загрузке страницы (только один раз)
        if (!window.processesFormDOMInitialized) {
            window.processesFormDOMInitialized = true;

    document.addEventListener('DOMContentLoaded', function() {
        const settings = loadPrintSettings();
        applyPrintSettings(settings);
        loadSettingsToForm(settings);

                // НЕ применяем ограничения строк при загрузке - они будут применены при печати
                // Это предотвращает блокировку UI

        // Загружаем настройки в форму при открытии модального окна
        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                const currentSettings = loadPrintSettings();
                loadSettingsToForm(currentSettings);

                // Инициализируем язык tooltips
                initTooltipLanguage(modal);
            });
        }
    });

    // Ключ для сохранения языка tooltips
    const TOOLTIP_LANG_KEY = 'tdrProcessesForm_tooltip_lang';

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
        }

        // Применяем ограничения строк перед печатью
        window.addEventListener('beforeprint', function() {
            const settings = loadPrintSettings();
            // Применяем ограничения ко всем формам на странице
            document.querySelectorAll('.form-wrapper, .container-fluid').forEach(function(formContainer) {
                const formSettings = loadPrintSettings();
                // Применяем ограничения только к элементам внутри текущей формы
                const ndtContainer = formContainer.querySelector('.ndt-data-container');
                const dataPages = formContainer.querySelectorAll('.data-page');

                if (ndtContainer || dataPages.length > 0) {
                    applyTableRowLimits(formSettings, formContainer);
                }
            });
        });

    } // Конец проверки на множественное выполнение
</script>

<!-- Bootstrap JS для работы модального окна (загружаем только один раз) -->
@if(!isset($hideBootstrapJS) || !$hideBootstrapJS)
    <script>
        if (typeof window.bootstrapLoaded === 'undefined') {
            window.bootstrapLoaded = true;
            const script = document.createElement('script');
            script.src = "{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}";
            script.async = true;
            document.head.appendChild(script);
        }
    </script>
    @endif
</div>
</body>
</html>
