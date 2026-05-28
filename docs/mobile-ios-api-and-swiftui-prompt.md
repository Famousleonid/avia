# Mobile iOS API and SwiftUI Prompt

## Current Mobile Section: What It Reads

The existing Laravel mobile section is under `/mobile` and is rendered by Blade views in `resources/views/mobile/pages`.
It is protected by `auth` and `verified` middleware. The mobile UI is a dark Bootstrap interface optimized for small screens.

### Shared Layout And Navigation

Blade:
- `resources/views/mobile/master.blade.php`
- `resources/views/components/mobile-menu.blade.php`

Data used:
- Authenticated user: `id`, `name`, `role`, permissions.
- Current route and optional current `workorder`.
- Department mode:
  - Paint users are redirected from `/mobile` to `/mobile/paint`.
  - Machining users are redirected from `/mobile` to `/mobile/machining`.
- Current Workorder route context for Workorder / Tasks / Parts / Process tabs.
- Session state for machining `mobile_machining_my_wo`.

UI:
- Top blue navigation bar, height about 60px.
- Dark page background `#343A40` / black content surfaces.
- Active menu icon has a green circular stroke animation.
- Common tabs:
  - WO
  - Workorder
  - Tasks
  - Parts
  - Process
  - Material
  - Create Draft
  - Profile
  - Logout
- Paint menu:
  - WO
  - Lost
  - Profile
  - Logout
- Machining menu:
  - WO
  - My WO toggle
  - optional Hide closed toggle on workorder detail
  - Profile
  - Logout

## Current Mobile Screens

### 1. Workorder List

Route:
- `GET /mobile`

Controller:
- `MobileController@index`

Reads:
- All workorders including drafts:
  - `id`
  - `number`
  - `user_id`
  - `is_draft`
  - done state derived from Completed main task
  - `unit.manuals`
  - `customer`
  - `instruction`
- Current user id.

Client-side filters:
- Search by workorder number.
- `All`: include workorders not owned by current user.
- `Done`: include completed workorders.
- `Draft`: shipping / manager / admin only; shows only drafts when enabled.
- Without Draft enabled, drafts are hidden.
- Without All enabled, only current user's workorders are shown.
- Without Done enabled, completed workorders are hidden.

Tapping a row opens the workorder detail.

### 2. Workorder Detail

Route:
- `GET /mobile/show/{workorder}`

Controller:
- `MobileController@show`

Reads:
- Workorder:
  - `id`
  - `number`
  - `is_draft`
  - `user.name`
  - `open_at`
  - `unit.part_number`
  - `unit.name`
  - `unit.manual.lib`
  - `unit.manual.number`
  - `serial_number`
  - `customer.name`
  - `instruction.name`
  - `approve_at`
  - `approve_name`
  - done date derived from Completed main task
  - `storage_rack`
  - `storage_level`
  - `storage_column`
  - computed `storage_location`
- Workorder media grouped by `config('workorder_media.groups')`.

Writes:
- `PATCH /mobile/workorders/{workorder}/storage`
  - allowed roles: Shipping, Manager, Admin
  - payload:
    - `storage_rack`: nullable integer 0..999
    - `storage_level`: nullable integer 0..999
    - `storage_column`: nullable integer 0..999
  - response:
    - `success`
    - `storage_location`
- `POST /mobile/workorders/photo/{workorder}`
  - uploads `photos[]` for a selected media category.
- `DELETE /mobile/workorders/photo/delete/{id}`
  - deletes a media item.
- `GET /mobile/workorders/photos/{id}?category=...`
  - reloads gallery data after upload/delete.

### 3. Draft Creation

Routes:
- `GET /mobile/draft`
- `POST /mobile/workorders/draft`
- `POST /mobile/draft/units/pending`

Controller:
- `MobileController@createDraft`
- `MobileController@storeDraft`
- `MobileController@storePendingDraftUnit`

