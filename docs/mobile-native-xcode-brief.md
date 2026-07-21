# Brief For Codex In Xcode

## Цель

Сделать нативный iOS-клиент, который максимально точно повторяет текущее mobile web поведение Laravel-приложения.

Это не greenfield UX. Здесь важно не "сделать красивее по iOS", а не сломать уже существующую роль-логику, меню, draft flow, material flow, paint/machining сценарии и legacy formatting.

## Source Of Truth

Серверный контракт и mobile parity уже зафиксированы в Laravel:

- API routes: `routes/api.php`
- Mobile API controller: `app/Http/Controllers/Api/Mobile/MobileApiController.php`
- Full TZ: `docs/mobile-native-tz.md`

Web mobile reference по визуалу и поведению:

- Login: `resources/views/auth/login.blade.php`
- Front background shell: `resources/views/front/master.blade.php`
- Mobile shell: `resources/views/mobile/master.blade.php`
- Top menu: `resources/views/components/mobile-menu.blade.php`
- Mobile pages: `resources/views/mobile/pages/*.blade.php`

## Общий принцип реализации

Не проектировать мобильный клиент "по ощущениям". Собирать его по API metadata и по фактическому Blade behavior.

Особенно важно:

- не хардкодить верхнее меню только по роли
- не нормализовать даты "как кажется правильнее"
- не расширять draft UI до всех возможных backend fields, если их нет в current mobile web
- не добавлять в default top menu manager/admin прямые кнопки `Paint` и `Machining`, потому что web mobile так не делает

## App Launch Flow

1. На cold start вызвать `GET /api/mobile/public/app-config`
2. Показать splash/login shell по metadata
3. На login отправить `POST /api/mobile/auth/login`
4. Сохранить bearer token
5. Сразу вызвать `GET /api/mobile/bootstrap`
6. На основе `bootstrap` собрать app shell, доступные секции и role-based navigation

## Auth

### Login endpoint

- `POST /api/mobile/auth/login`

Body:

- `email`
- `password`
- `device_name?`

Response:

- `data.token`
- `data.user`

### Logout

- `POST /api/mobile/auth/logout`

Удаляет именно текущий bearer token.

### Public app config

- `GET /api/mobile/public/app-config`

Использовать для:

- app name
- dark theme
- favicon / hero image
- login labels
- background gradient
- `remember_me_supported`
- `remember_me_mode`
- `forgot_password_supported`
- `forgot_password_url`
- `show_close_button`

### Важный нюанс по Remember Me

Web login визуально имеет checkbox `Remember Me`, но mobile API login не принимает отдельный `remember` параметр.

Для iOS трактовать это как client-side политику хранения bearer token:

- если user включает `Remember me`, можно восстанавливать сессию после полного закрытия app
- если выключает, не восстанавливать persisted session после cold start

### Важный нюанс по Forgot Password

Не придумывать отдельный native flow без требования.

Использовать `public.app-config.auth.forgot_password_url` как web handoff URL.

## Bootstrap

- `GET /api/mobile/bootstrap`

Нужно читать:

- `user`
- `menu_mode`
- `available_menu_modes`
- `media_groups`
- `display_date_format`
- `navigation`
- `screens`

Особенно важны:

- `navigation.top_menu`
- `navigation.top_menu_modes.workorders`
- `navigation.top_menu_modes.paint`
- `navigation.top_menu_modes.machining`
- `navigation.workorder_detail_menu`
- `navigation.available_sections`
- `screens.draft_create`
- `screens.workorder_parts`
- `screens.materials`
- `screens.paint_workorders`
- `screens.machining_workorders`

## Roles And Capabilities

Не делать логику только по role name. Использовать и `user.role`, и `user.capabilities`.

Сервер уже отдаёт capability flags:

- `can_update_storage`
- `can_create_draft`
- `can_use_paint`
- `can_use_machining`
- `can_edit_restricted_task_finish`

