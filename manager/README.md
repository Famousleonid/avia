# Quantum Manager Notes

Папка для сбора фактов по будущей странице manager в avia.

Цель: найти и подтвердить связи между Quantum workorder и соседними сущностями:

- Work Order / `WO_OPERATION`
- WO estimate
- invoice
- quote / billing / customer records
- любые промежуточные таблицы, которые связывают WO с estimate/invoice

## Правила

Quantum только для чтения.

Разрешено:

```text
SELECT
WITH ... SELECT
all_tab_columns / all_tables diagnostics
```

Запрещено:

```text
INSERT / UPDATE / DELETE / MERGE
DROP / ALTER / CREATE / TRUNCATE
EXEC / EXECUTE / FOR UPDATE
COMMIT / ROLLBACK
DBMS_ / UTL_
```

Oracle credentials не хранить в коде. Новый sync читает их из переменных окружения:

```text
QUANTUM_MANAGER_ORACLE_USER
QUANTUM_MANAGER_ORACLE_PASS
QUANTUM_MANAGER_ORACLE_DSN
```

Если manager-specific переменные не заданы, runner использует общие:

```text
ORACLE_USER
ORACLE_PASS
ORACLE_DSN
```

`ORACLE_DSN` по умолчанию: `MAXQPROD`.

## Файлы

```text
sync_manager.php
quantum_manager_query.php
.env.sync_quantum_manager.example
log/
```

## Запуск

```bash
php manager/sync_manager.php
php manager/sync_manager.php --mode=tables
php manager/sync_manager.php --mode=columns
php manager/sync_manager.php --mode=links
php manager/sync_manager.php --mode=wo-preview --wo=W107739
php manager/sync_manager.php --mode=wo-batch --wos=W107731,W107732,W107733
php manager/sync_manager.php --mode=wo-search --ref=RO456216 --pn=42700-13
php manager/sync_manager.php --mode=all --format=both
```

По умолчанию:

```text
mode=tables
format=csv
limit=5000
```

Результаты пишутся в:

```text
manager/log
```

## Что уже известно

Из WO/RO документации подтверждено:

```text
QCTL.WO_OPERATION.WOO_AUTO_KEY
QCTL.WO_OPERATION.SI_NUMBER = WO number
QCTL.WO_OPERATION.WO_DISP = WO status/display
QCTL.WO_OPERATION.COMPANY_REF_NUMBER
QCTL.WO_OPERATION.PNM_AUTO_KEY

QCTL.WO_BOM.WOO_AUTO_KEY -> QCTL.WO_OPERATION.WOO_AUTO_KEY
QCTL.WO_BOM.WOB_AUTO_KEY
QCTL.WO_BOM.PNM_AUTO_KEY
QCTL.WO_BOM.REF

QCTL.PARTS_MASTER.PNM_AUTO_KEY -> part master data
```

## Что нужно найти

1. Какие Quantum таблицы отвечают за estimate и invoice.
2. Есть ли прямые ключи `WOO_AUTO_KEY`, `WOB_AUTO_KEY`, `SI_NUMBER`, `WO_NUMBER`.
3. Если прямых ключей нет, какая цепочка связывает WO с estimate/invoice.
4. Какие поля нужны будущей странице manager:
   - WO number
   - customer
   - unit PN / description
   - serial number
   - estimate number/status/date/amount
   - invoice number/status/date/amount
   - source last modified
5. Какие даты являются авторитетными для incremental sync.

## Текущий первый шаг

Запустить metadata diagnostics:

```bash
php manager/sync_manager.php --mode=all --format=both
```

После этого смотреть CSV/XLS в `manager/log` и переносить подтвержденные факты в этот README.

## Контрольные примеры

### User-provided reference, 2026-06-17

```text
WO: 106254
RO/reference: RO456216
PN: 42700-13
SN: 64353
WO estimate: 15453.00
WO estimate date: 05/mar/2026
Approval date: 06/mar/2026
```

First Quantum check:

```text
WO_OPERATION.SI_NUMBER = W106254 exists.
WOO_AUTO_KEY = 6520
WO_STATUS = COG
COMPANY_REF_NUMBER = RO.1639786
ENTRY_DATE = 17/jul/2024
CLOSE_DATE = 29/jul/2024
BILL_NAME = Jazz Aviation LP
WO_OPERATION estimate total fields = 0
INVC_HEADER by WOO_AUTO_KEY = no rows
WO extractor UNIT_PN = 52195-3
WO extractor SERIAL_NUMBER = empty
```

Current conclusion:

```text
The provided reference values do not match W106254 through the confirmed
WO_OPERATION/PNM_AUTO_KEY path. Need search by RO456216 / PN 42700-13 / SN 64353
instead of assuming WO number alone is enough.
```

Additional checks:

```text
RO_HEADER search by RO456216 = no rows
RO_HEADER search by 456216 = no rows
PARTS_MASTER search by 42700-13 / PN_STRIPPED = no rows
WO_OPERATION search by COMPANY_REF_NUMBER/customer fields containing RO456216 = no rows
WO_OPERATION search by main PN 42700-13 = no rows
WO_OPERATION estimate total 15453.00 = no rows
WO_OPERATION date 05/mar/2026 across ENTRY_DATE/QUOTE_STATUS_DATE/CLOSE_DATE = no rows
WO_OPERATION date 06/mar/2026 across ENTRY_DATE/QUOTE_STATUS_DATE/CLOSE_DATE = 5 rows,
but none match PN 42700-13 or amount 15453.00
```

Likely next search area:

```text
Estimate data is probably not stored in WO_OPERATION header estimate totals for
this example. Search estimate/detail/billing tables next:
CQ_DETAIL
CQ_WO_OPTIONS
CQ_WO_TEMPLATES
KAC_BILLING
BILLING_GROUPS
INVC_HEADER / INVC_DETAIL
```

### Screenshot sample set, 2026-06-17

Visible rows from manager-like report screenshot:

| WO | PN | Description | SN | Customer | Type | Estimate date | Terms | Estimate | Approval date | Approved | Invoice/next date |
|---|---|---|---|---|---|---|---|---:|---|---|---|
| W107731 | 170-70150-403 | Side Stay, MLG | 00235 | AirStart | Overhaul | 29/apr/2026 | Net 30 | 26696.80 | 08/may/2026 | Y | 27/may/2026 |
| W107732 | 170-70150-403 | Side Stay, MLG | 00360 | Jazz | Overhaul | 04/may/2026 | Net 30 | 32343.50 | 07/may/2026 | Y | 22/may/2026 |
| W107733 | 170-70496-003 | Pin | 1424338/009 | Aviatechnik | Overhaul | 04/may/2026 |  |  |  | N |  |
| W107734 | 49300-9 | Side Stay, MLG | SPP510222 | Jazz | Repair | 05/may/2026 | Net 30 | 59753.60 | 09/jun/2026 | N |  |
| W107735 | 52000-31 | NLG | MA1647 | Air Nostrum | Overhaul | 07/may/2026 | Net 30 | 201843.80 | 02/jun/2026 | N |  |
| W107736 | 2801A0000-03 | MLG | 00220 | Regional One | Overhaul | 08/may/2026 | Net 30 | 174020.80 | 28/may/2026 | Y | 15/jun/2026 |
| W107737 | 55000-5 | MLG | SPC012009 | Air Nostrum | Repair | 08/may/2026 | Net 30 | 65420.80 | 29/may/2026 | Y | 17/jun/2026 |

Quantum checks against this screenshot:

```text
WO / PN / customer / estimate date:
Source: QCTL.WO_OPERATION + QCTL.PARTS_MASTER
WO_OPERATION.SI_NUMBER = WO
PARTS_MASTER.PN = PN
WO_OPERATION.BILL_NAME = customer
WO_OPERATION.ENTRY_DATE = estimate date shown in the screenshot

Serial number:
Source: QCTL.VIEW_SPB_WO_MAINCOMPONENT and QCTL.VIEW_SPS_WO_SERIAL_NUMS
VIEW_SPB_WO_MAINCOMPONENT.PART_NUMBER matches screenshot PN
VIEW_SPB_WO_MAINCOMPONENT.SERIAL_NUMBER matches screenshot serial number
VIEW_SPS_WO_SERIAL_NUMS.SERIAL_NUMBER also matches screenshot serial number
```

Confirmed sample results:

| WO | PN source | SN source | Customer source | Estimate date source |
|---|---|---|---|---|
| W107731 | 170-70150-403 | 00235 | AirStart Inc. | 29/apr/2026 |
| W107732 | 170-70150-403 | 00360 | Jazz Aviation LP | 04/may/2026 |
| W107733 | 170-70496-003 | 1424338/009 | Aviatechnik Corporation | 04/may/2026 |
| W107734 | 49300-9 | SPP510222 | Jazz Aviation LP | 05/may/2026 |
| W107735 | 52000-31 | MA1647 | Air Nostrum L.A.M.,S.A. | 07/may/2026 |
| W107736 | 2801A0000-03 Amdt. A | 00220 | Regional One | 08/may/2026 |
| W107737 | 55000-5 | SPC012009 | Air Nostrum L.A.M.,S.A. | 08/may/2026 |

