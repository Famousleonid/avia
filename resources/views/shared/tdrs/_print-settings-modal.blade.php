{{--
    Модальное окно настроек печати для TDR форм (ndtFormStd, cadFormStd, stressFormStd, paintFormStd).
    Переменные: $formType — 'ndtFormStd' | 'cadFormStd' | 'stressFormStd' | 'paintFormStd'
--}}
@php
    $formType = $formType ?? 'ndtFormStd';
    $formConfig = $formConfig ?? config('tdr_forms.' . $formType, config('tdr_forms.ndtFormStd'));
    $tableRowsKey = $formConfig['table_rows_key'] ?? 'ndtTableRows';
    $tableRowsDefault = $formConfig['table_rows_default'] ?? 16;
    $tableLabel = $formConfig['table_label'] ?? 'Table (row)';
@endphp
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">⚙️ Print Settings</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="langToggleBtn" onclick="toggleTooltipLanguage()">
                        <span id="langToggleText">US</span>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <div class="mb-4">
                        <h5 class="mb-3" data-bs-toggle="tooltip" data-bs-placement="top"
                            title="Настройки количества строк в таблице. Строки сверх лимита скрываются при печати. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-ru="Настройки количества строк в таблице. Строки сверх лимита скрываются при печати. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-en="Table row settings. Rows exceeding the limit are hidden when printing. Settings are applied automatically on page load.">
                            📊 Tables
                        </h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="{{ $tableRowsKey }}" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Максимальное количество строк в таблице на одной странице."
                                    data-tooltip-ru="Максимальное количество строк в таблице на одной странице."
                                    data-tooltip-en="Maximum number of rows in table per page.">
                                    {{ $tableLabel }}
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="{{ $tableRowsKey }}" name="{{ $tableRowsKey }}"
                                        min="1" max="100" step="1" value="{{ $tableRowsDefault }}">
                                </div>
                            </div>
                        </div>

                        <div class="accordion mb-3" id="tableSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="tableSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#tableSettingsCollapse" aria-expanded="false" aria-controls="tableSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="Дополнительные настройки таблицы: ширина, отступы контейнера."
                                            data-tooltip-ru="Дополнительные настройки таблицы: ширина, отступы контейнера."
                                            data-tooltip-en="Additional table settings: width, container padding and margins.">
                                            Table Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="tableSettingsCollapse" class="accordion-collapse collapse" aria-labelledby="tableSettingsHeading" data-bs-parent="#tableSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMaxWidth" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Максимальная ширина контейнера с таблицей в пикселях."
                                                    data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях."
                                                    data-tooltip-en="Maximum width of the table container in pixels.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                        min="500" max="2000" step="10" value="{{ $formConfig['container_max_width'] ?? 920 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerPadding" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Внутренние отступы контейнера. По умолчанию: 5px."
                                                    data-tooltip-ru="Внутренние отступы контейнера. По умолчанию: 5px."
                                                    data-tooltip-en="Container inner padding. Default: 5px.">
                                                    Padding (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                        min="0" max="50" step="1" value="{{ $formConfig['container_padding'] ?? 5 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginLeft" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Отступ контейнера от левого края."
                                                    data-tooltip-ru="Отступ контейнера от левого края."
                                                    data-tooltip-en="Table container margin from left edge.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMarginLeft" name="containerMarginLeft"
                                                        min="0" max="50" step="1" value="{{ $formConfig['container_margin_left'] ?? 10 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginRight" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Отступ контейнера от правого края."
                                                    data-tooltip-ru="Отступ контейнера от правого края."
                                                    data-tooltip-en="Table container margin from right edge.">
                                                    Right Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMarginRight" name="containerMarginRight"
                                                        min="0" max="50" step="1" value="{{ $formConfig['container_margin_right'] ?? 10 }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="accordion" id="pageSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="pageSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#pageSettingsCollapse" aria-expanded="false" aria-controls="pageSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="Настройки страницы: ширина, высота, поля и отступы."
                                            data-tooltip-ru="Настройки страницы: ширина, высота, поля и отступы."
                                            data-tooltip-en="Page settings: width, height, margins and padding.">
                                            Page Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="pageSettingsCollapse" class="accordion-collapse collapse" aria-labelledby="pageSettingsHeading" data-bs-parent="#pageSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="bodyWidth" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Ширина основного контента в процентах от ширины страницы."
                                                    data-tooltip-ru="Ширина основного контента в процентах от ширины страницы."
                                                    data-tooltip-en="Main content width as percentage of page width.">
                                                    Width (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyWidth" name="bodyWidth"
                                                        min="50" max="100" step="1" value="{{ $formConfig['body_width'] ?? 98 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="bodyHeight" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Высота основного контента в процентах от высоты страницы."
                                                    data-tooltip-ru="Высота основного контента в процентах от высоты страницы."
                                                    data-tooltip-en="Main content height as percentage of page height.">
                                                    Height (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyHeight" name="bodyHeight"
                                                        min="50" max="100" step="1" value="{{ $formConfig['body_height'] ?? 99 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="pageMargin" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Отступ от краев страницы при печати."
                                                    data-tooltip-ru="Отступ от краев страницы при печати."
                                                    data-tooltip-en="Margin from page edges when printing.">
                                                    Margin (mm)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="pageMargin" name="pageMargin"
                                                        min="0" max="50" step="0.5" value="{{ $formConfig['page_margin'] ?? 1 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="bodyMarginLeft" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Горизонтальный отступ основного контента от левого края."
                                                    data-tooltip-ru="Горизонтальный отступ основного контента от левого края."
                                                    data-tooltip-en="Horizontal margin of main content from left edge.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft"
                                                        min="0" max="50" step="1" value="{{ $formConfig['body_margin_left'] ?? 2 }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="accordion" id="footerSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="footerSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#footerSettingsCollapse" aria-expanded="false" aria-controls="footerSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="Настройки нижнего колонтитула формы."
                                            data-tooltip-ru="Настройки нижнего колонтитула формы."
                                            data-tooltip-en="Form footer settings.">
                                            Footer Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="footerSettingsCollapse" class="accordion-collapse collapse" aria-labelledby="footerSettingsHeading" data-bs-parent="#footerSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="footerWidth" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Ширина колонтитула в пикселях."
                                                    data-tooltip-ru="Ширина колонтитула в пикселях."
                                                    data-tooltip-en="Footer width in pixels.">
                                                    Width on pg (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                        min="400" max="1200" step="10" value="{{ $formConfig['footer_width'] ?? 800 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="footerFontSize" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Размер шрифта текста в колонтитуле."
                                                    data-tooltip-ru="Размер шрифта текста в колонтитуле."
                                                    data-tooltip-en="Footer text font size.">
                                                    Font Size (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerFontSize" name="footerFontSize"
                                                        min="6" max="20" step="0.5" value="{{ $formConfig['footer_font_size'] ?? 12 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="footerPadding" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Внутренние отступы колонтитула в формате CSS."
                                                    data-tooltip-ru="Внутренние отступы колонтитула в формате CSS."
                                                    data-tooltip-en="Footer inner padding in CSS format.">
                                                    Padding
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                        placeholder="3px 3px" value="{{ $formConfig['footer_padding'] ?? '3px 3px' }}">
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
