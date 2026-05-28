<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.user-scoped-storage')
    @php
        $tdrFormConfig = config('tdr_forms.paintFormStd');
        $componentName = (string) $current_wo->description;
        $manualNumber = substr((string) ($manual->number ?? ''), 0, 8);
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAINT PROCESS SHEET</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    @include('admin.tdrs.partials.std-sheet-styles', ['tdrFormConfig' => $tdrFormConfig])
</head>
<body>
@include('admin.tdrs.partials.std-sheet-toolbar')

<div class="container-fluid std-sheet-container">
    <div class="std-page page data-page" data-page-index="1">
        <div class="std-header header-page">
            <div class="std-header-top">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo" class="std-header-logo">
                <h2 class="std-header-title">PAINT PROCESS SHEET</h2>
            </div>

            <div class="std-meta-grid">
                <div class="std-meta-column">
                    <div class="std-meta-row">
                        <div class="std-meta-label">COMPONENT NAME:</div>
                        <div class="std-meta-value">
                            <strong>
                                <span class="std-component-name" @if(strlen($componentName) > 30) data-long="1" @endif>{{ $componentName }}</span>
                            </strong>
                        </div>
                    </div>
                    <div class="std-meta-row">
                        <div class="std-meta-label">PART NUMBER:</div>
                        <div class="std-meta-value"><strong>{{ $current_wo->unit->part_number }}</strong></div>
                    </div>
                    <div class="std-meta-row">
                        <div class="std-meta-label">WORK ORDER No:</div>
                        <div class="std-meta-value"><strong>W{{ $current_wo->number }}</strong></div>
                    </div>
                    <div class="std-meta-row">
                        <div class="std-meta-label">SERIAL No:</div>
                        <div class="std-meta-value"><strong>{{ $current_wo->serial_number }}</strong></div>
                    </div>
                </div>

                <div class="std-meta-column">
                    <div class="std-meta-row std-meta-row--right">
                        <div class="std-meta-label">DATE:</div>
                        <div class="std-meta-value"></div>
                    </div>
                    <div class="std-meta-row std-meta-row--right">
                        <div class="std-meta-label">RO No:</div>
                        <div class="std-meta-value">INTERNAL</div>
                    </div>
                    <div class="std-meta-row std-meta-row--right">
                        <div class="std-meta-label">VENDOR:</div>
                        <div class="std-meta-value"><strong>AVIATECHNIK</strong></div>
                    </div>
                </div>
            </div>

            <div class="std-instruction std-instruction-row">
                <div>Perform the Paint process as specified under Process No. and in accordance with SMM No.</div>
                <div class="std-manual-ref-label">MANUAL REF:</div>
                <div class="std-manual-ref-box">{{ $manualNumber }}</div>
            </div>
        </div>

        <div class="std-table table-header" style="--std-table-columns: 1fr 2fr 2fr 4fr 1fr 2fr;">
            <div class="std-grid-row std-grid-row--header">
                <div class="std-cell">ITEM No.</div>
                <div class="std-cell">PART No.</div>
                <div class="std-cell">DESCRIPTION</div>
                <div class="std-cell">PROCESS No.</div>
                <div class="std-cell">QTY</div>
                <div class="std-cell">CMM No.</div>
            </div>
        </div>

        <div class="all-rows-container" style="--std-table-columns: 1fr 2fr 2fr 4fr 1fr 2fr;">
            @php
                $previousManual = null;
                $rowIndex = 1;
            @endphp

            @forelse($paint_components as $component)
                @php
                    $currentManual = $component->manual ?? null;
                    $shouldInsertManualRow = $currentManual !== null
                        && $currentManual !== ''
                        && $currentManual !== $previousManual;
                @endphp

                @if($shouldInsertManualRow)
                    <div class="data-row manual-row std-grid-row std-grid-row--manual std-grid-row--full" data-row-index="{{ $rowIndex }}">
                        <div class="std-cell"><strong>{{ $currentManual }}</strong></div>
                    </div>
                    @php $rowIndex++; @endphp
                @endif

                @php $rowHeight = max(34, (int) ($component->row_height ?? 32)); @endphp
                <div class="data-row std-grid-row" data-row-index="{{ $rowIndex }}" style="--std-row-min-height: {{ $rowHeight }}px;">
                    <div class="std-cell">
                        <span class="std-cell--multiline">{{ $component->item_display ?? $component->ipl_num }}</span>
                    </div>
                    <div class="std-cell">{{ $component->part_number }}</div>
                    <div class="std-cell">
                        <span @if(strlen($component->name) > 15) class="std-description-long" @endif>{{ $component->name }}</span>
                    </div>
                    <div class="std-cell">
                        <span @if(strlen($component->process_name) > 30) class="std-description-long" @endif>{{ $component->process_name }}</span>
                    </div>
                    <div class="std-cell">{{ $component->qty }}</div>
                    <div class="std-cell">{{ $manualNumber }}</div>
                </div>
                @php
                    $rowIndex++;
                    $previousManual = $currentManual;
                @endphp
            @empty
                <div class="data-row std-grid-row std-grid-row--full" data-row-index="1">
                    <div class="std-cell"><strong>No Paint components with paint_list flag</strong></div>
                </div>
            @endforelse
        </div>

        <div class="std-table-summary">
            {{ __('Total QTY:') }} <strong>{{ $paintSum['total_qty'] ?? 0 }}</strong>
        </div>
        <footer class="std-footer">
            <div class="std-footer-grid">
                <div class="std-footer-left">{{ __('Form # 014') }}</div>
                <div class="std-footer-center">
                    {{ __('Page') }} <span class="page-number">1</span> {{ __('of') }} <span class="total-pages">1</span>
                </div>
                <div class="std-footer-right">
                    {{ __('Rev#0, 15/Dec/2012') }}
                </div>
            </div>
        </footer>
    </div>
