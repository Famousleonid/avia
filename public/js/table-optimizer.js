/**
 * Универсальная библиотека для оптимизации таблиц при просмотре и печати
 * Основана на методике расчета размеров страницы Letter и динамических элементов
 */

// ============================================================================
// 1. КОНСТАНТЫ И БАЗОВЫЕ РАСЧЕТЫ
// ============================================================================

/**
 * Константы размеров страницы Letter
 */
const LETTER_PAGE = {
    PORTRAIT: {
        width: 816,      // px при 96 DPI (стандарт экрана)
        height: 1056,    // px при 96 DPI
        widthPrint: 2550,   // px при 300 DPI (стандарт печати)
        heightPrint: 3300   // px при 300 DPI
    },
    LANDSCAPE: {
        width: 1056,     // px при 96 DPI
        height: 816,     // px при 96 DPI
        widthPrint: 3300,   // px при 300 DPI
        heightPrint: 2550   // px при 300 DPI
    }
};

/**
 * Стандартные поля страницы (margins)
 */
const PAGE_MARGINS = {
    top: 0.5,    // cm
    right: 0.5,  // cm
    bottom: 0.5, // cm
    left: 0.5    // cm
};

/**
 * Коэффициент конвертации cm в px (при 96 DPI)
 */
const CM_TO_PX = 37.8;

/**
 * Коэффициент конвертации для печати (300 DPI)
 */
const CM_TO_PX_PRINT = 118.11;

// ============================================================================
// 2. ФУНКЦИИ РАСЧЕТА РАЗМЕРОВ
// ============================================================================

/**
 * Расчет доступного пространства на странице
 * @param {string} orientation - 'portrait' или 'landscape'
 * @param {boolean} isPrint - true для печати, false для экрана
 * @param {Object} customMargins - Кастомные поля {top, right, bottom, left} в cm
 * @returns {Object} Объект с расчетными размерами
 */
function calculateAvailableSpace(orientation = 'portrait', isPrint = false, customMargins = null) {
    const page = orientation === 'portrait' ? LETTER_PAGE.PORTRAIT : LETTER_PAGE.LANDSCAPE;
    const margins = customMargins || PAGE_MARGINS;
    const cmToPx = isPrint ? CM_TO_PX_PRINT : CM_TO_PX;
    
    // Используем размеры для печати или экрана
    const pageWidth = isPrint ? page.widthPrint : page.width;
    const pageHeight = isPrint ? page.heightPrint : page.height;
    
    // Вычитаем поля
    const marginWidth = (margins.left + margins.right) * cmToPx;
    const marginHeight = (margins.top + margins.bottom) * cmToPx;
    
    return {
        width: Math.round(pageWidth - marginWidth),
        height: Math.round(pageHeight - marginHeight),
        pageWidth: pageWidth,
        pageHeight: pageHeight,
        margins: {
            width: Math.round(marginWidth),
            height: Math.round(marginHeight),
            top: Math.round(margins.top * cmToPx),
            right: Math.round(margins.right * cmToPx),
            bottom: Math.round(margins.bottom * cmToPx),
            left: Math.round(margins.left * cmToPx)
        }
    };
}

/**
 * Расчет доступной высоты для таблицы данных
 * @param {Object} fixedElements - Объект с высотами фиксированных элементов в px
 * @param {string} orientation - 'portrait' или 'landscape'
 * @param {boolean} isPrint - true для печати, false для экрана
 * @param {number} padding - Дополнительный отступ между элементами в px
 * @returns {Object} Объект с расчетными значениями
 */
