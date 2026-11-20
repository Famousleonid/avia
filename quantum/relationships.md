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

## Stock reservations to WO — verified 18/Nov/2026

`STOCK_RESERVATIONS.STM_AUTO_KEY -> STOCK.STM_AUTO_KEY -> STOCK.PNM_AUTO_KEY` identifies the stocked part. Positive `STOCK_RESERVATIONS.QTY_RESERVED` rows can link to WO_OPERATION through direct `WOO_AUTO_KEY`, or `WOB_AUTO_KEY -> WO_BOM.WOO_AUTO_KEY`. WO number is `WO_OPERATION.SI_NUMBER`. Other reservation targets exist (SOD, ROD, etc.); missing a direct WO must not be labelled as an error or assigned a guessed WO. Additional indirect mappings require verification.

Example P/N49101-13: PARTS_MASTER.QTY_RESERVED=22. Verified direct/BOM allocation: W107938=3, W107940=3, W108095=1, W108096=1, W108180=1; another13 have no WO via these two joins. Across positive reservation rows:15195 rows, total31639;30104 quantity links to WO via these joins;1170 rows do not. This is a newer snapshot than the earlier morning inventory; do not compare totals across timestamps as discrepancies.

For a future Reserved drilldown, export reservation links in the same refresh snapshot as quantities; show distinct WO numbers and optionally allocated quantity, explicitly preserving any unmatched portion. A click should read saved AVIA data, not trigger another Quantum export. Drilldown is not implemented yet.

### Reserved drilldown implemented — 18/Nov/2026

Supersedes the earlier incomplete direct/BOM mapping: also follow STOCK_RESERVATIONS.SOD_AUTO_KEY → SO_DETAIL.WOO_AUTO_KEY (or SO_DETAIL.WOB_AUTO_KEY → WO_BOM), and STOCK_RESERVATIONS.ROD_AUTO_KEY → RO_DETAIL.WOB_AUTO_KEY → WO_BOM.WOO_AUTO_KEY. Always use the stocked part's STOCK.PNM_AUTO_KEY; never infer a WO by P/N alone. Aggregate each reservation once with direct/BOM, then SO, then RO precedence.

The remaining13 for49101-13 are fully accounted for:
- Sales orders S6870→W106506, S6930→W107319, S7496→W107741, S7501→W107809, S7571→W107986, S7579→W107996, S7617→W108029, S7622→W107987, S7626→W108050:1 each (9 total).
- RO R9631: ROD34360/34361 → W108095:2; ROD34362/34363 → W108096:2 (4 total).
- Final grouped distribution is14 WOs,22 units,0 unassigned. W108095 and W108096 now total3 each including their direct reservation.

stock_query.sql reads PARTS_MASTER quantities and all positive reservation allocations in ONE SELECT for statement-level consistency. Snapshot v2 contains per-part reservations[{wo,quantity}], unassigned_reserved. Unknown targets stay explicitly unassigned. Full export contains22372 parts;6 parts have a difference between positive allocation sums and PARTS_MASTER reserved balance, surfaced as a signed difference in the dialog instead of forced reconciliation. Cause of those6 not investigated in this task.

Reserved click reads only the embedded saved snapshot; no Quantum/API refresh. v1 snapshots explicitly ask for refresh to load links. New import requires v2 allocation fields, positive quantities and unique WO per part, preserving old snapshot on invalid data. Both themes checked; modal centered with margin:auto because global CSS resets native dialog margins. Seven tests/36 assertions pass. Exporter/query copied to C:\avia\Stock; site changes remain local pending /app and /resources copy. Existing production tunnel/service activation prerequisite remains unchanged. sync_quantum untouched.
