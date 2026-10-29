{{--
    Модальное окно настроек печати для Special Process Form (specProcessForm, specProcessFormEmp).
    Управление: таблица процессов (пустые строки, высота, шрифт), таблица компонентов (шрифты).
--}}
@php
    $formConfig = $formConfig ?? config('process_forms.spec_process_form', []);
@endphp
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">⚙️ Print Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <div class="mb-4">
                        <h5 class="mb-3">📊 Processes Table</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="processTableExtraEmptyRows" class="form-label">Extra Empty Rows</label>
                                <input type="number" class="form-control" id="processTableExtraEmptyRows" name="processTableExtraEmptyRows"
                                    min="0" max="50" step="1" value="{{ $formConfig['process_table_extra_empty_rows'] ?? 0 }}"
                                    title="Дополнительные пустые строки (добавьте сами если надо).">
                            </div>
                            <div class="col-md-4">
                                <label for="processTableRowHeight" class="form-label">Row Height (px)</label>
                                <input type="number" class="form-control" id="processTableRowHeight" name="processTableRowHeight"
                                    min="14" max="50" step="1" value="{{ $formConfig['process_table_row_height'] ?? 22 }}">
                            </div>
                            <div class="col-md-4">
                                <label for="processNameFontSize" class="form-label">Process Name Font (px)</label>
                                <input type="number" class="form-control" id="processNameFontSize" name="processNameFontSize"
                                    min="6" max="24" step="0.5" value="{{ $formConfig['process_name_font_size'] ?? 14 }}">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h5 class="mb-3">📋 Components Table</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="componentDescriptionFontSize" class="form-label">Description Font (px)</label>
                                <input type="number" class="form-control" id="componentDescriptionFontSize" name="componentDescriptionFontSize"
                                    min="6" max="24" step="0.5" value="{{ $formConfig['component_description_font_size'] ?? 14 }}">
                            </div>
                            <div class="col-md-4">
                                <label for="componentPartNoFontSize" class="form-label">Part No. Font (px)</label>
                                <input type="number" class="form-control" id="componentPartNoFontSize" name="componentPartNoFontSize"
                                    min="6" max="24" step="0.5" value="{{ $formConfig['component_part_no_font_size'] ?? 14 }}">
                            </div>
                            <div class="col-md-4">
                                <label for="componentSerialNoFontSize" class="form-label">Serial No. Font (px)</label>
                                <input type="number" class="form-control" id="componentSerialNoFontSize" name="componentSerialNoFontSize"
                                    min="6" max="24" step="0.5" value="{{ $formConfig['component_serial_no_font_size'] ?? 14 }}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="specProcessFormResetPrintSettings()">Reset to Default</button>
                <button type="button" class="btn btn-primary" onclick="specProcessFormSavePrintSettings()">Save Settings</button>
            </div>
        </div>
    </div>
</div>