function calculateTableHeight(fixedElements = {}, orientation = 'portrait', isPrint = false, padding = 20) {
    const availableSpace = calculateAvailableSpace(orientation, isPrint);
    
    // Суммируем высоты всех фиксированных элементов
    const fixedHeight = (fixedElements.header || 0) + 
                       (fixedElements.footer || 0) + 
                       (fixedElements.tableHeader || 0) +
                       (fixedElements.title || 0) +
                       (fixedElements.other || 0);
    
    // Вычитаем из доступной высоты
    const tableAvailableHeight = availableSpace.height - fixedHeight - padding;
    
    // Рекомендуемый диапазон (с запасом для вариативности)
    const tableMinHeight = Math.round(tableAvailableHeight * 0.90);  // 90% от максимума
    const tableMaxHeight = Math.round(tableAvailableHeight * 0.98);  // 98% от максимума
    const tableOptimal = Math.round((tableMinHeight + tableMaxHeight) / 2);
    
    return {
        availableSpace: availableSpace,
        fixedHeight: fixedHeight,
        tableAvailableHeight: tableAvailableHeight,
        tableMinHeight: tableMinHeight,
        tableMaxHeight: tableMaxHeight,
        tableOptimal: tableOptimal,
        recommendedRange: {
            min: tableMinHeight,
            max: tableMaxHeight,
            optimal: tableOptimal
        },
        // Расчет количества строк (если известна высота строки)
        calculateRows: function(rowHeight) {
            if (!rowHeight || rowHeight <= 0) return null;
            const headerHeight = fixedElements.tableHeader || 0;
            const availableForRows = tableAvailableHeight - headerHeight;
            return {
                min: Math.floor(tableMinHeight / rowHeight),
                max: Math.floor(tableMaxHeight / rowHeight),
                optimal: Math.round(tableOptimal / rowHeight)
            };
        }
    };
}

// ============================================================================
// 3. ИЗМЕРЕНИЕ ЭЛЕМЕНТОВ
// ============================================================================

/**
 * Измерение реальной высоты фиксированных элементов
 * @param {Object} selectors - Объект с CSS селекторами элементов
 * @param {boolean} includeMargins - Включать ли margins в расчет
 * @returns {Object} Объект с измеренными высотами
 */
function measureFixedElements(selectors, includeMargins = true) {
    const measurements = {};
    
    Object.keys(selectors).forEach(key => {
        const selector = selectors[key];
        const element = document.querySelector(selector);
        
        if (element) {
            const computedStyle = window.getComputedStyle(element);
            const offsetHeight = element.offsetHeight;
            
            let totalHeight = offsetHeight;
            
            if (includeMargins) {
                const marginTop = parseFloat(computedStyle.marginTop) || 0;
                const marginBottom = parseFloat(computedStyle.marginBottom) || 0;
                totalHeight += marginTop + marginBottom;
            }
            
            measurements[key] = {
                element: element,
                offsetHeight: offsetHeight,
                clientHeight: element.clientHeight,
                scrollHeight: element.scrollHeight,
                marginTop: parseFloat(computedStyle.marginTop) || 0,
                marginBottom: parseFloat(computedStyle.marginBottom) || 0,
                paddingTop: parseFloat(computedStyle.paddingTop) || 0,
                paddingBottom: parseFloat(computedStyle.paddingBottom) || 0,
                totalHeight: Math.round(totalHeight),
                // Для печати может быть другой размер
                printHeight: null // Будет установлено при необходимости
            };
        } else {
            console.warn(`[table-optimizer] Элемент не найден: ${selector}`);
            measurements[key] = { 
                totalHeight: 0,
                printHeight: 0
            };
        }
    });
    
    return measurements;
}

/**
 * Получение высот элементов с учетом режима (экран/печать)
 * @param {Object} selectors - Объект с CSS селекторами
 * @param {boolean} isPrint - true для печати
 * @returns {Object} Объект с высотами
 */
function getFixedHeightsForMode(selectors, isPrint = false) {
    if (isPrint) {
        // При печати можно использовать фиксированные значения из CSS
        // или измерять после применения print стилей
        const measurements = measureFixedElements(selectors, true);
        
        // Если есть print-специфичные значения, используем их
        Object.keys(measurements).forEach(key => {
            const element = measurements[key].element;
            if (element) {
                // Проверяем, есть ли print-специфичный класс или стиль
                const printClass = element.classList.contains('print-height') ? 
                    element : element.querySelector('.print-height');
                
                if (printClass) {
                    const printStyle = window.getComputedStyle(printClass);
                    measurements[key].printHeight = parseFloat(printStyle.height) || 
                                                   measurements[key].totalHeight;
                } else {
                    measurements[key].printHeight = measurements[key].totalHeight;
                }
            }
        });
        
        return measurements;
    } else {
        // При просмотре измеряем реальные значения
        return measureFixedElements(selectors, true);
    }
}

// ============================================================================
// 4. РАБОТА СО СТРОКАМИ ТАБЛИЦЫ
// ============================================================================

/**
 * Вычисление средней высоты строк
 * @param {NodeList|Array} rows - Коллекция строк таблицы
 * @returns {number} Средняя высота в px
 */
