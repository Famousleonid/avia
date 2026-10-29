# Quantum RO Sync

## Purpose

Local bridge reads Quantum RO data and sends staged rows to avia API.
Quantum remains read-only.

## Main workorder RO tab (28/Sep/2026)

The Main page has a read-only RO tab backed by `quantum_ro_lines`, loaded via
`GET /mains/{workorder}/quantum-ro-lines` on tab activation. It shows
all buffered detail rows for that exact WO (trimmed, case-insensitive, with or
without W), regardless of apply status. No recent-date limit or completed-WO
exclusion is used. OUT_DATE and returned_date stay on their own source line;
the page does not aggregate return dates across a shared RO number.

Performance audit (27/Oct/2026; no optimization deployed): the endpoint filters
with `UPPER(TRIM(wo_number))`. Production `EXPLAIN` returned `type=ALL`,
`key=null`, approximately 4,382 rows and `Using where; Using filesort`, despite
the existing `wo_number` index. A future indexed lookup must preserve trimmed,
case-insensitive identity and optional W; do not simply remove normalization.
The current client reloads RO on each activation (only concurrent loads are
deduplicated). This is a secondary cost after the initial Main HTML and UI-state
restore, not an Oracle request during page opening.

Only Admin and Manager roles can see this tab or call its data endpoint; other
authenticated roles receive HTTP 403. A saved RO tab selection is ignored when
the user's current role no longer permits it.

Each staged row retains `last_seen_at`. This is the row's
last receipt by the sync, not a claim that every row is still present in Oracle.
Historical source rows remain visible in the synchronized snapshot.
Local validation for W107731 found 12 distinct RO / 15
buffered lines, including two R8990 CAD Plate B records with different receipt
times and return values. No Oracle-currentness inference is made from these
local records.

The tab now renders only an SP-form-style matrix: description, P/N and S/N in
column headers and process/REF rows. Cells show clickable RO numbers;
vendor and sent/returned dates appear in a small click popover.
Column identity is the normalized P/N + serial pair, with the verified applied-target exception for missing source serials described below. Each source detail remains
an independent entry, even when several details occupy the same cell. The
receipt time stays in the staging data and is not shown in the compact UI. Process
labels use unique `process_names.code` matches and SP form ordering; ambiguous
or unknown references remain explicit REF rows. T/T1/T2 references appear as
Traveler rows, without guessing process membership from current local targets.
No local process values are substituted for the synchronized Quantum dates.
RO status colors (27/Oct/2026): each source line with returned_date has a green RO-number background; a line without returned_date has a yellow/gold RO number. Apply status and other lines sharing the RO number do not determine this color. Click details and per-line dates remain unchanged.
Planned steps (27/Oct/2026): supplement the snapshot with visible Parts / Processes from the current WO and its used manuals. Exact normalized P/N + S/N identifies the column; no fuzzy P/N or P/N-only serial fallback. A missing source serial may use its verified applied TDR target as described below. Ignored/hidden/INSPECT rows are excluded as on Main. Each Traveler group is one step. Gray ordinals (1, 2, 3...) follow each detail's sort_order/id sequence and are shown for steps without Sent or assigned RO, including details with no Quantum lines yet. They are not fabricated RO numbers and do not alter source dates. The Description, P/N and S/N headers are green only when all known source RO lines and all visible planned processes in that column have Returned; any unreturned source line or unfinished planned step keeps it normal. Unique process-code matches share rows with Quantum; ambiguous/missing codes use separate named local process rows.

Internal operations (28/Oct/2026): the matrix also shows local AT entries, with each TdrProcess date_start/date_finish in its click popover. Explicit repair_order=AT is retained alongside source RO history. A dated operation with blank RO and no vendor is shown as AT only when that exact P/N + S/N and process row has no Quantum source RO. Unstarted blank-RO rows retain gray ordinals; external RO/vendor rows are not relabeled AT. Traveler members keep their individual dates. AT completion coloring follows its own date_finish; header completion rules are unchanged. Production read-only W107775: REPAIR SLEEVE 300-301-A01 / NSN, TDR1497, Machining (AT)2443 has RO=AT, Sent28/Jul/2026, Returned01/Aug/2026; it was previously omitted by the source-only/unsent-only rendering. Local data at validation had the same Sent but empty Returned, correctly shown yellow, so it must not be mistaken for the production date state.



