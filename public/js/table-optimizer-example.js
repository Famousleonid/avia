/**
 * ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ БИБЛИОТЕКИ table-optimizer.js
 * 
 * Этот файл содержит практические примеры применения методики оптимизации таблиц
 */

// ============================================================================
// ПРИМЕР 1: Базовая оптимизация таблицы для формы процесса
// ============================================================================

function example1_BasicTableOptimization() {
    // Подключаем библиотеку (должна быть загружена перед этим скриптом)
    // <script src="{{ asset('js/table-optimizer.js') }}"></script>
    
    document.addEventListener('DOMContentLoaded', function() {
        // Конфигурация оптимизации таблицы
        const tableConfig = {
            tableSelector: '.data-page',
            orientation: 'portrait',
            fixedElementsSelectors: {
                header: '.page-header',
                footer: '.page-footer',
                tableHeader: '.table thead',
                title: '.page-title'
            },
            rowSelector: '.data-page [data-row-index]',
            addRowCallback: function(rowIndex, tableElement) {
                const container = typeof tableElement === 'string' ?
                    document.querySelector(tableElement) : tableElement;
                if (!container) return;
                
                const row = document.createElement('div');
                row.className = 'row empty-row';
                row.setAttribute('data-row-index', rowIndex);
                row.innerHTML = `
                    <div class="col-1 border-l-b text-center" style="height: 32px"></div>
                    <div class="col-2 border-l-b text-center" style="height: 32px"></div>
                    <div class="col-2 border-l-b text-center" style="height: 32px"></div>
                    <div class="col-4 border-l-b text-center" style="height: 32px"></div>
                    <div class="col-1 border-l-b text-center" style="height: 32px"></div>
                    <div class="col-2 border-l-b-r text-center" style="height: 32px"></div>
                `;
                container.appendChild(row);
            },
            removeRowCallback: function(rowIndex, tableElement) {
                const container = typeof tableElement === 'string' ?
                    document.querySelector(tableElement) : tableElement;
                if (!container) return;
                
                const row = container.querySelector(`[data-row-index="${rowIndex}"]`);
                if (row) row.remove();
            },
            getRowIndexCallback: function(rowElement) {
                return parseInt(rowElement.getAttribute('data-row-index')) || 0;
            },
            padding: 20,
            onComplete: function(result) {
                console.log('Оптимизация завершена:', result);
            }
        };
        
        // Применяем оптимизацию после небольшой задержки
        setTimeout(function() {
            if (typeof optimizeTableForViewAndPrint !== 'undefined') {
                const result = optimizeTableForViewAndPrint(tableConfig);
                console.log('Результаты оптимизации:', result);
            } else {
                console.error('Библиотека table-optimizer.js не загружена!');
            }
        }, 200);
    });
}

// ============================================================================
// ПРИМЕР 2: Оптимизация для альбомной ориентации (Traveler)
// ============================================================================

function example2_LandscapeOptimization() {
    document.addEventListener('DOMContentLoaded', function() {
        const landscapeConfig = {
            tableSelector: '.traveler-table',
            orientation: 'landscape', // Альбомная ориентация
            fixedElementsSelectors: {
                header: '.traveler-header',
                footer: '.traveler-footer',
                tableHeader: '.traveler-table thead'
            },
            rowSelector: '.traveler-table tbody tr',
            addRowCallback: function(rowIndex, tableElement) {
                const tbody = tableElement.querySelector('tbody');
                if (!tbody) return;
                
                const row = document.createElement('tr');
                row.setAttribute('data-row-index', rowIndex);
                row.innerHTML = `
                    <td class="border-l-b text-center" style="height: 30px"></td>
                    <td class="border-l-b text-center" style="height: 30px"></td>
                    <td class="border-l-b text-center" style="height: 30px"></td>
                    <td class="border-l-b text-center" style="height: 30px"></td>
                    <td class="border-l-b-r text-center" style="height: 30px"></td>
                `;
                tbody.appendChild(row);
            },
            removeRowCallback: function(rowIndex, tableElement) {
                const row = tableElement.querySelector(`tr[data-row-index="${rowIndex}"]`);
                if (row) row.remove();
            },
            getRowIndexCallback: function(rowElement) {
                return parseInt(rowElement.getAttribute('data-row-index')) || 0;
            }
        };
        
        setTimeout(function() {
            if (typeof optimizeTableForViewAndPrint !== 'undefined') {
                optimizeTableForViewAndPrint(landscapeConfig);
            }
        }, 200);
    });
}

// ============================================================================
// ПРИМЕР 3: Ручной расчет без автоматической оптимизации
// ============================================================================

