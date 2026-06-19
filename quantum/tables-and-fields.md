# Quantum Tables And Fields

## QCTL.RO_HEADER

| Field | Status | Meaning |
|---|---|---|
| `ROH_AUTO_KEY` | confirmed | Internal RO header key |
| `RO_NUMBER` | confirmed | Repair Order number, e.g. `R8917` |
| `VENDOR_NAME` | confirmed | Vendor name |
| `ENTRY_DATE` | confirmed | RO entered/created date |
| `OUT_DATE` | confirmed | Date sent to vendor |
| `OPEN_FLAG` | confirmed | `T` open, `F` closed |
| `LAST_MODIFIED` | confirmed | Header last modified date |
| `APPROVED_DATE` | exists | RO header approval date, not manager screenshot approval |
| `QUOTE_DATE` | exists | RO quote date candidate |
| `NEXT_ACT_DATE` | exists | Next action date candidate |

Confirmed date logic:

```text
RO_HEADER.OUT_DATE = Date Sent
RO_HEADER.OPEN_FLAG = T means open
RO_HEADER.OPEN_FLAG = F means closed
```

## QCTL.RO_DETAIL

| Field | Status | Meaning |
|---|---|---|
| `ROD_AUTO_KEY` | confirmed | Internal RO detail key |
| `ROH_AUTO_KEY` | confirmed | Link to RO_HEADER |
| `WOB_AUTO_KEY` | confirmed | Link to WO_BOM |
| `WOO_AUTO_KEY` | exists | Direct WO link if populated |
| `PNM_AUTO_KEY` | confirmed | Link to PARTS_MASTER |
| `SERIAL_NUMBER` | exists | Detail serial candidate |
| `QTY_REPAIR` | confirmed candidate | To Repair |
| `QTY_RESERVED` | confirmed candidate | Reserved |
| `QTY_REPAIRED` | confirmed candidate | Repaired |
| `QTY_SCRAPPED` | exists | Scrapped quantity |
| `QTY_BILLED` | exists | Billed quantity |
| `LAST_DELIVERY_DATE` | confirmed | Date returned from vendor |
| `NEXT_DELIVERY_DATE` | confirmed | Expected/next delivery date |
| `LAST_MODIFIED` | confirmed | Detail last modified date |
| `WO_PRICE` | checked | Empty/0 for current manager sample |
| `EST_PRICE` | checked | Empty/0 for current manager sample |
| `QUOTED_PARTS_HOME` | checked | Empty/0 for current manager sample |
| `QUOTED_LABOR_HOME` | checked | Empty/0 for current manager sample |
| `QUOTED_MISC_HOME` | checked | Empty/0 for current manager sample |
| `MSG_QUOTE_REC_DATE` | checked | Empty for current manager sample |
| `MSG_QUOTE_APPR_DATE` | checked | Empty for current manager sample |
| `MSG_QUOTE_AUTO_APPR_DATE` | checked | Empty for current manager sample |
| `MSG_EST_DELIV_DATE` | exists | Message/event estimated delivery date |
| `SHIP_DATE` | exists | Often empty in tested RO data |

Confirmed date logic:

```text
RO_DETAIL.LAST_DELIVERY_DATE = Date Returned
RO_DETAIL.NEXT_DELIVERY_DATE = expected/next return
```

## QCTL.WO_OPERATION

