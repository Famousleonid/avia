---
name: avia-import-manual-package
description: Prepare, audit, reconcile, and import an AVIA component-manual package from numbered scanned IPL/FIG PDFs and a standard XLS/XLSX process workbook. Use for Parts, Service Bulletins, LLP/Log Card, NDT, CAD, Paint, PRL/KIT and manual-specific In-Process Check Sheet extraction, SQL handoff and production verification.
---

# AVIA Manual Package Import

Turn a standard manual package into reviewed AVIA import data. The normal user input is only:

- source directory;
- exact AVIA manual identifier, such as `32-21-02` or `32-21-04 Goodrich`.

Read [references/source-and-database-mapping.md](references/source-and-database-mapping.md) before processing a package.
For every XLS/XLSX package, also read [references/in-process-check-sheet.md](references/in-process-check-sheet.md): import the manual-specific `IN PROCESS CHECK SHEET` as a separate mandatory stage.
When the request includes ASSY, alternate P/N, bushing Original/Oversize, nested composition, or production SQL for Part Groups, also read [references/part-groups-and-assemblies.md](references/part-groups-and-assemblies.md).

## Non-negotiable safety

- Treat source PDF/XLS/XLSX files as immutable.
- Start with a dry run. Do not change any database until the audit report has zero unresolved structural issues and the user has resolved all true data conflicts.
- Production SSH and production DB access remain read-only under project rules. Apply confirmed data locally and prepare artifacts/instructions for the user's manual production import or deployment.
- Never resolve an IPL/part-number mismatch silently. Show the existing and source values and wait for a decision.
- Use a single DB transaction for an authorized local import. Guard the manual ID/number, current counts, duplicate IPLs, part-number matches, and final counts.
- Preserve existing component IDs when correcting an existing row so TDR/log-card/process references remain valid.
- Existing process flags may be turned on when the source requires them; never turn an existing true flag off merely because the workbook omits it.

## Workflow

### 0. Select the manual package and handoff directory

The user's standard source root is `C:\Airplane`. Each manual has its own subdirectory, normally named with the manual number, main ASSY P/N, and aircraft description. Expect numbered FIG PDFs and a process XLS/XLSX workbook inside it. Treat the folder name as a discovery hint; confirm the exact manual and production ID against the documents and production data. Ask only when that match is ambiguous. A workflow/setup request does not authorize bulk processing every folder.

Keep the source documents unchanged. Store intermediate extracts, manifests, snapshots, and audit outputs in the workspace run directory. Save the final versioned production import SQL and a separate SELECT-only verification SQL in the corresponding source manual directory, not in another manual's folder. Use names such as `<manual>_<YYYYMMDD>_v01_import.sql` and `<manual>_<YYYYMMDD>_v01_verify.sql`; do not silently overwrite a previous handoff. Respect filesystem approval requirements when that directory is outside the writable workspace.

### 1. Discover and fingerprint sources

Run `scripts/render_ipl_pdfs.py` with `--manifest-only` first. For a complete manual package, require exactly one workbook containing the expected standard tabs and one or more numbered PDFs (`1.pdf`, `2.pdf`, and so on). For an explicitly Parts/ASSY-only PDF package, add `--allow-no-workbook`; process flags and SB then remain unchanged. Record source size and SHA-256 in the run manifest. A workbook may contain several manuals; isolate the requested base manual number and never merge later sections belonging to another manual.

If there are multiple candidate workbooks, missing FIG numbers, duplicate FIG PDFs, or a manual number not found in the workbook, stop and report the ambiguity.

### 2. Convert scanned FIG PDFs to per-FIG CSV

These PDFs are commonly image-only and watermarked. Do not trust `pdftotext` or raw OCR alone.

1. Render all pages at 300 DPI with `scripts/render_ipl_pdfs.py`.
2. Visually inspect at least the first, middle, and last rendered page of every FIG PDF before extraction. Also compare repeated printed page numbers: when the same page is scanned more than once and one copy is crossed out, exclude the crossed-out copy and use the uncrossed copy.
3. Produce `<fig>.csv` beside the run output, one row per detail part, with this exact header:

   `ipl_num,part_number,assy_part_number,name,assy_ipl_num,units_assy`

4. Form `ipl_num` as `<FIG printed in the table>-<ITEM>`, preserving suffix letters and normalizing typographic dashes to ASCII `-`. The printed FIG can include a suffix absent from the filename (`1.pdf` may contain FIG `1A`); the table header wins.
5. Exclude page headers, footers, notes, `ATTACHING PARTS`, applicability/SB qualifier lines, and non-orderable title/configuration lines. Keep an explicit assembly row when it has its own IPL item and orderable P/N and is needed as a Part Group option, even when it precedes item 10. Do not decide from the item number alone; verify against the page layout.
6. Preserve the source part number exactly except for whitespace and dash normalization. Preserve a concise part name; remove indentation dots and supersession/applicability notes.
7. Set blank assembly columns unless the source unambiguously identifies an assembly relationship. Do not infer a relationship from indentation alone.
8. Visually cross-check every OCR-uncertain token, especially `0/O`, `1/I/L`, `5/S`, suffix letters, dashes, and quantities.
9. Always import the IPL quantity into `components.units_assy`: retain a numeric quantity; use `1` for a blank or letter-coded value (including `AR`/`RF`). Follow the quantity normalization section in the source mapping reference. Include quantity in both SQL inserts and the reviewed update plan for existing matched Parts, and verify it after import.

