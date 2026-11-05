# Quantum WO And Manager Findings

## Unit History tab (04/Nov/2026)

Description extension (04/Nov/2026): unit description comes from
`WO_OPERATION.PNM_AUTO_KEY -> PARTS_MASTER.DESCRIPTION`, the same part-master
row as the unit P/N. Export field `description` is optional on ingestion for
old snapshots; missing/empty descriptions display a dash. Deploy `/app` and
`/resources` before uploading the new snapshot: the old validator drops the
unrecognised field. Bridge exporter updated and SHA256 verified; after user
deployment, upload succeeded 04/Nov/2026 10:40 Toronto (8,184 WOs). Production
UI verified `55000-3` / W107176 / `MLG LH`. The existing daily task uses this
updated exporter without schedule changes. Confirmed SELECT examples: `55000-3` =
`MLG LH`, `190-70745-403` = `NLG Shock Strut`, `190-70745-605` =
`NLG Shock Strut Assy.`.

`/wo-status/unit-history`, beside Efficiency, uses a private complete Quantum
snapshot, not the local AVIA WO list. Admin/Manager access matches WO Status.
Root WO rows only; closed rows retained. Search matches the entered P/N prefix
(case-insensitive, trimmed): `55000` includes all its variants; `55000-3`
includes `55000-3`, `55000-3A`, `55000-3-1`, `55000-30`, but not `55000-1/2/4`.
Never strip the entered suffix to expand a full P/N back to siblings.
Input suggestions come from the same JSON root WO rows, independently of date
filters: unique groups and full P/Ns, with distinct Description values merged
per suggestion. Native datalist labels distinguish Group and P/N; free-text
prefixes remain valid and selecting a suggestion does not submit the form.
Filter datalist options by P/N startsWith on input/focus: native browser matching
otherwise includes substrings and labels. Keep the complete option list in memory
so changing or clearing the prefix restores the matching choices.
Suggested groups still remove the final dash and suffix. Blank dates mean full history.
The monthly table was removed at user request. Rolling twelve-month totals
use the same P/N prefix, ignore the optional date filter and are labelled. Mean gap
uses distinct WO opening dates, not unit count or repair completion dates.

Oracle read-only validation found 8,184 distinct WO numbers on 04/Nov/2026,
all with PNM_AUTO_KEY and ENTRY_DATE, no WOO_PARENT. Quantity is the recorded
WO_OPERATION.KIT_QTY (8,171 rows qty1, 12 qty2, one qty3), not COUNT(WO).
It is not proof of physical arrivals: some multi-quantity rows have repeated
serials across distinct stock IDs, mixed main-component P/Ns, or a mismatch
against VIEW_SPB_WO_MAINCOMPONENT. These get a visible quantity-review flag;
totals are labelled as recorded Quantum units. The main-component view combines
stock reservations, completed stock and RO reservations; do not blindly sum
its rows as unique physical arrivals. S/N display is deduplicated, quantities
are not silently rewritten. Group190-70745:36 WO/36 recorded units,
7 units in rolling12months at this check, first08/Aug/2022,last26/Oct/2026.

Data source: WO_OPERATION -> PARTS_MASTER via PNM_AUTO_KEY; customer BILL_NAME;
dates ENTRY_DATE/CLOSE_DATE; raw status WO_DISP. Raw WIP can coexist with a
close date, so do not infer open/closed solely from WO_DISP.

### Delivery and refresh

App folders to copy to production: `/app`, `/routes`, `/resources`.
No migration is required. Do not copy a developer's whole storage directory.
Copy `/WO` to the existing Quantum bridge. The standalone exporter uses
existing ORACLE_USER/ORACLE_PASS/ORACLE_DSN and AVIA_SYNC_API_TOKEN environment.
Set AVIA_UNIT_HISTORY_API_URL to https://aviatechnik.ca/api/quantum/unit-history;
alternatively it derives that endpoint from existing AVIA_SYNC_API_URL ending
in /ro-sync. HTTPS certificate verification stays enabled; no default token.
For the existing Windows bridge, use `WO/run_unit_history.ps1`: it loads
`C:\avia\RO\.env.sync_quantum`, resolves PHP_PATH and Oracle DLL/TNS paths,
checks oci8/curl, and logs to `WO/unit_history_sync.log`. `-CheckOnly` performs
local settings/module checks without Oracle query/upload.
One-command installation: run `WO/install_unit_history_task.ps1` from elevated
Windows PowerShell. It registers only the fixed task sync_quantum_unit_history,
daily06:30 in the bridge computer time zone, under SYSTEM (no interactive login
or Windows password needed). It starts the first upload under that same account
and waits for Task Scheduler success; failures are reported, with the task left
installed. Retry3x at15minutes, StartWhenAvailable, IgnoreNew, ten-minute limit.
An existing task with a different description or a running history task is not
overwritten. The original sync_quantum and its environment file are never changed.
The installer accepts -EnvFile for a nonstandard existing configuration and
-WhatIf for a no-action check. Syntax and WhatIf were tested locally; actual
registration/upload on the bridge remains a user-executed deployment step.
The raw `php -d extension=php_oci8_19.dll WO/unit_history_export.php --send`
command works only when the caller has already loaded the bridge environment.
This task was not installed or executed against production.
Do not run the exporter from the public web application: Oracle stays on the
bridge. Until a first sync the page says no history loaded; after two days
without refresh it warns of stale data.

