# Переносы строк в процессах и комментариях

Изменения подготовлены локально 23/Sep/2026. На production не перенесены. Миграция не требуется.

Сохранение Process и Comment уже поддерживает переводы строк. Исправлено отображение: комментарий начинается отдельной строкой при выборе процесса; печатные формы сохраняют переносы в спецификации, комментарии мануала и описании операции. Высота строки увеличивается по содержимому. При печати высокая строка занимает место пустых строк таблицы.

Перенести следующие файлы с сохранением путей (включая новый `_process-text.blade.php`):

```
public/css/admin-theme.css
public/js/main.js
public/js/tdr-processes/create-processes/create-processes.js
public/js/tdr-processes/edit-process/edit-process.js
resources/views/admin/manuals/show.blade.php
resources/views/admin/processes/create.blade.php
resources/views/admin/tdr-processes/partials/processes-body.blade.php
resources/views/admin/tdrs/partials/show-scripts.blade.php
resources/views/admin/wo_bushings/partials/process-selects.blade.php
resources/views/shared/process-forms/_layout.blade.php
resources/views/shared/process-forms/_styles.blade.php
resources/views/shared/process-forms/_scripts.blade.php
resources/views/shared/process-forms/_process-text.blade.php
resources/views/shared/process-forms/other/_content.blade.php
resources/views/shared/process-forms/stress/_content.blade.php
resources/views/shared/process-forms/ndt/_content.blade.php
```

После переноса обновить страницу Ctrl+F5, открыть выбор процесса заново и проверить предпросмотр печати.

Проверено: 9 тестов, 59 assertions; сохранение и получение многострочного текста через API, экранирование HTML, обычные и NDT формы. В Chrome проверены раскрытый список Bushing Processes на широком и узком экране и печать тестовой CAD-формы с девятью строками текста: соседние ячейки имеют одинаковую высоту, текст не обрезается, подвал остается на одном листе. Тестовые варианты в браузере не сохранялись в рабочие записи.

## Многострочное Description в Part Process — 23/Sep/2026

Локально: после уточнения пользователя окно Edit Part Process сохраняет прежний размер — до 880px шириной и 80vh высотой, без полноэкранного режима. Description перенесён под остальные поля на всю ширину формы внутри окна, заменён на textarea. То же расположение используется в отдельной странице редактирования, её прежняя ширина 850px сохранена. Существующий лимит `tdr_processes.description` — 255 символов — сохранён и указан в `maxlength`; миграция не требуется. Во время редактирования фоновый AI-виджет скрыт, чтобы не перекрывать Cancel на узком экране.

В NDT Description получил общий класс с `white-space: pre-line`; в Traveler добавлено такое же сохранение переносов. Вывод остаётся экранированным. Обычные и Stress формы уже используют общий многострочный шаблон выше.

Дополнительно перенести с сохранением путей:

```
resources/views/admin/tdrs/partials/show-modals.blade.php
resources/views/admin/tdr-processes/partials/edit-form.blade.php
resources/views/admin/tdr-processes/edit.blade.php
resources/views/admin/tdr-processes/travelForm.blade.php
resources/views/shared/process-forms/ndt/_content.blade.php
```

Предыдущий набор файлов с общим `_process-text` и `_styles` также должен быть установлен. Production в этой задаче не изменён.

Проверено: `TdrProcessMultilineDescriptionTest`, `TdrProcessNdtFormTest`, `PrintProcessFormFormattingTest` — 6 тестов, 69 assertions. Сохранение, повторное открытие и вывод переноса строки проверены для Chrome stripping, NDT-6, Stress Relief и Traveler в тестовой БД. В Chrome на локальном WO107901 проверены форма 1440×1000 и 390×844, заполнение, Cancel, отсутствие горизонтального переполнения и ошибок JavaScript. Предпросмотр CSS print проверен с многострочным DOM-образцом в обычной форме, NDT и Traveler; рабочие записи WO не менялись.
