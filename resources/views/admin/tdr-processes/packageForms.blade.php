<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Process Forms</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }
        .form-wrapper {
            page-break-after: always;
            page-break-inside: avoid;
            min-height: 100vh;
        }
        .form-wrapper:last-child {
            page-break-after: auto;
        }
        @media print {
            @page {
                size: letter portrait;
                margin: 1cm 1cm 1cm 1cm;
            }
            .form-wrapper {
                page-break-after: always;
                page-break-inside: avoid;
                min-height: 100vh;
            }
            .form-wrapper:last-child {
                page-break-after: auto;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">Print All Forms</button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        ⚙️ Print Settings
    </button>
</div>

<!-- Модальное окно настроек печати (только одно на странице) -->
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">
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
{{--                                </div>--}}
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
{{--                                <span class="input-group-text">px</span>--}}
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
{{--                                <span class="input-group-text">px</span>--}}
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
{{--                                <span class="input-group-text">px</span>--}}
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
{{--                                <span class="input-group-text">px</span>--}}
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
{{--                                <span class="input-group-text">CSS</span>--}}
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
{{--                                <span class="input-group-text">px</span>--}}
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

@foreach($formsData as $index => $formData)
    <div class="form-wrapper" data-form-index="{{ $index }}">
        @php
            // Устанавливаем переменные для использования в processesForm
            $process_name = $formData['process_name'];
            $current_wo = $formData['current_wo'];
            $components = $formData['components'];
            $tdrs = $formData['tdrs'];
            $manuals = $formData['manuals'];
            $manual_id = $formData['manual_id'];
            $selectedVendor = $formData['selectedVendor'];
            $current_tdr = $formData['current_tdr'] ?? null;
            $hidePrintButton = true;
            $hidePrintSettingsModal = ($index > 0); // Показываем модальное окно только в первой форме
            $hideBootstrapJS = ($index > 0); // Загружаем Bootstrap JS только для первой формы

            // Устанавливаем переменные для NDT или обычных процессов
            if (isset($formData['ndt_processes'])) {
                $ndt_processes = $formData['ndt_processes'];
                $ndt_components = $formData['ndt_components'];
                $current_ndt_id = $formData['current_ndt_id'];
                $ndt1_name_id = $formData['ndt1_name_id'];
                $ndt4_name_id = $formData['ndt4_name_id'];
                $ndt6_name_id = $formData['ndt6_name_id'];
                $ndt5_name_id = $formData['ndt5_name_id'];
            } else {
                $process_components = $formData['process_components'];
                $process_tdr_components = $formData['process_tdr_components'];
            }
        @endphp
        @include('admin.tdr-processes.processesForm', array_merge($formData, ['hidePrintButton' => true, 'hidePrintSettingsModal' => $hidePrintSettingsModal, 'hideBootstrapJS' => $hideBootstrapJS]))
    </div>
@endforeach

<!-- Bootstrap JS для работы модального окна (загружаем только один раз) -->
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>

<script>
    // Ключ для сохранения языка tooltips
    const TOOLTIP_LANG_KEY_PACKAGE = 'packageForms_tooltip_lang';

    // Функция переключения языка tooltips
    window.toggleTooltipLanguage = function() {
        const modal = document.getElementById('printSettingsModal');
        if (!modal) return;

        // Получаем текущий язык из localStorage (по умолчанию 'ru')
        let currentLang = localStorage.getItem(TOOLTIP_LANG_KEY_PACKAGE) || 'ru';

        // Переключаем язык
        currentLang = currentLang === 'ru' ? 'en' : 'ru';

        // Сохраняем новый язык
        localStorage.setItem(TOOLTIP_LANG_KEY_PACKAGE, currentLang);

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
        const currentLang = localStorage.getItem(TOOLTIP_LANG_KEY_PACKAGE) || 'ru';
        const langText = document.getElementById('langToggleText');
        if (langText) {
            langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
        }

        // Устанавливаем начальные title для всех tooltips
        setTimeout(function() {
            updateTooltipsLanguage(modal, currentLang);
        }, 100);
    }

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                // Инициализируем язык tooltips
                initTooltipLanguage(modal);
            });
        }
    });
</script>
</body>
</html>