Applied-target column identity (28/Oct/2026): a source line with apply_status=applied and empty serial may use the part identity of its saved tdr_processes target. Resolve targets only among the current WO/used-manual TDRs passed to the builder, require exact normalized P/N, and require every TDR target to exist and resolve to a single P/N + S/N identity. applied_targets takes precedence, with singular applied_target_table/id for legacy rows. Unknown, ambiguous, non-applied and conflicting nonempty serials retain their original source columns. This routes source RO cells and their completion states to the same column as local AT/planned operations without replacing source dates or inventing RO numbers. Production W107775 verified: source3528 R9235/N4 targets2444; source3551 R9239/CP targets2445. Both source serials are null; both target processes belong to TDR1497 / 300-301-A01 / NSN, whose third process2443 is AT. Previous strict raw serial grouping split two external ROs from AT. Regression verifies a single column with all three, source dates retained and incomplete AT preventing green header.


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

## 2026-09-08 W107775 R9181 / R9233 production diagnosis

`R9181` is not a Quantum data problem. Production staging contains three
separate applied RO_DETAIL rows with safety snapshots. Their targets contain
the expected sent and returned dates. On manually editable process rows such
as Machining, the Flatpickr alternate input has the `has-finish` class, but the
dark-theme `.form-control` rule has higher CSS specificity and hides the green
background. Read-only rows such as Chrome stripping render as spans and remain
green.

`R9233` has one Quantum source row: `rod:33246`, WO `W107775`, PN `1840-0024`,
REF `T1`, sent `12/Aug/2026`, no returned date. The current unresolved message
points to the legacy target `tdr_processes:2418` and says that it has no safety
snapshot.

The production activity trail confirms this sequence:

1. On `12/Aug/2026 08:30:01`, Quantum wrote `R9233`, vendor 4, and the sent
   date to 11 process rows (`2418`, `2740`-`2749`). At that moment all 11 rows
   were members of Traveler 1; the legacy leader/target pointer was row `2418`.
2. On `03/Sep/2026 10:48:40`, Yosypiv Dmytro ungrouped that Traveler. The
   operation cleared the sent date and its user field from every member, but
   intentionally preserved the RO and vendor. This produced the visible blank
   dates with `R9233` repeated on all 11 process rows.
3. On `04/Sep/2026 07:37:59`, Voronin Oleg recreated Traveler 1 from only the
   seven rows `2741`-`2747`. Machining `2418`, Stress Relief `2740`, NDT-6
   `2748`, and Paint `2749` stayed outside the new Traveler but retained the
   old RO/vendor values.

The resolver now maps REF `T1` to the recreated seven-row Traveler, while the
legacy staging record still points to the old leader `2418`. Because the old
apply predates target snapshots, automatic release is blocked and the row is
kept `unresolved` for manual review. Do not infer from the repeated RO that
Quantum supplied several source rows: this incident has only one source row,
which was expanded across the members of the former Traveler group.

The available audit log records who ungrouped and recreated the Traveler. It
does not identify who originally placed all 11 processes into Traveler 1.

### R9127 / W107736 / PN 1840-8402

This unresolved row has a different cause from R9233. Quantum source
`rod:32919` correctly identifies REF `N4`, sent `20/Jul/2026`, returned
`22/Jul/2026`. It was applied to NDT-4 process `tdr_processes:1892` on
`20/Jul/2026`, and the returned date was added on `22/Jul/2026 11:16`.

At `22/Jul/2026 13:12:29`, Medvid Vasyl deleted process row `1892`. The audit
snapshot confirms that the deleted row still contained `R9127`, vendor 6, and
both Quantum dates. On `05/Sep/2026 08:10:14`, Voronin Oleg created NDT-4 again
for the same TDR, but this is a new empty row with ID `4742`.

The resolver therefore correctly resolves REF `N4` to current row `4742`,
while the legacy staging record still points to deleted row `1892`. The old
apply predates `applied_targets` safety snapshots, so the parser cannot prove
that it is safe to move/reapply the values and leaves the source row
`unresolved`. This is a delete-and-recreate identity change, not a Quantum REF
or date error.

## Date Mapping

### 07/Oct/2026 R9374 / W107967: Traveler rebuilt without Silver plating

Read-only production and direct Oracle verification confirms rod:33668,
WOB 725033, PN 190-70954-005, REF T1, OUT_DATE 17/Sep/2026, no return.
Production staging 4060 retains the same source/applied hash and seven saved
target snapshots; current status is unresolved on tdr_processes:4697 date_start.

