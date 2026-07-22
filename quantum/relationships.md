# Quantum Relationships

## RO -> WO

Подтвержденная связь:

```text
QCTL.RO_HEADER.ROH_AUTO_KEY
  -> QCTL.RO_DETAIL.ROH_AUTO_KEY

QCTL.RO_DETAIL.WOB_AUTO_KEY
  -> QCTL.WO_BOM.WOB_AUTO_KEY

QCTL.WO_BOM.WOO_AUTO_KEY
  -> QCTL.WO_OPERATION.WOO_AUTO_KEY

QCTL.WO_OPERATION.SI_NUMBER
  -> WO number
```

SQL skeleton:

```sql
SELECT
    rh.RO_NUMBER,
    rh.VENDOR_NAME,
    wo.SI_NUMBER AS WO_NUMBER,
    rh.OUT_DATE AS DATE_SENT,
    rd.LAST_DELIVERY_DATE AS DATE_RETURNED
FROM QCTL.RO_HEADER rh
JOIN QCTL.RO_DETAIL rd
    ON rd.ROH_AUTO_KEY = rh.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
```

Важно:

```text
Один RO может содержать несколько RO_DETAIL rows и несколько WO.
Не считать RO_HEADER единственным source row для sync.
Для staging естественный уровень: RO_DETAIL.
```

Пример:

```text
R8907
  -> W107577
  -> W107477
  -> W107736
  -> W107738
```

## WO -> Parts Master

Подтверждено:

```text
WO_OPERATION.PNM_AUTO_KEY -> PARTS_MASTER.PNM_AUTO_KEY
WO_BOM.PNM_AUTO_KEY       -> PARTS_MASTER.PNM_AUTO_KEY
RO_DETAIL.PNM_AUTO_KEY    -> PARTS_MASTER.PNM_AUTO_KEY
```

`PARTS_MASTER` полезные поля:

```text
PNM_AUTO_KEY
PN
DESCRIPTION
PN_STRIPPED
PN_UPPER
DESCRIPTION_UPPER
```

## WO -> Serial

Для manager screenshot sample serial number подтвержден через:

```text
QCTL.VIEW_SPB_WO_MAINCOMPONENT.SERIAL_NUMBER
QCTL.VIEW_SPS_WO_SERIAL_NUMS.SERIAL_NUMBER
```

`WO_OPERATION` сам по себе не всегда дает serial.

For RO detail-part routing, the confirmed serialized BOM link is:

```text
RO_DETAIL.WOB_AUTO_KEY
  -> VIEW_RPT_KIT_MATERIAL.WOB_AUTO_KEY
  -> VIEW_RPT_KIT_MATERIAL.SERIAL_NUMBER
```

Use `RO_DETAIL.SERIAL_NUMBER` first when populated. Otherwise use the kit
material serial only when the WOB has exactly one distinct non-empty serial.
Do not guess when a WOB exposes multiple serials.

## WO -> BOM Price Component

Подтвержденная часть WO estimate amount:

```text
QCTL.WO_BOM.WOO_AUTO_KEY -> QCTL.WO_OPERATION.WOO_AUTO_KEY
QCTL.WO_BOM.UNIT_PRICE
QCTL.WO_BOM.QTY_NEEDED
```

Эквивалентно в view:

```text
QCTL.VIEW_SPR_WO_BOM.UNIT_PRICE
QCTL.VIEW_SPR_WO_BOM.QTY_NEEDED
```

Формула:

```text
SUM(UNIT_PRICE * QTY_NEEDED)
```

Эта сумма совпадает с `VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE`.
