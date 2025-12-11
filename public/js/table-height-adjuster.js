/**
 * Универсальная функция для автоматической настройки высоты таблицы
 * путем добавления/удаления строк до достижения целевой высоты
 * 
 * @param {Object} options - Объект с параметрами настройки
 * @param {number} options.min_height_tab - Минимальная высота таблицы в пикселях
 * @param {number} options.max_height_tab - Максимальная высота таблицы в пикселях
 * @param {string|Element} options.tab_name - CSS селектор таблицы (например, '.parent', '#myTable') или DOM-элемент (для многостраничных форм)
 * @param {number} options.row_height - Высота одной строки в пикселях (примерная, для расчетов)
 * @param {string} options.row_selector - CSS селектор для строк таблицы (например, '.data-row[data-row-index]')
 * @param {Function} options.addRowCallback - Функция для добавления новой строки (rowIndex, tableElement)
 * @param {Function} options.removeRowCallback - Функция для удаления строки (rowIndex, tableElement)
 * @param {Function} options.getRowIndexCallback - Функция для получения индекса строки из элемента (rowElement) -> number
 * @param {number} options.max_iterations - Максимальное количество итераций (по умолчанию 50)
 * @param {number} options.header_height - Высота заголовка таблицы в пикселях (по умолчанию 0)
 * @param {Function} options.onComplete - Callback функция, вызываемая после завершения настройки (currentHeight, rowCount)
 * 
 * @returns {Object} Объект с результатами: { success: boolean, currentHeight: number, rowCount: number, targetRows: {min, max, avg}, message: string }
 */
