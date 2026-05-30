# Quantum MRO Oracle → RO/WO Field Notes for Codex

Purpose: help Codex continue work on the local Quantum sync scripts without rediscovering already known table relations and confirmed field meanings.

This file contains only currently known/confirmed information from local SQLPlus/PHP OCI8 testing and manual checks against Quantum RO PDF/CSV exports.

---

## 1. Current architecture

Quantum Oracle is inside the local network.

Current flow:

```text
Windows Bridge PC
  -> PHP OCI8 / SQLPlus
  -> Oracle service alias MAXQPROD
  -> Quantum Oracle schema QCTL
  -> local console output + LOG + XLS export
```

For now, ignore Laravel/API integration. Current goal is to discover the correct Quantum fields and print/export:

```text
RO | Vendor | WO | Date Sent | Date Returned | Qty
```

---

## 2. Oracle connection facts

Known connection details:

```text
Oracle Database: 19c
Service Alias:   MAXQPROD
Service Name:    CCTL
Current User:    CRYSTAL
Schema:          QCTL
```

Important security rule:

```text
Do not put Oracle credentials in code.
sync.php reads credentials from Windows environment variables:
ORACLE_USER
ORACLE_PASS
ORACLE_DSN
```

Current sync.php also contains a read-only SQL guard:

```text
Only SELECT / WITH SQL should be allowed.
Dangerous keywords such as INSERT, UPDATE, DELETE, MERGE, DROP, ALTER,
TRUNCATE, CREATE, GRANT, REVOKE, EXEC, EXECUTE, FOR UPDATE are blocked.
```

---

## 3. Main known Quantum tables

RO-related tables:

```text
QCTL.RO_HEADER
QCTL.RO_DETAIL
QCTL.RO_STATUS
QCTL.RO_STATUS_HISTORY
```

WO-related tables:

```text
QCTL.WO_BOM
QCTL.WO_OPERATION
```

Currently confirmed working tables for RO -> WO join:

```text
QCTL.RO_HEADER
QCTL.RO_DETAIL
QCTL.WO_BOM
QCTL.WO_OPERATION
```

---

## 4. Confirmed RO -> WO relationship

Confirmed working relationship:

```text
RO_HEADER.ROH_AUTO_KEY
  -> RO_DETAIL.ROH_AUTO_KEY

RO_DETAIL.WOB_AUTO_KEY
  -> WO_BOM.WOB_AUTO_KEY

WO_BOM.WOO_AUTO_KEY
  -> WO_OPERATION.WOO_AUTO_KEY

WO_OPERATION.SI_NUMBER
  -> Work Order number, for example W107739
```

Visual chain:

```text
QCTL.RO_HEADER rh
    rh.ROH_AUTO_KEY

QCTL.RO_DETAIL rd
    rd.ROH_AUTO_KEY
    rd.WOB_AUTO_KEY

QCTL.WO_BOM wb
    wb.WOB_AUTO_KEY
    wb.WOO_AUTO_KEY

QCTL.WO_OPERATION wo
    wo.WOO_AUTO_KEY
    wo.SI_NUMBER
```

One RO can contain multiple WO lines. Do not assume one RO = one WO.

Example:

```text
R8907
  -> W107577
  -> W107477
  -> W107736
  -> W107738
```

---

## 5. Confirmed fields in QCTL.RO_HEADER

Known useful columns:

```text
ROH_AUTO_KEY
RO_NUMBER
VENDOR_NAME
ENTRY_DATE
OUT_DATE
OPEN_FLAG
LAST_MODIFIED
```

Meaning:

| Column | Current confirmed meaning |
|---|---|
| ROH_AUTO_KEY | Internal RO header key |
| RO_NUMBER | Repair Order number, e.g. R8917 |
| VENDOR_NAME | Vendor name |
| ENTRY_DATE | RO created/entered date |
| OUT_DATE | Date sent to vendor |
| OPEN_FLAG | T = open, F = closed |
| LAST_MODIFIED | Last modified date in Quantum |

Confirmed logic:

```text
RO_HEADER.OUT_DATE = Date Sent / sent to vendor
RO_HEADER.OPEN_FLAG = T means open
RO_HEADER.OPEN_FLAG = F means closed
```

---

## 6. Confirmed fields in QCTL.RO_DETAIL

Known useful columns:

