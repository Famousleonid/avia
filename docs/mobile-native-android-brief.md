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

## Feature Matrix

Паритет iOS/Android — в `docs/mobile-feature-matrix.md`. Каждый коммит с новой фичей клиента или изменением контракта обновляет соответствующую строку матрицы в том же коммите.

## Обновление Log Card / фото — 29/Oct/2026

Для обоих контуров добавлены защищённые маршруты:

- `GET /workorders/{id}/log-card/draft` → `data.draft`: null либо `{version:1, rows:[...]}`.
- `PUT /workorders/{id}/log-card/draft`, body `{value: <draft-or-null>}`. Явный JSON null удаляет черновик. Scope и key сервер задаёт сам: `mobile.log-card.draft` / `wo:<id>`, user_id только текущего пользователя. Формат rows совместим с PWA: `key`, `included`, `componentId`, `serial`, `assemblies:[{componentId,value}]`. Null/пустое value сборки означает явный No ASSY, а отсутствие записи сборки — ещё не сделанный выбор/default. Не подменять исчезнувшую деталь первым вариантом.
- `POST /workorders/{id}/log-card/photo`, multipart `photo`, `recognize`, `expected_part_number?`, `row_index?`. При recognize=1 возвращается `data.recognition` (P/N и S/N), `recognition_error`, `pending_confirmation=true`; фото не записывается. После явного Confirm повторно передать тот же локальный файл с recognize=0: только этот вызов сохраняет Logs WO. Ошибка сохраняет локальный кадр. Портрет проверяется до OCR/записи. Авторизация token + разрешённая роль, проверка WO и read-only Log Card.

Читать `assy_groups`, `available_manuals`, `assembly_choices` и template отдельных manuals через query `manual_id`. Для создания экземпляров Qty>1 обязательны `unit_index` и собственные S/N/ASSY; `units_assy` передаётся строкой. Новое создание без допустимого номера экземпляра сервер отклоняет 422, чтобы не сохранять неоднозначные дубликаты. Старые карты чтением не переписываются.

`PATCH /log-card/{card}/rows/{index}/assembly` дополнительно принимает `assy_selection_explicit=true` и `assy_option_id` (null/отсутствует = No ASSY). Сервер проверяет собственные доступные Part Group options детали; изменение не затрагивает соседний экземпляр. Legacy `component_assembly_id` сохраняет прежнее поведение. GET карты дополнительно отдаёт `assembly_choices`, `assy_option_id`, `assy_selection_explicit`, `unit_index`.

На клиенте: пустой начальный состав, Add/Deselect >=48dp, вариант >=64dp, S/N и Photo + read >=52dp. Черновик записывается последовательно в БД, навигация ждёт запись; локальный SavedStateHandle сохраняет незавершённое состояние при пересоздании Activity. Не закрывать preview до подтверждённого сохранения; отмена пересъёмки оставляет старый кадр. Проверять EXIF и реальные размеры файла, без поворота/обрезания портрета ради обхода. В landscape WO список групп прокручивается слева, отдельная камера с выбранной категорией остаётся справа.

### Уточнение после Android runtime — 30/Oct/2026

Пустой `draft.rows[].serial` Laravel может вернуть как JSON null, в том числе у невключённых строк. Android читает его как пустой S/N через отдельный serializer этого поля. Значение `assemblies[].value=null` сохраняет самостоятельный смысл явного No ASSY; глобальную замену всех null не применять.

Оба native upload endpoint (`POST …/media`, `POST …/log-card/photo`) дополнительно принимают необязательный multipart `upload_id`: 64 строчных hex-символа. Android вычисляет его из пути исходного кадра и его байтов; подготовленная копия и каждая повторная попытка не создают новый ID. Новый снимок получает другой ID. С `upload_id` обычный WO upload принимает ровно одно фото.

Сервер ограничивает ключ текущим пользователем, WO, коллекцией и контекстом строки Log Card. Внутри существующей блокировки WO `WorkorderPhotoStorageService` возвращает уже сохранённое media без нового файла/номера; несовпадение байтов под прежним ключом отклоняется 409. В Log Card recognize=1 по-прежнему не сохраняет фото; защита повтора применяется только при Confirm. Клиенты без `upload_id` сохраняют прежний контракт. Миграция не требуется.

Полноэкранные native фото-dialog используют общий `FullScreenPhotoDialog`: обычное измерение окна (`usePlatformDefaultWidth=true`) с явными `MATCH_PARENT` для ширины/высоты, `decorFitsSystemWindows=false` и собственным `safeDrawingPadding` содержимого. В Compose 1.7.3 ветка `usePlatformDefaultWidth=false` измеряет по `Configuration.screenHeightDp` и затем принудительно задаёт размер окну: на API35 запрошенные 1600px попадали в frame1516px. Одного переключения `decorFitsSystemWindows` недостаточно. Общий контейнер применяется к preview, подтверждению P/N/S/N и карусели сохранённых фото. Проверять оба направления поворота, все границы изображения и видимость Close при открытой клавиатуре в подтверждении P/N/S/N.