### Роли по факту

#### Shipping

- видит workorders
- может создавать draft
- может редактировать storage
- не видит `Material` и `Profile` в обычном top menu
- не видит `Tasks/Parts/Process` в workorder detail submenu

#### Technician

- обычный WO flow
- видит `Material` и `Profile`
- не может создавать draft
- не может редактировать storage
- не может менять restricted finish для `Approved` и `Completed`

#### Paint

- основной режим `paint`
- top menu: `WO`, `Lost`, `Profile`, `Logout`
- может работать с paint rows
- может отправлять owner message
- может работать с lost parts

#### Machining

- основной режим `machining`
- top menu: `WO`, `My WO`, `Profile`, `Logout`
- на detail screen есть `Hide closed`
- редактировать step dates может только назначенный machinist

#### Manager / Admin

- default mode после login: `workorders`
- имеют доступ к draft
- могут открывать paint и machining sections
- web mobile не показывает им `Paint` и `Machining` как постоянные кнопки в default top menu
- для входа в эти разделы использовать `available_sections` и отдельную внутреннюю навигацию

## Top Menu Rules

### Главное правило

Top menu зависит не только от роли, но и от screen context.

Опорный порядок:

1. Если screen endpoint вернул свой `menu_mode` и `top_menu`, использовать их
2. Иначе использовать `bootstrap.menu_mode`
3. Иначе использовать `bootstrap.navigation.top_menu_modes.*`

### Workorders mode

#### Non-shipping

- `WO`
- `Material`
- `Profile`
- `Logout`

#### Shipping

- `WO`
- `Create Draft`
- `Logout`

#### Manager/Admin in workorders mode

- `WO`
- `Material`
- `Profile`
- `Create Draft`
- `Logout`

Но не добавлять туда отдельные кнопки `Paint` / `Machining`.

### Paint mode

- `WO`
- `Lost`
- `Profile`
- `Logout`

### Machining mode

- `WO`
- `My WO` toggle
- `Profile`
- `Logout`

На machining workorder detail дополнительно есть `Hide closed` toggle.

### Workorder detail submenu

Всегда:

- `Workorder`

Если роль не `Shipping`:

- `Tasks`
- `Parts`
- `Process`

## Screen-By-Screen Implementation

## 1. Splash

Использовать `public/app-config`.

Визуально:

- dark splash
- brand icon/logo
- без лишних CTA

## 2. Login

Визуально повторить web mood:

- центрированная card-like форма
- blue → deepskyblue gradient
- airplane hero image из `front.master`
- close icon в правом верхнем углу card header
- поля `Email Address`, `Password`
- button `Login`
- checkbox `Remember Me`
- link `Forgot Your Password?`

## 3. Workorders list

Endpoint:

- `GET /api/mobile/workorders?scope=my|all|done|draft&search=...`

UI:

- fixed search/filter bar под top menu
- большие WO cards
- open WO cyan
- done WO grey
- draft WO yellow

Фильтры:

- search by number
- `All`
- `Done`
- `Draft` only for `Shipping|Manager|Admin`

Поведение:

- default: только свои WO
- `All`: показывает и чужие
- `Done`: completed
- `Draft`: только draft workorders

## 4. Workorder detail

Endpoint:

- `GET /api/mobile/workorders/{id}`

Секции:

1. Header info
2. Storage
3. Media groups

### Header info

Показывать:

- number
- open / done / draft visual state
- owner
- open date
- p/n
- s/n
- component/unit name
- customer
- instruction
- manual number
- manual lib
- approved badge
- done date

### Storage

Read from:

- `workorder.storage`

Write to:

- `PATCH /api/mobile/workorders/{id}/storage`

Editable only if:

- `storage.can_update == true`

### Media groups

Использовать:

- `workorder.media_groups`
- `GET /api/mobile/workorders/{id}/media?category=...`
- `POST /api/mobile/workorders/{id}/media`
- `DELETE /api/mobile/workorders/{id}/media/{media}`

