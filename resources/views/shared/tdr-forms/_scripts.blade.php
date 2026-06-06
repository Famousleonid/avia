{{--
    Общие скрипты для настроек печати TDR форм (ndtFormStd, cadFormStd, stressFormStd, paintFormStd).
    Как у processesForm — конфиг-драйвен.
    Переменные: $formType, $formConfig
    Форма должна определить window.tdrFormApplyTableRowLimits(settings) для применения лимитов строк.
--}}
@php
    $formType = $formType ?? 'ndtFormStd';
    $formConfig = $formConfig ?? config('tdr_forms.' . $formType, config('tdr_forms.ndtFormStd'));
    $tableRowsKey = $formConfig['table_rows_key'] ?? 'ndtTableRows';
    $tableRowsDefault = $formConfig['table_rows_default'] ?? 16;
@endphp
<script>
(function() {
    const PRINT_SETTINGS_KEY = '{{ $formConfig['storage_key'] ?? 'ndtFormStd_print_settings' }}';
    const TOOLTIP_LANG_KEY = '{{ $formConfig['tooltip_lang_key'] ?? 'ndtFormStd_tooltip_lang' }}';
    const LOCKED_PRINT_SETTINGS = @json($formConfig['locked_settings'] ?? []);

    const defaultSettings = {
        pageMargin: '{{ $formConfig['page_margin'] ?? 1 }}mm',
        bodyWidth: '{{ $formConfig['body_width'] ?? 98 }}%',
        bodyHeight: '{{ $formConfig['body_height'] ?? 99 }}%',
        bodyMarginLeft: '{{ $formConfig['body_margin_left'] ?? 2 }}px',
        printScale: '100',
        containerMaxWidth: '{{ $formConfig['container_max_width'] ?? 920 }}px',
        containerPadding: '{{ $formConfig['container_padding'] ?? 5 }}px',
        containerMarginLeft: '{{ $formConfig['container_margin_left'] ?? 10 }}px',
        containerMarginRight: '{{ $formConfig['container_margin_right'] ?? 10 }}px',
        footerWidth: '{{ $formConfig['footer_width'] ?? 800 }}px',
        footerFontSize: '{{ $formConfig['footer_font_size'] ?? 12 }}px',
        footerPadding: '{{ $formConfig['footer_padding'] ?? '3px 3px' }}',
        componentNameFontSize: '{{ (int) ($formConfig['component_name_font_size'] ?? 16) }}',
        headerDataFontSize: '{{ $formConfig['header_data_font_size'] ?? 16 }}',
        tableDataFontSize: '{{ $formConfig['table_data_font_size'] ?? 13 }}',
        '{{ $tableRowsKey }}': '{{ $tableRowsDefault }}'
    };

    function normalizePrintSettings(settings) {
        return Object.assign({}, defaultSettings, settings || {}, LOCKED_PRINT_SETTINGS);
    }

    function loadPrintSettings() {
        const saved = window.UserScopedStorage.getItem(PRINT_SETTINGS_KEY);
        if (saved) {
            try { return normalizePrintSettings(JSON.parse(saved)); } catch (e) { return normalizePrintSettings(defaultSettings); }
        }
        return normalizePrintSettings(defaultSettings);
    }

    function applyPrintSettings(settings) {
        const root = document.documentElement;
        root.style.setProperty('--print-page-margin', settings.pageMargin || defaultSettings.pageMargin);
        root.style.setProperty('--print-body-width', settings.bodyWidth || defaultSettings.bodyWidth);
        root.style.setProperty('--print-body-height', settings.bodyHeight || defaultSettings.bodyHeight);
        root.style.setProperty('--print-body-margin-left', settings.bodyMarginLeft || defaultSettings.bodyMarginLeft);
        root.style.setProperty('--container-max-width', settings.containerMaxWidth || defaultSettings.containerMaxWidth);
        root.style.setProperty('--container-padding', settings.containerPadding || defaultSettings.containerPadding);
        root.style.setProperty('--container-margin-left', settings.containerMarginLeft || defaultSettings.containerMarginLeft);
        root.style.setProperty('--container-margin-right', settings.containerMarginRight || defaultSettings.containerMarginRight);
        root.style.setProperty('--print-footer-width', settings.footerWidth || defaultSettings.footerWidth);
        root.style.setProperty('--print-footer-font-size', settings.footerFontSize || defaultSettings.footerFontSize);
        root.style.setProperty('--print-footer-padding', settings.footerPadding || defaultSettings.footerPadding);
        root.style.setProperty('--component-name-font-size', (settings.componentNameFontSize || defaultSettings.componentNameFontSize) + 'px');
        root.style.setProperty('--std-header-data-font-size', (settings.headerDataFontSize || defaultSettings.headerDataFontSize) + 'px');
        root.style.setProperty('--std-table-data-font-size', (settings.tableDataFontSize || defaultSettings.tableDataFontSize) + 'px');
        const printScale = Math.max(50, Math.min(120, parseFloat(String(settings.printScale || defaultSettings.printScale).replace(/[^\d.-]/g, '')) || 100));
        root.style.setProperty('--print-user-scale', String(printScale / 100));

        const parsePct = (v, fallback) => {
            const n = parseFloat(String(v ?? '').replace(/[^\d.-]/g, ''));
            return Number.isFinite(n) && n > 0 ? n : fallback;
        };
        const defW = parsePct(defaultSettings.bodyWidth, 100);
        const defH = parsePct(defaultSettings.bodyHeight, 100);
        const curW = parsePct(settings.bodyWidth, defW);
        const curH = parsePct(settings.bodyHeight, defH);
        const zx = curW / defW;
        const zy = curH / defH;
        root.style.setProperty('--print-scale-x', String(zx));
        root.style.setProperty('--print-scale-y', String(zy));
        /* trainingForms: transform:scale не меняет габариты в потоке → лишние страницы при печати; zoom учитывает раскладку (Chrome/Edge) */
        const layoutZoom = Math.sqrt(Math.max(0.05, zx * zy));
        root.style.setProperty('--print-layout-zoom', String(layoutZoom));
    }

    function loadSettingsToForm(settings) {
        const parseNum = (v) => parseFloat(String(v).replace(/[^\d.-]/g, '')) || 0;
        const el = id => document.getElementById(id);
        if (el('pageMargin')) el('pageMargin').value = parseNum(settings.pageMargin) || 1;
        if (el('bodyWidth')) el('bodyWidth').value = parseNum(settings.bodyWidth) || 98;
        if (el('bodyHeight')) el('bodyHeight').value = parseNum(settings.bodyHeight) || 99;
        if (el('bodyMarginLeft')) el('bodyMarginLeft').value = parseNum(settings.bodyMarginLeft) || 2;
        if (el('printScale')) el('printScale').value = parseFloat(String(settings.printScale || defaultSettings.printScale).replace(/[^\d.-]/g, '')) || 100;
        if (el('containerMaxWidth')) el('containerMaxWidth').value = parseNum(settings.containerMaxWidth) || 920;
        if (el('containerPadding')) el('containerPadding').value = parseNum(settings.containerPadding) || 5;
        if (el('containerMarginLeft')) el('containerMarginLeft').value = parseNum(settings.containerMarginLeft) || 10;
        if (el('containerMarginRight')) el('containerMarginRight').value = parseNum(settings.containerMarginRight) || 10;
        if (el('footerWidth')) el('footerWidth').value = parseNum(settings.footerWidth) || 800;
        if (el('footerFontSize')) el('footerFontSize').value = parseNum(settings.footerFontSize) || 12;
        if (el('footerPadding')) el('footerPadding').value = settings.footerPadding || '3px 3px';
        if (el('componentNameFontSize')) el('componentNameFontSize').value = parseFloat(String(settings.componentNameFontSize || defaultSettings.componentNameFontSize).replace(/[^\d.-]/g, '')) || 16;
        if (el('headerDataFontSize')) el('headerDataFontSize').value = parseFloat(String(settings.headerDataFontSize || defaultSettings.headerDataFontSize).replace(/[^\d.-]/g, '')) || 16;
        if (el('tableDataFontSize')) el('tableDataFontSize').value = parseFloat(String(settings.tableDataFontSize || defaultSettings.tableDataFontSize).replace(/[^\d.-]/g, '')) || 13;
        if (el('{{ $tableRowsKey }}')) el('{{ $tableRowsKey }}').value = settings['{{ $tableRowsKey }}'] || defaultSettings['{{ $tableRowsKey }}'];
    }

    function updateTooltipsLanguage(container, lang) {
        if (!container) return;
        if (!window.bootstrap?.Tooltip) return;
        const tooltipElements = container.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(function(el) {
            const existingTooltip = window.bootstrap.Tooltip.getInstance(el);
            if (existingTooltip) existingTooltip.dispose();
            const ruText = el.getAttribute('data-tooltip-ru');
            const enText = el.getAttribute('data-tooltip-en');
            if (lang === 'ru' && ruText) el.setAttribute('title', ruText);
            else if (lang === 'en' && enText) el.setAttribute('title', enText);
            new window.bootstrap.Tooltip(el);
        });
    }

    window.savePrintSettings = function() {
        try {
            const g = (id) => document.getElementById(id);
            const getVal = (id, def, suffix = '') => (g(id)?.value ?? def) + suffix;
            const settings = {
                pageMargin: getVal('pageMargin', '1', 'mm'),
                bodyWidth: getVal('bodyWidth', '98', '%'),
                bodyHeight: getVal('bodyHeight', '99', '%'),
                bodyMarginLeft: getVal('bodyMarginLeft', '2', 'px'),
                printScale: g('printScale')?.value ?? '100',
                containerMaxWidth: getVal('containerMaxWidth', '920', 'px'),
                containerPadding: getVal('containerPadding', '5', 'px'),
                containerMarginLeft: getVal('containerMarginLeft', '10', 'px'),
                containerMarginRight: getVal('containerMarginRight', '10', 'px'),
                footerWidth: getVal('footerWidth', '800', 'px'),
                footerFontSize: getVal('footerFontSize', '12', 'px'),
                footerPadding: g('footerPadding')?.value ?? '3px 3px',
                componentNameFontSize: g('componentNameFontSize')?.value ?? '{{ $formConfig['component_name_font_size'] ?? 16 }}',
                headerDataFontSize: g('headerDataFontSize')?.value ?? '{{ $formConfig['header_data_font_size'] ?? 16 }}',
                tableDataFontSize: g('tableDataFontSize')?.value ?? '{{ $formConfig['table_data_font_size'] ?? 13 }}',
                '{{ $tableRowsKey }}': g('{{ $tableRowsKey }}')?.value ?? '{{ $tableRowsDefault }}'
            };
            Object.assign(settings, LOCKED_PRINT_SETTINGS);
            window.UserScopedStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);
            if (typeof window.tdrFormApplyTableRowLimits === 'function') {
                window.tdrFormApplyTableRowLimits(settings);
            }
@if(($formType ?? '') === 'stressFormStd')
            {
                const rows = parseInt(String(settings['{{ $tableRowsKey }}'] ?? '').replace(/[^\d]/g, ''), 10);
                if (Number.isFinite(rows) && rows >= 1) {
                    const u = new URL(window.location.href);
                    const def = {{ (int) $tableRowsDefault }};
                    if (rows === def) {
                        u.searchParams.delete('stress_table_rows');
                    } else {
                        u.searchParams.set('stress_table_rows', String(rows));
                    }
                    if (u.href !== window.location.href) {
                        window.location.href = u.href;
                        return;
                    }
                }
            }
@elseif(($formType ?? '') === 'ndtFormStd')
            {
                const rows = parseInt(String(settings['{{ $tableRowsKey }}'] ?? '').replace(/[^\d]/g, ''), 10);
                if (Number.isFinite(rows) && rows >= 1) {
                    const u = new URL(window.location.href);
                    const def = {{ (int) $tableRowsDefault }};
                    if (rows === def) {
                        u.searchParams.delete('ndt_table_rows');
                    } else {
                        u.searchParams.set('ndt_table_rows', String(rows));
                    }
                    if (u.href !== window.location.href) {
                        window.location.href = u.href;
                        return;
                    }
                }
            }
@elseif(($formType ?? '') === 'cadFormStd')
            {
                const rows = parseInt(String(settings['{{ $tableRowsKey }}'] ?? '').replace(/[^\d]/g, ''), 10);
                if (Number.isFinite(rows) && rows >= 1) {
                    const u = new URL(window.location.href);
                    const def = {{ (int) $tableRowsDefault }};
                    if (rows === def) {
                        u.searchParams.delete('cad_table_rows');
                    } else {
                        u.searchParams.set('cad_table_rows', String(rows));
                    }
                    if (u.href !== window.location.href) {
                        window.location.href = u.href;
                        return;
                    }
                }
            }
@elseif(($formType ?? '') === 'paintFormStd')
            {
                const rows = parseInt(String(settings['{{ $tableRowsKey }}'] ?? '').replace(/[^\d]/g, ''), 10);
                if (Number.isFinite(rows) && rows >= 1) {
                    const u = new URL(window.location.href);
                    const def = {{ (int) $tableRowsDefault }};
                    if (rows === def) {
                        u.searchParams.delete('paint_table_rows');
                    } else {
                        u.searchParams.set('paint_table_rows', String(rows));
                    }
                    if (u.href !== window.location.href) {
                        window.location.href = u.href;
                        return;
                    }
                }
            }
@endif
            if (document.activeElement?.blur) document.activeElement.blur();
            const modal = window.bootstrap?.Modal?.getInstance(document.getElementById('printSettingsModal'));
            if (modal) modal.hide();
        } catch (e) {
            console.error('Error saving print settings:', e);
            if (typeof showNotification === 'function') showNotification('Error saving settings', 'error');
        }
    };

    window.resetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            window.UserScopedStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
            setTimeout(function() {
                if (typeof window.tdrFormApplyTableRowLimits === 'function') {
                    window.tdrFormApplyTableRowLimits(defaultSettings);
                }
@if(($formType ?? '') === 'stressFormStd')
                if (window.location.search.indexOf('stress_table_rows') !== -1) {
                    const u = new URL(window.location.href);
                    u.searchParams.delete('stress_table_rows');
                    window.location.href = u.href;
                }
@elseif(($formType ?? '') === 'ndtFormStd')
                if (window.location.search.indexOf('ndt_table_rows') !== -1) {
                    const u = new URL(window.location.href);
                    u.searchParams.delete('ndt_table_rows');
                    window.location.href = u.href;
                }
@elseif(($formType ?? '') === 'cadFormStd')
                if (window.location.search.indexOf('cad_table_rows') !== -1) {
                    const u = new URL(window.location.href);
                    u.searchParams.delete('cad_table_rows');
                    window.location.href = u.href;
                }
@elseif(($formType ?? '') === 'paintFormStd')
                if (window.location.search.indexOf('paint_table_rows') !== -1) {
                    const u = new URL(window.location.href);
                    u.searchParams.delete('paint_table_rows');
                    window.location.href = u.href;
                }
@endif
            }, 50);
            if (typeof showNotification === 'function') showNotification('Settings reset to default values!', 'success');
        }
    };

    window.toggleTooltipLanguage = function() {
        const modal = document.getElementById('printSettingsModal');
        if (!modal) return;
        let currentLang = window.UserScopedStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
        currentLang = currentLang === 'ru' ? 'en' : 'ru';
        window.UserScopedStorage.setItem(TOOLTIP_LANG_KEY, currentLang);
        updateTooltipsLanguage(modal, currentLang);
        const langText = document.getElementById('langToggleText');
        if (langText) langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
    };

    document.addEventListener('DOMContentLoaded', function() {
        const settings = loadPrintSettings();
        applyPrintSettings(settings);
        loadSettingsToForm(settings);
        setTimeout(function() {
            if (typeof window.tdrFormApplyTableRowLimits === 'function') {
                window.tdrFormApplyTableRowLimits(settings);
            }
@if(($formType ?? '') === 'stressFormStd')
            (function syncStressRowsQueryFromStorage() {
                const u = new URL(window.location.href);
                if (u.searchParams.has('stress_table_rows')) {
                    return;
                }
                const rows = parseInt(String(settings['{{ $tableRowsKey }}'] ?? '').replace(/[^\d]/g, ''), 10);
                const def = {{ (int) $tableRowsDefault }};
                if (!Number.isFinite(rows) || rows < 1 || rows === def) {
                    return;
                }
                u.searchParams.set('stress_table_rows', String(rows));
                window.location.replace(u.href);
            })();
@elseif(($formType ?? '') === 'ndtFormStd')
            (function syncNdtRowsQueryFromStorage() {
                const u = new URL(window.location.href);
                const rows = parseInt(String(settings['{{ $tableRowsKey }}'] ?? '').replace(/[^\d]/g, ''), 10);
                const def = {{ (int) $tableRowsDefault }};
                if (!Number.isFinite(rows) || rows < 1) {
                    return;
                }
                const queryRows = parseInt(String(u.searchParams.get('ndt_table_rows') ?? '').replace(/[^\d]/g, ''), 10);
                if (rows === def) {
                    if (u.searchParams.has('ndt_table_rows')) {
                        u.searchParams.delete('ndt_table_rows');
                        window.location.replace(u.href);
                    }
                    return;
                }
                if (queryRows !== rows) {
                    u.searchParams.set('ndt_table_rows', String(rows));
                    window.location.replace(u.href);
                }
            })();
@elseif(($formType ?? '') === 'cadFormStd')
            (function syncCadRowsQueryFromStorage() {
                const u = new URL(window.location.href);
                const rows = parseInt(String(settings['{{ $tableRowsKey }}'] ?? '').replace(/[^\d]/g, ''), 10);
                const def = {{ (int) $tableRowsDefault }};
                if (!Number.isFinite(rows) || rows < 1) {
                    return;
                }
                const queryRows = parseInt(String(u.searchParams.get('cad_table_rows') ?? '').replace(/[^\d]/g, ''), 10);
                if (rows === def) {
                    if (u.searchParams.has('cad_table_rows')) {
                        u.searchParams.delete('cad_table_rows');
                        window.location.replace(u.href);
                    }
                    return;
                }
                if (queryRows !== rows) {
                    u.searchParams.set('cad_table_rows', String(rows));
                    window.location.replace(u.href);
                }
            })();
@elseif(($formType ?? '') === 'paintFormStd')
            (function syncPaintRowsQueryFromStorage() {
                const u = new URL(window.location.href);
                const rows = parseInt(String(settings['{{ $tableRowsKey }}'] ?? '').replace(/[^\d]/g, ''), 10);
                const def = {{ (int) $tableRowsDefault }};
                if (!Number.isFinite(rows) || rows < 1) {
                    return;
                }
                const queryRows = parseInt(String(u.searchParams.get('paint_table_rows') ?? '').replace(/[^\d]/g, ''), 10);
                if (rows === def) {
                    if (u.searchParams.has('paint_table_rows')) {
                        u.searchParams.delete('paint_table_rows');
                        window.location.replace(u.href);
                    }
                    return;
                }
                if (queryRows !== rows) {
                    u.searchParams.set('paint_table_rows', String(rows));
                    window.location.replace(u.href);
                }
            })();
@endif
        }, 300);

        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                loadSettingsToForm(loadPrintSettings());
                const currentLang = window.UserScopedStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
                const langText = document.getElementById('langToggleText');
                if (langText) langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
                setTimeout(function() { updateTooltipsLanguage(modal, currentLang); }, 100);
            });
        }
    });

    window.addEventListener('beforeprint', function() {
        const settings = loadPrintSettings();
        setTimeout(function() {
            if (typeof window.tdrFormApplyTableRowLimits === 'function') {
                window.tdrFormApplyTableRowLimits(settings);
            }
        }, 10);
    });
})();
</script>
