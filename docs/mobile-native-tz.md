# Mobile Native TZ

## Цель

Сделать нативный iOS-клиент, который визуально и по поведению повторяет текущий web mobile интерфейс Laravel.

Источник истины по визуалу:

- `resources/views/auth/login.blade.php`
- `resources/views/mobile/master.blade.php`
- `resources/views/components/mobile-menu.blade.php`
- `resources/views/mobile/pages/*.blade.php`

Источник истины по данным:

- `routes/api.php`
- `app/Http/Controllers/Api/Mobile/MobileApiController.php`

## Root Cause текущего разрыва web mobile vs native

Раньше mobile API покрывал только часть мобильного сценария:

- login/profile/workorders/draft/tasks/components/processes/materials

Но важная логика нативного UX жила только в Blade/web mobile:

- верхнее mobile menu по ролям
- splash/login metadata
- paint mobile screens
- machining mobile screens
- lost parts flow
- owner message из paint

Из-за этого Xcode-клиенту пришлось бы угадывать shell/navigation по web-шаблонам. Это устранено: в API добавлены публичный app config, server-driven navigation и недостающие endpoint'ы paint/machining.

## Что уже есть в API

### Public

- `GET /api/mobile/public/app-config`

Назначение:

- splash screen
- стартовый login screen
- базовая app branding metadata

Ответ содержит:

- app name
- dark theme flag
- favicon url
- hero image url
- login background gradient colors
- login title / labels
- `remember_me_mode = client_token_persistence`
- `forgot_password_url`
- initial route = `login`

### Auth

- `POST /api/mobile/auth/login`
- `POST /api/mobile/auth/logout`

### Bootstrap

- `GET /api/mobile/bootstrap`

Теперь bootstrap возвращает:

- `user`
- `menu_mode`
- `available_menu_modes`
- `media_groups`
- `date_format`
- `display_date_format = dd/mmm/yyyy`
- `photo_upload`
- `navigation`
- `screens`

Дополнительно важно:

- `screens.draft_create.visible_flags`
- `screens.draft_create.supported_api_flags`
- `screens.draft_create.pending_unit_quick_fields`
- `screens.workorder_parts.component_create_fields`
- `screens.workorder_parts.component_edit_fields`

### Core workorders

- `GET /api/mobile/workorders`
- `GET /api/mobile/workorders/{id}`
- `PATCH /api/mobile/workorders/{id}/storage`
- `GET /api/mobile/workorders/{id}/media`
- `POST /api/mobile/workorders/{id}/media`
- `DELETE /api/mobile/workorders/{id}/media/{media}`

### Draft

- `GET /api/mobile/draft/options`
- `POST /api/mobile/drafts`
- `POST /api/mobile/draft-units`

### Tasks / parts / process / materials

- `GET /api/mobile/workorders/{id}/tasks`
- `PUT /api/mobile/workorders/{id}/tasks/{task}/dates`
- `GET /api/mobile/workorders/{id}/components`
- `POST /api/mobile/workorders/{id}/components`
- `PATCH /api/mobile/components/{component}`
- `POST /api/mobile/components/{component}/photo`
- `POST /api/mobile/workorders/{id}/component-attachments`
- `PATCH /api/mobile/component-attachments/{tdr}`
- `DELETE /api/mobile/component-attachments/{tdr}`
- `GET /api/mobile/workorders/{id}/processes`
- `PATCH /api/mobile/tdr-processes/{tdrProcess}/dates`
- `GET /api/mobile/materials`
- `PATCH /api/mobile/materials/{material}`

### Новое для native parity

- `GET /api/mobile/paint`
- `POST /api/mobile/paint/lost`
- `DELETE /api/mobile/paint/lost/{paint}`
- `POST /api/mobile/paint/messages`
- `GET /api/mobile/machining`
- `GET /api/mobile/machining/workorders/{id}`
- `PATCH /api/mobile/machining/steps/{machiningWorkStep}`
- `POST /api/mobile/machining/workorders/{id}/photos`
- `GET /api/mobile/machining/workorders/{id}/photos`
- `POST /api/mobile/machining/workorders/{id}/doc-pdfs`
- `GET /api/mobile/machining/workorders/{id}/pdfs`
- `DELETE /api/mobile/machining/workorders/{id}/media/{media}`