Local initial population used `--output=storage/app/codex/quantum-unit-history.json`
and `php artisan quantum-units:import storage/app/codex/quantum-unit-history.json`.
Private file storage/app/quantum/unit-history.json is Git-ignored. Authenticated
API ingestion validates identities/count/schema/timestamps, rejects older
snapshots and atomically replaces the file under a lock; failed updates retain
the previous snapshot. A query is read-only; it does not import or alter AVIA WOs.

## Application Integration Status

As of 15/Jul/2026, Marketing Sales Report does not read invoice data from Quantum.
Its **Invoiced Amount** and invoice date come only from the local Workorder fields
`sales_invoice_amount` and `sales_invoice_date`, entered by accounting in Marketing WO.
`wo_estimate_amount` remains a separate estimate value and is not treated as invoiced revenue.

## WO Preview Extraction

Current local files:

```text
WO/sync_wo.php
WO/quantum_wo_query.php
WO/.env.sync_quantum_wo.example
WO/log
```

Preview output fields:

```text
WO_NUMBER
UNIT_PN
CUSTOMER
SERIAL_NUMBER
DESCRIPTION
OPEN_DATE
```

Useful commands:

```powershell
php -d extension=php_oci8_19.dll WO\sync_wo.php --wo=W107739
php -d extension=php_oci8_19.dll WO\sync_wo.php --days=90
php -d extension=php_oci8_19.dll WO\sync_wo.php --metadata
php -d extension=php_oci8_19.dll WO\sync_wo.php --field-search
```

## Manager Discovery Files

Current local files:

```text
manager/sync_manager.php
manager/quantum_manager_query.php
manager/.env.sync_quantum_manager.example
manager/log
```

Useful modes added during 2026-06-17 research:

```text
tables
columns
links
table-columns
wo-batch
wo-report-lines
wo-bom-money
wo-header-money
wo-ro-detail
wo-labor-raw
wo-custom-fields
wo-date-fields
wo-summary-tat
wo-estimate-lines
wo-quote-deep
wo-billing-summary
latest-estimates
wo-manager-columns
wo-money-matrix
wo-money-wide
wo-bom-lines
wo-sps-totals
```

Current exports:

```text
quantum/log/latest_estimate_candidates_20.csv
quantum/log/latest_estimate_candidates_100.csv
quantum/log/wo_manager_columns_w107731_w107737.csv
quantum/log/wo_statuses.csv
```

## Screenshot Sample Set

Real rows provided by user:

| WO | PN | SN | Customer | Estimate date | Estimate | Approval date | Approved | Right date |
|---|---|---|---|---|---:|---|---|---|
| W107731 | 170-70150-403 | 00235 | AirStart | 29/apr/2026 | 26696.80 | 08/may/2026 | Y | 27/may/2026 |
| W107732 | 170-70150-403 | 00360 | Jazz | 04/may/2026 | 32343.50 | 07/may/2026 | Y | 22/may/2026 |
| W107733 | 170-70496-003 | 1424338/009 | Aviatechnik | 04/may/2026 | | | N | |
| W107734 | 49300-9 | SPP510222 | Jazz | 05/may/2026 | 59753.60 | 09/jun/2026 | N | |
| W107735 | 52000-31 | MA1647 | Air Nostrum | 07/may/2026 | 201843.80 | 02/jun/2026 | N | |
| W107736 | 2801A0000-03 | 00220 | Regional One | 08/may/2026 | 174020.80 | 28/may/2026 | Y | 15/jun/2026 |
| W107737 | 55000-5 | SPC012009 | Air Nostrum | 08/may/2026 | 65420.80 | 29/may/2026 | Y | 17/jun/2026 |

## Confirmed Identity Fields

For screenshot sample:

```text
WO number:
  WO_OPERATION.SI_NUMBER

Customer:
  WO_OPERATION.BILL_NAME

Estimate/open date:
  WO_OPERATION.ENTRY_DATE

PN:
  WO_OPERATION.PNM_AUTO_KEY -> PARTS_MASTER.PN
  also visible in VIEW_SPB_WO_MAINCOMPONENT.PART_NUMBER

Serial:
  VIEW_SPB_WO_MAINCOMPONENT.SERIAL_NUMBER
  VIEW_SPS_WO_SERIAL_NUMS.SERIAL_NUMBER
```

