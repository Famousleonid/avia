/**
 * ChartJSPatcher - модуль для исправления ошибок Chart.js
 * Используется в ndtFormStd для предотвращения ошибок identifyDuplicates
 */
class ChartJSPatcher {
    /**
     * Применяет патч для Chart.js
     * Переопределяет Chart.helpers.identifyDuplicates для предотвращения ошибок
     */
    static applyPatch() {
        function patchChartJs() {
            if (typeof Chart !== 'undefined' && Chart.helpers) {
                const originalIdentifyDuplicates = Chart.helpers.identifyDuplicates;
                if (originalIdentifyDuplicates && typeof originalIdentifyDuplicates === 'function') {
                    Chart.helpers.identifyDuplicates = function(statements) {
                        if (!statements || !Array.isArray(statements)) {
                            return [];
                        }
                        try {
                            return originalIdentifyDuplicates.call(this, statements);
                        } catch (e) {
                            console.warn('Chart.js identifyDuplicates error:', e);
                            return [];
                        }
                    };
                    console.log('Chart.js патч применен');
                }
            }
        }

        // Пытаемся переопределить сразу
        patchChartJs();

        // Также переопределяем после загрузки DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', patchChartJs);
        } else {
            // DOM уже загружен
            setTimeout(patchChartJs, 0);
        }

        // Переопределяем при каждом изменении Chart (на случай асинхронной загрузки)
        let chartCheckInterval = setInterval(function() {
            if (typeof Chart !== 'undefined' && Chart.helpers) {
                patchChartJs();
                clearInterval(chartCheckInterval);
            }
        }, 100);

        // Останавливаем проверку через 5 секунд
        setTimeout(function() {
            clearInterval(chartCheckInterval);
        }, 5000);
    }

    /**
     * Проверяет, применен ли патч
     * @returns {boolean} true если патч применен
     */
    static isPatched() {
        if (typeof Chart === 'undefined' || !Chart.helpers) {
            return false;
        }
        
        // Проверяем, что функция была переопределена
        const func = Chart.helpers.identifyDuplicates;
        return func && func.toString().includes('console.warn');
    }
}

// Применяем патч сразу при загрузке модуля
ChartJSPatcher.applyPatch();

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChartJSPatcher;
}



