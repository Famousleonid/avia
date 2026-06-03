# Quantum WO Extract Notes

Цель файла: короткая справка для будущей интеграции Quantum WO -> avia по аналогии с уже сделанной RO-синхронизацией.

Этот документ не заменяет большой `WO/README_quantum_fields.md`; здесь только выжимка: что уже известно, что нужно достать из Quantum для WO, и какие правила нельзя нарушать.

---

## 1. Главное правило

Quantum доступен только для чтения.

Разрешено:

```text
SELECT
WITH ... SELECT
all_tab_columns / read-only diagnostics
```

Запрещено из кода:

```text
INSERT / UPDATE / DELETE / MERGE
DROP / ALTER / CREATE / TRUNCATE
EXEC / EXECUTE / FOR UPDATE
```

Oracle логин/пароль не писать в код. На локальном мостике использовать переменные окружения:

```text
ORACLE_USER
ORACLE_PASS
ORACLE_DSN
```

Текущие известные значения окружения/подключения:

```text
Oracle Database: 19c
Service Alias:   MAXQPROD
Service Name:    CCTL
Schema:          QCTL
```

---

## 2. Уже подтвержденная Quantum цепочка из RO

Эта цепочка уже работает для RO и дает WO number:

```text
QCTL.RO_HEADER.ROH_AUTO_KEY
  -> QCTL.RO_DETAIL.ROH_AUTO_KEY

QCTL.RO_DETAIL.WOB_AUTO_KEY
  -> QCTL.WO_BOM.WOB_AUTO_KEY

QCTL.WO_BOM.WOO_AUTO_KEY
  -> QCTL.WO_OPERATION.WOO_AUTO_KEY

QCTL.WO_OPERATION.SI_NUMBER
  -> WO number, example W107739
```

Важно:

```text
RO_HEADER / RO_DETAIL нужны для repair order data.
Для WO sync базовой таблицей, вероятно, должна быть WO_OPERATION, а не RO_HEADER.
```

---

## 3. Подтвержденные WO-related таблицы

Подтверждено в работе RO:

```text
QCTL.WO_OPERATION
QCTL.WO_BOM
QCTL.PARTS_MASTER
```

Подтвержденные поля `QCTL.WO_OPERATION`:

| Field | Meaning |
|---|---|
| `WOO_AUTO_KEY` | Internal Quantum WO key |
| `SI_NUMBER` | WO number, for example `W107739` |
| `WO_DISP` | WO display/status, for example `WIP` |
| `COMPANY_REF_NUMBER` | Internal company reference number |
| `PNM_AUTO_KEY` | Part master key candidate |

Подтвержденные поля `QCTL.WO_BOM`:

| Field | Meaning |
|---|---|
| `WOB_AUTO_KEY` | Internal BOM row key |
| `WOO_AUTO_KEY` | Link to `WO_OPERATION` |
| `PNM_AUTO_KEY` | Part master key candidate |
| `REF` | User text field. In RO integration it is used as process/batch code, not as a relationship. |

Подтвержденное использование `QCTL.PARTS_MASTER`:

```text
PNM_AUTO_KEY links Quantum rows to part master data.
In RO sync it is used to read PN / description candidates.
```

---

## 4. Что нужно вытащить для WO

Минимальный набор для staging table:

| avia/staging field | Quantum source candidate | Status |
|---|---|---|
| `source_uid` | `WOO_AUTO_KEY` or stable composite key | Need confirm |
| `woo_auto_key` | `WO_OPERATION.WOO_AUTO_KEY` | Confirmed field |
| `wo_number` | `WO_OPERATION.SI_NUMBER` | Confirmed |
| `wo_status` | `WO_OPERATION.WO_DISP` | Confirmed |
| `company_ref_number` | `WO_OPERATION.COMPANY_REF_NUMBER` | Confirmed |
| `pnm_auto_key` | `WO_OPERATION.PNM_AUTO_KEY` | Confirmed field, meaning needs WO validation |
| `part_number` | `PARTS_MASTER` via `PNM_AUTO_KEY` | Need confirm exact column |
| `part_description` | `PARTS_MASTER` via `PNM_AUTO_KEY` | Need confirm exact column |
| `source_last_modified` | date column on `WO_OPERATION` | Need discover |
| `raw_payload` | full selected row | Needed for audit/history |

Business fields still to discover for WO creation/update:

```text
customer
unit / component PN
serial number
instruction / manual reference
opened date
closed/completed date
technician/user fields
status meaning mapping
```

Do not guess these fields. First inspect real Quantum columns and sample rows.

---

