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

- A FIG PDF may contain duplicate scans of the same printed page. Identify duplicates from the printed page number and table content, not merely from the PDF page index. If exactly one duplicate is visibly crossed out, exclude that entire crossed-out copy and extract only the uncrossed copy. Record the ignored PDF page in the audit. If both copies are uncrossed, both are crossed out, or their active content differs, treat the duplicate as unresolved and ask the user which version is authoritative.
- `ipl_num = <FIG>-<ITEM>`; keep suffix letters (`1-30A`, `10-91A`).
- Normalize Unicode dash/minus characters to ASCII `-`.
- Required fields are `ipl_num`, `part_number`, and `name`.
- Always populate `units_assy` using the quantity normalization rule below, not the raw letter code.
- Ignore column headings, page metadata, applicability text, SB pre/post notes, supersession notes, `ATTACHING PARTS`, and `ITEM NOT ILLUSTRATED` markers.
- The leading rows in each FIG commonly describe the complete assembly/configuration variants and are not detail Parts. In the known template the detail list normally starts at item 10. Verify visually rather than applying a numeric-only rule.
- Watermarks cross cells and make OCR substitutions likely. All uncertain PN/item/quantity tokens need visual confirmation.

## IPL quantity normalization (confirmed 17/Sep/2026)

- Read the quantity for each IPL row. Retain numeric quantities (for example `2` → `2`, `12` → `12`); do not default all rows to one.
- A blank value or a value containing letters, including `AR`, `RF`, `REF`, becomes `1` in imported Parts and generated SQL. This supersedes the former instruction to store `AR`/`RF` literally.
- Keep the original token in the extraction/audit evidence. Visually resolve uncertain OCR before normalization; punctuation-only or ambiguous numeric expressions are review issues, not guessed quantities.
- `scripts/ipl_quantity.php` implements normalization for the analyzer. Decimal comma is normalized to a dot. The same rule applies to source quantities used for direct/nested ASSY members, without confusing per-parent quantities with workorder totals.
- Include `units_assy` in new-row SQL and in the reviewed quantity-update plan for matching existing IPL/P/N rows, preserving component IDs. Verify expected quantities in the post-import SELECT audit. This rule does not authorize a retroactive bulk change or rewriting previous handoff SQL.

## Workbook mapping

Sheet matching is case-insensitive after trimming whitespace.

| Workbook source | Match rule | AVIA target |
|---|---|---|
| `LLP` | normalized PN plus compatible part name | `components.log_card = true` |
| `NDT`, `NDT (n)`, `NDT BUSHINGS` | IPL, then confirm PN | `components.ndt_list = true` |
| `CAD`, `CAD BUSHINGS`/legacy typo `CAD BUHSINGS`, `CAD AIRCO`, and relevant numbered CAD variants | IPL, then confirm PN | `components.cad_list = true` |
| `PAINT (2)` or `PAINT` | IPL, then confirm PN | `components.paint_list = true` |
| `PRL`, `PRL<n>` containing the requested base manual | FIG + ITEM, confirm PN, and CODE exactly `KIT` | `components.kit = true` |
| `SB` | one bulletin row | `manual_service_bulletins` |
| `IN PROCESS CHECK SHEET` | reviewed manual-specific tasks, stages, references and blank stamp slots | `manual_in_process_check_sheets` — see [in-process-check-sheet.md](in-process-check-sheet.md) |

Do not set KIT for PRL `RECOMMENDED` rows without CODE `KIT`. Do not use PRL section membership alone.

### PRL KIT explicit-cell rule (confirmed 21/Sep/2026)

- Set a new `components.kit = true` only when that PRL row's actual CODE cell explicitly contains `KIT` (trim whitespace and compare case-insensitively).
- A blank CODE, including the commonly blank cells at the end of PRL, means no KIT assignment from this row. Any other code also gives no KIT assignment. Never carry `KIT` down from a previous row; only FIG has the carry-down behavior described below.
- Never invent `KIT` in intermediate extracts, remapping decisions or generated SQL based on a part's name, position in PRL, a neighboring row, customary replacement practice, or membership in an ASSY/KIT group. An approved IPL/P/N remap does not change the source CODE.
- Retain the actual CODE cell/sheet/row as audit evidence for every newly enabled KIT flag. New Parts with blank CODE remain unchecked unless another correctly matched source PRL row explicitly supplies `KIT` for that same part.
- Existing true flags remain subject to the preserve-existing rule: report any existing KIT mark not supported by the current PRL separately, not as a new source-supported mark. Clearing old flags requires a scoped reconciliation decision; this rule update alone does not authorize retroactive database or handoff-SQL changes.

The standard NDT/CAD/Paint rows use IPL in column A, PN in column C, and description in column G (older variants may use I). Cells may contain newline-separated IPLs/PNs. One declared base IPL with several P/N values is valid when the P/N values uniquely resolve to letter-suffixed alternatives of that base item. Otherwise counts must be either one PN shared by all IPLs or one PN per IPL; report any remaining structural conflict. A blank FIG carries down from the most recent explicit FIG. If a secondary process sheet starts with bare item numbers, resolve them only when item + PN uniquely identify a component. Do not treat an internal subcomponent sheet such as `NDT (Shimmy)` as the requested manual's Parts mapping unless it explicitly identifies the requested manual and its rows resolve to that manual's IPL components.

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

- Same IPL + same normalized PN: preserve the existing ID and unrelated basic fields; reconcile quantity against the normalized IPL quantity in the reviewed SQL plan.
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