function calculateAverageRowHeight(rows) {
    if (!rows || rows.length === 0) return 32; // Значение по умолчанию
    
    let totalHeight = 0;
    let count = 0;
    
    Array.from(rows).forEach(row => {
        const height = row.offsetHeight;
        if (height > 0) {
            totalHeight += height;
            count++;
        }
    });
    
    return count > 0 ? Math.round(totalHeight / count) : 32;
}

/**
 * Получение максимального индекса строки
 * @param {Element} table - Элемент таблицы
 * @param {string} rowSelector - CSS селектор строк
 * @param {Function} getRowIndexCallback - Функция получения индекса из элемента
 * @returns {number} Максимальный индекс
 */
function getMaxRowIndex(table, rowSelector, getRowIndexCallback = null) {
    const rows = table.querySelectorAll(rowSelector);
    let maxIndex = 0;
    
    rows.forEach(row => {
        const index = getRowIndexCallback ? 
            getRowIndexCallback(row) : 
            parseInt(row.getAttribute('data-row-index')) || 0;
        if (index > maxIndex) {
            maxIndex = index;
        }
    });
    
    return maxIndex;
}

/**
 * Быстрый расчет количества строк
 * @param {number} availableHeight - Доступная высота в px
 * @param {number} rowHeight - Высота одной строки в px
 * @param {number} headerHeight - Высота заголовка таблицы в px
 * @returns {Object} Объект с расчетными значениями
 */
function quickCalculateRows(availableHeight, rowHeight, headerHeight = 0) {
    if (!rowHeight || rowHeight <= 0) {
        return { min: 0, max: 0, optimal: 0 };
    }
    
    const availableForRows = availableHeight - headerHeight;
    const maxRows = Math.floor(availableForRows / rowHeight);
    const minRows = Math.floor(availableForRows * 0.9 / rowHeight);
    const optimalRows = Math.round((minRows + maxRows) / 2);
    
    return {
        min: Math.max(0, minRows),
        max: Math.max(0, maxRows),
        optimal: Math.max(0, optimalRows)
    };
}

// ============================================================================
// 5. УНИВЕРСАЛЬНАЯ ФУНКЦИЯ ОПТИМИЗАЦИИ
// ============================================================================

/**
 * Универсальная функция оптимизации таблицы для просмотра и печати
 * @param {Object} config - Конфигурация таблицы
 * @returns {Object} Результаты оптимизации
 */
