<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R&M Record</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 820px;
            height: auto;
            /*transform: scale(0.8);*/
            transform-origin: top left;
            padding: 3px;
            margin-left: 10px;
            margin-right: 10px;
        }


        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                /*size: letter ;*/
                size: Letter;
                margin: 2mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: auto;
                width: auto;
                margin-left: 3px;
                padding: 0;
            }


            .container-fluid {
                max-height: calc(100vh - 20px); /* Оставляем место для футера */
                overflow: hidden;
                margin: 0 !important;
                padding: 3px !important;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print{
                display: none;
            }
            /* Уменьшаем отступы между секциями */
            .row {
                margin-bottom: 0 !important;
            }
            /* Колонтитул внизу страницы */
            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 2px 0;
                margin: 0;
            }

            /*!* Уменьшаем отступы в таблицах *!*/
            /*.div1, .div2, .div3, .div4, .div31, .div32, .div33, .div34, .div35, .div36 {*/
            /*    padding-top: 2px !important;*/
            /*}*/

            .border-r {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

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
        .border-lll-b-r {
            border-left: 8px  solid lightgrey;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-r {
            border-right: 1px solid black;
        }
        .border-l-b-rrr {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 5px solid black;
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
        .border-l-t {
            border-left: 1px solid black;
            border-top: 1px solid black;
        }
        .border-l {
            border-left: 1px solid black;
        }
        .border-ll-bb {
            border-left: 2px solid black;
            border-bottom: 2px solid black;

        }
        .border-ll-bb-rr {
            border-left: 2px solid black;
            border-bottom: 2px solid black;
            border-right: 2px solid black;
        }
        .border-bb {
            border-bottom: 2px solid black;
        }
        .border-b {
            border-bottom: 1px solid black;
        }
        .border-t-r-b {
            border-top: 1px solid black;
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-t {
            border-top: 1px solid black;

        }
        .border-tt-gr {
            border-top: 3px solid gray;

        }
        .border-r-b {

            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .text-center {
            text-align: center;

        }

        .text-black {
            color: #000;
        }

        /*.p-1, .p-2, .p-3, .p-4 {*/
        /*    padding: 0.25rem;*/
        /*    padding: 0.5rem;*/
        /*    padding: 0.75rem;*/
        /*    padding: 1rem;*/
        /*}*/

        .topic-header {
            width: 100px;
        }

        .topic-content {
            width: 600px;
        }

        .topic-content-2 {
            width: 701px;
        }

        .hrs-topic, .trainer-init {
            width: 100px;
        }
        .hrs-topic-1,.trainer-init-1 {
            width: 98px;
        }
        .trainer-init-1 {
            width: 99px;
        }
        .fs-9 {
            font-size: 0.9rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-7 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.75rem; /* или любое другое подходящее значение */
        }
        .fs-4 {
            font-size: 0.4rem; /* или любое другое подходящее значение */
        }

        .details-row {
            display: flex;
            align-items: center; /* Выравнивание элементов по вертикали */
            height: 36px; /* Фиксированная высота строки */
        }
        .details-cell {
            flex-grow: 1; /* Позволяет колонкам растягиваться и занимать доступное пространство */
            display: flex;
            justify-content: center; /* Центрирование содержимого по горизонтали */
            align-items: center; /* Центрирование содержимого по вертикали */
            border: 1px solid black; /* Границы для наглядности */
        }
        .check-icon {
            width: 24px; /* Меньший размер изображения */
            height: auto;
            margin: 0 5px; /* Отступы вокруг изображения */
        }
        .page-break {
            page-break-after: always;
        }



        .title {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(1, 1fr);
            gap: 0px;
        }


        .div2 {
            grid-column: span 3 / span 3;
        }

        .div3 {
            grid-column-start: 5;
        }


        .parent {
            display: grid;
            /*grid-template-columns: repeat(12, 1fr);*/
            grid-template-columns: .6fr 2.7fr 1fr 3fr 1fr 1fr 3fr ;
            /*grid-template-rows: repeat(5, .5fr);*/
            gap: 0;
        }


        /*.div12 {*/
        /*    grid-column: span 2 / span 2;*/
        /*}*/

        /*.div13 {*/
        /*    grid-column-start: 4;*/
        /*}*/

        /*.div14 {*/
        /*    grid-column: span 3 / span 3;*/
        /*    grid-column-start: 5;*/
        /*}*/

        /*.div15 {*/
        /*    grid-column-start: 8;*/
        /*}*/

        /*.div16 {*/
        /*    grid-column-start: 9;*/
        /*}*/

        /*.div17 {*/
        /*    grid-column: span 3 / span 3;*/
        /*    grid-column-start: 10;*/
        /*}*/



        .qc_stamp {
            display: grid;
            grid-template-columns: 3.3fr 4fr 1fr 4fr;
            /*grid-template-columns: repeat(12, 1fr);*/
            grid-template-rows: repeat(1, 1fr);
            gap: 0px;
        }

        /*.div21 {*/
        /*    grid-column: span 3 / span 3;*/
        /*}*/

        /*.div22 {*/
        /*    grid-column: span 4 / span 4;*/
        /*    grid-column-start: 4;*/
        /*}*/

        /*.div23 {*/
        /*    grid-column-start: 8;*/
        /*}*/

        /*.div24 {*/
        /*    grid-column: span 4 / span 4;*/
        /*    grid-column-start: 9;*/
        /*}*/








    </style>
</head>
<body>
<!-- Кнопка для печати -->
<div class="text-start m-3">
    <button class="btn btn-outline-primary no-print" onclick="window.print()">
        Print Form
    </button>
</div>
<div class="container-fluid">


    <div class="title">

        <div class="div1">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 140px">
        </div>
        <div class="div2">
            <h5 class="pt-3  text-black text-center"><strong>Repair and Modification Record WO#</strong></h5>

        </div>
        <div class="div3 pt-3 border-all text-center mb-2">
            <h4>
                    <strong>W{{$current_wo->number}}</strong>
            </h4>
        </div>
    </div>

    <div class="row border-all-b  m-sm-0">
        <h5 class="ps-1 fs-9">Technical Notes:</h5>
        @for($i = 1; $i <= 7; $i++)
            @php
                $noteKey = 'note' . $i;
                $noteValue = $technicalNotes[$noteKey] ?? '';
            @endphp
            <div class="border-b pt-2" style="height: 30px">{{ $noteValue }}</div>
        @endfor
    </div>
<p></p>

    <div class="parent mt-3">
        <div class="div11 border-l-t-b text-center align-content-center fs-75" >Item</div>
        <div class="div12 border-l-t-b text-center align-content-center fs-75">Part Description</div>
        <div class="div13 border-l-t-b text-center align-content-center fs-75">Modification or Repair #</div>
        <div class="div14 border-l-t-b text-center align-content-center fs-75">Description of Modification  or
            Repair</div>
        <div class="div15 border-l-t-b text-center align-content-center fs-75">Previously Carried out</div>
        <div class="div16 border-l-t-b text-center align-content-center fs-75">Carried out by AT</div>
        <div class="div17 border-all text-center align-content-center fs-75">Identification Method</div>
        @php
            // Максимальное количество строк для рендеринга (больше, чем нужно, чтобы было из чего выбирать)
            $max_row = 25;
            // Сохраняем данные для использования в JavaScript
            $rmRecordsData = [];
            if ($rmRecords && count($rmRecords) > 0) {
                foreach($rmRecords as $record) {
                    $rmRecordsData[] = [
                        'part_description' => $record->part_description ?? '',
                        'mod_repair' => $record->mod_repair ?? '',
                        'description' => $record->description ?? '',
                        'ident_method' => $record->ident_method ?? ''
                    ];
                }
            }
        @endphp
        @for($i=1; $i<$max_row; $i++)
            @php
                $rmRecord = $rmRecords->get($i-1);
            @endphp
            <div class="div11 border-l-b text-center align-content-center fs-75 data-row" style="min-height: 37px" data-row-index="{{$i}}">{{$i}}</div>
            <div class="div12 border-l-b text-center align-content-center fs-75 data-row" data-row-index="{{$i}}">{{ $rmRecord ? $rmRecord->part_description : '' }}</div>
            <div class="div13 border-l-b text-center align-content-center fs-75 data-row" data-row-index="{{$i}}">{{ $rmRecord ? $rmRecord->mod_repair : '' }}</div>
            <div class="div14 border-l-b text-center align-content-center fs-75 data-row" data-row-index="{{$i}}">{{ $rmRecord ? $rmRecord->description : '' }}</div>
            <div class="div15 border-l-b text-center align-content-center fs-75 data-row" style="color: lightgray" data-row-index="{{$i}}">tech stamp</div>
            <div class="div16 border-l-b text-center align-content-center fs-75 data-row" style="color: lightgray" data-row-index="{{$i}}">tech stamp</div>
            <div class="div17 border-l-b-r text-center align-content-center fs-75 data-row" data-row-index="{{$i}}">{{ $rmRecord ? $rmRecord->ident_method : '' }}</div>
        @endfor

    </div>

    <div class="qc_stamp mt-1">
        <div class="div21" style="min-height: 37px"></div>
        <div class="div22 border-all text-end align-content-center pe-1 fs-8" >Quality Assurance Acceptance </div>
        <div class="div23 border-t-r-b text-center align-content-center fs-8" style="color: lightgray">Q.C. stamp</div>
        <div class="div24 border-t-r-b text-center  pt-4  fs-8" style="color: lightgray">Date</div>
    </div>


</div>

<footer >
    <div class="row" style="width: 100%; padding: 1px 1px;">
        <div class="col-6 text-start">
            {{__("Form #005")}}
        </div>

        <div class="col-6 text-end pe-4 ">
            {{__('Rev#0, 15/Dec/2012   ')}}
        </div>
    </div>
</footer>

<!-- Подключаем скрипт для автоматической настройки высоты таблиц -->
<script src="{{ asset('js/table-height-adjuster.js') }}"></script>

<script>
    // Данные из PHP для использования в JavaScript
    const rmRecordsData = @json($rmRecordsData ?? []);

    // Функция для определения общей высоты двух основных таблиц
    function getTablesHeight() {
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
        const rowHeightInfo = getRowHeightStatistics();

        return {
            table1Height: height1,
            table2Height: height2,
            marginBetween: marginTop,
            totalHeight: totalHeightWithMargin,
            rowHeightInfo: rowHeightInfo
        };
    }

    // Функция для получения статистики по высотам строк
    function getRowHeightStatistics() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        if (rows.length === 0) {
            return {
                min: 0,
                max: 0,
                avg: 0,
                count: 0
            };
        }

        // Группируем строки по индексу (каждая строка состоит из 7 ячеек)
        const rowGroups = {};
        rows.forEach(cell => {
            const index = parseInt(cell.getAttribute('data-row-index'));
            if (!rowGroups[index]) {
                rowGroups[index] = [];
            }
            rowGroups[index].push(cell);
        });

        // Измеряем высоту каждой строки (берем максимальную высоту среди ячеек строки)
        const rowHeights = [];
        Object.keys(rowGroups).forEach(index => {
            const cells = rowGroups[index];
            let maxCellHeight = 0;
            cells.forEach(cell => {
                const height = cell.offsetHeight;
                if (height > maxCellHeight) {
                    maxCellHeight = height;
                }
            });
            if (maxCellHeight > 0) {
                rowHeights.push(maxCellHeight);
            }
        });

        if (rowHeights.length === 0) {
            return {
                min: 0,
                max: 0,
                avg: 0,
                count: 0
            };
        }

        const min = Math.min(...rowHeights);
        const max = Math.max(...rowHeights);
        const avg = Math.round(rowHeights.reduce((sum, h) => sum + h, 0) / rowHeights.length);

        return {
            min: min,
            max: max,
            avg: avg,
            count: rowHeights.length,
            heights: rowHeights // Массив всех высот для детального анализа
        };
    }

    // Функция для получения текущего количества строк в таблице
    function getCurrentRowCount() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        const rowIndices = new Set();
        rows.forEach(row => {
            const index = parseInt(row.getAttribute('data-row-index'));
            if (!isNaN(index)) {
                rowIndices.add(index);
            }
        });
        return rowIndices.size;
    }

    // Функция для получения максимального индекса строки
    function getMaxRowIndex() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        let maxIndex = 0;
        rows.forEach(row => {
            const index = parseInt(row.getAttribute('data-row-index'));
            if (!isNaN(index) && index > maxIndex) {
                maxIndex = index;
            }
        });
        return maxIndex;
    }

    // Функция для удаления строки по индексу
    function removeRow(rowIndex) {
        const rows = document.querySelectorAll(`.parent .data-row[data-row-index="${rowIndex}"]`);
        rows.forEach(row => row.remove());
    }

    // Функция для добавления пустой строки
    function addEmptyRow(rowIndex) {
        const parent = document.querySelector('.parent');
        if (!parent) return;

        // Создаем все 7 ячеек строки
        const div11 = document.createElement('div');
        div11.className = 'div11 border-l-b text-center align-content-center fs-75 data-row';
        div11.style.minHeight = '37px';
        div11.setAttribute('data-row-index', rowIndex);
        div11.textContent = rowIndex;

        const div12 = document.createElement('div');
        div12.className = 'div12 border-l-b text-center align-content-center fs-75 data-row';
        div12.setAttribute('data-row-index', rowIndex);

        const div13 = document.createElement('div');
        div13.className = 'div13 border-l-b text-center align-content-center fs-75 data-row';
        div13.setAttribute('data-row-index', rowIndex);

        const div14 = document.createElement('div');
        div14.className = 'div14 border-l-b text-center align-content-center fs-75 data-row';
        div14.setAttribute('data-row-index', rowIndex);

        const div15 = document.createElement('div');
        div15.className = 'div15 border-l-b text-center align-content-center fs-75 data-row';
        div15.style.color = 'lightgray';
        div15.setAttribute('data-row-index', rowIndex);
        div15.textContent = 'tech stamp';

        const div16 = document.createElement('div');
        div16.className = 'div16 border-l-b text-center align-content-center fs-75 data-row';
        div16.style.color = 'lightgray';
        div16.setAttribute('data-row-index', rowIndex);
        div16.textContent = 'tech stamp';

        const div17 = document.createElement('div');
        div17.className = 'div17 border-l-b-r text-center align-content-center fs-75 data-row';
        div17.setAttribute('data-row-index', rowIndex);

        // Добавляем данные, если они есть
        const recordData = rmRecordsData[rowIndex - 1];
        if (recordData) {
            div12.textContent = recordData.part_description || '';
            div13.textContent = recordData.mod_repair || '';
            div14.textContent = recordData.description || '';
            div17.textContent = recordData.ident_method || '';
        }

        // Добавляем все ячейки в контейнер
        parent.appendChild(div11);
        parent.appendChild(div12);
        parent.appendChild(div13);
        parent.appendChild(div14);
        parent.appendChild(div15);
        parent.appendChild(div16);
        parent.appendChild(div17);
    }

    // Функция для измерения реальной высоты строк с учетом разного содержимого
    function getActualRowHeight() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        if (rows.length === 0) {
            return 37; // Возвращаем значение по умолчанию, если строк нет
        }

        // Группируем строки по индексу (каждая строка состоит из 7 ячеек)
        const rowGroups = {};
        rows.forEach(cell => {
            const index = parseInt(cell.getAttribute('data-row-index'));
            if (!rowGroups[index]) {
                rowGroups[index] = [];
            }
            rowGroups[index].push(cell);
        });

        // Измеряем высоту каждой строки (берем максимальную высоту среди ячеек строки)
        const rowHeights = [];
        Object.keys(rowGroups).forEach(index => {
            const cells = rowGroups[index];
            let maxCellHeight = 0;
            cells.forEach(cell => {
                const height = cell.offsetHeight;
                if (height > maxCellHeight) {
                    maxCellHeight = height;
                }
            });
            if (maxCellHeight > 0) {
                rowHeights.push(maxCellHeight);
            }
        });

        if (rowHeights.length === 0) {
            return 37; // Значение по умолчанию
        }

        // Возвращаем среднюю высоту строки (можно использовать Math.max для максимальной)
        const avgHeight = rowHeights.reduce((sum, h) => sum + h, 0) / rowHeights.length;
        const maxHeight = Math.max(...rowHeights);

        // Используем среднее значение, но не меньше минимальной высоты
        return Math.max(37, Math.round(avgHeight));
    }

    // Функция для измерения высоты заголовка таблицы
    function getHeaderHeight() {
        const headerCells = document.querySelectorAll('.parent > div:not(.data-row)');
        if (headerCells.length === 0) {
            return 0;
        }

        // Находим максимальную высоту среди ячеек заголовка
        let maxHeight = 0;
        headerCells.forEach(cell => {
            const height = cell.offsetHeight;
            if (height > maxHeight) {
                maxHeight = height;
            }
        });

        return maxHeight;
    }

    // Функция для автоматической настройки высоты таблицы (использует универсальную функцию)
    function adjustTableHeight() {
        // Сначала измеряем реальную высоту строк и заголовка
        let actualRowHeight = getActualRowHeight();
        const headerHeight = getHeaderHeight();
        console.log('Начальная измеренная высота строки:', actualRowHeight + 'px');
        console.log('Высота заголовка таблицы:', headerHeight + 'px');

        // Переменная для отслеживания изменений высоты строк
        let lastRowHeight = actualRowHeight;
        let iterationCount = 0;

        // Используем универсальную функцию adjustTableHeightToRange
        const result = adjustTableHeightToRange({
            min_height_tab: 611,
            max_height_tab: 680,
            tab_name: '.parent',
            row_height: actualRowHeight, // Используем реальную высоту строки
            header_height: headerHeight, // Учитываем высоту заголовка
            row_selector: '.data-row[data-row-index]',
            addRowCallback: function(rowIndex, tableElement) {
                addEmptyRow(rowIndex);
                iterationCount++;

                // После добавления строки даем время на отрисовку и пересчитываем высоту
                // Используем requestAnimationFrame для более точного измерения
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        const newRowHeight = getActualRowHeight();
                        if (Math.abs(newRowHeight - lastRowHeight) > 3) {
                            console.log(`[Итерация ${iterationCount}] Высота строки изменилась: ${lastRowHeight}px → ${newRowHeight}px`);
                            lastRowHeight = newRowHeight;
                        }
                    }, 50); // Небольшая задержка для полной отрисовки
                });
            },
            removeRowCallback: function(rowIndex, tableElement) {
                removeRow(rowIndex);
                iterationCount++;
            },
            getRowIndexCallback: function(rowElement) {
                return parseInt(rowElement.getAttribute('data-row-index'));
            },
            max_iterations: 50,
            onComplete: function(currentHeight, rowCount) {
                // Финальный пересчет высоты строки после завершения настройки
                setTimeout(() => {
                    const finalRowHeight = getActualRowHeight();
                    const rowStats = getRowHeightStatistics();

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
                }, 100);
            }
        });

        return result;
    }

    // Вызываем функции после полной загрузки страницы
    window.addEventListener('load', function() {
        // Сначала настраиваем высоту таблицы
        setTimeout(function() {
            adjustTableHeight();

            // Затем выводим информацию о высотах
            const heights = getTablesHeight();
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

                // Информационный блок скрыт
                // Раскомментируйте код ниже, если нужно показать информационный блок на странице

                const infoDiv = document.createElement('div');
                infoDiv.className = 'no-print';
                infoDiv.style.cssText = 'position: fixed; top: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 15px; border-radius: 5px; z-index: 10000; font-size: 12px;';
                const rowInfo = heights.rowHeightInfo ?
                    `<br><strong>Высоты строк:</strong><br>
                    Мин: ${heights.rowHeightInfo.min}px, Макс: ${heights.rowHeightInfo.max}px, Средняя: ${heights.rowHeightInfo.avg}px` : '';
                infoDiv.innerHTML = `
                    <strong>Высота таблиц:</strong><br>
                    Таблица 1 (.parent): ${heights.table1Height}px<br>
                    Таблица 2 (.qc_stamp): ${heights.table2Height}px<br>
                    Отступ: ${heights.marginBetween}px<br>
                    <strong>Общая высота: ${heights.totalHeight}px</strong><br>
                    <strong>Количество строк: ${getCurrentRowCount()}</strong>${rowInfo}
                `;
                document.body.appendChild(infoDiv);

            }
        }, 100); // Небольшая задержка для полной отрисовки
    });

    // Также можно вызвать функцию вручную через консоль: getTablesHeight() или adjustTableHeight()
</script>
</body>
</html>

