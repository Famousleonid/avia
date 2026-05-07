# TDR Audit Stage 1

Date: 2026-05-07
Branch: `codex/tdr-audit-stage1`
Baseline test command: `php artisan test`
Baseline result: `129 passed`

## Purpose

This stage freezes the current TDR surface before architectural changes.
No TDR lifecycle behavior is changed in this stage. The goal is to know which
routes, controllers, views, and data meanings exist before adding `tdr_type`
and splitting the 5000-line controller.

## Main Risk Found

`tdrs` currently stores several different business concepts in one table
without an explicit row type. Code guesses the row meaning from nullable fields
and flags:

- `component_id`
- `conditions_id`
- `codes_id`
- `necessaries_id`
- `use_tdr`
- `use_process_forms`
- `description`

That guessing already caused production data loss. A row that looked like a
workorder-level unit inspection could also look like an STD list carrier.

Required direction: add an explicit `tdr_type`, backfill existing rows, and
replace dangerous field-combination queries with typed scopes.

## Current TDR Meanings In One Table

Observed row meanings:

- Component TDR: a part/component row for repair, missing, order new, etc.
- Unit inspection: workorder-level condition row, usually `component_id = null`
  and `conditions_id is not null`.
- STD list carrier: workorder-level technical row that owns STD NDT/CAD/Stress/Paint
  `tdr_processes`.
- Manufacture pair: special create path creates Order New and Repair rows.
- Transfer clone: replicated TDR row in another workorder during transfer.
- Legacy/blank workorder-level row: old or generated row with weak identity.

## Database Summary

### `tdrs`

Core fields:

- `workorder_id`: parent workorder.
- `component_id`: actual component/part for component-level rows.
- `order_component_id`: ordered component reference; currently not enforced by FK in migration.
- `serial_number`, `assy_serial_number`: serial values for component/assy.
- `codes_id`: code such as Missing, Manufacture, Repairable.
- `conditions_id`: condition/inspection reason.
- `necessaries_id`: required action such as Repair or Order New.
- `description`: free text; currently also used as an STD carrier marker.
- `qty`: quantity.
- `po_num`, `received`: order tracking fields.
- `use_tdr`, `use_process_forms`: display/process flags.
- `deleted_at`: soft delete.

Weak points:

- No explicit `tdr_type`.
- `order_component_id` is not a constrained foreign key in migration.
- Multiple meanings depend on nullable combinations.
- Some delete paths manually delete related rows; the model does not protect type-specific deletion.

### `tdr_processes`

Core fields:

- `tdrs_id`: parent TDR.
- `process_names_id`: process name row.
- `plus_process`: extra NDT process ids as comma-separated text.
- `processes`: JSON process ids.
- `description`, `notes`.
- `repair_order`, `vendor_id`.
- `date_start`, `date_finish`, `date_promise`.
- `date_start_user_id`, `date_finish_user_id`.
- `sort_order`, `working_steps_count`.
- `ignore_row`, `in_traveler`, `traveler_group`.
- `ec`, `standalone_ec_only`.
- `user_id`.

Weak points:

- `tdrs_id` uses `onDelete set null`, but some controller paths manually delete processes.
- Activity log currently does not include all investigation-critical fields:
  `sort_order`, `working_steps_count`, `date_start_user_id`,
  `date_finish_user_id`, `ec`, `standalone_ec_only`.

## Model Summary

### `App\Models\Tdr`

Current features:

- Uses `SoftDeletes`.
- Uses Spatie activity log.
- Logs all fillable fields with `logOnlyDirty()` and `dontSubmitEmptyLogs()`.
- Relations:
  - `workorder`
  - `component`
  - `orderComponent`
  - `conditions`
  - `necessaries`
  - `codes`
  - `tdrProcesses`

Missing:

- Casts for `use_tdr`, `use_process_forms`, `received`.
- Type constants.
- Type predicates, for example `isUnitInspection()`.
- Typed scopes, for example `scopeUnitInspections()`.
- Guarded deletion rules by row type.

### `App\Models\TdrProcess`

Current features:

- Uses Spatie activity log.
- Casts `processes`, dates, booleans, traveler group.
- Has helper `normalizeStoredProcessIds()`.
- Relations:
  - `tdr`
  - `processName`
  - `vendor`
  - update user relations.

