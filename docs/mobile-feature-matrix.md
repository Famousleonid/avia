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
| Bootstrap / ролевая навигация | `GET bootstrap` | ❓ | ✅ | Shell и ролевая навигация реализованы; в аудите 29/Oct/2026 проверены DTO и локальный API, полный обход ролей на устройстве не выполнялся. |
| Logout | `POST auth/logout` | ❓ | ✅ | |
| Profile (просмотр/правка/пароль) | `GET/PUT profile`, `POST profile/password` | ❓ | ✅ | 21.07 (e5c56f9): поля+Team, аватар (POST+_method=PUT), смена пароля |
| Workorders: список | `GET workorders` | ❓ | ✅ | 21.07: поиск, Done, My/All; проверено на устройстве с реальным аккаунтом |
| Workorder: карточка (storage, arrival box) | `GET workorders/{id}`, `PATCH …/storage`, `PATCH …/arrival-box` | ❓ | ✅ | 21.07: просмотр + редактирование storage/arrival (cf05b7c); Android-контур добавил recorded_by_name |
| Workorder: медиа (фото, thumb) | `GET/POST/DELETE …/media`, `GET media/{id}/thumb|file` | ❓ | ✅ | 21.07: ленты + съёмка с камеры + fullscreen-зум (cf05b7c); удаление фото — нет |
| Drafts (create/show) | `GET draft/options`, `POST drafts`, `POST draft-units` | ❓ | ✅ | 21.07: создание с новым unit, ролевой чип по can_create_draft |
| Tasks (даты) | `GET …/tasks`, `PUT …/tasks/{task}/dates` | ❓ | ✅ | 21.07 (975f3a2): даты календарём по can_edit_*; проверено на устройстве |
| Components + фото | `GET/POST …/components`, `PATCH components/{id}`, `POST components/{id}/photo` | ❓ | ✅ | 21.07 (ac07d35): список+фото детали; создание нового компонента — нет |
| TDR attachments | `POST …/component-attachments`, `PATCH/DELETE component-attachments/{tdr}` | ❓ | ✅ | 21.07 (995320d): attach/правка/удаление + раздельные страницы Parts/Order New/Missing |
| Log Card: состав, ASSY, несколько manuals, экземпляры | `GET/POST …/log-card`, `GET …/log-card/template`, `PATCH …/log-card/…` | ❓ | 🔨 | 29/Oct/2026: в локальные Android-исходники перенесены assy_groups/available_manuals, явный No ASSY, Add/Deselect, unit_index/units_assy и отдельные S/N. Это разработка поверх 0.9.1, публичный APK не обновлялся. Итог проверки ниже. |
| Горизонтальный режим WO / фото | `POST …/media` и web mobile upload | ❓ | 🔨 | Локальный перенос 29/Oct/2026: отдельная правая панель камеры, выбранная категория, внутренний scroll; адаптивное preview остаётся до ответа сервера, ошибка/отмена retake его не сбрасывает. Поворот физического телефона не проверен. |
| Log Card: черновик пользователя / WO | web `GET/POST user-ui-settings`; native `GET/PUT …/log-card/draft` | ❓ | 🔨 | Общий DB scope `mobile.log-card.draft`, key `wo:<id>`, user_id текущего пользователя. Native пишет последовательно, навигация ждёт flush; SavedStateHandle держит pending при пересоздании Activity. PWA No ASSY исправлен и проверен после Parts → Log Card. |
| Log Card: Photo + read | `POST …/log-card/photo` | ❓ | 🔨 | В локальном Android: P/N + S/N, проверка доступного варианта, Confirm до сохранения фото, ручной ввод при отказе OCR. Новый token-auth endpoint использует тот же обработчик, что PWA; внешние OCR-запросы в локальном QA отключены. |
| Processes (даты) | `GET …/processes`, `PATCH tdr-processes/{id}/dates` | ❓ | ✅ | 21.07 (975f3a2): Start/Finish/Promise по правам; проверено на устройстве |
| Materials | `GET materials`, `PATCH materials/{id}` | ❓ | ✅ | 21.07 (ba82aad): список+поиск, правка только description; чипы разделов в shell |
| Paint (lost, owner message) | `GET paint`, `POST paint/lost`, `DELETE paint/lost/{id}`, `POST paint/messages` | ❓ | ✅ | 22.07 (5bd1c0a): вкладки WO/Lost, правка дат (source=paint), lost с камеры, сообщение владельцу |
| Machining: список/WO/шаги | `GET machining`, `GET machining/workorders/{id}`, `PATCH machining/steps/{id}` | ❓ | ✅ | 22.07 (5bd1c0a): My WO, шаги деталей, правка дат своих шагов, Hide closed |
| Machining: фото/PDF | `…/photos`, `…/doc-pdfs`, `…/pdfs`, `DELETE …/media/{id}` | ❓ | ✅ | 23.07 (3f5f62d): сетка фото/карусель/съёмка/удаление, PDF-сканы + внешний просмотрщик, счётчики на WO-экране |
| Push-уведомления | `POST push-token` | ❓ | ⚠️ | Android содержит FCM service и регистрацию токена; сквозная доставка уведомлений в аудите 29/Oct/2026 не проверялась. |
| Measurements / Dimensions | нет в mobile API | — | — | сознательно вне v1 (см. mobile_measurements_plan) |

История заметных решений:
- 21.07.2026 — Android e2e на реальном устройстве (login→bootstrap), контракт UserDto уточнён: role строкой, team {id,name}, capabilities map.
- 15.07.2026 — Android-контур `/api/android/*` (наследование iOS-контроллера), миграция `platform` в mobile_api_tokens.

## Предыдущая сверка PWA / Android — Laravel 3d356495, 29/Oct/2026

Проверен Laravel `3d356495` после fast-forward с `332e711e` и Android `0.9.1` / versionCode `10` (восстановленный исходный commit `7a95c5c`). Обновление Laravel не обновляет отдельный APK. Более ранние отметки в таблице сохраняют исторические даты проверок и не означают нового полного e2e-аудита.

