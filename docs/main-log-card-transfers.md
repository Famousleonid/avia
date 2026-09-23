# Parts: перевод из Log Card

Реализовано локально 23/Sep/2026. Продакшин не изменён.

В Parts выбрать Transfer from WO, ввести номер WO-источника, загрузить Log Card, выбрать подходящий P/N и S/N и подтвердить перевод. Статус WO не ограничивает выбор; мануал должен совпадать. Источник и получатель должны быть разными WO.

Перевод записывает снимок P/N, IPL, S/N и количества из выбранного источника и PO в строку Parts. Работает и для KIT/PRL без TDR. Обе Log Card остаются неизменными, искусственные TDR не создаются. Дата получения вводится отдельно. Transfer Form использует сохранённый снимок. Для изменения источника сначала отменить перевод через кнопку Transfer; отмена очищает PO и дату получения.

Перед записью сервер повторно проверяет источник и доступное количество. Уже переведённые экземпляры, Missing и детали с кодом, требующим destruction certificate, недоступны. Перевод оформляется на всё количество целевой строки из одного источника; частичные переводы одной строки из нескольких источников здесь не реализованы.

## Ручной перенос

Сначала выполнить предыдущую миграцию `2026_09_23_190000_create_workorder_part_receipts_table.php`, если ещё не выполнена. Затем скопировать новую миграцию в соответствующий каталог продакшина и выполнить из `/home/mcxq8917/aviatechnik.ca`:

```bash
php artisan migrate --path=database/migrations/2026_09_23_200000_add_log_card_source_to_transfers.php --force
```

После миграций перенести файлы, сохраняя пути (в дополнение к файлам из `main-parts-kit-prl-receipts.md`):

- `app/Services/LogCardTransferSource.php` — новый
- `app/Http/Controllers/Admin/LogCardTransferController.php` — новый
- `app/Models/Transfer.php`
- `app/Services/WorkorderPartsList.php`
- `app/Http/Controllers/Admin/WorkorderPartReceiptController.php`
- `app/Http/Controllers/Admin/TransferController.php`
- `routes/web.php`
- `resources/views/admin/mains/partials/modals.blade.php`
- `resources/views/admin/mains/partials/js/mains-parts-training.blade.php`
- `resources/views/admin/transfers/transferForm.blade.php`
- `resources/views/admin/transfers/transfersForm.blade.php`
- `resources/views/admin/transfers/partial.blade.php`

Обновить страницу Main. Исторические переводы не пересоздаются и не переписываются.

## Проверки

- Автотесты: запись без TDR, сохранение обеих Log Card, повторная отправка запроса, защита от повторного использования источника, отмена, другой мануал, изменившаяся Log Card, завершённый WO, Missing и нехватка количества.
- Браузер: локальный W107736, источник W107459, P/N 1840-0002, S/N L2615; загрузка выбора и отмена подтверждения без записи.