## Общие UI правила

### Theme

- Базовая тема: dark
- Основа фона: почти чёрный / тёмно-серый
- Accent: cyan / info-blue
- Success: green
- Warning: yellow/orange
- Draft emphasis: yellow

### Layout shell

Поведение shell в web mobile:

- фиксированный top menu
- внутренний scroll только у content area
- body без общего scroll
- fullscreen mobile app feel

В native повторить так:

- `NavigationStack` не использовать как стандартный iOS navbar
- кастомный top bar
- контент скроллится внутри экрана
- safe area учитывать сверху и снизу

### Dates

Критично: current web mobile по датам не полностью унифицирован.

Фактическая картина в Blade:

- draft open date input и task flatpickr ближе к `dd/mmm/yyyy`
- profile birthday и paint/machining custom date chips часто показывают `dd.mmm.yyyy`
- workorder header blocks местами используют `d-M-Y`
- process screen использует native `<input type="date">`, там визуал зависит от системного control

Поэтому нельзя выводить одну глобальную формулу "web везде показывает одинаково".

Во внутреннем API:

- ISO `YYYY-MM-DD` для task/process dates

Рекомендация для native:

- transport/storage: только ISO там, где это API contract
- exact parity mode: повторять screen-specific visual format из этого ТЗ
- если продукт захочет унифицировать все пользовательские даты в `dd/mmm/yyyy`, это отдельное UX-решение, не текущее web parity

## Экран 1. Splash

Источник:

- новый `GET /api/mobile/public/app-config`

Визуально:

- тёмный экран
- логотип/иконка бренда по центру
- без лишних CTA
- затем переход на Login

## Экран 2. Login

Источник web:

- `resources/views/auth/login.blade.php`

Состав:

- title `Login`
- поле `Email Address`
- поле `Password`
- кнопка `Login`

В native:

- один центрированный card-like блок
- синий градиентный фон
- airplane hero image как в `front.master`
- в header card есть close icon как в web login
- primary button
- серверная авторизация только через `POST /api/mobile/auth/login`
- `Remember me` и `Forgot password` считать поддержанными по metadata
- `Remember me` в native трактовать как client-side policy хранения bearer token:
  - `on`: хранить token между cold starts
  - `off`: не восстанавливать сессию после полного закрытия app
- `Forgot password` брать из `public.app-config.auth.forgot_password_url`
- важно: mobile API login не ждёт отдельный server-side параметр `remember`

После успешного логина:

1. сохранить bearer token
2. вызвать `GET /api/mobile/bootstrap`
3. собрать shell/menu по `navigation` и `screens`

## Верхнее mobile menu

Источник web:

- `resources/views/components/mobile-menu.blade.php`

Важно:

- menu зависит не только от роли, но и от контекста экрана
- использовать:
  - `bootstrap.menu_mode` как default mode
  - `bootstrap.available_menu_modes`
  - `bootstrap.navigation.top_menu_modes`
  - если конкретный screen endpoint вернул свой `menu_mode` и `top_menu`, приоритет у screen endpoint

### Роль Paint

Пункты:

- `WO`
- `Lost`
- `Profile`
- `Logout`

### Роль Machining

Пункты:

- `WO`
- `My WO` toggle
- `Profile`
- `Logout`

На machining workorder screen дополнительно:

- `Hide closed` toggle

### Обычный режим workorders

Если роль не `Shipping`:

- `WO`
- `Material`
- `Profile`
- `Logout`

Если роль `Shipping|Manager|Admin`:

- есть `Create Draft`

Точное поведение для shipping:

- `Shipping` в web mobile не видит `Material` и `Profile`
- у `Shipping` меню: `WO`, `Create Draft`, `Logout`

