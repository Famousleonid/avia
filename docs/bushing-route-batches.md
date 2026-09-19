# Bushing route batches

Current bushings are grouped automatically when the Bushing List is saved. The signature consists of the existing process columns (Machining, Stress Relief, NDT, Passivation, CAD, Anodizing, Xylan), ignoring individual instructions and NDT variants. No new process types are introduced.

`wo_bushing_batches.route_number` is shared by all operations of one route within a WO. Operation batch IDs remain distinct, preserving existing per-operation RO, vendor, dates and machining steps. SP Form combines these operations into one route column, including routes not yet sent. Six columns fit on a sheet; additional columns continue on subsequent sheets. Legacy sent batches retain their previous SP behavior.

Any RO, start/finish date or machining step on a line or batch protects the line from regrouping. Protection extends to its connected batch members and the other operations of a started automatic route. Saving a stale Bushing List also retains these protected lines and their original process row IDs. New parts cannot join a started route. Historical display numbers are retained in `legacy_number`; this metadata snapshot does not update timestamps, RO, dates or memberships.

## Manual deployment

Deploy the changed PHP/Blade files and the migration, then run on the server as the application owner:

```sh
php artisan migrate --path=database/migrations/2026_09_18_140000_add_route_number_to_bushing_batches.php --force
php artisan bushings:group-routes --all
php artisan bushings:group-routes --all --apply
```

The first grouping command is a transactionally rolled-back preview. To scope the command use `--workorder=205` (database ID, not WO number) instead of `--all`. Repeated application preserves current route numbers and operation batch IDs. No production changes were made by Codex.

Quantum RO matching uses the persisted route number rather than position within the operation list. Legacy workorders without route metadata retain their original positional matching.

## Local verification, 18/Sep/2026

W107736 (WO ID 205) currently has 12 lines / 15 pieces:

| Batch | Route | Qty |
|---|---|---:|
| B1 | Machining → NDT → Passivation → CAD | 6 |
| B2 | Machining → NDT → CAD | 3 |
| B3 | NDT → CAD | 5 |
| B4 | NDT → Passivation → CAD | 1 |

Browser QA used Playwright/Edge because the Browser plugin was unavailable. Real local controller-rendered HTML and local assets were used: matching horizontal labels, batch selection, four SP columns, 8px part numbers without clipping, one physical PDF page, no JavaScript errors. Automated tests also cover thirteen routes on three sheets, historical RO preservation through list save, new arrivals after sending, legacy batch forms and RO mapping.
