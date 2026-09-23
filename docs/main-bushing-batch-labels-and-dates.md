# Main: номера партий и даты Machining

Проверка production W107736 (WO 205), 23/Sep/2026, только чтение:

| Процесс | Batch ID | Номер | QTY | RO | Sent | Return |
|---|---:|---|---:|---|---|---|
| NDT | 141 | B1 (legacy) | 13 | R9376 | 17/Sep/2026 | 18/Sep/2026 |
| Machining (AT) | 143 | B1 (legacy) | 13 | — | — | — |
| Passivation | 180 | B1 (legacy) | 4 | R9384 | 21/Sep/2026 | — |
| CAD | 181 | B1 (legacy) | 13 | R9383 | 21/Sep/2026 | — |
| Machining (AT) | 212 | B2 | 5 | — | — | — |
| CAD | 213 | B2 | 5 | — | — | — |
| NDT | 214 | B2 | 5 | — | — | — |

Причина расхождения: Main присваивал Grp 1/2 после сортировки по IPL, независимо от сохранённых route_number/legacy_number. Партии и RO не были перепутаны в БД. Main теперь использует BushingRouteBatches::labels(), как таблица втулок.

В Main поля дат втулок раньше были только для просмотра. Для Machining добавлены Sent/Return с общим календарём dd/Mmm/yyyy и внутренним подтверждением. Даты сохраняются через существующий endpoint конкретного wo_bushing_batch. После обновления таблицы втулок её sent/Ret берутся из этих же полей. Не требуется копировать даты в отдельные строки, менять RO или состав партии. NDT/CAD/Passivation остаются для просмотра.

Для production вручную перенести:

- app/Http/Controllers/Admin/MainController.php
- resources/views/admin/mains/main.blade.php
- resources/views/admin/mains/partials/js/mains-general-tasks.blade.php
- public/js/main.js

Миграция не нужна. Обновить открытые страницы. Production не изменялся.

Целевой тест MainBushingBatchDisplayTest проверяет противоположную сортировку B2/B1, даты под Technician, запрет Return без Sent, статусы таблицы втулок и неизменность соседнего батча. В браузере локального Main проверены поля, английский формат дат, внутреннее подтверждение и отмена без сохранения; ошибок JS нет. Локальные данные W107736 отличаются от production; фактический production-состав приведён выше.