Run the package analyzer after each completed FIG. It rejects duplicate IPLs, malformed required fields, and inconsistent FIG prefixes. Do not assemble the final CSV until every per-FIG file passes.

### 3. Analyze the workbook and build import artifacts

Run:

`php .agents/skills/avia-import-manual-package/scripts/analyze_sources.php --source-dir="<directory>" --manual-number="<manual>" --output-dir="<run-output>"`

The analyzer supports `.xls` and `.xlsx` through the project's PhpSpreadsheet dependency. It aggregates numbered CSVs, validates Parts, maps workbook tabs, and writes:

- `parts.csv` — canonical basic Parts import;
- `component_flags.json` — additive flags by IPL;
- `in_process_check_sheet.json` — manual-specific tasks/stages/references and empty stamp slots, with source evidence (not a shared default checklist);
- `service_bulletins.csv` — SB rows in AVIA import format;
- `analysis.json` and `report.md` — counts, mappings, and unresolved issues.

The mapping must follow the reference exactly: LLP -> `log_card`; every relevant `NDT`/`NDT (n)` -> `ndt_list`; `CAD` and relevant CAD process variants -> `cad_list`; `PAINT (n)`/PAINT -> `paint_list`; the `PRL`/`PRL<n>` sheet whose header contains the requested base manual, only where CODE is `KIT` -> `kit`; SB -> `manual_service_bulletins`.

For PRL/KIT, follow the explicit-cell rule in the source mapping reference: a blank CODE (including trailing PRL rows) never authorizes a new KIT flag. Do not invent or carry down KIT codes in extracted data or generated SQL.

### 4. Reconcile with the actual target manual

Trace the complete chain: manual number -> target manual row -> current components/SB rows -> source Parts -> workbook mappings -> proposed changes.

Create a read-only target snapshot with `scripts/snapshot_manual.php`. For the local database:

`php .agents/skills/avia-import-manual-package/scripts/snapshot_manual.php --manual-number="<manual>" --output="<run-output>/target.json"`

For production, run the same SELECT-only snapshot logic through the project's read-only SSH workflow without writing a remote file or exposing secrets. Then re-run `scripts/analyze_sources.php` with `--target-json="<run-output>/target.json"`. A source-only flag count is not final because valid workbook rows may refer to existing target components absent from the new CSV.

For each source IPL:

- no existing IPL: propose create;
- existing IPL plus same normalized part number: preserve the existing record/ID, reconcile `units_assy` to the normalized IPL quantity in the reviewed SQL plan, and add only missing true flags; leave unrelated basic fields unchanged;
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
- the In-Process Check Sheet template matches the reviewed workbook tasks/stages/references and uses current WO identity, without importing sample stamps or results;
- existing true flags remain true;
- existing component IDs referenced by TDR/log-card/process data were preserved;
- local UI counts agree with DB quantities.

Prepare the exact user-facing production handoff. Never write production through the read-only SSH account.

### 7. Verify after the user's production import

The user runs the production SQL. After they report the import, inspect the current production database using only read-only queries. Compare against the reviewed package, not merely against aggregate counts: expected IPL/P/N identities, names and quantities, preserved IDs, flags, SB identities, group types/options, direct and nested composition, and required quantities. Report missing or differing records per FIG; prepare a separate reviewed corrective SQL if needed, never execute it on production. Update the persistent ledger with the verification date, results, and remaining work. Only mark completion when all required stages pass.

## Completion report

State the source directory and manual, source file counts/hashes, Parts/SB/flag counts, creates/skips/corrections, unresolved decisions (must be zero for a completed import), local verification results, and what the user must copy/import on production.

Keep run outputs under `storage/app/codex/manual-package-import/<manual>/<timestamp>/`; do not place generated logs in the skill directory.

The final user-facing SQL handoff belongs in the source manual folder under `C:\Airplane`, as specified above. Include links to the import and verification files. Present progress as a per-manual/per-FIG table with Parts, groups/ASSY, workbook flags/SB, production verification, and a final completion column. Use `✓` only for evidenced completion; distinguish missing work from unknown status. Incorporate newly confirmed reusable rules into this skill or its references; keep package-specific decisions in the run artifacts and ledger.

## Persistent import ledger

Read [references/processed-manuals.md](references/processed-manuals.md) before deciding that a manual or FIG was already processed. Update it after every material stage: PDF Parts extraction/reconciliation, group/ASSY construction, workbook mapping, production handoff, and post-import production verification.

For each manual preserve the exact manual number/ID, source and run artifact paths, stage status, per-FIG Parts/group/process counts, SB totals, In-Process Check Sheet task/slot counts and source/content hashes, verification date, and discrepancies. For new package audits use `complete` only when PDF → Parts → required groups → workbook flags/SB and In-Process Check Sheet → production verification has passed (or the user explicitly approved a stage as N/A). Preserve historical completion evidence; report the newly added check-sheet stage separately until verified. Never infer completion from production row counts alone.