Точное поведение для manager/admin:

- дефолтный режим после login: `workorders`
- при этом они могут открывать `paint` и `machining`
- web mobile не показывает им прямую кнопку перехода в эти секции из default top menu
- поэтому native не должен автоматически добавлять `Paint/Machining` в верхнее меню workorders-режима
- если нужен доступ в эти секции, использовать `available_sections` и отдельный section picker / internal navigation

### Workorder detail submenu

Источник web:

- тот же `mobile-menu.blade.php`

Пункты:

- всегда `Workorder`
- если не `Shipping`:
  - `Tasks`
  - `Parts`
  - `Process`

## Экран 3. Workorders list

Источник:

- `resources/views/mobile/pages/index.blade.php`

Визуал:

- fixed search/filter bar под top menu
- крупные карточки-кнопки workorder
- номер WO крупным шрифтом по центру
- open WO cyan
- done WO secondary/grey
- draft WO yellow border/text

Фильтры:

- search by number
- `All`
- `Done`
- `Draft` только для `Shipping|Manager|Admin`

Правила:

- по умолчанию показываются только свои WO
- `All` включает чужие
- `Done` включает completed
- `Draft` переключает в режим только draft

API:

- `GET /api/mobile/workorders?scope=my|all|done|draft&search=...`

## Экран 4. Workorder detail

Источник:

- `resources/views/mobile/pages/show.blade.php`

Структура:

1. Header block с основной информацией
2. Storage block
3. Media groups table

### Header block

Показывать:

- number
- draft/done/open visual state
- owner name
- open date
- p/n
- s/n
- component/unit name
- customer
- manual lib
- instruction
- manual number
- approved badge
- done date

API:

- `GET /api/mobile/workorders/{id}`

### Storage block

Показывать:

- current storage location
- edit pencil only if `storage.can_update = true`
- поля `Rack / Level / Column`

API:

- read from `workorder.storage`
- save via `PATCH /api/mobile/workorders/{id}/storage`

### Media groups block

Поведение web:

- слева список групп media
- по центру thumbnail/count выбранной группы
- справа одна большая camera action

В native можно сделать проще, но эквивалентно:

- segmented list of media groups
- thumbnail strip/grid
- photo count badge
- camera upload button

API:

- `workorder.media_groups`
- `GET /api/mobile/workorders/{id}/media?category=...`
- `POST /api/mobile/workorders/{id}/media`
- `DELETE /api/mobile/workorders/{id}/media/{media}`

## Экран 5. Tasks

Источник:

- `resources/views/mobile/pages/tasks.blade.php`

Визуал:

- accordion по general task groups
- внутри группы task rows
- по каждой задаче `Start` и `Finish`
- если дата есть: зелёная подложка
- если restricted field: lock icon

Права:

- `Approved` и `Completed` finish нельзя менять обычному technician
- можно только `Admin|Manager`

API:

- `GET /api/mobile/workorders/{id}/tasks`
- `PUT /api/mobile/workorders/{id}/tasks/{task}/dates`

## Экран 6. Parts

Источник:

- `resources/views/mobile/pages/components.blade.php`

Визуал:

- список component cards
- avatar слева
- название + IPL + P/N
- add parts button сверху
- внутри карточки swipe rows по TDR attachment

Поведение:

- tap по названию открывает edit component
- edit component включает:
  - `Name`
  - `IPL`
  - `P/N`
  - `Is Bushing`
  - `Log card`
  - `Bush IPL`
  - camera button для обновления photo
- quick create inside picker включает:
  - `IPL`
  - `P/N`
  - `Name`
  - `Is Bushing`
  - `Log card`
  - `Bush IPL`
  - `Photo`
- add/edit/delete attachment
- upload component photo
- attachment form rules:
  - если code содержит `missing`:
    - показывать `qty`
    - `necessaries` не нужен
  - если code не `missing`:
    - показывать `necessaries`
    - при `Order new` показывать `qty`
    - при `Repair` показывать `serial number`

API:

- `GET /api/mobile/workorders/{id}/components`
- `POST /api/mobile/workorders/{id}/components`
- `PATCH /api/mobile/components/{component}`
- `POST /api/mobile/components/{component}/photo`
- `POST /api/mobile/workorders/{id}/component-attachments`
- `PATCH /api/mobile/component-attachments/{tdr}`
- `DELETE /api/mobile/component-attachments/{tdr}`

## Экран 7. Process

Источник:

- `resources/views/mobile/pages/process.blade.php`

Визуал:

- список parts
- под каждым mini-table процессов
- колонки:
  - process name
  - sent
  - returned

API:

- `GET /api/mobile/workorders/{id}/processes`
- `PATCH /api/mobile/tdr-processes/{tdrProcess}/dates`

## Экран 8. Materials

Источник:

- `resources/views/mobile/pages/materials.blade.php`

Это обязательно показывать в native.

Визуал:

- header `Materials (count)`
- search input
- table:
  - Code
  - Material
  - Specification
  - Description

Поведение:

- поиск локально по уже загруженным данным или server-side search
- editable только поле `Description`
- после blur/save web кратко подсвечивает строку green

API:

- `GET /api/mobile/materials?search=...`
- `PATCH /api/mobile/materials/{material}`

Роли:

- в top menu видно не для `Shipping`

## Экран 9. Profile

Источник:

- `resources/views/mobile/pages/profile.blade.php`

Состав:

- avatar
- name
- team
- phone
- birthday
- email read-only
- stamp
- team select
- avatar upload
- save / cancel
- change password modal

Уточнение:

- визуально есть option `No team selected`
- но и web, и API на save требуют валидный `team_id`
- поэтому native не должен отправлять пустой `team_id` как нормальный успешный сценарий

API:

- `GET /api/mobile/profile`
- `PUT /api/mobile/profile`
- `POST /api/mobile/profile/password`

## Экран 10. Create Draft

Источник:

- `resources/views/mobile/pages/createdraft.blade.php`

Состав:

- Draft number preview
- Open date
- Unit select + Add
- Customer
- Serial number
- Description
- Customer PO
- Storage:
  - Rack
  - Level
  - Column
- flags:
  - External Damage
  - Name Plate Missing
- Box modal

Важно по parity:

- current web mobile на этом экране показывает только 2 checkbox-флага:
  - `External Damage`
  - `Name Plate Missing`
- API поддерживает больше draft boolean fields:
  - `received_disassembly`
  - `disassembly_upon_arrival`
  - `extra_parts`
- для exact parity первого релиза native повторять именно текущий web mobile screen, а не автоматически раскрывать все server-supported flags

Pending unit modal:

- в current web mobile быстрый add pending unit реально просит только `Part Number`
- API умеет принять ещё `name` и `description`, но это не используется текущим mobile Blade

### Box block

Это обязательно для native parity.

Поля:

- hidden value `arrival_box_status`
- hidden value `arrival_box_notes`

Статусы:

- `ok`
- `easy`
- `medium`
- `hard`
- `replace`

UI:

- кнопка `Box`
- рядом summary:
  - status label
  - notes preview

API:

- `GET /api/mobile/draft/options`
- `POST /api/mobile/drafts`
- `POST /api/mobile/draft-units`

## Экран 11. Paint WO

Источник:

- `resources/views/mobile/pages/paint.blade.php`

Таблица:

- queue column
- WO
- Detail
- Start
- Finish

Поведение:

- `Hide closed rows` toggle
- WO строка открывает modal owner message
- даты редактируются inline
- если дата заполнена, visual green

Owner message modal:

- owner
- detail
- message textarea
- send button

API:

- `GET /api/mobile/paint?tab=wo`
- `PATCH /api/mobile/tdr-processes/{tdrProcess}/dates`
- `POST /api/mobile/paint/messages`

## Экран 12. Paint Lost

Источник:

- та же `paint.blade.php`, tab `lost`

Визуал:

- coverflow-like carousel cards
- фото
- PN
- serial/comment
- delete button
- add lost part button снизу

