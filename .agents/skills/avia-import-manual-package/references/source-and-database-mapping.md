# AVIA manual package source and database mapping

## Required inputs

- A source directory.
- One exact AVIA manual identifier beginning with `NN-NN-NN`; it may include a variant/manufacturer suffix such as `32-21-04 Goodrich`.
- Numbered scanned IPL PDFs: `1.pdf`, `2.pdf`, .... Use the FIG printed in the table; the numeric filename is only the package ordering key and may omit a suffix such as `A`.
- One standard process workbook in `.xls` or `.xlsx` format.

Generated per-FIG CSVs use:

```text
ipl_num,part_number,assy_part_number,name,assy_ipl_num,units_assy
```

The CSV/PDF result is canonical for the basic component identity. The workbook supplies process membership and service bulletins; it does not silently replace a conflicting Parts PN.

## PDF/IPL interpretation

- `ipl_num = <FIG>-<ITEM>`; keep suffix letters (`1-30A`, `10-91A`).
- Normalize Unicode dash/minus characters to ASCII `-`.
- Required fields are `ipl_num`, `part_number`, and `name`.
- `units_assy` may be numeric or source codes such as `AR`/`RF`; retain the source value.
- Ignore column headings, page metadata, applicability text, SB pre/post notes, supersession notes, `ATTACHING PARTS`, and `ITEM NOT ILLUSTRATED` markers.
- The leading rows in each FIG commonly describe the complete assembly/configuration variants and are not detail Parts. In the known template the detail list normally starts at item 10. Verify visually rather than applying a numeric-only rule.
- Watermarks cross cells and make OCR substitutions likely. All uncertain PN/item/quantity tokens need visual confirmation.

## Workbook mapping

Sheet matching is case-insensitive after trimming whitespace.

| Workbook source | Match rule | AVIA target |
|---|---|---|
| `LLP` | normalized PN plus compatible part name | `components.log_card = true` |
| `NDT`, `NDT (n)` | IPL, then confirm PN | `components.ndt_list = true` |
| `CAD`, relevant CAD process variants | IPL, then confirm PN | `components.cad_list = true` |
| `PAINT (2)` or `PAINT` | IPL, then confirm PN | `components.paint_list = true` |
| `PRL`, `PRL<n>` containing the requested base manual | FIG + ITEM, confirm PN, and CODE exactly `KIT` | `components.kit = true` |
| `SB` | one bulletin row | `manual_service_bulletins` |

Do not set KIT for PRL `RECOMMENDED` rows without CODE `KIT`. Do not use PRL section membership alone.

The standard NDT/CAD/Paint rows use IPL in column A, PN in column C, and description in column G (older variants may use I). Cells may contain newline-separated IPLs/PNs. Counts must be either one PN shared by all IPLs or one PN per IPL; otherwise report a structural conflict. A blank FIG carries down from the most recent explicit FIG. If a secondary process sheet starts with bare item numbers, resolve them only when item + PN uniquely identify a component.

The standard PRL rows use FIG in A, ITEM in B, description in C, PN in D, quantity in E, and CODE in F. FIG carries down through blank cells. Pair newline-separated values by position. Ignore `ALT` helper rows as standalone IPLs. If counts cannot be paired unambiguously, report a conflict.

The standard SB sheet uses columns:

1. Year Introduced
2. AC MFG Service Bulletin No.
3. OEM Service Bulletin No.
4. AWD No.
5. Identification Method
6. Description
7-9. reserved/template columns
10. Optional marker
11. Recommended marker
12. Mandatory marker

Requirement priority is Mandatory, Recommended, then Optional when more than one marker is present.

## Matching and conflict rules

Normalize PN only for comparison: uppercase, collapse/remove whitespace, normalize dashes. Keep the original source PN for storage.

Normalize names only for matching: uppercase, collapse punctuation/space, and expand obvious template abbreviations (`ASSY` -> `ASSEMBLY`, `LWR` -> `LOWER`, `UPR` -> `UPPER`). Do not rewrite stored names based on fuzzy matching.

- Same IPL + same normalized PN: existing row wins; do not overwrite basic fields.
- Same IPL + different normalized PN: user decision required.
- Same PN at another IPL: show as a move candidate, never move automatically.
- Workbook PN differs from canonical CSV PN: report both and use neither silently.
- Workbook-only IPL: user decision required (add, remap, or skip).
- Existing-only IPL: preserve unless the user explicitly approves a correction/removal.
- If correcting an existing IPL/PN, update the existing record rather than delete/recreate it.
- Never clear a true process flag because a new workbook omits it.

## Production boundary

The saved production SSH identity is diagnostic/read-only. Allowed production comparison operations are read-only `SELECT`/metadata inspection. Do not execute imports, DB writes, Artisan mutations, file uploads, or deployment through SSH. Produce reviewed local results and handoff artifacts for the user.

Use `scripts/snapshot_manual.php` to serialize only the target manual's component and SB fields needed for reconciliation. Feed that snapshot to `scripts/analyze_sources.php --target-json=...`. Never copy `.env`, connection credentials, tokens, or unrelated rows into the snapshot.
