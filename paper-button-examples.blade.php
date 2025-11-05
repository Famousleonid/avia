{{-- Примеры использования компонента Paper Button --}}

{{-- 1. Базовая кнопка с текстом (по умолчанию: outline-success, portrait) --}}
<x-paper-button text="Export CSV" />

{{-- 2. Кнопка с ссылкой --}}
<x-paper-button 
    text="Download CSV" 
    href="/download/csv" 
/>

{{-- 3. Кнопка с маршрутом --}}
<x-paper-button 
    text="Export Data" 
    route="trainings.export" 
/>

{{-- 4. Кнопка с onclick действием --}}
<x-paper-button 
    text="Export CSV" 
    action="exportToCSV()" 
/>

{{-- 5. Альбомная ориентация (80x60) --}}
<x-paper-button 
    text="Export CSV File" 
    size="landscape" 
/>

{{-- 6. Разные цвета Bootstrap --}}
<x-paper-button text="Success" color="outline-success" />
<x-paper-button text="Primary" color="outline-primary" />
<x-paper-button text="Danger" color="outline-danger" />
<x-paper-button text="Warning" color="outline-warning" />
<x-paper-button text="Info" color="outline-info" />

{{-- 7. Кастомные цвета --}}
<x-paper-button 
    text="Custom Colors" 
    :customColors="[
        'fold' => '#ff6b6b',
        'stroke' => '#ff6b6b',
        'text' => '#c92a2a',
        'paper' => '#fff5f5'
    ]" 
/>

{{-- 8. Полный пример с всеми параметрами --}}
<x-paper-button 
    text="Export CSV File" 
    route="export.csv" 
    color="outline-success" 
    size="portrait" 
    ariaLabel="Export CSV file to download" 
/>

{{-- 9. В цикле --}}
@foreach($items as $item)
    <x-paper-button 
        :text="$item->name" 
        :route="'items.export'" 
        color="outline-primary" 
    />
@endforeach

{{-- 10. С условием --}}
@if($hasData)
    <x-paper-button 
        text="Export Data" 
        route="data.export" 
        color="outline-success" 
    />
@else
    <x-paper-button 
        text="No Data" 
        color="outline-secondary" 
        disabled 
    />
@endif

{{-- 
    Параметры компонента:
    
    text (string) - Текст на кнопке (по умолчанию: "Export CSV")
    href (string|null) - Прямая ссылка (если указан, создается тег <a>)
    route (string|null) - Имя маршрута Laravel (если указан, создается тег <a>)
    action (string|null) - JavaScript функция для onclick
    color (string) - Цветовая тема Bootstrap:
        - outline-success (по умолчанию)
        - outline-primary
        - outline-danger
        - outline-warning
        - outline-info
    size (string) - Размер кнопки:
        - portrait (60x80px, по умолчанию)
        - landscape (80x60px)
    ariaLabel (string|null) - ARIA label для доступности
    customColors (array|null) - Массив кастомных цветов:
        [
            'fold' => '#198754',    // цвет уголка
            'stroke' => '#198754',  // цвет обводки и линии сгиба
            'text' => '#0f5132',    // цвет текста
            'paper' => '#f5f5f5'    // цвет фона листа
        ]
--}}