Parity note:

Web имеет layout "group label / preview-count / one camera action". В native можно сделать удобнее, но поведенчески эквивалентно.

## 5. Tasks

Endpoints:

- `GET /api/mobile/workorders/{id}/tasks`
- `PUT /api/mobile/workorders/{id}/tasks/{task}/dates`

UI:

- accordion by general task groups
- task rows
- `Start` / `Finish`
- зелёный visual if date is set
- lock visual for restricted finish

Правила:

- restricted finish for `Approved` and `Completed`
- edit allowed only for `Admin|Manager`
- читать `restricted_finish` и `can_edit_finish` из API

## 6. Parts

Endpoints:

- `GET /api/mobile/workorders/{id}/components`
- `POST /api/mobile/workorders/{id}/components`
- `PATCH /api/mobile/components/{component}`
- `POST /api/mobile/components/{component}/photo`
- `POST /api/mobile/workorders/{id}/component-attachments`
- `PATCH /api/mobile/component-attachments/{tdr}`
- `DELETE /api/mobile/component-attachments/{tdr}`

UI:

- component cards list
- avatar left
- name + IPL + P/N
- attachments inside component
- add parts flow

### Component create

Использовать поля из `screens.workorder_parts.component_create_fields`:

- `ipl_num`
- `part_number`
- `name`
- `is_bush`
- `log_card`
- `bush_ipl_num`
- `photo`

### Component edit

Использовать поля из `screens.workorder_parts.component_edit_fields`:

- `name`
- `ipl_num`
- `part_number`
- `eff_code`
- `is_bush`
- `log_card`
- `bush_ipl_num`

### Важный parity note

`log_card` должен быть editable и на create, и на update. Это уже поддержано API, не потерять на стороне Swift models/forms.

### Attachment rules

Если code содержит `missing`:

- показывать `qty`
- не требовать `necessaries`

Если code не `missing`:

- показывать `necessaries`
- если necessary содержит `order new`, показывать `qty`
- если necessary содержит `repair`, показывать `serial number`

## 7. Process

Endpoints:

- `GET /api/mobile/workorders/{id}/processes`
- `PATCH /api/mobile/tdr-processes/{tdrProcess}/dates`

UI:

- component list
- under each component mini-table of processes
- columns:
  - process name
  - sent
  - returned

## 8. Materials

Endpoints:

- `GET /api/mobile/materials?search=...`
- `PATCH /api/mobile/materials/{material}`

UI:

- header `Materials (count)`
- search field
- table:
  - `Code`
  - `Material`
  - `Specification`
  - `Description`

Behavior:

- only `Description` editable
- after save можно дать короткий success flash / green highlight

Role note:

- `Shipping` этот раздел не видит

## 9. Profile

Endpoints:

- `GET /api/mobile/profile`
- `PUT /api/mobile/profile`
- `POST /api/mobile/profile/password`

UI:

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

### Важный parity note

UI показывает option `No team selected`, но успешный save и в web, и в API требует валидный `team_id`.

То есть:

- можно визуально показать пустое состояние
- но не считать empty `team_id` допустимым save flow

## 10. Create Draft

Endpoints:

- `GET /api/mobile/draft/options`
- `POST /api/mobile/drafts`
- `POST /api/mobile/draft-units`

UI:

- Draft number preview
- Open date
- Unit select + Add
- Customer
- Serial number
- Description
- Customer PO
- Storage: `Rack / Level / Column`
- current visible flags:
  - `External Damage`
  - `Name Plate Missing`
- `Box` modal

### Important parity note

API поддерживает больше boolean fields, чем current mobile web screen показывает:

- visible in current web mobile:
  - `external_damage`
  - `nameplate_missing`

- supported by API but not shown in current mobile web:
  - `received_disassembly`
  - `disassembly_upon_arrival`
  - `extra_parts`

