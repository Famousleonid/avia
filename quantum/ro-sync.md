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
Do not delete staged rows.
```

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
PN with digits -> DETAIL_PART
Other PN values -> DETAIL_PROCESS
Empty PN -> UNKNOWN
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
