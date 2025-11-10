@props([
    'text' => 'Text',
    'href' => null,
    'route' => null,
    'action' => null,
    'color' => 'outline-success', // outline-success, outline-primary, outline-danger, outline-warning, outline-info
    'size' => 'portrait', // portrait (60x80) или landscape (80x60)
    'ariaLabel' => null,
    'customColors' => null, // ['fold' => '#198754', 'stroke' => '#198754', 'text' => '#0f5132', 'paper' => '#f5f5f5']
    'viewBox' => '200 270', // для portrait: '200 270', для landscape: '270 200'
])

@php
    // Определяем размеры
    $width = $size === 'portrait' ? 60 : 80;
    $height = $size === 'portrait' ? 80 : 60;

    // Определяем viewBox на основе размера
    if ($size === 'portrait') {
        $viewBoxCoords = '0 0 190 270';
        $paperPath = 'M10 10 H140 L180 50 V240 H10 Z';
        $foldPoints = '140,10 140,50 180,50';
        $linePath = 'M140 12 V50 H180';
        $foreignObjectX = 20;
        $foreignObjectY = 60;
        $foreignObjectWidth = 120;
        $foreignObjectHeight = 130;
    } else {
        $viewBoxCoords = '0 0 260 200';
        $paperPath = 'M10 10 H210 L250 50 V170 H10 Z';
        $foldPoints = '210,10 210,50 250,50';
        $linePath = 'M210 12 V50 H250';
        $foreignObjectX = 20;
        $foreignObjectY = 60;
        $foreignObjectWidth = 190;
        $foreignObjectHeight = 90;
    }

    // Определяем aria-label
    $ariaLabelValue = $ariaLabel ?? $text;

    // Определяем классы Bootstrap
    $buttonClass = 'paper-btn btn-' . $color . ' p-0 paper-' . $size;

    // Определяем атрибуты для кнопки/ссылки
    $tag = $href || $route ? 'a' : 'button';
    $attributes = $attributes->merge([
        'class' => $buttonClass,
        'aria-label' => $ariaLabelValue,
    ]);

    if ($href) {
        $attributes = $attributes->merge(['href' => $href]);
    } elseif ($route) {
        $attributes = $attributes->merge(['href' => route($route)]);
    }

    if ($action) {
        $attributes = $attributes->merge(['onclick' => $action]);
    }

    // Если есть кастомные цвета, добавляем их в style
    $customStyle = '';
    if ($customColors && is_array($customColors)) {
        $styleParts = [];
        if (isset($customColors['fold'])) $styleParts[] = '--fold:' . $customColors['fold'];
        if (isset($customColors['stroke'])) $styleParts[] = '--stroke:' . $customColors['stroke'];
        if (isset($customColors['text'])) $styleParts[] = '--text:' . $customColors['text'];
        if (isset($customColors['paper'])) $styleParts[] = '--paper:' . $customColors['paper'];
        if (!empty($styleParts)) {
            $customStyle = ' style="' . implode('; ', $styleParts) . '"';
        }
    }
@endphp

<{{ $tag }} {{ $attributes }}>
    <svg viewBox="{{ $viewBoxCoords }}" width="{{ $width }}" height="{{ $height }}"
         preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg"{!! $customStyle !!}>
        <!-- лист -->
        <path class="paper" d="{{ $paperPath }}"/>
        <!-- уголок -->
        <polygon class="fold" points="{{ $foldPoints }}"/>
        <!-- линия сгиба -->
        <path class="line" d="{{ $linePath }}"/>
        <!-- текст с переносом -->
        <foreignObject x="{{ $foreignObjectX }}" y="{{ $foreignObjectY }}"
                      width="{{ $foreignObjectWidth }}" height="{{ $foreignObjectHeight }}">
            <div xmlns="http://www.w3.org/1999/xhtml"
                 style="font: 36px Arial, sans-serif;
                        text-align: center;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        height: 100%;
                        word-wrap: break-word;
                        overflow-wrap: break-word;">
                {{ $text }}
            </div>
        </foreignObject>
    </svg>
</{{ $tag }}>