```text
ROD_AUTO_KEY
ROH_AUTO_KEY
WOB_AUTO_KEY
SHIP_DATE
TRACKING_NUMBER
MSG_RO_SHIP_DATE
MSG_SHIP_NOTICE_DATE
MSG_VEND_ACK_DATE
MSG_EST_DELIV_DATE
LAST_DELIVERY_DATE
NEXT_DELIVERY_DATE
```

Meaning:

| Column | Current known meaning |
|---|---|
| ROD_AUTO_KEY | Internal RO detail/item row key |
| ROH_AUTO_KEY | Link back to RO_HEADER |
| WOB_AUTO_KEY | Link to WO_BOM |
| SHIP_DATE | Often empty in tested data; do not use as main sent date yet |
| TRACKING_NUMBER | Tracking number if filled |
| MSG_RO_SHIP_DATE | Message/event date, not currently main sent date |
| MSG_SHIP_NOTICE_DATE | Message/event date |
| MSG_VEND_ACK_DATE | Vendor acknowledgment message/event date |
| MSG_EST_DELIV_DATE | Estimated delivery message/event date |
| LAST_DELIVERY_DATE | Confirmed returned/received date from vendor |
| NEXT_DELIVERY_DATE | Expected/next delivery date if applicable |

Confirmed logic:

```text
RO_DETAIL.LAST_DELIVERY_DATE = Date Returned / returned from vendor
RO_DETAIL.NEXT_DELIVERY_DATE = expected next delivery / expected return
```

---

## 7. Confirmed fields in QCTL.WO_BOM

Known columns used in join:

```text
WOB_AUTO_KEY
WOO_AUTO_KEY
```

Meaning:

| Column | Meaning |
|---|---|
| WOB_AUTO_KEY | Work Order BOM row key |
| WOO_AUTO_KEY | Link to WO_OPERATION |

---

## 8. Confirmed fields in QCTL.WO_OPERATION

Known useful columns:

```text
WOO_AUTO_KEY
SI_NUMBER
WO_DISP
COMPANY_REF_NUMBER
```

Meaning:

| Column | Current known meaning |
|---|---|
| WOO_AUTO_KEY | Internal WO operation key |
| SI_NUMBER | Work Order number, e.g. W107739 |
| WO_DISP | WO display/status, e.g. WIP |
| COMPANY_REF_NUMBER | Internal Quantum/company reference number |

Important:

```text
A RO may be closed while the WO is still WIP.
This is normal: the external repair order can be finished while the main workorder continues.
```

---

## 9. Confirmed date logic from examples

### R8917

User manually confirmed:

```text
R8917 went out: 27-May-2026
R8917 came back: 28-May-2026
```

Oracle/PHP output confirmed:

```text
RO=R8917
WO=W107739
OPEN_FLAG=F
VENDOR=SkyService F.B.O. Inc.

ENTRY_DATE=27-MAY-26
OUT_DATE=27-MAY-26
LAST_MODIFIED=28-MAY-26
LAST_DELIVERY_DATE=28-MAY-26
WO_DISP=WIP
COMPANY_REF_NUMBER=M0000059334
```

Conclusion:

```text
OUT_DATE = sent date
LAST_DELIVERY_DATE = returned date
OPEN_FLAG F = closed
```

### R8880

Known from PDF/CSV and Oracle output:

```text
RO=R8880
Vendor=SkyService F.B.O. Inc.
WO lines:
  W107345
  W107580

RO Date / Ship Date in PDF: 2026-05-21
OUT_DATE=21-MAY-26
OPEN_FLAG=T
LAST_DELIVERY_DATE is empty
```

Conclusion:

```text
R8880 was sent on 21-May-2026 and is still open/not returned.
OUT_DATE = sent date
empty LAST_DELIVERY_DATE = not returned yet
OPEN_FLAG T = open
```

### R8907

Known from PDF/CSV and Oracle output:

```text
RO=R8907
Vendor=SkyService F.B.O. Inc.
WO lines:
  W107577
  W107477
  W107736
  W107738

RO Date / Ship Date in PDF: 2026-05-25
OUT_DATE=25-MAY-26
LAST_DELIVERY_DATE=26-MAY-26
OPEN_FLAG=F
```

Conclusion:

```text
R8907 was sent on 25-May-2026 and returned on 26-May-2026.
```

---

## 10. Current minimal SQL for required output

Current required output:

```text
RO | Vendor | WO | Date Sent | Date Returned | Qty
```

