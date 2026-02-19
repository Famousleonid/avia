{{--
    Скрипты для настроек печати форм процессов.
    Переменные: $module, $formConfig (storage_key, ndt_table_rows, stress_table_rows, other_table_rows)
--}}
@php
    $storageKey = $formConfig['storage_key'] ?? 'processesForm_print_settings';
    $defaultNdt = $formConfig['ndt_table_rows'] ?? 17;
    $defaultStress = $formConfig['stress_table_rows'] ?? 21;
    $defaultOther = $formConfig['other_table_rows'] ?? 21;
    $defaultContainerWidth = $formConfig['container_max_width'] ?? 1200;
@endphp
<script>
if (typeof window.processesFormScriptInitialized === 'undefined') {
    window.processesFormScriptInitialized = true;

    const PRINT_SETTINGS_KEY = '{{ $storageKey }}';
    const defaultSettings = {
        pageMargin: '1mm',
        bodyWidth: '98%',
        bodyHeight: '99%',
        bodyMarginLeft: '2px',
        containerMaxWidth: '{{ $defaultContainerWidth }}px',
        containerPadding: '5px',
        containerMarginLeft: '10px',
        containerMarginRight: '10px',
        containerMaxHeight: '100vh',
        footerWidth: '800px',
        footerFontSize: '10px',
        footerPadding: '3px 3px',
        footerBottom: '0px',
        ndtTableRows: '{{ $defaultNdt }}',
        stressTableRows: '{{ $defaultStress }}',
        otherTableRows: '{{ $defaultOther }}',
        ndtProcessFontSize: '10',
        ndtTableDataFontSize: '9',
        stressTableDataFontSize: '9',
        otherTableDataFontSize: '9'
    };

    function loadPrintSettings() {
        const saved = localStorage.getItem(PRINT_SETTINGS_KEY);
        if (saved) {
            try { return JSON.parse(saved); } catch (e) { return defaultSettings; }
        }
        return defaultSettings;
    }

    window.savePrintSettings = function() {
        try {
            const saved = loadPrintSettings();
            const g = (id) => document.getElementById(id);
            const settings = {
                pageMargin: (g('pageMargin')?.value ?? parseFloat(saved.pageMargin) ?? 1) + 'mm',
                bodyWidth: (g('bodyWidth')?.value ?? parseFloat(saved.bodyWidth) ?? 98) + '%',
                bodyHeight: (g('bodyHeight')?.value ?? parseFloat(saved.bodyHeight) ?? 99) + '%',
                bodyMarginLeft: (g('bodyMarginLeft')?.value ?? parseFloat(saved.bodyMarginLeft) ?? 2) + 'px',
                containerMaxWidth: (g('containerMaxWidth')?.value ?? parseFloat(saved.containerMaxWidth) ?? 1200) + 'px',
                containerPadding: (g('containerPadding')?.value ?? parseFloat(saved.containerPadding) ?? 5) + 'px',
                containerMarginLeft: (g('containerMarginLeft')?.value ?? parseFloat(saved.containerMarginLeft) ?? 10) + 'px',
                containerMarginRight: (g('containerMarginRight')?.value ?? parseFloat(saved.containerMarginRight) ?? 10) + 'px',
                containerMaxHeight: g('containerMaxHeight')?.value ?? saved.containerMaxHeight ?? '100vh',
                footerWidth: (g('footerWidth')?.value ?? parseFloat(saved.footerWidth) ?? 800) + 'px',
                footerFontSize: (g('footerFontSize')?.value ?? parseFloat(saved.footerFontSize) ?? 10) + 'px',
                footerPadding: g('footerPadding')?.value ?? saved.footerPadding ?? '3px 3px',
                footerBottom: (g('footerBottom')?.value ?? parseFloat(saved.footerBottom) ?? 0) + 'px',
                ndtTableRows: g('ndtTableRows')?.value ?? saved.ndtTableRows ?? defaultSettings.ndtTableRows,
                stressTableRows: g('stressTableRows')?.value ?? saved.stressTableRows ?? defaultSettings.stressTableRows,
                otherTableRows: g('otherTableRows')?.value ?? saved.otherTableRows ?? defaultSettings.otherTableRows,
                ndtProcessFontSize: g('ndtProcessFontSize')?.value ?? saved.ndtProcessFontSize ?? defaultSettings.ndtProcessFontSize,
                ndtTableDataFontSize: g('ndtTableDataFontSize')?.value ?? saved.ndtTableDataFontSize ?? defaultSettings.ndtTableDataFontSize,
                stressTableDataFontSize: g('stressTableDataFontSize')?.value ?? saved.stressTableDataFontSize ?? defaultSettings.stressTableDataFontSize,
                otherTableDataFontSize: g('otherTableDataFontSize')?.value ?? saved.otherTableDataFontSize ?? defaultSettings.otherTableDataFontSize
            };
            localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);
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
        } catch (error) {
            console.error('Error saving print settings:', error);
            const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
            if (modal) modal.hide();
            setTimeout(() => window.location.reload(), 100);
        }
    };

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
        root.style.setProperty('--ndt-process-font-size', (settings.ndtProcessFontSize || defaultSettings.ndtProcessFontSize) + 'px');
        root.style.setProperty('--ndt-table-data-font-size', (settings.ndtTableDataFontSize || defaultSettings.ndtTableDataFontSize) + 'px');
        root.style.setProperty('--stress-table-data-font-size', (settings.stressTableDataFontSize || defaultSettings.stressTableDataFontSize) + 'px');
        root.style.setProperty('--other-table-data-font-size', (settings.otherTableDataFontSize || defaultSettings.otherTableDataFontSize) + 'px');
    }

    function addEmptyRowNDT(rowIndex, container) {
        if (!container) return;
        const row = document.createElement('div');
        row.className = 'row fs-85 data-row-ndt empty-row';
        row.setAttribute('data-row-index', rowIndex);
        row.innerHTML = '<div class="col-1 border-l-b details-row text-center" style="height: 32px"></div><div class="col-3 border-l-b details-row text-center" style="height: 32px"></div><div class="col-3 border-l-b details-row text-center" style="height: 32px"></div><div class="col-2 border-l-b details-row text-center" style="height: 32px"></div><div class="col-1 border-l-b details-row text-center" style="height: 32px"></div><div class="col-1 border-l-b details-row text-center" style="height: 32px"></div><div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>';
        container.appendChild(row);
    }

    function addEmptyRowRegular(rowIndex, container, isStress = false) {
        if (!container) return;
        const row = document.createElement('div');
        row.className = 'row empty-row data-row';
        row.setAttribute('data-row-index', rowIndex);
        if (isStress) row.setAttribute('data-stress', 'true');
        row.innerHTML = '<div class="col-1 border-l-b text-center" style="height: 32px"></div><div class="col-2 border-l-b text-center" style="height: 32px"></div><div class="col-2 border-l-b text-center" style="height: 32px"></div><div class="col-4 border-l-b text-center" style="height: 32px"></div><div class="col-1 border-l-b text-center" style="height: 32px"></div><div class="col-2 border-l-b-r text-center" style="height: 32px"></div>';
        container.appendChild(row);
    }

    function applyTableRowLimits(settings, container) {
        if (!settings) settings = loadPrintSettings();
        setTimeout(function() {
            try {
                const searchContainer = container || document;
                const ndtMaxRows = parseInt(settings.ndtTableRows) || 17;
                const stressMaxRows = parseInt(settings.stressTableRows) || 21;
                const otherMaxRows = parseInt(settings.otherTableRows) || 21;

                const processRows = function(rows, maxRows, callback) {
                    if (!rows || rows.length === 0) { if (callback) setTimeout(() => callback(0, 0), 0); return; }
                    const rowsArray = Array.from(rows);
                    let maxIndex = 0;
                    let dataRows = 0;
                    for (const row of rowsArray) {
                        const ri = parseInt(row.getAttribute('data-row-index')) || 0;
                        if (ri > maxIndex) maxIndex = ri;
                        if (!row.classList.contains('empty-row')) dataRows++;
                        row.classList.remove('print-hide-row');
                    }
                    if (callback) setTimeout(() => callback(maxIndex, dataRows), 0);
                };

                const createFullPageBlocks = function(block, dataContainer, rowSelector, maxRows) {
                    block.parentNode.querySelectorAll('.form-page-block-continuation').forEach(el => el.remove());
                    const rows = Array.from(dataContainer.querySelectorAll(rowSelector));
                    if (rows.length <= maxRows) return;
                    const chunks = [];
                    for (let i = 0; i < rows.length; i += maxRows) chunks.push(rows.slice(i, i + maxRows));
                    dataContainer.innerHTML = '';
                    chunks[0].forEach(r => dataContainer.appendChild(r));
                    const containerClass = dataContainer.classList.contains('ndt-data-container') ? '.ndt-data-container' : '.data-page';
                    for (let c = 1; c < chunks.length; c++) {
                        const blockClone = block.cloneNode(true);
                        blockClone.classList.remove('form-page-block-first');
                        blockClone.classList.add('form-page-block-continuation');
                        const cloneContainer = blockClone.querySelector(containerClass);
                        if (cloneContainer) {
                            cloneContainer.innerHTML = '';
                            chunks[c].forEach(r => cloneContainer.appendChild(r));
                        }
                        block.parentNode.insertBefore(blockClone, block.nextSibling);
                    }
                };

                const ndtContainer = searchContainer.querySelector('.ndt-data-container');
                if (ndtContainer) {
                    const ndtRows = ndtContainer.querySelectorAll('.data-row-ndt[data-row-index]');
                    processRows(ndtRows, ndtMaxRows, function(maxIndex, dataRows) {
                        ndtRows.forEach(r => { if (r.classList.contains('empty-row') && (parseInt(r.getAttribute('data-row-index')) || 0) > ndtMaxRows) r.remove(); });
                        const remaining = ndtContainer.querySelectorAll('.data-row-ndt[data-row-index]');
                        let lastIdx = 0;
                        remaining.forEach(r => { const ri = parseInt(r.getAttribute('data-row-index')) || 0; if (ri > lastIdx) lastIdx = ri; });
                        if (lastIdx < ndtMaxRows) for (let i = lastIdx + 1; i <= ndtMaxRows; i++) addEmptyRowNDT(i, ndtContainer);
                        const block = searchContainer.querySelector('.form-page-block');
                        if (block) createFullPageBlocks(block, ndtContainer, '.data-row-ndt[data-row-index]', ndtMaxRows);
                    });
                }

                const stressRows = searchContainer.querySelectorAll('.data-page .data-row[data-stress="true"][data-row-index]');
                const stressContainer = stressRows.length ? stressRows[0].closest('.data-page') : null;
                if (stressContainer) {
                    processRows(stressRows, stressMaxRows, function(maxIndex, dataRows) {
                        stressRows.forEach(r => { if (r.classList.contains('empty-row') && (parseInt(r.getAttribute('data-row-index')) || 0) > stressMaxRows) r.remove(); });
                        const remaining = stressContainer.querySelectorAll('.data-row[data-stress="true"][data-row-index]');
                        let lastIdx = 0;
                        remaining.forEach(r => { const ri = parseInt(r.getAttribute('data-row-index')) || 0; if (ri > lastIdx) lastIdx = ri; });
                        if (lastIdx < stressMaxRows) for (let i = lastIdx + 1; i <= stressMaxRows; i++) addEmptyRowRegular(i, stressContainer, true);
                        const block = searchContainer.querySelector('.form-page-block');
                        if (block) createFullPageBlocks(block, stressContainer, '.data-row[data-stress="true"][data-row-index]', stressMaxRows);
                    });
                }

                const allOtherRows = searchContainer.querySelectorAll('.data-page .data-row[data-row-index]');
                const otherRows = Array.from(allOtherRows).filter(r => r.getAttribute('data-stress') !== 'true');
                const otherContainer = otherRows.length ? otherRows[0].closest('.data-page') : null;
                if (otherContainer) {
                    processRows(otherRows, otherMaxRows, function(maxIndex, dataRows) {
                        otherRows.forEach(r => { if (r.classList.contains('empty-row') && (parseInt(r.getAttribute('data-row-index')) || 0) > otherMaxRows) r.remove(); });
                        const remaining = Array.from(otherContainer.querySelectorAll('.data-row[data-row-index]')).filter(r => r.getAttribute('data-stress') !== 'true');
                        let lastIdx = 0;
                        remaining.forEach(r => { const ri = parseInt(r.getAttribute('data-row-index')) || 0; if (ri > lastIdx) lastIdx = ri; });
                        if (lastIdx < otherMaxRows) for (let i = lastIdx + 1; i <= otherMaxRows; i++) addEmptyRowRegular(i, otherContainer, false);
                        const block = searchContainer.querySelector('.form-page-block');
                        if (block) createFullPageBlocks(block, otherContainer, '.data-row[data-row-index]:not([data-stress="true"])', otherMaxRows);
                    });
                }
            } catch (e) { console.error('Error in applyTableRowLimits:', e); }
        }, 0);
    }

    function loadSettingsToForm(settings) {
        const parseNum = (v) => parseFloat(String(v).replace(/[^\d.-]/g, '')) || 0;
        const el = id => document.getElementById(id);
        if (el('pageMargin')) el('pageMargin').value = parseNum(settings.pageMargin) || 1;
        if (el('bodyWidth')) el('bodyWidth').value = parseNum(settings.bodyWidth) || 98;
        if (el('bodyHeight')) el('bodyHeight').value = parseNum(settings.bodyHeight) || 99;
        if (el('bodyMarginLeft')) el('bodyMarginLeft').value = parseNum(settings.bodyMarginLeft) || 2;
        if (el('containerMaxWidth')) el('containerMaxWidth').value = parseNum(settings.containerMaxWidth) || 1200;
        if (el('containerPadding')) el('containerPadding').value = parseNum(settings.containerPadding) || 5;
        if (el('containerMarginLeft')) el('containerMarginLeft').value = parseNum(settings.containerMarginLeft) || 10;
        if (el('containerMarginRight')) el('containerMarginRight').value = parseNum(settings.containerMarginRight) || 10;
        if (el('containerMaxHeight')) el('containerMaxHeight').value = settings.containerMaxHeight || '100vh';
        if (el('footerWidth')) el('footerWidth').value = parseNum(settings.footerWidth) || 800;
        if (el('footerFontSize')) el('footerFontSize').value = parseNum(settings.footerFontSize) || 10;
        if (el('footerPadding')) el('footerPadding').value = settings.footerPadding || '3px 3px';
        if (el('footerBottom')) el('footerBottom').value = parseNum(settings.footerBottom) || 0;
        if (el('ndtTableRows')) el('ndtTableRows').value = settings.ndtTableRows || defaultSettings.ndtTableRows;
        if (el('stressTableRows')) el('stressTableRows').value = settings.stressTableRows || defaultSettings.stressTableRows;
        if (el('otherTableRows')) el('otherTableRows').value = settings.otherTableRows || defaultSettings.otherTableRows;
        if (el('ndtProcessFontSize')) el('ndtProcessFontSize').value = parseNum(settings.ndtProcessFontSize) || 10;
        if (el('ndtTableDataFontSize')) el('ndtTableDataFontSize').value = parseNum(settings.ndtTableDataFontSize) || 9;
        if (el('stressTableDataFontSize')) el('stressTableDataFontSize').value = parseNum(settings.stressTableDataFontSize) || 9;
        if (el('otherTableDataFontSize')) el('otherTableDataFontSize').value = parseNum(settings.otherTableDataFontSize) || 9;
    }

    function resetPrintSettings() {
        if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
        }
    }

    if (!window.processesFormDOMInitialized) {
        window.processesFormDOMInitialized = true;
        document.addEventListener('DOMContentLoaded', function() {
            const settings = loadPrintSettings();
            applyPrintSettings(settings);
            loadSettingsToForm(settings);
            // Применяем лимиты строк при загрузке (из Print Settings), не только при печати
            setTimeout(function() {
                const wrappers = document.querySelectorAll('.form-wrapper');
                const containers = wrappers.length ? wrappers : document.querySelectorAll('.container-fluid');
                containers.forEach(function(formContainer) {
                    const ndtContainer = formContainer.querySelector('.ndt-data-container');
                    const dataPages = formContainer.querySelectorAll('.data-page');
                    if (ndtContainer || dataPages.length) applyTableRowLimits(settings, formContainer);
                });
            }, 50);
            const modal = document.getElementById('printSettingsModal');
            if (modal) modal.addEventListener('show.bs.modal', () => loadSettingsToForm(loadPrintSettings()));
        });
    }

    window.addEventListener('beforeprint', function() {
        const wrappers = document.querySelectorAll('.form-wrapper');
        const containers = wrappers.length ? wrappers : document.querySelectorAll('.container-fluid');
        const settings = loadPrintSettings();
        containers.forEach(function(formContainer) {
            const ndtContainer = formContainer.querySelector('.ndt-data-container');
            const dataPages = formContainer.querySelectorAll('.data-page');
            if (ndtContainer || dataPages.length) applyTableRowLimits(settings, formContainer);
        });
    });
}
</script>
