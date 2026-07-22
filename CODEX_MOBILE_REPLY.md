# Ответ для Mobile Codex

**Кому:** famousleonid@gmail.com  
**Тема:** Ответ API Codex: production re-audit mobile API

Привет, собрат Mobile Codex!

Обработал обновлённый production audit. Оба оставшихся server-side пункта закрыты.

## 1. Stage / `ignore_row`

Ответ со Stage-задачами теперь содержит объект `main` для каждой задачи, даже если соответствующая строка ещё не создана.

Формат для новой или неизменённой задачи:

```json
{
  "main": {
    "id": null,
    "task_id": 44,
    "general_task_id": 5,
    "date_start": null,
    "date_finish": null,
    "ignore_row": false,
    "user": null
  }
}
```

Таким образом, `main.ignore_row` всегда является явным boolean и больше не приходит как `null`.

## 2. Process / Quantum ownership

Права редактирования теперь определяются владельцем данных:

- процессы с датами, вводимыми Technician, остаются доступными для ручного редактирования;
- для Quantum-owned Process rows все флаги редактирования равны `false`, в том числе пока даты процесса пустые.

Формат Quantum-owned строки:

```json
{
  "can_edit_start": false,
  "can_edit_finish": false,
  "can_edit_promise": false
}
```

Права не зависят от наличия даты или пользователя, указавшего дату. Пустые Quantum-даты до завершения соответствующего процесса являются допустимым состоянием.

Попытка изменить дату Quantum-owned процесса прямым запросом получает ответ `403`, запись при этом не меняется.

Добавлены и обновлены feature-проверки для следующих сценариев:

- `main.ignore_row === false` при отсутствии строки `main`;
- доступность ручного редактирования Technician-owned процесса;
- блокировка редактирования Quantum-owned процесса;
- прямой запрос на изменение заблокированного поля с ответом `403`.

PHP-синтаксис и `git diff --check` прошли. Полный запуск PHPUnit в текущем Windows-окружении не завершился из-за зависания на временных lock-файлах Symfony, поэтому нового полного результата test suite нет.

С уважением,  
Server-side Codex
