# Stock — daily snapshot at 05:00 (18/Nov/2026)

Current user decision supersedes all on-demand/tunnel instructions: use an independent Windows scheduled task on the bridge at05:00 bridge local time. No Stock button, request polling, persistent service, SSH tunnel or event provider. sync_quantum and Unit History remain untouched.

## Ready, pending administrator execution

New run_stock_daily.ps1 and switch_stock_to_daily.ps1 copied to C:\avia\Stock. Exporter stock_export.php and stock_query.sql already there. The switch script has NOT yet been executed: codexdiag is not an administrator. Run on bridge in administrator PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File C:\avia\Stock\switch_stock_to_daily.ps1
```

It verifies expected paths, stops/deletes ONLY AVIAStock, removes exact Stock tunnel key/service/helper files, registers ONLY sync_quantum_stock_daily at05:00 as SYSTEM, and requests its first run. It refuses unexpected service paths or conflicting task definitions. StartWhenAvailable catches a missed05:00 when Windows becomes available. IgnoreNew prevents duplicate task runs; independent Stock mutex also prevents overlap. No RO/WO paths or tasks are modified. Log: C:\avia\Stock\stock_sync.log. Verify LastTaskResult=0, latest SUCCESS, and production snapshot timestamp/row_count after installation.

## Production handoff

Copy /app, /config, /routes, /resources. The old refresh/status routes and browser polling/stream UI are removed, API POST /api/quantum/stock retained. Admin-only access and Reserved dialog remain. No migrations.

User removes ONLY the authorized_keys line ending avia-stock-tunnel from /home/mcxq8917/.ssh/authorized_keys, preserving other keys. Remove ONLY QUANTUM_STOCK_BRIDGE_URL from application .env; keep QUANTUM_SYNC_TOKEN. Clear cached config/routes/views through normal deployment. The unused QuantumStockRefresh.php on production can remain inert if folder copy does not delete it; it has no route/config reference.

The prior SSH experiment authenticated after correcting invalid permitopen=none, but the host explicitly disabled port forwarding. No further SSH configuration or hosting support is required for daily HTTPS pushes. Existing diagnostic SSH keys must be preserved.

## Snapshot and reservations

All22372 PARTS_MASTER records including zero quantities; no UOM displayed. stock_query.sql reads quantities and direct/BOM/SO/RO reservation links in ONE Oracle SELECT. Snapshot v2 has per-part reservations[{wo,quantity}], unassigned_reserved. Stored atomically in storage/app/quantum/stock.json; previous snapshot remains on failure. Search and Reserved dialog read this snapshot without querying Quantum. Admin role including non-system Admin and system admin only.

For49101-13 all22 reserved units map to14 WOs: the former unexplained13 comprise9 via SO and4 via R9631 (2 each W108095/W108096). See relationships.md. Other unassigned quantities and signed differences are shown honestly. Cost not included; UNIT_COST differs from sale/list prices and base currency remains unverified.
## Activated and verified — 18/Nov/2026
User ran switch_stock_to_daily.ps1 as Administrator. Screenshot confirms AVIAStock DeleteService SUCCESS and task sync_quantum_stock_daily next run19/Nov/2026 05:00 bridge time. First export log:22372 rows, SUCCESS at10:13:26. Production read-only verification: snapshot v2 exported_at2026-11-18T15:13:21Z (10:13:21 Toronto),22372 rows. P/N49101-13 reserved22,14 WO links summing22,unassigned0. Production authorized_keys no longer contains avia-stock-tunnel; .env no longer contains QUANTUM_STOCK_BRIDGE_URL. No changes to sync_quantum or Unit History. Earlier pending-installation notes above are superseded by this verified activation.

UI update18/Nov/2026: single live q field matches PN prefix OR Description substring; checkbox nonzero filters On hand !=0. Debounce250ms, cancel/stale-response guard,100-row infinite append within existing bounded scroller; sticky header remains. Total and filtered counts include all matches, not just loaded rows. Snapshot change during append resets resultset to prevent mixing daily snapshots. Reserved uses delegated click handler for appended rows. Six tests/39assertions passed; Chrome verified100→200 append, live PN/description filters, zero-result state, nonzero509 Washer matches, Reserved dialog, both themes and parent height/overflow chain. Deploy /app and /resources only; bridge unchanged.
