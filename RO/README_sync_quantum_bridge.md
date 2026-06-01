# Quantum RO local bridge

Локальный мостик запускает только чтение Quantum и отправку строк в avia API.
Парсер `php artisan quantum-ro:apply` должен работать на сервере avia, а не на локальной машине.

## Что поставить

1. Oracle Instant Client в `C:\oracle`.
   - Нужен `oci.dll`.
   - Разрядность Oracle Client должна совпадать с PHP: x64 с x64, x86 с x86.
   - Если `ORACLE_DSN=MAXQPROD` используется как TNS alias, положите `tnsnames.ora` в `C:\oracle` или укажите `TNS_ADMIN`.
   - Если Oracle Client требует runtime, поставьте Microsoft Visual C++ Redistributable подходящей версии.

2. PHP CLI в `C:\avia\RO\PHP_8.1`.
   - В `php.ini` должны быть включены `oci8` и `openssl`.
   - `allow_url_fopen` должен быть `On`, потому что `sync.php` отправляет HTTPS запрос в avia API.
   - Пример расширения: `extension=php_oci8_19.dll`, если используется Instant Client 19.

3. Файлы мостика в `C:\avia\RO`.
   - `sync.php`
   - `quantum_ro_query.php`
   - `install_sync_quantum_task.ps1`
   - `run_sync_quantum.ps1`
   - `run_sync_quantum_hidden.vbs`
   - `.env.sync_quantum`

## Настройка .env

Создайте файл из примера:

```powershell
cd C:\avia\RO
Copy-Item .env.sync_quantum.example .env.sync_quantum
notepad .env.sync_quantum
```

Минимально заполнить:

```text
ORACLE_CLIENT_DIR=C:\oracle
TNS_ADMIN=C:\oracle
ORACLE_USER=...
ORACLE_PASS=...
ORACLE_DSN=MAXQPROD
PHP_PATH=C:\avia\RO\PHP_8.1\php.exe
AVIA_SYNC_API_URL=https://YOUR_HOST/api/quantum/ro-sync
AVIA_SYNC_API_TOKEN=...
AVIA_SYNC_TIMEZONE=America/Toronto
```

`AVIA_SYNC_API_TOKEN` должен совпадать с `QUANTUM_SYNC_TOKEN` в `.env` на сервере avia.

## Установка задачи Windows

```powershell
cd C:\avia\RO
powershell -ExecutionPolicy Bypass -File .\install_sync_quantum_task.ps1 -RunNow
```

Можно передать значения сразу параметрами:

```powershell
powershell -ExecutionPolicy Bypass -File .\install_sync_quantum_task.ps1 `
  -ApiUrl "https://YOUR_HOST/api/quantum/ro-sync" `
  -ApiToken "..." `
  -OracleUser "..." `
  -OraclePass "..." `
  -OracleDsn "MAXQPROD" `
  -RunNow
```

Будет создана задача Windows Scheduler `sync_quantum`.
Она запускается каждые 5 минут скрыто через `run_sync_quantum_hidden.vbs`.

## Проверка

```powershell
Get-ScheduledTask -TaskName sync_quantum
Start-ScheduledTask -TaskName sync_quantum
Get-Content C:\avia\RO\quantum_ro_sync.log -Tail 20
Get-Content C:\avia\RO\sync_quantum_runner.log -Tail 20
```

Основной лог `quantum_ro_sync.log` показывает результат синхронизации:

```text
date, status, quantum_rows, received, inserted, updated, unchanged, result
```

`sync_quantum_runner.log` нужен для ошибок окружения: PHP не найден, нет `oci8`, нет `openssl`, не найден `oci.dll`, не заполнен токен.

## Что должно быть на сервере avia

На сервере avia должен быть такой же токен:

```text
QUANTUM_SYNC_TOKEN=...
```

И отдельный cron каждые 5 минут:

```bash
php artisan quantum-ro:apply --quiet
```

Локальная машина не должна запускать `artisan`; она только читает Quantum и отправляет данные в hosted avia.
