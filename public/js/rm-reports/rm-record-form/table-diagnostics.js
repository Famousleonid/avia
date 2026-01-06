/**
 * RmRecordTableDiagnostics - модуль для диагностики и получения информации о таблицах
 */
class RmRecordTableDiagnostics {
    /**
     * Определяет общую высоту двух основных таблиц
     * @returns {Object|null} Объект с высотами таблиц или null если таблицы не найдены
     */
    static getTablesHeight() {
        // Находим первую таблицу (блок с классом "parent")
        const table1 = document.querySelector('.parent');
        // Находим вторую таблицу (блок с классом "qc_stamp")
        const table2 = document.querySelector('.qc_stamp');

        if (!table1 || !table2) {
            console.error('Таблицы не найдены');
            return null;
        }

        // Получаем высоту каждой таблицы в пикселях
        const height1 = table1.offsetHeight;
        const height2 = table2.offsetHeight;

        // Вычисляем общую высоту
        const totalHeight = height1 + height2;

        // Также учитываем отступ между таблицами (mt-1 = margin-top)
        const marginTop = parseInt(window.getComputedStyle(table2).marginTop) || 0;
        const totalHeightWithMargin = totalHeight + marginTop;

        // Получаем информацию о высотах строк
        const rowHeightInfo = RmRecordHeightAnalyzer.getRowHeightStatistics();

        return {
            table1Height: height1,
            table2Height: height2,
            marginBetween: marginTop,
            totalHeight: totalHeightWithMargin,
            rowHeightInfo: rowHeightInfo
        };
    }

