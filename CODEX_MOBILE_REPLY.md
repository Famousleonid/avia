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

---

# Дополнение: Mobile Swift Log Card API

**Статус на 01/aug/2026:** серверная часть готова для просмотра, создания, заполнения и полного редактирования Log Card по тем же правилам, что desktop-вкладка TDR.

Все запросы ниже используют Bearer token и стандартный envelope:

```json
{
  "ok": true,
  "data": {},
  "meta": {},
  "message": null
}
```

## Маршруты

```text
GET   /api/mobile/workorders/{workorderId}/log-card
GET   /api/mobile/workorders/{workorderId}/log-card/template
POST  /api/mobile/workorders/{workorderId}/log-card
PUT   /api/mobile/workorders/{workorderId}/log-card

PATCH /api/mobile/log-card/{logCardId}/rows/{rowIndex}
PATCH /api/mobile/log-card/{logCardId}/rows/{rowIndex}/variant
PATCH /api/mobile/log-card/{logCardId}/rows/{rowIndex}/assembly
```

Android-контур получает те же маршруты под `/api/android/*`.

## Получение сохранённой карточки

`GET /api/mobile/workorders/{workorderId}/log-card` возвращает:

- `exists` и `log_card_id`;
- `read_only`, `can_edit`, `read_only_message`;
- краткие данные WO;
- справочник `codes` для Reason to Removed;
- упорядоченный массив `rows`;
- варианты детали в той же IPL-группе;
- доступные assemblies для каждого компонента;
- подсказки причины из TDR для Missing и Order New.

Строки-разделители manual имеют `kind: "manual"`. Рабочие строки имеют `kind: "component"` и поле `index`. Для PATCH необходимо отправлять именно этот `index`, поскольку он соответствует позиции строки в сохранённом `component_data`.

## Шаблон создания и редактирования состава

Основной manual:

```text
GET /api/mobile/workorders/{workorderId}/log-card/template
```

Дополнительный manual, аналог desktop-кнопки **Add another manual**:

```text
GET /api/mobile/workorders/{workorderId}/log-card/template?manual_id={manualId}
```

Ответ содержит:

- `manual` и `is_primary_manual`;
- `available_manuals`;
- `groups` — IPL-группы с вариантами;
- `separate` — отдельные строки для компонентов с несколькими units;
- `assemblies` внутри каждого варианта;
- `hint` из TDR.

Для основного manual применяется unit/IPL branch rule. Для дополнительного manual desktop-правило не применяет фильтр по основному юниту.

## Создание и полное обновление

Создание:

```text
POST /api/mobile/workorders/{workorderId}/log-card
```

Полная замена выбранного состава и введённых данных:

```text
PUT /api/mobile/workorders/{workorderId}/log-card
```

Формат обоих запросов:

```json
{
  "rows": [
    {
      "component_id": 123,
      "manual_id": 45,
      "ipl_group": "1-190",
      "included": true,
      "serial_number": "CPS1278",
      "assy_serial_number": "ASSY-SN",
      "reason": "7",
      "new_serial_number": "NEW-SN",
      "component_assembly_id": 88,
      "unit_index": null,
      "units_assy": null
    }
  ]
}
```

`manual_id` можно не передавать: сервер определяет его по компоненту. Для строки из `separate` следует передать `unit_index` и `units_assy`; для обычной вариантной строки используется `ipl_group`.

Сервер сам:

- проверяет, что компонент разрешён для Log Card;
- проверяет принадлежность component/manual/assembly;
- применяет IPL branch rule основного юнита;
- группирует строки по manuals и добавляет разделители;
- подставляет assembly по правилам desktop;
- выполняет ту же нормализацию и activity logging, что desktop `LogCardController`.

Успешный ответ:

```json
{
  "ok": true,
  "data": {
    "log_card_id": 10,
    "rows_count": 2
  },
  "meta": {},
  "message": "Log Card created."
}
```

Для PUT сообщение будет `Log Card updated.`.

## Точечное редактирование сохранённой строки

Обычное поле:

```text
PATCH /api/mobile/log-card/{logCardId}/rows/{rowIndex}
```

```json
{
  "field": "serial_number",
  "value": "CPS1278"
}
```

Поддерживаемые поля:

```text
included
serial_number
assy_serial_number
reason
new_serial_number
```

Для `included` можно передавать настоящий JSON boolean.

Смена варианта в той же IPL-группе:

```text
PATCH /api/mobile/log-card/{logCardId}/rows/{rowIndex}/variant
```

