# Quantum RO Sync

## Purpose

Local bridge reads Quantum RO data and sends staged rows to avia API.
Quantum remains read-only.

## Bridge Architecture

```text
Quantum Oracle
  -> RO/sync.php
  -> Laravel API
  -> quantum_ro_sync_runs / quantum_ro_lines
  -> later parser/apply into avia tables
```

Local bridge files:

```text
RO/sync.php
RO/quantum_ro_query.php
RO/run_sync_quantum.ps1
RO/run_sync_quantum_hidden.vbs
RO/install_sync_quantum_task.ps1
RO/quantum_ro_sync.log
```

Laravel endpoints:

```text
POST /api/quantum/ro-sync
GET  /api/quantum/ro-sync/state
Authorization: Bearer <QUANTUM_SYNC_TOKEN>
```

Environment:

```text
QUANTUM_SYNC_TOKEN=<shared secret>      # Laravel side
AVIA_SYNC_API_URL=<api url>             # Bridge side
AVIA_SYNC_API_TOKEN=<same shared secret>
ORACLE_USER
ORACLE_PASS
ORACLE_DSN
```

## Incremental Logic

Normal sync:

```text
php sync.php
```

Flow:

```text
1. Bridge calls GET /api/quantum/ro-sync/state.
2. Laravel returns max quantum_ro_lines.source_last_modified.
3. Laravel recommends since = max(source_last_modified) - 10 minutes.
4. Bridge queries Quantum with overlap.
5. API upsert treats duplicate overlap rows as unchanged.
```

Manual modes:

```text
php sync.php --days=90
php sync.php --since="2026-05-29 14:01:00"
```

## Staging Rules

```text
Stage Quantum rows only.
Do not write directly into avia TDR / STD / Bush tables from bridge.
Keep raw_payload for audit.
Use source_hash to detect actual changes.
Keep apply_status / apply_message / applied target fields for parser.
Keep `applied_targets` value snapshots so a later REF remap can release the
previous target without overwriting manual edits.
Do not delete staged rows.
```

### REF remap safety

One Quantum source row is identified by its stable `source_uid` (normally
`rod:<ROD_AUTO_KEY>`). If a changed payload makes that same source row resolve
to another AVIA process, the parser must treat it as a target move:

```text
validate the saved old-target snapshot
-> clear only the unchanged Quantum-managed values on old target(s)
-> fill the new target(s)
-> save the new target list and value snapshots
```

The release and new apply run in one database transaction. If an old target no
longer matches its saved snapshot, the parser preserves both the row and the
last successful target metadata, marks the source row `unresolved`, and
requires manual review. This prevents a later REF correction from erasing
human edits.

Legacy `applied` rows without `applied_targets` are revisited by the scheduled
parser. When their legacy `applied_target_table` / `applied_target_id` still
match the current resolved target, the parser records a baseline snapshot. If
the target already differs and no safety snapshot exists, automatic cleanup is
blocked.

Do not deduplicate or release by `RO_NUMBER` alone. One RO can legitimately
contain several RO_DETAIL source rows mapped to different processes.

### Meaning of `applied`

`apply_status = applied` is an audit result for the moment when the staged
Quantum row was processed. It does not guarantee that the target row still
contains the applied values later. A subsequent manual or bulk update of the
target can replace or clear `repair_order`, vendor, or dates while the staging
row remains `applied`.

The parser normally will not revisit such a row while
`applied_source_hash = source_hash`. Therefore an unchanged Quantum source row
does not automatically repair later target-side drift.

Confirmed example (production snapshot, WO 107789 / PN 2821-0222 / RO R9101 /
REF T1): Quantum successfully applied RO, vendor, and sent date to all 10 rows
of Traveler 1 at 15/Jul/2026 18:55. The same 10 `tdr_processes` rows were bulk
updated later at 22:03 and currently have null RO and sent date, while the
staging row remains `applied`. Bulk query updates can bypass the model activity
log, so target-side audit must not rely on `quantum_ro_lines.apply_status`
alone.