Reads:
- Next draft number: `Workorder::nextDraftNumber()`.
- Units:
  - `id`
  - `part_number`
  - `name`
  - `manual.number`
- Customers:
  - `id`
  - `name`
- Manuals are loaded but the visible manual selector in pending-unit modal is hidden.

Writes draft:
- `unit_id`: required integer
- `customer_id`: required integer
- `instruction_id`: ignored by current implementation and forced to `6`
- `serial_number`: nullable string max 255
- `description`: nullable string max 255
- `open_at`: nullable project date string, parsed by `parse_project_date`
- `customer_po`: nullable string max 255
- booleans:
  - `external_damage`
  - `received_disassembly`
  - `disassembly_upon_arrival`
  - `nameplate_missing`
  - `extra_parts`
- storage:
  - `storage_rack`
  - `storage_level`
  - `storage_column`

Important bug in current Blade:
- The Level input is named `storage_2`, but controller expects `storage_level`.

Writes pending unit:
- `part_number`: required string max 255
- `name`: nullable string max 255
- `description`: nullable string max 255
- Creates or reuses a unit with `manual_id = null`, `verified = true`.

### 4. Tasks

Route:
- `GET /mobile/tasks/{workorder}`

Controller:
- `MobileTaskController@tasks`

Reads:
- Workorder with `generalTaskStatuses`.
- `GeneralTask` ordered by `sort_order`, `id`.
- `Task` grouped by `general_task_id`.
- `Main` rows for this workorder, keyed by `task_id`.

For each task:
- `general_task.id`
- `general_task.name`
- `general_task.is_done` from workorder general task status
- `task.id`
- `task.name`
- `task.task_has_start_date`
- existing main row:
  - `id`
  - `date_start`
  - `date_finish`
  - `ignore_row`
  - `user`

Writes:
- Current Blade posts to desktop routes `mains.store` and `mains.update`, not mobile API routes.
- Inputs:
  - `workorder_id`
  - `task_id`
  - `date_start`
  - `date_finish`
- Business rules are in `Main::validateAndResolveDates`.
- Restricted finish dates:
  - Task `Approved` and task `Completed` can only be edited by Admin or Manager.
- Saving recalculates general task statuses and workorder done state.

### 5. Parts / Components

Route:
- `GET /mobile/components/{workorder}`

Controller:
- `MobileComponentController@components`

Reads:
- Workorder with:
  - `unit`
  - `media`
  - `tdrs.codes`
  - `tdrs.component.media`
- Manual id from `workorder.unit.manual_id`.
- Component conditions where `unit = false`.
- All codes.
- All necessaries.
- All manuals.
- Components attached to the workorder, grouped by `component_id`.
- TDR details per component:
  - `tdr.id`
  - `code_id`
  - `code_name`
  - `necessaries_id`
  - `necessaries_name`
  - `qty`
  - `serial_number`
- Manual components for picker:
  - ordered by `ipl_num`, `part_number`, `name`.

Writes:
- Create component:
  - `workorder_id`
  - `ipl_num`
  - `part_number`
  - `eff_code`
  - `photo`
  - `name`
  - `is_bush`
  - `log_card`
- Quick create component JSON:
  - `workorder_id`
  - `ipl_num`
  - `part_number`
  - `eff_code`
  - `name`
  - `is_bush`
  - `log_card`
  - `bush_ipl_num`
  - `photo`
- Update component:
  - `name`
  - `ipl_num`
  - `part_number`
  - `eff_code`
  - `is_bush`
  - `bush_ipl_num`
- Attach component to workorder by creating TDR:
  - `workorder_id`
  - `component_id`
  - `code_id`
  - `necessaries_id`
  - `qty`
  - `serial_number`
- Update attached TDR:
  - `code_id`
  - `necessaries_id`
  - `qty`
  - `serial_number`
- Delete attached TDR.
- Update component photo.

Important attach rules:
- If selected code name contains `missing`, then:
  - `use_tdr = 0`
  - `use_process_forms = 0`
  - qty is editable
