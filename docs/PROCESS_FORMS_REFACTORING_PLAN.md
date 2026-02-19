# План рефакторинга Process Forms

## Выполнено (этапы 1–6 + мультистраничность)

- **Этап 1:** Созданы `config/process_forms.php`, `app/Services/ProcessFormDataNormalizer.php`
- **Этап 2:** Созданы shared layout, `_print-settings-modal`, `_header`, `_footer`, `_styles`, `_scripts`
- **Этапы 3–5:** Созданы partials `ndt/_content`, `stress/_content`, `other/_content`
- **Этап 6:** `tdr-processes/processesForm.blade.php` переведён на shared layout; обновлены `TdrProcessController`, `TdrController`, `packageForms`
- **Мультистраничность:** Лимит строк (NDT Table Rows и т.д.) — это **строк на страницу**. Если данных больше (напр. 14 при лимите 10), оставшиеся 4 переносятся на вторую страницу. Класс `print-page-break-after` добавляется каждой N-й строке; `page-break-inside: auto` позволяет разрыв контейнера. Пустые строки добавляются только для заполнения первой страницы.

---

## Цель
Унификация и разделение форм процессов (NDT, Stress Relief, Other) для модулей **tdr-processes**, **extra_processes**, **wo_bushings** с выносом общего кода в shared-компоненты.

---

## Этап 1. Подготовка инфраструктуры

### 1.1 Создать структуру папок
```
resources/views/shared/process-forms/
├── _layout.blade.php
├── _print-settings-modal.blade.php
├── _header.blade.php
├── _footer.blade.php
├── ndt/
│   └── _content.blade.php
├── stress/
│   └── _content.blade.php
└── other/
    └── _content.blade.php
```

### 1.2 Создать конфигурационный файл
- **Файл:** `config/process_forms.php`
- **Содержимое:** ключи для каждого модуля (container_max_width, ndt_table_rows, stress_table_rows, other_table_rows, header_title, storage_key)

### 1.3 Создать сервис нормализации данных (опционально)
- **Файл:** `app/Services/ProcessFormDataNormalizer.php`
- **Назначение:** приведение данных из разных модулей к единому формату для shared partials

---

## Этап 2. Вынос shared layout и Print Settings

### 2.1 Создать `_layout.blade.php`
- Вынести из `tdr-processes/processesForm.blade.php`:
  - HTML-структуру (head, body, DOCTYPE)
  - Подключение Bootstrap
  - Блок кнопок Print / Print Settings
  - Слот для контента формы

### 2.2 Создать `_print-settings-modal.blade.php`
- Вынести модальное окно Print Settings
- Параметризовать: `$module`, `$config` (defaults из config)
- Поддержать скрытие Stress Relief / Other блоков для wo_bushings (если нужно)

### 2.3 Создать `_header.blade.php`
- Вынести шапку формы: COMPONENT NAME, PART NUMBER, WORK ORDER, SERIAL, DATE, RO No, VENDOR
- Параметры: `$current_wo`, `$selectedVendor`, `$header_title`

### 2.4 Создать `_footer.blade.php`
- Вынести футер: Form #, Rev#
- Параметр: `$process_name`

### 2.5 Вынести общие стили
- Создать `resources/css/process-forms.css` или оставить в `_layout.blade.php`
- Стили: border-*, details-row, process-text-long, print-hide-row, @media print

---

## Этап 3. Создание shared partial для NDT

### 3.1 Создать `ndt/_content.blade.php`
- Вынести из `tdr-processes/processesForm.blade.php` блок NDT:
  - Grid 3 колонки (MAGNETIC PARTICLE, LIQUID PENETRANT, ULTRASOUND и т.д.)
  - Таблица с колонками: ITEM No., Part No, DESCRIPTION, PROCESS No., QTY, ACCEPT, REJECT
  - Контейнер `.ndt-data-container`
- **Переменные:** `$ndt_processes`, `$ndt_components` (или `$ndt_rows` после нормализации), `$ndt1_name_id` … `$ndt8_name_id`, `$manuals`, `$current_wo`
- **Вариант:** поддержать два формата (tdr/extra) и формат wo_bushings (`$table_data`)

### 3.2 Согласовать структуру данных
- Документировать ожидаемый формат `$ndt_rows` для shared partial
- Если нужна нормализация — реализовать в `ProcessFormDataNormalizer`

---

## Этап 4. Создание shared partial для Stress Relief

### 4.1 Создать `stress/_content.blade.php`
- Вынести блок STRESS RELIEF:
  - MANUAL REF
  - Текст "Perform the Stress Relief..."
  - Таблица с колонкой PERFORMED вместо CMM No.
- **Переменные:** `$process_tdr_components`, `$process_components`, `$manuals`, `$current_wo`
- **Атрибуты:** `data-stress="true"` для строк

---

## Этап 5. Создание shared partial для Other (CAD, Chrome, Bake...)

### 5.1 Создать `other/_content.blade.php`
- Вынести блок для остальных процессов:
  - Текст "Perform the [process]..."
  - Таблица с колонкой CMM No.
- **Переменные:** `$process_tdr_components`, `$process_components`, `$manuals`, `$current_wo`

---

## Этап 6. Интеграция в tdr-processes

### 6.1 Рефакторинг `processesForm.blade.php`
- Заменить содержимое на `@include('shared.process-forms.layout', [...])`
- Передавать `module => 'tdr-processes'` и все нужные переменные
- Подключить нужный content partial по `$process_name->process_sheet_name`