| Сценарий | Текущая PWA | Android 0.9.1 | Основание |
|---|---|---|---|
| WO в landscape | Прокручиваемые группы фото слева, неподвижная панель камеры справа, выбранная категория и Test camera | Общая вертикальная Compose-компоновка и переход в системную камеру; отдельной аналогичной панели в коде нет | PWA проверена в браузере 844×390; Android — сверка кода, без проверки поворота на физическом телефоне |
| Ориентация загружаемого фото | Клиентская проверка и общий MobileLandscapePhotoProcessor на сервере | Тот же серверный processor в Android API | Тесты подтвердили 422 и отсутствие media для portrait, затем успешное сохранение landscape в testing-БД |
| Ошибка/отказ сохранения кадра | Отклонённый кадр и Retake остаются в памяти текущей страницы; отмена пересъёмки сохраняет preview | WorkorderDetailScreen обнуляет reviewPath сразу после вызова асинхронного upload; ViewModel хранит ошибку, без состояния повторного открытия этого кадра | Сверка JS/Kotlin; реальная камера и прерывания Android отдельно не проверялись |
| Log Card: состав | Читает assy_groups, available_manuals, assembly_choices; сериализует manual_id, unit_index, units_assy и явный выбор ASSY | Читает только groups/separate; создание передаёт component_id, ipl_group, included | Проверка актуальных DTO/рендера и отдельная JVM-проба: assy_groups отбрасываются, два экземпляра сериализуются одинаково |
| OCR | Log Card распознаёт P/N и S/N через сервер с подтверждением; Shipping Draft также имеет распознавание шильдика | ML Kit формирует кандидаты S/N в Log Card после успешного upload; в Draft аналогичного OCR нет | Сверка контроллеров/JS/Kotlin; Shipping-тест использует fake внешнего сервиса, реальные фото во внешний OCR не отправлялись |
| Новый компонент в Parts | Create New Component / Create & Select внутри picker | Привязка существующей детали и её фото; соответствующего create-вызова в AviaApi нет | PWA picker и исходный код обоих клиентов |
| Обычные фото WO | Есть удаление через внутреннее подтверждение | Удаление обычных WO-фото отсутствует в клиентском AviaApi; удаление Machining media реализовано отдельно | Сверка маршрутов, JS и Kotlin |

Валидация: release-прогон Android — 18 тестов без ошибок, lint 0 ошибок / 12 предупреждений; APK/AAB совпали с сохранёнными 0.9.1. В эмуляторе проверены установка, старт и открытие формы Login, без входа в production. Восемь сохранённых локальных ответов API успешно декодированы настоящими Android DTO. После pull прошли 16 серверных тестов / 118 assertions (`AndroidApiTest`, `MobileLandscapePhotoTest`, `MobileShippingDraftRecognitionTest`); отдельный Android landscape contract test также прошёл.

Ограничения локального стенда: рабочая БД `avia` отстаёт от кода — Processes возвращает 500 без process_names.identity_name, шаблон Log Card возвращает 500 без workorder_assy_configuration_choices. Ошибка шаблона воспроизведена и в PWA; это общий локальный schema blocker. Рабочая БД не мигрировалась в этом аудите; три миграции из pull применены только к aviatechnik_testing (298 миграций). Старый MobileApiTest ожидает три ASSY-choice в первой группе, тогда как фактический ответ делит их по IPL-семьям на группы 2+1; все три компонента остаются в ответе. Физическое устройство, реальная съёмка и полный авторизованный обход Android UI остаются непроверенными.

## Повторная сверка после мобильного обновления — 29/Oct/2026

Актуальная основа: Laravel `41dcf1d1`, Android `0.9.1` / versionCode `10`, исходный commit `7a95c5c` в `C:/Users/lipik/Documents/Codex/Recovered/avia-android-git`. Android checkout не изменялся. Предыдущий раздел сохраняет результаты более раннего прогона; новые результаты приведены здесь.

### Что переносить в Android

| Приоритет | Область | Обновлённая PWA | Проверенное отличие Android / необходимые изменения |
|---|---|---|---|
| P0 | Контракт Log Card | Объединённые IPL/Alternative-группы, `assy_groups`, `available_manuals`, `assembly_choices`, отдельные экземпляры | Настоящий Android DTO отбросил единственную ASSY-группу из реального нового ответа API: видимые `groups/separate` стали `0/0`. Добавить все эти структуры, загрузку template с `manual_id`, выбор собственного родителя и редактирование ASSY. |
| P0 | Сохранение экземпляров | Каждая строка передаёт `component_id + unit_index`, `units_assy`, S/N и явную ASSY | Android читает `unit_index`, но `CreateLogCardView.rows()` его не отправляет. Два экземпляра сериализуются одинаково. Реальный API: при нескольких родителях такой запрос получает 422 `Select an ASSY`; без родителей получает 200 и сохраняет две одинаковые строки без `unit_index`. Добавить идентичность экземпляров в DTO, UI-state и payload; отдельно усилить серверную защиту от такого неоднозначного запроса. |
| P1 | Выбор состава | Пустой начальный выбор, Add/Deselect, крупная строка варианта, скрытие остальных вариантов только своей группы | Android по умолчанию выбирает первый вариант и включает строки. Перенести явный выбор и предупреждение без варианта; отправлять только выбранные строки. `No ASSY` должен сохранять `assy_selection_explicit=1` и пустой `assy_option_id`. |
| P1 | Черновик и поворот | Per-user/per-WO DB draft; навигация ждёт запись, после создания черновик удаляется | В Android выбранные варианты/исключения находятся в обычном `remember(tpl)`, API настроек отсутствует. Нужны ViewModel/SavedStateHandle для текущего экрана и DB draft через новый token-auth API для восстановления после ухода. Сохранять группу, вариант, unit_index, ASSY и S/N. Восстановление при пересоздании Android Activity оценено по коду, не прогоном поворота на устройстве. |
| P1 | WO в landscape | Список групп прокручивается слева, камера с названием выбранной группы остаётся справа | Android использует общую вертикальную Column и камеры в каждой строке. Сделать адаптивную компоновку с отдельной панелью камеры, safe-area/insets и ограниченной высотой внутреннего scroll. Manifest не запрещает поворот; отсутствие специального layout не означает блокировки landscape. |
| P1 | Жизненный цикл фото | Отказанный/несохранённый кадр остаётся в preview; успех только после ответа сервера | WO и Log Card Android закрывают review сразу после вызова async upload. Retake также сбрасывает review до результата новой съёмки; отмена системной камеры не восстанавливает старое preview. Нужны состояния review/uploading/error/saved, retry, сохранение кадра/категории и явное подтверждение удаления. Закрывать preview только после подтверждённого успеха. |
| P1 | Проверка ориентации | Проверяется сам файл с EXIF; portrait отклоняется, не теряя preview | Серверная проверка общая для обоих клиентов и работает. Добавить раннюю клиентскую проверку Android до upload, без поворота/обрезания портрета ради обхода. Превью сейчас имеет фиксированную высоту 280dp в AlertDialog; сделать адаптивным для короткого landscape. Путь камеры уже хранится через rememberSaveable — это сохранить. |
| P1 | Log Card Photo + read | Предпросмотр P/N + S/N, проверка соответствия детали, Confirm перед сохранением фото | Native сначала сохраняет фото в Logs и только затем запускает ML Kit для S/N. Нет выбора варианта по P/N и соответствующего двухэтапного подтверждения. Нужен общий native API распознавания/подтверждения с token auth; существующий web-маршрут использует сессию и CSRF. |

