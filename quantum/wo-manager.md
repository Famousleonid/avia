# Quantum WO And Manager Findings

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