Current working SQL, except Qty:

```sql
SELECT
    rh.RO_NUMBER,
    rh.VENDOR_NAME,
    wo.SI_NUMBER AS WO_NUMBER,
    rh.OUT_DATE AS DATE_SENT,
    rd.LAST_DELIVERY_DATE AS DATE_RETURNED,
    NULL AS QTY
FROM QCTL.RO_HEADER rh
JOIN QCTL.RO_DETAIL rd
    ON rd.ROH_AUTO_KEY = rh.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
WHERE rh.LAST_MODIFIED >= SYSDATE - 14
ORDER BY rh.RO_NUMBER, wo.SI_NUMBER
```

For a specific RO:

```sql
SELECT
    rh.RO_NUMBER,
    rh.VENDOR_NAME,
    wo.SI_NUMBER AS WO_NUMBER,
    rh.OUT_DATE AS DATE_SENT,
    rd.LAST_DELIVERY_DATE AS DATE_RETURNED,
    NULL AS QTY
FROM QCTL.RO_HEADER rh
JOIN QCTL.RO_DETAIL rd
    ON rd.ROH_AUTO_KEY = rh.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
WHERE rh.RO_NUMBER = 'R8917'
ORDER BY rh.RO_NUMBER, wo.SI_NUMBER
```

---

## 11. Qty field is NOT found yet

The required `Qty` field is still unknown.

Quantum PDF/CSV reports show values like:

```text
To Repair
Reserved
Repaired
Qty
```

But these are not yet mapped to a confirmed Oracle column.

Important failed attempt:

```sql
rd.REPAIRED
```

Oracle error:

```text
ORA-00904: "RD"."REPAIRED": invalid identifier
```

Therefore:

```text
REPAIRED is not a physical column in QCTL.RO_DETAIL.
It may be:
- a column in another table,
- a calculated report field,
- a Crystal Reports alias,
- or stored under a different Oracle column name.
```

Current sync uses:

```sql
NULL AS QTY
```

So Qty appears empty until the real source is found.

---

## 12. Suggested safe queries to find Qty

Search columns in known nearby tables:

```sql
SELECT
    table_name,
    column_name,
    data_type
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name IN ('RO_DETAIL', 'WO_BOM', 'WO_OPERATION')
  AND (
      column_name LIKE '%QTY%'
      OR column_name LIKE '%QUAN%'
      OR column_name LIKE '%REPAIR%'
      OR column_name LIKE '%RESERV%'
      OR column_name LIKE '%DELIV%'
      OR column_name LIKE '%REC%'
  )
ORDER BY table_name, column_name
```

If not enough, broaden search to RO/WO tables:

```sql
SELECT
    table_name,
    column_name,
    data_type
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND (
      table_name LIKE 'RO%'
      OR table_name LIKE 'WO%'
  )
  AND (
      column_name LIKE '%QTY%'
      OR column_name LIKE '%QUAN%'
      OR column_name LIKE '%REPAIR%'
      OR column_name LIKE '%RESERV%'
      OR column_name LIKE '%DELIV%'
      OR column_name LIKE '%REC%'
  )
ORDER BY table_name, column_name
```

Another useful full column dump for RO_DETAIL:

```sql
SELECT
    column_id,
    column_name,
    data_type
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name = 'RO_DETAIL'
ORDER BY column_id
```

---

## 13. Current scripts structure

The local sync is split into two PHP files:

```text
sync.php
quantum_ro_query.php
```

Expected roles:

```text
sync.php
  Stable runner:
  - reads CLI parameters
  - reads Oracle credentials from environment variables
  - connects via OCI8
  - asserts SQL is read-only
  - executes SQL
  - prints console table
  - writes a same-run .log file next to sync.php
  - always writes XLS output

quantum_ro_query.php
  Frequently changed:
  - stores SELECT SQL
  - stores joins
  - stores experimental fields
  - should be the main file Codex edits while searching fields
```

Expected console/XLS fields only:

```text
RO
Vendor
WO
Date Sent
Date Returned
Qty
```

---

## 14. Safety instructions for Codex

Work only with:

```text
sync.php
quantum_ro_query.php
README_quantum_fields.md
```

Prefer changing only:

```text
quantum_ro_query.php
```

Allowed SQL:

```text
SELECT
WITH
queries to QCTL tables
queries to all_tab_columns
```

Forbidden SQL:

