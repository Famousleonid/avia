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

Project convention:

```text
date_start = sent / process started outside
date_finish = returned / process finished
date_promise = ECD / expected completion or promise date
```

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

