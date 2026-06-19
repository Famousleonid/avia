# Quantum Estimate Money Reconciliation

Last updated: 2026-06-18.

Scope: manager screenshot sample `W107731` through `W107737`.

## Current Confirmed Formula

For current Quantum data, the best current estimate amount formula is:

```text
VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE
+ WO_OPERATION.LABOR_FLAT_PRICE
+ WO_OPERATION.PARTS_FLAT_PRICE
+ WO_OPERATION.MISC_FLAT_PRICE
```

Equivalent parts source:

```text
SUM(QCTL.WO_BOM.UNIT_PRICE * QCTL.WO_BOM.QTY_NEEDED)
```

For this sample:

```text
PARTS_FLAT_PRICE = 0
MISC_FLAT_PRICE  = 0
TAX_TOTAL        = 0
```

`VIEW_SPS_WO_OPERATION.TOTAL_PARTS` also matches `VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE`.

## Reconciliation Matrix

| WO | Screenshot amount | Parts ext | Labor flat | Base formula | Diff |
|---|---:|---:|---:|---:|---:|
| W107731 | 26696.80 | 10374.00 | 16187.00 | 26561.00 | 135.80 |
| W107732 | 32343.50 | 14708.70 | 16187.00 | 30895.70 | 1447.80 |
| W107733 | | 0.00 | 0.00 | 0.00 | |
| W107734 | 59753.60 | 55803.60 | 3950.00 | 59753.60 | 0.00 |
| W107735 | 201843.80 | 147380.80 | 54463.00 | 201843.80 | 0.00 |
| W107736 | 174020.80 | 101106.80 | 72914.00 | 174020.80 | 0.00 |
| W107737 | 65420.80 | 43620.80 | 19800.00 | 63420.80 | 2000.00 |

Detailed CSV:

```text
quantum/log/wo_estimate_money_reconciliation_w107731_w107737.csv
```

## Checked Sources

These sources were checked for the sample WO set:

```text
WO_OPERATION header flat/tax/cost fields
VIEW_SPS_WO_OPERATION
VIEW_SPR_WO_OPERATION
VIEW_WO_UNBILLED_PARTS
VIEW_WO_UNBILLED_MISC
WO_BOM and VIEW_WOBOM
VIEW_SPR_WO_BOM
VIEW_SPR_REQUISITION_WOB
VIEW_RPT_CQ_WORKSHEET_WO_HIST
VIEW_RPT_PO_OPEN
VIEW_WIP_DETAIL_PART
WO_CHARGE / VIEW_WIP_DETAIL_CHARGE
VIEW_WO_TASK_TOTALS
VGQL_WO_TASK
WO_REPAIRS
RO_DETAIL quoted fields
SA_LOG
STOCK_AUDIT
VIEW_SM_STOCK_PICKING
invoice views linked by customer PO/reference
```

Result:

```text
No linked current WO money field found exact 135.80, 1447.80, or 2000.00
for W107731, W107732, or W107737.
```

`VIEW_WO_UNBILLED_MISC` has real amounts, but it is not the screenshot estimate formula as-is:

| WO | VIEW_WO_UNBILLED_MISC.EXTENDED_PRICE |
|---|---:|
| W107731 | 4450.00 |
| W107732 | 4450.00 |
| W107734 | 12630.00 |
| W107735 | 24100.00 |
| W107736 | 19673.50 |
| W107737 | 9080.00 |

Including this field would break rows that already match exactly, so do not add it to the estimate formula without another report-specific filter.

## 2026-06-18 Deeper Pass

Additional checks performed after the initial matrix:

```text
QCTL.QC_WO_PKG.SPB_WO_QUOTES
VIEW_BILLING_GROUPS_AUDIT
WIP_AUDIT_LOG
REV_AUDIT_LOG
REV_CHANGE_LOG
COST_LOG
COST_AUDIT_LOG
COST_CHANGE_LOG
VIEW_WO_UNBILLED_PARTS line split by Part/OSV
VIEW_WO_UNBILLED_MISC line split by OSV
Exact amount scans for 26696.80, 32343.50, 65420.80, 2000.00
```

Result:

```text
SPB_WO_QUOTES is a QCTL.QC_WO_PKG function returning REF CURSOR, not a SQL table function.
OCI REF CURSOR call works with OCI_NO_AUTO_COMMIT + rollback, but returns 0 rows for W107731-W107737.
SPB_QUOTES_FOR_WOO is referenced by SYS_QUERIES, but is not visible in ALL_OBJECTS / ALL_ARGUMENTS / checked package source.
VIEW_BILLING_GROUPS_AUDIT / COST_LOG / COST_AUDIT_LOG / COST_CHANGE_LOG returned 0 rows for W107731-W107737.
WIP_AUDIT_LOG rows exist, but EXT_COST is 0 for the sample; REV_AUDIT_LOG / REV_CHANGE_LOG do not link to these WO through WIP_AUTO_KEY.
Exact amount scans found no current numeric row for 26696.80, 32343.50, or 65420.80.
Exact amount scan for 2000.00 has many generic hits, but none linked to W107731-W107737 / WOO_AUTO_KEY 8036-8042.
```

Important line-level finding:

```text
VIEW_WO_UNBILLED_PARTS already includes both Part and OSV item types.
VIEW_WO_UNBILLED_MISC is the OSV subset already counted inside VIEW_WO_UNBILLED_PARTS.
```

For the sample:

| WO | Unbilled Parts: OSV | Unbilled Parts: Part | Unbilled Parts Total | Unbilled Misc |
|---|---:|---:|---:|---:|
| W107731 | 4450.00 | 5924.00 | 10374.00 | 4450.00 |
| W107732 | 4450.00 | 10258.70 | 14708.70 | 4450.00 |
| W107734 | 12630.00 | 43173.60 | 55803.60 | 12630.00 |
| W107735 | 24100.00 | 123280.80 | 147380.80 | 24100.00 |
| W107736 | 19673.50 | 81433.30 | 101106.80 | 19673.50 |
| W107737 | 9080.00 | 34540.80 | 43620.80 | 9080.00 |

So `VIEW_WO_UNBILLED_MISC` must not be added to `VIEW_WO_UNBILLED_PARTS`; that double-counts OSV.

New supporting exports:

```text
quantum/log/object_lookup_spb.csv
quantum/log/wo_spb_quotes_proc_w107731_w107737.csv
quantum/log/wo_audit_money_w107731_w107737.csv
quantum/log/wo_change_audit_w107731_w107737.csv
quantum/log/wo_report_lines_w107731_w107737.csv
quantum/log/wo_bom_lines_w107731_w107737.csv
quantum/log/wo_task_prices_w107731_w107737.csv
quantum/log/wo_money_matrix_w107731_w107737.csv
quantum/log/amount_scan_26696_80.csv
quantum/log/amount_scan_32343_50.csv
quantum/log/amount_scan_65420_80.csv
quantum/log/amount_scan_2000_00.csv
```

## Exact Amount Search Notes

`1447.80` exact broad scan result:

```text
VIEW_SPR_AP_CASH_FLOW_FORECAST.FOREIGN_AMOUNT, date 2020-12-22
VIEW_WO_UNBILLED_PARTS.REC_COST / UNIT_COST for W103160
```

Not related to `W107732`.

`135.80` exact broad scan result:

```text
VIEW_RPT_CQ_VQ_ACTVTY_LISTING / related VQ worksheet views, date 2023-04-28
```

Not related to `W107731`.

Earlier exact search for `2000.00` found unrelated invoice `I5957` dated 2024-09-10, not the sample WO.

## PVIEW Warning

`PVIEW_WQD_*` views expose very relevant-looking quote price fields, including `TOTAL_PRICE`, `PARTS_PRICE`, `LABOR_PRICE`, `MISC_PRICE`, and tax fields.

But querying them triggers:

```text
ORA-14551: cannot perform a DML operation inside a query
QCTL.QC_CACHE_PKG
```

Treat `PVIEW_WQD_*` as unsafe/unavailable for read-only sync unless Quantum provides a supported way to refresh/read that cache.

## Current Conclusion

For current table data, use:

```text
VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE + WO_OPERATION.LABOR_FLAT_PRICE
```

plus header flat parts/misc when nonzero.

Three screenshot amounts still do not match current linked Quantum money fields:

```text
W107731 +135.80
W107732 +1447.80
W107737 +2000.00
```

Most likely next sources:

```text
Crystal report SQL / report formula
Quantum UI report query/log
report-specific cached quote data behind PVIEW_WQD_*
historical snapshot at approval time that is not exposed in the checked current WO-linked/audit tables
manual/exported manager report value outside current Oracle fields available to CRYSTAL
```