- Otherwise:
  - `use_tdr = 1`
  - `use_process_forms = 1`
- If necessary name is exactly `order new`:
  - `use_tdr = 1`
  - `use_process_forms = 0`
  - `order_component_id = component_id`
  - qty is editable
- If necessary contains `repair`:
  - serial number is stored
  - qty remains 1

### 6. Process

Route:
- `GET /mobile/process/{workorder}`

Controller:
- `MobileProcessController@process`

Reads:
- Workorder with:
  - `unit`
  - `media`
  - `tdrs.component.media`
  - `tdrs.tdrProcesses.processName`
- Components grouped by `component_id`.
- For each component, `processesForWorkorder` is the flattened list of all TDR processes on its TDRs.
- Components without processes are hidden.

Writes:
- Current Blade posts date changes to desktop route:
  - `PATCH /tdr-processes/{tdrProcess}/dates`
- Inputs:
  - `date_start`
  - `date_finish`
- Business rules are in `TdrProcessController@updateDate`.

### 7. Materials

Routes:
- `GET /mobile/materials`
- `POST /mobile/materials/{id}/update-description`

Controller:
- `MobileController@materials`
- `MobileController@updateMaterialDescription`

Reads:
- All materials:
  - `id`
  - `code`
  - `material`
  - `specification`
  - `description`

Writes:
- Inline update:
  - `description`

### 8. Paint

Routes:
- `GET /mobile/paint?tab=wo|lost`
- `POST /mobile/paint/lost`
- `DELETE /mobile/paint/lost/{paint}`

Controller:
- `MobileController@paint`
- `MobileController@storePaintLost`
- `MobileController@destroyPaintLost`

Access:
- Paint, Admin, Manager.

Reads WO tab:
- Open, approved, non-draft, not-done workorders.
- Workorders with:
  - `user`
  - `unit.manual.plane`
  - `tdrs.component`
  - `tdrs.tdrProcesses.processName`
- Rows are built by `PaintIndexRowsBuilder`.
- Row fields used by UI:
  - workorder id/number/user/queue order
  - queue display / position
  - `detail_label`
  - `date_start`
  - `date_finish`
  - `edit_paint_process.id`
  - `edit_paint_process.date_start`
  - `edit_paint_process.date_finish`
  - `is_queue_master`

Writes WO tab:
- Current Blade posts date changes to:
  - `PATCH /tdr-processes/{tdrProcess}/dates`
- Sends `from_paint_index=1`.

Reads Lost tab:
- Latest 100 `Paint` lost parts with:
  - `id`
  - `part_number`
  - `serial_number`
  - `comment`
  - `user.name`
  - media thumb/big URL

Writes Lost tab:
- Create:
  - `part_number`
  - `serial_number`
  - `comment`
  - `photo`: required image max 10MB
- Delete:
  - deletes lost record and logs media ids.

### 9. Machining

Routes:
- `GET /mobile/machining`
- `GET /mobile/machining/{workorder}`
- `PATCH /mobile/machining/steps/{machiningWorkStep}`
- media routes under `/mobile/machining/{workorder}/...`

Controller:
- `MobileController@machining`
- `MobileController@machiningWorkorder`
- `MobileController@updateMachiningWorkStepMobile`

Access:
- Machining, Admin, Manager.

Reads list:
- Open machining workorders:
  - not done
  - not draft
  - has machining date sent
  - ordered by `machining_queue_order`, then number
- Rows are built by `MachiningListingRowsBuilder`.
- Only rows without finish date are shown in list.
- Optional `My WO` session filter keeps rows where at least one machining step is assigned to current user.

Reads detail:
- Workorder plus machining detail items.
- Items can be:
  - `pending_steps`
  - `step`
  - `step_group`
- Display fields:
  - workorder number
  - detail name
  - detail part number label
  - detail serial
  - process label
  - parent description
  - date sent
  - step index
  - effective start
  - finish
  - machinist name if not current user
  - step description
  - counts for machining photos and PDFs

