# Laravel Mobile API Answers For iOS

Base URL:

```http
https://aviatechnik.ca/api/mobile
```

All protected endpoints require:

```http
Authorization: Bearer <token>
Accept: application/json
```

## Standard Response Envelope

Current success envelope:

```json
{
  "ok": true,
  "data": {},
  "meta": {},
  "message": null
}
```

Current error envelope:

```json
{
  "ok": false,
  "message": "Validation failed.",
  "errors": {
    "field": ["Error message"]
  }
}
```

Notes:

- Success responses include `meta`, not `errors`.
- Error responses may not always include `errors`; for example `401` can be:

```json
{
  "ok": false,
  "message": "Unauthenticated."
}
```

- Current v1 list responses do not return top-level arrays. Arrays are inside `data.items`, `data.media`, `data.groups`, etc.
- `data` can be `null` for actions like logout.

## GET /api/mobile/bootstrap

Sample shape:

```json
{
  "ok": true,
  "data": {
    "user": {
      "id": 27,
      "name": "Administ",
      "email": "admin@admin.ca",
      "role": "Admin",
      "team": {
        "id": 1,
        "name": "Management"
      },
      "capabilities": {
        "can_update_storage": true,
        "can_create_draft": true,
        "can_use_paint": true,
        "can_use_machining": true,
        "can_edit_restricted_task_finish": true
      }
    },
    "menu_mode": "workorders",
    "media_groups": {
      "received": "As received",
      "Machining": "Machining",
      "photos": "Photos of the unit",
      "extra": "Extra parts",
      "ec": "EC",
      "damages": "Damage & Corroded",
      "repair": "Repair parts",
      "logs": "Log card",
      "final": "Final assy",
      "shipping": "Shipping"
    },
    "date_format": "YYYY-MM-DD",
    "display_date_format": "dd.mmm.yyyy",
    "offline_mode": false,
    "photo_upload": {
      "compress_on_client": false,
      "queue_on_client": true,
      "delete_local_after_success": true
    }
  },
  "meta": {},
  "message": null
}
```

## Media Category Values

iOS must send the stable category key, not the display label.

Valid current workorder media category keys:

```json
[
  "received",
  "Machining",
  "photos",
  "extra",
  "ec",
  "damages",
  "repair",
  "logs",
  "final",
  "shipping"
]
```

Important:

- Use `damages`, not `damage`.
- Use `repair`, not `repair_parts`.
- Use `photos` for normal unit photos.
- `Machining` is capitalized because it matches the legacy Spatie media collection name.
- If `category` is omitted during upload, backend defaults to `photos`.

## GET /api/mobile/workorders

No pagination in v1. It returns all matching rows.

Supported query params:

```http
scope=my|all|draft|done
include_done=0|1
only_done=0|1
search=123456
```

Response shape:

```json
{
  "ok": true,
  "data": {
    "items": [
      {
        "id": 148,
        "number": 100500,
        "number_display": "100 500",
        "is_draft": false,
        "is_done": false,
        "done_at": null,
        "open_at": "2026-05-20",
        "approved": true,
        "owned_by_current_user": true,
        "customer": {
          "id": 4,
          "name": "Customer Name"
        },
        "unit": {
          "id": 55,
          "part_number": "PN-123",
          "name": "Unit Name",
          "description": "Unit Description",
          "manual_id": 12,
          "manual": {
            "id": 12,
            "number": "CMM-123",
            "lib": "LIB"
          },
          "verified": true
        }
      }
    ]
  },
  "meta": {},
  "message": null
}
```

No `page`, `per_page`, or pagination `meta` currently.

## GET /api/mobile/workorders/{id}

Response shape:

```json
{
  "ok": true,
  "data": {
    "workorder": {
      "id": 148,
      "number": 100500,
      "number_display": "100 500",
      "is_draft": false,
      "is_done": false,
      "done_at": null,
      "open_at": "2026-05-20",
      "approved": true,
      "owner": {
        "id": 27,
        "name": "Technician Name"
      },
      "serial_number": "SN-123",
      "description": "Workorder description",
      "customer_po": "PO-123",
      "customer": {
        "id": 4,
        "name": "Customer Name"
      },
      "instruction": {
        "id": 2,
        "name": "Overhaul"
      },
      "unit": {
        "id": 55,
        "part_number": "PN-123",
        "name": "Unit Name",
        "description": "Unit Description",
        "manual_id": 12,
        "manual": {
          "id": 12,
          "number": "CMM-123",
          "lib": "LIB"
        },
        "verified": true
      },
      "approve_at": "2026-05-21",
      "approve_name": "Manager Name",
      "storage": {
        "rack": 1,
        "level": 2,
        "column": 3,
        "location": "Rack: 1 _ Level: 2 _ Column: 3",
        "can_update": true
      },
      "media_groups": [
        {
          "key": "photos",
          "label": "Photos of the unit",
          "count": 2,
          "media": []
        }
      ]
    }
  },
  "meta": {},
  "message": null
}
```