Add modal:

- part number
- serial
- comment
- photo

API:

- `GET /api/mobile/paint?tab=lost`
- `POST /api/mobile/paint/lost`
- `DELETE /api/mobile/paint/lost/{paint}`

## Экран 13. Machining list

Источник:

- `resources/views/mobile/pages/machining.blade.php`

Визуал:

- сверхкомпактная таблица
- queue слева
- большая outline button с номером WO справа

Toggle:

- `My WO`

API:

- `GET /api/mobile/machining?my_wo=0|1`

## Экран 14. Machining workorder detail

Источник:

- `resources/views/mobile/pages/machining-workorder.blade.php`

Состав:

- title `WO {number}`
- action buttons:
  - `Photo`
  - `Doc`
- links:
  - `Machining photos`
  - `PDFs`
- blocks по детали / step group / step

Показывать:

- detail name
- PN
- SN
- process labels
- sent date
- step start
- step finish
- machinist name
- optional description/note

Поведение:

- шаг редактирует только назначенный machinist
- `Hide closed` скрывает завершённые step blocks

API:

- `GET /api/mobile/machining/workorders/{id}`
- `PATCH /api/mobile/machining/steps/{machiningWorkStep}`
- `POST /api/mobile/machining/workorders/{id}/photos`
- `POST /api/mobile/machining/workorders/{id}/doc-pdfs`

## Экран 15. Machining photos

Источник:

- `resources/views/mobile/pages/machining-workorder-photos.blade.php`

Визуал:

- grid 3 columns
- thumbnail square cells
- delete on each cell
- gallery open on tap

API:

- `GET /api/mobile/machining/workorders/{id}/photos`
- `DELETE /api/mobile/machining/workorders/{id}/media/{media}`

## Экран 16. Machining PDFs

Источник:

- `resources/views/mobile/pages/machining-workorder-pdfs.blade.php`

Визуал:

- vertical card list
- document label
- created_at
- buttons:
  - Open
  - Download
  - Delete

API:

- `GET /api/mobile/machining/workorders/{id}/pdfs`
- `DELETE /api/mobile/machining/workorders/{id}/media/{media}`

## JSON error contract

Единый формат ошибок:

- `401`: unauthenticated
- `403`: forbidden
- `404`: not found
- `422`: validation failed

Пример:

```json
{
  "ok": false,
  "message": "Validation failed.",
  "errors": {
    "field": ["message"]
  }
}
```

## Что важно для iOS-реализации

1. Не угадывать меню локально по hardcode.
Использовать `bootstrap.navigation` и `bootstrap.screens`.

Отдельно использовать:

- `bootstrap.navigation.top_menu_modes.workorders`
- `bootstrap.navigation.top_menu_modes.paint`
- `bootstrap.navigation.top_menu_modes.machining`
- `bootstrap.navigation.available_sections`

2. Не использовать browser storage.
Если появится задача сохранять не критичное UI state для app/web parity, это должно жить server-side per-user.

3. Даты для task/process отправлять только ISO.

4. Не предполагать один глобальный display format для всех экранов.
Использовать screen-specific parity:

- login/draft/task inputs: ближе к `dd/mmm/yyyy`
- profile/paint/machining custom chips: ближе к `dd.mmm.yyyy`
- workorder info headers в web сейчас legacy и местами используют `d-M-Y`

5. Material screen обязателен в native.

6. Box-поля draft WO обязательны в native.

## Проверка после внедрения

Минимальный smoke checklist:

1. Login → bootstrap → correct top menu by role
2. Workorders list filters
3. Workorder detail + storage + media upload
4. Tasks date editing + restricted finish behavior
5. Parts CRUD + attachments
6. Process dates editing
7. Materials search + description edit
8. Profile update + password change
9. Draft create + box status/notes
10. Paint WO + owner message
11. Paint Lost carousel + add/delete
12. Machining list + my WO toggle
13. Machining WO steps + photo/doc upload
14. Machining photos/PDFs listing + delete
