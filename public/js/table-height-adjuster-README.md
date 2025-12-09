# Table Height Adjuster - Универсальная функция для настройки высоты таблиц

## Описание

Универсальная JavaScript функция для автоматической настройки высоты таблицы путем добавления/удаления строк до достижения целевого диапазона высоты.

## Установка

Подключите файл в вашем Blade-шаблоне:

```html
<script src="{{asset('js/table-height-adjuster.js')}}"></script>
```

## Основная функция

### `adjustTableHeightToRange(options)`

Автоматически настраивает высоту таблицы в заданном диапазоне.

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| `min_height_tab` | number | Да | Минимальная высота таблицы в пикселях |
| `max_height_tab` | number | Да | Максимальная высота таблицы в пикселях |
| `tab_name` | string | Да | CSS селектор таблицы (например, '.parent', '#myTable') |
| `row_height` | number | Нет | Примерная высота одной строки (для расчетов) |
| `row_selector` | string | Нет | CSS селектор для строк (по умолчанию: '[data-row-index]') |
| `addRowCallback` | Function | Нет | Функция для добавления новой строки: `(rowIndex, tableElement) => void` |
| `removeRowCallback` | Function | Нет | Функция для удаления строки: `(rowIndex, tableElement) => void` |
| `getRowIndexCallback` | Function | Нет | Функция для получения индекса строки: `(rowElement) => number` |
| `max_iterations` | number | Нет | Максимальное количество итераций (по умолчанию: 50) |
| `onComplete` | Function | Нет | Callback после завершения: `(currentHeight, rowCount) => void` |

#### Возвращаемое значение

```javascript
{
    success: boolean,        // Успешно ли выполнена настройка
    currentHeight: number,   // Текущая высота таблицы в px
    rowCount: number,        // Количество строк
    message: string          // Сообщение о результате
}
```

## Вспомогательная функция

### `calculateMaxTableRows(min_height_tab, max_height_tab, row_height, header_height)`

Рассчитывает максимальное количество строк на основе целевой высоты.

#### Параметры

- `min_height_tab` - Минимальная высота таблицы в пикселях
- `max_height_tab` - Максимальная высота таблицы в пикселях
- `row_height` - Высота одной строки в пикселях
- `header_height` - Высота заголовка таблицы в пикселях (по умолчанию: 0)

#### Возвращаемое значение

```javascript
{
    minRows: number,  // Минимальное количество строк
    maxRows: number,  // Максимальное количество строк
    avgRows: number   // Среднее количество строк
}
```

## Примеры использования

### Пример 1: Базовое использование

```javascript
adjustTableHeightToRange({
    min_height_tab: 593,
    max_height_tab: 639,
    tab_name: '.my-table',
    row_height: 37
});
```

### Пример 2: С кастомными функциями добавления/удаления строк

```javascript
adjustTableHeightToRange({
    min_height_tab: 500,
    max_height_tab: 600,
    tab_name: '#myTable',
    row_height: 40,
    row_selector: 'tr.data-row',
    
    addRowCallback: function(rowIndex, tableElement) {
        const tbody = tableElement.querySelector('tbody');
        const row = document.createElement('tr');
        row.className = 'data-row';
        row.setAttribute('data-row-index', rowIndex);
        row.innerHTML = `<td>${rowIndex}</td><td>Data</td>`;
        tbody.appendChild(row);
    },
    
    removeRowCallback: function(rowIndex, tableElement) {
        const row = tableElement.querySelector(`tr[data-row-index="${rowIndex}"]`);
        if (row) row.remove();
    }
});
```

### Пример 3: Использование в Laravel Blade

```blade
<script src="{{asset('js/table-height-adjuster.js')}}"></script>
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            adjustTableHeightToRange({
                min_height_tab: 593,
                max_height_tab: 639,
                tab_name: '.parent',
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
```

## Требования к структуре таблицы

Для корректной работы функции строки таблицы должны иметь:
- Атрибут `data-row-index` с уникальным индексом строки
- Или использовать кастомную функцию `getRowIndexCallback` для определения индекса

## Примечания

- Функция автоматически добавляет строки, если высота меньше минимума
- Функция автоматически удаляет строки, если высота больше максимума
- Процесс повторяется до достижения целевого диапазона или до достижения максимального количества итераций
- Все изменения логируются в консоль браузера

## Файлы

- `table-height-adjuster.js` - Основной файл с функциями
- `table-height-adjuster-example.js` - Примеры использования
- `table-height-adjuster-README.md` - Документация (этот файл)