Nullable fields include:

- `done_at`
- `open_at`
- `owner`
- `serial_number`
- `description`
- `customer_po`
- `customer`
- `instruction`
- `unit`
- `unit.manual`
- `approve_at`
- `approve_name`
- `storage.rack`
- `storage.level`
- `storage.column`
- `storage.location`

Types:

- `id`: integer
- `number`: integer
- `number_display`: string
- `is_draft`, `is_done`, `approved`: boolean
- dates: string `YYYY-MM-DD` or null
- objects: object or null

## GET /api/mobile/workorders/{id}/media?category=...

The shape is:

```json
{
  "ok": true,
  "data": {
    "media": [
      {
        "id": 501,
        "name": "wo_100500_20260525_120000_abcd",
        "file_name": "wo_100500_20260525_120000_abcd.jpg",
        "mime_type": "image/jpeg",
        "size": 4821332,
        "collection": "photos",
        "thumb_url": "https://aviatechnik.ca/api/mobile/media/501/thumb",
        "url": "https://aviatechnik.ca/api/mobile/media/501/file",
        "created_at": "2026-05-25T16:00:00+00:00"
      }
    ]
  },
  "meta": {},
  "message": null
}
```

It is not `data: []`.

## POST /api/mobile/workorders/{id}/media

Multipart request:

```text
category = photos
photos[] = file
photos[] = file
```

The API returns the full updated media list for that category, not only newly uploaded items.

Response shape:

```json
{
  "ok": true,
  "data": {
    "media": [
      {
        "id": 501,
        "name": "wo_100500_20260525_120000_abcd",
        "file_name": "wo_100500_20260525_120000_abcd.jpg",
        "mime_type": "image/jpeg",
        "size": 4821332,
        "collection": "photos",
        "thumb_url": "https://aviatechnik.ca/api/mobile/media/501/thumb",
        "url": "https://aviatechnik.ca/api/mobile/media/501/file",
        "created_at": "2026-05-25T16:00:00+00:00"
      }
    ],
    "photo_count": 1
  },
  "meta": {},
  "message": null
}
```

Server validation currently allows each uploaded image up to 100 MB. PHP/web server upload limits must also allow the actual file size.

## Media URLs

Current media payload fields:

- `thumb_url`
- `url`

There is no `big_url` field in current v1. Treat `url` as the full-size/original file URL.

URLs are absolute because Laravel `route()` generates full URLs.

These URLs are protected by the same mobile bearer middleware. iOS can request them directly, but must send:

```http
Authorization: Bearer <token>
```

Do not use unauthenticated `AsyncImage` for these URLs.

## DELETE /api/mobile/workorders/{workorder}/media/{media}

Endpoint exists:

```http
DELETE /api/mobile/workorders/{workorder}/media/{media}
```

Current v1 behavior:

- Any authenticated mobile API user can call it.
- Backend verifies that the media item belongs to the given workorder.
- If not, returns 404.

Response:

```json
{
  "ok": true,
  "data": {
    "id": 501
  },
  "meta": {},
  "message": null
}
```

## Auth / Token Lifetime

Current token behavior:

- Login creates a token row in `mobile_api_tokens`.
- Server stores only SHA-256 hash of the token.
- Plain token is returned only once from login.
- `expires_at` exists but is currently not set by login.
- Therefore current tokens do not expire automatically.
- `last_used_at` is updated on authenticated requests.

On any `401`, iOS should clear the token and return to login.

Logout endpoint:

```http
POST /api/mobile/auth/logout
```

Response is not empty. Current shape:

```json
{
  "ok": true,
  "data": null,
  "meta": {},
  "message": "Logged out."
}
```

After logout, the same token should no longer work.
