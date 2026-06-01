# Avia iOS: push notifications API + BOX role / arrival box sync

Привет.

Я проверил текущий Laravel backend Avia и brief по server push notifications.

## Current mobile API contract

Mobile API уже использует Bearer token из:

```http
POST /api/mobile/auth/login
```

Все mobile API responses сейчас идут в общем envelope:

```json
{
  "ok": true,
  "data": {},
  "meta": {},
  "message": null
}
```

Auth для новых endpoints должен быть такой же:

```http
Authorization: Bearer <mobile_token>
```

## Push notifications: what server needs to add

Нужно добавить endpoint регистрации APNs device token:

```http
POST /api/mobile/notifications/register-device
Authorization: Bearer <mobile_token>
Content-Type: application/json
```

Request body:

```json
{
  "token": "<apns_device_token_hex>",
  "platform": "ios",
  "environment": "development",
  "device_name": "<iPhone name>",
  "device_id": "<stable uuid from app>",
  "app_version": "1.0"
}
```

Expected response:

```json
{
  "ok": true,
  "data": {
    "device": {
      "id": 123,
      "last_seen_at": "2026-06-01T12:00:00-04:00"
    }
  },
  "meta": {},
  "message": null
}
```

Registration must be idempotent. Повторная отправка того же APNs token не должна создавать дубль или ошибку.

Server should store multiple devices per user:

```text
user_id
token
platform
environment
device_name
device_id
app_version
last_seen_at
revoked_at
```

Suggested unique/idempotency logic:

```text
unique token, or unique platform + environment + device_id when device_id exists
```

If the same token is registered again, update:

```text
user_id
platform
environment
device_name
device_id
app_version
last_seen_at = now()
revoked_at = null
```

## Push notifications: badge/read endpoints

Нужны endpoints для unread badge count и read state:

```http
GET /api/mobile/notifications/unread-count
GET /api/mobile/notifications
POST /api/mobile/notifications/mark-read
POST /api/mobile/notifications/read-all
```

Unread count response:

```json
{
  "ok": true,
  "data": {
    "unread_count": 3
  },
  "meta": {},
  "message": null
}
```

`mark-read` can accept one notification:

```json
{
  "id": "<notification_uuid>"
}
```

or multiple:

```json
{
  "ids": ["<notification_uuid>"]
}
```

After mark-read/read-all, return updated count:

```json
{
  "ok": true,
  "data": {
    "unread_count": 2
  },
  "meta": {},
  "message": null
}
```

Badge count must be:

```text
current_user.unreadNotifications().count()
```

## APNs payload

Use standard Apple payload:

```json
{
  "aps": {
    "alert": {
      "title": "Avia",
      "body": "..."
    },
    "sound": "default",
    "badge": 3
  },
  "event": "<event_type>",
  "id": "<notification_id>",
  "url": "<optional deep link>"
}
```

`aps.badge` must always be current unread count for that user.

## Event types currently used by backend

Current server-side notification values that iOS should handle:

```text
assigned
approved
unapproved
draft_created
overdue
process_ready_for_next
birthday_2days
birthday_today
manual.revision_check_due
date_notification
message/null fallback
```

Current stored notification data usually contains:

```json
{
  "type": "workorder",
  "event": "draft_created",
  "severity": "info",
  "title": "Draft Workorder created",
  "text": "Draft WO 18 created by User",
  "url": "https://...",
  "ui": {},
  "payload": {},
  "from_user_id": 1,
  "from_name": "User"
}
```

For iOS UI, use `title` if present, otherwise fallback to `type/event`. Use `text` as notification body.

## APNs credentials/config

Server needs Apple Developer Program APNs credentials:

```text
bundle_id: ca.aviatechnik.Avia
team_id: <Apple Developer Team ID>
key_id: <APNs Auth Key ID>
private_key: <.p8 key content/path>
environment: development|production
```

Important: current iOS build signed with Personal Team cannot enable Push Notifications because Personal Team does not support `aps-environment`. Need a paid Apple Developer Program team/profile with Push Notifications capability.

## BOX role / arrival box sync

Current backend already has arrival box fields for workorders:

```text
arrival_box_status
arrival_box_notes
arrival_box_recorded_by
arrival_box_recorded_at
```

Current statuses:

```text
ok
easy
medium
hard
replace
```

Meaning:

```text
ok      - box is ok
easy    - easy repair
medium  - medium repair
hard    - hard repair
replace - replace/buy new box
```

Current mobile workorder detail payload already includes:

```json
{
  "arrival_box": {
    "status": "ok",
    "notes": "Corner dented",
    "recorded_by": 1,
    "recorded_at": "2026-06-01T12:00:00-04:00"
  }
}
```

Current mobile draft creation already accepts:

```json
{
  "arrival_box_status": "replace",
  "arrival_box_notes": "Corner dented"
}
```

For two-way sync after draft/workorder already exists, backend should add:

```http
PATCH /api/mobile/workorders/{workorderId}/arrival-box
Authorization: Bearer <mobile_token>
Content-Type: application/json
```

Request body:

```json
{
  "status": "ok",
  "notes": "Corner dented"
}
```

Response:

```json
{
  "ok": true,
  "data": {
    "arrival_box": {
      "status": "ok",
      "notes": "Corner dented",
      "recorded_by": 1,
      "recorded_at": "2026-06-01T12:00:00-04:00"
    }
  },
  "meta": {},
  "message": null
}
```

When status or notes are changed, server should set:

```text
arrival_box_recorded_by = current user id
arrival_box_recorded_at = now()
```

If both status and notes are cleared, decide whether to clear recorded fields too. My recommendation: clear all four fields to keep data consistent:

```text
arrival_box_status = null
arrival_box_notes = null
arrival_box_recorded_by = null
arrival_box_recorded_at = null
```

## BOX role permissions

The user said a new role was added for Shipping BOX. Current local DB still has:

```text
Admin
Machining
Manager
Paint
Shipping
Shop Certifying Authority (SCA)
Team Leader
Technician
```

No `BOX` role exists in this local DB snapshot yet.

Important: Laravel `User::roleIs()` compares role names strictly and case-sensitively. If DB role name is `BOX`, backend checks must use exactly `BOX`.

Where server should add `BOX` alongside `Shipping`, `Manager`, `Admin`:

```text
GET /api/mobile/workorders?scope=draft
PATCH /api/mobile/workorders/{workorderId}/storage
GET /api/mobile/draft/options
POST /api/mobile/drafts
POST /api/mobile/draft-units
GET /api/mobile/bootstrap capabilities
GET /api/mobile/workorders/{workorderId} storage.can_update
PATCH /api/mobile/workorders/{workorderId}/arrival-box
```

Suggested capabilities in `/api/mobile/bootstrap` and `/api/mobile/me`:

```json
{
  "capabilities": {
    "can_update_storage": true,
    "can_create_draft": true,
    "can_update_arrival_box": true,
    "can_use_paint": false,
    "can_use_machining": false,
    "can_edit_restricted_task_finish": false
  }
}
```

For iOS:

1. After login or APNs token refresh, call `POST /api/mobile/notifications/register-device`.
2. When the user opens a notification, call `POST /api/mobile/notifications/mark-read`.
3. When the user clears/opens all notifications, call `POST /api/mobile/notifications/read-all`.
4. Use `/api/mobile/bootstrap` capabilities to show/hide Shipping/BOX actions.
5. Use `arrival_box.status`, `arrival_box.notes`, `arrival_box.recorded_by`, `arrival_box.recorded_at` for display.
6. Use `PATCH /api/mobile/workorders/{workorderId}/arrival-box` for two-way BOX sync.