## Серверная готовность Android 0.9.1(10) — 02/Nov/2026

Локальный аудит завершён. Все 49 Retrofit-запросов текущего Android сопоставлены с фактически зарегистрированными Laravel-маршрутами; multipart POST профиля использует прежний _method=PUT. Три новых метода draft/photo зарегистрированы в обоих контурах /api/android и /api/mobile, с bearer-авторизацией и mobile.log-card.role. Android наследует общую реализацию; изменения общего контроллера и media-service затрагивают также mobile API/PWA.

Проверены 64 разных сценария, 638 assertions: исходный прогон 58 тестов дал 57 успешных и одно устаревшее ожидание; адресный повтор исправленного Draft-теста и шести новых legacy/lock-проверок дал 7/7. Описание API Draft сохраняется в WO, а имя общего каталожного Unit не меняется. Изменено только ожидание теста, storeDraft идентичен Git HEAD. Код приложения в этом аудите не менялся. Дополнительные случаи проверили оба API-контура: прежний payload Qty=1, отказ Qty>1 без идентичности и блокировку нового photo endpoint после Post Disassembly inspection.

Тестирование изолировано: локальный MySQL OSPanel/127.0.0.1:3306, отдельная aviatechnik_testing_android_20261102_qa1, отдельные media/views/uploads. Канонические тесты запускаются из ignored копий, где заменены только имя testing-БД и fixture-пути; добавлены запреты внешних HTTP/jobs. Приложение и его routes/services/models берутся из этого checkout. Исходный тестовый bootstrap выполняет migrate:fresh, поэтому его принудительное имя БД заменено только в ignored копии, а фактическая загрузка этой копии проверяется Reflection до запуска. Адресный повтор использует ту же собственную схему после подтверждения отсутствия pending migrations. Общая aviatechnik_testing и прежние recovery-артефакты сохраняются отдельно; сверка снимков записана в итоговом JSON. Внешний OCR, архивный мост, production и фактический APK из Play/code9 не использовались.

### Граница обратной совместимости

| Операция | Результат |
|---|---|
| Прежние маршруты и базовые поля ответов | Не удалены; текущий Android игнорирует неизвестные JSON-поля |
| Фото без upload_id | Сохраняют прежний контракт; защита от повторного commit действует при переданном ключе |
| Log Card create с Qty=1 и старым payload | Работает в обоих API-контурах |
| Log Card create с Qty>1 без unit_index | HTTP422, карта не создаётся; нужен новый клиент с раздельными экземплярами |
| Чтение существующей карты | Существующие карты чтением не переписываются; полная замена карты старым клиентом отдельно не проверялась |
| Фото после блокировки Post Disassembly | HTTP423 без записи/запроса OCR для обычного Technician |

Полную совместимость старых клиентов заявлять нельзя: исторический DTO создания не передавал unit_index. Защиту от неоднозначных экземпляров не отключать ради старого payload. При переносе серверной версии предусмотреть обновление клиентов для сценариев Qty>1; те же требования касаются старых клиентов общего /api/mobile. Бинарный runtime Play/code9 этим аудитом не покрыт.

### План переноса серверной части

Только после отдельного согласования переноса и проверки целевого сервера:

| Каталог верхнего уровня | Назначение |
|---|---|
| /app | Draft/photo API, явный No ASSY, раздельные Unit, idempotency и освобождение временных нормализованных фото |
| /routes | Защищённые GET/PUT draft и POST photo для обоих API-контуров |
| /public | Исправление восстановления null/пустой ASSY в PWA |

Текущая Blade-страница уже добавляет filemtime к URL JS, поэтому для этого JS-исправления отдельная правка шаблона не нужна. Новых migrations/Composer-зависимостей в мобильном diff нет. Не переносить test/runtime/credentials/signing-файлы. Не объединять с посторонними незавершёнными правками.

Перед переносом требуется отдельно проверить схему целевого сервера: user_ui_settings (JSON value и unique user_id/scope/key), workorder_media_sequences (unique WO/collection), media.custom_properties, log_cards.component_data, компоненты/Part Groups и coverage.choice_slot, workorder_assy_configuration_choices, process_names.identity_name, manuals.log_card_order. Эти предпосылки подтверждены только локально, с 298 применёнными миграциями и без pending. Если целевая схема отстаёт, сначала подготовить отдельный проверенный план схемы; не выполнять автоматически общий migrate. Состояние route/config/view cache на целевом сервере также не проверялось; никакого удалённого cache clear или deploy здесь не было.