function adjustTableHeightToRange(options) {
    // Проверка обязательных параметров
    if (!options.min_height_tab || !options.max_height_tab || !options.tab_name) {
        console.error('adjustTableHeightToRange: Не указаны обязательные параметры (min_height_tab, max_height_tab, tab_name)');
        return {
            success: false,
            currentHeight: 0,
            rowCount: 0,
            message: 'Не указаны обязательные параметры'
        };
    }

    // Поддержка как селектора (строка), так и DOM-элемента
    let table;
    if (typeof options.tab_name === 'string') {
        table = document.querySelector(options.tab_name);
    } else if (options.tab_name instanceof Element || options.tab_name instanceof HTMLElement) {
        table = options.tab_name;
    } else {
        console.error(`adjustTableHeightToRange: Неверный тип параметра tab_name. Ожидается строка (селектор) или DOM-элемент`);
        return {
            success: false,
            currentHeight: 0,
            rowCount: 0,
            message: `Неверный тип параметра tab_name`
        };
    }
    
    if (!table) {
        const tabNameStr = typeof options.tab_name === 'string' ? options.tab_name : 'DOM-элемент';
        console.error(`adjustTableHeightToRange: Таблица "${tabNameStr}" не найдена`);
        return {
            success: false,
            currentHeight: 0,
            rowCount: 0,
            message: `Таблица "${tabNameStr}" не найдена`
        };
    }

    const MIN_HEIGHT = options.min_height_tab;
    const MAX_HEIGHT = options.max_height_tab;
    const MAX_ITERATIONS = options.max_iterations || 50;
    const rowSelector = options.row_selector || '[data-row-index]';
    
    // Вспомогательные функции для работы со строками
    function getCurrentRowCount() {
        if (!options.row_selector) {
            // Если не указан селектор, пытаемся найти строки внутри таблицы
            const rows = table.querySelectorAll(rowSelector);
            const rowIndices = new Set();
            rows.forEach(row => {
                let index;
                if (options.getRowIndexCallback) {
                    index = options.getRowIndexCallback(row);
                } else {
                    index = parseInt(row.getAttribute('data-row-index'));
                }
                if (!isNaN(index) && index > 0) {
                    rowIndices.add(index);
                }
            });
            return rowIndices.size;
        } else {
            const rows = table.querySelectorAll(options.row_selector);
            const rowIndices = new Set();
            rows.forEach(row => {
                let index;
                if (options.getRowIndexCallback) {
                    index = options.getRowIndexCallback(row);
                } else {
                    index = parseInt(row.getAttribute('data-row-index'));
                }
                if (!isNaN(index) && index > 0) {
                    rowIndices.add(index);
                }
            });
            return rowIndices.size;
        }
    }

    function getMaxRowIndex() {
        const rows = table.querySelectorAll(rowSelector);
        let maxIndex = 0;
        rows.forEach(row => {
            let index;
            if (options.getRowIndexCallback) {
                index = options.getRowIndexCallback(row);
            } else {
                index = parseInt(row.getAttribute('data-row-index'));
            }
            if (!isNaN(index) && index > maxIndex) {
                maxIndex = index;
            }
        });
        return maxIndex;
    }

    function addRow(rowIndex) {
        if (options.addRowCallback && typeof options.addRowCallback === 'function') {
            options.addRowCallback(rowIndex, table);
        } else {
            console.warn('adjustTableHeightToRange: Функция addRowCallback не указана, невозможно добавить строку');
        }
    }

    function removeRow(rowIndex) {
        if (options.removeRowCallback && typeof options.removeRowCallback === 'function') {
            options.removeRowCallback(rowIndex, table);
        } else {
            // Попытка удалить строку по умолчанию
            const rows = table.querySelectorAll(`${rowSelector}[data-row-index="${rowIndex}"]`);
            rows.forEach(row => row.remove());
        }
    }

    // Получаем высоту заголовка таблицы (если есть)
    const headerHeight = options.header_height || 0;
    let rowHeight = options.row_height || 34;
    
    // Функция для динамического пересчета средней высоты строк
    function recalculateAverageRowHeight() {
        const rows = table.querySelectorAll(rowSelector);
        if (rows.length === 0) return rowHeight;
        
        let totalHeight = 0;
        let count = 0;
        rows.forEach(row => {
            const height = row.offsetHeight;
            if (height > 0) {
                totalHeight += height;
                count++;
            }
        });
        
        if (count > 0) {
            const newAvgHeight = Math.round(totalHeight / count);
            // Обновляем rowHeight только если разница значительна (более 2px)
            if (Math.abs(newAvgHeight - rowHeight) > 2) {
                console.log(`adjustTableHeightToRange: Обновлена средняя высота строки с ${rowHeight}px на ${newAvgHeight}px`);
                rowHeight = newAvgHeight;
            }
        }
        return rowHeight;
    }
    
    // Рассчитываем целевое количество строк на основе диапазона высот
    const availableMinHeight = MIN_HEIGHT - headerHeight;
    const availableMaxHeight = MAX_HEIGHT - headerHeight;
    let targetMinRows = Math.floor(availableMinHeight / rowHeight);
    let targetMaxRows = Math.floor(availableMaxHeight / rowHeight);
    let targetAvgRows = Math.round((targetMinRows + targetMaxRows) / 2);
    
    // Основной цикл настройки высоты
    let iterations = 0;
    let lastHeight = 0;
    let stableCount = 0; // Счетчик стабильных итераций (когда высота не меняется)
    let lastRowCount = 0;

    while (iterations < MAX_ITERATIONS) {
        const currentHeight = table.offsetHeight;
        const currentRowCount = getCurrentRowCount();
        
        // Пересчитываем среднюю высоту строк каждые 3 итерации для более точного расчета
        if (iterations > 0 && iterations % 3 === 0 && currentRowCount > 0) {
            const recalculatedHeight = recalculateAverageRowHeight();
            // Пересчитываем целевое количество строк с учетом новой высоты
            targetMinRows = Math.floor(availableMinHeight / recalculatedHeight);
            targetMaxRows = Math.floor(availableMaxHeight / recalculatedHeight);
            targetAvgRows = Math.round((targetMinRows + targetMaxRows) / 2);
        }
        
        // Проверка на стабильность (если высота и количество строк не меняются)
        if (currentHeight === lastHeight && currentRowCount === lastRowCount) {
            stableCount++;
            if (stableCount > 3) {
                console.warn('adjustTableHeightToRange: Высота таблицы стабилизировалась, но не в целевом диапазоне');
                break;
            }
        } else {
            stableCount = 0;
        }
        lastHeight = currentHeight;
        lastRowCount = currentRowCount;

        // Проверка достижения целевого диапазона
        if (currentHeight >= MIN_HEIGHT && currentHeight <= MAX_HEIGHT) {
            const rowCount = getCurrentRowCount();
            const result = {
                success: true,
                currentHeight: currentHeight,
                rowCount: rowCount,
                targetRows: { min: targetMinRows, max: targetMaxRows, avg: targetAvgRows },
                message: `Высота таблицы настроена: ${currentHeight}px (целевой диапазон: ${MIN_HEIGHT}-${MAX_HEIGHT}px), строк: ${rowCount} (целевой диапазон: ${targetMinRows}-${targetMaxRows})`
            };
            
            console.log(result.message);
            console.log(`✓ Целевой диапазон достигнут за ${iterations} итераций`);
            
            if (options.onComplete && typeof options.onComplete === 'function') {
                options.onComplete(currentHeight, rowCount);
            }
            
            return result;
        }
        
        // Дополнительная отладочная информация
        if (iterations % 5 === 0) {
            console.log(`Итерация ${iterations}: высота=${currentHeight}px (цель: ${MIN_HEIGHT}-${MAX_HEIGHT}px), строк=${currentRowCount} (цель: ${targetMinRows}-${targetMaxRows})`);
        }

        // Улучшенная логика корректировки высоты с учетом целевого количества строк
        if (currentHeight < MIN_HEIGHT) {
            // Высота меньше минимума - добавляем строки
            console.log(`Итерация ${iterations}: Высота ${currentHeight}px < минимум ${MIN_HEIGHT}px, нужно добавить строки`);
            console.log(`Текущее количество строк: ${currentRowCount}, целевое минимальное: ${targetMinRows}, максимальное: ${targetMaxRows}`);
            
            // Если текущее количество строк меньше минимального целевого, добавляем несколько строк сразу
            if (currentRowCount < targetMinRows) {
                const rowsToAdd = Math.min(targetMinRows - currentRowCount, 3); // Добавляем до 3 строк за раз
                console.log(`Добавляем ${rowsToAdd} строк(и) для достижения минимального целевого количества`);
                for (let i = 0; i < rowsToAdd; i++) {
                    const maxIndex = getMaxRowIndex();
                    addRow(maxIndex + 1);
                }
            } else {
                // Если уже близко к целевому, добавляем по одной строке
                console.log(`Добавляем 1 строку (уже близко к целевому)`);
                const maxIndex = getMaxRowIndex();
                addRow(maxIndex + 1);
            }
        } else if (currentHeight > MAX_HEIGHT) {
            // Высота больше максимума - удаляем строки
            // Если текущее количество строк больше максимального целевого, удаляем несколько строк сразу
            if (currentRowCount > targetMaxRows) {
                const rowsToRemove = Math.min(currentRowCount - targetMaxRows, 3); // Удаляем до 3 строк за раз
                for (let i = 0; i < rowsToRemove; i++) {
                    const maxIndex = getMaxRowIndex();
                    if (maxIndex > 0) {
                        removeRow(maxIndex);
                    } else {
                        break;
                    }
                }
            } else {
                // Если уже близко к целевому, удаляем по одной строке
                const maxIndex = getMaxRowIndex();
                if (maxIndex > 0) {
                    removeRow(maxIndex);
                } else {
                    console.warn('adjustTableHeightToRange: Нельзя удалить все строки');
                    break;
                }
            }
        }

        iterations++;
    }

    // Если не удалось достичь целевого диапазона
    const finalHeight = table.offsetHeight;
    const finalRowCount = getCurrentRowCount();
    const result = {
        success: iterations >= MAX_ITERATIONS,
        currentHeight: finalHeight,
        rowCount: finalRowCount,
        targetRows: { min: targetMinRows, max: targetMaxRows, avg: targetAvgRows },
        message: iterations >= MAX_ITERATIONS 
            ? `Достигнуто максимальное количество итераций. Текущая высота: ${finalHeight}px, строк: ${finalRowCount} (целевой диапазон: ${targetMinRows}-${targetMaxRows})`
            : `Настройка завершена. Текущая высота: ${finalHeight}px, строк: ${finalRowCount} (целевой диапазон: ${targetMinRows}-${targetMaxRows})`
    };

    if (iterations >= MAX_ITERATIONS) {
        console.warn(result.message);
    } else {
        console.log(result.message);
    }

    if (options.onComplete && typeof options.onComplete === 'function') {
        options.onComplete(finalHeight, finalRowCount);
    }

    return result;
}

