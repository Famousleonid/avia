# CSS Grid: Управление шириной столбцов

## Основные способы задания ширины столбцов

### 1. Фиксированная ширина (px, em, rem)

```css
.grid-container {
    display: grid;
    grid-template-columns: 100px 200px 150px;
}
```

**Применение:** Когда нужны точные размеры (сайдбары, навигация, фиксированные элементы).

### 2. Процентная ширина (%)

```css
.grid-container {
    display: grid;
    grid-template-columns: 25% 50% 25%;
}
```

**Применение:** Относительные размеры от ширины контейнера. Сумма может быть меньше 100%.

### 3. Fractional units (fr)

```css
.grid-container {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
}
```

**Применение:** Пропорциональное распределение доступного пространства. 1fr = 1 часть.

### 4. Автоматическая ширина (auto)

```css
.grid-container {
    display: grid;
    grid-template-columns: auto 1fr auto;
}
```

**Применение:** Ширина определяется содержимым элемента.

### 5. minmax() - диапазон ширины

```css
.grid-container {
    display: grid;
    grid-template-columns: minmax(100px, 200px) minmax(200px, 1fr);
}
```

**Применение:** Задает минимальную и максимальную ширину столбца.

### 6. repeat() - повторяющиеся столбцы

```css
.grid-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    /* Эквивалентно: 1fr 1fr 1fr */
}
```

**Применение:** Создание одинаковых столбцов.

### 7. Адаптивные столбцы

```css
.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}
```

**Применение:** Автоматическое создание столбцов в зависимости от доступного пространства.

## Практические примеры

### Типичный макет сайта

```css
.layout {
    display: grid;
    grid-template-columns: 250px 1fr 200px;
    grid-template-areas: 
        "header header header"
        "sidebar main aside"
        "footer footer footer";
    min-height: 100vh;
}

.header { grid-area: header; }
.sidebar { grid-area: sidebar; }
.main { grid-area: main; }
.aside { grid-area: aside; }
.footer { grid-area: footer; }
```

### Адаптивная сетка карточек

```css
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}
```

### Навигационная панель

```css
.navbar {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    padding: 0 20px;
}
```

## Продвинутые техники

### Именованные линии

```css
.grid-container {
    display: grid;
    grid-template-columns: [sidebar-start] 200px [sidebar-end main-start] 1fr [main-end];
}

.sidebar {
    grid-column: sidebar-start / sidebar-end;
}
```

### Условные столбцы

```css
.grid-container {
    display: grid;
    grid-template-columns: 
        minmax(200px, 1fr)
        minmax(200px, 1fr)
        minmax(200px, 1fr);
}
```

### Комбинирование с медиа-запросами

```css
.grid-container {
    display: grid;
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .grid-container {
        grid-template-columns: 250px 1fr;
    }
}

@media (min-width: 1200px) {
    .grid-container {
        grid-template-columns: 250px 1fr 200px;
    }
}
```

## Полезные свойства

### gap - промежутки между элементами

```css
.grid-container {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px; /* row-gap column-gap */
    /* или */
    row-gap: 20px;
    column-gap: 30px;
}
```

### justify-items - выравнивание по горизонтали

```css
.grid-container {
    justify-items: start | end | center | stretch;
}
```

### align-items - выравнивание по вертикали

```css
.grid-container {
    align-items: start | end | center | stretch;
}
```

## Советы по использованию

1. **Используйте fr для гибких макетов** - они автоматически адаптируются к размеру контейнера.

2. **Комбинируйте единицы измерения** - фиксированная ширина + fr для оптимального результата.

3. **Применяйте minmax() для адаптивности** - задавайте минимальную и максимальную ширину.

4. **Используйте auto для контента** - когда ширина должна зависеть от содержимого.

5. **Применяйте repeat() для повторяющихся элементов** - упрощает код и делает его более читаемым.

6. **Используйте grid-template-areas для сложных макетов** - визуально понятная структура.

## Отладка CSS Grid

### Визуализация сетки в браузере

```css
.grid-container {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    /* Добавьте для отладки */
    border: 2px solid #333;
}

.grid-item {
    border: 1px solid #666;
    background: rgba(0, 123, 255, 0.1);
}
```

### Полезные инструменты разработчика

- Chrome DevTools: включите "Show grid" в Elements panel
- Firefox DevTools: Grid Inspector
- CSS Grid Visualizer онлайн

## Примеры реальных проектов

### Карточки товаров

```css
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px;
}
```

### Галерея изображений

```css
.gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}
```

### Форма с метками

```css
.form-grid {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 10px;
    align-items: center;
}
```

Это руководство поможет вам эффективно управлять шириной столбцов в CSS Grid для создания гибких и адаптивных макетов. 