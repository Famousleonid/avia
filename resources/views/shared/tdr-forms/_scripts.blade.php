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

    const defaultSettings = {
        pageMargin: '{{ $formConfig['page_margin'] ?? 1 }}mm',
        bodyWidth: '{{ $formConfig['body_width'] ?? 98 }}%',
        bodyHeight: '{{ $formConfig['body_height'] ?? 99 }}%',
        bodyMarginLeft: '{{ $formConfig['body_margin_left'] ?? 2 }}px',
        containerMaxWidth: '{{ $formConfig['container_max_width'] ?? 920 }}px',
        containerPadding: '{{ $formConfig['container_padding'] ?? 5 }}px',
        containerMarginLeft: '{{ $formConfig['container_margin_left'] ?? 10 }}px',
        containerMarginRight: '{{ $formConfig['container_margin_right'] ?? 10 }}px',
        footerWidth: '{{ $formConfig['footer_width'] ?? 800 }}px',
        footerFontSize: '{{ $formConfig['footer_font_size'] ?? 10 }}px',
        footerPadding: '{{ $formConfig['footer_padding'] ?? '3px 3px' }}',
        componentNameFontSize: '12',
        '{{ $tableRowsKey }}': '{{ $tableRowsDefault }}'
    };

    function loadPrintSettings() {
        const saved = localStorage.getItem(PRINT_SETTINGS_KEY);
        if (saved) {
            try { return JSON.parse(saved); } catch (e) { return defaultSettings; }
        }
        return defaultSettings;
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
    }

    function loadSettingsToForm(settings) {
        const parseNum = (v) => parseFloat(String(v).replace(/[^\d.-]/g, '')) || 0;
        const el = id => document.getElementById(id);
        if (el('pageMargin')) el('pageMargin').value = parseNum(settings.pageMargin) || 1;
        if (el('bodyWidth')) el('bodyWidth').value = parseNum(settings.bodyWidth) || 98;
        if (el('bodyHeight')) el('bodyHeight').value = parseNum(settings.bodyHeight) || 99;
        if (el('bodyMarginLeft')) el('bodyMarginLeft').value = parseNum(settings.bodyMarginLeft) || 2;
        if (el('containerMaxWidth')) el('containerMaxWidth').value = parseNum(settings.containerMaxWidth) || 920;
        if (el('containerPadding')) el('containerPadding').value = parseNum(settings.containerPadding) || 5;
        if (el('containerMarginLeft')) el('containerMarginLeft').value = parseNum(settings.containerMarginLeft) || 10;
        if (el('containerMarginRight')) el('containerMarginRight').value = parseNum(settings.containerMarginRight) || 10;
        if (el('footerWidth')) el('footerWidth').value = parseNum(settings.footerWidth) || 800;
        if (el('footerFontSize')) el('footerFontSize').value = parseNum(settings.footerFontSize) || 10;
        if (el('footerPadding')) el('footerPadding').value = settings.footerPadding || '3px 3px';
        if (el('componentNameFontSize')) el('componentNameFontSize').value = parseFloat(String(settings.componentNameFontSize || defaultSettings.componentNameFontSize).replace(/[^\d.-]/g, '')) || 12;
        if (el('{{ $tableRowsKey }}')) el('{{ $tableRowsKey }}').value = settings['{{ $tableRowsKey }}'] || defaultSettings['{{ $tableRowsKey }}'];
    }

    function updateTooltipsLanguage(container, lang) {
        if (!container) return;
        const tooltipElements = container.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(function(el) {
            const existingTooltip = bootstrap.Tooltip.getInstance(el);
            if (existingTooltip) existingTooltip.dispose();
            const ruText = el.getAttribute('data-tooltip-ru');
            const enText = el.getAttribute('data-tooltip-en');
            if (lang === 'ru' && ruText) el.setAttribute('title', ruText);
            else if (lang === 'en' && enText) el.setAttribute('title', enText);
            new bootstrap.Tooltip(el);
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
                containerMaxWidth: getVal('containerMaxWidth', '920', 'px'),
                containerPadding: getVal('containerPadding', '5', 'px'),
                containerMarginLeft: getVal('containerMarginLeft', '10', 'px'),
                containerMarginRight: getVal('containerMarginRight', '10', 'px'),
                footerWidth: getVal('footerWidth', '800', 'px'),
                footerFontSize: getVal('footerFontSize', '10', 'px'),
                footerPadding: g('footerPadding')?.value ?? '3px 3px',
                componentNameFontSize: g('componentNameFontSize')?.value ?? '12',
                '{{ $tableRowsKey }}': g('{{ $tableRowsKey }}')?.value ?? '{{ $tableRowsDefault }}'
            };
            localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);
            if (typeof window.tdrFormApplyTableRowLimits === 'function') {
                window.tdrFormApplyTableRowLimits(settings);
            }
            if (document.activeElement?.blur) document.activeElement.blur();
            const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
            if (modal) modal.hide();
            if (typeof showNotification === 'function') showNotification('Settings saved successfully!', 'success');
        } catch (e) {
            console.error('Error saving print settings:', e);
            if (typeof showNotification === 'function') showNotification('Error saving settings', 'error');
        }
    };

    window.resetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
            setTimeout(function() {
                if (typeof window.tdrFormApplyTableRowLimits === 'function') {
                    window.tdrFormApplyTableRowLimits(defaultSettings);
                }
            }, 50);
            if (typeof showNotification === 'function') showNotification('Settings reset to default values!', 'success');
        }
    };

    window.toggleTooltipLanguage = function() {
        const modal = document.getElementById('printSettingsModal');
        if (!modal) return;
        let currentLang = localStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
        currentLang = currentLang === 'ru' ? 'en' : 'ru';
        localStorage.setItem(TOOLTIP_LANG_KEY, currentLang);
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
        }, 300);

        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                loadSettingsToForm(loadPrintSettings());
                const currentLang = localStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
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