/**
 * Функция для определения максимального количества строк таблицы
 * на основе целевой высоты и высоты одной строки
 * 
 * @param {number} min_height_tab - Минимальная высота таблицы в пикселях
 * @param {number} max_height_tab - Максимальная высота таблицы в пикселях
 * @param {number} row_height - Высота одной строки в пикселях
 * @param {number} header_height - Высота заголовка таблицы в пикселях (по умолчанию 0)
 * 
 * @returns {Object} Объект с расчетными значениями: { minRows: number, maxRows: number, avgRows: number, targetHeight: number }
 */
function calculateMaxTableRows(min_height_tab, max_height_tab, row_height, header_height = 0) {
    if (!min_height_tab || !max_height_tab || !row_height || row_height <= 0) {
        console.error('calculateMaxTableRows: Неверные параметры');
        return { minRows: 0, maxRows: 0, avgRows: 0, targetHeight: 0 };
    }

    const availableMinHeight = min_height_tab - header_height;
    const availableMaxHeight = max_height_tab - header_height;

    const minRows = Math.floor(availableMinHeight / row_height);
    const maxRows = Math.floor(availableMaxHeight / row_height);
    const avgRows = Math.round((minRows + maxRows) / 2);
    const targetHeight = Math.round((min_height_tab + max_height_tab) / 2);

    return {
        minRows: Math.max(0, minRows),
        maxRows: Math.max(0, maxRows),
        avgRows: Math.max(0, avgRows),
        targetHeight: targetHeight
    };
}