Writes:
- Step dates:
  - allowed only if current authenticated user is assigned machinist for that step
  - Admin and Manager do not bypass this on mobile
  - forwards to `Admin\MachiningController@updateMachiningWorkStep`
- Workorder machining photos:
  - `photos[]`, each image max 15MB
- Machining document PDF from image:
  - `image`, jpg/jpeg/png/webp max 15MB
  - server converts image to a single-page PDF and stores in `pdfs`
- Delete machining media:
  - allowed collections: `Machining` images and `pdfs`.

### 10. Profile

Routes:
- `GET /mobile/profile`
- `PUT /mobile/profile`
- `POST /mobile/change_password/user`

Controller:
- Existing `ProfileController`

Reads:
- Current user:
  - avatar
  - name
  - phone
  - birthday
  - email
  - team
  - stamp
- Teams list.

Writes:
- Profile:
  - `name`
  - `phone`
  - `birthday` in mobile project format such as `10.aug.2026`
  - avatar file under `file`
  - `team_id`
  - `stamp`
- Password:
  - `old_pass`
  - `password`
  - `password_confirmation`

## Proposed Native iOS API

Base path:
- `/api/mobile`

Authentication:
- Native app should use `Authorization: Bearer <token>`.
- Implemented direction: first-party bearer tokens in `mobile_api_tokens`; tokens are stored hashed server-side and the plain token is returned only once at login.
- Do not use browser session, CSRF, localStorage, or sessionStorage for the native app.

Confirmed v1 scope:
- Workorders
- Drafts
- Tasks
- Parts / Components
- Process
- Materials
- Profile
- Workorder media upload/delete for iOS photo queue support

Not in v1:
- Paint native screens
- Machining native screens
- Offline business editing

Confirmed users:
- The iOS app is for all roles. Role and capability flags must drive which screens/actions are visible.

Confirmed online behavior:
- The app is online-only for business data. If there is no internet, show a clear "No internet connection. The app is online-only for now." state and do not allow normal editing.
- Photo capture must feel continuous. If the API cannot accept uploads as fast as the user takes photos, the Swift app should maintain an in-memory upload queue and keep draining it.
- Do not keep photos in the phone's permanent storage after successful upload.
- Do not compress photos in v1; upload maximum quality/original image data where practical.

Standard response envelope:

```json
{
  "ok": true,
  "data": {},
  "meta": {},
  "message": null
}
```

Validation error:

```json
{
  "ok": false,
  "message": "Validation failed.",
  "errors": {
    "field": ["Message"]
  }
}
```

Date format:
- API transport: `YYYY-MM-DD` or `null`.
- UI display: `dd.mmm.yyyy` lower-case month, for example `10.aug.2026`.

### Auth Endpoints

`POST /api/mobile/auth/login`

Request:

```json
{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "Leo iPhone"
}
```

Response:

```json
{
  "ok": true,
  "data": {
    "token": "plain-text-token-returned-once",
    "user": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com",
      "role": "Manager",
      "team": {"id": 2, "name": "Team"},
      "capabilities": {
        "can_update_storage": true,
        "can_create_draft": true,
        "can_use_paint": true,
        "can_use_machining": false,
        "can_edit_restricted_task_finish": true
      }
    }
  }
}
```

`GET /api/mobile/me`

`POST /api/mobile/auth/logout`

### Bootstrap

`GET /api/mobile/bootstrap`

Returns current user, role, capabilities, media group labels, menu mode, and initial route suggestion.

### Workorders

`GET /api/mobile/workorders?scope=my|all|draft|done&include_done=0|1&only_done=0|1&search=123`

Filter rules:
- `scope=my` returns current user's non-draft workorders.
- `scope=all` returns all non-draft workorders.
- `scope=draft` returns only drafts and is allowed only for Shipping, Manager, Admin.
- `scope=done` or `only_done=1` returns only done non-draft workorders.
- `include_done=1` includes done rows in `my` or `all` scopes.
- default is `scope=my&include_done=0`.

