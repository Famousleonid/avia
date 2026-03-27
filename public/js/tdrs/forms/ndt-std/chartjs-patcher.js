/**
 * ChartJSPatcher — защита Chart.helpers.identifyDuplicates от неитерируемых / не-массивных аргументов.
 * Идемпотентно: один раз сохраняет оригинал в Chart.helpers.__tdrOriginalIdentifyDuplicates.
 */
class ChartJSPatcher {
    static _normalizeStatements(statements) {
        if (statements == null) {
            return [];
        }
        if (Array.isArray(statements)) {
            return statements;
        }
        if (statements && typeof statements.toArray === 'function') {
            try {
                const a = statements.toArray();
                return Array.isArray(a) ? a : [];
            } catch (e) {
                /* fall through */
            }
        }
        if (typeof statements === 'object' && typeof statements.length === 'number' && typeof statements !== 'string') {
            try {
                return Array.prototype.slice.call(statements);
            } catch (e) {
                /* fall through */
            }
        }
        if (typeof Symbol !== 'undefined' && typeof statements[Symbol.iterator] === 'function') {
            try {
                return Array.from(statements);
            } catch (e) {
                /* fall through */
            }
        }
        return [];
    }

    static applyPatch() {
        function patchChartJs() {
            if (typeof Chart === 'undefined' || !Chart.helpers) {
                return;
            }
            const identify = Chart.helpers.identifyDuplicates;
            if (typeof identify !== 'function') {
                return;
            }

            const helpers = Chart.helpers;
            const key = '__tdrOriginalIdentifyDuplicates';
            if (!helpers[key]) {
                helpers[key] = identify;
            }

            helpers.identifyDuplicates = function tdrPatchedIdentifyDuplicates(statements) {
                const arr = ChartJSPatcher._normalizeStatements(statements);
                try {
                    return helpers[key].call(this, arr);
                } catch (e) {
                    console.warn('Chart.js identifyDuplicates error:', e);
                    return [];
                }
            };
        }

        patchChartJs();

        if (ChartJSPatcher._hooksInstalled) {
            return;
        }
        ChartJSPatcher._hooksInstalled = true;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', patchChartJs);
        } else {
            setTimeout(patchChartJs, 0);
        }

        const chartCheckInterval = setInterval(function () {
            if (typeof Chart !== 'undefined' && Chart.helpers) {
                patchChartJs();
                clearInterval(chartCheckInterval);
            }
        }, 100);

        setTimeout(function () {
            clearInterval(chartCheckInterval);
        }, 5000);
    }

    static isPatched() {
        return typeof Chart !== 'undefined'
            && Chart.helpers
            && typeof Chart.helpers.__tdrOriginalIdentifyDuplicates === 'function';
    }
}

ChartJSPatcher.applyPatch();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChartJSPatcher;
}
