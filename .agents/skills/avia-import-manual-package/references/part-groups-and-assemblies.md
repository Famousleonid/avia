# AVIA Part Groups and assembly composition

Read this reference when a manual-package task includes new Part Groups, assembly hierarchy, alternate P/N, Original/Oversize bushings, or SQL that creates these relationships.

## Component rows

- The PDF/IPL is canonical for IPL, P/N, name, quantity, indentation, and assembly membership.
- Keep every real orderable IPL row as a `components` row, including an ASSY row used as the selectable assembly option. Exclude only headings and configuration/title lines that are not orderable rows.
- Preserve existing component IDs. Restore a matching soft-deleted row instead of creating a replacement ID. Never silently replace a conflicting P/N.
- Import the numeric IPL quantity; a blank or letter-coded quantity (including `AR`/`RF`) becomes `1`, following `source-and-database-mapping.md`. Preserve the printed token in audit evidence. Do not infer quantity from how many times a part is referenced by parent assemblies.

## Reading the printed assembly hierarchy

- Use the PDF's printed indentation dots as hierarchy levels: a two-dot row belongs under the nearest preceding valid one-dot parent assembly; a one-dot row belongs under the appropriate enclosing assembly. Apply the same rule recursively for deeper levels.
- When indentation returns to a shallower level, close the previous deeper branch. Continue across page boundaries only when the figure/table continuation supports it.
- Establish the parent using the actual ASSY row, orderable P/N, table layout, and applicability/notes, not dots or OCR whitespace alone. Do not promote a plain detail into an ASSY merely because another row appears more indented.
- Keep alternate configurations and applicability branches distinct. Ambiguous dots, missing parents, or conflicting quantities require visual verification and, if still unresolved, a question to the user.
- Build lower-level ASSY and bushing/alternative families first, then nest them in the higher-level ASSY. Include every applicable composition item without duplicating nested children as direct parent members.

## Group types

### Alternative P/N

Use `alternative_pn` when several IPL/P/N rows are approved variants of the same functional detail, including letter-suffix or adjacent IPL items. Each variant is one option. Selecting one option covers the other variants for the configured forms.

Do not use an Alternative P/N group to represent a physical assembly composition or an Original/Oversize bushing family.

### Bushing Original/Oversize

Use one `oversize` group for the original bushing and every oversize P/N of that physical detail.

- Set `is_bush=1` on every family member.
- Set `bush_ipl_num` on every member to the original bushing IPL; the original points to itself.
- Mark the original option as `original` and the remaining options as `oversize`.
- The family quantity comes from the original IPL row.
- Mixed selection is valid: for a required quantity of two, one original and one oversize may be ordered. The sum of all selected sizes must not exceed the required family quantity.

When an ASSY contains this bushing family, nest the complete bushing group through its original/default option with the required quantity. Do not add only one concrete bushing as an unrelated direct member. The composition resolver must cover the complete family, while the workorder may choose a permitted mixture of sizes.

### ASSY

Use `assy` for an orderable assembly P/N and its composition.

- The ASSY option points to the actual assembly component row from the PDF.
- Add every part that belongs directly to that ASSY at its indentation level, with the PDF quantity.
- Nest a lower ASSY, Alternative P/N family, or Bushing Original/Oversize family through the appropriate default option instead of flattening it into an unrelated concrete part.
- An ASSY may contain another ASSY and that child may contain a bushing group. Resolve the composition transitively.
- Do not duplicate a child group's internal members as direct parent coverages unless the PDF explicitly lists the same parts directly at the parent level.
- Reject self-reference and every direct or indirect cycle.

If two complete ASSY P/Ns are alternatives, create one ASSY group for each composition and a separate `alternative_pn` group whose options are the complete ASSY components.

## Ordering and cross-outs

- Selecting an ASSY must cover its ASSY component and every direct and nested member, preventing duplicate ordering of the assembly and its included parts.
- Apply coverage to PRL and the configured STD forms: NDT, CAD, Stress, and Paint. Narrow the forms only when the source explicitly requires it.
- Alternative P/N is normally choose-one. The bushing Original/Oversize family is the exception that permits mixed quantities up to the required total.
- A concrete bushing already belonging to an Original/Oversize family must not be added alone to an ASSY; direct the editor to add the complete family.

## Existing legacy relationships

- Audit existing `component_assemblies` and old Assy fields before creating groups.
- Preserve legacy rows and component IDs needed by current TDR/Log Card history. When an exact legacy composition row corresponds to a new coverage, retain its reference in `legacy_component_assembly_id`.
- Do not use legacy data as authority when it contradicts the PDF. Report the conflict.
- Completed workorders do not require behavioral migration testing, but their historical references must remain valid.

## Deterministic SQL handoff

- Production inspection is read-only. Generate SQL for the user to run.
- Use one transaction, an exact manual-number/ID guard, duplicate-IPL checks, P/N and quantity reconciliation, and deterministic group codes.
- Make the script repeatable: update or reuse the same managed records instead of creating duplicates.
- Do not delete or rebuild unrelated groups. Prefer no temporary tables when the same guarded result remains readable and verifiable.
- If PDFs are supplied without the standard process workbook, do not change LLP/Log Card, NDT, CAD, Paint, PRL/KIT, or SB data.
- Finish with postflight queries for component counts, group/option/direct/nested counts, missing expected relationships, and a clear `SUCCESS` or no-op/abort result.
- For MySQL/MariaDB handoff SQL, write line comments as `-- text` with whitespace after the second hyphen. `--text` is not a comment. Validate the actual delivered bytes, including comments, through the local SQL engine in an isolated database. A test runner that removes every `--...` line can hide syntax errors and is not sufficient handoff validation. If a driver uses single-statement prepares, use an explicitly multi-statement test connection or the native client for this raw-file check; never test this by executing writes on production.