Response item:

```json
{
  "id": 10,
  "number": 123456,
  "number_display": "123 456",
  "owned_by_current_user": true,
  "is_draft": false,
  "is_done": false,
  "done_at": null,
  "open_at": "2026-05-25",
  "customer": {"id": 1, "name": "Customer"},
  "unit": {
    "id": 5,
    "part_number": "PN-1",
    "name": "Unit",
    "manual": {"id": 8, "number": "CMM-1", "lib": "A"}
  }
}
```

`GET /api/mobile/workorders/{workorder}`

Returns full header data, storage, available tabs, media groups with counts and URLs.

`PATCH /api/mobile/workorders/{workorder}/storage`

`GET /api/mobile/workorders/{workorder}/media?category=photos`

`POST /api/mobile/workorders/{workorder}/media`
- multipart
- `category`
- `photos[]`
- server validation currently allows each image up to 100 MB; PHP/web-server upload limits must also allow the real file size.

`DELETE /api/mobile/workorders/{workorder}/media/{media}`

### Drafts

`GET /api/mobile/draft/options`

Returns:
- `draft_number`
- `units`
- `customers`

`POST /api/mobile/drafts`

`POST /api/mobile/draft-units`

### Tasks

`GET /api/mobile/workorders/{workorder}/tasks`

Response:

```json
{
  "workorder": {"id": 10, "number": 123456, "open_at": "2026-05-25", "approved": true, "is_done": false},
  "groups": [
    {
      "id": 1,
      "name": "Disassembly",
      "is_done": false,
      "tasks": [
        {
          "id": 11,
          "name": "Inspection",
          "has_start_date": true,
          "restricted_finish": false,
          "can_edit_finish": true,
          "main": {
            "id": 50,
            "date_start": "2026-05-25",
            "date_finish": null,
            "ignore_row": false,
            "user": {"id": 1, "name": "User Name"}
          }
        }
      ]
    }
  ]
}
```

`PUT /api/mobile/workorders/{workorder}/tasks/{task}/dates`

Request:

```json
{
  "date_start": "2026-05-25",
  "date_finish": null
}
```

### Components / Parts

`GET /api/mobile/workorders/{workorder}/components`

Returns:
- workorder header
- attached components with TDR details
- manual component picker list
- codes
- necessaries
- conditions

`POST /api/mobile/workorders/{workorder}/components`

`PATCH /api/mobile/components/{component}`

`POST /api/mobile/workorders/{workorder}/component-attachments`

`PATCH /api/mobile/component-attachments/{tdr}`

`DELETE /api/mobile/component-attachments/{tdr}`

`POST /api/mobile/components/{component}/photo`

### Process

`GET /api/mobile/workorders/{workorder}/processes`

`PATCH /api/mobile/tdr-processes/{tdrProcess}/dates`

Request:

```json
{
  "date_start": "2026-05-25",
  "date_finish": null,
  "date_promise": null,
  "source": "mobile_process"
}
```

For paint source, send `"source": "paint"` and server maps to `from_paint_index=1`.
For machining source, send `"source": "machining"` and server maps to `from_machining_index=1`.

### Materials

`GET /api/mobile/materials?search=steel`

`PATCH /api/mobile/materials/{material}`

Request:

```json
{
  "description": "Updated description"
}
```

### Paint

Deferred after v1.

Planned endpoints:

`GET /api/mobile/paint/workorders?hide_closed=0`

`PATCH /api/mobile/tdr-processes/{tdrProcess}/dates` with `source=paint`

`GET /api/mobile/paint/lost`

`POST /api/mobile/paint/lost`
- multipart
- `part_number`
- `serial_number`
- `comment`
- `photo`

`DELETE /api/mobile/paint/lost/{paint}`

### Machining

Deferred after v1.

Planned endpoints:

`GET /api/mobile/machining/workorders?my=0`

