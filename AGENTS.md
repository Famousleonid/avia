Язык ответов: всегда отвечай пользователю на русском языке, если он явно не попросил другой язык.

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

Глобальные подтверждения AVIA:
- Для действий, меняющих данные или запускающих необратимую операцию из веб-интерфейса, используй общее внутреннее окно `window.confirmDialog(...)` из `public/js/main.js`.
- Не используй нативный browser `confirm()` / `window.confirm()` для новых или изменяемых подтверждений.
- Если `window.confirmDialog` недоступен, действие не выполнять: показать пользователю ошибку через штатное уведомление.

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

Quantum knowledge rule:
- For any task involving Quantum, Oracle `QCTL`, RO/WO sync, manager Quantum data, WO estimates, invoices, or Quantum table/field discovery, first read the `quantum/` folder.
- Treat `quantum/` as the canonical project knowledge base for confirmed Quantum tables, fields, relationships, safe connection rules, and open questions.
- When new Quantum facts are confirmed against real data, update the relevant file in `quantum/`; when a previous note is proven wrong, correct it there instead of leaving duplicate conflicting notes.
- Keep hypotheses and unresolved items in `quantum/open-questions.md` until they are verified.
- Store Quantum CSV/XLS/log exports under `quantum/log/`, not in the root of `quantum/`, so the knowledge files stay separate from generated outputs.

Codex local browser login:
- For visual QA that requires signing in to `avia.loc`, use the local ignored credentials file at `storage/app/codex/browser-admin-login.json`.
- The account is `codex.admin@avia.local` with Admin role; do not commit or print the password.

AVIA manual package imports:
- When the user provides a directory containing numbered scanned IPL/FIG PDFs plus the standard XLS/XLSX workbook and a manual number, use the project skill `$avia-import-manual-package` from `.agents/skills/avia-import-manual-package`.
- The normal required inputs are only the source directory and manual number. The skill must prepare Parts, Service Bulletins, LLP/Log Card, NDT, CAD, Paint, and PRL/KIT mappings, run a dry audit, and ask only about unresolved data conflicts.
- Production access remains read-only. Apply reviewed imports locally and prepare exact production handoff artifacts/instructions for the user; never import or deploy through production SSH.

AVIA production SSH access and diagnostics:
- RETIRED/DO NOT USE: `51.222.203.80` is the old server and must never be used for production diagnostics, even if SSH still accepts the saved key.
- Current production SSH is `72.251.11.16`, port `27`, user `mcxq8917`. This SSH host is separate from the public web A record for `aviatechnik.ca`; do not substitute the web IP as the SSH host.
- The current production Laravel application directory is `/home/mcxq8917/aviatechnik.ca`.
- Use private key `storage/app/codex/aviatechnik_target_whc_codexdiag_ed25519` and pinned host-key file `storage/app/codex/target_whc_known_hosts`. Both are intentionally ignored by Git through `storage/app/.gitignore`.
- The verified current-production ED25519 host-key fingerprint is `SHA256:BY4ZcFoK0bYDGPzhRMbhVaCThrJcAyKZ+HDLK6RF/MI`.
- Use the Windows OpenSSH client with batch mode and strict host-key checking:
  `C:\WINDOWS\System32\OpenSSH\ssh.exe -i storage\app\codex\aviatechnik_target_whc_codexdiag_ed25519 -p 27 -o BatchMode=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile=storage\app\codex\target_whc_known_hosts mcxq8917@72.251.11.16`
- The old key `storage/app/codex/production_whc_codexdiag_ed25519`, old host-key file `storage/app/codex/production_known_hosts`, and old fingerprint `SHA256:268W+wKyPHqomq3vlvE1ME2yvzQlsTchMHRZjqw877I` belong to the retired server and must never be used for current production.
- Never bypass host verification with `StrictHostKeyChecking=no`. Stop and report any mismatch against the pinned current-production fingerprint.
- Production access is strictly read-only and diagnostic even though the hosting account may technically have write permissions. It may be used to inspect code, file metadata, logs, process state, configuration structure, deployed behavior, and database data needed to identify root causes.
- Never print, copy into chat, or otherwise expose the private key, `.env` contents, database credentials, API tokens, session data, or other production secrets.
- Allowed database operations are read-only statements such as `SELECT`, `SHOW`, `DESCRIBE`, and `EXPLAIN`. Do not execute stored procedures or functions unless they are confirmed read-only. Never run `INSERT`, `UPDATE`, `DELETE`, `REPLACE`, DDL, migrations, seeders, imports, restores, or any query that can acquire destructive locks or modify server state.
- Do not edit, create, upload, copy, move, rename, delete, chmod, or chown production files. Do not clear or rebuild caches, run migrations, install dependencies, restart services, change cron or queue state, run jobs, or invoke application commands that can modify data.
- Do not deploy to production and do not use remote Git operations such as pull, push, checkout, reset, merge, or clean. The user performs production deployment manually by copying files.
- The production deployment currently has no `.git` directory. Establish deployed state by inspecting the actual production code and file timestamps, without modifying them.
- Network access may require sandbox escalation. Request it only for a concrete read-only SSH diagnostic command.