Confirmed sample results:

| WO | PN source | SN source | Customer source | Entry date |
|---|---|---|---|---|
| W107731 | 170-70150-403 | 00235 | AirStart Inc. | 29/apr/2026 |
| W107732 | 170-70150-403 | 00360 | Jazz Aviation LP | 04/may/2026 |
| W107733 | 170-70496-003 | 1424338/009 | Aviatechnik Corporation | 04/may/2026 |
| W107734 | 49300-9 | SPP510222 | Jazz Aviation LP | 05/may/2026 |
| W107735 | 52000-31 | MA1647 | Air Nostrum L.A.M.,S.A. | 07/may/2026 |
| W107736 | 2801A0000-03 Amdt. A | 00220 | Regional One | 08/may/2026 |
| W107737 | 55000-5 | SPC012009 | Air Nostrum L.A.M.,S.A. | 08/may/2026 |

## Confirmed Current Estimate Formula

Parts component:

```text
QCTL.VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE
QCTL.VIEW_SPS_WO_OPERATION.TOTAL_PARTS
SUM(QCTL.WO_BOM.UNIT_PRICE * QCTL.WO_BOM.QTY_NEEDED)
```

Labor component:

```text
QCTL.WO_OPERATION.LABOR_FLAT_PRICE
```

Current formula:

```text
VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE
+ WO_OPERATION.LABOR_FLAT_PRICE
+ WO_OPERATION.PARTS_FLAT_PRICE
+ WO_OPERATION.MISC_FLAT_PRICE
```

In current sample `PARTS_FLAT_PRICE`, `MISC_FLAT_PRICE`, and `TAX_TOTAL` are 0.

Validation:

| WO | BOM parts ext | Labor flat | Candidate amount | Screenshot amount | Diff |
|---|---:|---:|---:|---:|---:|
| W107731 | 10374.00 | 16187.00 | 26561.00 | 26696.80 | 135.80 |
| W107732 | 14708.70 | 16187.00 | 30895.70 | 32343.50 | 1447.80 |
| W107734 | 55803.60 | 3950.00 | 59753.60 | 59753.60 | 0.00 |
| W107735 | 147380.80 | 54463.00 | 201843.80 | 201843.80 | 0.00 |
| W107736 | 101106.80 | 72914.00 | 174020.80 | 174020.80 | 0.00 |
| W107737 | 43620.80 | 19800.00 | 63420.80 | 65420.80 | 2000.00 |

Evidence:

```text
manager/log/quantum_manager_wo_bom_money_20260617_160150.csv
manager/log/quantum_manager_wo_header_money_20260617_160316.csv
quantum/estimate-money-reconciliation.md
quantum/log/wo_estimate_money_reconciliation_w107731_w107737.csv
quantum/log/wo_money_wide_with_audit_w107731_w107737.csv
```

Important:

```text
VIEW_WO_UNBILLED_MISC is not a direct add-on to the manager estimate.
It has real values, but adding it would make already matched rows wrong.
```

Wide context scan checked linked current money fields, including audit/log tables.
No linked current field contained exact `135.80`, `1447.80`, or `2000.00`
for `W107731`, `W107732`, or `W107737`.

`PVIEW_WQD_*` exposes relevant-looking quote totals but currently throws
`ORA-14551` through `QCTL.QC_CACHE_PKG` on SELECT. Do not use it in read-only sync
unless Quantum provides a supported read path.

## Manager Invoice / Shipping / Completed Columns

User supplied additional page columns:

```text
Invoice
Invoice Date
Ship Date
AWB #
Completed Date
```

Confirmed candidate mapping:

| Page column | Preferred source | Fallback/source candidates |
|---|---|---|
| Invoice | `INVC_HEADER.INVC_NUMBER` joined by `COMPANY_PO_NUMBER` to `WO_OPERATION.COMPANY_REF_NUMBER` | `INVC_HEADER.WOO_AUTO_KEY`, `VIEW_WO_BILLING.INVC_NUMBER` checked but empty for WO-linked samples |
| Invoice Date | `INVC_HEADER.INVOICE_DATE` through PO/reference join | `VIEW_WO_BILLING.POST_DATE`, `VIEW_WO_SUMMARY_WITH_TAT.INVC_POST_DATE` |
| Ship Date | `INVC_HEADER.SHIP_DATE` through PO/reference join | `WO_STM_COMPLETE.SHIP_DATE`, `WO_QUOTE_HEADER.SHIP_DATE`, `VIEW_WO_SUMMARY_WITH_TAT.SHIP_DATE`, `WO_OPERATION.MSG_SHIP_TO_CUST_DATE` |
| AWB # | `INVC_HEADER.AIRWAY_BILL` / `INVC_HEADER.TRACKING_NUMBER` | `WO_QUOTE_HEADER.AIRWAY_BILL` |
| Completed Date | `WO_OPERATION.CLOSE_DATE` | `WO_OPERATION.CAMP_CLOSED_DATE` |