function example3_ManualCalculation() {
    // Измеряем фиксированные элементы
    const fixedHeights = measureFixedElements({
        header: '.page-header',
        footer: '.page-footer',
        tableHeader: '.table thead'
    });
    
    // Подготавливаем объект для расчета
    const fixedHeightsForCalc = {
        header: fixedHeights.header.totalHeight || 0,
        footer: fixedHeights.footer.totalHeight || 0,
        tableHeader: fixedHeights.tableHeader.totalHeight || 0
    };
    
    // Рассчитываем доступное пространство для экрана
    const screenSpace = calculateTableHeight(
        fixedHeightsForCalc,
        'portrait',
        false, // для экрана
        20     // padding
    );
    
    // Получаем среднюю высоту строки
    const table = document.querySelector('.data-page');
    const rows = table.querySelectorAll('[data-row-index]');
    const avgRowHeight = calculateAverageRowHeight(Array.from(rows));
    
    // Рассчитываем количество строк
    const rowCount = screenSpace.calculateRows(avgRowHeight);
    
    console.log('Доступная высота для таблицы:', screenSpace.tableAvailableHeight, 'px');
    console.log('Рекомендуемый диапазон:', screenSpace.recommendedRange);
    console.log('Средняя высота строки:', avgRowHeight, 'px');
    console.log('Рекомендуемое количество строк:', rowCount);
    
    // Используем результаты для ручной настройки
    if (typeof adjustTableHeightToRange !== 'undefined') {
        adjustTableHeightToRange({
            min_height_tab: screenSpace.tableMinHeight,
            max_height_tab: screenSpace.tableMaxHeight,
            tab_name: '.data-page',
            row_height: avgRowHeight,
            // ... остальные параметры
        });
    }
}

// ============================================================================
// ПРИМЕР 4: Оптимизация нескольких таблиц на одной странице
// ============================================================================

function example4_MultipleTables() {
    document.addEventListener('DOMContentLoaded', function() {
        // Таблица 1: NDT таблица
        const ndtConfig = {
            tableSelector: '.ndt-data-container',
            orientation: 'portrait',
            fixedElementsSelectors: {
                header: '.page-header',
                tableHeader: '.ndt-table thead'
            },
            rowSelector: '.data-row-ndt[data-row-index]',
            // ... callbacks
        };
        
        // Таблица 2: Обычная таблица
        const regularConfig = {
            tableSelector: '.data-page',
            orientation: 'portrait',
            fixedElementsSelectors: {
                header: '.page-header',
                tableHeader: '.regular-table thead'
            },
            rowSelector: '.data-page [data-row-index]',
            // ... callbacks
        };
        
        setTimeout(function() {
            if (typeof optimizeTableForViewAndPrint !== 'undefined') {
                // Оптимизируем обе таблицы
                const ndtResult = optimizeTableForViewAndPrint(ndtConfig);
                const regularResult = optimizeTableForViewAndPrint(regularConfig);
                
                console.log('NDT таблица:', ndtResult);
                console.log('Обычная таблица:', regularResult);
            }
        }, 200);
    });
}

// ============================================================================
// ПРИМЕР 5: Использование с кастомными полями страницы
// ============================================================================

function example5_CustomMargins() {
    // Рассчитываем доступное пространство с кастомными полями
    const customMargins = {
        top: 1.0,    // 1 см
        right: 1.0,
        bottom: 1.0,
        left: 1.0
    };
    
    const availableSpace = calculateAvailableSpace('portrait', false, customMargins);
    console.log('Доступное пространство с кастомными полями:', availableSpace);
}

// ============================================================================
// ПРИМЕР 6: Динамическое изменение при изменении размера окна
// ============================================================================

function example6_ResponsiveOptimization() {
    let resizeTimeout;
    
    window.addEventListener('resize', function() {
        // Дебаунс для оптимизации производительности
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // Пересчитываем и переоптимизируем таблицу
            const table = document.querySelector('.data-page');
            if (table) {
                const fixedHeights = measureFixedElements({
                    header: '.page-header',
                    footer: '.page-footer',
                    tableHeader: '.table thead'
                });
                
                const fixedHeightsForCalc = {
                    header: fixedHeights.header.totalHeight || 0,
                    footer: fixedHeights.footer.totalHeight || 0,
                    tableHeader: fixedHeights.tableHeader.totalHeight || 0
                };
                
                const screenSpace = calculateTableHeight(
                    fixedHeightsForCalc,
                    'portrait',
                    false,
                    20
                );
                
                // Применяем новые размеры
                if (typeof adjustTableHeightToRange !== 'undefined') {
                    const rows = table.querySelectorAll('[data-row-index]');
                    const avgRowHeight = calculateAverageRowHeight(Array.from(rows));
                    
                    adjustTableHeightToRange({
                        min_height_tab: screenSpace.tableMinHeight,
                        max_height_tab: screenSpace.tableMaxHeight,
                        tab_name: '.data-page',
                        row_height: avgRowHeight,
                        // ... остальные параметры
                    });
                }
            }
        }, 300); // Задержка 300мс
    });
}

