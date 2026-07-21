# Mobile Feature Matrix — iOS / Android

Единственное место, где виден функциональный паритет мобильных клиентов.

**Протокол обновления:**
- Сервер/Android — обновляется в том же коммите, что и фича (ведёт Claude Code).
- iOS — по итогам iOS-сессии: агент в Xcode завершает работу блоком «Matrix update», владелец передаёт его сюда.
- Статусы: ✅ готово · 🔨 в работе · — не начато · ⚠️ отличается (причина в примечании) · ❓ статус неизвестен, уточнить.
- Правило изменений: сначала Laravel (+ТЗ), потом клиенты. Изменения контракта — только аддитивные.

Контракт: `routes/api.php` (общий список маршрутов, iOS `/api/mobile/*`, Android `/api/android/*`),
`MobileApiController` (реализация) + `AndroidApiController` (overrides: publicAppConfig, login).
ТЗ: `docs/mobile-native-tz.md`. Брифы: `mobile-native-xcode-brief.md`, `mobile-native-android-brief.md`.

| Фича / Экран | Эндпоинты | iOS | Android | Примечания |
|---|---|---|---|---|
| Splash / app-config | `GET public/app-config` | ❓ | ✅ | Android: platform-блок в ответе |
| Login (+Remember me) | `POST auth/login` | ❓ | ✅ | e2e на Samsung A13 21.07.2026; токен с platform='android' |
| Bootstrap / ролевая навигация | `GET bootstrap` | ❓ | ✅ | Home-заглушка показывает menu_mode; полноценный shell — 🔨 |
| Logout | `POST auth/logout` | ❓ | ✅ | |
| Profile (просмотр/правка/пароль) | `GET/PUT profile`, `POST profile/password` | ❓ | — | |
| Workorders: список | `GET workorders` | ❓ | ✅ | 21.07: поиск, Done, My/All; проверено на устройстве с реальным аккаунтом |
| Workorder: карточка (storage, arrival box) | `GET workorders/{id}`, `PATCH …/storage`, `PATCH …/arrival-box` | ❓ | — | |
| Workorder: медиа (фото, thumb) | `GET/POST/DELETE …/media`, `GET media/{id}/thumb|file` | ❓ | — | Bearer-заголовок для картинок |
| Drafts (create/show) | `GET draft/options`, `POST drafts`, `POST draft-units` | ❓ | — | |
| Tasks (даты) | `GET …/tasks`, `PUT …/tasks/{task}/dates` | ❓ | — | |
| Components + фото | `GET/POST …/components`, `PATCH components/{id}`, `POST components/{id}/photo` | ❓ | — | |
| TDR attachments | `POST …/component-attachments`, `PATCH/DELETE component-attachments/{tdr}` | ❓ | — | |
| Processes (даты) | `GET …/processes`, `PATCH tdr-processes/{id}/dates` | ❓ | — | |
| Materials | `GET materials`, `PATCH materials/{id}` | ❓ | — | |
| Paint (lost, owner message) | `GET paint`, `POST paint/lost`, `DELETE paint/lost/{id}`, `POST paint/messages` | ❓ | — | |
| Machining: список/WO/шаги | `GET machining`, `GET machining/workorders/{id}`, `PATCH machining/steps/{id}` | ❓ | — | |
| Machining: фото/PDF | `…/photos`, `…/doc-pdfs`, `…/pdfs`, `DELETE …/media/{id}` | ❓ | — | |
| Push-уведомления | нет в API | — | — | этап 7, FCM сразу для обеих платформ |
| Measurements / Dimensions | нет в mobile API | — | — | сознательно вне v1 (см. mobile_measurements_plan) |

История заметных решений:
- 21.07.2026 — Android e2e на реальном устройстве (login→bootstrap), контракт UserDto уточнён: role строкой, team {id,name}, capabilities map.
- 15.07.2026 — Android-контур `/api/android/*` (наследование iOS-контроллера), миграция `platform` в mobile_api_tokens.
