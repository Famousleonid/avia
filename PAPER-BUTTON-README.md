# Компонент Paper Button

Компонент кнопки в виде листка с загнутым уголком для Laravel Blade.

## Установка

1. Компонент уже создан в `resources/views/components/paper-button.blade.php`
2. Стили находятся в `public/css/paper-button.css`
3. Подключите стили в ваш layout:

```blade
<x-paper-button-styles />
```

Или вручную в `<head>`:
```html
<link rel="stylesheet" href="{{ asset('css/paper-button.css') }}">
```

## Использование

### Базовый пример

```blade
<x-paper-button text="Export CSV" />
```

### Параметры

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `text` | string | "Export CSV" | Текст на кнопке |
| `href` | string\|null | null | Прямая ссылка (создает `<a>`) |
| `route` | string\|null | null | Имя маршрута Laravel (создает `<a>`) |
| `action` | string\|null | null | JavaScript функция для `onclick` |
| `color` | string | "outline-success" | Цветовая тема Bootstrap |
| `size` | string | "portrait" | Размер: "portrait" (60x80) или "landscape" (80x60) |
| `ariaLabel` | string\|null | null | ARIA label для доступности |
| `customColors` | array\|null | null | Массив кастомных цветов |

### Примеры

#### 1. Кнопка с ссылкой
```blade
<x-paper-button 
    text="Download CSV" 
    href="/download/csv" 
/>
```

#### 2. Кнопка с маршрутом
```blade
<x-paper-button 
    text="Export Data" 
    route="trainings.export" 
/>
```

#### 3. Кнопка с JavaScript действием
```blade
<x-paper-button 
    text="Export CSV" 
    action="exportToCSV()" 
/>
```

#### 4. Альбомная ориентация
```blade
<x-paper-button 
    text="Export CSV File" 
    size="landscape" 
/>
```

#### 5. Разные цвета Bootstrap
```blade
<x-paper-button text="Success" color="outline-success" />
<x-paper-button text="Primary" color="outline-primary" />
<x-paper-button text="Danger" color="outline-danger" />
<x-paper-button text="Warning" color="outline-warning" />
<x-paper-button text="Info" color="outline-info" />
```

#### 6. Кастомные цвета
```blade
<x-paper-button 
    text="Custom Colors" 
    :customColors="[
        'fold' => '#ff6b6b',
        'stroke' => '#ff6b6b',
        'text' => '#c92a2a',
        'paper' => '#fff5f5'
    ]" 
/>
```

#### 7. Полный пример
```blade
<x-paper-button 
    text="Export CSV File" 
    route="export.csv" 
    color="outline-success" 
    size="portrait" 
    ariaLabel="Export CSV file to download" 
/>
```

## Поведение hover

### Для Bootstrap цветов (outline-success, outline-primary и т.д.)

**По умолчанию:**
- Обводка SVG - зелёная/синяя и т.д.
- Уголок - зелёный/синий и т.д.
- Линия сгиба - зелёная/синяя и т.д.
- Текст - тёмный цвет
- Фон листа - белый

**При hover:**
- Обводка SVG - белая
- Уголок - белый
- Линия сгиба - остаётся цветной (зелёная/синяя и т.д.)
- Текст - белый
- Фон листа - цветной (зелёный/синий и т.д.)

## Размеры

- **portrait**: 60px × 80px (вертикальная ориентация)
- **landscape**: 80px × 60px (горизонтальная ориентация)

## Кастомизация цветов

Вы можете задать свои цвета через параметр `customColors`:

```blade
<x-paper-button 
    text="Custom" 
    :customColors="[
        'fold' => '#198754',    // цвет уголка
        'stroke' => '#198754',  // цвет обводки и линии сгиба
        'text' => '#0f5132',    // цвет текста
        'paper' => '#f5f5f5'    // цвет фона листа
    ]" 
/>
```

## Файлы

- Компонент: `resources/views/components/paper-button.blade.php`
- Стили: `public/css/paper-button.css`
- Компонент стилей: `resources/views/components/paper-button-styles.blade.php`
- Примеры: `paper-button-examples.blade.php`

## Зависимости

- Bootstrap 5 (для классов `btn-outline-*`)
- Laravel Blade компоненты