Порядок выпуска: согласовать серверный перенос и обновление старых клиентов для Qty>1 → после переноса проверить API/No ASSY/фото без дублей → сверить upload certificate AAB с Play Console → внутренний тест code10 на реальном Android (поворот, камера/EXIF/OCR, Confirm, draft и recovery). До готовности серверного API телефон проверять на локальном стенде. AAB 0.9.1(10), собранный ранее, не пересобирался: Android-исходники в этом аудите не менялись.

Артефакты ignored: storage/app/codex/android-server-readiness-20261102/server-readiness.json, runtime-audit.json, junit.xml, junit-focused.xml, draft-baseline-proof.json, schema-ready.json и снимки общей testing-БД. Итоговый JSON содержит финальную сверку и очистку временной БД. Коммитов, push, production-доступа и публикации нет.

## Повторная локальная сверка после Git — 02/Nov/2026, Laravel 670db432

Пользователь обновил только Git, свежая копия production-БД не предоставлена. Эта проверка относится к текущему checkout и локальному MySQL; production не подключался. Выводы предыдущей серверной сверки на 41dcf1d1 сохраняются как исторические.

Подтверждены 49/49 маршрутов текущего Android, 67/67 тестов и 652 assertions одним последовательным прогоном. Включены AndroidApi/MobileApi, NativeParity, PhotoRetry, LogCardWeb, LandscapePhoto, LegacyContract и три новых случая WorkorderStdApplicability. Проверены явный No ASSY, раздельные unit_index/S/N, общий серверный черновик, подтверждение фото, запрет portrait, повтор без дублей и права/блокировки. Это API/рендер-тесты; физический поворот и камера заново не проверялись.

Отдельная временная БД aviatechnik_testing_android_refresh_20261102_qa1 успешно создана с нуля: 299 миграций, 131 таблица, pending=0. Требуемые мобильные поля присутствуют. Общая aviatechnik_testing сохранена: контрольные SQL-снимки до/после совпали побайтно по размеру/SHA256. Метаданные основной локальной avia также совпали до/после.

Основная локальная avia пока НЕ готова: 117 таблиц, 278 записей migration history, 30 файлов миграций отсутствуют в этой истории (это сравнение имён, а не простая разность счётчиков). Фактически отсутствуют workorder_media_sequences, workorder_assy_configuration_choices, manual_part_group_coverages.choice_slot, process_names.identity_name и manuals.log_card_order, включая необходимые уникальные индексы двух отсутствующих таблиц. Поэтому обновление Git само по себе не устраняет ошибки фото/Processes/Log Card на основной локальной БД. Полный перечень pending и метаданные находятся в ignored local-schema-before.json. Миграции основной базы не запускались.

В новом Git добавлена 2026_10_30_120000_create_manual_bushing_guides (три таблицы справочника), а также серверная логика исключения пустых/ignored STD из QA, последовательности, напоминаний и прогноза. Три новых STD-теста прошли. Android Processes по-прежнему возвращает исторические TDR-процессы без нового признака N/A; полный перенос настольного QA/STD интерфейса не заявляется. Изменений mobile API в новом commit нет; пять файлов прежних мобильных исправлений совпали с резервной копией перед Git. Прежняя таблица переноса /app, /routes, /public относится только к мобильному diff на 41dcf1d1, а не ко всему новому обновлению AVIA со своими views, assets и миграцией.

Все 56 Kotlin-файлов, состав исходников и SHA256 подписанных APK/AAB совпали с ранее проверенной сборкой 0.9.1(10); пересборка не требуется по результатам этой проверки. Граница старого клиента сохранена: Qty>1 без unit_index отклоняется HTTP422 без создания карты. Реальный Play/code9, подпись upload certificate в Console и целевая production-схема здесь не проверены.

Следующий локальный этап: сохранить резервную копию avia и проверить обновление её отдельной копии с существующими данными; затем согласовать применение нужных миграций к основной локальной базе. Успешный migrate:fresh во временной БД не доказывает безопасный upgrade существующих данных. Для сравнения с production нужна актуальная выгрузка её структуры/истории миграций либо свежая копия БД; Git её не заменяет.

Доказательства: storage/app/codex/android-local-refresh-20261102/server-readiness.json, junit.xml, runtime-audit.json, schema-ready.json, local-schema-before.json, local-schema-after.json и cleanup.json. Собственная временная БД удалена после успешной сверки, созданные media и журналы остаются локальными ignored артефактами. Код приложения/Android в этом аудите не менялся; обновлены результаты в документации.