```text
INSERT
UPDATE
DELETE
MERGE
DROP
ALTER
TRUNCATE
CREATE
GRANT
REVOKE
COMMIT
ROLLBACK
EXEC
EXECUTE
FOR UPDATE
DBMS_
UTL_
```

Do not remove or bypass:

```text
assertReadOnlySql()
```

Do not add credentials into code.

---

## 15. Current open task

Find the real Oracle source for Qty / To Repair / Repaired / Reserved.

Once found, replace:

```sql
NULL AS QTY
```

with the confirmed real field or expression.

The target output remains:

```text
RO | Vendor | WO | Date Sent | Date Returned | Qty
```

---

## 16. 2026-05-29 current sync discovery step

Project goal for `avia`:

```text
Every 5 minutes a bridge sync will read only changed RO data from Quantum.
At this stage do not write into avia yet.
First confirm how new/changed RO rows appear in Quantum during the last couple of days.
```

Future required data shape:

```text
RO
WO
Vendor
Process / status-related field if available
Date Sent
Date Returned
Qty
```

Current important question:

```text
Which Oracle date/change field should drive incremental sync cursor?
```

Candidate already used but not fully proven:

```text
QCTL.RO_HEADER.LAST_MODIFIED
```

Current `quantum_ro_query.php` mode:

```text
Diagnostic metadata query.
It reads ALL_TAB_COLUMNS for:
RO_HEADER
RO_DETAIL
WO_BOM
WO_OPERATION
```

Because `sync.php` currently prints only fixed columns, metadata is temporarily mapped as:

| sync.php column | Diagnostic meaning |
|---|---|
| RO | table name |
| Vendor | column name |
| WO | data type |
| Date Sent | column id |
| Date Returned | nullable |
| Qty | candidate purpose |

Run:

```text
php sync.php
```

Expected purpose of this run:

```text
Find all date/change/qty/repair/reserved/process columns in the confirmed RO -> WO chain.
After reading the output/XLS, switch query back to RO rows and test changed rows for the last 2 days.
```

Result from `quantum_ro_preview_20260529_141152.xls`:

```text
RO_DETAIL has direct quantity candidates:
RO_DETAIL.QTY_REPAIR
RO_DETAIL.QTY_RESERVED
RO_DETAIL.QTY_REPAIRED
RO_DETAIL.QTY_SCRAPPED
RO_DETAIL.QTY_SCRAP_LINE
RO_DETAIL.QTY_BILLED
RO_DETAIL.QB_QTY_REPAIRED
RO_DETAIL.QB_QTY_SCRAPPED
RO_DETAIL.QTY_REC_INCR
```

Most important likely mapping:

| Report field | Quantum candidate |
|---|---|
| To Repair | QCTL.RO_DETAIL.QTY_REPAIR |
| Reserved | QCTL.RO_DETAIL.QTY_RESERVED |
| Repaired | QCTL.RO_DETAIL.QTY_REPAIRED |

Change-date candidates found in confirmed chain:

| Table | Candidate |
|---|---|
| QCTL.RO_HEADER | LAST_MODIFIED |
| QCTL.RO_HEADER | SYSUR_MODIFIED |
| QCTL.RO_DETAIL | LAST_MODIFIED |
| QCTL.RO_DETAIL | SYSUR_MODIFIED |
| QCTL.WO_OPERATION | LAST_STATUS_CHG |
| QCTL.WO_OPERATION | SYSUR_LAST_STATUS_CHG |

Next query mode:

```text
Return actual RO rows for the last couple of days.
Filter by either RO_HEADER.LAST_MODIFIED or RO_DETAIL.LAST_MODIFIED so detail-line updates are visible.
Map Qty temporarily as:
TO_REPAIR=<QTY_REPAIR>, RESERVED=<QTY_RESERVED>, REPAIRED=<QTY_REPAIRED>
```

Important:

```text
Do not use semicolons inside SQL string literals in quantum_ro_query.php.
sync.php blocks semicolon anywhere in SQL to reduce multi-statement risk.
```

---

## 17. 2026-05-29 sync.php log output

`sync.php` now writes a per-run `.log` file in the same `RO` folder, next to the generated `.xls`.

Naming:

```text
No specific RO:
quantum_ro_preview_YYYYMMDD_HHMMSS.log

Specific RO:
quantum_ro_R8917_YYYYMMDD_HHMMSS.log
```