`GET /api/mobile/machining/workorders/{workorder}?my=0&hide_closed=0`

`PATCH /api/mobile/machining/steps/{machiningWorkStep}`

`POST /api/mobile/machining/workorders/{workorder}/photos`

`GET /api/mobile/machining/workorders/{workorder}/photos`

`POST /api/mobile/machining/workorders/{workorder}/doc-pdf`

`GET /api/mobile/machining/workorders/{workorder}/pdfs`

`DELETE /api/mobile/machining/workorders/{workorder}/media/{media}`

### Profile

`GET /api/mobile/profile`

Returns:
- `profile`
  - `id`
  - `name`
  - `phone`
  - `birthday`
  - `email`
  - `stamp`
  - `team`
  - `avatar`
- `teams`

`PUT /api/mobile/profile`

Request:

```json
{
  "name": "User Name",
  "phone": "123 456",
  "birthday": "10.aug.2000",
  "stamp": "ABC",
  "team_id": 1
}
```

Multipart field:
- `file`: optional avatar image.

`POST /api/mobile/profile/password`

Request:

```json
{
  "old_pass": "current",
  "password": "new-password",
  "password_confirmation": "new-password"
}
```

## Confirmed Decisions

1. Authentication is project-native bearer token auth via `mobile_api_tokens`, not Sanctum.
2. Native iOS v1 includes Workorders, Drafts, Tasks, Parts, Process, Materials, Profile, and workorder media upload/delete. Paint and Machining are deferred.
3. The app is for all roles; UI must be capability-driven.
4. Business data is online-only for now. No internet means show an offline warning and block normal editing.
5. Photos are not compressed in v1. iOS should upload maximum quality and maintain a transient upload queue when capture is faster than API upload.
6. Uploaded photos should not remain in permanent phone storage after successful server upload.

## Prompt For Codex For Mac To Build SwiftUI App

You are building a native iOS SwiftUI app for the Aviatechnik Laravel project. The current mobile web UI exists under `/mobile`; reproduce its workflows, colors, hierarchy, and compact phone-first layout, but use native SwiftUI components and a clean API client.

Build an iPhone-first SwiftUI app with a dark industrial style:
- Main background: `#343A40` and black panels.
- Cards/panels: dark gray `#2b3035` / `#1f2327`.
- Primary blue menu bar: Bootstrap-like `#0d6efd`.
- Accent cyan: `#0DCAF0`.
- Success green: `#198754`.
- Warning yellow: `#ffc107`.
- Text: white / light gray, secondary muted gray.
- Compact controls, small typography, dense layout for shop-floor use.
- Respect safe areas and one-handed use.
- Avoid marketing/landing pages. First screen after login is the operational WO list or department-specific screen.

API:
- Base URL is configurable.
- All requests use `Authorization: Bearer <token>`.
- Auth is custom bearer-token auth, not Sanctum.
- JSON date transport is `YYYY-MM-DD`.
- Display dates as `dd.mmm.yyyy`, lower-case month, for example `25.may.2026`.
- Standard response envelope:
  - `ok`
  - `data`
  - `meta`
  - `message`
  - `errors` on validation failures.

Implement these Swift layers:
- `APIClient` using async/await.
- `AuthStore` persisted securely in Keychain.
- Codable DTOs matching the API contract in this document.
- View models per screen.
- A reusable dark date picker row that displays `...` when nil and green when filled.
- Reusable media picker/uploader using PhotosUI and camera capture where available.
- Error banners/toasts for validation errors.
- Loading overlay similar to the current spinner behavior.

Navigation:
- Use a top bar similar to current mobile menu, not a marketing tab bar.
- Show menu items based on role/capabilities from `/api/mobile/bootstrap`.
- General users:
  - WO
  - Workorder
  - Tasks
  - Parts
  - Process
  - Material
  - Profile
  - Logout
