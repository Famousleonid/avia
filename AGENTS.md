Для UI/CSS задач работай медленнее и проверяй не только целевой div, но всю цепочку layout сверху вниз:
container -> page wrapper -> card -> card-body -> form -> row/grid -> column -> inner scroll area.

Перед тем как сказать, что fixed:
1. проверь родительские ограничения по width/height/min-height/overflow/display/flex/grid;
2. ищи причину на уровень выше, а не только в проблемном блоке;
3. если задача про scroll, обязательно проверь, откуда берётся ограниченная высота;
4. если задача про width/columns, обязательно проверь, что проценты считаются от нужного родителя;
5. не останавливайся на первом правдоподобном патче — доводи до реальной причины;
6. в ответе коротко указывай root cause, а не только что поменял;
7. для простых CSS/UI багов сначала делай диагностику структуры, потом patch.

При спорных визуальных багах предпочитай более глубокую проверку, даже если это медленнее.

Laravel/PHP tests:
- Не запускать несколько `php artisan test ...` параллельно в этом проекте.
- Testing database у проекта общая, Laravel во время тестов может дропать/создавать таблицы; параллельные запуски дают гонку вроде `migrations table doesn't exist`, `table already exists`, `unknown table`.
- Все feature/unit test suites запускать последовательно, один `php artisan test ...` за раз.
- Если случайно был параллельный запуск и testing schema сломалась, не считать это failure бизнес-логики; повторить нужные suites по одному.

Общее правило диагностики для этого проекта:
- Нельзя закрывать задачу на уровне "теория не сработала", "не угадал" или первого правдоподобного патча.
- Для задач поиска, данных, бизнес-логики и UI сначала проходи всю цепочку от входного действия до результата: пользовательский запрос/route -> controller/service/tool -> query/model -> реальные данные в БД/JSON -> формат ответа/рендер.
- Перед изменением кода проверь, где именно ломается фактический сценарий пользователя, а не только похожий тестовый случай.
- Если есть конкретное значение из интерфейса или базы, проверь его напрямую в текущих данных и только потом меняй код.
- После патча проверяй тем же сценарием, который не работал, плюс минимальным автотестом, если это разумно.
- В финальном ответе коротко указывай root cause и что проверено; не выдавай гипотезу за факт.

Правило сохранения UI-состояния в этом проекте:
- Если пользователь просит сохранить состояние, размер окна/панели, позицию, фильтры, поиск, выбранную вкладку, checkbox, колонки или другое некритичное UI-состояние, сохраняй это per-user в `user_ui_settings`.
- Не используй browser `localStorage` или `sessionStorage` и не добавляй fallback'и на browser storage.
- Для явных структурированных настроек используй `window.UserUiSettings` со scope/key.
- Для старого localStorage-like поведения используй `window.UserScopedStorage` или `window.UserScopedSessionStorage`.

Date format project rule:
- User-facing dates must be displayed as `dd/mmm/yyyy`, for example `12/may/2026`.
- In PHP views/services use `format_project_date()` or Blade `@projectDate(...)` for display.
- In PHP request parsing use `parse_project_date()` when accepting user typed project dates.
- Keep ISO `Y-m-d` only for database values, JSON/API contracts, tests, filenames, sorting keys, and native HTML `<input type="date">` values.