```json
{
  "component_id": 456
}
```

Смена assembly текущего компонента:

```text
PATCH /api/mobile/log-card/{logCardId}/rows/{rowIndex}/assembly
```

```json
{
  "component_assembly_id": 89
}
```

## Права и блокировки

- После заполнения даты Post Disassembly inspection для Technician и Team Leader редактирование блокируется.
- GET остаётся доступным и возвращает `read_only: true`, `can_edit: false` и пояснение.
- POST, PUT и PATCH в заблокированном состоянии возвращают `423`.
- Ограниченный review-аккаунт получает `404` для Log Card чужого/production WO даже при известном `log_card_id`.

## Проверка

```text
MobileApiTest: 29 passed
AndroidApiTest: 7 passed
```

Отдельно проверены:

- шаблон основного и дополнительного manuals;
- создание карточки из нескольких manuals;
- просмотр сохранённых значений;
- полная замена состава через PUT;
- выбор assembly;
- JSON boolean для `included`;
- защита review-аккаунта;
- обратная совместимость Android API.

## Полный data contract для Swift-моделей

### Общие правила типов

```text
ID                         JSON number / Swift Int
included                   JSON boolean
read_only, can_edit        JSON boolean
пользовательские поля      JSON string, пустое значение = ""
невыбранный внешний ID     null
пустой массив              []
даты WO                    "yyyy-MM-dd" или null
meta                       JSON object, обычно {}
```

Сервер не возвращает `null` вместо boolean для `included`, `read_only` и `can_edit`.

### GET сохранённой карточки

```http
GET /api/mobile/workorders/321/log-card
Authorization: Bearer <token>
Accept: application/json
```

Пример полного ответа:

```json
{
  "ok": true,
  "data": {
    "exists": true,
    "log_card_id": 10,
    "workorder": {
      "id": 321,
      "number": 107900,
      "number_display": "107 900",
      "is_draft": false,
      "is_done": false,
      "done_at": null,
      "open_at": "2026-08-01",
      "approved": true
    },
    "read_only": false,
    "can_edit": true,
    "read_only_message": null,
    "codes": [
      {
        "id": 7,
        "name": "Corroded"
      }
    ],
    "rows": [
      {
        "index": 0,
        "kind": "manual",
        "label": "CMM 32-10-01 Landing Gear"
      },
      {
        "index": 1,
        "kind": "component",
        "component": {
          "id": 123,
          "component_id": 123,
          "manual_id": 45,
          "name": "CYLINDER ASSY",
          "part_number": "47401-1",
          "ipl_num": "1-190",
          "units_assy": "1",
          "assemblies": [
            {
              "id": 88,
              "assy_part_number": "47400-1",
              "assy_ipl_num": "1-10",
              "units_assy": "1"
            }
          ]
        },
        "manual_id": 45,
        "ipl_group": "1-190",
        "included": true,
        "serial_number": "CPS1278",
        "assy_serial_number": "ASSY-SN",
        "reason": "7",
        "new_serial_number": "NEW-SN",
        "component_assembly_id": 88,
        "assy_part_number": "47400-1",
        "assy_ipl_num": "1-10",
        "units_assy": "1",
        "unit_index": null,
        "hint": {
          "code_id": 7,
          "label": "Corroded",
          "kind": "order_new"
        },
        "variants": [
          {
            "id": 123,
            "component_id": 123,
            "manual_id": 45,
            "name": "CYLINDER ASSY",
            "part_number": "47401-1",
            "ipl_num": "1-190",
            "units_assy": "1",
            "assemblies": [],
            "allowed": true
          }
        ]
      }
    ]
  },
  "meta": {},
  "message": null
}
```

Если карточка ещё не создана:

```json
{
  "exists": false,
  "log_card_id": null,
  "codes": [],
  "rows": []
}
```

`hint` имеет один из вариантов:

```json
null
```

```json
{
  "code_id": 1,
  "label": "Missing",
  "kind": "missing"
}
```

```json
{
  "code_id": 7,
  "label": "Corroded",
  "kind": "order_new"
}
```

### GET шаблона

```http
GET /api/mobile/workorders/321/log-card/template?manual_id=45
```

