# Quantum Connection And Safety

## Подключение

Подтверждено локальными проверками:

```text
Oracle Database: 19c
Service alias: MAXQPROD
Service name: CCTL
Schema: QCTL
Current user: CRYSTAL
TNS_ADMIN: C:\oracle\ora92\network\admin
```

`C:\oracle\ora92\network\admin\tnsnames.ora` уже содержит Oracle/TNS
настройки. Для кода и sync-скриптов пароль не хранить в репозитории.

## Переменные окружения

Общие переменные:

```text
ORACLE_USER=CRYSTAL
ORACLE_DSN=MAXQPROD
ORACLE_PASS=<из окружения>
TNS_ADMIN=C:\oracle\ora92\network\admin
```

Manager-specific переменные, если нужен отдельный namespace:

```text
QUANTUM_MANAGER_ORACLE_USER
QUANTUM_MANAGER_ORACLE_PASS
QUANTUM_MANAGER_ORACLE_DSN
```

Если manager-specific переменные не заданы, `manager/sync_manager.php`
использует общие `ORACLE_USER`, `ORACLE_PASS`, `ORACLE_DSN`.

## PHP OCI8

В текущем окружении обычный `php` может не загружать OCI8 по умолчанию.
Рабочий запуск:

```powershell
php -d extension=php_oci8_19.dll manager\sync_manager.php --mode=tables
```

## Read-only правило

Quantum использовать только для чтения.

Разрешено:

```text
SELECT
WITH ... SELECT
all_tab_columns / all_tables diagnostics
```

Запрещено:

```text
INSERT / UPDATE / DELETE / MERGE
DROP / ALTER / CREATE / TRUNCATE
EXEC / EXECUTE / FOR UPDATE
COMMIT / ROLLBACK
DBMS_ / UTL_
```

Не обходить `assertReadOnlySql()` в bridge/runner скриптах.
Не добавлять credentials в код, README, CSV или логи.