The log currently contains the same compact console table plus generated file paths.

Small encoding cleanup:

```text
The old one-character truncation marker in sync.php was a non-UTF8 Windows byte.
It was replaced with ASCII "..." so the file can be patched safely.
```

Console output note:

```text
2026-05-29 adjustment:
sync.php prints the console table before XLS export again.
The same text is then written into the .log file after XLS export completes.
```

---

## 18. 2026-05-29 default change window

Default run:

```text
php sync.php
```

Now means:

```text
Show RO rows that changed today:
- new RO_HEADER rows entered today by RO_HEADER.ENTRY_DATE >= TRUNC(SYSDATE)
- older RO_HEADER rows modified today by RO_HEADER.LAST_MODIFIED >= TRUNC(SYSDATE)
- older RO_DETAIL rows modified today by RO_DETAIL.LAST_MODIFIED >= TRUNC(SYSDATE)
```

This is the current working definition of "changes" for the future 5-minute sync.

For wider inspection, explicit days still works:

```text
php sync.php --days=2
```

That uses:

```text
RO_HEADER.LAST_MODIFIED >= SYSDATE - 2
OR RO_DETAIL.LAST_MODIFIED >= SYSDATE - 2
```

Result from `quantum_ro_preview_20260529_160951.xls`:

```text
Default "today's new or changed RO" returned 29 rows.
These are joined RO_DETAIL / WO lines, not unique RO headers.
Unique RO count: 19.
```

Important observation:

```text
One RO can produce multiple output rows because one RO can contain multiple RO_DETAIL lines and/or multiple WO numbers.
For sync into avia, the natural source row should probably be RO_DETAIL-level, not RO_HEADER-only.
```

Examples from this run:

| RO | Row count | WO |
|---|---:|---|
| R8624 | 4 | W107615, W107614, W107598, W107597 |
| R8848 | 4 | W107615, W107614, W107598, W107597 |
| R8906 | 2 | W107714, W107733 |
| R8929 | 2 | W107709, W107579 |

Quantity candidate values looked consistent:

```text
Closed/returned rows mostly show:
TO_REPAIR=n, RESERVED=0, REPAIRED=n

Open/new R8929 rows show:
TO_REPAIR=2, RESERVED=2, REPAIRED=0
TO_REPAIR=1, RESERVED=1, REPAIRED=0
```

This supports the current likely mapping:

```text
To Repair = RO_DETAIL.QTY_REPAIR
Reserved = RO_DETAIL.QTY_RESERVED
Repaired = RO_DETAIL.QTY_REPAIRED
```

---

## 19. 2026-05-29 avia storage audit for RO sync

Goal:

```text
Find where avia already stores RO, vendor, sent date, returned date, process, and qty-related data.
This is audit only; Quantum sync does not write into avia yet.
```

Confirmed project convention:

```text
date_start = sent / sent to vendor / process started outside
date_finish = returned / received back / process finished
date_promise = ECD / expected completion or promise date
```

Important migration note:

```text
database/migrations/2026_04_30_100000_machining_work_steps_date_start_drop_date_send.php
explicitly removed date_send from process tables.
Its comment says a separate date_send is not needed because "send" = tdr_processes / wo_bushing *.date_start.
```

Current Vendor Tracking UI/export reads these fields:

```text
RO        = repair_order
Vendor    = vendor_id -> vendors.name
WO        = related workorders.number
Process   = related process/process_name
Sent      = date_start
Returned  = date_finish
ECD       = date_promise
```

Tables currently used by Vendor Tracking:

| Source | Table/model | RO | Vendor | Sent | Returned | Qty |
|---|---|---|---|---|---|---|
| Part / legacy STD / Traveler | tdr_processes / TdrProcess | repair_order | vendor_id | date_start | date_finish | no direct qty column |
| Normalized STD | workorder_std_processes / WorkorderStdProcess | repair_order | vendor_id | date_start | date_finish | related items have qty |
| Bushing process | wo_bushing_processes / WoBushingProcess | repair_order | vendor_id | date_start | date_finish | qty |
| Bushing batch | wo_bushing_batches / WoBushingBatch | repair_order | vendor_id | date_start | date_finish | child processes have qty |

Actual columns confirmed in current DB:

```text
tdr_processes:
repair_order, vendor_id, date_start, date_finish, date_promise

workorder_std_processes:
workorder_id, std_type, process_name_id, repair_order, vendor_id,
date_start, date_finish, date_promise

wo_bushing_processes:
wo_bushing_line_id, process_id, batch_id, qty, repair_order, vendor_id,
date_start, date_finish, date_promise

wo_bushing_batches:
workorder_id, process_id, process_column_key, repair_order, vendor_id,
date_start, date_finish, date_promise

vendors:
id, name, is_trusted, description

workorders:
id, number
```

Most likely avia write target for Quantum RO sync:

```text
For bushing-like / quantity line data:
wo_bushing_processes is the closest existing table because it already has:
workorder link through wo_bushing_line_id -> workorder_id,
process_id,
qty,
repair_order,
vendor_id,
date_start,
date_finish.

For non-bushing STD process rows:
workorder_std_processes is the current normalized table.
```

Open design point before writing sync:

```text
Quantum RO rows arrive at RO_DETAIL level and can be multiple rows per RO/WO.
avia Vendor Tracking can display multiple source rows.
The sync should not be RO_HEADER-only.
It needs a stable line identity, likely Quantum ROD_AUTO_KEY, saved somewhere in avia.
Current avia tables do not have a quantum/source external key column yet.
```

Partial return rule from user:

```text
If Quantum returns an RO partially, there can be two RO_DETAIL rows:
- one line has LAST_DELIVERY_DATE filled
- another line still has reserved qty and no return date

In avia, mark the RO as returned with the partial return date.
Meaning for sync:
for an RO-level/vendor-tracking aggregate, date_finish should use the first/available
partial LAST_DELIVERY_DATE even if some detail quantity remains reserved.
```

Candidate implementation rule:

```text
Per Quantum detail line:
date_start = RO_HEADER.OUT_DATE
date_finish = RO_DETAIL.LAST_DELIVERY_DATE when present

Per avia RO aggregate/display row:
date_finish = MIN(RO_DETAIL.LAST_DELIVERY_DATE) for that RO where LAST_DELIVERY_DATE is not null
or possibly MAX(...) if business wants latest partial return date.
User wording says "date of partial return", so use the actual partial date from the returned line.
Need confirm MIN vs MAX if multiple partial returns happen on different days.
```

---

## 20. 2026-05-29 automatic classification question

Problem:

```text
avia has separate process/storage families:
- tdr_processes for part/detail processes
- workorder_std_processes for STD list processes
- wo_bushing_processes / wo_bushing_batches for bushings

Quantum RO feed currently gives RO, WO, vendor, dates, quantities.
Quantum does not directly know avia source groups: TDR vs STD vs Bush.
Manual sorting line-by-line is not acceptable.
```

Agreed architecture:

```text
Create a neutral buffer/staging table first.
sync.php should write Quantum RO rows into that buffer.
Then avia will resolve/sort staged rows into existing avia structures.
```

Current task before creating buffer:

```text
Inspect Quantum for part-number/name/component fields that can automate matching.
If Quantum RO_DETAIL/WO_BOM can provide part number or description, avia can map:
Quantum WO + part number/name -> avia workorder/component/bushing/std candidates.
```

Current `quantum_ro_query.php` mode:

```text
Diagnostic metadata query over ALL_TAB_COLUMNS.
Searches QCTL RO/WO/PN/PART/STOCK/BOM-like tables and columns for:
PNM_AUTO_KEY
PART
PN
DESC
ITEM
COMP
STOCK
BOM
WOB
WOO
```

Because `sync.php` has fixed output columns, metadata is temporarily mapped as:

| sync.php column | Diagnostic meaning |
|---|---|
| RO | table name |
| Vendor | column name |
| WO | data type |
| Date Sent | column id |
| Date Returned | nullable |
| Qty | candidate purpose |

Run:

```text
php sync.php
```

After reading the output, the next step is to print actual RO rows with the best part-number/name candidate fields.

Result from `quantum_ro_preview_20260529_171439.xls`:

```text
RO_DETAIL has PNM_AUTO_KEY.
WO_BOM has PNM_AUTO_KEY.
WO_OPERATION has PNM_AUTO_KEY.

PARTS_MASTER has:
PNM_AUTO_KEY
PN
DESCRIPTION
PN_STRIPPED
PN_UPPER
DESCRIPTION_UPPER
```

Best first join for RO detail classification:

```sql
RO_DETAIL.PNM_AUTO_KEY -> PARTS_MASTER.PNM_AUTO_KEY
```

