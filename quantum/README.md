# Quantum Knowledge Base

Single place for confirmed Quantum Oracle knowledge for the `avia` project.

Before new Quantum work, read this folder first. After a finding is confirmed on real Quantum data, update or correct these notes. Keep one-time CSV/XLS exports in `quantum/log/`.

## Sections

| File | Contents |
|---|---|
| `connection-and-safety.md` | Oracle connection, environment variables, read-only rules |
| `tables-and-fields.md` | Confirmed Quantum tables and fields |
| `relationships.md` | Confirmed links between RO, WO, BOM, PARTS_MASTER |
| `ro-sync.md` | RO bridge, staging, qty/date/process mapping |
| `wo-manager.md` | WO extraction, manager page findings, estimate/invoice research |
| `estimate-money-reconciliation.md` | Current WO estimate money formula and mismatch analysis |
| `open-questions.md` | Unknowns and partial findings |
| `log/` | CSV/XLS exports and one-time diagnostic outputs |

## Main Confirmed Facts

```text
Oracle service alias: MAXQPROD
Oracle schema: QCTL
Current bridge user: CRYSTAL
Quantum access: read-only only
```

Working RO -> WO chain:

```text
RO_HEADER.ROH_AUTO_KEY
  -> RO_DETAIL.ROH_AUTO_KEY

RO_DETAIL.WOB_AUTO_KEY
  -> WO_BOM.WOB_AUTO_KEY

WO_BOM.WOO_AUTO_KEY
  -> WO_OPERATION.WOO_AUTO_KEY

WO_OPERATION.SI_NUMBER
  -> WO number
```

Working WO identity chain:

```text
WO_OPERATION.WOO_AUTO_KEY
WO_OPERATION.SI_NUMBER = WO number
WO_OPERATION.WO_DISP = WO status/display
WO_OPERATION.BILL_NAME = customer name candidate
WO_OPERATION.ENTRY_DATE = estimate/open date in current manager screenshot sample
WO_OPERATION.PNM_AUTO_KEY -> PARTS_MASTER.PNM_AUTO_KEY
```

Confirmed PN/SN sources for manager screenshot sample:

```text
PARTS_MASTER.PN
VIEW_SPB_WO_MAINCOMPONENT.SERIAL_NUMBER
VIEW_SPS_WO_SERIAL_NUMS.SERIAL_NUMBER
```

Current confirmed WO estimate formula for current Quantum data:

```text
VIEW_WO_UNBILLED_PARTS.EXTENDED_PRICE
+ WO_OPERATION.LABOR_FLAT_PRICE
+ WO_OPERATION.PARTS_FLAT_PRICE
+ WO_OPERATION.MISC_FLAT_PRICE
```

Equivalent parts source:

```text
SUM(WO_BOM.UNIT_PRICE * WO_BOM.QTY_NEEDED)
```

This formula exactly matches `W107734`, `W107735`, `W107736`.

Open mismatches versus the provided screenshot:

```text
W107731 +135.80
W107732 +1447.80
W107737 +2000.00
```

See:

```text
quantum/estimate-money-reconciliation.md
quantum/log/wo_estimate_money_reconciliation_w107731_w107737.csv
quantum/log/wo_money_wide_with_audit_w107731_w107737.csv
quantum/log/wo_money_matrix_w107731_w107737.csv
quantum/log/wo_report_lines_w107731_w107737.csv
quantum/log/wo_spb_quotes_proc_w107731_w107737.csv
quantum/log/wo_audit_money_w107731_w107737.csv
quantum/log/wo_change_audit_w107731_w107737.csv
```

Latest estimate reconciliation check:

```text
VIEW_WO_UNBILLED_PARTS already includes OSV + Part.
VIEW_WO_UNBILLED_MISC is the OSV subset and must not be added on top.
SPB_WO_QUOTES is callable as QCTL.QC_WO_PKG REF CURSOR function, but returned 0 rows for the screenshot sample.
Accessible audit/change logs did not expose the missing 135.80 / 1447.80 / 2000.00 components.
```

## Work Rule

Add new information here only after checking it on real Quantum data.

If something is a hypothesis, write it in `open-questions.md`, not as a confirmed fact.

Do not put exports in the root of `quantum/`. Use `quantum/log/` for CSV/XLS/log outputs so reference docs and one-time diagnostics stay separated.
