{{--
    Скрипты Print Settings для Special Process Form (specProcessForm, specProcessFormEmp).
--}}
@php
    $formConfig = $formConfig ?? config('process_forms.spec_process_form', []);
    $storageKey = $formConfig['storage_key'] ?? 'specProcessForm_print_settings';
@endphp
<script>
(function() {
    const PRINT_SETTINGS_KEY = '{{ $storageKey }}_v4';
    const SETTINGS_VERSION = 4;
    const defaultSettings = {
        processTableMinRows: {{ $formConfig['process_table_min_rows'] ?? 10 }},
        processTableExtraEmptyRows: {{ $formConfig['process_table_extra_empty_rows'] ?? 0 }},
        processTableRowHeight: {{ $formConfig['process_table_row_height'] ?? 22 }},
        processNameFontSize: {{ $formConfig['process_name_font_size'] ?? 11 }},
        componentDescriptionFontSize: {{ $formConfig['component_description_font_size'] ?? 11 }},
        componentPartNoFontSize: {{ $formConfig['component_part_no_font_size'] ?? 11 }},
        componentSerialNoFontSize: {{ $formConfig['component_serial_no_font_size'] ?? 11 }}
    };

    function loadPrintSettings() {
        localStorage.removeItem('{{ $storageKey }}');
        const saved = localStorage.getItem(PRINT_SETTINGS_KEY);
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                if (parsed._version !== SETTINGS_VERSION) {
                    localStorage.removeItem(PRINT_SETTINGS_KEY);
                    return defaultSettings;
                }
                delete parsed.processTableEmptyRows;
                delete parsed.processTableMinRows;
                return { ...defaultSettings, ...parsed };
            } catch (e) { return defaultSettings; }
        }
        return defaultSettings;
    }

    function applyPrintSettings(settings) {
        const root = document.documentElement;
        root.style.setProperty('--spec-process-row-height', (settings.processTableRowHeight || defaultSettings.processTableRowHeight) + 'px');
        root.style.setProperty('--spec-process-name-font-size', (settings.processNameFontSize || defaultSettings.processNameFontSize) + 'px');
        root.style.setProperty('--spec-component-description-font-size', (settings.componentDescriptionFontSize || defaultSettings.componentDescriptionFontSize) + 'px');
        root.style.setProperty('--spec-component-part-no-font-size', (settings.componentPartNoFontSize || defaultSettings.componentPartNoFontSize) + 'px');
        root.style.setProperty('--spec-component-serial-no-font-size', (settings.componentSerialNoFontSize || defaultSettings.componentSerialNoFontSize) + 'px');
    }

    function addEmptyProcessRows(extraEmptyRows, columnCount) {
        const containers = document.querySelectorAll('.spec-process-table-body');
        containers.forEach(function(container, index) {
            const dataRows = container.querySelectorAll('.spec-process-name-row').length;
            const existing = container.querySelectorAll('.spec-process-empty-row').length;
            const minRows = Math.max(10, dataRows);
            const minEmpty = index === 0 ? Math.max(0, minRows - dataRows) : 0;
            const totalNeeded = minEmpty + (index === 0 ? (extraEmptyRows || 0) : 0);
            const toAdd = Math.max(0, totalNeeded - existing);
            for (let i = 0; i < toAdd; i++) {
                const row = document.createElement('div');
                row.className = 'row g-0 fs-7 spec-process-empty-row';
                const cells = [];
                for (let c = 0; c < columnCount; c++) {
                    const isLast = c === columnCount - 1;
                    cells.push('<div class="col ' + (isLast ? 'border-l-b-r' : 'border-l-b') + ' text-center spec-process-row-cell" style="position:relative"><div class="border-r spec-process-row-inner"></div><div class="spec-process-empty-divider"></div></div>');
                }
                row.innerHTML = '<div class="col-2 border-l-b ps-1 spec-process-name-cell"><div class="spec-process-name-inner"></div></div><div class="col-10"><div class="row g-0">' + cells.join('') + '</div></div>';
                container.appendChild(row);
            }
            const toRemove = existing - totalNeeded;
            if (toRemove > 0) {
                const emptyRows = container.querySelectorAll('.spec-process-empty-row');
                for (let i = 0; i < toRemove && emptyRows.length > 0; i++) {
                    emptyRows[emptyRows.length - 1 - i].remove();
                }
            }
        });
    }

    function removeAllEmptyRows() {
        document.querySelectorAll('.spec-process-empty-row').forEach(el => el.remove());
    }

    window.specProcessFormSavePrintSettings = function() {
        try {
            const g = (id) => document.getElementById(id);
            const settings = {
                processTableExtraEmptyRows: Math.max(0, parseInt(g('processTableExtraEmptyRows')?.value, 10) || 0),
                processTableRowHeight: parseInt(g('processTableRowHeight')?.value, 10) || defaultSettings.processTableRowHeight,
                processNameFontSize: parseFloat(g('processNameFontSize')?.value) || defaultSettings.processNameFontSize,
                componentDescriptionFontSize: parseFloat(g('componentDescriptionFontSize')?.value) || defaultSettings.componentDescriptionFontSize,
                componentPartNoFontSize: parseFloat(g('componentPartNoFontSize')?.value) || defaultSettings.componentPartNoFontSize,
                componentSerialNoFontSize: parseFloat(g('componentSerialNoFontSize')?.value) || defaultSettings.componentSerialNoFontSize
            };
            settings._version = SETTINGS_VERSION;
            localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);
            removeAllEmptyRows();
            addEmptyProcessRows(settings.processTableExtraEmptyRows, 6);
            const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
            if (modal) {
                document.getElementById('printSettingsModal').addEventListener('hidden.bs.modal', function reload() {
                    this.removeEventListener('hidden.bs.modal', reload);
                    setTimeout(() => window.location.reload(), 100);
                }, { once: true });
                modal.hide();
            } else {
                setTimeout(() => window.location.reload(), 100);
            }
        } catch (e) {
            console.error('Error saving print settings:', e);
            setTimeout(() => window.location.reload(), 100);
        }
    };

    window.specProcessFormResetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            applyPrintSettings(defaultSettings);
            removeAllEmptyRows();
            addEmptyProcessRows(defaultSettings.processTableExtraEmptyRows, 6);
            const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
            if (modal) {
                document.getElementById('printSettingsModal').addEventListener('hidden.bs.modal', function reload() {
                    this.removeEventListener('hidden.bs.modal', reload);
                    setTimeout(() => window.location.reload(), 100);
                }, { once: true });
                modal.hide();
            } else {
                setTimeout(() => window.location.reload(), 100);
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const settings = loadPrintSettings();
        applyPrintSettings(settings);
        const columnCount = parseInt(document.querySelector('.spec-process-table-body')?.dataset?.columnCount) || 6;
        addEmptyProcessRows(settings.processTableExtraEmptyRows || 0, columnCount);

        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                const s = loadPrintSettings();
                const g = (id) => document.getElementById(id);
                if (g('processTableExtraEmptyRows')) g('processTableExtraEmptyRows').value = s.processTableExtraEmptyRows || 0;
                if (g('processTableRowHeight')) g('processTableRowHeight').value = s.processTableRowHeight;
                if (g('processNameFontSize')) g('processNameFontSize').value = s.processNameFontSize;
                if (g('componentDescriptionFontSize')) g('componentDescriptionFontSize').value = s.componentDescriptionFontSize;
                if (g('componentPartNoFontSize')) g('componentPartNoFontSize').value = s.componentPartNoFontSize;
                if (g('componentSerialNoFontSize')) g('componentSerialNoFontSize').value = s.componentSerialNoFontSize;
            });
        }
    });
})();
</script>