| Field | Status | Meaning |
|---|---|---|
| `WOO_AUTO_KEY` | confirmed | Internal WO key |
| `SI_NUMBER` | confirmed | WO number, e.g. `W107739` |
| `WO_DISP` | confirmed | WO status/display, e.g. `WIP` |
| `COMPANY_REF_NUMBER` | confirmed | Company/reference number |
| `BILL_NAME` | confirmed candidate | Customer name in manager screenshot sample |
| `ENTRY_DATE` | confirmed | Estimate/open date in manager screenshot sample |
| `PNM_AUTO_KEY` | confirmed | Link to PARTS_MASTER |
| `PARTS_FLAT_PRICE` | exists | Header flat parts component |
| `LABOR_FLAT_PRICE` | confirmed | Header flat labor component for estimate amount |
| `MISC_FLAT_PRICE` | exists | Header flat misc component |
| `TAX_TOTAL` | checked | 0 for manager screenshot sample; not the missing estimate diff |
| `EST_PARTS_COST` | checked | 0 for manager screenshot sample |
| `EST_LABOR_COST` | checked | 0 for manager screenshot sample |
| `EST_MISC_COST` | checked | 0 for manager screenshot sample |
| `EST_OSV_COST` | checked | 0 for manager screenshot sample |
| `EI_TOT_LABOR_COST` | checked | 0 for manager screenshot sample |
| `QUOTE_STATUS` | checked | 0 for manager screenshot sample |
| `QUOTE_STATUS_DATE` | checked | Empty for manager screenshot sample |
| `MSG_QUOTE_APPR_DATE` | checked | Empty for manager screenshot sample |
| `MSG_WO_INVOICE_SENT_DATE` | checked | Empty for manager screenshot sample |
| `CLOSE_DATE` | confirmed candidate | Completed Date candidate for closed WO |
| `CAMP_CLOSED_DATE` | exists | Secondary completed/closed date candidate |
| `MSG_SHIP_TO_CUST_DATE` | exists | Secondary ship-to-customer event date candidate |

Important:

```text
A RO can be closed while the related WO is still WIP.
This is normal.
```

Confirmed current `WO_OPERATION.WO_DISP` values:

| WO_DISP | Seen with TAT status |
|---|---|
| `COG` | `CANCELLED`, `Closed`, `Open` |
| `INV` | `Closed` |
| `WIP` | `Closed`, `Open`, `Pending` |

Evidence:

```text
quantum/log/wo_statuses.csv
```

## QCTL.WO_BOM

| Field | Status | Meaning |
|---|---|---|
| `WOB_AUTO_KEY` | confirmed | Internal BOM row key |
| `WOO_AUTO_KEY` | confirmed | Link to WO_OPERATION |
| `PNM_AUTO_KEY` | confirmed | Link to PARTS_MASTER |
| `REF` | confirmed | Quantum UI Ref field in Adding Bill of Materials |
| `QTY_NEEDED` | confirmed | Quantity used in parts estimate formula |
| `QTY_ISSUED` | exists | Issued quantity |
| `UNIT_PRICE` | confirmed | Unit price used in parts estimate formula |
| `EST_COST` | exists | Estimated cost |
| `CONS_PRICE` | exists | Consumable price candidate |
| `EXCHANGE_REPAIR_PRICE` | exists | Exchange repair price candidate |

Confirmed:

```text
SUM(WO_BOM.UNIT_PRICE * WO_BOM.QTY_NEEDED)
matches VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE.
```

## QCTL.INVC_HEADER

| Field | Status | Meaning |
|---|---|---|
| `INH_AUTO_KEY` | confirmed | Internal invoice header key |
| `WOO_AUTO_KEY` | confirmed | Direct link to `WO_OPERATION.WOO_AUTO_KEY` when populated |
| `INVC_NUMBER` | confirmed | Invoice number |
| `INVOICE_DATE` | confirmed | Invoice Date candidate |
| `POST_DATE` | confirmed | Posted invoice date candidate |
| `SHIP_DATE` | confirmed | Invoice-level Ship Date candidate |
| `AIRWAY_BILL` | confirmed | AWB # candidate |
| `TRACKING_NUMBER` | confirmed | Tracking/AWB fallback candidate |
| `TOTAL_PRICE` | exists | Invoice header total |

For manager columns, preferred direct mapping when present:

```text
Invoice      = INVC_HEADER.INVC_NUMBER
Invoice Date = INVC_HEADER.INVOICE_DATE
Ship Date    = INVC_HEADER.SHIP_DATE
AWB #        = INVC_HEADER.AIRWAY_BILL or INVC_HEADER.TRACKING_NUMBER
```

## QCTL.WO_STM_COMPLETE

| Field | Status | Meaning |
|---|---|---|
| `WSC_AUTO_KEY` | confirmed | Internal complete/stock movement key |
| `WOO_AUTO_KEY` | confirmed | Link to `WO_OPERATION.WOO_AUTO_KEY` |
| `QTY_COMPLETE` | exists | Completed quantity |
| `QTY_BILLED` | exists | Billed quantity |
| `QTY_RETURNED` | exists | Returned quantity |
| `SHIP_DATE` | confirmed candidate | Workorder completion/stock movement ship date |

