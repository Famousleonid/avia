# IN PROCESS CHECK SHEET — required workbook stage

Confirmed 17/Sep/2026. Each manual's workbook can contain different tasks. This is a separate WO printable form, **not** WO Process Sheet, TDR, process flags or a universal checklist. Its paper icon precedes TDR Form. The current implementation is a print template, not electronic completion/signoff.

## Extract and review

- Locate exactly one `IN PROCESS CHECK SHEET` tab (case/space/hyphen/underscore insensitive). A missing or ambiguous tab is a reported incomplete stage; do not silently manufacture standard tasks.
- `App\Services\InProcessCheckSheetImporter` reads XLS/XLSX using PhpSpreadsheet without modifying the workbook. The normal analyzer writes `in_process_check_sheet.json` and incorporates parser issues in the package audit.
- Preserve every task, paragraph, stage number, reference, stamp label and intentional empty task slot. Never apply another manual's wording. Conditional text (e.g. JAZZ-only lubricant instructions) remains conditional; do not infer applicability or mark the operation complete.
- The reviewed layout uses a stage label in column A, task text in C, stage number two rows below, reference five rows below (value F), and stamp labels in M. Find each stage label rather than assuming only five blocks. Preserve eight legend entries in order. Changed/unmapped populated cells, missing stages, formula task/reference values and sample completions require review; extend the parser only after inspecting the new source.
- Runtime fields `K3` (WO) and `E5` (master P/N), including cached cross-sheet formulas, must never become template identity. The web form reads the current WO number/P/N and AVIA manual/description/manufacturer/library metadata. Retain source header metadata in JSON for evidence, not as an alternative manual binding. Confirm the exact manual ID/number and workbook identity from production; matching only a base manual number is insufficient (e.g. Goodrich vs Liebherr).
- Never import a sample technician stamp, signature, date or completed result. No default completed flags are created. Render blanks for handwriting as in the workbook.

## SQL handoff

After review, generate standalone or package-integrated template SQL:

```text
php .agents/skills/avia-import-manual-package/scripts/export_in_process_check_sheet.php --workbook="<xls/xlsx>" --manual-id=<verified ID> --manual-number="<exact number>" --output-prefix="<run>/<manual>_<date>_v01_check_sheet"
```

Outputs: `_source.json` (evidence), `_import.sql` and SELECT-only `_verify.sql`. Existing files are never overwritten. Store final SQL in the manual's source folder; retain JSON/hash/run metadata in the workspace.

The new table is `manual_in_process_check_sheets`, one reviewed template per `manual_id`, with schema version, source filename/sheet/SHA256, content SHA256 and JSON content. Deploy migration `2026_09_17_140000_create_manual_in_process_check_sheets_table.php` (or the reviewed equivalent schema SQL) before template imports. The user performs production schema/import/deployment; SSH stays read-only.

The SQL guards exact active manual ID/number and existing content, preserves IDs, and is idempotent. By default an existing different template blocks replacement. For a reviewed update supply `--expected-hash=<current production content_sha256>` after comparing the existing source/content; do not force overwrite or delete/recreate. The generator has no DB connection. Do not use an empty/unknown cached source formula as an instruction.

## Verify and record

- Check exact source JSON/tasks/stages/references/blank slots and SHA256 against the stored row, not only task counts. Verify the original WO → manual → template → print path, a different manual and a manual with no template. GET does not save/create anything.
- The form uses the current manual template on each opening. It does not mutate saved WO/TDR/Log Card data or represent an archived signed sheet. Electronic signoff and immutable signed WO copies require a separate explicit implementation request.
- Include `In-Process Check Sheet` as its own stage in the ledger/handoff, with task/slot counts, source hash, SQL and postflight date. Future complete package imports must include this stage (or an explicit user-approved N/A); missing/unreviewed/unverified is not complete.
- Historical `complete` labels describe the earlier PDF/groups/flags/SB audit. Do not falsely claim their newly required check-sheet stage was verified. Show it separately as pending until read-only production verification passes; retain prior audit dates and evidence.