</div>

@include('shared.tdr-forms._print-settings-modal', ['formType' => 'paintFormStd', 'formConfig' => $tdrFormConfig])

<script>
    if (typeof window.bootstrapLoaded === 'undefined') {
        window.bootstrapLoaded = true;
        const script = document.createElement('script');
        script.src = "{{ asset('assets/Bootstrap 5/bootstrap.bundle.min.js') }}";
        script.async = true;
        document.head.appendChild(script);
    }
</script>

<script>
    window.tdrFormApplyTableRowLimits = function(settings) {
        const paintMaxRows = parseInt(settings.stdTableRows ?? settings.paintTableRows, 10) || 18;
        const primarySheet = document.querySelector('.container-fluid.std-sheet-container');
        const allRowsContainer = primarySheet ? primarySheet.querySelector('.all-rows-container') : null;

        if (!primarySheet || !allRowsContainer) {
            return;
        }

        document.querySelectorAll('.dynamic-page-wrapper').forEach(function(wrapper) {
            wrapper.remove();
        });

        allRowsContainer.querySelectorAll('.empty-row').forEach(function(row) {
            row.remove();
        });

        const allRows = Array.from(allRowsContainer.querySelectorAll('.data-row:not(.empty-row)'));
        const manualRows = allRows.filter(function(row) {
            return row.classList.contains('manual-row');
        });
        const dataRows = allRows.filter(function(row) {
            return !row.classList.contains('manual-row');
        });
        const hasManualRows = manualRows.length > 0;
        const rowsToProcess = hasManualRows ? allRows : dataRows;
        const totalRows = rowsToProcess.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / paintMaxRows));

        const originalHeader = primarySheet.querySelector('.header-page');
        const originalTableHeader = primarySheet.querySelector('.table-header');
        const originalFooter = primarySheet.querySelector('footer');

        rowsToProcess.forEach(function(row, index) {
            row.style.display = index < paintMaxRows ? '' : 'none';
        });

        const updateFooter = function(footer, page, total) {
            const pageEl = footer.querySelector('.page-number');
            const totalEl = footer.querySelector('.total-pages');
            if (pageEl) pageEl.textContent = String(page);
            if (totalEl) totalEl.textContent = String(total);
        };

        updateFooter(originalFooter, 1, totalPages);

        let insertAnchor = primarySheet;
        for (let pageIndex = 1; pageIndex < totalPages; pageIndex++) {
            const startIndex = pageIndex * paintMaxRows;
            const endIndex = Math.min(startIndex + paintMaxRows, rowsToProcess.length);
            const pageRows = rowsToProcess.slice(startIndex, endIndex);

            const pageWrapper = document.createElement('div');
            pageWrapper.className = 'container-fluid std-sheet-container dynamic-page-wrapper';

            const pageDiv = document.createElement('div');
            pageDiv.className = 'std-page page data-page';
            pageDiv.setAttribute('data-page-index', String(pageIndex + 1));

            pageDiv.appendChild(originalHeader.cloneNode(true));
            pageDiv.appendChild(originalTableHeader.cloneNode(true));

            const rowsContainer = document.createElement('div');
            rowsContainer.className = 'all-rows-container';
            rowsContainer.style.setProperty('--std-table-columns', getComputedStyle(allRowsContainer).getPropertyValue('--std-table-columns'));

            pageRows.forEach(function(row) {
                const node = row.cloneNode(true);
                node.style.display = '';
                rowsContainer.appendChild(node);
            });

            const rowsOnLastPage = pageRows.length;
            const emptyRowsNeeded = rowsOnLastPage === 0 ? paintMaxRows : (paintMaxRows - rowsOnLastPage);
            if (pageIndex === totalPages - 1 && emptyRowsNeeded > 0 && emptyRowsNeeded < paintMaxRows) {
                for (let i = 0; i < emptyRowsNeeded; i++) {
                    const emptyRow = document.createElement('div');
                    emptyRow.className = 'data-row empty-row std-grid-row';
                    emptyRow.innerHTML =
                        '<div class="std-cell"></div><div class="std-cell"></div><div class="std-cell"></div>' +
                        '<div class="std-cell"></div><div class="std-cell"></div><div class="std-cell"></div>';
                    rowsContainer.appendChild(emptyRow);
                }
            }

            pageDiv.appendChild(rowsContainer);

            const footerClone = originalFooter.cloneNode(true);
            updateFooter(footerClone, pageIndex + 1, totalPages);
            pageDiv.appendChild(footerClone);

            pageWrapper.appendChild(pageDiv);
            primarySheet.parentNode.insertBefore(pageWrapper, insertAnchor.nextSibling);
            insertAnchor = pageWrapper;
        }

        if (totalPages === 1) {
            const emptyRowsNeeded = totalRows === 0 ? paintMaxRows : (paintMaxRows - totalRows);
            if (emptyRowsNeeded > 0 && emptyRowsNeeded < paintMaxRows) {
                for (let i = 0; i < emptyRowsNeeded; i++) {
                    const emptyRow = document.createElement('div');
                    emptyRow.className = 'data-row empty-row std-grid-row';
                    emptyRow.innerHTML =
                        '<div class="std-cell"></div><div class="std-cell"></div><div class="std-cell"></div>' +
                        '<div class="std-cell"></div><div class="std-cell"></div><div class="std-cell"></div>';
                    allRowsContainer.appendChild(emptyRow);
                }
            }
        }
    };
</script>
@include('components.session-heartbeat-config')
<script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
@include('shared.tdr-forms._scripts', ['formType' => 'paintFormStd', 'formConfig' => $tdrFormConfig])
</body>
</html>
