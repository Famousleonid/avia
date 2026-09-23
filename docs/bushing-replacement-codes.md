# Коды замены втулок в PRL

При выборе втулки рядом с checkbox появляется список кодов из справочника Codes. После выбора отображается буква; нажатие на неё открывает список снова. Код сохраняется вместе с формой Bushing Data и выводится в PRL. У вариантов одной группы с разными причинами замены будут отдельные строки PRL.

Для существующих записей код остаётся пустым до выбора пользователем. Изменение кода у втулки с начатым RO сохраняет её количество, процессы и даты. Строки комплекта сохраняют обозначение KIT.

## Перенос на production

Сначала скопировать и выполнить новую миграцию, затем переносить код приложения. Production в этой задаче не изменялся.

```bash
cd /home/mcxq8917/aviatechnik.ca
php artisan migrate --path=database/migrations/2026_09_22_180000_add_codes_id_to_wo_bushing_lines.php --force
```

Файлы изменения:

- `database/migrations/2026_09_22_180000_add_codes_id_to_wo_bushing_lines.php`
- `app/Models/WoBushingLine.php`
- `app/Services/WoBushingRelationalSync.php`
- `app/Http/Controllers/Admin/WoBushingController.php`
- `app/Http/Controllers/Admin/TdrPrintFormController.php`
- `resources/views/admin/wo_bushings/partials/replacement-code-assets.blade.php`
- `resources/views/admin/wo_bushings/partials/create-form.blade.php`
- `resources/views/admin/wo_bushings/create.blade.php`
- `resources/views/admin/wo_bushings/edit.blade.php`

Эти файлы содержат также предшествующие изменения проекта. Для них сохраняются ранее описанные требования к миграциям, в том числе `2026_09_22_160000_add_identity_name_to_process_names.php`.

Проверка: выбрать втулку, выбрать код, сохранить Bushing Data, открыть форму повторно и проверить букву, затем открыть PRL и проверить CODE у той же детали.