User-provided Quantum business hint:

```text
If PARTS_MASTER.PN is NDT or CAD, this is an STD/list bucket:
NDT -> NDT list
CAD -> CAD list

If PARTS_MASTER.PN is a real part number, this is a detail/part candidate.
```

Current `quantum_ro_query.php` mode:

```text
Print actual RO rows again.
Join QCTL.PARTS_MASTER as pm_rd on pm_rd.PNM_AUTO_KEY = RO_DETAIL.PNM_AUTO_KEY.
Put classification and part info into the Qty column for diagnosis:
CLASS=<STD_LIST_NDT|STD_LIST_CAD|DETAIL_PART|UNKNOWN>,
PN=<PARTS_MASTER.PN>,
DESC=<PARTS_MASTER.DESCRIPTION>,
RD_PNM=<RO_DETAIL.PNM_AUTO_KEY>,
WB_PNM=<WO_BOM.PNM_AUTO_KEY>,
WO_PNM=<WO_OPERATION.PNM_AUTO_KEY>,
TO_REPAIR=<RO_DETAIL.QTY_REPAIR>,
RESERVED=<RO_DETAIL.QTY_RESERVED>,
REPAIRED=<RO_DETAIL.QTY_REPAIRED>
```

Important:

```text
The console table truncates Qty.
Use the generated XLS/log parsing for full diagnostic text.
```

Result from `quantum_ro_preview_20260529_171722.xls`:

```text
Rows: 31
Unique RO: 20

Initial exact-only classification produced:
DETAIL_PART: 19
STD_LIST_NDT: 12
```

Important correction:

```text
Treating every non-null PARTS_MASTER.PN as DETAIL_PART is too broad.
Quantum PN values like CAD Plate, CAD Removal, and Nital Etch are not avia detail part numbers.
They map better to avia process/list buckets.
```

avia cross-check:

```text
process_names includes:
NDT-1
NDT-4
NDT-6
NDT-7
STD NDT List
STD CAD List
Cad stripping
Cad plate
Nital Etch Inspection
```

Current diagnostic classification rule:

```text
PN = NDT or CAD -> STD_LIST_NDT / STD_LIST_CAD
PN starts with CAD -> STD_PROCESS_CAD
PN or DESCRIPTION contains ETCH -> STD_PROCESS_ETCH
PN contains a digit -> DETAIL_PART
PN exists but does not match above -> PROCESS_OR_LIST
PN empty -> UNKNOWN
```

Examples from the output:

| Quantum PN | Description | Better class |
|---|---|---|
| NDT | Non Destructive Test | STD_LIST_NDT |
| CAD Plate | Cadmium Plating | STD_PROCESS_CAD |
| CAD Removal | CAD Removal | STD_PROCESS_CAD |
| Nital Etch | Etch Inspection | STD_PROCESS_ETCH |
| 8871-1 | Pin | DETAIL_PART |
| 170-70496-003 | RA ATT PIN | DETAIL_PART |
| 190-70954-005 | Cylinder | DETAIL_PART |

avia component matching check:

```text
Several Quantum numeric/hyphen PN values match avia components.part_number
inside the same WO, for example:
8871-1
170-70496-003
190-70954-005
2309-2058-001
```

This supports automatic matching strategy:

```text
1. Match Quantum WO -> avia workorders.number.
2. If Quantum PN is process/list-like, map to process_names/list bucket.
3. If Quantum PN contains a digit, try exact match to avia components.part_number under that WO.
4. If multiple avia rows match same PN in one WO, keep staged row unresolved until a stronger key is found.
```

Result from `quantum_ro_preview_20260529_172019.xls` after revised classification:

```text
Rows: 31
Unique RO: 20

DETAIL_PART: 12
STD_LIST_NDT: 12
STD_PROCESS_CAD: 6
STD_PROCESS_ETCH: 1
```

This looks much cleaner than the first exact-only pass:

```text
CAD Plate / CAD Removal are no longer DETAIL_PART.
Nital Etch is no longer DETAIL_PART.
Numeric/hyphen part numbers remain DETAIL_PART.
```

Confirmed examples:

| RO | WO | Class | PN | Description |
|---|---|---|---|---|
| R8930 | W107751 | STD_LIST_NDT | NDT | Non Destructive Test |
| R8746 | W107656 | STD_PROCESS_CAD | CAD Plate | Cadmium Plating |
| R8680 | W107656 | STD_PROCESS_CAD | CAD Removal | CAD Removal |
| R8699 | W107656 | STD_PROCESS_ETCH | Nital Etch | Etch Inspection |
| R8906 | W107714 | DETAIL_PART | 8871-1 | Pin |
| R8906 | W107733 | DETAIL_PART | 170-70496-003 | Pin |

Update 2026-05-29:

```text
sync.php output now exports separate columns:
PN
DESC
CLASS

QTY remains a diagnostic text column for:
RD_PNM / WB_PNM / WO_PNM
TO_REPAIR / RESERVED / REPAIRED
```

## 13. First staging sync design

First implementation step:

```text
Quantum -> RO/sync.php -> Laravel API -> quantum_ro_* staging tables
```

Laravel API endpoint:

```text
POST /api/quantum/ro-sync
Authorization: Bearer <QUANTUM_SYNC_TOKEN>
```

Laravel environment:

```text
QUANTUM_SYNC_TOKEN=<shared secret>
```

Bridge/sync.php environment:

```text
AVIA_SYNC_API_URL=http://avia.loc/api/quantum/ro-sync
AVIA_SYNC_API_TOKEN=<same shared secret>
```

Current bridge fallback:

```text
RO/sync.php has DEFAULT_AVIA_SYNC_API_URL and DEFAULT_AVIA_SYNC_API_TOKEN.
If AVIA_SYNC_API_URL / AVIA_SYNC_API_TOKEN are not set, sync.php uses those defaults.
Oracle credentials still come only from ORACLE_USER / ORACLE_PASS / ORACLE_DSN.
```

Created staging tables:

```text
quantum_ro_sync_runs
quantum_ro_lines
```

Current rule:

```text
Stage Quantum rows only. Do not write into avia TDR / STD / Bush tables yet.
Use first non-null returned_date per RO later for partial-return logic.
```

## 14. Incremental update mode

Update 2026-05-29:

```text
php sync.php
```

is now the normal cron/update mode.

Flow:

```text
1. sync.php calls GET /api/quantum/ro-sync/state.
2. Laravel returns max quantum_ro_lines.source_last_modified.
3. Laravel also returns recommended_since = max(source_last_modified) - 10 minutes.
4. sync.php queries Quantum only where source_last_modified >= recommended_since.
5. API upsert keeps duplicate overlap rows as unchanged and updates changed rows.
```

State endpoint:

```text
GET /api/quantum/ro-sync/state
Authorization: Bearer <QUANTUM_SYNC_TOKEN>
```

Manual backfill is still available:

```text
php sync.php --days=90
```

Manual since/debug mode:

```text
php sync.php --since="2026-05-29 14:01:00"
```

If the state endpoint has no checkpoint yet, normal sync falls back to the initial
90-day window.

## 15. Cron output mode

Update 2026-05-29:

```text
sync.php no longer prints the preview table to console and no longer creates XLS files.
```

Current behavior:

```text
1. Send rows to Laravel API.
2. Append one compact line to RO/quantum_ro_sync.log.
3. Successful cron runs are silent in console/stdout.
```

Reason:

```text
The script is now intended for Windows Task Scheduler every 5 minutes.
Run history and row counts are stored in Laravel tables:
quantum_ro_sync_runs
quantum_ro_lines
```

Log line contains:

```text
started / finished
status
filter mode / since checkpoint
rows read from Quantum
API run_id
received / inserted / updated / unchanged
result="nothing new" or result="rows transferred"
```

## 16. Classification correction before applying to avia

Update 2026-05-29:

```text
Only PN exactly NDT or CAD is a STD list candidate.
```

Current class rules in quantum_ro_query.php:

```text
PN = NDT -> STD_LIST_NDT
PN = CAD -> STD_LIST_CAD
PN with digits -> DETAIL_PART
other PN values -> DETAIL_PROCESS
empty PN -> UNKNOWN
```

Important correction:

```text
Nital Etch Inspection is not a STD database/list.
It is a process for a concrete detail/WO and must be matched later through
the detail/process side, not through workorder_std_processes as a standard list.
```

Vendor comparison file:

```text
RO/quantum_vendors_for_mapping.csv
```

Update after importing C:/Download/vendors.sql:

```text
RO/quantum_vendors_after_import.csv
```

Exact Quantum vendor name matches after import:

```text
18 matched
4 unmatched
```
