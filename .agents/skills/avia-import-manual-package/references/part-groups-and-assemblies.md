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

Ordinary letter variants of the same IPL position (`5-70`, `5-70A`, `5-70B`, `5-70C`) already form an automatic family within one manual. Do not create a separate `alternative_pn` group solely for those suffixes. For ASSY procurement coverage, add one representative component with the position's quantity; the coverage resolver covers all ordinary letter variants, including inside nested ASSY. Do not list each variant as an additional physical quantity. Bushing Original/Oversize remains a separate explicit family and is excluded from this automatic expansion.

User rejected per-row **IPL family / Exact part only** controls on22/Sep/2026. Keep the editor simple: select the ASSY and its members/quantities, without a per-member coverage-mode choice. Do not reintroduce that control or its request field. Existing `manual_part_group_coverages.expand_ipl_family=0` links are retained as compatibility data for already-imported PDF-specific restrictions (37 links confirmed on production); ordinary links default to1. Group editing must preserve these existing values. Do not drop the column or remove resolver support before a reviewed replacement/data migration, because doing so broadens existing coverage to inapplicable letters. A replacement must derive applicability from reviewed PDF/configuration data, not guess from suffixes or burden users with technical switches. Verify direct/nested PRL/STD quantities and Log Card membership remain restricted. Unknown WO SB state remains unresolved, and complete Original/Oversize nesting and explicit multi-option subset groups must be preserved.

Exception: an explicit Alternative P/N group containing only a subset of an IPL family can encode a configuration restriction (for example A/B versus C/D). Preserve that boundary; suffixes alone do not prove interchangeability across subsets. The bundle resolver disables automatic family expansion for such a position and uses the explicitly nested group's members. Audit before retiring existing groups: compare both procurement coverage and Work Scope quantities, retain history/Log Card/WO references, and exclude nontrivial internal option coverages. Never remove partial-family groups merely because their numeric IPL matches.

Retiring a redundant full-family group also requires verifying Log Card/mobile composition membership, not just cross-outs. Direct family representatives must resolve to the same member IDs as the former nested group, without admitting another configuration or bushings. Check TDR orders for multiple selected variants of one retiring family (explicit choose-one and automatic coverage differ in that case); retain/block such groups until reviewed. Include these checks in the guarded SQL and test the whole raw file, repeat, and rollback on an isolated local database. Deploy both coverage and composition resolvers before the cleanup SQL; database SQL alone cannot establish deployed PHP behavior.

Use `alternative_pn` when different base IPL positions (for example `5-70`, `5-71`, `5-72`) are confirmed by the PDF as variants of the same functional detail. Adjacent numbering alone is not evidence of interchangeability. Each variant is one option. Selecting one option covers the other variants for the configured forms.

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

If two complete ASSY P/Ns are alternatives, keep one ASSY group for each distinct composition. Create a separate `alternative_pn` only when needed to link different base IPL positions, not merely letter suffixes. Automatic letter coverage does not traverse another variant ASSY's different composition: nest the applicable child ASSY explicitly.

## Ordering and cross-outs

- Selecting an ASSY must cover its ASSY component and every direct and nested member, preventing duplicate ordering of the assembly and its included parts.
- Ordinary direct IPL-family members share the member quantity, multiplied by the ordered and nested ASSY quantities. Existing directly listed letter variants in the same option are not added together (the resolver uses the largest listed quantity); new imports must use one representative, and conflicting source quantities require review. Distinct occurrences in separate nested branches remain additive. Existing explicit groups and incoming links must not be deleted without a separate reviewed migration.
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