- Shipping/Manager/Admin additionally see Create Draft and Storage editing.
- Paint/Machining native screens are deferred after v1. If the current user is Paint-only or Machining-only, show a clear "This native section is coming later" placeholder for that department-specific area instead of trying to call missing endpoints.

Screens to build:

1. Login
- Email, password, device name optional.
- Calls `POST /api/mobile/auth/login`.
- Store token in Keychain.
- Load `/api/mobile/bootstrap`.

2. Workorder List
- Calls `GET /api/mobile/workorders`.
- Search field fixed near the top.
- Toggle chips/checkboxes:
  - All
  - Done
  - Draft if allowed
- Map toggles to API params:
  - All off => `scope=my`
  - All on => `scope=all`
  - Done on => `include_done=1`
  - Draft on => `scope=draft`
- Large centered WO number buttons.
- Draft rows yellow.
- Done rows gray.
- Normal rows cyan.
- Format number as groups, e.g. `123 456`.
- Tap opens Workorder Detail.

3. Workorder Detail
- Calls `GET /api/mobile/workorders/{id}`.
- Header panel:
  - `W number` or `Draft: number`
  - owner name
  - Open date
  - PN
  - SN
  - Component
  - Customer
  - Lib
  - Instruction
  - Manual
  - Approved badge
  - Done date if present
- Storage panel:
  - show storage location
  - if `can_update_storage`, edit Rack/Level/Column and call PATCH.
- Media group list:
  - rows from media groups
  - category label
  - thumbnail circle
  - count badge
  - camera/upload button
  - gallery viewer
  - delete media action if allowed.

4. Create Draft
- Calls `GET /api/mobile/draft/options`.
- Form fields:
  - Draft number readonly
  - Unit searchable picker
  - Add Pending Unit modal with Part Number
  - Customer picker
  - Serial number
  - Open date
  - Description, auto-fill from selected unit name if description empty
  - Customer PO
  - Storage Rack/Level/Column
  - External Damage
  - Received Disassembly
  - Name Plate Missing
  - Extra Parts
- Save calls `POST /api/mobile/drafts`.
- Add pending unit calls `POST /api/mobile/draft-units`.

5. Tasks
- Calls `GET /api/mobile/workorders/{id}/tasks`.
- Header with WO number, approved icon/badge, open date.
- Accordion/list of general task groups.
- Group title green when complete.
- Each task card:
  - task name
  - Start date on left only if `has_start_date`
  - Finish date on right always
  - disabled lock icon if `can_edit_finish=false` or ignored
- Save date changes with `PUT /api/mobile/workorders/{workorder}/tasks/{task}/dates`.
- Preserve open group and scroll position in view model.

6. Parts
- Calls `GET /api/mobile/workorders/{id}/components`.
- Show attached component cards:
  - image thumbnail
  - name
  - IPL
  - PN
  - attached TDR detail chips/rows
- Component picker:
  - searchable manual component list
  - create new component form with IPL, PN, name, Is Bushing, Log Card, Bush IPL, optional photo
- Attach modal:
  - selected component
  - code picker
  - necessary picker
  - quantity
  - serial number
- Implement attach business UI:
  - Missing code exposes quantity and does not ask necessary/serial.
  - Order new exposes quantity.
  - Repair exposes serial number.
- API calls:
  - create component
  - update component
  - upload photo
  - attach/update/delete TDR.

7. Process
- Calls `GET /api/mobile/workorders/{id}/processes`.
- Show component list with thumbnail, name, IPL, PN.
- For each component, show compact dark table:
  - process name
  - Sent / Start date
  - Returned / Finish date
- Date edit calls `PATCH /api/mobile/tdr-processes/{id}/dates`.

8. Materials
- Calls `GET /api/mobile/materials`.
- Search locally after data load.
- Table/list rows:
  - code
  - material
  - specification
  - editable description
- Save description with `PATCH /api/mobile/materials/{id}`.

9. Profile
- Calls `GET /api/mobile/profile`.
- Form:
  - avatar
  - name
  - phone
  - birthday
  - email readonly
  - team picker
  - stamp