Still not confirmed:

```text
Estimate amount column from the screenshot.
Approval date column from the screenshot.
Final invoice/next-date column from the screenshot.
Y/N approval flag source.
```

Checked and not matching for the screenshot amount/date layer:

```text
WO_OPERATION.EST_* totals = 0 for all 7 sample WOs
WO_OPERATION.EI_TOT_* totals = 0 for all 7 sample WOs
INVC_HEADER by WOO_AUTO_KEY = 0 rows for all 7 sample WOs
BILLING_GROUPS flat price totals = 0 for all 7 sample WOs
CQ_DETAIL through WO_BOM = 0 rows
WO_QUOTE_DETAIL through WOO_REF/WOK_WOO_REF = 0 matched quote detail rows
VIEW_SPR_WO_OPERATION estimate fields = 0
VIEW_WQH_SO_BACK_CHARGE = 0 rows
VIEW_SPS_WOBILLING3 = 0 rows
Exact amount search for 26696.80 across first likely money fields = 0 rows
```

Current likely next search:

```text
The screenshot may be using a custom/reporting layer not exposed by the first
standard quote/invoice tables. Search report views around:
VIEW_WO_BILLING
VIEW_WO_BILLED_BOM
VIEW_WO_BILLED_LABOR
VIEW_WO_BILLED_MISC
VIEW_WO_BILLED_PARTS
VIEW_WOO_BILL_COST
and amount values from the screenshot.
```

### Continued discovery, 2026-06-17

Additional checks that do not contain the screenshot amount/date layer:

```text
KAC_BILLING / KAC_BASE_LABOR = 0 rows
WO_QUOTE_HEADER / WO_QUOTE_DETAIL deep scan = 0 rows
WO_TASK_LABOR raw labor records = 0 rows
RO_DETAIL linked through WO_BOM exists, but price/quote date fields are 0/empty
VIEW_WO_SUMMARY_WITH_TAT gives PN/SN/customer/entry date, but TOTAL_QUOTED_PRICE is empty
WO custom UDF/SDF fields and WO_OPERATION date fields are empty except ENTRY_DATE
```

Confirmed partial amount formula:

```text
QCTL.VIEW_SPR_WO_BOM / QCTL.WO_BOM:
  UNIT_PRICE_EXT_SUM = SUM(UNIT_PRICE * QTY_NEEDED)
  This matches VIEW_WO_UNBILLED_PARTS extended price.

QCTL.WO_OPERATION:
  LABOR_FLAT_PRICE = header flat labor component.

Candidate estimate amount:
  UNIT_PRICE_EXT_SUM + LABOR_FLAT_PRICE
  (+ PARTS_FLAT_PRICE/MISC_FLAT_PRICE if non-zero; current sample has 0)
```

Validated against screenshot amounts:

| WO | BOM parts ext | Labor flat | Candidate amount | Screenshot amount | Diff |
|---|---:|---:|---:|---:|---:|
| W107731 | 10374.00 | 16187.00 | 26561.00 | 26696.80 | 135.80 |
| W107732 | 14708.70 | 16187.00 | 30895.70 | 32343.50 | 1447.80 |
| W107734 | 55803.60 | 3950.00 | 59753.60 | 59753.60 | 0.00 |
| W107735 | 147380.80 | 54463.00 | 201843.80 | 201843.80 | 0.00 |
| W107736 | 101106.80 | 72914.00 | 174020.80 | 174020.80 | 0.00 |
| W107737 | 43620.80 | 19800.00 | 63420.80 | 65420.80 | 2000.00 |

Evidence files:

```text
manager\log\quantum_manager_wo_bom_money_20260617_160150.csv
manager\log\quantum_manager_wo_header_money_20260617_160316.csv
manager\log\quantum_manager_wo_ro_detail_20260617_155906.csv
manager\log\quantum_manager_wo_date_fields_20260617_160657.csv
```

Current conclusion:

```text
The screenshot estimate amount is mostly explained by:
parts extended price from WO_BOM / VIEW_SPR_WO_BOM
+ LABOR_FLAT_PRICE from WO_OPERATION.

This exactly matches 3 of 6 rows with amounts. W107731, W107732, and W107737
still have an extra component not found yet.

Approval date, approved Y/N flag, and final/rightmost date are not in
WO_OPERATION, WO_QUOTE_*, RO_DETAIL, INVC_HEADER, or WO custom fields for
this sample.
```
 