```json
{
  "ok": true,
  "data": {
    "exists": false,
    "read_only": false,
    "can_edit": true,
    "read_only_message": null,
    "manual": {
      "id": 45,
      "number": "CMM 32-10-01",
      "title": "Landing Gear",
      "label": "CMM 32-10-01 Landing Gear"
    },
    "is_primary_manual": true,
    "available_manuals": [
      {
        "id": 45,
        "number": "CMM 32-10-01",
        "title": "Landing Gear",
        "label": "CMM 32-10-01 Landing Gear"
      }
    ],
    "groups": [
      {
        "ipl_group": "1-190",
        "variants": [
          {
            "id": 123,
            "component_id": 123,
            "manual_id": 45,
            "name": "CYLINDER ASSY",
            "part_number": "47401-1",
            "ipl_num": "1-190",
            "units_assy": "1",
            "assemblies": [],
            "hint": null
          }
        ]
      }
    ],
    "separate": [
      {
        "id": 200,
        "component_id": 200,
        "manual_id": 45,
        "name": "WASHER",
        "part_number": "47409-1",
        "ipl_num": "1-100",
        "units_assy": 2,
        "assemblies": [],
        "hint": null,
        "unit_index": 1
      }
    ]
  },
  "meta": {},
  "message": null
}
```

В `separate.units_assy` следует декодировать значение как число либо строку, если Swift-модель должна поддерживать старые данные manuals. При отправке сервер принимает его как строку.

### POST и PUT: описание входных полей

| Поле | Тип | Обязательное | Значение |
|---|---|---:|---|
| `rows` | array | да | Минимум одна выбранная строка, максимум 500 |
| `component_id` | integer | да | ID выбранного компонента |
| `manual_id` | integer/null | нет | Проверочное значение; сервер также определяет manual по компоненту |
| `ipl_group` | string/null | нет | Группа обычной вариантной строки; сервер вычисляет её из IPL при отсутствии |
| `included` | boolean | нет | По умолчанию `true` |
| `serial_number` | string/null | нет | Serial Number, максимум 255 символов |
| `assy_serial_number` | string/null | нет | ASSY Serial Number, максимум 255 символов |
| `reason` | string/integer/null | нет | ID из `codes`, хранится и возвращается строкой |
| `new_serial_number` | string/null | нет | New Serial Number, максимум 255 символов |
| `component_assembly_id` | integer/null | нет | Assembly текущего компонента; при отсутствии используется первый доступный assembly |
| `unit_index` | integer/null | нет | Номер отдельной единицы, диапазон 1–999 |
| `units_assy` | string/null | нет | Общее количество единиц, максимум 100 символов |

`row_type`, `manual_label`, `assy_part_number` и `assy_ipl_num` отправлять не нужно. Сервер формирует и проверяет их сам.

POST и PUT возвращают одинаковую структуру:

```json
{
  "ok": true,
  "data": {
    "log_card_id": 10,
    "rows_count": 2
  },
  "meta": {},
  "message": "Log Card updated."
}
```

### PATCH обычного поля

Запрос:

```json
{
  "field": "reason",
  "value": 7
}
```

Ответ:

```json
{
  "ok": true,
  "data": {
    "field": "reason",
    "value": "7"
  },
  "meta": {},
  "message": null
}
```

Для `included` ответное `value` равно строке `"1"` или `"0"`, хотя в GET это поле возвращается нормальным boolean.

### PATCH варианта

Запрос:

```json
{
  "component_id": 456
}
```

Ответ:

```json
{
  "ok": true,
  "data": {
    "component_id": 456,
    "component": {
      "id": 456,
      "component_id": 456,
      "manual_id": 45,
      "name": "CYLINDER ASSY",
      "part_number": "47401-5",
      "ipl_num": "1-190A",
      "units_assy": "1",
      "assemblies": []
    },
    "component_assembly_id": null
  },
  "meta": {},
  "message": null
}
```

### PATCH assembly

Запрос:

```json
{
  "component_assembly_id": 89
}
```

Ответ:

```json
{
  "ok": true,
  "data": {
    "component_assembly_id": 89,
    "assy_part_number": "47400-5",
    "assy_ipl_num": "1-20",
    "units_assy": "2"
  },
  "meta": {},
  "message": null
}
```

### Ошибки

```text
401  Bearer token отсутствует или недействителен
404  WO/Log Card не существует либо недоступен review-аккаунту
422  Ошибка данных, чужой component/manual/assembly или недопустимый variant
423  Log Card заблокирован после Post Disassembly inspection
```

Стандартная ошибка валидации:

```json
{
  "ok": false,
  "message": "Validation failed.",
  "errors": {
    "rows.0.component_id": [
      "The selected rows.0.component id is invalid."
    ]
  }
}
```