// ============================================================================
// ПРИМЕР 7: Интеграция с существующим кодом processesForm.blade.php
// ============================================================================

function example7_IntegrationWithExistingCode() {
    // Этот пример показывает, как интегрировать библиотеку
    // с существующим кодом в processesForm.blade.php
    
    document.addEventListener('DOMContentLoaded', function() {
        // Сначала загружаем библиотеку (если еще не загружена)
        if (typeof optimizeTableForViewAndPrint === 'undefined') {
            const script = document.createElement('script');
            script.src = '/js/table-optimizer.js';
            script.onload = function() {
                initializeOptimization();
            };
            document.head.appendChild(script);
        } else {
            initializeOptimization();
        }
        
        function initializeOptimization() {
            // Измеряем фиксированные элементы
            const fixedHeights = measureFixedElements({
                header: '.page-header',
                footer: 'footer',
                tableHeader: '.table thead'
            });
            
            // Рассчитываем доступное пространство
            const fixedHeightsForCalc = {
                header: fixedHeights.header?.totalHeight || 0,
                footer: fixedHeights.footer?.totalHeight || 0,
                tableHeader: fixedHeights.tableHeader?.totalHeight || 0
            };
            
            const screenSpace = calculateTableHeight(
                fixedHeightsForCalc,
                'portrait',
                false,
                20
            );
            
            // Используем существующие функции addEmptyRowRegular и removeRowRegular
            // из processesForm.blade.php
            const tableConfig = {
                tableSelector: '.data-page',
                orientation: 'portrait',
                fixedElementsSelectors: {
                    header: '.page-header',
                    footer: 'footer',
                    tableHeader: '.table thead'
                },
                rowSelector: '.data-page [data-row-index]',
                addRowCallback: addEmptyRowRegular, // Существующая функция
                removeRowCallback: removeRowRegular, // Существующая функция
                getRowIndexCallback: function(rowElement) {
                    return parseInt(rowElement.getAttribute('data-row-index')) || 0;
                },
                padding: 20
            };
            
            setTimeout(function() {
                const result = optimizeTableForViewAndPrint(tableConfig);
                
                // Используем результаты для дополнительной настройки
                if (result && typeof adjustTableHeightToRange !== 'undefined') {
                    console.log('Оптимизация применена:', result);
                }
            }, 200);
        }
    });
}

// ============================================================================
// ПРИМЕР 8: Отладочный вывод всех расчетов
// ============================================================================

function example8_DebugOutput() {
    console.log('=== ОТЛАДОЧНАЯ ИНФОРМАЦИЯ ===');
    
    // Размеры страницы
    console.log('Размеры Letter Portrait (96 DPI):', LETTER_PAGE.PORTRAIT);
    console.log('Размеры Letter Landscape (96 DPI):', LETTER_PAGE.LANDSCAPE);
    
    // Доступное пространство
    const screenSpace = calculateAvailableSpace('portrait', false);
    const printSpace = calculateAvailableSpace('portrait', true);
    console.log('Доступное пространство (экран):', screenSpace);
    console.log('Доступное пространство (печать):', printSpace);
    
    // Измерение элементов
    const fixedHeights = measureFixedElements({
        header: '.page-header',
        footer: '.page-footer',
        tableHeader: '.table thead'
    });
    console.log('Измеренные высоты элементов:', fixedHeights);
    
    // Расчет высоты таблицы
    const fixedHeightsForCalc = {
        header: fixedHeights.header?.totalHeight || 0,
        footer: fixedHeights.footer?.totalHeight || 0,
        tableHeader: fixedHeights.tableHeader?.totalHeight || 0
    };
    
    const tableHeight = calculateTableHeight(fixedHeightsForCalc, 'portrait', false);
    console.log('Расчет высоты таблицы:', tableHeight);
    
    // Расчет количества строк
    const avgRowHeight = 32; // Пример
    const rows = tableHeight.calculateRows(avgRowHeight);
    console.log('Расчет количества строк (при высоте строки ' + avgRowHeight + 'px):', rows);
}



