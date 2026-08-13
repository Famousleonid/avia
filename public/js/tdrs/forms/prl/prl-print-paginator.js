(function (global) {
    'use strict';

    const DEFAULT_SAFETY_GAP = 3;
    const FIT_TOLERANCE = 0.5;
    const state = {
        initialized: false,
        sourceRows: [],
        originalContainer: null,
        firstPage: null,
        firstRowsContainer: null,
        headerTemplate: null,
        footerTemplate: null,
        stampsTemplate: null,
    };

    function sumHeight(items) {
        return items.reduce((total, item) => total + item.height, 0);
    }

    function planFillerRows(remainingHeight, preferredRowHeight) {
        const remaining = Math.max(0, Number(remainingHeight) || 0);
        const preferred = Math.max(1, Number(preferredRowHeight) || 32);

        if (remaining < 1) return [];

        const count = Math.max(1, Math.floor(remaining / preferred));
        const heights = Array(count).fill(preferred);
        const assigned = preferred * count;

        // Put the rounding/remainder into the final blank row so the grid
        // reaches the stamps exactly without changing the real data rows.
        heights[count - 1] += remaining - assigned;

        return heights;
    }

    function packByHeight(items, capacity) {
        const pages = [];
        let page = [];
        let used = 0;

        items.forEach((item) => {
            if (page.length > 0 && used + item.height > capacity + FIT_TOLERANCE) {
                pages.push(page);
                page = [];
                used = 0;
            }

            page.push(item);
            used += item.height;
        });

        if (page.length > 0 || pages.length === 0) {
            pages.push(page);
        }

        return pages;
    }

    /**
     * Packs rows into full-height pages and reserves the reduced final-page
     * budget for the production/quality stamp block.
     */
    function planPages(items, normalCapacity, lastPageCapacity) {
        const pages = packByHeight(items, normalCapacity);
        const lastPageIndex = pages.length - 1;
        const lastPage = pages[lastPageIndex];

        if (sumHeight(lastPage) <= lastPageCapacity + FIT_TOLERANCE) {
            return pages;
        }

        // The page already fits the normal-page budget. To make the final
        // page shorter for the stamps, moving its last group is sufficient
        // whenever that group itself fits the reduced budget. Moving the
        // largest suffix that fits would invert the balance: a nearly full
        // one-page PRL could leave only one row on page 1 and put everything
        // else on page 2.
        const lastItem = lastPage[lastPage.length - 1];
        const suffix = lastItem && lastItem.height <= lastPageCapacity + FIT_TOLERANCE
            ? [lastItem]
            : [];

        // A single unusually tall row may leave no room for the stamps. In
        // that exceptional case the stamps receive their own final page; the
        // data row is never clipped or silently hidden.
        if (suffix.length === 0) {
            pages.push([]);
            return pages;
        }

        pages[lastPageIndex] = lastPage.slice(0, -1);
        pages.push(suffix);

        return pages;
    }

    function outerHeight(element) {
        if (!element) return 0;

        const rect = element.getBoundingClientRect();
        const style = global.getComputedStyle(element);
        const marginTop = Number.parseFloat(style.marginTop) || 0;
        const marginBottom = Number.parseFloat(style.marginBottom) || 0;

        return rect.height + marginTop + marginBottom;
    }

    function boxHeight(element) {
        return element ? element.getBoundingClientRect().height : 0;
    }

    function removeGeneratedPages() {
        document.querySelectorAll('.container-fluid.dynamic-page-wrapper').forEach((container) => {
            container.remove();
        });
    }

    function captureSource() {
        if (state.initialized) return true;

        state.originalContainer = Array.from(document.querySelectorAll('.container-fluid'))
            .find((container) => container.querySelector('.data-page[data-page-index="1"]')) || null;
        state.firstPage = state.originalContainer?.querySelector('.data-page[data-page-index="1"]') || null;
        state.firstRowsContainer = state.firstPage?.querySelector('.all-rows-container') || null;

        const header = state.firstPage?.querySelector('.header-page') || null;
        const footer = state.firstPage?.querySelector('footer') || null;
        const stamps = state.firstPage?.querySelector('.stamps-block') || null;

        if (!state.originalContainer || !state.firstPage || !state.firstRowsContainer || !header || !footer) {
            return false;
        }

        state.sourceRows = Array.from(
            state.firstRowsContainer.querySelectorAll(':scope > .data-row-prl:not(.empty-row)')
        );
        state.headerTemplate = header.cloneNode(true);
        state.footerTemplate = footer.cloneNode(true);
        state.stampsTemplate = stamps ? stamps.cloneNode(true) : null;
        state.initialized = true;

        return true;
    }

    function resetSourceRows() {
        state.sourceRows.forEach((row) => {
            row.classList.remove('print-hide-row');
            row.style.removeProperty('display');
        });
    }

    function createMeasurementStage() {
        const stage = document.createElement('div');
        stage.className = 'prl-print-measurement-stage';
        stage.setAttribute('aria-hidden', 'true');

        const page = document.createElement('div');
        page.className = 'page data-page prl-print-measurement-page';

        const header = state.headerTemplate.cloneNode(true);
        const rowsContainer = document.createElement('div');
        rowsContainer.className = 'all-rows-container';
        const footer = state.footerTemplate.cloneNode(true);

        page.append(header, rowsContainer, footer);
        stage.appendChild(page);
        document.body.appendChild(stage);

        return { stage, page, header, rowsContainer, footer };
    }

    function measureLayout(safetyGap) {
        const measurement = createMeasurementStage();
        const pageHeight = measurement.page.getBoundingClientRect().height;
        const headerHeight = boxHeight(measurement.header);
        // footer uses margin-top:auto to stay at the bottom of the flex page.
        // Chromium exposes the resolved free space as the computed margin;
        // only the footer's own border-box height belongs in the budget.
        const footerHeight = boxHeight(measurement.footer);
        const normalCapacity = Math.max(1, pageHeight - headerHeight - footerHeight - safetyGap);

        const measuredRows = state.sourceRows.map((row, index) => {
            const clone = row.cloneNode(true);
            clone.style.removeProperty('display');
            measurement.rowsContainer.appendChild(clone);
            const height = outerHeight(clone);
            clone.remove();

            return { index, row, height };
        });

        let stampsHeight = 0;
        if (state.stampsTemplate) {
            const stamps = state.stampsTemplate.cloneNode(true);
            stamps.style.display = 'block';
            stamps.classList.add('stamps-block-clone');
            measurement.rowsContainer.appendChild(stamps);
            stampsHeight = outerHeight(stamps);
            stamps.remove();
        }

        measurement.stage.remove();

        return {
            pageHeight,
            headerHeight,
            footerHeight,
            normalCapacity,
            lastPageCapacity: Math.max(0, normalCapacity - stampsHeight),
            stampsHeight,
            measuredRows,
        };
    }

    function groupMeasuredRows(measuredRows) {
        const groups = [];

        for (let index = 0; index < measuredRows.length; index += 1) {
            const current = measuredRows[index];

            // Keep a run of manual/section headings with the following data
            // row, so none of those headings can be orphaned at a page end.
            if (current.row.classList.contains('manual-row')) {
                const grouped = [];
                let cursor = index;

                while (cursor < measuredRows.length
                    && measuredRows[cursor].row.classList.contains('manual-row')) {
                    grouped.push(measuredRows[cursor]);
                    cursor += 1;
                }

                if (cursor < measuredRows.length) {
                    grouped.push(measuredRows[cursor]);
                    cursor += 1;
                }

                groups.push({
                    rows: grouped.map((item) => item.row),
                    height: grouped.reduce((total, item) => total + item.height, 0),
                    rowHeights: grouped.map((item) => item.height),
                });
                index = cursor - 1;
                continue;
            }

            groups.push({
                rows: [current.row],
                height: current.height,
                rowHeights: [current.height],
            });
        }

        return groups;
    }

    function clearFirstPage() {
        state.firstRowsContainer.replaceChildren();
        state.firstPage.querySelectorAll('.stamps-block-clone').forEach((block) => block.remove());
        const stampsSource = state.firstPage.querySelector('.stamps-block');
        if (stampsSource) stampsSource.style.display = 'none';
        state.firstPage.dataset.pageIndex = '1';
        state.firstPage.style.removeProperty('page-break-before');
    }

    function createAdditionalPage(pageNumber) {
        const wrapper = document.createElement('div');
        wrapper.className = 'container-fluid dynamic-page-wrapper';

        const page = document.createElement('div');
        page.className = 'page data-page';
        page.dataset.pageIndex = String(pageNumber);
        page.style.setProperty('page-break-before', 'always');

        const rowsContainer = document.createElement('div');
        rowsContainer.className = 'all-rows-container';

        page.append(
            state.headerTemplate.cloneNode(true),
            rowsContainer,
            state.footerTemplate.cloneNode(true)
        );
        wrapper.appendChild(page);

        return { wrapper, page, rowsContainer };
    }

    function createEmptyRow(height) {
        const row = document.createElement('div');
        row.className = 'row data-row-prl ms-3 empty-row prl-height-filler-row';
        row.style.width = '100%';
        row.style.height = `${height}px`;
        row.style.minHeight = `${height}px`;
        row.setAttribute('aria-hidden', 'true');
        row.innerHTML = `
            <div class="prl-col-fig border-l-b"></div>
            <div class="prl-col-item border-l-b"></div>
            <div class="prl-col-desc border-l-b"></div>
            <div class="prl-col-part border-l-b"></div>
            <div class="prl-col-qty border-l-b"></div>
            <div class="prl-col-code border-l-b"></div>
            <div class="prl-col-po border-l-b"></div>
            <div class="prl-col-notes border-l-b-r"></div>
        `;

        return row;
    }

    function renderPages(plannedPages, metrics) {
        removeGeneratedPages();
        clearFirstPage();

        let insertionAnchor = state.originalContainer;
        const renderedPages = [];

        plannedPages.forEach((groups, index) => {
            const pageNumber = index + 1;
            let page = state.firstPage;
            let rowsContainer = state.firstRowsContainer;

            if (index > 0) {
                const additional = createAdditionalPage(pageNumber);
                page = additional.page;
                rowsContainer = additional.rowsContainer;
                insertionAnchor.parentNode.insertBefore(additional.wrapper, insertionAnchor.nextSibling);
                insertionAnchor = additional.wrapper;
            }

            groups.forEach((group) => {
                group.rows.forEach((row) => rowsContainer.appendChild(row));
            });

            const isLastPage = index === plannedPages.length - 1;
            const contentHeight = sumHeight(groups);
            const capacity = isLastPage ? metrics.lastPageCapacity : metrics.normalCapacity;
            let fillerHeights = [];

            if (isLastPage) {
                fillerHeights = planFillerRows(
                    Math.max(0, metrics.lastPageCapacity - contentHeight),
                    32
                );
                fillerHeights.forEach((height) => rowsContainer.appendChild(createEmptyRow(height)));
            }

            if (isLastPage && state.stampsTemplate) {
                const stamps = state.stampsTemplate.cloneNode(true);
                stamps.style.display = 'block';
                stamps.classList.add('stamps-block-clone');
                rowsContainer.appendChild(stamps);
            }

            page.dataset.prlContentHeight = contentHeight.toFixed(2);
            page.dataset.prlContentCapacity = capacity.toFixed(2);
            page.dataset.prlRowCount = String(groups.reduce((count, group) => count + group.rows.length, 0));
            page.dataset.prlFillerRowCount = String(fillerHeights.length);
            page.dataset.prlFillerHeight = sumHeight(fillerHeights.map((height) => ({ height }))).toFixed(2);
            page.dataset.prlOverflow = contentHeight > capacity + FIT_TOLERANCE ? '1' : '0';
            renderedPages.push(page);
        });

        renderedPages.forEach((page, index) => {
            const counter = page.querySelector('footer .prl-page-counter');
            if (counter) counter.textContent = `${index + 1} of ${renderedPages.length}`;
        });

        document.documentElement.dataset.prlPaginationMode = 'height';
        document.documentElement.dataset.prlPageCount = String(renderedPages.length);
        document.documentElement.dataset.prlMeasuredPageHeight = metrics.pageHeight.toFixed(2);
        document.documentElement.dataset.prlMeasuredHeaderHeight = metrics.headerHeight.toFixed(2);
        document.documentElement.dataset.prlMeasuredFooterHeight = metrics.footerHeight.toFixed(2);
        document.documentElement.dataset.prlMeasuredStampsHeight = metrics.stampsHeight.toFixed(2);
        document.documentElement.dataset.prlNormalCapacity = metrics.normalCapacity.toFixed(2);
        document.documentElement.dataset.prlLastCapacity = metrics.lastPageCapacity.toFixed(2);

        return renderedPages;
    }

    function paginate(options) {
        if (!captureSource()) {
            console.warn('PRL height paginator could not find the source page structure.');
            return { totalPages: 0, pages: [], metrics: null };
        }

        resetSourceRows();
        removeGeneratedPages();

        const safetyGap = Math.max(0, Number(options?.safetyGap ?? DEFAULT_SAFETY_GAP));
        const metrics = measureLayout(safetyGap);
        const groups = groupMeasuredRows(metrics.measuredRows);
        const plannedPages = planPages(groups, metrics.normalCapacity, metrics.lastPageCapacity);
        const pages = renderPages(plannedPages, metrics);

        const result = { totalPages: pages.length, pages, metrics, plannedPages };
        api.lastResult = result;

        const oversizedPages = pages.filter((page) => page.dataset.prlOverflow === '1');
        if (oversizedPages.length > 0) {
            console.warn('PRL contains a row taller than the available printable area.', oversizedPages);
        }

        return result;
    }

    function whenReady(callback) {
        const fontsReady = document.fonts?.ready || Promise.resolve();
        const images = Array.from(document.images).map((image) => {
            if (image.complete) return Promise.resolve();
            if (typeof image.decode === 'function') return image.decode().catch(() => undefined);
            return new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true });
                image.addEventListener('error', resolve, { once: true });
            });
        });

        Promise.all([fontsReady, ...images]).then(() => {
            global.requestAnimationFrame(() => callback());
        });
    }

    const api = {
        paginate,
        whenReady,
        planPages,
        packByHeight,
        planFillerRows,
        lastResult: null,
    };

    global.PrlPrintPaginator = api;

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { planPages, packByHeight, planFillerRows };
    }
})(typeof window !== 'undefined' ? window : globalThis);