Для контрактов создания расширить `LogCardCreateRowDto`: `manual_id`, `unit_index`, `units_assy`, `serial_number`, `assy_serial_number`, `manual_part_group_id`, `manual_part_group_option_id`, `manual_part_group_choice`, `assy_selection_explicit`, `assy_option_id`, совместимый `component_assembly_id`. Расширения сервера делать аддитивно для `/api/mobile` и `/api/android`. API настроек должен читать/писать `user_ui_settings` только текущего пользователя; не подменять DB draft локальным хранилищем клиента.

Браузерные recovery-параметры getUserMedia и обходы S26 не переносятся буквально в native TakePicture. Переносится поведение: проверка фактического кадра, доступная повторная съёмка, сохранение preview, восстановление после фона/поворота и отсутствие ложного успеха. Реальная камера, EXIF конкретного телефона, прекращение процесса и работа при сетевом обрыве требуют отдельного device-прогона.

### Найденный дефект PWA: восстановление No ASSY

Воспроизведено в браузере на вымышленной `QA-SINGLE-PARENT`, имеющей единственного родителя: Add → No ASSY → S/N `QA-NO-ASSY` → Parts → Log Card. После возврата S/N сохранился, но ASSY снова стала `QA-ASSY-A`.

Причина: middleware ConvertEmptyStringsToNull преобразует пустой `assemblies[].value` в `null` при записи UI settings. `restoreDraft()` сравнивает option с `String(assy.value)`, то есть `"null"`, и не восстанавливает пустую option. При единственном родителе рендер заранее ставит selected на него. См. `public/js/mobile-log-card.js:241` и `:266`. Черновик в БД с `value:null` и ошибочное восстановление подтверждены отдельно. Нормализовать null/пустую строку в единую семантику явного No ASSY и добавить проверку восстановления для одного и нескольких родителей; исчезнувший вариант по-прежнему не подменять другим. В рамках аудита приложение не исправлялось.

### Что проверено локально

- Рабочий `http://avia`: вход QA, WO и его горизонтальный экран 844×390; синтетический portrait отклонён с сохранённым preview и Retake. Рабочий Log Card template всё ещё получает 500: отсутствует `avia.workorder_assy_configuration_choices`. Рабочая схема не мигрировалась.
- Для полного UI Log Card поднят временный сервер **только 127.0.0.1:8097** на полностью мигрированной `aviatechnik_testing` (298 миграций), с обычным входом Technician и вымышленным WO990029. У сервера отдельная session cookie, внешний OCR отключён. После проверки сервер остановлен, временная вкладка закрыта.
- PWA на 390×844 и 844×390: выбор второго варианта скрывает первый; Deselect сбрасывает только свой выбор; Add без варианта оставляет группу выключенной, показывает внутреннее `Select a part variant.` и фокусирует первый вариант. Восстановились вариант, S/N и различные ASSY двух экземпляров после Parts → Log Card. Отдельно воспроизведён описанный дефект No ASSY с одним родителем.
- На 844×390 Log Card header=47px, scroll=283px, overflow auto; Add/Deselect=48px, поля S/N и Photo + read=52px, видимые строки варианта больше 64px. Состояние не потерялось при смене тестового viewport. Это браузерная проверка PWA, не тест поворота native Activity.
- Через UI создана карта из четырёх выбранных строк. Прямое чтение testing-БД подтвердило выбранный вариант, две строки Spring с `unit_index=1/2`, разные S/N, собственные ASSY и явный No ASSY. После успеха DB draft пуст. Исторические WO и рабочая БД для этого сценария не использовались.
- Последовательный PHP-прогон: **71/72 теста, 832 assertions**, 0 errors. AndroidApiTest 7/7, LogCardAssemblyIdentityTest 21/21, MobileLandscapePhotoTest 4/4, MobileLogCardWebTest 8/8; MobileApiTest 31/32. Единственный failure — `test_mobile_api_can_create_draft_with_draft_instruction`, строка393: ожидает имя Unit `API Draft`, фактически `QA Unit`; сам Draft и его description создаются. Этот сбой не относится к фото/Log Card и не исправлялся в аудите.
- Дополнительно **4 диагностических Android API теста / 20 assertions**: новый template, независимые экземпляры и No ASSY, отказ без обязательного выбора ASSY, принятие текущего неоднозначного запроса без номеров экземпляров. Последний тест фиксирует существующий дефект, а не желаемое поведение. Ещё **1 Android landscape contract test / 9 assertions**: portrait → 422 без media; landscape → 200 и сохранённое media.
- Свежая JVM-проверка настоящими скомпилированными Android DTO подтвердила отбрасывание ASSY-группы и потерю полей экземпляров при сериализации запроса. Полный Android UI с авторизованным входом, настоящая камера и физическое устройство в этом повторном аудите не проверялись; предыдущие результаты release build не выдаются за новый device-прогон.

Артефакты (ignored): `storage/app/codex/android-check-20261029/current-mobile-tests.xml`, `current-android-parity.xml`, `current-android-landscape.log`, `current-contract-decoding.log`, `updated-android-duplicate-instances.json`, `current-fixture-state.json`, `current-log-card-layout.json`, `current-pwa-landscape.jpg`, `current-pwa-rejected.jpg`, `current-pwa-log-card-landscape.jpg`, `current-pwa-no-assy-restore.jpg`, `current-pwa-created-log-card.jpg`. Данные изолированного QA-стенда оставлены в testing-БД для воспроизведения; следующий обычный migrate:fresh их удалит.

Рекомендуемый порядок работ: (1) исправить восстановление No ASSY в PWA и определить аддитивный native API черновика/OCR; (2) обновить Android контракт и создание/редактирование Log Card; (3) обеспечить сохранность фото и состояния при ошибках/повороте; (4) добавить landscape-компоновку и крупные controls; (5) проверить эти сценарии на локальном Android runtime и реальном телефоне перед выпуском. Коммиты, изменение production и выпуск APK в этот аудит не входили.

## Выполненный локальный перенос — 29/Oct/2026

Этот раздел заменяет рекомендации предыдущего аудита результатами реализации. Основа сервера — `41dcf1d1`; изменения пока не закоммичены и не опубликованы. Production не использовался.

