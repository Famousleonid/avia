# Quantum Open Questions

Use this file only for unknowns or partial findings. When a question is closed, move the confirmed fact to the relevant reference file.

## Manager / New Page

Still unknown for the screenshot sample:

```text
Approval date source
Approved Y/N flag source
Rightmost invoice/next-date source
Extra amount component for W107731, W107732, W107737
```

Known missing estimate amount components after the current formula:

| WO | Missing amount |
|---|---:|
| W107731 | 135.80 |
| W107732 | 1447.80 |
| W107737 | 2000.00 |

Current formula:

```text
VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE
+ WO_OPERATION.LABOR_FLAT_PRICE
+ WO_OPERATION.PARTS_FLAT_PRICE
+ WO_OPERATION.MISC_FLAT_PRICE
```

For the screenshot sample, header flat parts/misc and tax are zero.

## Estimate / Approval Candidate Areas

Already checked and not matching:

```text
WO_OPERATION header flat/tax/cost fields
WO_QUOTE_HEADER / WO_QUOTE_DETAIL
INVC_HEADER by WOO_AUTO_KEY
INVC_HEADER / invoice views through PO/reference join
RO_DETAIL quote and price fields
WO_OPERATION MSG_QUOTE_* fields
WO custom UDF/SDF fields
WO_TASK_LABOR
WO_CHARGE / VIEW_WIP_DETAIL_CHARGE
KAC_BILLING / KAC_BASE_LABOR
VIEW_WOO_PRICES
VIEW_WQD_PRICES
VIEW_SPS_WOBILLING3
VIEW_WO_UNBILLED_MISC as a direct add-on
VIEW_RPT_CQ_WORKSHEET_WO_HIST
VIEW_RPT_PO_OPEN
VIEW_WIP_DETAIL_PART
SA_LOG
STOCK_AUDIT
VIEW_SM_STOCK_PICKING
QCTL.QC_WO_PKG.SPB_WO_QUOTES for W107731-W107737
VIEW_BILLING_GROUPS_AUDIT
WIP_AUDIT_LOG / REV_AUDIT_LOG / REV_CHANGE_LOG
COST_LOG / COST_AUDIT_LOG / COST_CHANGE_LOG
Exact current amount rows for screenshot totals 26696.80 / 32343.50 / 65420.80
```

Important partial result:

```text
VIEW_WO_UNBILLED_MISC has real values, but adding it breaks W107734/W107735/W107736,
which already match the screenshot exactly. It needs a report-specific filter if it is involved.

VIEW_WO_UNBILLED_PARTS already includes OSV + Part. VIEW_WO_UNBILLED_MISC is the OSV subset
already counted inside VIEW_WO_UNBILLED_PARTS, so direct addition double-counts OSV.

SPB_WO_QUOTES is callable only as QCTL.QC_WO_PKG REF CURSOR function, not as FROM SPB_WO_QUOTES.
The OCI cursor call returned 0 rows for W107731-W107737.
```

Potential next search directions:

```text
Crystal report source SQL/formula
Quantum UI report name and report query/log
Supported way to read PVIEW_WQD_* quote cache without ORA-14551
Historical approval-time snapshot not exposed in checked current WO-linked/audit tables
External/manual manager report export value outside Oracle fields visible to CRYSTAL
Approval dates from non-WOO keys, maybe external message/portal tables
Closed/invoiced/shipped WO examples to validate Invoice, Invoice Date, Ship Date, AWB #, Completed Date source priority
```

## RO Sync

Open:

```text
If multiple partial returns happen on different dates, should avia aggregate
date_finish use earliest partial return date or latest return date?
```

Current user wording says "date of partial return", but MIN vs MAX still needs confirmation for multiple partial returns.

## WO Sync

Open:

```text
Reliable WO incremental change date.
Authoritative customer source if BILL_NAME differs from COMPANIES join.
Authoritative serial source outside manager sample.
Status mapping from Quantum WO_DISP to avia workorder lifecycle.
Whether WO sync should create/update avia workorders or only stage rows first.
```
