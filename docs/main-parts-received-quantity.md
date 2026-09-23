# Received Qty в Parts

Рядом с QTY добавлено полученное количество Rec. QTY. Редактируют только Manager и Admin. Целое число от 0 до заказанного количества; пустое значение означает, что количество ещё не указано. Каждое изменение записывается в Activity Log (`part_receipt`) со старым и новым значением и автором.

Полное получение: количество равно QTY, источник/PO заполнен, дата получения заполнена. Один критерий применяется к зелёной подсветке, счётчику Parts и фильтрам Received/Pending. Старые записи автоматически не считаются полностью полученными — количество нужно ввести явно.

## Перенос вручную

Сначала скопировать миграцию и выполнить на сервере из каталога приложения:

```bash
php artisan migrate --path=database/migrations/2026_09_23_210000_add_received_qty_to_workorder_part_receipts.php --force
```

Затем перенести:

- `app/Services/WorkorderPartsList.php`
- `app/Http/Controllers/Admin/WorkorderPartReceiptController.php`
- `app/Http/Controllers/Admin/MainController.php`
- `resources/views/admin/mains/partials/modals.blade.php`
- `resources/views/admin/mains/partials/js/mains-parts-training.blade.php`
- `resources/views/admin/mains/partials/styles.blade.php`

Предыдущие изменения Parts и `PartReceiptAudit.php` должны быть установлены. Миграция применена локально; продакшин не изменялся.
