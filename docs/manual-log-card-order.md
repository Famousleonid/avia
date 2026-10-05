# Порядок Log Card для мануала

Начальная очередность задаётся при импорте пакета мануала по порядку строк
листа LLP / Log Card исходного XLS/XLSX и включается в production SQL.
На странице Manual → Parts → Log Card order нет загрузки файла: доступны поиск,
перетаскивание, стрелки и ввод номера позиции (Enter/выход из поля сохраняет AJAX).
Альтернативные P/N одной позиции перемещаются вместе.

Порядок общий для мануала, хранится в manuals.log_card_order (JSON component IDs),
изменения попадают в activity_log. Новые карты web/mobile используют его;
Сохранённые карты активных WO также показываются и печатаются по порядку мануала.
У завершённых WO и карт, заблокированных QA (дата Post Disassembly inspection),
сохраняется порядок component_data, включая просмотр администратором. Просмотр не перезаписывает
данные карты; индексы редактирования и серийные номера остаются у своих деталей.
Перетаскивание/стрелки сохраняются кнопкой Save order, номер позиции — сразу. Конкурирующее
сохранение возвращает 409 и требует обновить страницу.

## Перенос на production пользователем

- app/Models/Manual.php
- app/Services/ManualLogCardOrder.php
- app/Http/Controllers/Admin/ManualLogCardOrderController.php
- app/Http/Controllers/Admin/LogCardController.php
- app/Http/Controllers/Api/Mobile/MobileApiController.php
- routes/web.php
- resources/views/admin/manuals/show.blade.php
- resources/views/admin/manuals/log-card-order.blade.php
- public/js/manual-log-card-order.js
- public/js/mobile-log-card.js
- database/migrations/2026_10_01_120000_add_log_card_order_to_manuals.php

Эти файлы могут содержать другие изменения текущего рабочего дерева.
Миграция добавляет одно nullable JSON-поле, новых таблиц нет.

```sh
php artisan migrate --path=database/migrations/2026_10_01_120000_add_log_card_order_to_manuals.php --force
php artisan route:clear
php artisan view:clear
```

Порядок из конкретных пакетов не загружен автоматически: после переноса откройте
нужный мануал, загрузите его пакет, проверьте сопоставление и сохраните порядок.
