{{--
    Модальное окно настроек печати для форм процессов.
    Переменные: $module, $formConfig, $showFormTypes — какие настройки таблиц показывать:
    ['ndt'] — только NDT, ['stress'] — только Stress Relief, ['other'] — только Other,
    ['ndt','stress','other'] — все (для packageForms)
    $showOtherTableRowLimit — показывать поле «Other Table (row)» (лимит строк для .data-page/.data-row).
    На формах без такой разметки (например Part Traveler) передайте false.
--}}
@php
    $formConfig = $formConfig ?? config('process_forms.' . ($module ?? 'tdr-processes'), config('process_forms.tdr-processes'));
    $showFormTypes = $showFormTypes ?? ['ndt', 'stress', 'other'];
    $showNdt = in_array('ndt', $showFormTypes);
    $showStress = in_array('stress', $showFormTypes);
    $showOther = in_array('other', $showFormTypes);
    $showOtherTableRowLimit = $showOtherTableRowLimit ?? true;
    $showTableRowCountInputs = $showNdt || $showStress || ($showOther && $showOtherTableRowLimit);
    $isTravelForm = ($module ?? '') === 'travel-form';
    $otherTableRowsInputValue = $isTravelForm
        ? (int) ($formConfig['traveler_table_total_rows'] ?? $formConfig['other_table_rows'] ?? 14)
        : (int) ($formConfig['other_table_rows'] ?? 21);
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
                        @if($showTableRowCountInputs)
                        <h5 class="mb-3" data-bs-toggle="tooltip" data-bs-placement="top"
                            title="Настройки количества строк в таблицах. Строки сверх лимита скрываются при печати."
                            data-tooltip-ru="Настройки количества строк в таблицах. Строки сверх лимита скрываются при печати."
                            data-tooltip-en="Table row settings. Rows exceeding the limit are hidden when printing.">
                            📊 Tables
                        </h5>
                        <div class="row mb-3">
                            @if($showNdt)
                            <div class="{{ ($showNdt && !$showStress && !$showOther) ? 'col-12' : 'col-md-4' }}">
                                <label for="ndtTableRows" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Максимальное количество строк в таблице NDT."
                                    data-tooltip-ru="Максимальное количество строк в таблице NDT."
                                    data-tooltip-en="Maximum number of rows in NDT table.">
                                    NDT Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="ndtTableRows" name="ndtTableRows"
                                        min="1" max="100" step="1" value="{{ $formConfig['ndt_table_rows'] ?? 17 }}">
                                </div>
                            </div>
                            @endif
                            @if($showStress)
                            <div class="{{ ($showStress && !$showNdt && !$showOther) ? 'col-12' : 'col-md-4' }}">
                                <label for="stressTableRows" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Максимальное количество строк в таблице Stress Relief."
                                    data-tooltip-ru="Максимальное количество строк в таблице Stress Relief."
                                    data-tooltip-en="Maximum number of rows in Stress Relief table.">
                                    Stress Relief Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="stressTableRows" name="stressTableRows"
                                        min="1" max="100" step="1" value="{{ $formConfig['stress_table_rows'] ?? 21 }}">
                                </div>
                            </div>
                            @endif
                            @if($showOther && $showOtherTableRowLimit)
                            <div class="{{ ($showOther && !$showNdt && !$showStress) ? 'col-12' : 'col-md-4' }}">
                                <label for="otherTableRows" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                    @if($isTravelForm)
                                    title="Сколько всего строк в таблице Part Traveler (заполненные и пустые). Автоматически не меньше числа строк с процессами."
                                    data-tooltip-ru="Сколько всего строк в таблице Part Traveler (заполненные и пустые). Автоматически не меньше числа строк с процессами."
                                    data-tooltip-en="Total rows on Part Traveler (filled and blank). Never less than the number of process rows."
                                    @else
                                    title="Максимальное количество строк в таблицах других процессов."
                                    data-tooltip-ru="Максимальное количество строк в таблицах других процессов."
                                    data-tooltip-en="Maximum number of rows in other process tables."
                                    @endif>
                                    @if($isTravelForm)
                                        Traveler table — total rows
                                    @else
                                        Other Table (row)
                                    @endif
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="otherTableRows" name="otherTableRows"
                                        min="1" max="100" step="1" value="{{ $otherTableRowsInputValue }}">
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
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
                            @if($showNdt)
                            <div class="col-md-4">
                                <label for="ndtProcessFontSize" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Размер шрифта для блока процессов (MAGNETIC PARTICLE, LIQUID PENETRANT и т.д.)."
                                    data-tooltip-ru="Размер шрифта для блока процессов (MAGNETIC PARTICLE, LIQUID PENETRANT и т.д.)."
                                    data-tooltip-en="Font size for process block (MAGNETIC PARTICLE, LIQUID PENETRANT, etc.).">
                                    NDT Process Font (px)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="ndtProcessFontSize" name="ndtProcessFontSize"
                                        min="6" max="24" step="0.5" value="{{ $formConfig['ndt_process_font_size'] ?? 10 }}">
                                </div>
                            </div>
                            @endif
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
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                    min="500" max="2000" step="10" value="{{ $formConfig['container_max_width'] ?? 1200 }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="containerPadding" class="form-label">Padding (px)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                    min="0" max="50" step="1" value="5">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="containerMaxHeight" class="form-label">Max Height</label>
                                            <select class="form-control" id="containerMaxHeight" name="containerMaxHeight">
                                                <option value="100vh">100vh (full height)</option>
                                                <option value="90vh">90vh</option>
                                                <option value="80vh">80vh</option>
                                                <option value="70vh">70vh</option>
                                                <option value="auto">auto (automatic)</option>
                                            </select>
                                        </div>
                                        @if($showNdt)
                                        <div class="col-md-4 mb-3">
                                            <label for="ndtTableDataFontSize" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Размер шрифта данных в таблице NDT."
                                                data-tooltip-ru="Размер шрифта данных в таблице NDT."
                                                data-tooltip-en="Font size for NDT table data.">
                                                NDT Table Data Font (px)
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="ndtTableDataFontSize" name="ndtTableDataFontSize"
                                                    min="6" max="20" step="0.5" value="{{ $formConfig['ndt_table_data_font_size'] ?? 9 }}">
                                            </div>
                                        </div>
                                        @endif
                                        @if($showStress)
                                        <div class="col-md-4 mb-3">
                                            <label for="stressTableDataFontSize" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Размер шрифта данных в таблице Stress Relief."
                                                data-tooltip-ru="Размер шрифта данных в таблице Stress Relief."
                                                data-tooltip-en="Font size for Stress Relief table data.">
                                                Stress Table Data Font (px)
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="stressTableDataFontSize" name="stressTableDataFontSize"
                                                    min="6" max="20" step="0.5" value="{{ $formConfig['stress_table_data_font_size'] ?? 9 }}">
                                            </div>
                                        </div>
                                        @endif
                                        @if($showOther)
                                        <div class="col-md-4 mb-3">
                                            <label for="otherTableDataFontSize" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Размер шрифта данных в таблицах Other (CAD, Machining и т.д.)."
                                                data-tooltip-ru="Размер шрифта данных в таблицах Other (CAD, Machining и т.д.)."
                                                data-tooltip-en="Font size for Other process table data.">
                                                Other Table Data Font (px)
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="otherTableDataFontSize" name="otherTableDataFontSize"
                                                    min="6" max="20" step="0.5" value="{{ $formConfig['other_table_data_font_size'] ?? 9 }}">
                                            </div>
                                        </div>
                                        @endif
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
                                            <input type="number" class="form-control" id="bodyWidth" name="bodyWidth" min="50" max="100" step="1" value="98">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="bodyHeight" class="form-label">Height (%)</label>
                                            <input type="number" class="form-control" id="bodyHeight" name="bodyHeight" min="50" max="100" step="1" value="99">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="pageMargin" class="form-label">Margin (mm)</label>
                                            <input type="number" class="form-control" id="pageMargin" name="pageMargin" min="0" max="50" step="0.5" value="1">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="bodyMarginLeft" class="form-label">Left Margin (px)</label>
                                            <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft" min="0" max="50" step="1" value="2">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="containerMarginLeft" class="form-label">Table Left Margin (px)</label>
                                            <input type="number" class="form-control" id="containerMarginLeft" name="containerMarginLeft" min="0" max="50" step="1" value="10">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="containerMarginRight" class="form-label">Table Right Margin (px)</label>
                                            <input type="number" class="form-control" id="containerMarginRight" name="containerMarginRight" min="0" max="50" step="1" value="10">
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
                                            <input type="number" class="form-control" id="footerWidth" name="footerWidth" min="400" max="1200" step="10" value="800">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="footerFontSize" class="form-label">Font Size (px)</label>
                                            <input type="number" class="form-control" id="footerFontSize" name="footerFontSize" min="6" max="20" step="0.5" value="10">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="footerPadding" class="form-label">Padding</label>
                                            <input type="text" class="form-control" id="footerPadding" name="footerPadding" placeholder="3px 3px" value="3px 3px">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="footerBottom" class="form-label">Bottom Margin (px)</label>
                                            <input type="number" class="form-control" id="footerBottom" name="footerBottom" min="0" max="50" step="1" value="0">
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
