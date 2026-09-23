# Process identity and renaming

Process relationships continue to use `process_names.id` / `process_names_id`.
Previously, secondary business rules repeatedly looked up those IDs by editable
`process_names.name`. Renaming Machining to Machining (AT) therefore removed it
from the bushing picker and other workflows.

`process_names.identity_name` now records the immutable purpose of each ID.
The migration captures it once for existing records; Eloquent captures it when
a new directory record is created. The directory can edit the display `name`,
but cannot edit this identity. Machining and Machining (AT) share the existing
Machining purpose; Machining (EC) remains separate.

Use `ProcessName::identityIds()`, `whereIdentityName(s)()`, `hasIdentity()` or
`identityName()` for business rules. Query scopes select the matching IDs from
the stored identity. Never resolve business behavior from the current display
name. Keep `name` for user searches, sorting labels, logs and rendering.
New raw SQL inserts into `process_names` must provide `identity_name` too;
prefer Eloquent creation. Renaming an unrelated existing row to Machining does
not turn that row into a Machining process.

Updated areas: bushing selectors/save/grouping/SP forms, TDR and Extra Processes,
Machining/EC forms, date permissions including Paint/mobile, STD lists,
measurement planning, Traveler and overdue notifications.

## Production handoff

Production was inspected read-only; no deployment or database write was made.

1. Copy the new migration file to production first.
2. Run the migration before copying the runtime code that depends on its column:

   ```sh
   php artisan migrate --path=database/migrations/2026_09_22_160000_add_identity_name_to_process_names.php --force
   ```

3. Copy the changed application files and views and `public/js/tdr-processes/edit-process/edit-process.js`.
   Include the new `app/Models/Concerns/HasProcessIdentity.php` trait.
4. Clear compiled views through the normal deployment procedure.
5. Verify W107990 → Bushing Processes → Add Process: Machining (AT), ID 10,
   is available. Rename through the directory should change its displayed label
   while preserving availability, links and date permissions.

The migration changes no foreign keys, workorder processes, RO data or batches.
Historical migration `2026_04_02_100000` keeps its original one-time name lookup,
because it runs before the new identity column exists on older installations.

## Verification

- Local W107990/manual 54: Add Process resolves Machining ID 10.
- Rename regression tests cover directory updates, bushing Add Process and save,
  route batches, NDT print specifications, STD options, Traveler exclusion and
  Paint finish permissions. A differently named process renamed to Machining
  does not acquire Machining permissions.
- Extended tests also expose four unrelated failures, reproduced with original
  controller/model implementations loaded from HEAD: MobileApiTest log-card ASSY
  selection, StdProcessAuditTest switching authenticated users, and
  WoMeasurementFindingFinalTest switching authenticated users, and the
  QualityAssuranceTest certificate-signer authorization scenario. Diagnostic logs
  are under `storage/app/codex/identity-*-tests.txt` and `identity-tests-*.txt`.
