---
name: avia-import-manual-package
description: Prepare, audit, reconcile, and import an AVIA component-manual package from a directory of numbered scanned IPL/FIG PDFs plus a standard XLS/XLSX process workbook. Use when the user gives a source directory and manual number and expects Parts, Service Bulletins, LLP/Log Card, NDT, CAD, Paint, and PRL/KIT data to be placed in the correct AVIA manual.
---

# AVIA Manual Package Import

Turn a standard manual package into reviewed AVIA import data. The normal user input is only:

- source directory;
- exact AVIA manual identifier, such as `32-21-02` or `32-21-04 Goodrich`.

Read [references/source-and-database-mapping.md](references/source-and-database-mapping.md) before processing a package.

## Non-negotiable safety

- Treat source PDF/XLS/XLSX files as immutable.
- Start with a dry run. Do not change any database until the audit report has zero unresolved structural issues and the user has resolved all true data conflicts.
- Production SSH and production DB access remain read-only under project rules. Apply confirmed data locally and prepare artifacts/instructions for the user's manual production import or deployment.
- Never resolve an IPL/part-number mismatch silently. Show the existing and source values and wait for a decision.
- Use a single DB transaction for an authorized local import. Guard the manual ID/number, current counts, duplicate IPLs, part-number matches, and final counts.
- Preserve existing component IDs when correcting an existing row so TDR/log-card/process references remain valid.
- Existing process flags may be turned on when the source requires them; never turn an existing true flag off merely because the workbook omits it.

## Workflow

### 1. Discover and fingerprint sources

Run `scripts/render_ipl_pdfs.py` with `--manifest-only` first. Require exactly one workbook containing the expected standard tabs and one or more numbered PDFs (`1.pdf`, `2.pdf`, and so on). Record source size and SHA-256 in the run manifest. A workbook may contain several manuals; isolate the requested base manual number and never merge later sections belonging to another manual.

If there are multiple candidate workbooks, missing FIG numbers, duplicate FIG PDFs, or a manual number not found in the workbook, stop and report the ambiguity.

### 2. Convert scanned FIG PDFs to per-FIG CSV

These PDFs are commonly image-only and watermarked. Do not trust `pdftotext` or raw OCR alone.

1. Render all pages at 300 DPI with `scripts/render_ipl_pdfs.py`.
2. Visually inspect at least the first, middle, and last rendered page of every FIG PDF before extraction.
3. Produce `<fig>.csv` beside the run output, one row per detail part, with this exact header:

   `ipl_num,part_number,assy_part_number,name,assy_ipl_num,units_assy`

4. Form `ipl_num` as `<FIG printed in the table>-<ITEM>`, preserving suffix letters and normalizing typographic dashes to ASCII `-`. The printed FIG can include a suffix absent from the filename (`1.pdf` may contain FIG `1A`); the table header wins.
5. Exclude page headers, footers, notes, `ATTACHING PARTS`, applicability/SB qualifier lines, and the leading top-level assembly/configuration variants before the first detail item (normally item 10). Do not discard a real detail item only because its number is below 10; verify against the page layout.
6. Preserve the source part number exactly except for whitespace and dash normalization. Preserve a concise part name; remove indentation dots and supersession/applicability notes.
7. Set blank assembly columns unless the source unambiguously identifies an assembly relationship. Do not infer a relationship from indentation alone.
8. Visually cross-check every OCR-uncertain token, especially `0/O`, `1/I/L`, `5/S`, suffix letters, dashes, and quantities.

Run the package analyzer after each completed FIG. It rejects duplicate IPLs, malformed required fields, and inconsistent FIG prefixes. Do not assemble the final CSV until every per-FIG file passes.

### 3. Analyze the workbook and build import artifacts

Run:

`php .agents/skills/avia-import-manual-package/scripts/analyze_sources.php --source-dir="<directory>" --manual-number="<manual>" --output-dir="<run-output>"`

The analyzer supports `.xls` and `.xlsx` through the project's PhpSpreadsheet dependency. It aggregates numbered CSVs, validates Parts, maps workbook tabs, and writes:

- `parts.csv` — canonical basic Parts import;
- `component_flags.json` — additive flags by IPL;
- `service_bulletins.csv` — SB rows in AVIA import format;
- `analysis.json` and `report.md` — counts, mappings, and unresolved issues.

The mapping must follow the reference exactly: LLP -> `log_card`; every relevant `NDT`/`NDT (n)` -> `ndt_list`; `CAD` and relevant CAD process variants -> `cad_list`; `PAINT (n)`/PAINT -> `paint_list`; the `PRL`/`PRL<n>` sheet whose header contains the requested base manual, only where CODE is `KIT` -> `kit`; SB -> `manual_service_bulletins`.

### 4. Reconcile with the actual target manual

Trace the complete chain: manual number -> target manual row -> current components/SB rows -> source Parts -> workbook mappings -> proposed changes.

Create a read-only target snapshot with `scripts/snapshot_manual.php`. For the local database:

`php .agents/skills/avia-import-manual-package/scripts/snapshot_manual.php --manual-number="<manual>" --output="<run-output>/target.json"`

For production, run the same SELECT-only snapshot logic through the project's read-only SSH workflow without writing a remote file or exposing secrets. Then re-run `scripts/analyze_sources.php` with `--target-json="<run-output>/target.json"`. A source-only flag count is not final because valid workbook rows may refer to existing target components absent from the new CSV.

For each source IPL:

- no existing IPL: propose create;
- existing IPL plus same normalized part number: skip basic row, preserve the existing record, and add only missing true flags;
- existing IPL plus different part number: unresolved conflict;
- workbook-only IPL: unresolved until the user chooses add, remap, or skip;
- existing-only IPL: preserve by default and report it.

For LLP, match both normalized part number and normalized name. A repeated part number or weak/ambiguous name match requires review.

For SB, identify existing records by normalized AC MFG SB number. Do not replace or duplicate a differing existing bulletin silently.

### 5. Ask only about real conflicts

Present a compact numbered table containing source sheet/page/row, IPL, source PN/name, existing PN/name, and suggested safe action. Ask the user only for decisions the sources and target DB cannot determine.

Record confirmed resolutions in the run output. Re-run the analyzer/reconciliation and require zero unresolved conflicts before import.

### 6. Apply locally and verify

Apply an authorized local import transactionally. The ordinary Parts CSV upload is create-only and intentionally ignores process flags, so do not claim completion after uploading `parts.csv` alone. Apply the reviewed flag plan and SB plan separately, with guarded current-value checks.

Verify after commit:

- exact component and SB totals;
- no duplicate IPL within the manual;
- every source IPL resolves to the expected normalized PN;
- every requested flag is true;
- KIT count includes only PRL rows whose CODE is `KIT`;
- existing true flags remain true;
- existing component IDs referenced by TDR/log-card/process data were preserved;
- local UI counts agree with DB quantities.

Prepare the exact user-facing production handoff. Never write production through the read-only SSH account.

## Completion report

State the source directory and manual, source file counts/hashes, Parts/SB/flag counts, creates/skips/corrections, unresolved decisions (must be zero for a completed import), local verification results, and what the user must copy/import on production.

Keep run outputs under `storage/app/codex/manual-package-import/<manual>/<timestamp>/`; do not place generated logs in the skill directory.