Missing:

- Full logging for all operational fields.
- Dedicated domain methods for vendor-tracking rows vs traveler group rows vs STD list rows.

## TdrController Inventory

File: `app/Http/Controllers/Admin/TdrController.php`

The controller has these public methods:

- `index`
- `create`
- `inspectionUnit`
- `inspectionComponent`
- `getComponentsByManual`
- `store`
- `store_old`
- `storeUnitInspections`
- `processesPartial`
- `processes`
- `showGroupForms`
- `show`
- `edit`
- `editForm`
- `update`
- `prlForm`
- `ndtForm`
- `ndtStd`
- `cadStd`
- `paintStd`
- `stressStd`
- `specProcessForm`
- `specProcessFormEmp`
- `logCardForm`
- `tdrForm`
- `wo_Process_Form`
- `wo_BoxTitle`
- `destroy`
- `updatePartField`

Large private/helper areas:

- IPL normalization and pagination.
- NDT/CAD/Stress/Paint STD calculations.
- All-parts process grouping.
- Show-data assembly.
- STD list visibility.

## Route Inventory

### Routes pointing to missing TdrController methods

These routes exist, but the target methods do not exist in `TdrController`:

- `tdrs.inspection` -> `inspection`
- `tdrs.cadForm` -> `cadForm`
- `tdrs.machiningForm` -> `machiningForm`
- `tdrs.passivationForm` -> `passivationForm`
- `tdrs.specProcess` -> `specProcess`
- `tdrs.storeProcesses` -> `storeProcesses`
- `tdrs.xylanForm` -> `xylanForm`

These are immediate cleanup candidates. Before removal, search buttons/links/JS
that may still point to their route names.

### Public controller method not routed

- `store_old`

Likely legacy. Candidate for deletion after confirming no reflection/manual calls.

### Suspicious route

- `tdrs/create/{id}` calls `create()`, but `create()` is empty.

## View Inventory

Directory: `resources/views/admin/tdrs`

Views with controller/include references found:

- `admin.tdrs.cadFormStd`
- `admin.tdrs.component-inspection`
- `admin.tdrs.edit`
- `admin.tdrs.index`
- `admin.tdrs.logCardForm`
- `admin.tdrs.ndtForm`
- `admin.tdrs.ndtFormStd`
- `admin.tdrs.paintFormStd`
- `admin.tdrs.prlForm`
- `admin.tdrs.processes`
- `admin.tdrs.show`
- `admin.tdrs.specProcessForm`
- `admin.tdrs.specProcessFormEmp`
- `admin.tdrs.stressFormStd`
- `admin.tdrs.tdrForm`
- `admin.tdrs.unit-inspection`
- `admin.tdrs.wo_BoxTitle`
- `admin.tdrs.wo_ProcessForm`
- `admin.tdrs.partials.*`

Views with zero references found in this scan:

- `admin.tdrs.create`
- `admin.tdrs.inspection`
- `admin.tdrs.processForm`

These are dead-view candidates, not yet deleted.

## Proposed Controller Split

Target direction:

- `TdrShowController`
  - show page orchestration and partial refreshes.
- `TdrPartController`
  - component TDR create/update/delete/edit form/update part field.
- `TdrUnitInspectionController`
  - unit inspection edit/store only.
- `TdrComponentInspectionController`
  - component inspection form/modal endpoints.
- `TdrProcessPageController`
  - all-parts/extra-parts process table pages and group forms.
- `TdrStdFormController`
  - NDT/CAD/Stress/Paint STD forms and calculations.
- `TdrPrintFormController`
  - PRL, TDR, WO process, box title, spec forms.

Target services:

- `TdrTypeService`
- `TdrLifecycleService`
- `TdrStdListService`
- `TdrFormDataService`
- `TdrDeleteService`
- `TdrStdCalculator`

## Stage 2 Entry Criteria

Before adding `tdr_type`:

- Baseline tests pass.
- This inventory is committed.
- No controller logic was changed in stage 1.

Stage 2 should add:

- nullable `tdr_type` column with index;
- type constants/casts/scopes on `Tdr`;
- dry-run backfill command;
- tests for classification/backfill.