**PWA No ASSY исправлен.** В `restoreDraft()` null и пустая строка восстанавливают пустую option. В браузере после Parts → Log Card остались No ASSY у детали с одним родителем и двух экземпляров с несколькими родителями; S/N сохранился. Доказательство: `storage/app/codex/android-check-20261029/fixed-pwa-no-assy.jpg`.

**Android-исходники обновлены** в `C:/Users/lipik/Documents/Codex/Recovered/avia-android-git`, поверх `7a95c5c` / 0.9.1 (10). Старый `E:/dev/avia-android` не обновлялся. Перенесены перечисленные выше P0/P1: новый контракт, Add/Deselect, несколько manuals, независимые экземпляры и ASSY, DB draft, отдельная камера справа в landscape, адаптивный preview, сохранение исходного кадра при ошибке/отмене retake, двухэтапные Photo + read / Confirm. В старых данных пустой/нечисловой `units_assy` читается как неизвестное количество, без сбоя Android-декодирования. Подробности клиента — `MOBILE_PARITY.md` в Android checkout.

Серверный контракт дополнен общими `/api/mobile` и `/api/android` маршрутами `GET/PUT …/log-card/draft`, `POST …/log-card/photo`, полями выбора ASSY при PATCH строки и проверкой `unit_index` при создании нескольких экземпляров. Настройки принадлежат только текущему пользователю/WO. Фото получает native media URL с token auth; при сохранении передаётся контекст строки. Миграции для этих изменений не нужны.

### Проверки реализации

- **50 PHP-тестов / 625 assertions, все прошли:** AndroidApiTest, MobileLogCardWebTest, MobileLandscapePhotoTest, LogCardAssemblyIdentityTest, MobileNativeParityTest и Log Card-тесты MobileApiTest. Включены изоляция черновика, No ASSY, номера экземпляров, независимая правка ASSY, запрет portrait и сохранение фото только после Confirm. OCR в тесте подменён; внешних запросов нет. Лог/XML: `port-final-server.log`, `port-final-server.xml` в ignored QA-каталоге.
- **25 Android debug unit tests прошли повторно после финальной принудительной компиляции:** 9 контрактных, 10 Log Card и 6 SerialNumberCandidates. Итоговый debug APK содержит последнюю защиту черновика при повторной загрузке; совпадение 49 Kotlin-файлов с Android checkout проверено по SHA-256. Это проверки JVM, не UI Activity.
- **Шесть сценариев настоящего Android Retrofit прошли через локальный HTTP**, не mock API: вход/новый template с legacy-пустым количеством; DB draft с No ASSY; создание с независимыми S/N и изменение/очистка ASSY; JSON null удаляет draft; фото Log Card сохраняется только после Confirm; обычное WO landscape-фото загружается, portrait получает 422 без дополнительного media. Контекст `row_index=3` проверен чтением testing-БД. Лог: `local-native-smoke.log`.
- Release APK со сжатием R8 собран; **28 release unit tests прошли**, включая три проверки DEX-сигнатур/сериализаторов API и OCR-ресурсов в сжатом APK. `lintRelease` завершился успешно: **0 ошибок, 12 предупреждений, 2 информационных замечания**. Сборка/тесты/lint — `port-complete-build.log`; полный lint-отчёт — `android-port-20261029/app/build/reports/lint-results-release.html`.

Все мутации выполнялись только в `aviatechnik_testing` на вымышленных WO990029/990030. Локальный QA HTTP слушал только `127.0.0.1:8097`, имел отдельную session cookie и отдельные media disks; внешний OCR отключён. После проверки временный сервер остановлен. Рабочая БД `avia` не мигрировалась: её ранее описанный schema blocker остаётся. Это не результат проверки production.

Финальные артефакты в ignored `storage/app/codex/android-check-20261029/`: `port-final-verification.json` (результаты и SHA-256), `port-final-debug-refresh.log`, `avia-local-mobile-parity-debug.apk` (65 369 760 bytes; SHA-256 `14CCEFD73B71E5A73E65A2513115C9AFE1C0140A5EA12DACEF0970D6586633B8`). Debug использует локальный API на loopback:8080; для изолированного QA требуется снова запустить сервер и настроить перенаправление на PC:8097. Версия 0.9.1 (10) оставлена прежней, APK не опубликован.

**Граница проверки:** новый Android UI, поворот Activity, аппаратная камера, EXIF конкретного телефона и убийство процесса пока не проверены на runtime. Управление окнами CUA дважды завершилось timeout; разрешение на альтернативное управление эмулятором через ADB/UI Automator запрошено отдельно. Успех сборки/API не означает успешного прогона этих сценариев. Публичный APK и версия приложения не менялись.

## Android runtime — 30/Oct/2026

Следующий этап выполнен после разрешения ADB/UI Automator на локальном read-only AVD Android15/API35: 720×1600 и 1600×720, density280. Все записи — только вымышленный WO990031 в `aviatechnik_testing`, media на отдельном QA disk, HTTP loopback8097 через ADB reverse8080. Внешний OCR отключён. Production и физический телефон не использовались.

### Исправления по результатам runtime

- Пустой S/N приходит из Laravel как JSON null. Специализированный Android draft serializer преобразует его в пустую строку, сохраняя семантику null для No ASSY. Ранее весь draft не декодировался.
- Тайм-аут после сохранения фото вызывал дубликат при Retry. Необязательный `upload_id` связывает исходный кадр и повторы; сервер под блокировкой WO возвращает существующее media при том же файле/контексте. Другой файл с тем же ключом получает409. Пользователь, WO, категория и строка Log Card ограничивают контекст. Старые клиенты без ключа сохраняют прежнее поведение. См. `docs/mobile-native-android-brief.md`.
- Compose1.7.3/API35 измерял non-default-width Dialog по screenHeightDp и обрезал нижние кнопки. Один decorFitsSystemWindows=false не помог. Общая FullScreenPhotoDialog использует обычные window constraints, MATCH_PARENT и safeDrawingPadding; Review, галерея и подтверждение P/N/S/N используют её. В портрете Close теперь внутри1537px при доступном окне до1558px; короткий landscape имеет scroll действий.
- Открытая галерея восстанавливается по saveable category key и актуальным media WO, без сериализации MediaDto в Bundle.

### Проверенные сценарии