- Activity 40436: Fouxman Alexander created Traveler 1 on 16/Sep/2026 15:23:43
  for TDR 2318, including Silver plating 4697.
- Activities 40586–40592: Quantum applied R9374 and sent date 17/Sep/2026
  to all seven members on 17/Sep/2026 11:45:01.
- Activity 40697: Yushkevich Viacheslav ungrouped it on 17/Sep/2026 15:32:09;
  this cleared date_start/date_start_user_id while retaining RO/vendor.
- Activity 40698: the same user recreated Traveler 1 at 15:33:01, replacing
  Silver plating 4697 with Stress Relief 5042. Creation cleared repair_order
  on selected members; excluded Silver plating retained R9374.

Current Traveler members are 5042, 4691–4695, 4723 with empty RO/sent date.
Silver plating 4697 is outside the Traveler with R9374 and null date_start,
which differs from its saved Quantum snapshot. The deployed remap guard
therefore blocks release/reapply. The message "Quantum target changed" here
means local Traveler membership changed, not that Quantum REF changed.
No production changes were made. Diagnostic scripts:
`storage/app/codex/audit-107967.php`, `quantum/log/audit-107967-oracle.php`.

Local implementation added on 07/Oct/2026 (not deployed by Codex): Admin-only
`Rebind` in Needs attention previews release/current Traveler values, then
uses the shared confirmDialog before an atomic release/apply. Only unresolved
detail-part Traveler rows with complete prior snapshots qualify. Old targets
must belong to the same TDR, be outside every current Traveler, and contain
only saved values or cleared fields; new targets cannot contain another RO
or vendor. Missing targets and changed nonempty old values require separate
review. The server verifies a signed preview fingerprint and records
`quantum_ro_line / traveler_rebound` with actor and before/after values.
Ordinary Reapply/Restore retains its existing safety behavior.

### 07/Oct/2026 in-app AI diagnosis (local implementation)

`diagnoseQuantumRo` is a read-only System Admin tool, additionally requiring
Vendor Tracking and applicable workorder view access. It accepts exact WO/RO,
P/N, S/N and source_uid filters. Separate source details sharing an RO remain
separate reports. It uses `QuantumRoBufferApplyService::inspectMapping`, not
a second copy of the routing rules, and provides:

- Bridge receipt status/timestamps and staged REF/part/date/quantity values.
- Actual current resolver result, WO part/Traveler candidates, previous saved
  targets, current values, added/removed targets and field-level differences.
- Explicit automatic-release blockers. Changed membership alone is not a
  blocker; missing safety snapshots or changed old-target values can be.
- Paginated complete matching process, Traveler and source-line audit history,
  including deleted prior target IDs, actors, cleared fields and before values.
- Rebind eligibility and planned effects using the same validation as the UI,
  without a write token, mutations, or row locks. The administrator must open
  a fresh UI preview and confirm the action; the agent cannot execute it.

Agent instructions require cause → dated evidence → exact blocking difference
→ correction effect. They distinguish AVIA received data from live Oracle,
bridge success from parser application, and `applied` from intact current
values. Missing history/actors remain unknown. Source and audit pagination
must be completed or the unexamined portion explicitly reported.
`markLine` updates applied_at/applied_source_hash on unresolved attempts too;
these fields must not be described as the last successful application.
Target snapshots contain no historical REF, so the agent must not describe
the current T1 as a newly changed REF without separate historical evidence.

Validation: local W107967/R9374 data yields Silver plating removed and Stress
Relief added, the cleared sent-date conflict and the recorded ungroup/recreate
events. Local staging status differs from the previously checked production
status; the tool reports that difference instead of silently changing it.
Dedicated tests cover read-only behavior, permissions, same-RO isolation,
missing/deleted targets, applied drift, pagination and agent dispatch. A live
model check used synthetic WO/RO/actors only; actual business records were not
sent to the external model in that check. No production deployment performed.

### 2026-09-11 W107855: RO date versus physical dispatch

Production staging last received both lines at 11/Sep/2026 07:23 Toronto:
R9327 / rod:33536 / REF CP and R9331 / rod:33546 / REF N1, both PN
52141-1, SN ACR111089. Both payloads contain OUT_DATE 08/Sep/2026,
returned_date null, QTY_REPAIR=1, QTY_RESERVED=1, QTY_REPAIRED=0.
Their source/applied hashes match; target rows 3374 and 3378 contain those dates.
This is not a stalled parser or a wrong-PN assignment.