Для первого iOS релиза, если цель exact parity, повторять именно current web mobile UI, а не автоматически раскрывать все server-supported flags.

### Pending unit quick add

Current mobile web quick add реально использует только:

- `part_number`

API при этом умеет принять ещё:

- `name`
- `description`

Но для parity не надо делать обязательный расширенный form, если product этого не просил.

### Box

Критично для parity.

Draft body fields:

- `arrival_box_status`
- `arrival_box_notes`

Statuses:

- `ok`
- `easy`
- `medium`
- `hard`
- `replace`

Display labels:

- `OK`
- `Easy repair`
- `Medium repair`
- `Hard repair`
- `Replace`

Если отправлен `arrival_box_status` или `arrival_box_notes`, backend сам пишет:

- `arrival_box_recorded_by`
- `arrival_box_recorded_at`

## 11. Paint WO

Endpoints:

- `GET /api/mobile/paint?tab=wo`
- `PATCH /api/mobile/tdr-processes/{tdrProcess}/dates`
- `POST /api/mobile/paint/messages`

UI:

- compact table
- columns:
  - queue
  - WO
  - detail
  - start
  - finish

Behavior:

- `Hide closed rows` toggle
- inline date editing
- green visual if date set
- tap WO opens owner message modal

Owner message modal:

- owner
- detail
- textarea
- send button

## 12. Paint Lost

Endpoints:

- `GET /api/mobile/paint?tab=lost`
- `POST /api/mobile/paint/lost`
- `DELETE /api/mobile/paint/lost/{paint}`

UI:

- coverflow-like cards
- photo
- PN
- serial/comment
- delete button
- add lost part button

Add modal fields:

- `part_number`
- `serial_number`
- `comment`
- `photo`

## 13. Machining list

Endpoint:

- `GET /api/mobile/machining?my_wo=0|1`

UI:

- ultra-compact list
- queue left
- WO button right

Behavior:

- `My WO` toggle

## 14. Machining workorder detail

Endpoints:

- `GET /api/mobile/machining/workorders/{id}`
- `PATCH /api/mobile/machining/steps/{machiningWorkStep}`
- `POST /api/mobile/machining/workorders/{id}/photos`
- `POST /api/mobile/machining/workorders/{id}/doc-pdfs`

UI:

- title `WO {number}`
- buttons `Photo` and `Doc`
- links to:
  - machining photos
  - PDFs
- blocks by detail / step group / pending steps

Show:

- detail name
- PN
- SN
- process labels
- sent date
- step start
- step finish
- machinist name
- optional note/description

Critical rule:

- edit step dates may be done only by the assigned machinist
- do not widen this on client side for admin/manager if API says no

## 15. Machining photos

Endpoints:

- `GET /api/mobile/machining/workorders/{id}/photos`
- `DELETE /api/mobile/machining/workorders/{id}/media/{media}`

UI:

- 3-column grid
- tap thumbnail opens gallery
- per-item delete

## 16. Machining PDFs

Endpoints:

- `GET /api/mobile/machining/workorders/{id}/pdfs`
- `DELETE /api/mobile/machining/workorders/{id}/media/{media}`

UI:

- vertical document cards
- label
- created_at
- buttons:
  - `Open`
  - `Download`
  - `Delete`

## Core Swift Models

Ниже не полный код, а набор сущностей, которые стоит держать отдельно.

### Session

- `token`
- `user`
- `bootstrap`

### User

- `id`
- `name`
- `email`
- `role`
- `team`
- `capabilities`

### Bootstrap

- `menuMode`
- `availableMenuModes`
- `mediaGroups`
- `navigation`
- `screens`

### Navigation

- `topMenu`
- `topMenuModes`
- `workorderDetailMenu`
- `availableSections`

### Workorder