- Landscape WO: группы прокручиваются слева, выбранная категория и камера остаются справа. Категория сохраняется при повороте.
- Файлы виртуальной камеры: landscape1856×1392 и portrait1392×1856, EXIF1. Portrait отклонён, поворот экрана не обходит проверку. Keep photo и отмена Retake сохраняют исходный SHA; удаление — только через явный Discard. Ошибка сети оставляет preview с Retry. Review и ручные P/N/S/N переживают обе ориентации.
- WO upload: router задержал ответ после commit, UI показал timeout. Два запроса одного кадра дали одно новое media45; счётчик Machining2→3. Старые media32/33 оставлены как доказательство дубликата до исправления и не входят в успешный повторный тест.
- Log Card: Add без варианта показывает внутреннее предупреждение; выбранный вариант скрывает остальные только своей группы. После APK update, Parts → Log Card и поворотов сохранились No ASSY у детали с одним родителем, выбранный вариант и разные S/N/ASSY двух экземпляров.
- Photo + read: до Confirm media Logs отсутствовало. При отключённом OCR доступен ручной ввод; неверный P/N блокирует Confirm. QA-PART-A/PLATE-A сохранились при повороте и переключили вариант B→A. Delayed Confirm→timeout→Retry сохранил одно media46, Logs0→1.
- Через внутреннее Create-подтверждение создана карта из четырёх компонентных строк. Testing-БД подтвердила QA-PART-A/PLATE-A/No ASSY; QA-SINGLE-PARENT/SOLO/No ASSY; QA-SPRING unit_index1/U1/No ASSY и unit_index2/U2/QA-ASSY-B. Исключённая legacy-строка не попала в карту. DB draft стал null после успеха.

Артефакты текущего этапа (ignored): `storage/app/codex/android-runtime-20261030/`: `http.jsonl`, `delayed-uploads.jsonl`, `final-card.json`, `log-confirm-delayed.json`, `log-confirm-retry.json`, `review-final-landscape.png`, `review-final-portrait.png`, `log-confirm-final-portrait.png` и история UI observations. Неудачные первые попытки сохранены отдельно и не выдаются за финальный результат.

Граница проверки: проверены настоящий Android Activity и виртуальная камера AVD. Реальный телефон, его камера/EXIF, внешний OCR и восстановление после убийства процесса ещё не проверены. При одновременной тяжёлой сборке на слабом ПК наблюдались startup/launcher ANR; основной UI-прогон выполнялся без сборки. Рабочая схема `avia` не мигрировалась. Коммитов, push и публикации нет.


Финальная проверка кода 30/Oct: **52 PHP-теста /656 assertions**, **28 Android debug unit tests**, `assembleDebug` и `lintRelease` после основных runtime-исправлений прошли. После последующей небольшой правки подписи Fig повторены debug-сборка и unit-тесты; lint отдельно для этой текстовой правки не повторялся. Итоговый lint:0 ошибок,12 предупреждений,2 информационных замечания. Последняя сборка — `android-gallery-final-build.log`; release R8 и release unit tests в этот этап повторно не запускались, результаты29Oct выше исторические. Совпадение всех53 Kotlin-файлов сборочного каталога с обновлённым Android checkout подтверждено SHA-256; `git diff --check` проходит в обоих репозиториях.

Итоговый локальный debug APK: `storage/app/codex/android-runtime-20261030/avia-android-local-fixed-debug.apk`,65267887 bytes, SHA-256 `8DE486DF4C340931ECBC9CBAF7D15C47335E1B86E972D0AF4A01C978D8D48E36`. Сводка машинной сверки — `final-verification.json`. Версия остаётся0.9.1 (10); для запуска требуется локальный QA API и ADB reverse. Это не публичная сборка и не обновление Play.


Дополнительный контроль: галерея сохранила выбранное фото2/3 после landscape→portrait→landscape. Загрузка оригинала и thumb получила200 через локальные `/api/mobile/media/...`; визуально подтверждена тестовая сцена. Первые чёрные кадры галереи при отключённых WiFi/data не были ошибкой media API: Coil отдавал504 only-if-cached при offline network observer, хотя Retrofit через ADB reverse работал. Для проверки изображений включён сетевой статус только AVD, все API/media URL остаются loopback. После pause/resume VM однажды завис дисплей (включая launcher); выключение/включение экрана восстановило его без сброса данных. Это ограничение стенда не выдаётся за успешную проверку производительности.

Карта повторно открыта после обновления APK: All4 показывает все четыре компонента с собственными S/N, Empty0 корректно скрывает заполненные строки. Найденная подпись Fig.manual исправлена: `ipl_group` — служебная идентичность, номер Fig берётся из настоящего `component.ipl_num`. Идентичность групп, payload и данные карты не меняются.


Окончательный APK с исправленным Fig установлен поверх предыдущего без очистки данных. В UI подтверждены Fig.10/30/20, все четыре строки и S/N; после поворота доступна прокрутка сохранённой карты. Доказательства: `card-fig-fixed-portrait.png`, `card-fig-fixed-landscape.png`, `final-card-ui.json`. Галерея с настоящими загруженными пикселями — `gallery-online-landscape.png` и `gallery-online-portrait.png`. Сборка последней правки и28 тестов — `android-figure-final-build.log`; lint основных исправлений — `android-gallery-final-build.log`.

После завершения Android UiAutomation bridge, read-only AVD и временный PHP-сервер127.0.0.1:8097 остановлены; отсутствие их процессов подтверждено. Рабочие сервисы OSPanel не останавливались. Вымышленные QA-данные и ignored артефакты оставлены для воспроизведения. Финальное чтение testing-БД повторно подтвердило четыре строки, null draft и только два новых keyed media. В Git не добавлялись `.env` и секреты. Коммитов и публикаций нет.


## Восстановление Android после SIGKILL — 30/Oct/2026

Этот этап добавляет настоящий process-death прогон к предыдущим проверкам. Read-only AVD Android15/API35, локальный debug0.9.1(10), только вымышленный WO990032/id272, user140, manual86 в `aviatechnik_testing`. Фото — отдельный `codex-test-runtime/process-recovery` disk; HTTP127.0.0.1:8097 через ADB reverse8080. Production и рабочая схема `avia` не использовались.

Для зачётных случаев Activity переведена в STOPPED с сохранённым состоянием задачи, затем SIGKILL отправлен только проверенному PID пакета через run-as. Проверены исчезновение старого PID, reason=SIGNALED/status9 и другой PID после запуска. Задача не удалялась, данные не очищались. Force-stop при установке нового APK и reboot для ремонта стенда не засчитываются как process-death восстановление.

