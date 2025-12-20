/**
 * RmRecordTableHeightManager - модуль для настройки высоты таблицы
 * Использует детальный анализ и отслеживание изменений высоты строк
 * Может использовать базовые функции из HeightCalculator для общих вычислений
 */
class RmRecordTableHeightManager {
    /**
     * Автоматическая настройка высоты таблицы
     * @returns {Object} Результат настройки: {success, message}
     */
    static adjustTableHeight() {
        // Сначала измеряем реальную высоту строк и заголовка
        let actualRowHeight = RmRecordHeightAnalyzer.getActualRowHeight();
        const headerHeight = RmRecordHeightAnalyzer.getHeaderHeight();

        console.log('=== Начальные измерения ===');
        console.log('Измеренная высота строки:', actualRowHeight + 'px');
        console.log('Высота заголовка таблицы:', headerHeight + 'px');

        // Проверка целостности строк
        const integrityCheck = RmRecordTableIntegrityValidator.validateRowIntegrity();
        if (!integrityCheck.isValid) {
            console.warn('⚠️ Обнаружены проблемы с целостностью строк:');
            integrityCheck.issues.forEach(issue => console.warn('  -', issue));
        } else {
            console.log('✅ Целостность строк проверена: все строки имеют по 7 ячеек');
        }

        // Детальный анализ перед настройкой
        const initialAnalysis = RmRecordHeightAnalyzer.analyzeTableHeightCalculations();
        if (initialAnalysis) {
            console.log('--- Анализ до настройки ---');
            console.log('Текущая высота таблицы:', initialAnalysis.actualTableHeight + 'px');
            console.log('Высота заголовка:', initialAnalysis.headerHeight + 'px');
            console.log('Количество строк:', initialAnalysis.rowCount);

            // Информация о высотах строк
            if (initialAnalysis.hasVariableRowHeights) {
                console.warn(`⚠️ Обнаружены строки с РАЗНОЙ высотой!`);
                console.log('  - Минимальная:', initialAnalysis.rowStats.min + 'px');
                console.log('  - Максимальная:', initialAnalysis.rowStats.max + 'px');
                console.log('  - Средняя:', initialAnalysis.rowStats.avg + 'px');
                console.log('  - Разница:', initialAnalysis.rowsHeightDifference + 'px');
                console.log('  - Реальная сумма высот всех строк:', initialAnalysis.totalRowsHeight + 'px');
                console.log('  ✅ Расчеты используют РЕАЛЬНУЮ сумму высот, а не среднюю * количество');
            } else {
                console.log('Высота строки (все одинаковые):', initialAnalysis.rowStats.avg + 'px');
            }

            console.log('Целевой диапазон:', initialAnalysis.targetMinHeight + 'px - ' + initialAnalysis.targetMaxHeight + 'px');
            console.log('Целевое количество строк:', initialAnalysis.targetMinRows + ' - ' + initialAnalysis.targetMaxRows);
            console.log('В диапазоне:', initialAnalysis.isInRange ? 'ДА' : 'НЕТ');
            if (!initialAnalysis.isInRange) {
                console.log('Отклонение:', initialAnalysis.difference + 'px');
            }
            if (initialAnalysis.tableExtraHeight > 0) {
                console.log('Дополнительная высота (padding + border):', initialAnalysis.tableExtraHeight + 'px');
            }
        }

        // Переменная для отслеживания изменений высоты строк
        let lastRowHeight = actualRowHeight;
        let iterationCount = 0;

        // Проверяем, загружена ли функция adjustTableHeightToRange
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('❌ Функция adjustTableHeightToRange не найдена! Убедитесь, что скрипт table-height-adjuster.js загружен.');
            return {
                success: false,
                message: 'Функция adjustTableHeightToRange не загружена'
            };
        }

        // Используем универсальную функцию adjustTableHeightToRange
        const result = adjustTableHeightToRange({
            min_height_tab: 593,
            max_height_tab: 640,
            tab_name: '.parent',
            row_height: actualRowHeight, // Используем реальную высоту строки
            header_height: headerHeight, // Учитываем высоту заголовка
            row_selector: '.data-row[data-row-index]',
            addRowCallback: function(rowIndex, tableElement) {
                RmRecordRowManager.addEmptyRow(rowIndex);
                iterationCount++;

                // После добавления строки даем время на отрисовку и пересчитываем высоту
                // Используем requestAnimationFrame для более точного измерения
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        const newRowHeight = RmRecordHeightAnalyzer.getActualRowHeight();
                        if (Math.abs(newRowHeight - lastRowHeight) > 3) {
                            console.log(`[Итерация ${iterationCount}] Высота строки изменилась: ${lastRowHeight}px → ${newRowHeight}px`);
                            lastRowHeight = newRowHeight;
                        }
                    }, 50); // Небольшая задержка для полной отрисовки
                });
            },
            removeRowCallback: function(rowIndex, tableElement) {
                RmRecordRowManager.removeRow(rowIndex);
                iterationCount++;
            },
            getRowIndexCallback: function(rowElement) {
                return RmRecordRowManager.getRowIndex(rowElement);
            },
            max_iterations: 50,
            onComplete: function(currentHeight, rowCount) {
                // Финальный пересчет высоты строки после завершения настройки
                setTimeout(() => {
                    const finalRowHeight = RmRecordHeightAnalyzer.getActualRowHeight();
                    const rowStats = RmRecordHeightAnalyzer.getRowHeightStatistics();
                    const finalAnalysis = RmRecordHeightAnalyzer.analyzeTableHeightCalculations();

                    console.log(`=== Настройка завершена ===`);
                    console.log(`Высота таблицы: ${currentHeight}px`);
                    console.log(`Количество строк: ${rowCount}`);
                    console.log(`Средняя высота строки: ${finalRowHeight}px`);

                    if (rowStats && rowStats.count > 0) {
                        console.log(`Диапазон высот строк: ${rowStats.min}px - ${rowStats.max}px`);
                        if (rowStats.max !== rowStats.min) {
                            console.warn(`⚠️ Обнаружены строки с разной высотой (разница: ${rowStats.max - rowStats.min}px)`);
                        }
                    }

                    // Проверка целостности строк после настройки
                    const finalIntegrityCheck = RmRecordTableIntegrityValidator.validateRowIntegrity();
                    if (!finalIntegrityCheck.isValid) {
                        console.warn('⚠️ После настройки обнаружены проблемы с целостностью строк:');
                        finalIntegrityCheck.issues.forEach(issue => console.warn('  -', issue));
                    }

                    // Детальный анализ после настройки
                    if (finalAnalysis) {
                        RmRecordTableDiagnostics.logDetailedAnalysis(finalAnalysis);
                    }
                }, 100);
            }
        });

        return result;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RmRecordTableHeightManager;
}