The user reports that CAD has physically returned and NDT has not been sent.
Current mapping treats RO_HEADER.OUT_DATE as dispatch without an independent
shipment event or previous-process-return validation. The latest staged payload
therefore does not represent the reported physical movement. A RO header date
must not be treated as proven physical shipment in such cases.

Direct Oracle verification succeeded later in the same diagnosis after explicitly
setting TNS_ADMIN=C:\oracle\network\admin. Exact RO_DETAIL rows 33536 and 33546
still have LAST_DELIVERY_DATE=null, SHIP_DATE=null, QTY_REPAIRED=0,
QTY_RESERVED=1 and QTY_REPAIR=1. Both headers are open (OPEN_FLAG=T), with
ENTRY_DATE=OUT_DATE=08/Sep/2026. The line last-modified timestamps remain
08/Sep/2026 11:06:20 and 15:39:53 respectively. Thus the actual Quantum RO
fields used by the integration agree with staging, not with the reported physical
CAD receipt / pending NDT dispatch. SHIP_DATE is also null on other lines of both
ROs and is not yet validated as a replacement dispatch source. Whether a separate
receipt/shipment record contains the physical event remains unverified. Do not
invent a return date or clear targets during diagnosis.

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
PN = CAD or CAD Plate -> STD_LIST_CAD
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
For automatic horizontal routes (local implementation verified 18/Sep/2026),
the number selects wo_bushing_batches.route_number within that process key.
The same route number spans all operations; missing operation numbers are valid.
Example: Machining B2 targets route_number=2 even if it is the first machining row.
Legacy batches retain their captured legacy_number; workorders without route metadata
retain the original per-process positional lookup. Existing RO memberships are protected.
See docs/bushing-route-batches.md for migration and verification details.
```

Examples:

| Quantum PN | Description | Class |
|---|---|---|
| `NDT` | Non Destructive Test | `STD_LIST_NDT` |
| `CAD Plate` | Cadmium Plating | `STD_LIST_CAD` |
| `CAD Removal` | CAD Removal | `DETAIL_PROCESS` |
| `Nital Etch` | Etch Inspection | `DETAIL_PROCESS` |
| `170-70496-003` | Pin | `DETAIL_PART` |

Important correction:

```text
PN normalized as NDT, CAD, or CADPLATE is a STD list candidate.
CADPLATE routing was verified in deployed production code on 28/Sep/2026.
Nital Etch Inspection is not a STD database/list.
```

## Avia Storage Audit

### 2026-09-28 W107830 empty detail processes

Read-only checks of live Oracle, production staging/targets/activity and
deployed routing code confirm WO 306 / W107830, TDR 1806, PN 2225-0014,
SN HM00868:

- Machining (AT) 3103 and Stress Relief 3104 remain empty. Quantum BOM
  715771 (Machining) and 715774 (Stress Relief) have no linked RO_DETAIL,
  no REF and ITEM_COMPLETE=F. These BOM-only rows do not enter RO staging.
- NDT-1 3105 remains empty because R9286 / rod:33412 / WOB 721829
  supplies PN=NDT, REF=N1, not the detail PN. Staging 3739 is applied
  to STD NDT 524, which contains R9286, sent 26/Aug/2026 and returned
  28/Aug/2026. That STD row has ignore_row=1. The deployed resolver does
  not exclude ignored STD targets or redirect them into detail processes.
- NDT-4 3107 has no corresponding N4 RO in the current WO Oracle rows
  or production staging. A separate generic NDT BOM 715783 has no RO
  or REF and cannot be identified as an N4 operation.
- Chrome plating 3106 (R9298, 31/Aug/2026–11/Sep/2026), NDT-6 3108
  (R9356, 11/Sep/2026–16/Sep/2026), Passivation 3109 (R9379,
  sent 18/Sep/2026, return null) agree with Oracle and applied staging.
- All four staged RO source/applied hashes match; last_seen_at at check
  was 28/Sep/2026 10:33:10. Empty detail process updated_at values still
  equal their creation on 17/Jul/2026; available target activity contains
  no later updates to those rows. No evidence of RO/date clearing found.

Do not copy R9286 into NDT-4 or infer a completed shop operation from a
generic BOM entry. Scripts: `quantum/log/audit-107830-oracle.php` and
`storage/app/codex/audit-107830.php`. No production changes.

### 2026-09-28 W107854 Brace CAD routed to STD list

Confirmed against live Oracle, current production DB and deployed
`QuantumRoBufferApplyService::routeForLine` (read-only diagnosis):

- WO 339 / W107854, TDR 1847: BRACE, LOWER, PN 170-70621-001,
  IPL 1-452, SN 1574924/003. Cad plate process 3433 exists, but RO and
  dates remain null; updated_at is still 23/Jul/2026.
- R9281 / rod:33403 / WOB 721798 has RO detail PN **CAD Plate**,
  BOM PN **CAD Plate**, REF CP, and no detail serial. Oracle dates:
  sent 25/Aug/2026, returned 01/Sep/2026. The exported relationship
  does not identify the Brace PN. Do not infer a physical Brace association
  merely from quantity 1 or the matching WO.
- Production staging 3728 received the row on 25/Aug/2026 15:03:09;
  last_seen_at at diagnosis was 28/Sep/2026 10:13:09. Applied/source
  hashes match. The deployed classifier routes CAD/CADPLATE to STD CAD
  before detail-part matching, irrespective of REF CP.
- Target workorder_std_processes 581 currently contains R9281 and both
  dates. Audit first wrote R9281 there on 25/Aug/2026 15:05:02,
  replacing R9186; return date was written 01/Sep/2026 14:50:02.
  On 04/Sep/2026 the older R9186 was reapplied at 17:20:02, then R9281
  restored at 17:25:01. Both sources map to the same STD row.
- By contrast, Brace NDT R9274 / rod:33378 supplies PN 170-70621-001
  and REF N1, and correctly populates its TDR process 3432.

Cause: generic service-PN routing, not a missing import or a deleted Brace
RO. Assigning this CAD RO specifically to Brace requires an explicit source
part association or reviewed mapping; do not copy the STD RO to arbitrary
Cad plate rows. No production data or application code changed.
Diagnostic SELECT scripts: `quantum/log/audit-107854-oracle.php` and
`storage/app/codex/audit-107854.php`.

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

### 2026-09-09 R9326 / W107679 mixed applied and stale detail

One RO header can contain both a successfully applied row and a later-removed
detail for the same WO. Confirmed production/Oracle chain:

```text
Current R9326 / W107679 row:
  ROD_AUTO_KEY = 33519
  WOB_AUTO_KEY = 724149
  PN = 190-70974-005 (Rod)
  SN = 1464957/004
  REF = T1
  staging quantum_ro_lines.id = 3841
  status = applied
  target = TDR 979, Traveler 1, four tdr_processes rows