### 6.2 Рефакторинг контроллера `TdrProcessController`
- Вынести подготовку данных в приватные методы: `prepareNdtFormData()`, `prepareStressFormData()`, `prepareOtherFormData()`
- Убедиться, что `processesForm()`, `show()`, `packageForms()` передают корректные данные

### 6.3 Проверить `packageForms.blade.php`
- Убедиться, что `@include` processesForm работает с новой структурой
- Проверить `formsData` и переменные для каждого типа формы

### 6.4 Проверить `processesFormContent.blade.php` и `TdrController`
- Убедиться, что вызовы view не сломаны

---

## Этап 7. Интеграция в extra_processes

### 7.1 Рефакторинг `extra_processes/processesForm.blade.php`
- Заменить на `@include('shared.process-forms.layout', ['module' => 'extra_processes', ...])`
- Адаптировать передаваемые переменные под структуру extra_processes

### 7.2 Рефакторинг `ExtraProcessController`
- Привести подготовку данных к формату shared partials
- Учесть отличия (например, container 920px, другие defaults)

### 7.3 Проверить package-forms и другие вызовы extra_processes

---

## Этап 8. Интеграция в wo_bushings

### 8.1 Анализ отличий wo_bushings
- Структура `$table_data` vs `ndt_components` / `process_tdr_components`
- Различия в Print Settings (только NDT, 20 rows)
- Структура header (WO BUSHING)

### 8.2 Рефакторинг `wo_bushings/processesForm.blade.php`
- Заменить на shared layout
- При необходимости: адаптер или нормализация `$table_data` → `$ndt_rows`

### 8.3 Рефакторинг `WoBushingController`
- Подготовка данных в формате shared partials
- Нормализация для NDT (если используется `$table_data`)

### 8.4 Конфигурация Print Settings для wo_bushings
- Добавить в `config/process_forms.php`
- При необходимости скрыть Stress Relief / Other в модалке

---

## Этап 9. JavaScript для печати

### 9.1 Вынести скрипт в shared
- `applyTableRowLimits()`, `addEmptyRowNDT()`, `addEmptyRowRegular()`
- `loadPrintSettings()`, `savePrintSettings()`, `applyPrintSettings()`
- Поддержка `$module` для выбора storage key

### 9.2 Унифицировать обработку beforeprint
- Один `beforeprint` listener, работающий с `.form-wrapper`, `.container-fluid`
- Селекторы: `.ndt-data-container`, `[data-stress="true"]`, `.data-row`

### 9.3 Удалить дублирование скриптов
- Убрать копии из processesForm каждого модуля
- Подключить shared script в layout

---

## Этап 10. Тестирование и очистка

### 10.1 Тестирование tdr-processes
- [ ] Одиночная форма NDT
- [ ] Одиночная форма Stress Relief
- [ ] Одиночная форма Other (CAD, Chrome, Bake)
- [ ] packageForms (смешанные формы)
- [ ] processesFormContent
- [ ] Print Settings: сохранение, сброс, применение
- [ ] Печать: корректность страниц, разрывы, лимиты строк

### 10.2 Тестирование extra_processes
- [ ] Одиночные формы
- [ ] Package forms (если есть)
- [ ] Print Settings
- [ ] Печать

### 10.3 Тестирование wo_bushings
- [ ] NDT форма
- [ ] Other формы
- [ ] Print Settings
- [ ] Печать

### 10.4 Очистка
- Удалить неиспользуемый код из старых processesForm
- Удалить дублирующиеся стили
- Удалить дублирующиеся скрипты

---

## Этап 11. Документация

### 11.1 Обновить документацию
- Описание структуры shared/process-forms
- Контракт данных для каждого partial
- Как добавить новый модуль

### 11.2 Комментарии в коде
- Описание параметров в layout и partials
- Примеры в config/process_forms.php

---

## Порядок выполнения (рекомендуемый)

1. **Этап 1** — инфраструктура
2. **Этап 2** — layout и Print Settings
3. **Этап 3** — NDT partial
4. **Этап 6** — интеграция tdr-processes (полная проверка)
5. **Этап 4, 5** — Stress и Other partials
6. **Этап 7** — extra_processes
7. **Этап 8** — wo_bushings
8. **Этап 9** — JavaScript
9. **Этап 10** — тестирование
10. **Этап 11** — документация

---

## Риски и митигация

| Риск | Митигация |
|------|-----------|
| Разные структуры данных | Нормализатор или адаптеры в контроллерах |
| Регрессия | Поэтапное внедрение, тестирование после каждого этапа |
| packageForms | Сохранить совместимость формData и переменных |
| localStorage | Разные ключи для разных модулей |

---

## Оценка трудозатрат (ориентировочно)

| Этап | Часы |
|------|------|
| 1. Инфраструктура | 1–2 |
| 2. Layout и Print Settings | 2–3 |
| 3. NDT partial | 2–3 |
| 4. Stress partial | 1–2 |
| 5. Other partial | 1–2 |
| 6. tdr-processes | 2–3 |
| 7. extra_processes | 2–3 |
| 8. wo_bushings | 2–4 |
| 9. JavaScript | 1–2 |
| 10. Тестирование | 2–3 |
| 11. Документация | 0.5–1 |
| **Итого** | **~17–28 ч** |