/**
 * Функция для расчета количества строк на основе текущей высоты таблицы и целевого диапазона
 * Полезно для предварительного расчета перед применением adjustTableHeightToRange
 * 
 * @param {string|Element} tab_name - CSS селектор таблицы или DOM-элемент
 * @param {number} min_height_tab - Минимальная высота таблицы в пикселях
 * @param {number} max_height_tab - Максимальная высота таблицы в пикселях
 * @param {number} row_height - Высота одной строки в пикселях
 * @param {number} header_height - Высота заголовка таблицы в пикселях (по умолчанию 0)
 * 
 * @returns {Object} Объект с расчетными значениями и рекомендациями
 */
function calculateRowsFromHeightRange(tab_name, min_height_tab, max_height_tab, row_height, header_height = 0) {
    // Поддержка как селектора (строка), так и DOM-элемента
    let table;
    if (typeof tab_name === 'string') {
        table = document.querySelector(tab_name);
    } else if (tab_name instanceof Element || tab_name instanceof HTMLElement) {
        table = tab_name;
    } else {
        console.error(`calculateRowsFromHeightRange: Неверный тип параметра tab_name. Ожидается строка (селектор) или DOM-элемент`);
        return null;
    }
    
    if (!table) {
        const tabNameStr = typeof tab_name === 'string' ? tab_name : 'DOM-элемент';
        console.error(`calculateRowsFromHeightRange: Таблица "${tabNameStr}" не найдена`);
        return null;
    }

    const currentHeight = table.offsetHeight;
    const availableMinHeight = min_height_tab - header_height;
    const availableMaxHeight = max_height_tab - header_height;
    
    const currentRows = Math.floor((currentHeight - header_height) / row_height);
    const targetMinRows = Math.floor(availableMinHeight / row_height);
    const targetMaxRows = Math.floor(availableMaxHeight / row_height);
    const targetAvgRows = Math.round((targetMinRows + targetMaxRows) / 2);
    
    let action = 'none';
    let rowsToAdd = 0;
    let rowsToRemove = 0;
    
    if (currentHeight < min_height_tab) {
        action = 'add';
        rowsToAdd = Math.max(1, targetMinRows - currentRows);
    } else if (currentHeight > max_height_tab) {
        action = 'remove';
        rowsToRemove = Math.max(1, currentRows - targetMaxRows);
    }

    return {
        currentHeight: currentHeight,
        currentRows: currentRows,
        targetRows: {
            min: targetMinRows,
            max: targetMaxRows,
            avg: targetAvgRows
        },
        action: action,
        rowsToAdd: rowsToAdd,
        rowsToRemove: rowsToRemove,
        recommendation: action === 'add' 
            ? `Добавить ${rowsToAdd} строк(и) для достижения целевого диапазона`
            : action === 'remove'
            ? `Удалить ${rowsToRemove} строк(и) для достижения целевого диапазона`
            : 'Количество строк соответствует целевому диапазону'
    };
}

// Экспорт функций для использования в других модулях (если используется модульная система)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        adjustTableHeightToRange,
        calculateMaxTableRows,
        calculateRowsFromHeightRange
    };
}