| Сценарий | PID до → после | Результат |
|---|---|---|
| Несохранённое фото WO | 7032 → 7307 | Вернулись тот же JPEG по SHA-256 и категория Machining. Автоматической загрузки нет. После проверки явный Discard удалил только новый локальный кадр; media47/48 не изменились. |
| Ещё не отправленный draft | 7307 → 7476 | При отключённом reverse сервер ещё содержал PD-MANUAL, UI уже PD-FINAL-PENDING. После SIGKILL изменение восстановилось и сохранилось в DB; остальные строки и ASSY не изменились. После доказательства тестовый S/N возвращён в PD-MANUAL. |
| Log Card: процесс убит в системной камере перед Done | 6546 → 6749 | Результат TakePicture принят новым процессом, восстановлены файл и цель QA-PART-B. Logs до Confirm пуст. |
| P/N/S/N перед Confirm, после исправления | 6749 → 6887 | Вернулось сразу окно подтверждения, ручной PD-MANUAL, кандидаты распознавания и исходный JPEG. Повторный Read photo и автоматический Save не потребовались. |
| После подтверждённого Save | 6887 → 7032 | Одно Logs media48, локальный исходник удалён после успеха. После следующего SIGKILL старое preview/Confirm не появилось; draft и счётчики сохранились. |

В UI после завершающего SIGKILL отдельно сверены Unit1/PD-U1/No ASSY и Unit2/PD-U2/QA-ASSY-B. В DB по-прежнему четыре включённые строки и одна исключённая legacy-строка; у единственного родителя QA-SINGLE-PARENT сохранён явный No ASSY. Готовая Log Card в этом прогоне не создавалась: проверялся её черновик.

### Найденный и исправленный дефект

До исправления photoRead и recognition жили только в памяти LogCardViewModel. После SIGKILL кадр сохранялся, но окно откатывалось на Read photo. Ручные P/N/S/N уже сохранялись через rememberSaveable и возвращались после лишнего повторного Read — это был сброс этапа, а не потеря байтов фото или введённого S/N.

Теперь этап, распознанные кандидаты и сообщение OCR сохраняются в SavedStateHandle. JPEG остаётся в filesDir, его байты в Bundle не записываются. Recovery-ключи очищаются при сбросе фото и подтверждённом сохранении. Confirm ждёт загрузки данных карты/template. Изменены только Android LogCardScreen.kt и NameplateConfirmation.kt; серверный production-код в этом этапе не менялся.

Внешний OCR отключён. До исправления проверен ручной fallback; после исправления только QA-router для WO272 подставляет синтетические P/N/S/N-кандидаты после обычных auth/file-проверок. Так проверено восстановление непустого recognition без внешнего сервиса. Реальный OCR и физический телефон этим результатом не покрыты.

### Проверки и артефакты

`assembleDebug` и28 debug unit tests прошли; проверены новые recovery-ключи в DEX и совпадение всех53 Kotlin-файлов сборочного каталога с Android checkout. Release/R8/lint в этом этапе повторно не запускались. Коммитов и публикации нет.

Все пять зачётных случаев таблицы выполнены на финальном APK. Первоначальный успешный WO Save на предыдущем APK создал media47; финальный повтор проверил восстановление и явный Discard без изменения существующих media.

Артефакты ignored: `storage/app/codex/android-process-20261030/`: `final-process-verification.json`, `final-regression-result.json`, `flow-result.json`, `camera-flow-before-fix.json`, `camera-flow-result.json`, `restored-unit-ui.json`, `*-death.json`, `*-exit-info.txt`, `*-activities-before.txt`, `flow-observations/`, `android-process-build.log`. Пиксели: `wo-final-restored.png`, `draft-final-restored.png`, `lc-camera-restored.png`, `lc-confirm-after-kill.png`, `lc-confirm-saved.png`, `restored-unit-1.png`, `restored-unit-2.png`.

Первые попытки были искажены системными ANR AVD, startup timeout UI Automator и сбросом ADB-перенаправлений. На ПК было95% занято ОЗУ, свободно346МБ из8ГБ. Эти попытки не объявлялись успешным восстановлением. После стабилизации стенда выполнены отдельные зачётные случаи выше. Сохранность файла сама по себе не принималась за успешное восстановление UI.

Граница результата: SIGKILL фонового процесса с сохранённой задачей Android. Удаление задачи из Recents, clear-data, force-stop и перезагрузка устройства — другие сценарии; их восстановление здесь не заявляется.

Новый локальный APK: `storage/app/codex/android-process-20261030/avia-process-recovery-debug.apk`, 65267887 bytes, SHA-256 `82770BD91D4F64A1AAF4C953B3D2788A85E2625F7A54F9D95708BAA631D0850C`. Он требует локального QA API; версия0.9.1(10) оставлена прежней.

После финального повтора UI Automator, read-only AVD и временный PHP QA-сервер остановлены. Отсутствие собственных процессов15832/20836/12456 подтверждено в `cleanup.json`; рабочие сервисы OSPanel и другие приложения не останавливались. `git diff --check` проходит в обоих репозиториях. Доказательства и вымышленные testing-данные сохранены локально.

## Удаление задачи из Recents и reboot — подготовка 30/Oct/2026, проверка заблокирована

Новый запрос пользователя расширяет предыдущий SIGKILL-прогон: отдельно проверить фото WO, неподтверждённое фото Log Card/P/N/S/N и ещё не отправленный draft после удаления карточки приложения из недавних и перезагрузки Android. Эти сценарии **пока не проверены**; предыдущие успешные результаты SIGKILL на них не распространяются.

До начала зачётных действий стенд оказался нестабильным: System UI ANR, перезапуски system_server и DeadSystemException системных процессов; не удалось получить устойчивый исходный UI приложения. Подготовительный reboot не засчитывается как восстановление фото или draft. Пробовались read-only codex-r8 (длительная первоначальная распаковка системных пакетов), подготовленный read-only avia с программной графикой и avia с Intel OpenGL/720×1600. Последний вариант загрузился за115с, но системные зависания продолжились. На ПК наблюдалось90% использования физической памяти; phpstorm64/PID16704 имел открытое окно и около2594МБ private memory. Это диагностика стенда, а не доказательство потери данных или ошибки приложения; единственная причина системных сбоев не установлена.

Независимый контроль локального API и `aviatechnik_testing` выполнен: вымышленный WO990032/id272, user140, прежние четыре включённые строки draft с независимыми S/N/ASSY и media47/48 с прежними SHA-256. Финальное состояние draft/media/наличия карты полностью совпало с исходным. Production не использовался, приложение не менялось, сборка и unit-тесты повторно не запускались. Использовался прежний APK с SHA-256 `82770BD91D4F64A1AAF4C953B3D2788A85E2625F7A54F9D95708BAA631D0850C`.