Diagnostic mode:

```powershell
php -d extension=php_oci8_19.dll manager\sync_manager.php --mode=wo-manager-columns --wos=W107731,W107732,W107733,W107734,W107735,W107736,W107737 --format=csv
```

Result for screenshot sample `W107731-W107737`:

```text
All five new fields are empty in confirmed sources.
WO_STATUS is WIP for all seven rows.
VIEW_WO_SUMMARY_WITH_TAT.STATUS is Pending for all seven rows.
```

So these columns likely populate only after invoice/ship/close events, not while the WO is still WIP.

Latest 100 estimate export:

```text
quantum/log/latest_estimate_candidates_100.csv
```

Changes made for the manager table:

```text
Removed WOO_AUTO_KEY from visible export.
Removed SOURCE_FORMULA from visible export.
Invoice columns are present, but latest 100 estimate rows have no invoice number/date.
Some rows have Ship Date and Completed Date from completion/close candidates.
```

Confirmed invoice link:

```text
INVC_HEADER.WOO_AUTO_KEY is empty in latest invoice samples.
INVC_HEADER.WQH_AUTO_KEY is empty in latest invoice samples.
VIEW_WO_BILLING did not produce WO-linked invoice samples.

Working join:
REGEXP_REPLACE(UPPER(INVC_HEADER.COMPANY_PO_NUMBER), '[^A-Z0-9]', '')
= REGEXP_REPLACE(UPPER(WO_OPERATION.COMPANY_REF_NUMBER), '[^A-Z0-9]', '')
```

Evidence:

```text
quantum/log/invoice_header_samples.csv
quantum/log/invoice_po_wo_samples.csv
```

Important: one invoice/customer PO can match multiple WO rows.
Example `I7413` / `RO 22130703` matched W105378, W105379, W105380.

## Checked And Not Matching

For current screenshot sample, these did not provide amount/approval/final date:

```text
WO_OPERATION.EST_* totals = 0
WO_OPERATION.EI_TOT_* totals = 0
INVC_HEADER by WOO_AUTO_KEY = 0 rows
BILLING_GROUPS flat price totals = 0
CQ_DETAIL through WO_BOM = 0 rows
WO_QUOTE_HEADER / WO_QUOTE_DETAIL deep scan = 0 rows
VIEW_SPR_WO_OPERATION estimate fields = 0
VIEW_WQH_SO_BACK_CHARGE = 0 rows
VIEW_SPS_WOBILLING3 = 0 rows
KAC_BILLING / KAC_BASE_LABOR = 0 rows
WO_TASK_LABOR raw labor records = 0 rows
WO_CHARGE / VIEW_WIP_DETAIL_CHARGE = 0 or not matching
VIEW_WO_TASK_TOTALS = 0 rows
VIEW_RPT_CQ_WORKSHEET_WO_HIST = not matching screenshot estimate total
VIEW_RPT_PO_OPEN = PO cost/order data, not estimate total
SA_LOG / STOCK_AUDIT = no missing diff amounts
VIEW_SM_STOCK_PICKING = quantities/user ids, not estimate money
PVIEW_WQD_* = unsafe/unavailable due ORA-14551/QC_CACHE_PKG
RO_DETAIL linked through WO_BOM has 0/empty price and quote date fields
WO custom UDF/SDF fields empty
WO_OPERATION date fields empty except ENTRY_DATE
VIEW_WO_SUMMARY_WITH_TAT.TOTAL_QUOTED_PRICE empty
```

## Control Example That Did Not Match

User-provided reference:

```text
WO: 106254
RO/reference: RO456216
PN: 42700-13
SN: 64353
WO estimate: 15453.00
WO estimate date: 05/mar/2026
Approval date: 06/mar/2026
```

Quantum check:

```text
WO_OPERATION.SI_NUMBER = W106254 exists.
WOO_AUTO_KEY = 6520
WO_STATUS = COG
COMPANY_REF_NUMBER = RO.1639786
ENTRY_DATE = 17/jul/2024
CLOSE_DATE = 29/jul/2024
BILL_NAME = Jazz Aviation LP
WO extractor UNIT_PN = 52195-3
SERIAL_NUMBER = empty
```

Conclusion:

```text
The provided values do not match W106254 through the confirmed WO path.
Do not assume bare numeric WO is enough. Search by RO / PN / SN too.
```
