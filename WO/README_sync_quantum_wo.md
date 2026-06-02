# Quantum WO Preview Export

Purpose: first read-only WO extraction from Quantum into local CSV/XLS only.

This does not write to avia DB and does not call any Laravel API. The next step
will be a neutral staging/buffer table only after the exported fields are proven.

All generated CSV/XLS/log files are written to:

```text
WO/log
```

## Files

```text
sync_wo.php
quantum_wo_query.php
.env.sync_quantum_wo.example
```

## Required output fields

```text
WO_NUMBER
UNIT_PN
CUSTOMER
SERIAL_NUMBER
DESCRIPTION
OPEN_DATE
```

Normal CSV/XLS exports contain only these columns. Use `--metadata` or
`--field-search` for separate diagnostic files.

## Usage

```bash
php sync_wo.php
php sync_wo.php --days=90
php sync_wo.php --wo=W107739
php sync_wo.php --format=xls
php sync_wo.php --format=both
php sync_wo.php --count
php sync_wo.php --metadata
php sync_wo.php --field-search
```

Default mode:

```text
last 30 days
limit 1000 rows
format csv
```

Useful filters:

```text
--since="2026-06-01 00:00:00"
--status=WIP
--customer=Sky
--limit=5000
--all
```

## Safety

Quantum is read-only. The runner blocks non-SELECT SQL, semicolons, and dangerous
SQL keywords. Oracle credentials must come from environment variables:

```text
ORACLE_USER
ORACLE_PASS
ORACLE_DSN
```