Артефакты в ignored `storage/app/codex/android-task-reboot-20261030/`: `blocked-verification.json` (ноль завершённых сценариев восстановления), `baseline.json`, `final-state.json`, `boot-services.txt`, `boot-crash.txt`, `diag-activities.txt`, `diag-window.txt`, `diag-crash.txt`, `initial-app.png`, журналы AVD и история UI. Подготовлены отдельные команды для Recents swipe и reboot с проверкой task ID/boot ID; их наличие не означает выполнения сценария. Для продолжения запрошено полностью завершить PhpStorm и освободить память. Временный UI Automator, эмулятор и QA-сервер остановлены; отсутствие собственных процессов фиксируется в `cleanup.json`. Другие приложения и рабочие сервисы OSPanel не останавливались.


## Recents и reboot — повторная проверка завершена 30/Oct/2026

После полного закрытия PhpStorm выполнены шесть зачётных сценариев на том же debug APK0.9.1(10), read-only AVD avia/API35, Intel OpenGL,720×1600/density280 и виртуальной камере. Только WO990032/id272 в `aviatechnik_testing`, локальный HTTP127.0.0.1:8097 через ADB reverse8080 и отдельные QA media disks. Предыдущий раздел о блокировке описывает первую попытку подготовки, а не итог этого повтора.

| Состояние | Удаление из Recents | Перезагрузка Android |
|---|---|---|
| Уже сохранённый серверный draft | Вернулся с прежними строками/S/N/ASSY | Вернулся с прежними строками/S/N/ASSY |
| Ещё не отправленная правка S/N | Потеряна; загружен прежний серверный S/N | Потеряна; загружен прежний серверный S/N |
| Несохранённое фото WO/Machining | JPEG цел, превью не восстановилось | JPEG цел, превью не восстановилось |
| Фото Log Card до Confirm | JPEG цел; подтверждение/ручной S/N не вернулись | JPEG цел; подтверждение/ручной S/N не вернулись |

Удаление выполнено реальным системным accessibility-действием Close у наблюдаемой карточки Aviatechnik в Recents: task12→13,13→14,14→15 после новых запусков; старые task ID исчезли и процессы завершились. Два первоначальных свайпа не удалили карточку и не засчитаны. Три зачётных `adb reboot` подтвердили разные kernel boot ID; задачи специально не удалялись, данные не очищались, приложение между событиями не переустанавливалось. Первоначальный reboot с незавершённой UI-проверкой сохранён отдельно и исключён из зачётной шестёрки.

Четыре зачётных исходника виртуальной камеры1856×1392/EXIF1 сохранились в filesDir с прежними SHA-256, но их превью/назначение не поднялись даже после открытия соответствующего WO/Log Card. У Log Card не вернулись этап подтверждения, ручной S/N и непустые кандидаты распознавания. Для unsent-проверок до события отключался только QA reverse: UI содержал новое значение, а DB — PD-MANUAL; после события снова показан PD-MANUAL. Автоматического upload/создания карты не было.

Серверные media47/Machining и48/Logs, их файлы/SHA и draft полностью совпали с исходным состоянием. В последнем UI отдельно сверены Unit1/PD-U1/No ASSY и Unit2/PD-U2/QA-ASSY-B; у единственного родителя также остался No ASSY. Четыре включённые строки и исключённая legacy-строка не изменились.

Причина соответствует коду: пути/категория/строка фото и ручные поля живут в rememberSaveable, этап/recognition и logCardPending — в SavedStateHandle. Это состояние задачи Android, а не долговременный индекс файлов или очередь неотправленных правок. Нужен отдельный долговременный recovery-журнал по текущему пользователю и WO: путь/назначение/этап/P/N/S/N/кандидаты и pending draft; JPEG хранить отдельно, загрузку выполнять только после явного подтверждения. При таком исправлении учесть существующую очистку CameraFiles файлов старше24ч при следующей съёмке, чтобы активный неподтверждённый кадр не удалялся по возрасту. В этом этапе эти исправления ещё не внесены.

Проверен SHA установленного APK `82770BD91D4F64A1AAF4C953B3D2788A85E2625F7A54F9D95708BAA631D0850C` и совпадение всех53 Kotlin-файлов с исходниками сборки. Код приложения не менялся; unit-тесты/сборка/release/lint здесь повторно не запускались. Внешний OCR отключён: кандидаты P/N/S/N подставлял только локальный QA-router после штатных auth/file-проверок. Реальный телефон/OCR и производительность не проверялись.

Ограничение стенда: после закрытия IDE сохранились тайм-ауты запуска телефонной службы и System UI ANR, поэтому нехватка памяти не была установлена как единственная причина. Системные диалоги обрабатывались Wait, зависшее отображение — screen off/on; приложение не force-stop и не clear-data. Для снижения фоновой нагрузки только в read-only AVD отключены Search/Messages/System Intelligence. Итогом считалось наблюдение настоящего UI после стабилизации, а не наличие файла на диске.

Артефакты ignored: `storage/app/codex/android-task-reboot-retry-20261030/`: `final-task-reboot-verification.json`, `*-prepared.json`, `*-result.json`, `*-removal.json`, `*-reboot.json`, `*-reboot-before.json`, `*-original-*.jpg`, `*-before.png`, `*-after-open.png`, `*-wo-after-open.png`, `restored-unit-ui.json`, `final-local-frames.json`, `final-build-identity.json`, `system-ui-waits.jsonl` и `flow-observations/`. Production, рабочая схема avia, коммиты и публикация не затрагивались.

После итоговой сверки UI Automator, read-only AVD и временный QA-сервер остановлены. Отсутствие собственных процессов20228/21336/17628 подтверждено в `cleanup.json` и добавлено в итоговую JSON-сводку. Рабочие сервисы OSPanel и другие Windows-приложения не останавливались. `git diff --check` прошёл в обоих репозиториях; прежние незавершённые изменения сохранены.

## Долговременное Android recovery — 02/Nov/2026

Исправлена выявленная 30/Oct граница SavedStateHandle: неотправленный draft и метаданные фото теперь записываются атомарно в приватный noBackupFilesDir/recovery-v1 по API-окружению, подтверждённому user ID и WO. JPEG остаётся отдельным файлом. Сохраняются категория/строка/юнит, этап чтения и подтверждения, кандидаты и ручные P/N/S/N. Токен и пароль в журнал не попадают. Новая правка сначала фиксируется на диске; после серверного ACK очищается только подтверждённая версия. Если серверный draft изменился, предлагается явный выбор между восстановленным и серверным вариантом. Фото загружается только по явному подтверждению. Это дополнительный журнал к серверному per-user/per-WO draft, а не его замена.