Historical R9326 / W107679 row:
  ROD_AUTO_KEY = 33518
  WOB_AUTO_KEY = 724148
  PN = 190-70954-005 (Cylinder)
  SN = 1464957/004
  imported REF = T1
  first_seen_at = last_seen_at = 04/Sep/2026 13:28:09
  staging quantum_ro_lines.id = 3839
  parser result = No Traveler 1 target
  status changed to dismissed at 04/Sep/2026 13:40:20
```

Production Nodus has component `190-70954-005` in manual 73, but W107679 has
no TDR for that component. Its only component TDR is Rod `190-70974-005`, so
the parser correctly had no Cylinder Traveler 1 target and could not create or
populate one. The RO apply service populates existing targets; it does not
create missing TDRs.

Current Oracle state confirms that `RO_DETAIL.ROD_AUTO_KEY = 33518` no longer
exists. `WO_BOM.WOB_AUTO_KEY = 724148` still exists for W107679 / Cylinder, but
its current `REF` is null. The staging row is therefore a historical snapshot,
not a current R9326 detail. The dismiss endpoint used at that time did not write
an activity-log record, so the responsible user cannot be established from the
application audit.

For the current Rod line (`ROD_AUTO_KEY = 33519`), Quantum also contains a
separate W107679 BOM service row:

```text
WO_BOM.WOB_AUTO_KEY = 703657
PN / DESCRIPTION = Shot Peening
ACTIVITY = Repair
RO_DETAIL link = none
```

This service BOM row is not the R9326 detail row. R9326 is linked only to the
Rod BOM row `WOB_AUTO_KEY = 724149`, whose `REF = T1`; neither that RO detail nor
its header/WO text fields contain `Shot` or `Peen`. The current RO sync reads
`RO_HEADER + RO_DETAIL + WO_BOM` for the RO detail and does not infer Traveler
membership from other service BOM rows on the same WO.

Production audit confirms that Traveler 1 for TDR 979 was created on
04/Sep/2026 with process IDs `1095, 4194, 1097, 4719` (Chrome plating, Chrome
plating Thin Dens, NDT-4, Passivation). Shot peening process ID `1093` was not
selected and correctly remains a standalone process with `in_traveler = false`.
It must not receive R9326.

The separate Quantum Shot Peening BOM row was created on 09/Apr/2026, remains
incomplete (`ITEM_COMPLETE = F`), has quantity needed 1 / issued 0, and has no
`REF`, `ROD_LINK`, or linked `RO_DETAIL`. It therefore never enters the current
RO-only staging query at all. Showing that Quantum has requested this
standalone process requires a separate WO_BOM process-presence sync; it cannot
be derived from R9326.

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


## 2026-09-28 W107775: two ROs applied to one NDT row

Read-only verification on current production (72.251.11.16): WO 223 / W107775,
TDR 1510, tdr_processes 2754, NDT-6 Eddy Current, process 440,
P/N 1840-0002, S/N L694, REF N6.

- Activity 38132, 08/Sep/2026 15:25:02 Toronto: Quantum filled R9331 and
  date_start 08/Sep/2026. Activity 39720, 14/Sep/2026 15:35:01: Quantum
  filled date_finish 14/Sep/2026.
- Activity 43204, 28/Sep/2026 13:10:02: background update changed R9331 to
  R9423, changed date_start to 28/Sep/2026, cleared the Quantum-managed
  date_finish. No human causer is recorded for these updates.
- Staging 3916 / rod:33543 (R9331) and 4186 / rod:33773 (R9423) both
  have applied target tdr_processes:2754 and matching WO/P/N/S/N/REF.
  Old staging snapshot retains R9331 and the earlier return date; the target
  row holds R9423. New staging was first seen 28/Sep/2026 13:08:09.
- Deployed TdrProcessController::show passes this row's repair_order to
  formHeaderRepairOrder; shared/process-forms/_header renders it directly.
  The form therefore reflected the stored RO, not an invented or cached number.
- Deployed fillTarget unconditionally assigns the source RO and clears a
  Quantum-managed finish when the incoming source has no return date.
  Two source rows targeting one process can therefore replace the earlier
  cycle's current fields. Historical values remain in activity/staging.

No production changes were made. This confirms AVIA staging/application
history, not whether the physical part was dispatched or returned.

### 2026-10-21 W107946 / R9521: trailing R is not normalized away

Read-only production inspection of buffered source `rod:34076` confirmed:
Quantum PN `2821-0222R`, SN `L1912`, REF `CHP`, sent 21/Oct/2026;
TDR 2246 has component 3500, PN `2821-0222`, SN `L1912`, manual 78.
That manual contains separate parts: 3500 / IPL 13-180B / 2821-0222
and 8010 / IPL 13-180R / 2821-0222R. Equal serials do not override
exact normalized PN matching. Current outcome is unresolved, no previous targets.
Do not assume these part numbers interchangeable merely from the R suffix.

There are two Chrome plating rows: standalone 4359 and Traveler 3 row 4652,
both with no RO/sent/returned. A diagnostic in-memory copy of the source with
PN changed to 2821-0222 (never saved) resolved to standalone 4359.
No source, target, mapping or production code was changed. This evidence is
from AVIA's received Quantum snapshot, not a direct Oracle query.

Follow-up 28/Oct/2026 (direct Oracle SELECT and production SELECT): R9521
now links W107946 through new RO_DETAIL 34185 with PN 2821-0222 and REF CHP;
old detail 34076 is absent from that RO. New staging row 4613 / rod:34185
was received at 11:53:09 and applied to tdr_processes:4359 at 12:00:02
(production timestamps), with RO R9521 and Sent 21/Oct/2026.
Old staging 4497 / rod:34076 remains unresolved with PN 2821-0222R because
source deletion does not delete staging. The existing per-row checkmark
in Needs attention dismisses that old record while retaining audit history;
do not reapply the obsolete row or dismiss the replacement. No production
mutation was performed during this check.

Quantum buffer WO navigation (28/Oct/2026): WO columns in both Needs attention
and Latest received link to mains.show using the actual AVIA workorder ID.
Resolve numbers in one batch for the rendered rows, including search and
pagination; missing AVIA workorders remain plain text.
