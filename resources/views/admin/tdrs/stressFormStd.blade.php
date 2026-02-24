<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STRESS RELIEF PROCESS SHEET</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        :root {
            --container-max-width: 920px;
            --container-padding: 5px;
            --container-margin-left: 10px;
            --container-margin-right: 10px;
            --print-page-margin: 1mm;
            --print-body-height: 99%;
            --print-body-width: 98%;
            --print-body-margin-left: 2px;
            --print-footer-width: 800px;
            --print-footer-font-size: 10px;
            --print-footer-padding: 2px 2px;
        }

        .container-fluid {
            max-width: var(--container-max-width);
            height: 98%;
            padding: var(--container-padding);
            margin-left: var(--container-margin-left);
            margin-right: var(--container-margin-right);
        }

        @media print {
            @page {
                size: letter;
                margin: var(--print-page-margin);
            }

            html, body {
                height: var(--print-body-height);
                width: var(--print-body-width);
                margin-left: var(--print-body-margin-left);
                padding: 0;
            }

            h1, p {
                page-break-inside: avoid;
            }

            /* Разрешаем разрыв таблиц внутри страницы, но избегаем разрыва строк */
            .data-page {
                page-break-inside: auto;
            }

            .data-row {
                page-break-inside: avoid;
            }

            .no-print {
                display: none;
            }

            /* Скрываем строки сверх лимита */
            .print-hide-row {
                display: none !important;
            }

            footer {
                position: fixed;
                bottom: 0;
                width: var(--print-footer-width);
                text-align: center;
                font-size: var(--print-footer-font-size);
                background-color: #fff;
                padding: var(--print-footer-padding);
            }

            .container {
                max-height: 100vh;
                overflow: hidden;
            }
        }

        /* Скрываем строки сверх лимита на экране тоже */
        .print-hide-row {
            display: none !important;
        }

        .border-all {
            border: 1px solid black;
        }
        .border-all-b {
            border: 2px solid black;
        }

        .border-l-t-r {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b-r {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-t-r {
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-b {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-t-b {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-b {
            border-bottom: 1px solid black;
        }
        .border-t-r-b {
            border-top: 1px solid black;
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-r-b {
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }

        .process-text-long {
            font-size: 0.9em;
            line-height: 1;
            letter-spacing: -0.3px;
            display: inline-block;
            transform-origin: left;

        }
        .description-text-long {
            font-size: 0.9rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-black {
            color: #000;
        }

        .fs-7 {
            font-size: 0.9rem;
        }
        .fs-75 {
            font-size: 0.8rem;
        }
        .fs-85 {
            font-size: 0.85rem;
        }
        .fs-8 {
            font-size: 0.7rem;
        }

        .details-row {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 36px;
        }
        .description-text-long {
            font-size: 0.9rem;
            line-height: 1.1;
            letter-spacing: -0.3px;
            display: inline-block;
            vertical-align: top;
        }
        .header-page .component-name-value { font-size: var(--component-name-font-size, 12px) !important; }
        .header-page .component-name-value[data-long="1"] { line-height: 1.1; letter-spacing: -0.3px; }
        /* ITEM No. — уменьшенный межстрочный интервал */
        .data-row > div:first-child,
        .table-header .row > div:first-child { line-height: 1.1; }
        .details-cell {
            display: flex;
            justify-content: center;
            align-items: center;
            line-height: 1.2;
        }
    </style>
</head>
<body>
<!-- Кнопки для печати и настроек -->
<div class="text-start m-1 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        ⚙️ Print Settings
    </button>
</div>
<div class="container-fluid">
    <div class="header-page">
        <div class="row">
            <div class="col-3">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 180px; margin: 6px 10px 0;">
            </div>
            <div class="col-9">
                <h2 class="mt-3 text-black"><strong>STRESS RELIEF PROCESS SHEET</strong></h2>
            </div>
        </div>
        <div class="row ">
            <div class="col-6">
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>COMPONENT NAME</strong> :</div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>
                            <span class="component-name-value" @if(strlen($current_wo->description) > 30) data-long="1" @endif>{{$current_wo->description}}</span>
                        </strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>PART NUMBER:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>{{$current_wo->unit->part_number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>WORK ORDER No:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>W{{$current_wo->number}}</strong></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-6 pt-2 text-end"><strong>SERIAL No:</strong></div>
                    <div class="col-6 fs-7 pt-2 border-b"><strong>{{$current_wo->serial_number}}</strong></div>
                </div>
            </div>
            <div class="col-6">
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>DATE:</strong></div>
                    <div class="col-8 pt-2 border-b"></div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>RO No:</strong></div>
                    <div class="col-8 pt-2 border-b">INTERNAL</div>
                </div>
                <div class="row" style="height: 32px">
                    <div class="col-4 pt-2 text-end"><strong>VENDOR:</strong></div>
                    <div class="col-8 pt-2 border-b"><strong> AVIATECHNIK</strong></div>
                </div>
                <div class="row" style="height: 32px">
{{--                    <div class="col-4 pt-2 text-end"><strong>TOTAL QTY:</strong></div>--}}
{{--                    <div class="col-8 pt-2 border-b">--}}
{{--                        @if(isset($total_quantities['total_qty']))--}}
{{--                            {{ $total_quantities['total_qty'] }}--}}
{{--                        @endif--}}
{{--                    </div>--}}
{{--                </div>--}}
            </div>

        </div>
            <div class="row">
                <div class="col-6"></div>
                <div class="col-3 text-end pe-2 pt-3">
                    <strong>
                        MANUAL REF:
                    </strong>

                </div>
                <div class="col-3 border-all text-center" style="height: 55px">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h6 class="text-center mt-3"> <strong> {{substr($manual->number, 0, 8)}} </strong></h6>
                        @endif
                    @endforeach
                </div>
            </div>
           <h5 class="ps-3 mt-2 mb-2 ">
               @foreach($manuals as $manual)
                   @if($manual->id == $current_wo->unit->manual_id)
                       <h6 class="ps-4">
                           <strong class="">
                           {{__('Perform the Stress Relief as specified under Process No. and in accordance with SMM No. ')}}
{{--                            <span class="ms-5">--}}
{{--                                {{$manual->number}}--}}
{{--                            </span>--}}
                          </strong>
                       </h6>
                   @endif
               @endforeach
           </h5>
    </div>

    <div class="page table-header">
        <div class="row mt-2">
            <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7">
                    <strong>ITEM No.</strong></h6></div>
            <div class="col-2 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PART No.</strong></h6></div>
            <div class="col-2 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>DESCRIPTION</strong></h6></div>
            <div class="col-4 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>PROCESS No.</strong></h6></div>
            <div class="col-1 border-l-t-b pt-2 details-row text-center" style="height: 42px"><h6 class="fs-7"><strong>QTY</strong></h6></div>
            <div class="col-2 border-all pt-2  details-row  text-center" style="height: 42px">
                <h6  class="fs-7" ><strong>PERFORMED</strong> </h6>
            </div>
        </div>
    </div>

    @php
        // Все компоненты передаются без разбиения на страницы
        // Разбиение происходит на фронтенде через JavaScript
        $previousManual = null;
    @endphp

    {{-- Все компоненты выводятся в одном контейнере - разбиение на страницы через JavaScript --}}
    <div class="all-rows-container">
        @php
            $rowIndex = 1;
        @endphp

        @foreach($stress_components as $component)
            @php
                $currentManual = $component->manual ?? null;
                // Если manual изменился и не пустой, вставляем строку с manual
                $shouldInsertManualRow = ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);
            @endphp

            @if($shouldInsertManualRow)
                {{-- Строка с Manual --}}
                <div class="row fs-85 data-row manual-row" data-row-index="{{ $rowIndex }}">
                    <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px; font-weight: bold;">
                        <strong>{{ $currentManual }}</strong>
                    </div>
                    <div class="col-4 border-l-b details-cell text-center" style="min-height: 34px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                    <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px; font-weight: bold;">
                        <!-- Пустая ячейка -->
                    </div>
                </div>
                @php $rowIndex++; @endphp
            @endif

            <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->ipl_num }}
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->part_number }}
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->name }}
                </div>
                <div class="col-4 border-l-b details-cell text-center process-cell" style="min-height: 34px">
                    {{ $component->process_name }}
                </div>
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->qty }}
                </div>
                <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px">
{{--                    @foreach($manuals as $manual)--}}
{{--                        @if($manual->id == $current_wo->unit->manual_id)--}}
{{--                            <h6 class="text-center mt-3">{{$manual->number}}</h6>--}}
{{--                        @endif--}}
{{--                    @endforeach--}}
                </div>
            </div>
            @php
                $rowIndex++;
                $previousManual = $currentManual;
            @endphp
        @endforeach
    </div>
    {{-- Пустые строки будут генерироваться на фронтенде через JavaScript --}}

    <footer>
        <div class="row fs-85" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__('Form # 015')}}
            </div>
            <div class="col-3 text-center">
                {{__('Page')}} <span class="page-number">1</span> {{__('of')}} <span class="total-pages">1</span>
            </div>
            <div class="col-3 text-end pe-4">
                {{__('Rev#0, 15/Dec/2012   ')}}
                <br>
                {{'Total: '}} {{ $stressSum['total_qty'] }}
            </div>
        </div>
    </footer>