Traveler create/recreate and ungroup operations now write explicit
`tdr_traveler` activity events (`traveler_created` and `traveler_ungrouped`)
with the authenticated user, WO/TDR/PN, affected process IDs, previous values,
and cleared fields. This explicit audit is required because those operations
still use efficient bulk target updates.

To recover a confirmed target-side drift without changing Quantum, requeue only
the exact staging row by setting `apply_status = pending`, clearing
`applied_source_hash`, `applied_at`, and the applied target fields, and then run
`php artisan quantum-ro:apply`. Verify the natural identifiers (`source_uid`,
RO, WO, PN, serial number, and REF) before requeueing. Do not change `source_hash` or the source
payload: those belong to the read-only Quantum import.

The Vendor Tracking recent Quantum table exposes this operation as `Reapply`
for rows with status `applied`. It requires confirmation, requeues only the
selected row, and records the requesting user in `activity_log`. The normal
server-side `quantum-ro:apply` schedule then applies the staged source values.

Natural source row:

```text
RO_DETAIL-level row, not RO_HEADER-only.
Likely stable key: ROD_AUTO_KEY.
```

## 2026-08-10 production parser incident

Live production evidence for RO `R9192`, WO `W107880`, PN `NDT`:

```text
Quantum staging returned_date = 2026-08-06
Quantum target = workorder_std_processes:632
Target date_start = 2026-08-04
Target date_finish = null
```

The target activity log shows the last system/Quantum write at
`04/Aug/2026 14:31`; it wrote the sent date, RO, and vendor while the return
date was still empty. Two user activities at `06/Aug/2026 13:20` changed only
`ignore_row` and did not clear `date_finish`. No Quantum-managed
`WorkorderStdProcess` or `TdrProcess` target writes were recorded from
`06/Aug/2026` through `10/Aug/2026`, although the bridge continued receiving
successful runs and the staged row acquired the return date.

Operational conclusion: the bridge/API staging sync was healthy, but the
separate hosted `php artisan quantum-ro:apply --quiet` parser was not applying
source changes. The migrated five-minute crontab entry was present with the
correct PHP and application paths, but that one line ended in Windows `CRLF`
bytes (`0d 0a`) while the working Laravel `schedule:run` line ended in Unix
`LF` (`0a`). The hidden carriage return corrupted the final `2>&1` shell
redirection, so the Quantum command never started. The internal apply log's
last entry remained the manual migration run at `04/Aug/2026 14:31`, and the
cron output file created at `14:35` remained empty. Normalize only the Quantum
crontab line to Unix LF and verify a new apply-log entry after the next
five-minute boundary.

Resolved on `10/Aug/2026`: the production crontab was backed up, exactly one
`CRLF` sequence was normalized to `LF`, and the five-line crontab was
reinstalled unchanged otherwise. The scheduled runs at `10:00:02` and
`10:05:02` both completed with `status=ok` and `errors=0`. The first recovered
run scanned 406 staged rows and applied 91. For `R9192` / `W107880`, staging
and target hashes matched afterward and `workorder_std_processes:632` had
`date_finish = 2026-08-06` with `date_finish_user = Quantum`.

The Vendor Tracking status can still display the previous `applied` result
because staging updates do not reset `apply_status`; the parser detects the
change through `applied_source_hash <> source_hash` only when it actually runs.

Infrastructure note from the same diagnosis: `aviatechnik.ca` no longer
resolved to the documented diagnostic SSH host `51.222.203.80`; that host's
database stopped receiving Quantum rows on `03/Aug/2026` and must not be used
to diagnose current live sync state.

## Date Mapping

Confirmed:

```text
date_start  = RO_HEADER.OUT_DATE
date_finish = RO_DETAIL.LAST_DELIVERY_DATE
```

Apply rule (confirmed):

```text
Use returned_date only from the exact staged RO_DETAIL row being matched
(ROD_AUTO_KEY / WO / PN / serial / REF). Never aggregate or copy a return
date from another P/N merely because RO_NUMBER is the same.
```

Project convention:

```text
date_start = sent / process started outside
date_finish = returned / process finished
date_promise = ECD / expected completion or promise date
```

Mobile process-date ownership, confirmed by the production review audit:

```text
ProcessName::allowsManualDateEditing() = true  -> shop/Technician-entered; mobile may edit dates.
All other TDR process names                     -> Quantum-owned; mobile must return all can_edit_* flags as false.
```

This rule intentionally does not depend on whether Quantum has already sent a
date or populated `date_*_user`: blank dates are normal before the external
process is completed.

Partial return rule from user:

```text
If Quantum returns an RO partially, mark the RO as returned with the partial
return date from the returned detail line.
```

Open detail:

```text
If multiple partial returns happen on different dates, confirm whether avia
should use MIN(returned date) or MAX(returned date) for aggregate display.
```

## Quantity Mapping

Confirmed candidate mapping:

```text
To Repair = RO_DETAIL.QTY_REPAIR
Reserved  = RO_DETAIL.QTY_RESERVED
Repaired  = RO_DETAIL.QTY_REPAIRED
```

Observed values support this:

```text
Closed/returned rows: TO_REPAIR=n, RESERVED=0, REPAIRED=n
Open/new rows:        TO_REPAIR=n, RESERVED=n, REPAIRED=0
```

## Serial Mapping

Confirmed for detail-part RO rows:

```text
serial_number = RO_DETAIL.SERIAL_NUMBER when populated
serial_number = the single distinct VIEW_RPT_KIT_MATERIAL.SERIAL_NUMBER
                linked by WOB_AUTO_KEY otherwise
```

If the linked WOB exposes more than one distinct serial, sync leaves
`serial_number` empty instead of choosing an arbitrary unit.

Confirmed production example for WO W106874 / PN D63820:

```text
WOB 714543 -> A529
WOB 714545 -> A528
WOB 714551 -> A529
WOB 714552 -> A528
```

The apply service uses this serial together with WO, PN, and REF/process code
to select the correct TDR when several TDR units share the same part number.
It tries the exact serial first. If Quantum's serial does not match but WO + PN
+ REF identifies exactly one TDR process (or exactly one Traveler group), that
single target is used as a safe fallback. If more than one target remains, the
row stays unresolved; the service must not guess between units.

## Classification Mapping

Best first join:

```text
RO_DETAIL.PNM_AUTO_KEY -> PARTS_MASTER.PNM_AUTO_KEY
```

Current classification:

```text
PN = NDT -> STD_LIST_NDT
PN = CAD -> STD_LIST_CAD
PN = NDTB -> BUSHING_NDTB (bushing process key: ndt)
PN = Machining -> BUSHING_MACHINING (bushing process key: machining)
PN = CADB or CAD Plate B -> bushing process key: cad
PN = Anodizing/Anodising -> bushing process key: anodizing
PN = Passivation -> bushing process key: passivation
PN with digits -> DETAIL_PART
Other PN values -> unsupported
Empty PN -> UNKNOWN
```

Confirmed bushing batch rule:

```text
For bushing P/N values, REF must be B1, B2, ... .
The number selects the corresponding batch within that bushing process key.
Example: PN = Machining and REF = B1 targets the first machining bushing batch.
```

Examples:

| Quantum PN | Description | Class |
|---|---|---|
| `NDT` | Non Destructive Test | `STD_LIST_NDT` |
| `CAD Plate` | Cadmium Plating | `DETAIL_PROCESS` |
| `CAD Removal` | CAD Removal | `DETAIL_PROCESS` |
| `Nital Etch` | Etch Inspection | `DETAIL_PROCESS` |
| `170-70496-003` | Pin | `DETAIL_PART` |

Important correction:

```text
Only PN exactly NDT or CAD is a STD list candidate.
Nital Etch Inspection is not a STD database/list.
```

## Avia Storage Audit

### 2026-08-24 TDR P/N mismatch example: R9273 / W107849

Confirmed against current production staging and TDR data:

```text
Quantum source_uid = rod:33371
RO = R9273
WO = W107849
REF = RSV -> process_names EHSV Repair
Quantum PN = 53014-103
Quantum SN = 1386

Matching WO TDR process = EHSV Repair, tdr_processes.id 4279
TDR component PN = 53014-103 (32-51-04)
TDR SN = NSN
```

