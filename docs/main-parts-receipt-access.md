# Права и журнал изменений Parts

Изменение источника получения, PO и даты доступно только ролям Admin и Manager. Остальные роли видят заблокированные поля. Сервер проверяет роль независимо от интерфейса, включая старый маршрут updatePartField и создание/отмену Transfer.

Журнал: существующий Activity Log, категория `part_receipt`. Записываются WO, идентификатор строки KIT/PRL, автор, время, старые и новые значения. Запись выполняется в одной транзакции с изменением данных. Повторное сохранение того же значения не создаёт запись. История начинается после установки этого изменения; авторы прежних значений не восстанавливаются задним числом.

Для переноса вручную скопировать:

- `app/Services/PartReceiptAudit.php` — новый файл
- `app/Http/Controllers/Admin/WorkorderPartReceiptController.php`
- `app/Http/Controllers/Admin/LogCardTransferController.php`
- `app/Http/Controllers/Admin/TransferController.php`
- `app/Http/Controllers/Admin/TdrController.php`
- `resources/views/admin/mains/partials/modals.blade.php`

Дополнительная миграция не нужна. Предыдущие изменения KIT/PRL и Transfer должны быть установлены. Продакшин в этой задаче не изменялся.