    /**
     * Выводит детальную диагностическую информацию в консоль
     * @param {Object} analysis - Результат анализа высоты таблицы
     */
    static logDetailedAnalysis(analysis) {
        if (!analysis) {
            console.warn('Анализ не предоставлен');
            return;
        }

        console.log('--- Анализ после настройки ---');
        console.log('Фактическая высота таблицы:', analysis.actualTableHeight + 'px');
        console.log('Высота заголовка:', analysis.headerHeight + 'px');
        console.log('Количество строк:', analysis.rowCount);

        // Информация о высотах строк
        if (analysis.hasVariableRowHeights) {
            console.warn(`⚠️ Строки имеют РАЗНУЮ высоту!`);
            console.log('  - Минимальная высота строки:', analysis.rowStats.min + 'px');
            console.log('  - Максимальная высота строки:', analysis.rowStats.max + 'px');
            console.log('  - Средняя высота строки:', analysis.rowStats.avg + 'px');
            console.log('  - Разница:', analysis.rowsHeightDifference + 'px');
            console.log('  - Реальная сумма высот всех строк:', analysis.totalRowsHeight + 'px');
            console.log('  - Если бы все строки были средней высоты:', (analysis.rowCount * analysis.rowStats.avg) + 'px');
            const difference = Math.abs(analysis.totalRowsHeight - (analysis.rowCount * analysis.rowStats.avg));
            if (difference > 5) {
                console.warn(`  ⚠️ Расхождение: ${difference}px (используется реальная сумма высот)`);
            }
        } else {
            console.log('✅ Все строки имеют одинаковую высоту:', analysis.rowStats.avg + 'px');
        }

        console.log('Расчетная высота (заголовок + реальная сумма высот строк):', analysis.calculatedHeight + 'px');
        console.log('Расчетный диапазон (мин/макс высоты):', analysis.calculatedHeightMin + 'px - ' + analysis.calculatedHeightMax + 'px');
        console.log('Целевой диапазон:', analysis.targetMinHeight + 'px - ' + analysis.targetMaxHeight + 'px');
        console.log('В целевом диапазоне:', analysis.isInRange ? '✅ ДА' : '❌ НЕТ');

        if (!analysis.isInRange) {
            console.warn(`⚠️ Высота таблицы вне целевого диапазона! Отклонение: ${analysis.difference}px`);
            if (analysis.actualTableHeight < analysis.targetMinHeight) {
                const avgHeightForEstimation = analysis.hasVariableRowHeights
                    ? analysis.rowStats.avg
                    : analysis.rowStats.avg;
                const rowsToAdd = Math.ceil(analysis.difference / avgHeightForEstimation);
                console.warn(`   Нужно добавить примерно ${rowsToAdd} строк(и)`);
                if (analysis.hasVariableRowHeights) {
                    console.log(`   (оценка основана на средней высоте ${avgHeightForEstimation}px, реальные строки могут быть выше/ниже)`);
                }
            } else {
                const avgHeightForEstimation = analysis.hasVariableRowHeights
                    ? analysis.rowStats.avg
                    : analysis.rowStats.avg;
                const rowsToRemove = Math.ceil(analysis.difference / avgHeightForEstimation);
                console.warn(`   Нужно удалить примерно ${rowsToRemove} строк(и)`);
                if (analysis.hasVariableRowHeights) {
                    console.log(`   (оценка основана на средней высоте ${avgHeightForEstimation}px, реальные строки могут быть выше/ниже)`);
                }
            }
        } else {
            console.log('✅ Высота таблицы соответствует целевому диапазону!');
            if (analysis.hasVariableRowHeights) {
                console.log('   ✅ Учтена разная высота строк при расчетах');
            }
        }

        // Проверка расчетов
        const heightDifference = Math.abs(analysis.actualTableHeight - analysis.calculatedHeight);
        if (heightDifference > 5) {
            console.warn(`⚠️ Расхождение между фактической и расчетной высотой: ${heightDifference}px`);
            console.warn(`   Это может быть связано с отступами, границами или другими CSS свойствами`);
            console.log('   Проверьте CSS свойства таблицы: padding, margin, border, gap');

            // Дополнительная диагностика
            if (analysis.hasVariableRowHeights) {
                console.log('   Примечание: расчеты учитывают реальную сумму высот всех строк');
                console.log('   (не среднюю высоту * количество, так как строки разной высоты)');
            }
        } else {
            console.log(`✅ Расчеты точны (расхождение: ${heightDifference}px)`);
            if (analysis.hasVariableRowHeights) {
                console.log('   ✅ Учтена разная высота строк (использована реальная сумма высот)');
            }
        }

        // Дополнительная информация
        console.log('--- Дополнительная информация ---');
        console.log('Доступная высота для строк (мин):', analysis.availableMinHeight + 'px');
        console.log('Доступная высота для строк (макс):', analysis.availableMaxHeight + 'px');
        console.log('Целевое количество строк (мин):', analysis.targetMinRows);
        console.log('Целевое количество строк (макс):', analysis.targetMaxRows);
        console.log('Дополнительная высота таблицы (padding + border):', analysis.tableExtraHeight + 'px');
        if (analysis.tableExtraHeight > 0) {
            console.log('  - Padding top:', analysis.cssProperties.paddingTop + 'px');
            console.log('  - Padding bottom:', analysis.cssProperties.paddingBottom + 'px');
            console.log('  - Border top:', analysis.cssProperties.borderTop + 'px');
            console.log('  - Border bottom:', analysis.cssProperties.borderBottom + 'px');
        }
    }

    /**
     * Выводит информацию о высотах таблиц
     */
    static logTablesHeight() {
        const heights = this.getTablesHeight();
        if (heights) {
            console.log('Высота первой таблицы (.parent):', heights.table1Height + 'px');
            console.log('Высота второй таблицы (.qc_stamp):', heights.table2Height + 'px');
            console.log('Отступ между таблицами:', heights.marginBetween + 'px');
            console.log('Общая высота двух таблиц:', heights.totalHeight + 'px');

            // Выводим информацию о высотах строк
            if (heights.rowHeightInfo && heights.rowHeightInfo.count > 0) {
                console.log('--- Статистика по высотам строк ---');
                console.log('Количество строк:', heights.rowHeightInfo.count);
                console.log('Минимальная высота строки:', heights.rowHeightInfo.min + 'px');
                console.log('Максимальная высота строки:', heights.rowHeightInfo.max + 'px');
                console.log('Средняя высота строки:', heights.rowHeightInfo.avg + 'px');
                if (heights.rowHeightInfo.max !== heights.rowHeightInfo.min) {
                    console.warn('⚠️ ВНИМАНИЕ: Строки имеют разную высоту! Разница:', (heights.rowHeightInfo.max - heights.rowHeightInfo.min) + 'px');
                }
            }
        }
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RmRecordTableDiagnostics;
}


