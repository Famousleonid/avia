# AVIA schema observed 07/Oct/2026

Re-check these files in the target repository before importing:

- `app/Models/TravelerProcessTemplate.php`, `TravelerProcessTemplateStep.php`
- `app/Services/TravelerProcessTemplates.php`
- `app/Http/Controllers/Admin/TravelerProcessTemplateController.php`
- `database/migrations/2026_10_07_160000_create_traveler_process_templates.php`

Templates: `traveler_process_templates` has manual_id, full part_number, name, active, revision, timestamps. Ordered child table `traveler_process_template_steps` has template_id, process_id, sort_order, nullable traveler_group, description. Same positive group number combines steps; NULL means standalone.

Processes reference `process_names`; manual membership is `manual_processes.manual_id / processes_id`, with requires_fig/requires_zone. A process can exist globally without being linked to the manual. Inspect column sizes and exact specifications before adding definitions. Do not silently truncate text.

Application copies steps into TDR processes and records an immutable snapshot in `tdr_traveler_template_applications`. Importing template definitions must not insert application records or change TDR processes. A revision change does not update prior WO snapshots.

Manual management uses existing manual access, with no extra permission. Technicians select active exact-P/N templates in TDR. Template creation/editing is in the manual's Traveler tab.

Observed workbook layout: A2 PART TRAVELER; G5 description/IPL; K5 full P/N; D7 CMM; G7 repair reference; row 9 headings; A10+ sequence, B task, E specification, H vendor, K notes. G RO / I completed date / J signature must not enter reusable instructions. This layout is not guaranteed for all workbooks.

First source case: `4259A_4260A0000-ser   F.xlsx`, manual 32-11-15RM. On 07/Oct/2026 the user selected Main Fitting without `(2)`, retained all internal/external steps with only external steps grouped, and approved changing 32-11-14RM to32-11-15RM in2225-0014!E10/E17 and2821-0001!E10. These corrections are specific to this source, not universal replacements. Main Fitting's sheet name and actual Parts support separate2821-0111/2822-0111 templates. Blank EC/Zone and conditional operations remain review items.

Green-tab gate added on 08/Oct/2026: the preserved source.xlsx has green tab ARGB FF28FF78. Sheet 2821-0622 has no tab color and is excluded from future conversion/SQL; 2821_2822-0111 PP (2) is red (FFFF0000) and excluded. CAD AIRCO PLATING is green but is not automatically eligible: the part-number/Traveler-layout requirements still apply. These observed colors are source-specific, not a universal palette. Prior delivered SQL and existing database templates were not retroactively rewritten by this rule update.

Observed storage limit: `processes.process` is VARCHAR(255); recheck before import. Current rule: all requirements belong in Specification, and Description holds only extra instructions. The historical `Per Traveler template; full specification in Description.` fallback is retired: if a specification does not fit, stop that portion of import until catalog storage is expanded; never delete legacy full text while its catalog entry is only a placeholder. Preserve source line breaks and meaningful task qualifiers; reuse exact definitions without approximating technical limits. The initial 07/Oct import duplicated task names/specifications; cleanup must preserve user edits and existing WO snapshots. FIG/ZONE requirements may already be satisfied by Specification and must not force duplicate Description text. Incomplete/conditional instructions still require review before activation.