AVIA archive bridge access and diagnostics:
- The archive bridge is a Windows host on the AVIA local network at `192.168.0.212`.
- The bridge application directory is `C:\Avia`.
- Connect over SSH as the dedicated standard user `codexdiag`.
- The local private key is `storage/app/codex/codex_bridge_192_168_0_212.key`. It is intentionally ignored by Git through `storage/app/.gitignore`. Never commit, print, copy into chat, or expose its contents.
- Do not ask the user to provide or paste the SSH key while this file exists. Use the existing ignored key directly from its local path. If it is missing or rejected, report that the saved bridge access must be restored; do not ask the user to search for the key contents in chat history.
- Use the Windows OpenSSH client:
  `C:\WINDOWS\System32\OpenSSH\ssh.exe -i storage\app\codex\codex_bridge_192_168_0_212.key -o BatchMode=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=NUL codexdiag@192.168.0.212`
- Network access from Codex may require sandbox escalation. Request it only for a specific read-only SSH diagnostic command unless the user explicitly authorized a remote change.
- Before SSH, connectivity can be checked locally with:
  `Test-NetConnection 192.168.0.212 -Port 22`
- Main bridge files:
  - sync script: `C:\Avia\sync_media.php`
  - sync log: `C:\Avia\sync_media.log`
  - archive destination root: `\\F519\backup4\SHOP\WORK ORDERS`
  - archive API base: `https://aviatechnik.ca/api/archive`
- The API bearer token is stored on the bridge and is a secret. It must never be printed in tool output, logs, responses, commands shown to the user, or committed to the repository.
- Do not ask the user to provide or paste the bearer token while it remains configured on the bridge. For diagnostics, reuse it only inside the remote bridge process/command and return only the HTTP status or redacted result. Never copy the token from the bridge back into the local workspace or conversation.
- Relevant production API routes are:
  - read-only: `GET /api/archive/pending-media`
  - read-only: `GET /api/archive/download/{media}`
  - changes data: `POST /api/archive/mark-synced`
- The corresponding application code starts at:
  - `app/Http/Controllers/Api/ArchiveController.php`
  - archive routes in `routes/api.php`
  - token middleware `app/Http/Middleware/EnsureArchiveToken.php`
  - token configuration `config/archive.php`
- Default bridge work is diagnostic and read-only. Do not edit `sync_media.php`, change the scheduled task, restart services, manually run the sync script, copy/delete archive files, or call `mark-synced` without explicit user authorization. Running the sync script can copy files and change `archive_synced_at`.
- For a bridge incident, check the complete chain in this order:
  1. Confirm port 22 and SSH access.
  2. Read the tail of `C:\Avia\sync_media.log` and establish the last successful media ID, workorder, timestamp, pending count, and exact error.
  3. Inspect the scheduled task that invokes `C:\Avia\sync_media.php`: enabled state, trigger interval, last run time, last result, action executable, arguments, and working directory. Do not change it during diagnosis.
  4. Confirm whether `sync_media.php` is currently running or stuck before considering a manual run.
  5. Check `GET /pending-media` from the bridge without exposing the bearer token.
  6. For a concrete media/workorder, verify the database/API record, physical source file, authenticated download response, destination path, and only then the acknowledgement step.
  7. Compare timestamps carefully because the local workstation, production server, bridge, and log may use different time zones.
- Useful read-only commands after SSH:
  - `Get-Content C:\Avia\sync_media.log -Tail 200`
  - `Get-Item C:\Avia\sync_media.php, C:\Avia\sync_media.log | Select-Object FullName,Length,LastWriteTime`
  - `Get-ScheduledTask | Where-Object { $_.Actions.Arguments -match 'sync_media\.php' } | Select-Object TaskName,TaskPath,State`
  - `Get-ScheduledTaskInfo -TaskName '<confirmed task name>'`
  - `Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -match 'sync_media\.php' } | Select-Object ProcessId,Name,CreationDate,CommandLine`
- A zero pending count does not prove that all photos were archived. Cross-check later valid media IDs and source files. Historically, stale unsynced media rows with missing files blocked later valid rows when a database `limit` was applied before filesystem validation.
- Missing source files must not be marked as successfully archived. Use the local `archive:audit-media` command as a dry run first; use `--mark-missing` only after the listed orphan records have been checked.
- After any fix or deployment, verify the original failing media/workorder end to end: it appears in pending, downloads with HTTP 200, is copied to the expected workorder archive folder, receives a successful acknowledgement, and disappears from pending. Also confirm the next scheduled run remains healthy.