## QCTL.WO_QUOTE_HEADER

| Field | Status | Meaning |
|---|---|---|
| `WQH_AUTO_KEY` | confirmed | Internal WO quote header key |
| `WOO_MASTER` | confirmed | Link to `WO_OPERATION.WOO_AUTO_KEY` |
| `SHIP_DATE` | confirmed candidate | Quote-level ship date |
| `AIRWAY_BILL` | confirmed candidate | Quote-level AWB # |

## QCTL.PARTS_MASTER

| Field | Status | Meaning |
|---|---|---|
| `PNM_AUTO_KEY` | confirmed | Part master key |
| `PN` | confirmed | Part number / process code |
| `DESCRIPTION` | confirmed | Description |
| `PN_STRIPPED` | exists | Search helper |
| `PN_UPPER` | exists | Search helper |
| `DESCRIPTION_UPPER` | exists | Search helper |

Classification hints from RO sync:

```text
PN = NDT -> STD_LIST_NDT
PN = CAD -> STD_LIST_CAD
PN with digits -> DETAIL_PART
Other PN values -> DETAIL_PROCESS
Empty PN -> UNKNOWN
```

## Useful Views

| View | Status | Notes |
|---|---|---|
| `VIEW_SPB_WO_MAINCOMPONENT` | confirmed | PN/SN for manager screenshot sample |
| `VIEW_SPS_WO_SERIAL_NUMS` | confirmed | Serial number also matches sample |
| `VIEW_SPR_WO_BOM` | confirmed | BOM price component, `UNIT_PRICE * QTY_NEEDED` |
| `VIEW_SPS_WO_OPERATION` | confirmed | `TOTAL_PARTS` matches `VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE`; `LABOR_FLAT_PRICE` matches WO header labor |
| `VIEW_WO_UNBILLED_PARTS` | confirmed | Best current parts estimate source: `EXTENDED_PRICE`; matches BOM extended formula |
| `VIEW_WO_UNBILLED_MISC` | partial | Misc/OSV lines visible, but not direct screenshot estimate formula |
| `VIEW_WO_SUMMARY_WITH_TAT` | partial | Good identity PN/SN/customer/date, `TOTAL_QUOTED_PRICE` empty in sample |
| `VIEW_WO_BILLING` | confirmed candidate | `INVC_NUMBER`, `POST_DATE`, `WOO_AUTO_KEY`; empty invoice values for W107731-W107737 sample |
| `VIEW_WOO_PRICES` | checked | 0 rows for current manager sample |
| `VIEW_WQD_PRICES` | checked | Not matched for current manager sample |
| `VIEW_SPS_WOBILLING3` | checked | 0 rows for current manager sample |

## Checked Money / History Areas

The 2026-06-18 wide money scan checked current WO-linked money fields for `W107731-W107737`, including:

```text
WO_OPERATION / VIEW_SPS_WO_OPERATION / VIEW_SPR_WO_OPERATION
WO_BOM / VIEW_WOBOM / VIEW_SPR_WO_BOM
VIEW_WO_UNBILLED_PARTS
VIEW_WO_UNBILLED_MISC
VIEW_RPT_CQ_WORKSHEET_WO_HIST
VIEW_RPT_PO_OPEN
VIEW_WIP_DETAIL_PART
SA_LOG
STOCK_AUDIT
VIEW_SM_STOCK_PICKING
invoice views linked through customer PO/reference
```

No linked current field contained exact missing diff values:

```text
W107731: 135.80
W107732: 1447.80
W107737: 2000.00
```

Evidence:

```text
quantum/log/wo_money_wide_with_audit_w107731_w107737.csv
quantum/log/wo_estimate_money_reconciliation_w107731_w107737.csv
```

## Unsafe / Avoid For Sync

`PVIEW_WQD_MAIN`, `PVIEW_WQD_PRICES`, `PVIEW_WQD_TREEPARSE_VALUES`, and `PVIEW_WQH_PRICES`
have relevant-looking quote/price columns, but SELECT currently triggers:

```text
ORA-14551: cannot perform a DML operation inside a query
QCTL.QC_CACHE_PKG
```

Do not use these PVIEW sources in read-only sync unless Quantum provides a supported way to read or refresh that cache.