The process exists, but the exact-PN target query does not match because the
current normalization removes spaces only:

```text
53014-103 != 53014-103(32-51-04)
```

Diagnostic rule:

```text
If the WO + REF/process exists on one or more TDR rows but their component P/N
does not equal the Quantum P/N, keep apply_status = unresolved and report
TDR P/N mismatch with both Quantum and TDR P/N values.

Use No TDR process target only when no TDR row exists for that WO + process.
```

The RO shown in the Vendor Tracking Quantum table is the source
`quantum_ro_lines.ro_number`; it does not mean `tdr_processes.repair_order`
was populated. For this incident the target repair_order remained null.

### 2026-08-14 stale deleted-detail example: R9238 / W107873

Confirmed against both current production staging and live Quantum Oracle:

```text
Production quantum_ro_lines source_uid = rod:33264
RO = R9238
WO = W107873
ROD_AUTO_KEY = 33264
WOB_AUTO_KEY = 720688
WOO_AUTO_KEY = 8183
PN = 55325-101
SN = SPP011099
REF at import time = T1
first_seen_at = 13/Aug/2026 08:48:09
last_seen_at  = 13/Aug/2026 08:53:09
apply_status = unresolved
applied target = none
```

The current Oracle state no longer contains `RO_DETAIL.ROD_AUTO_KEY = 33264`.
Current `R9238` contains only `ROD_AUTO_KEY = 33263`, linked to `W107784`.
`WO_BOM.WOB_AUTO_KEY = 720688` still belongs to `W107873` and PN
`55325-101`, but its current `REF` is null.

Therefore the bridge did not invent the WO: while ROD 33264 existed, the
confirmed join `RO_DETAIL.WOB_AUTO_KEY -> WO_BOM.WOO_AUTO_KEY ->
WO_OPERATION.SI_NUMBER` produced `W107873`. The Quantum detail was later
removed/unlinked and the BOM REF was cleared. Because staging is upsert-only
and the bridge sends only rows returned by the current SELECT, disappearance
of a Quantum row does not delete or dismiss its existing `quantum_ro_lines`
record. Vendor Tracking can consequently show a stale unresolved row after
the source detail has been deleted.

Important display/audit rule:

```text
last_seen_at is the last time the exact source row was returned by Quantum.
An old last_seen_at combined with a current Oracle miss means the staging row
is historical/stale, not a current RO-to-WO relationship.
```

The current production persistence audit contains no write from this stale
line into a W107873 STD row. The exact timeline is:

```text
13/Aug/2026 07:10:02  R9238 -> tdr_processes 2551..2559, W107784,
                      PN 49203-107, nine Traveler 1 rows
13/Aug/2026 08:48:09  rod:33264 first staged as R9238 / W107873 /
                      PN 55325-101 / REF T1
13/Aug/2026 08:48-08:53 rod:33264 remained unresolved because W107873 had
                        no matching Traveler 1 target
13/Aug/2026 12:35:01  R9241 -> workorder_std_processes 604, W107873 NDT;
                      activity old value was repair_order = null
```

`activity_log` has no `WorkorderStdProcess` event containing `R9238`; every
persisted `R9238` event is one of the nine W107784 `TdrProcess` updates above.
The W107873 STD row is activity-logged and its R9241 update explicitly records
the previous RO as null. Therefore an observed R9238 display in the W107873 STD
cell would require a separate unlogged/display path; it is not the persisted
result of `rod:33264` in the production database audit.

Vendor Tracking presents the staging buffer in two sections:

```text
Needs attention              = unresolved rows that require action
Latest received from Quantum = local audit view of staged rows in all apply statuses
```

Both sections exclude rows whose `wo_number` belongs to a local workorder with
`workorders.done_at IS NOT NULL`. WO filtering is an exact normalized match on
`quantum_ro_lines.wo_number`; input with or without the `W` prefix is equivalent
(for example, `107789` and `W107789`). RO filtering is applied to `ro_number`.

Existing avia date fields:

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
```

Need a Quantum/source external key before applying reliably into avia tables.