Убрана очистка неподтверждённых JPEG по возрасту24ч. При Retake старый кадр сохраняется до принятия нового; Cancel оставляет прежний кадр/поля. Явный Discard и подтверждённый Save сначала очищают соответствующий индекс, затем исходник. Смена пользователя блокирует старый recovery handle. Повреждённый журнал не перезаписывается, исходники сохраняются.

На финальном debug APK пройдены шесть реальных сценариев на read-only AVD avia/API35 с виртуальной камерой:

| Состояние | Удаление из Recents | Перезагрузка Android |
|---|---|---|
| Неотправленный S/N draft | Вернулся и синхронизировался после подключения локального API | Вернулся и синхронизировался после подключения локального API |
| Фото WO/Machining | Вернулись точный JPEG, превью и категория | Вернулись точный JPEG, превью и категория |
| Фото Log Card до Confirm | Вернулись точный JPEG, назначение, этап Confirm, ручной S/N и оба кандидата | Вернулись точный JPEG, назначение, этап Confirm, ручной S/N и оба кандидата |

Recents закрывался наблюдаемым accessibility-действием Close карточки Aviatechnik; проверены исчезновение прежних task ID/PID. Reboot подтверждён сменой kernel boot ID. Ни force-stop, ни clear-data, ни reinstall вместо этих событий не применялись. После холодного запуска пользователь открывает соответствующий WO/Log Card; автоматический переход к последней карте не заявляется. У фото не было автоматического upload; явный Discard удалил только его исходник/индекс. Для pending draft до события отключался только ADB reverse: сервер ещё содержал PD-MANUAL, UI — новое значение; после возврата локальной связи подтверждённая правка очистила pending-журнал. Затем тестовый S/N возвращён в исходное состояние.

Стенд изолирован: только synthetic WO990032/id272, user140, aviatechnik_testing, localhost8097 через ADB reverse8080, отдельные media disks; внешний OCR отключён. Кандидаты задавались QA-router после штатных auth/file-проверок. Реальный телефон, камера/EXIF и внешний OCR этим прогоном не покрыты. Повторявшиеся системные ANR и display freeze устранялись Wait/screen off-on, отдельно от результата приложения; другие Windows-программы не завершались.

assembleDebug и42 JVM-теста прошли (включая14 новых тестов durable journal, изоляции, Retake/Cancel, позднего ACK, повреждённой записи, уникальных имён и отсутствия age purge). Все56 Kotlin-файлов сборочного каталога совпали с Android checkout. Статический ELF-контроль шести arm64/x86_64 библиотек показал PT_LOAD alignment16КБ; это отдельно от финальной проверки релизного AAB.

APK: storage/app/codex/android-durable-recovery-20261102/avia-durable-recovery-debug.apk, SHA-256 ebaf5e2b14755e93859bbd9b335ac450aa47c717389829f725375d5b814ed18b, 65558963 bytes, версия0.9.1(10). Он предназначен для локального QA API. Артефакты ignored в том же каталоге: final-durable-verification.json, final-build-identity.json, final-state.json, final-journal.json, *-result.json, *-removal.json, *-reboot.json, исходные кадры/SHA, screenshots и журналы UI. Production, публикация и Git commit/push не выполнялись.

Дополнительная UI-проверка подтвердила поворот Log Card Confirm в landscape, доступность Retake/Close через прокрутку короткой панели и сохранность прежнего JPEG/ручного S/N после отмены камеры. В финальном UI независимо сверены Unit1/PD-U1/No ASSY и Unit2/PD-U2/QA-ASSY-B. Подготовительные прерывания QA-helper (тап Done, ожидание перехода камеры и прокрутка) исправлялись с продолжением того же кадра; они не засчитывались отдельными успешными событиями.

Журнал создаётся для новых правок и снимков. Старые JPEG без сохранённых метаданных не привязываются автоматически к WO/строке по догадке и не удаляются; восстановление их прежнего назначения не заявляется.

### Локальная релизная сборка — 02/Nov/2026

После остановки AVD выполнены assembleRelease, testReleaseUnitTest, lintRelease и bundleRelease с R8/resource shrinking. Пройдено 45 release JVM-тестов, включая проверку Retrofit generic signatures/serializers в minified APK; lint без Error/Fatal (17 предупреждений, 2 информационных сообщения). Подпись APK проверена apksigner, подпись AAB — jarsigner; private signing credentials не выводились и не переносились в репозитории. APK прошёл zipalign -c -P16, все arm64/x86_64 ELF в APK/AAB имеют PT_LOAD alignment16КБ, BundleConfig.pb декодирован штатными bundletool/protobuf-классами: PAGE_ALIGNMENT_16K. Проверка на реальном 16КБ устройстве не выполнялась.

Версия0.9.1/versionCode10 сохранена. Пользователь подтвердил максимальный уже загруженный в Play Console code9, поэтому code10 подходит следующему AAB. Подписанные артефакты и release-verification.json находятся в ignored каталоге android-durable-recovery-20261102. Upload certificate в Play Console ещё не сравнивался с подписью сборки; загрузка/публикация не выполнялись. Release runtime на телефоне, настоящая камера/EXIF/OCR и совместимость production API требуют отдельной проверки перед распространением; production в этой задаче не использовался.

### Повтор после обновления Git — 02/Nov/2026, Laravel 670db432

Обновлён только код, свежая production-БД не загружена. Локально пройдены 67 тестов / 652 assertions, включая три новых STD-сценария; все 49 запросов текущего Android имеют серверные маршруты. На собственной временной БД успешно выполнены 299 миграций (131 таблица, pending=0). Проверены фото/landscape validation, Confirm/retry, No ASSY, per-unit Log Card и общий черновик; новый прогон не является повторной проверкой камеры/поворота на устройстве.

Основная локальная avia отстаёт: 30 migration-файлов отсутствуют в её истории; нет workorder_media_sequences, workorder_assy_configuration_choices, coverage.choice_slot, process_names.identity_name и manuals.log_card_order. Основная схема не менялась, SQL-снимки общей testing-БД совпали до/после. Нужна резервная копия и проверка upgrade отдельной копии существующей БД; свежая схема с нуля не проверяет перенос старых данных.

Новый Git добавляет Bushing Guide с отдельной миграцией и серверное исключение пустых/ignored STD; мобильный API нового поля N/A не добавляет. Прежние мобильные исправления сохранены. Все 56 Kotlin-исходников и SHA256 release APK/AAB совпали с проверенной сборкой 0.9.1(10). Production не подключался; его готовность не подтверждена. Подробности и ограничения — в разделе повторной сверки mobile-native-android-brief.md; доказательства — ignored android-local-refresh-20261102/server-readiness.json.