## 5. First diagnostic queries

List WO_OPERATION columns:

```sql
SELECT column_name, data_type, data_length, nullable
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name = 'WO_OPERATION'
ORDER BY column_id
```

List WO_BOM columns:

```sql
SELECT column_name, data_type, data_length, nullable
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name = 'WO_BOM'
ORDER BY column_id
```

Find useful WO date/user/status columns:

```sql
SELECT table_name, column_name, data_type
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name IN ('WO_OPERATION', 'WO_BOM')
  AND (
      column_name LIKE '%DATE%'
      OR column_name LIKE '%MOD%'
      OR column_name LIKE '%STATUS%'
      OR column_name LIKE '%USER%'
      OR column_name LIKE '%SYSUR%'
      OR column_name LIKE '%OPEN%'
      OR column_name LIKE '%CLOSE%'
  )
ORDER BY table_name, column_id
```

Preview a known WO:

```sql
SELECT
    wo.WOO_AUTO_KEY,
    wo.SI_NUMBER,
    wo.WO_DISP,
    wo.COMPANY_REF_NUMBER,
    wo.PNM_AUTO_KEY
FROM QCTL.WO_OPERATION wo
WHERE wo.SI_NUMBER = 'W107739'
```

Preview BOM rows for a known WO:

```sql
SELECT
    wb.WOB_AUTO_KEY,
    wb.WOO_AUTO_KEY,
    wb.PNM_AUTO_KEY,
    wb.REF
FROM QCTL.WO_BOM wb
JOIN QCTL.WO_OPERATION wo
  ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
WHERE wo.SI_NUMBER = 'W107739'
ORDER BY wb.WOB_AUTO_KEY
```

---

## 6. Suggested WO sync architecture

Use the same pattern as RO:

```text
Quantum Oracle
  -> local bridge in C:\avia\WO\
  -> Laravel API on avia hosting
  -> staging tables in avia
  -> parser/apply command
  -> working avia tables
```

Recommended staging tables:

```text
quantum_wo_sync_runs
quantum_wo_lines
```

Recommended local files:

```text
WO/sync.php
WO/quantum_wo_query.php
WO/.env.sync_quantum_wo
WO/.env.sync_quantum_wo.example
WO/run_sync_quantum_wo.ps1
WO/run_sync_quantum_wo_hidden.vbs
WO/install_sync_quantum_wo_task.ps1
WO/quantum_wo_sync.log
```

Recommended Laravel endpoints:

```text
GET  /api/quantum/wo-sync/state
POST /api/quantum/wo-sync
```

Recommended artisan parser:

```text
php artisan quantum-wo:apply --quiet
```

---

## 7. Incremental sync rule

RO sync uses:

```text
source_last_modified = greatest available Quantum modified date from joined source rows
recommended_since = max(source_last_modified) - 10 minutes
```

For WO sync, first discover reliable change columns on `WO_OPERATION`.

Candidate names to look for:

```text
LAST_MODIFIED
SYSUR_MODIFIED
LAST_STATUS_CHG
SYSUR_LAST_STATUS_CHG
ENTRY_DATE
OPEN_DATE
CLOSE_DATE
```

Do not use incremental sync until the change column is confirmed with real changed WO examples.

Safe first mode:

```text
manual backfill by days or explicit WO number
compact log only
no writes to Quantum
stage rows only
```

---

## 8. Mapping to avia: do later, after staging is trusted

First step must only stage Quantum WO rows.

Do not immediately write to avia `workorders` until these are confirmed:

```text
How Quantum WO maps to avia workorders.number
How Quantum PN maps to avia units/components
How Quantum customer maps to avia customers
Which Quantum statuses should create/update/close an avia workorder
Which Quantum dates are authoritative
```

The parser should keep history fields like RO does:

```text
apply_status
apply_message
applied_target_table
applied_target_id
applied_source_hash
applied_at
```

Rows should not be deleted after parsing. Keep them as audit/history.

---

## 9. Known RO lessons to reuse

Useful rules from RO integration:

```text
1. Keep Quantum read-only.
2. Stage first, parse second.
3. Use source_hash to detect actual data changes.
4. Keep raw_payload for audit.
5. Keep compact bridge logs.
6. Keep local bridge and hosted parser separate.
7. Do not create fake users for Quantum changes.
8. If a value is fixed in Quantum, the next changed source_hash should let it re-enter parsing.
```

For future ignore/hide behavior:

```text
Do not delete staged rows.
If a bad row should stop showing, mark that exact source_hash as ignored.
If Quantum data changes and source_hash changes, the row should become eligible again.
```