- `id`
- `number`
- `numberDisplay`
- `isDraft`
- `isDone`
- `doneAt`
- `openAt`
- `approved`
- `owner`
- `serialNumber`
- `description`
- `customerPo`
- `customer`
- `instruction`
- `unit`
- `storage`
- `arrivalBox`
- `mediaGroups`

### Component

- `id`
- `name`
- `iplNum`
- `partNumber`
- `effCode`
- `isBush`
- `bushIplNum`
- `logCard`
- `photo`
- `tdrs`

### Tdr attachment

- `id`
- `componentId`
- `codeId`
- `codeName`
- `necessariesId`
- `necessariesName`
- `qty`
- `serialNumber`
- `useTdr`
- `useProcessForms`

### Material

- `id`
- `code`
- `material`
- `specification`
- `description`

### Draft options

- `draftNumber`
- `units`
- `customers`
- `boxStatuses`

### Paint row

- `workorder`
- `detailLabel`
- `isQueueMaster`
- `queuePosition`
- `queueDisplay`
- `owner`
- `startDate`
- `finishDate`
- `editableProcessId`
- `closed`

### Paint lost item

- `id`
- `partNumber`
- `serialNumber`
- `comment`
- `photo`
- `owner`

### Machining detail item

Поддерживать polymorphic decoding:

- `step`
- `step_group`
- `pending_steps`

## Error Contract

Use unified API envelope.

Success:

- `ok = true`
- `data`
- `meta`
- `message?`

Error:

- `ok = false`
- `message`
- `errors?`

Critical statuses:

- `401` unauthenticated
- `403` forbidden
- `404` not found
- `422` validation failed

## Legacy Quirks That Must Not Be “Normalized By Accident”

1. Top menu is context-dependent, not role-only.

2. Manager/Admin can access paint and machining, but current web mobile does not expose these as permanent default top-menu tabs.

3. Draft screen UI is narrower than backend capability.

4. Dates in current mobile web are not globally unified.

5. `Remember me` in native is token persistence policy, not an API login field.

6. `log_card` must survive component update flow.

7. Shipping has a materially different shell:

- no `Material`
- no `Profile`
- no `Tasks/Parts/Process`

## Known Backend Risk

Есть важный backend risk, который полезно помнить при клиентской реализации:

- часть mobile workorder access в backend сейчас не выглядит строго ограниченной ownership-based checks

Из этого вывод:

- iOS не должен предполагать, что любой `id`, который случайно оказался в руках клиента, точно уже server-side изолирован идеально
- не строить UX на предположении "если endpoint открылся, значит user guaranteed owner"

Это не client workaround-задача, а просто важное замечание.

## Acceptance Checklist

1. Login uses `public/app-config` and `auth/login`
2. Splash/login shell совпадает по mood и controls
3. Correct top menu by role and screen context
4. Workorders filters behave exactly as web mobile
5. Shipping shell differs from technician shell
6. Workorder detail shows storage and media groups
7. Tasks enforce restricted finish rules
8. Parts support `log_card`, `is_bush`, `bush_ipl_num`, photo, attachments
9. Process dates edit correctly
10. Materials screen exists and edits only description
11. Profile supports avatar/team/password flow
12. Draft supports box status + notes
13. Paint WO supports owner message
14. Paint Lost supports add/delete
15. Machining supports `My WO`
16. Machining step editing allowed only for assigned machinist
17. Machining photos/PDFs support list and delete

## Feature Matrix Reporting (обязательно)

Паритет iOS/Android отслеживается в `docs/mobile-feature-matrix.md` (avia repo).

В КОНЦЕ КАЖДОЙ рабочей сессии выведи блок:

```
Matrix update:
- <Фича/Экран>: <✅ готово | 🔨 в работе | ⚠️ отличается: причина>
```

Перечисли только строки, чей статус изменился за сессию. Владелец перенесёт блок в матрицу. Если поведение экрана пришлось сделать иначе, чем в ТЗ/у Android — статус ⚠️ с одной строкой причины.
