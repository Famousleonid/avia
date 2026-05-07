# TDR Audit Stage 2

Date: 2026-05-07
Branch: `codex/tdr-audit-stage1`

## Purpose

Stage 2 introduces explicit TDR row typing without switching existing screens
or controller queries to the new type yet.

The goal is to create a safe migration path:

1. Add `tdr_type`.
2. Add model constants/scopes/helpers.
3. Add a dry-run backfill command.
4. Test classification.
5. Keep runtime behavior unchanged until backfill has been reviewed.

## Database Change

Migration:

`database/migrations/2026_05_07_080000_add_tdr_type_to_tdrs_table.php`

Adds:

```text
tdrs.tdr_type string(64) nullable indexed
```

It is nullable on purpose. Existing production rows remain valid until the
backfill is reviewed and run.

## Model Additions

File:

`app/Models/Tdr.php`

Added type constants:

- `component_tdr`
- `unit_inspection`
- `std_list_carrier`
- `order_new`
- `manufacture_order`
- `manufacture_repair`
- `transfer_clone`
- `unknown`

Added casts:

- `received` -> date
- `use_tdr` -> boolean
- `use_process_forms` -> boolean

Added scopes:

- `forWorkorder($workorderId)`
- `ofType($type)`
- `componentTdrs()`
- `unitInspections()`
- `stdListCarriers()`
- `orderNewRows()`

Added predicates:

- `isComponentTdr()`
- `isUnitInspection()`
- `isStdListCarrier()`
- `isOrderNew()`

Added inference:

- `inferType($manufactureCodeId, $orderNewNecessaryId, $repairNecessaryId)`

## Backfill Command

Command:

```bash
php artisan tdrs:backfill-types
```

Default mode is dry-run.

Write mode:

```bash
php artisan tdrs:backfill-types --write
```

Useful options:

```bash
php artisan tdrs:backfill-types --with-trashed
php artisan tdrs:backfill-types --overwrite
php artisan tdrs:backfill-types --limit-unknown=100
```

Production-safe rule: run dry-run first and review `unknown` rows before using
`--write`.

## Current Inference Rules

```text
component_id null + description "STD List carrier"
  -> std_list_carrier

component_id null + conditions_id not null
  -> unit_inspection

codes_id Manufacture + necessaries_id Order New
  -> manufacture_order

codes_id Manufacture + necessaries_id Repair
  -> manufacture_repair

component_id not null + necessaries_id Order New
  -> order_new

component_id not null
  -> component_tdr

otherwise
  -> unknown
```

`transfer_clone` is intentionally not inferred yet. Transfer history needs a
separate pass because cloned rows are not reliably distinguishable from regular
component TDR rows by one local field.

## Tests

Added:

`tests/Feature/TdrTypeBackfillTest.php`

Coverage:

- core type inference;
- command dry-run does not write;
- command `--write` persists inferred type.

## Verification

Commands run:

```bash
php artisan migrate
php -l app/Models/Tdr.php
php -l app/Console/Commands/BackfillTdrTypes.php
php -l tests/Feature/TdrTypeBackfillTest.php
php -l database/migrations/2026_05_07_080000_add_tdr_type_to_tdrs_table.php
php artisan test --filter=TdrTypeBackfillTest
php artisan test --filter=TdrsTest
php artisan test --filter=WorkorderStdListProcessesServiceTest
php artisan test --filter=BackfillStdListCarrierProcessesCommandTest
php artisan test
php artisan tdrs:backfill-types --limit-unknown=20
```

Full test result:

```text
131 passed
```

Note: during verification, parallel Feature test runs collided on the shared
MySQL testing database. The testing schema was reset with:

```bash
php artisan migrate:fresh --env=testing
```

After that, tests were run sequentially and passed.

## Next Stage

Stage 3 should start replacing the most dangerous query areas with typed
fallback scopes:

1. STD list carrier lookup.
2. Unit inspection lookup/delete.
3. Component TDR create/update/delete.

Before production write:

1. Deploy migration.
2. Run dry-run:
   `php artisan tdrs:backfill-types --with-trashed --limit-unknown=200`
3. Review unknown rows.
4. Run:
   `php artisan tdrs:backfill-types --write`

