/**
 * ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ УНИВЕРСАЛЬНОЙ ФУНКЦИИ adjustTableHeightToRange
 * 
 * Скопируйте нужный пример в ваш Blade-файл или JS-файл
 */

// ============================================
// ПРИМЕР 1: Простое использование с базовыми параметрами
// ============================================
function example1() {
    adjustTableHeightToRange({
        min_height_tab: 593,
        max_height_tab: 639,
        tab_name: '.my-table',
        row_height: 37
    });
}

// ============================================
// ПРИМЕР 2: Полное использование с кастомными функциями
// ============================================
function example2() {
    // Данные для заполнения строк (если нужно)
    const tableData = [
        { col1: 'Value 1', col2: 'Value 2' },
        { col1: 'Value 3', col2: 'Value 4' }
    ];

    adjustTableHeightToRange({
        min_height_tab: 500,
        max_height_tab: 600,
        tab_name: '#myTable',
        row_height: 40,
        row_selector: 'tr.data-row',
        
        // Функция для добавления новой строки
        addRowCallback: function(rowIndex, tableElement) {
            const tbody = tableElement.querySelector('tbody');
            if (!tbody) return;
            
            const row = document.createElement('tr');
            row.className = 'data-row';
            row.setAttribute('data-row-index', rowIndex);
            
            // Получаем данные для этой строки (если есть)
            const rowData = tableData[rowIndex - 1] || {};
            
            row.innerHTML = `
                <td>${rowIndex}</td>
                <td>${rowData.col1 || ''}</td>
                <td>${rowData.col2 || ''}</td>
            `;
            
            tbody.appendChild(row);
        },
        
        // Функция для удаления строки
        removeRowCallback: function(rowIndex, tableElement) {
            const row = tableElement.querySelector(`tr[data-row-index="${rowIndex}"]`);
            if (row) {
                row.remove();
            }
        },
        
        // Функция для получения индекса строки (опционально)
        getRowIndexCallback: function(rowElement) {
            return parseInt(rowElement.getAttribute('data-row-index'));
        },
        
        // Callback после завершения настройки
        onComplete: function(currentHeight, rowCount) {
            console.log(`Таблица настроена: высота ${currentHeight}px, строк ${rowCount}`);
        }
    });
}

// ============================================
// ПРИМЕР 3: Использование с таблицей на основе div (как в rmRecordForm)
// ============================================
function example3() {
    const tableData = []; // Ваши данные
    
    adjustTableHeightToRange({
        min_height_tab: 593,
        max_height_tab: 639,
        tab_name: '.parent',
        row_height: 37,
        row_selector: '.data-row[data-row-index]',
        
        addRowCallback: function(rowIndex, tableElement) {
            // Создаем все ячейки строки
            const div1 = document.createElement('div');
            div1.className = 'col-1 data-row border';
            div1.setAttribute('data-row-index', rowIndex);
            div1.textContent = rowIndex;
            
            const div2 = document.createElement('div');
            div2.className = 'col-2 data-row border';
            div2.setAttribute('data-row-index', rowIndex);
            
            const div3 = document.createElement('div');
            div3.className = 'col-3 data-row border';
            div3.setAttribute('data-row-index', rowIndex);
            
            // Добавляем данные, если они есть
            const rowData = tableData[rowIndex - 1];
            if (rowData) {
                div2.textContent = rowData.field1 || '';
                div3.textContent = rowData.field2 || '';
            }
            
            // Добавляем в таблицу
            tableElement.appendChild(div1);
            tableElement.appendChild(div2);
            tableElement.appendChild(div3);
        },
        
        removeRowCallback: function(rowIndex, tableElement) {
            const rows = tableElement.querySelectorAll(`.data-row[data-row-index="${rowIndex}"]`);
            rows.forEach(row => row.remove());
        }
    });
}

// ============================================
// ПРИМЕР 4: Использование функции calculateMaxTableRows для предварительного расчета
// ============================================
function example4() {
    // Сначала рассчитываем максимальное количество строк
    const calculation = calculateMaxTableRows(
        593,  // min_height_tab
        639,  // max_height_tab
        37,   // row_height
        50    // header_height (высота заголовка таблицы)
    );
    
    console.log('Минимальное количество строк:', calculation.minRows);
    console.log('Максимальное количество строк:', calculation.maxRows);
    console.log('Среднее количество строк:', calculation.avgRows);
    
    // Затем используем adjustTableHeightToRange для точной настройки
    adjustTableHeightToRange({
        min_height_tab: 593,
        max_height_tab: 639,
        tab_name: '.my-table',
        row_height: 37
    });
}

// ============================================
// ПРИМЕР 5: Использование в Blade-шаблоне Laravel
// ============================================
/*
В вашем Blade-файле:

1. Подключите скрипт в <head>:
   <script src="{{asset('js/table-height-adjuster.js')}}"></script>

2. В секции <script> используйте функцию:

<script>
    // Данные из PHP (если нужны)
    const tableData = @json($yourData ?? []);
    
    window.addEventListener('load', function() {
        setTimeout(function() {
            adjustTableHeightToRange({
                min_height_tab: 593,
                max_height_tab: 639,
                tab_name: '.your-table-class',
                row_height: 37,
                row_selector: '.data-row[data-row-index]',
                addRowCallback: function(rowIndex, tableElement) {
                    // Ваша логика добавления строки
                },
                removeRowCallback: function(rowIndex, tableElement) {
                    // Ваша логика удаления строки
                }
            });
        }, 100);
    });
</script>
*/

// ============================================
// ПРИМЕР 6: Использование с Bootstrap таблицей
// ============================================
function example6() {
    adjustTableHeightToRange({
        min_height_tab: 400,
        max_height_tab: 500,
        tab_name: '.table-responsive',
        row_height: 50,
        row_selector: 'tbody tr',
        
        addRowCallback: function(rowIndex, tableElement) {
            const tbody = tableElement.querySelector('tbody') || tableElement;
            const row = document.createElement('tr');
            row.setAttribute('data-row-index', rowIndex);
            row.innerHTML = `
                <td>${rowIndex}</td>
                <td>Column 1</td>
                <td>Column 2</td>
                <td>Column 3</td>
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
    });
}