</div>

@php $tdrFormConfig = config('tdr_forms.stressFormStd'); @endphp
@include('shared.tdr-forms._print-settings-modal', ['formType' => 'stressFormStd', 'formConfig' => $tdrFormConfig])

<!-- Bootstrap JS для работы модального окна -->
<script>
    if (typeof window.bootstrapLoaded === 'undefined') {
        window.bootstrapLoaded = true;
        const script = document.createElement('script');
        script.src = "{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}";
        script.async = true;
        document.head.appendChild(script);
    }
</script>

<script>
    // Stress Relief-специфичная логика лимитов строк (используется shared scripts)
    window.tdrFormApplyTableRowLimits = function(settings) {
        const stressMaxRows = parseInt(settings.stressTableRows) || 21;
        console.log('Применение ограничений строк Stress Relief:', { stressMaxRows, settings });
        
        const allRowsContainer = document.querySelector('.all-rows-container');
        if (!allRowsContainer) {
            console.warn('Контейнер .all-rows-container не найден!');
            return;
        }
        
        // Удаляем все созданные ранее динамические страницы
        document.querySelectorAll('.dynamic-page-wrapper').forEach(function(wrapper) {
            wrapper.remove();
        });
        
        // Удаляем все пустые строки из контейнера перед пересчётом
        const emptyRowsToRemove = allRowsContainer.querySelectorAll('.data-row.empty-row, .empty-row');
        emptyRowsToRemove.forEach(function(row) {
            row.remove();
        });
        console.log('Удалено пустых строк перед пересчётом:', emptyRowsToRemove.length);
        
        // Собираем все строки из контейнера (только строки с данными, без пустых)
        const allRows = Array.from(allRowsContainer.querySelectorAll('.data-row:not(.empty-row)'));
        
        // Разделяем на manual-row и data-rows
        const manualRows = allRows.filter(function(row) {
            return row.classList.contains('manual-row');
        });
        const dataRows = allRows.filter(function(row) {
            return !row.classList.contains('manual-row');
        });
        
        const hasManualRows = manualRows.length > 0;
        console.log('Найдено manual-row:', hasManualRows, 'количество:', manualRows.length);
        console.log('Найдено строк с данными:', dataRows.length);
        
        let totalRows;
        let rowsToProcess;
        
        if (hasManualRows) {
            // Случай с manual-row: считаем все строки (manual + data)
            totalRows = allRows.length;
            rowsToProcess = allRows;
        } else {
            // Случай без manual-row: считаем только data-rows
            totalRows = dataRows.length;
            rowsToProcess = dataRows;
        }
        
        // Вычисляем количество страниц
        const totalPages = Math.max(1, Math.ceil(totalRows / stressMaxRows));
        console.log('Всего строк:', totalRows, ', Лимит на странице:', stressMaxRows, ', Создано страниц:', totalPages);
        
        // Находим элементы для копирования
        const originalHeader = document.querySelector('.header-page');
        const originalTableHeader = document.querySelector('.table-header');
        const originalFooter = document.querySelector('footer');
        const firstContainerFluid = document.querySelector('.container-fluid');
        
        if (!originalHeader || !originalTableHeader || !originalFooter || !firstContainerFluid) {
            console.warn('Не найдены необходимые элементы для создания страниц:', {
                header: !!originalHeader,
                tableHeader: !!originalTableHeader,
                footer: !!originalFooter,
                containerFluid: !!firstContainerFluid
            });
            return;
        }
        
        // Скрываем строки, которые не на первой странице
        rowsToProcess.forEach(function(row, index) {
            if (index < stressMaxRows) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Обновляем footer для первой страницы
        const firstPageNumberEl = originalFooter.querySelector('.page-number');
        const firstTotalPagesEl = originalFooter.querySelector('.total-pages');
        if (firstPageNumberEl) firstPageNumberEl.textContent = '1';
        if (firstTotalPagesEl) firstTotalPagesEl.textContent = totalPages;
        
        // Создаём дополнительные страницы (начиная со второй)
        for (let pageIndex = 1; pageIndex < totalPages; pageIndex++) {
            const startIndex = pageIndex * stressMaxRows;
            const endIndex = Math.min(startIndex + stressMaxRows, rowsToProcess.length);
            const pageRows = rowsToProcess.slice(startIndex, endIndex);
            
            // Создаём контейнер для новой страницы (как container-fluid)
            const dynamicPageWrapper = document.createElement('div');
            dynamicPageWrapper.className = 'container-fluid dynamic-page-wrapper';
            
            // Создаём новую страницу
            const pageDiv = document.createElement('div');
            pageDiv.className = 'page data-page';
            pageDiv.setAttribute('data-page-index', pageIndex + 1);
            pageDiv.style.pageBreakBefore = 'always';
            
            // Копируем header
            if (originalHeader) {
                const headerClone = originalHeader.cloneNode(true);
                pageDiv.appendChild(headerClone);
            }
            
            // Копируем table-header
            if (originalTableHeader) {
                const tableHeaderClone = originalTableHeader.cloneNode(true);
                pageDiv.appendChild(tableHeaderClone);
            }
            
            // Создаём контейнер для строк этой страницы
            const rowsContainer = document.createElement('div');
            rowsContainer.className = 'page-rows-container';
            
            // Клонируем строки для этой страницы
            pageRows.forEach(function(row) {
                const rowClone = row.cloneNode(true);
                rowClone.style.display = '';
                rowsContainer.appendChild(rowClone);
            });
            
            // Добавляем пустые строки на последней странице, если нужно
            if (pageIndex === totalPages - 1) {
                const rowsOnLastPage = pageRows.length;
                const emptyRowsNeeded = rowsOnLastPage === 0 ? stressMaxRows : (stressMaxRows - rowsOnLastPage);
                
                if (emptyRowsNeeded > 0 && emptyRowsNeeded < stressMaxRows) {
                    for (let i = 0; i < emptyRowsNeeded; i++) {
                        const emptyRow = document.createElement('div');
                        emptyRow.className = 'row fs-85 data-row empty-row';
                        emptyRow.innerHTML = `
                            <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px"></div>
                            <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px"></div>
                            <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px"></div>
                            <div class="col-4 border-l-b details-cell text-center" style="min-height: 34px"></div>
                            <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px"></div>
                            <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px"></div>
                        `;
                        rowsContainer.appendChild(emptyRow);
                    }
                    console.log('Добавлено пустых строк на последнюю страницу:', emptyRowsNeeded, 'из', stressMaxRows, '(строк на странице:', rowsOnLastPage, ')');
                }
            }
            
            pageDiv.appendChild(rowsContainer);
            
            // Копируем footer с правильной нумерацией
            if (originalFooter) {
                const footerClone = originalFooter.cloneNode(true);
                const pageNumberEl = footerClone.querySelector('.page-number');
                const totalPagesEl = footerClone.querySelector('.total-pages');
                if (pageNumberEl) {
                    pageNumberEl.textContent = pageIndex + 1;
                }
                if (totalPagesEl) {
                    totalPagesEl.textContent = totalPages;
                }
                pageDiv.appendChild(footerClone);
            }
            
            // Добавляем pageDiv в dynamicPageWrapper
            dynamicPageWrapper.appendChild(pageDiv);
            
            // Вставляем страницу после первого container-fluid
            if (firstContainerFluid && firstContainerFluid.parentNode) {
                const nextSibling = firstContainerFluid.nextSibling;
                if (nextSibling) {
                    firstContainerFluid.parentNode.insertBefore(dynamicPageWrapper, nextSibling);
                } else {
                    firstContainerFluid.parentNode.appendChild(dynamicPageWrapper);
                }
            } else {
                console.warn('Не удалось найти родительский элемент для вставки страницы');
            }
        }
        
        // Добавляем пустые строки на первую страницу, если это единственная страница и нужно
        if (totalPages === 1) {
            const rowsOnFirstPage = rowsToProcess.length;
            const emptyRowsNeeded = rowsOnFirstPage === 0 ? stressMaxRows : (stressMaxRows - rowsOnFirstPage);
            
            if (emptyRowsNeeded > 0 && emptyRowsNeeded < stressMaxRows) {
                for (let i = 0; i < emptyRowsNeeded; i++) {
                    const emptyRow = document.createElement('div');
                    emptyRow.className = 'row fs-85 data-row empty-row';
                    emptyRow.innerHTML = `
                        <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px"></div>
                        <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px"></div>
                        <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px"></div>
                        <div class="col-4 border-l-b details-cell text-center" style="min-height: 34px"></div>
                        <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px"></div>
                        <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px"></div>
                    `;
                    allRowsContainer.appendChild(emptyRow);
                }
                console.log('Добавлено пустых строк на первую страницу:', emptyRowsNeeded, 'из', stressMaxRows, '(строк на странице:', rowsOnFirstPage, ')');
            }
        }
        
        console.log('Ограничения строк применены. Всего страниц:', totalPages);
    };
</script>
<script src="{{ asset('js/main.js') }}"></script>
@include('shared.tdr-forms._scripts', ['formType' => 'stressFormStd', 'formConfig' => $tdrFormConfig])
<script>
// Предотвращаем ошибки Chart.js - переопределяем функцию как можно раньше
(function() {
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
})();
</script>
<!-- table-height-adjuster.js отключен - управление строками через Print Settings -->

<!-- Общие модули -->
<script src="{{ asset('js/tdrs/forms/common/multi-page-handler.js') }}"></script>

<!-- Переиспользуемые модули из tdr-processes -->
<script src="{{ asset('js/tdr-processes/processes-form/row-manager.js') }}"></script>

<!-- Модули для Stress Relief формы -->
<script src="{{ asset('js/tdrs/forms/stress/stress-row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/stress/stress-form-main.js') }}"></script>
</body>
</html>
