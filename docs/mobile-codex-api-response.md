# API response for Mobile Codex

Готово по API.

## Task dates

`PUT /api/mobile/workorders/{workorder}/tasks/{task}/dates`

- принимает `date_start`, `date_finish`, `ignore_row`;
- `null` у дат очищает поле, пропущенное поле сохраняет старое значение;
- смена `ignore_row` не стирает даты;
- ответ: `data.main` с `date_start`, `date_finish`, `ignore_row`.

`GET /api/mobile/workorders/{workorder}/tasks`

Каждая задача возвращает:

```json
{
  "has_start_date": true,
  "can_edit_start": true,
  "can_edit_finish": true,
  "restricted_finish": false,
  "restriction_code": null,
  "main": {
    "date_start": "2026-07-03",
    "date_finish": "2026-07-19",
    "ignore_row": false
  }
}
```

Для `WO Submitted for Quote` и legacy `WO Submitted for Quate` у Technician/Team Leader оба `can_edit_* = false`; прямой PUT вернёт `403`.

`restriction_code`: `manager_only_quote_submission_dates`.

## Components / TDR

`GET /api/mobile/workorders/{workorder}/components`

- добавлен основной ключ `data.attached_components`;
- формат строки: компонент с `id`, `ipl_num`, `part_number`, `name`, `tdrs: []`;
- TDR содержит `id`, `code_id`, `code_name`, `necessaries_id`, `necessaries_name`, `qty`, `serial_number`;
- старый `data.components` пока оставлен как alias для совместимости.

## Processes

`GET /api/mobile/workorders/{workorder}/processes`

У каждого процесса теперь всегда есть boolean:

```json
{
  "can_edit_start": true,
  "can_edit_finish": true,
  "can_edit_promise": true
}
```

`PATCH /api/mobile/tdr-processes/{process}/dates` применяет те же права; изменение locked-поля возвращает `403`.

`null` очищает дату, отсутствие поля её сохраняет.

## Review account

- серверно ограничен synthetic WO №`100500`, включая прямые WO/TDR/media URLs;
- в login/bootstrap capabilities возвращаются:

```json
{
  "can_view_all_workorders": false,
  "can_view_done_workorders": false
}
```

Также добавлены публичные `/privacy` и `/support`.

Проверка backend: `php artisan test --filter=MobileApiTest` — 25 passed.

Отдельно: в локальной БД нет `appreview@aviatechnik.ca`, поэтому самого пользователя, пароль и наполнение demo-данными/фото нужно создать на production отдельно.