- Save with `PUT /api/mobile/profile`.
- Change password sheet with old password, new password, confirm.

Deferred screens:

10. Paint WO
- Calls `GET /api/mobile/paint/workorders`.
- Do not build as functional in v1 because backend endpoint is deferred. Keep only a placeholder or TODO.
- Compact table:
  - queue
  - WO
  - detail
  - start
  - finish
- Hide closed rows toggle.
- Tapping master WO opens a details sheet with owner, detail, message text area if messaging endpoint exists.
- Date edits call `PATCH /api/mobile/tdr-processes/{id}/dates` with source `paint`.

11. Paint Lost
- Calls `GET /api/mobile/paint/lost`.
- Do not build as functional in v1 because backend endpoint is deferred. Keep only a placeholder or TODO.
- Swipeable/card carousel style:
  - image
  - part number
  - serial or comment
  - delete button
- Add Lost Part sheet:
  - part number required
  - serial
  - comment
  - photo required
- Save multipart to `POST /api/mobile/paint/lost`.

12. Machining List
- Calls `GET /api/mobile/machining/workorders?my=0|1`.
- Do not build as functional in v1 because backend endpoint is deferred. Keep only a placeholder or TODO.
- Very compact two-column list:
  - queue display
  - WO button
- My WO toggle reloads with `my=1`.

13. Machining Detail
- Calls `GET /api/mobile/machining/workorders/{id}?my=0|1&hide_closed=0|1`.
- Do not build as functional in v1 because backend endpoint is deferred. Keep only a placeholder or TODO.
- Header `WO number`.
- Action row:
  - Photo
  - Doc
  - Machining photos count
  - PDFs count
- Detail blocks:
  - detail name
  - PN and optional SN
  - processes label clamped to two lines
  - parent description clamped to four lines
  - sent date
  - pending steps message if no steps
  - step group with step count if grouped
  - each step shows Step index, optional machinist name, Start, Finish, note
- Only allow date editing when `can_edit=true` for that step.
- Step date edits call `PATCH /api/mobile/machining/steps/{id}`.
- Photo upload calls `POST /api/mobile/machining/workorders/{id}/photos`.
- Doc upload captures/selects image and calls `POST /api/mobile/machining/workorders/{id}/doc-pdf`.
- Galleries call photos/pdfs endpoints.

Implementation details:
- Use `NavigationStack`.
- Keep state in `@StateObject` view models.
- Avoid hardcoded mock data except SwiftUI previews.
- Build previews with sample DTOs.
- Handle 401 by clearing token and returning to login.
- Handle 403 with a clear "Forbidden" message.
- Handle 422 by showing field validation messages near controls where practical.
- Use multipart upload for images.
- Do not compress photos in v1. Send maximum quality/original image data where practical.
- Media URLs returned by the API are also protected by bearer auth. Do not use plain SwiftUI `AsyncImage` for protected images; implement an authenticated image loader that sends `Authorization: Bearer <token>` with the image request.
- Implement a transient upload queue for workorder photos:
  - camera capture must remain responsive and allow repeated photo taking without waiting for each upload to complete;
  - queue photos in memory or a temporary system location only while upload is pending;
  - keep retrying while online;
  - after the server confirms success, remove the queued local item;
  - do not save successful uploads to the user's photo library or persistent app gallery.
- If internet is unavailable, show a clear online-only warning and pause upload attempts until connectivity returns.
- Keep UI dense and functional; this is a shop-floor tool, not a public website.

Deliverables:
- A compilable SwiftUI app.
- Codable models for all endpoints.
- API client with typed methods.
- Keychain-backed auth.
- All screens above with realistic previews.
- For v1, "all screens above" means Login, Workorder List, Workorder Detail, Create Draft, Tasks, Parts, Process, Materials, and Profile; deferred Paint/Machining screens should be placeholders/TODO only.
- Clear TODO comments only where the backend API is not yet implemented.
