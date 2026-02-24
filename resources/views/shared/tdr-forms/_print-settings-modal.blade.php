{{--
    Модальное окно настроек печати для TDR форм (ndtFormStd, cadFormStd, stressFormStd, paintFormStd).
    Как у processesForm — конфиг-драйвен, единая структура.
    Переменные: $formType (ndtFormStd|cadFormStd|stressFormStd|paintFormStd), $formConfig
--}}
@php
    $formType = $formType ?? 'ndtFormStd';
    $formConfig = $formConfig ?? config('tdr_forms.' . $formType, config('tdr_forms.ndtFormStd'));
    $tableRowsKey = $formConfig['table_rows_key'] ?? 'ndtTableRows';
    $tableRowsDefault = $formConfig['table_rows_default'] ?? 16;
    $tableLabel = match($formType) {
        'ndtFormStd' => 'NDT Table (row)',
        'cadFormStd' => 'CAD Table (row)',
        'stressFormStd' => 'Stress Relief Table (row)',
        'paintFormStd' => 'Paint Table (row)',
        default => 'Table (row)',
    };
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
                            title="Настройки количества строк в таблице. Строки распределяются по страницам. Настройки применяются автоматически при загрузке."
                            data-tooltip-ru="Настройки количества строк в таблице. Строки распределяются по страницам. Настройки применяются автоматически при загрузке."
                            data-tooltip-en="Table row settings. Rows are distributed across pages. Settings are applied automatically on page load.">
                            📊 Tables
                        </h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="componentNameFontSize" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Размер шрифта для Component Name (название компонента в шапке формы)."
                                    data-tooltip-ru="Размер шрифта для Component Name (название компонента в шапке формы)."
                                    data-tooltip-en="Font size for Component Name (component name in form header).">
                                    Component Name Font (px)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="componentNameFontSize" name="componentNameFontSize"
                                        min="6" max="24" step="0.5" value="{{ $formConfig['component_name_font_size'] ?? 12 }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="{{ $tableRowsKey }}" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Максимальное количество строк на одной странице."
                                    data-tooltip-ru="Максимальное количество строк на одной странице."
                                    data-tooltip-en="Maximum number of rows per page.">
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
                                        Table Setting
                                    </button>
                                </h2>
                                <div id="tableSettingsCollapse" class="accordion-collapse collapse" aria-labelledby="tableSettingsHeading" data-bs-parent="#tableSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMaxWidth" class="form-label">Max Width (px)</label>
                                                <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                    min="500" max="2000" step="10" value="{{ $formConfig['container_max_width'] ?? 920 }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerPadding" class="form-label">Padding (px)</label>
                                                <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                    min="0" max="50" step="1" value="{{ $formConfig['container_padding'] ?? 5 }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginLeft" class="form-label">Left Margin (px)</label>
                                                <input type="number" class="form-control" id="containerMarginLeft" name="containerMarginLeft"
                                                    min="0" max="50" step="1" value="{{ $formConfig['container_margin_left'] ?? 10 }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginRight" class="form-label">Right Margin (px)</label>
                                                <input type="number" class="form-control" id="containerMarginRight" name="containerMarginRight"
                                                    min="0" max="50" step="1" value="{{ $formConfig['container_margin_right'] ?? 10 }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion mb-3" id="pageSettingsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="pageSettingsHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#pageSettingsCollapse" aria-expanded="false" aria-controls="pageSettingsCollapse">
                                    Page Setting
                                </button>
                            </h2>
                            <div id="pageSettingsCollapse" class="accordion-collapse collapse" aria-labelledby="pageSettingsHeading" data-bs-parent="#pageSettingsAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="bodyWidth" class="form-label">Width (%)</label>
                                            <input type="number" class="form-control" id="bodyWidth" name="bodyWidth" min="50" max="100" step="1" value="{{ $formConfig['body_width'] ?? 98 }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="bodyHeight" class="form-label">Height (%)</label>
                                            <input type="number" class="form-control" id="bodyHeight" name="bodyHeight" min="50" max="100" step="1" value="{{ $formConfig['body_height'] ?? 99 }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="pageMargin" class="form-label">Margin (mm)</label>
                                            <input type="number" class="form-control" id="pageMargin" name="pageMargin" min="0" max="50" step="0.5" value="{{ $formConfig['page_margin'] ?? 1 }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="bodyMarginLeft" class="form-label">Left Margin (px)</label>
                                            <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft" min="0" max="50" step="1" value="{{ $formConfig['body_margin_left'] ?? 2 }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion mb-3" id="footerSettingsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="footerSettingsHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#footerSettingsCollapse" aria-expanded="false" aria-controls="footerSettingsCollapse">
                                    Footer Setting
                                </button>
                            </h2>
                            <div id="footerSettingsCollapse" class="accordion-collapse collapse" aria-labelledby="footerSettingsHeading" data-bs-parent="#footerSettingsAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="footerWidth" class="form-label">Width on pg (px)</label>
                                            <input type="number" class="form-control" id="footerWidth" name="footerWidth" min="400" max="1200" step="10" value="{{ $formConfig['footer_width'] ?? 800 }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="footerFontSize" class="form-label">Font Size (px)</label>
                                            <input type="number" class="form-control" id="footerFontSize" name="footerFontSize" min="6" max="20" step="0.5" value="{{ $formConfig['footer_font_size'] ?? 10 }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="footerPadding" class="form-label">Padding</label>
                                            <input type="text" class="form-control" id="footerPadding" name="footerPadding" placeholder="3px 3px" value="{{ $formConfig['footer_padding'] ?? '3px 3px' }}">
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
