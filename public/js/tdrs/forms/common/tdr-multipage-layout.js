/**
 * TdrMultipageLayout — общая клиентская нарезка логических страниц для TDR std-форм.
 * Данные в Blade не меняются: только распределение уже отрисованных строк по листам.
 */
(function (global) {
    'use strict';

    function parseMaxRows(settings, key, fallback) {
        const n = parseInt(settings && settings[key], 10);
        if (Number.isFinite(n) && n >= 1) return n;
        return fallback;
    }

    /** Номера страниц в футере (клоны тоже): сначала data-атрибуты из Blade, иначе классы. */
    function footerPageTargets(footer) {
        if (!footer) {
            return { pageNumberEl: null, totalPagesEl: null };
        }
        let pageNumberEl = footer.querySelector('[data-tdr-footer-page]');
        let totalPagesEl = footer.querySelector('[data-tdr-footer-total]');
        if (!pageNumberEl) {
            pageNumberEl = footer.querySelector('.page-number');
        }
        if (!totalPagesEl) {
            totalPagesEl = footer.querySelector('.total-pages');
        }
        return { pageNumberEl: pageNumberEl, totalPagesEl: totalPagesEl };
    }

    function appendEmptyRows(container, className, html, count) {
        for (let i = 0; i < count; i++) {
            const emptyRow = document.createElement('div');
            emptyRow.className = className;
            emptyRow.innerHTML = html;
            container.appendChild(emptyRow);
        }
    }

    /**
     * Только цепочка .dynamic-page-wrapper сразу после primary (до модалки и пр.).
     * Так не трогаем чужие блоки и порядок снятия совпадает с тем, как вставляли.
     */
    function takeRowsFromFollowingDynamicWrappers(primarySheet) {
        const fromWrappers = [];
        if (!primarySheet || !primarySheet.parentNode) {
            return fromWrappers;
        }
        let el = primarySheet.nextElementSibling;
        while (el && el.classList && el.classList.contains('dynamic-page-wrapper')) {
            const wrapper = el;
            el = el.nextElementSibling;
            const rc = wrapper.querySelector('.page-rows-container');
            if (rc) {
                Array.from(rc.querySelectorAll(':scope > .data-row')).forEach(function (r) {
                    if (r.classList.contains('empty-row')) {
                        r.remove();
                    } else {
                        r.remove();
                        fromWrappers.push(r);
                    }
                });
            }
            wrapper.remove();
        }
        return fromWrappers;
    }

    function cadCollectRows(allRowsContainer, primarySheet) {
        const fromWrappers = takeRowsFromFollowingDynamicWrappers(primarySheet);
        const fromPrimary = [];
        Array.from(allRowsContainer.querySelectorAll(':scope > .data-row')).forEach(function (r) {
            if (r.classList.contains('empty-row')) {
                r.remove();
            } else {
                r.remove();
                fromPrimary.push(r);
            }
        });
        // Сначала primary — канонический узел на data-row-index; строки с продолжений с тем же индексом
        // (устаревшие cloneNode-копии после смены режима или повторного apply) отбрасываем.
        const byIndex = new Map();
        function absorb(list) {
            list.forEach(function (r) {
                if (!r.classList || !r.classList.contains('data-row') || r.classList.contains('empty-row')) {
                    return;
                }
                const idx = parseInt(r.getAttribute('data-row-index'), 10) || 0;
                if (byIndex.has(idx)) {
                    r.remove();
                    return;
                }
                byIndex.set(idx, r);
            });
        }
        absorb(fromPrimary);
        absorb(fromWrappers);
        const sortedKeys = Array.from(byIndex.keys()).sort(function (a, b) {
            return a - b;
        });
        return sortedKeys.map(function (k) {
            return byIndex.get(k);
        });
    }

    /**
     * @param {object} cfg — см. presets
     * @param {object} settings
     */
    function apply(cfg, settings) {
        settings = settings || {};
        const maxRows = Math.max(1, Math.floor(parseMaxRows(settings, cfg.maxRowsKey, cfg.maxRowsFallback)));

        const primarySheet =
            cfg.variant === 'ndt'
                ? document.querySelector('.container-fluid')
                : document.querySelector('.tdr-primary-sheet') ||
                  document.querySelector('.container-fluid:not(.dynamic-page-wrapper)') ||
                  document.querySelector('.container-fluid');

        if (!primarySheet) {
            console.warn('[' + cfg.id + '] primary sheet не найден');
            return;
        }

        const allRowsContainer = primarySheet.querySelector('.all-rows-container');
        if (!allRowsContainer) {
            console.warn('[' + cfg.id + '] .all-rows-container не найден');
            return;
        }

        if (cfg.variant === 'ndt') {
            document.querySelectorAll('.data-page[data-page-index]').forEach(function (page) {
                const pi = page.getAttribute('data-page-index');
                if (pi && parseInt(pi, 10) > 1) {
                    const parent = page.parentElement;
                    page.remove();
                    if (
                        parent &&
                        parent.classList &&
                        parent.classList.contains('container-fluid') &&
                        parent.parentNode &&
                        parent !== primarySheet &&
                        parent.childElementCount === 0
                    ) {
                        parent.remove();
                    }
                }
            });
            document.querySelectorAll('.page-break-divider').forEach(function (el) { el.remove(); });
            document.querySelectorAll('.page-break-after').forEach(function (el) {
                el.classList.remove('page-break-after');
            });
            allRowsContainer.querySelectorAll('.data-row-ndt.empty-row').forEach(function (r) { r.remove(); });
        } else if (cfg.variant === 'cad' || cfg.variant === 'stress') {
            primarySheet.classList.remove('tdr-print-force-page-end');
            primarySheet.style.removeProperty('page-break-after');
            primarySheet.style.removeProperty('break-after');
        }

        let allRows;
        if (cfg.variant === 'cad' || cfg.variant === 'stress') {
            allRows = cadCollectRows(allRowsContainer, primarySheet);
        } else if (typeof cfg.collectRowsFromPrimary === 'function') {
            allRows = cfg.collectRowsFromPrimary(allRowsContainer);
        } else {
            allRows = Array.from(allRowsContainer.querySelectorAll(cfg.rowSelectorInPrimary)).filter(function (el) {
                return el.classList && !el.classList.contains('empty-row');
            });
        }

        const manualRows = allRows.filter(function (row) { return row.classList.contains('manual-row'); });
        const dataRows = allRows.filter(function (row) { return !row.classList.contains('manual-row'); });
        const hasManualRows = manualRows.length > 0;
        const totalRows = hasManualRows ? allRows.length : dataRows.length;
        const rowsToProcess = hasManualRows ? allRows : dataRows;
        const totalPages = Math.max(1, Math.ceil(totalRows / maxRows));

        const originalHeader = primarySheet.querySelector('.header-page');
        const originalTableHeader = primarySheet.querySelector('.table-header');
        const originalFooter = primarySheet.querySelector('footer');

        if (!originalHeader || !originalTableHeader || !originalFooter) {
            console.warn('[' + cfg.id + '] нет header/table-header/footer');
            return;
        }

        let pageInsertAnchor = primarySheet;

        if (cfg.useTdrPrintLockOnPrimary && totalPages > 1) {
            primarySheet.classList.add('tdr-print-force-page-end');
            primarySheet.style.setProperty('page-break-after', 'always', 'important');
            primarySheet.style.setProperty('break-after', 'page', 'important');
        }

        if (cfg.variant === 'cad' || cfg.variant === 'stress') {
            for (let fi = 0; fi < Math.min(maxRows, rowsToProcess.length); fi++) {
                const r = rowsToProcess[fi];
                r.classList.remove('tdr-source-row-off');
                r.style.removeProperty('display');
                allRowsContainer.appendChild(r);
            }
        } else {
            rowsToProcess.forEach(function (row, index) {
                row.style.display = index < maxRows ? '' : 'none';
            });
        }

        if (originalFooter) {
            const ft = footerPageTargets(originalFooter);
            if (ft.pageNumberEl) ft.pageNumberEl.textContent = '1';
            if (ft.totalPagesEl) ft.totalPagesEl.textContent = String(totalPages);
        }

        for (let pageIndex = 1; pageIndex < totalPages; pageIndex++) {
            const startIndex = pageIndex * maxRows;
            const endIndex = Math.min(startIndex + maxRows, rowsToProcess.length);
            const pageRows = rowsToProcess.slice(startIndex, endIndex);

            const pageDiv = document.createElement('div');
            pageDiv.className = 'page data-page';
            pageDiv.setAttribute('data-page-index', String(pageIndex + 1));

            let insertNode;

            if (cfg.wrapContinuationInDynamicPage) {
                const outerWrap = document.createElement('div');
                outerWrap.className = 'container-fluid dynamic-page-wrapper';
                outerWrap.style.setProperty('break-before', 'page', 'important');
                outerWrap.style.setProperty('page-break-before', 'always', 'important');
                outerWrap.appendChild(pageDiv);
                insertNode = outerWrap;
            } else {
                pageDiv.style.pageBreakBefore = 'always';
                const pageContainer = document.createElement('div');
                pageContainer.className = 'container-fluid';
                pageContainer.appendChild(pageDiv);
                insertNode = pageContainer;
            }

            pageDiv.appendChild(originalHeader.cloneNode(true));
            pageDiv.appendChild(originalTableHeader.cloneNode(true));

            const rowsContainer = document.createElement('div');
            rowsContainer.className = cfg.continuationRowsContainerClass;

            if (cfg.rowPlacement === 'move') {
                for (let ri = startIndex; ri < endIndex; ri++) {
                    const r = rowsToProcess[ri];
                    r.classList.remove('tdr-source-row-off');
                    r.style.removeProperty('display');
                    rowsContainer.appendChild(r);
                }
            } else {
                pageRows.forEach(function (row) {
                    const node = row.cloneNode(true);
                    node.classList.remove('tdr-source-row-off');
                    node.style.display = '';
                    rowsContainer.appendChild(node);
                });
            }

            if (pageIndex === totalPages - 1) {
                if (cfg.emptyRowsLastPageMode === 'ndt') {
                    const rem = totalRows % maxRows;
                    const emptyRowsNeeded = rem === 0 ? 0 : (maxRows - rem);
                    if (emptyRowsNeeded > 0) {
                        appendEmptyRows(rowsContainer, cfg.emptyRowClassName, cfg.emptyRowHtml, emptyRowsNeeded);
                    }
                } else {
                    const rowsOnLastPage = pageRows.length;
                    let emptyRowsNeeded = rowsOnLastPage === 0 ? maxRows : (maxRows - rowsOnLastPage);
                    if (emptyRowsNeeded > 0 && emptyRowsNeeded < maxRows) {
                        appendEmptyRows(rowsContainer, cfg.emptyRowClassName, cfg.emptyRowHtml, emptyRowsNeeded);
                    }
                }
            }

            pageDiv.appendChild(rowsContainer);

            const footerClone = originalFooter.cloneNode(true);
            const ftc = footerPageTargets(footerClone);
            if (ftc.pageNumberEl) ftc.pageNumberEl.textContent = String(pageIndex + 1);
            if (ftc.totalPagesEl) ftc.totalPagesEl.textContent = String(totalPages);
            pageDiv.appendChild(footerClone);

            if (primarySheet.parentNode) {
                primarySheet.parentNode.insertBefore(insertNode, pageInsertAnchor.nextSibling);
                pageInsertAnchor = insertNode;
            } else {
                document.body.appendChild(insertNode);
            }
        }

        if (totalPages === 1) {
            if (cfg.emptyRowsSinglePageMode === 'ndt') {
                const rem = totalRows % maxRows;
                const emptyRowsNeeded = rem === 0 ? 0 : (maxRows - rem);
                if (emptyRowsNeeded > 0 && cfg.lastDataRowSelectorForPad) {
                    const lastDataRow = allRowsContainer.querySelector(cfg.lastDataRowSelectorForPad);
                    if (lastDataRow) {
                        appendEmptyRows(allRowsContainer, cfg.emptyRowClassName, cfg.emptyRowHtml, emptyRowsNeeded);
                    }
                }
            } else {
                const rowsOnFirst = rowsToProcess.length;
                const emptyRowsNeeded = rowsOnFirst === 0 ? maxRows : (maxRows - rowsOnFirst);
                if (emptyRowsNeeded > 0 && emptyRowsNeeded < maxRows) {
                    appendEmptyRows(allRowsContainer, cfg.emptyRowClassName, cfg.emptyRowHtml, emptyRowsNeeded);
                }
            }
        }

        if (originalFooter) {
            const fte = footerPageTargets(originalFooter);
            if (fte.pageNumberEl) fte.pageNumberEl.textContent = '1';
            if (fte.totalPagesEl) fte.totalPagesEl.textContent = String(totalPages);
        }

        console.log('[' + cfg.id + '] страниц:', totalPages, 'лимит строк:', maxRows);
    }

    const EMPTY_NDT =
        '<div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>' +
        '<div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>' +
        '<div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>' +
        '<div class="col-2 border-l-b details-row text-center" style="height: 32px"></div>' +
        '<div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>' +
        '<div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>' +
        '<div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>';

    const EMPTY_STRESS =
        '<div class="col-1 border-l-b details-cell text-center" style="min-height: 34px"></div>' +
        '<div class="col-2 border-l-b details-cell text-center" style="min-height: 34px"></div>' +
        '<div class="col-2 border-l-b details-cell text-center" style="min-height: 34px"></div>' +
        '<div class="col-4 border-l-b details-cell text-center" style="min-height: 34px"></div>' +
        '<div class="col-1 border-l-b details-cell text-center" style="min-height: 34px"></div>' +
        '<div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px"></div>';

    const EMPTY_CAD =
        '<div class="col-1 border-l-b details-cell text-center" style="height: 32px"></div>' +
        '<div class="col-2 border-l-b details-cell text-center" style="height: 32px"></div>' +
        '<div class="col-3 border-l-b details-cell text-center" style="height: 32px"></div>' +
        '<div class="col-3 border-l-b details-cell text-center" style="height: 32px"></div>' +
        '<div class="col-1 border-l-b details-cell text-center" style="height: 32px"></div>' +
        '<div class="col-2 border-l-b-r details-cell text-center" style="height: 32px"></div>';

    const presets = {
        ndtFormStd: {
            id: 'ndtFormStd',
            variant: 'ndt',
            maxRowsKey: 'ndtTableRows',
            maxRowsFallback: 16,
            rowSelectorInPrimary: '.data-row-ndt:not(.empty-row)',
            emptyRowClassName: 'row fs-85 data-row-ndt empty-row',
            emptyRowHtml: EMPTY_NDT,
            continuationRowsContainerClass: 'all-rows-container',
            wrapContinuationInDynamicPage: false,
            useTdrPrintLockOnPrimary: false,
            rowPlacement: 'clone',
            emptyRowsLastPageMode: 'ndt',
            emptyRowsSinglePageMode: 'ndt',
            lastDataRowSelectorForPad: '.data-row-ndt:not(.empty-row):last-of-type'
        },
        stressFormStd: {
            id: 'stressFormStd',
            variant: 'stress',
            maxRowsKey: 'stressTableRows',
            maxRowsFallback: 21,
            rowSelectorInPrimary: '',
            emptyRowClassName: 'row fs-85 data-row empty-row',
            emptyRowHtml: EMPTY_STRESS,
            continuationRowsContainerClass: 'page-rows-container',
            wrapContinuationInDynamicPage: true,
            /* break-after на primary + break-before на wrapper давали дубль первой страницы в Chrome при печати */
            useTdrPrintLockOnPrimary: false,
            rowPlacement: 'move',
            emptyRowsLastPageMode: 'stress',
            emptyRowsSinglePageMode: 'stress'
        },
        cadFormStd: {
            id: 'cadFormStd',
            variant: 'cad',
            maxRowsKey: 'cadTableRows',
            maxRowsFallback: 19,
            emptyRowClassName: 'row fs-85 data-row empty-row',
            emptyRowHtml: EMPTY_CAD,
            continuationRowsContainerClass: 'page-rows-container',
            wrapContinuationInDynamicPage: true,
            useTdrPrintLockOnPrimary: false,
            rowPlacement: 'move',
            emptyRowsLastPageMode: 'stress',
            emptyRowsSinglePageMode: 'stress'
        }
    };

    function createDebouncedApplicator(presetKey) {
        let busy = false;
        let queued = null;
        const preset = presets[presetKey];
        if (!preset) {
            console.error('TdrMultipageLayout: неизвестный пресет', presetKey);
            return function () {};
        }
        function impl(settings) {
            apply(Object.assign({}, preset), settings);
        }
        function debounced(settings) {
            queued = settings;
            if (busy) return;
            busy = true;
            try {
                while (queued) {
                    const next = queued;
                    queued = null;
                    impl(next);
                }
            } finally {
                busy = false;
                if (queued) debounced(queued);
            }
        }
        return debounced;
    }

    global.TdrMultipageLayout = {
        apply: apply,
        presets: presets,
        createDebouncedApplicator: createDebouncedApplicator
    };
})(typeof window !== 'undefined' ? window : this);