function optimizeTableForViewAndPrint(config) {
    const {
        tableSelector,           // Селектор таблицы
        orientation = 'portrait', // 'portrait' или 'landscape'
        fixedElementsSelectors,   // Селекторы фиксированных элементов
        rowSelector,              // Селектор строк таблицы
        addRowCallback,           // Функция добавления строки
        removeRowCallback,        // Функция удаления строки
        getRowIndexCallback,     // Функция получения индекса строки
        padding = 20,            // Отступ между элементами
        minRows = 0,             // Минимальное количество строк
        maxRows = null,          // Максимальное количество строк
        onComplete = null        // Callback после завершения
    } = config;
    
    // Проверка обязательных параметров
    if (!tableSelector || !fixedElementsSelectors || !rowSelector) {
        console.error('[table-optimizer] Не указаны обязательные параметры');
        return null;
    }
    
    // 1. Измеряем фиксированные элементы
    const fixedHeights = measureFixedElements(fixedElementsSelectors);
    
    // Подготавливаем объект с высотами для расчета
    const fixedHeightsForCalc = {};
    Object.keys(fixedHeights).forEach(key => {
        fixedHeightsForCalc[key] = fixedHeights[key].totalHeight || 0;
    });
    
    // 2. Рассчитываем доступное пространство для экрана
    const screenSpace = calculateTableHeight(
        fixedHeightsForCalc,
        orientation,
        false, // для экрана
        padding
    );
    
    // 3. Рассчитываем доступное пространство для печати
    const printSpace = calculateTableHeight(
        fixedHeightsForCalc,
        orientation,
        true, // для печати
        padding
    );
    
    // 4. Получаем таблицу
    const table = document.querySelector(tableSelector);
    if (!table) {
        console.error(`[table-optimizer] Таблица не найдена: ${tableSelector}`);
        return null;
    }
    
    // 5. Вычисляем среднюю высоту строки
    const existingRows = table.querySelectorAll(rowSelector);
    const avgRowHeight = calculateAverageRowHeight(Array.from(existingRows));
    
    // 6. Настраиваем таблицу для экрана
    let screenResult = null;
    if (typeof adjustTableHeightToRange !== 'undefined' && addRowCallback && removeRowCallback) {
        screenResult = adjustTableHeightToRange({
            min_height_tab: screenSpace.tableMinHeight,
            max_height_tab: screenSpace.tableMaxHeight,
            tab_name: tableSelector,
            row_height: avgRowHeight,
            row_selector: rowSelector,
            header_height: fixedHeightsForCalc.tableHeader || 0,
            addRowCallback: addRowCallback,
            removeRowCallback: removeRowCallback,
            getRowIndexCallback: getRowIndexCallback,
            onComplete: function(currentHeight, rowCount) {
                console.log(`[table-optimizer] Экран: высота ${currentHeight}px, строк ${rowCount}`);
                
                // Сохраняем количество строк для печати
                window._tableRowCount = rowCount;
                window._tableAvgRowHeight = avgRowHeight;
                
                if (onComplete) {
                    onComplete({
                        mode: 'screen',
                        height: currentHeight,
                        rowCount: rowCount
                    });
                }
            }
        });
    } else {
        console.warn('[table-optimizer] adjustTableHeightToRange не найдена или не указаны callbacks');
    }
    
    // 7. Настраиваем таблицу для печати (при событии печати)
    const printHandler = function() {
        if (typeof adjustTableHeightToRange !== 'undefined' && addRowCallback && removeRowCallback) {
            // Используем сохраненное количество строк или пересчитываем
            const targetRowCount = window._tableRowCount || 
                Math.floor(printSpace.recommendedRange.optimal / avgRowHeight);
            
            // Удаляем лишние строки или добавляем недостающие
            const currentRows = table.querySelectorAll(rowSelector);
            const currentRowCount = currentRows.length;
            
            if (currentRowCount < targetRowCount) {
                // Добавляем строки
                for (let i = currentRowCount; i < targetRowCount; i++) {
                    const maxIndex = getMaxRowIndex(table, rowSelector, getRowIndexCallback);
                    addRowCallback(maxIndex + 1, table);
                }
            } else if (currentRowCount > targetRowCount) {
                // Удаляем строки
                for (let i = currentRowCount; i > targetRowCount; i--) {
                    const maxIndex = getMaxRowIndex(table, rowSelector, getRowIndexCallback);
                    if (maxIndex > 0) {
                        removeRowCallback(maxIndex, table);
                    }
                }
            }
            
            console.log(`[table-optimizer] Печать: настроено ${targetRowCount} строк`);
        }
    };
    
    window.addEventListener('beforeprint', printHandler);
    
    // 8. Восстанавливаем после печати (опционально)
    window.addEventListener('afterprint', function() {
        console.log('[table-optimizer] Печать завершена');
    });
    
    // Возвращаем результаты
    return {
        screen: screenSpace,
        print: printSpace,
        avgRowHeight: avgRowHeight,
        fixedHeights: fixedHeights,
        screenResult: screenResult,
        // Расчет количества строк
        rows: {
            screen: screenSpace.calculateRows(avgRowHeight),
            print: printSpace.calculateRows(avgRowHeight)
        }
    };
}

// ============================================================================
// 6. ЭКСПОРТ ФУНКЦИЙ
// ============================================================================

// Экспорт для использования в других модулях (если используется модульная система)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LETTER_PAGE,
        PAGE_MARGINS,
        calculateAvailableSpace,
        calculateTableHeight,
        measureFixedElements,
        getFixedHeightsForMode,
        calculateAverageRowHeight,
        getMaxRowIndex,
        quickCalculateRows,
        optimizeTableForViewAndPrint
    };
}

// Глобальный экспорт для использования в браузере
if (typeof window !== 'undefined') {
    window.TableOptimizer = {
        LETTER_PAGE,
        PAGE_MARGINS,
        calculateAvailableSpace,
        calculateTableHeight,
        measureFixedElements,
        getFixedHeightsForMode,
        calculateAverageRowHeight,
        getMaxRowIndex,
        quickCalculateRows,
        optimizeTableForViewAndPrint
    };
}



