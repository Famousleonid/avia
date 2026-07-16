# Brief For Android Client (Lipikhin/avia-android)

## Цель

Сделать нативный Android-клиент, который повторяет текущее mobile web поведение Laravel-приложения — тот же паритет, который уже зафиксирован для iOS.

Это не greenfield UX. Важно не «сделать красивее по Material», а не сломать существующую роль-логику, меню, draft flow, material flow, paint/machining сценарии и legacy formatting.

## Source Of Truth

Порядок приоритета:

1. Полное ТЗ (платформо-нейтральное): `docs/mobile-native-tz.md`
2. Поведение клиента, нюансы флоу и анти-паттерны: `docs/mobile-native-xcode-brief.md` — **читать целиком**; всё, что там сказано про экраны, применимо к Android 1:1, меняется только базовый префикс API (см. ниже)
3. Ответы по контракту API: `docs/laravel-api-answers-for-ios.md`
4. Серверный код: `routes/api.php`, `app/Http/Controllers/Api/Mobile/MobileApiController.php`, `app/Http/Controllers/Api/Android/AndroidApiController.php`
5. Web mobile эталон по визуалу: `resources/views/auth/login.blade.php`, `resources/views/mobile/master.blade.php`, `resources/views/components/mobile-menu.blade.php`, `resources/views/mobile/pages/*.blade.php`

## Android-контур API

Базовый префикс: **`/api/android/*`** (не `/api/mobile/*` — тот принадлежит iOS).

Карта маршрутов идентична iOS-контуру (оба регистрируются одним общим списком в `routes/api.php`). Обработчик — `AndroidApiController extends MobileApiController`: наследует все эндпоинты, переопределены только:

- `GET /api/android/public/app-config` — в `data.app` добавлено:
  - `platform: "android"`
  - `android: { min_sdk: 26, dynamic_color: false }` — брендовая палитра важнее Material You; dynamic color не включать
- `POST /api/android/auth/login` — `device_name` по умолчанию `"Android device"`; токен помечается `platform = 'android'` на сервере

Всё остальное (payload'ы, навигация, экраны, медиа) — байт-в-байт как у iOS.

## Стек

- Kotlin, Jetpack Compose, minSdk 26, targetSdk — актуальный
- Сеть: Retrofit + OkHttp, kotlinx-serialization (envelope см. ниже)
- DI: Hilt
- Токен: DataStore + EncryptedSharedPreferences (persist по политике Remember Me)
- Картинки: Coil c OkHttp-клиентом, у которого interceptor добавляет `Authorization: Bearer <token>` — media-эндпоинты (`/media/{id}/thumb`, `/media/{id}/file`) закрыты токеном
- Камера/фото: CameraX (или system picker) → upload по `photo_upload` политике из bootstrap (`queue_on_client: true`, `delete_local_after_success: true`)
- Навигация: Navigation Compose; структура shell — server-driven из `bootstrap.navigation`

## Envelope контракт

Каждый ответ:

```json
// success
{ "ok": true,  "data": {...}, "meta": {}, "message": null }
// error
{ "ok": false, "message": "...", "errors": { "field": ["..."] } }
```

- `401` на любом защищённом эндпоинте → сбросить токен → экран login (не бесконечный retry)
- `422` — валидация, показывать `errors` по полям

## App Launch Flow

1. Cold start → `GET /api/android/public/app-config` → splash/login shell по metadata
2. `POST /api/android/auth/login` → сохранить bearer token
3. `GET /api/android/bootstrap` → app shell: `menu_mode`, `available_menu_modes`, `navigation.top_menu*`, `screens`, `display_date_format`, `media_groups`
4. Меню и доступные секции строить ТОЛЬКО из `bootstrap.navigation` — не хардкодить по ролям

Remember Me: чисто клиентская политика хранения токена (`remember_me_mode = client_token_persistence`). Forgot password: web handoff по `forgot_password_url`, свой native flow не придумывать.

## Экраны v1 (паритет с mobile web)

| Экран (blade-эталон в `mobile/pages/`) | Эндпоинты |
|---|---|
| Login (`auth/login.blade.php`) | `public/app-config`, `auth/login` |
| Workorders list (`index`) | `GET /workorders` |
| Workorder detail (`show`) | `GET /workorders/{id}`, `PATCH .../storage`, `PATCH .../arrival-box`, `GET/POST/DELETE .../media` |
| Components / TDR (`components`) | `GET/POST .../components`, `PATCH /components/{id}`, `POST /components/{id}/photo`, `POST .../component-attachments`, `PATCH/DELETE /component-attachments/{tdr}` |
| Processes (`process`) | `GET .../processes`, `PATCH /tdr-processes/{id}/dates` |
| Tasks (`tasks`) | `GET .../tasks`, `PUT .../tasks/{task}/dates` |
| Materials (`materials`) | `GET /materials`, `PATCH /materials/{id}` |
| Paint (`paint`) | `GET /paint`, `POST /paint/lost`, `DELETE /paint/lost/{id}`, `POST /paint/messages` |
| Machining list (`machining`) | `GET /machining` |
| Machining WO (`machining-workorder`) | `GET /machining/workorders/{id}`, `PATCH /machining/steps/{id}` |
| Machining photos (`machining-workorder-photos`) | `GET/POST .../photos`, `DELETE .../media/{media}` |
| Machining PDFs (`machining-workorder-pdfs`) | `GET .../pdfs`, `POST .../doc-pdfs` |
| Draft create (`createdraft`) | `GET /draft/options`, `POST /drafts`, `POST /draft-units` |
| Draft view (`showdraft`) | из `GET /workorders` (драфты) |
| Profile (`profile`) | `GET/PUT /profile`, `POST /profile/password`, `auth/logout` |

Порядок реализации (по ценности): Login+Profile → Workorders+media → Tasks/Processes → Components+TDR → Materials → Paint → Machining → Drafts.

## Анти-паттерны (те же, что для iOS)

- НЕ проектировать «по ощущениям» — собирать по API metadata и blade-поведению
- НЕ хардкодить верхнее меню по роли — только `bootstrap.navigation`
- НЕ нормализовать даты «как правильнее» — использовать `display_date_format` (`dd/mmm/yyyy`)
- НЕ расширять draft UI до всех backend-полей, которых нет в mobile web
- НЕ включать Material You dynamic color — палитра из `app-config`

## Чего в v1 НЕТ (сознательно)

- Push-уведомлений (нет и в API; этап 7 — FCM для обеих платформ)
- Measurements/Dimensions (в mobile API отсутствуют)
- Офлайн-режима (`bootstrap.offline_mode = false`)

## Репозиторий и дистрибуция

- Код клиента: **github.com/Lipikhin/avia-android** (Laravel-репо не трогается)
- Сборка: Gradle CLI; подпись — keystore в секретах, не в репо
- Распространение: Play Console internal testing track или прямой APK сотрудникам
