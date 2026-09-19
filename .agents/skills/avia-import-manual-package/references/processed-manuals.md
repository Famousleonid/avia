# AVIA processed manual ledger

Canonical history of manual-package work. Read this file before answering whether a manual or FIG has already been processed, and update it in the same task after every material import or verification stage.

## Status rules

### 32-11-14RM /95 — Check Sheet production PASS, all stages complete — 19/Sep/2026

User reported the new Check Sheet SQL uploaded. Pinned read-only production audit **19/Sep/2026 15:16:41UTC** passed the exact delivered SELECT verification for active manual95 /32-11-14RM. Also independently compared the stored JSON to reviewed source JSON: exact equality,2 filled tasks (stages5/7),5 slots,8 legend entries, blank references preserved. SourceSHA `df7ab75b554fd5c63ff73652a0648b6a18200db15238fb2629d04bbeea91eb30`, contentSHA `2e8d37c2ddc3db44d99ea2350edaeffc066c5d33e6a9a8c66d67d2da8c8b12d4`, both match. Zero discrepancies. No production writes by agent.

Evidence: `storage/app/codex/manual-package-import/32-11-14RM/20260919-check-sheet/production-postflight-2026-09-19T151641_0000.json`; `handoff-manifest.json` now complete with verification metadata. `capture_journal_templates.py` and `record_verification.py` reproduce the audit; previous missing-template snapshot archived. Rechecks of91/78 also PASS.

| Manual / ID | FIG / previous package | Check Sheet | Completion |
|---|---|---|---|
|32-11-14RM /95|FIG1–15,1206 Parts,114 groups,14 SB; full package audit14/Sep/2026 retained|Exact JSON/source/content verified19/Sep/2026|✓ complete including new Check Sheet stage|

Both HTML journal paths and Markdown updated:6 full-green manuals58/91/41/40/95/78, no historical-complete rows awaiting Check Sheet. The underlying all-manual counters remain the dated19/Sep inventory; this task reverified Check Sheet only, not all historical Parts/group rows. Rendered headless Chrome smoke PASS:6-count and green filter, manual95 green with15/15 FIG and Check Sheet tick, details show completed stages, no page errors. The source-folder SQL remains unchanged; no repeat upload required.

### Check Sheet uploads91/78 verified; manual95 handoff ready — 19/Sep/2026

User confirmed uploading the previously supplied Check Sheet SQL and provided `C:/Airplane/32-11-14RM ERJ 190 MLG`. Pinned read-only production audit at **19/Sep/2026 15:06:57UTC** returned PASS for the exact reviewed JSON and source/content hashes of manual91 /32-11-05 ELEB and manual78 /32-11-15RM. Exact identities checked against active manuals. Evidence `storage/app/codex/manual-package-import/journal-check-sheet-audit-20260919.json`; prior14:06:37UTC missing-template evidence preserved in timestamped archive. Current production templates:58,40,41,91,78; manual95 still has none. No production writes by agent.

| Manual / ID | Previously verified package | New Check Sheet stage | Current overall status |
|---|---|---|---|
|32-11-05 ELEB /91|FIG1,57 Parts,17 groups; PDF/groups/XLS audit15/Sep/2026|Exact v02 JSON/source/content verified19/Sep/2026; contentSHA `f9ccd769bbf24369972e8769502c8916037b0d0595a1145bb6407405eb0307ab`|✓ complete including Check Sheet|
|32-11-15RM /78|FIG1–15,997 Parts,143 groups,13 SB; package audit17/Sep/2026|Exact v02 JSON/source/content verified19/Sep/2026; contentSHA `35467337f4900c49d006847d83f83ef491b62934a1279951b5f8471c8b954eb8`|✓ complete including Check Sheet|
|32-11-14RM /95|FIG1–15,1206 Parts,114 groups,14 SB; historical package audit14/Sep/2026|New SQL prepared and tested; production template absent|Previous package complete; Check Sheet upload/postflight pending|

Manual95 source `2821_2822A0000-series.xlsx` (624136 bytes), SHA256 `df7ab75b554fd5c63ff73652a0648b6a18200db15238fb2629d04bbeea91eb30`. Confirmed workbook identity32-11-14/32-11-14RM in DISASSEMBLY/ASSEMBLY F4 and process-sheet headers against production95. Extracted only `IN PROCESS CHECK SHEET`:2 tasks (stages5 and7),5 slots,8 legend entries;35 relevant source cells independently reconciled with XLSX XML, zero source issues. Blank references/slots preserved; WO/P/N runtime cells K3/E5 excluded. Original source unchanged. ContentSHA `2e8d37c2ddc3db44d99ea2350edaeffc066c5d33e6a9a8c66d67d2da8c8b12d4`. No repeat PDF, Parts, groups, flags or SB import.

Run `storage/app/codex/manual-package-import/32-11-14RM/20260919-check-sheet/`: source JSON, independent `source-audit.json`, `sql-local-test.json`, `handoff-manifest.json`. Whole raw SQL including comments tested in isolated local scratch: initial SUCCESS, exact verify PASS, byte-identical repeat SUCCESS; wrong number, deleted manual and conflicting template all blocked unchanged. Scratch removed; working local database unchanged. Source-folder handoff files copied and SHA-checked:

- `32-11-14RM_20260919_v01_check_sheet_import.sql` (12523 bytes), SHA256 `73429f70ca59ee4656f2f10a04f26370f2211802ce08e11d872aa6cdf4862fd6`.
- `32-11-14RM_20260919_v01_check_sheet_verify.sql` (4268 bytes), SHA256 `332b2360ab53169ed4afaabc9934a6423a7a39c1210695168c7becc0d3b64840`.
- Matching README. Existing production table confirmed; separate schema SQL unnecessary. User executes import, agent verifies read-only afterwards.

Both journal HTML paths and Markdown regenerated:5 green complete manuals58/91/41/40/78, only95 remains historical-scope yellow awaiting Check Sheet. Earlier package audit dates retained in detail notes; full-completion verification dates for91/78 now19/Sep/2026. Browser QA passed desktop/mobile,5/1 filters, row sorting, FIG expansion, manual expected-FIG guard and HTML save/reopen; no console errors. Existing inventory counters remain the explicitly dated19/Sep morning snapshot; this turn reverified only Check Sheet data, not the entire old PDF/group package.

### Production postflight and whole journal refresh — 19/Sep/2026

User uploaded manual41 v02 and requested verification plus correction of the visible HTML journal. Fresh pinned read-only SSH audit **19/Sep/2026 14:05:06UTC** passed all reviewed SELECT assertions; no production writes. Runner `storage/app/codex/manual-package-import/verify_uploaded_20260919.py`; combined evidence `uploaded-packages-audit-20260919T140503Z.json`; each manual's `20260918-full/production-postflight-20260919T140503Z.json` retains exact checks and target rows.

| Manual / ID | FIG coverage | Active Parts | Groups | SB | Exact checks | Completion |
|---|---|---:|---:|---:|---|---|
|32-11-06 /41|FIG1:112 Parts/56 groups; FIG2:256/70; FIG3:322/78; two EC-only Parts retained|692|204|27|1344/1344 PASS|✓ complete for explicitly approved confirmed-only scope|
|32-11-10 /40|FIG1:131 Parts/37 groups|131|37|2|679/679 PASS|✓ complete; reverified|
|32-10-02 /58|FIG1:65 Parts/18 groups|65|18|2|238/238 PASS|✓ complete; LLP/PAINT approved N/A; reverified|

Manual41 verified147 ASSY/38 Alternative/19 Oversize,274 options,5604 coverages including939 nested; flags LC58/NDT132/CAD72/Paint81/Stress42/KIT302; legacy rows and IDs retained. Check Sheet exact JSON/source/content hashes passed (3 tasks/5 slots). Zero discrepancies. **Scope limitation remains:**45 positions/families and534 root/member links deliberately omitted by user approval while preserving Parts; no claim of fully certified composition for all SB configurations. Prior pending-upload statements are historical and superseded by this successful postflight.

Fresh all-manual inventory `production-inventory-20260919T140503Z.json`:119 active manuals +1 deleted,9891 active Parts across inventory,593 groups. This replaces the stale15/Sep snapshot in `output/manuals-production-20260915.html` and adds stable alias `output/manuals-production-journal.html`; Markdown counterpart also updated. Root cause of missing green rows: hard-coded old counters/progress and a manual78-only refresh, not failed imports. Generator `update_journal_20260919.py`, source `journal-ui.js`, combined audit data `journal-data-20260919.json`; old HTML preserved as `journal-before-refresh-20260919.html`. All future refreshes should use production inventory plus explicit audit evidence, never counts as completion proof.

Separate read-only Check Sheet audit at14:06:37UTC (`journal-check-sheet-audit-20260919.json`, runner `capture_journal_templates.py`) confirms templates exist only for58/40/41. Historical completed scopes for91 /32-11-05 ELEB,95 /32-11-14RM and78 /32-11-15RM remain valid for PDF/Parts/groups/XLS/SB, but their newly required Check Sheet is absent. They are light-yellow with an explicit missing stage, not falsely full-green and not relabelled as failed Parts audits. Current all-stage green rows:58,41,40. Previous dates15/Sep,14/Sep,17/Sep retained for historical package audits.

Rendered QA PASS in isolated headless Chrome via bundled Playwright (Browser plugin not available),1600x900 and390x844:120 rows/119 active, current totals,3 green rows first,3 historical-scope rows, complete/previous filters, search, FIG expansion including scope limitation, expected-FIG completion guard, saved HTML reopen without duplicate rows, bounded inner scroll through body/main/container chain, no console errors. QA report/screenshots in workstation temp `avia-journal-qa.json`, `avia-journal-desktop.png`, `avia-journal-completed.png`, `avia-journal-mobile.png`. No application code or source PDF/XLS changed.

### 32-11-06 / ID41 — v01 withdrawn; raw-tested v02 handed off — 19/Sep/2026

User's phpMyAdmin attempt failed with MariaDB1064 at the first line. Root cause: v01 used `--text` instead of `-- text`; the previous test stripped invalid comment lines and masked the syntax error. The v01 test claims below establish query logic only, not executable raw-file validity. **Do not use v01. Current handoff is v02.** No Parts, groups or business rules changed: `v02-revision-audit.json` proves the import and verification differ only by whitespace after comment markers.

Current files copied with SHA256 equality checks to `C:/Airplane/32-11-06 49050 CRJ700-900/`:

- `32-11-06_20260919_v02_import.sql`: 8,163,283 bytes; SHA256 `d28f0616adb68267348ba5b9d91674522cf699fa374a51fc995fe49c739dc0c5`.
- `32-11-06_20260919_v02_verify.sql`: SELECT-only, 3,207,437 bytes; SHA256 `372dccf0138dadfc62e080d6f08176c5fad0809e77bd47d6bf4ff9bfb2738bcc`.
- Matching v02 `_README.md` and `_omitted-links.md`; v01 retained only as historical evidence.

The actual delivered v02 bytes, including comments, passed isolated local multi-statement execution: SUCCESS, 1344/1344 SELECT checks, byte-identical repeat SUCCESS, 1470 resolver cases and all five negative rollback cases. Raw v01 reproduces1064 with unchanged data. Generator rejects malformed comments; reusable skill rule now requires raw-file validation. `sql-local-test.json`, `reviewed-audit.json` and `handoff-manifest.json` reference current v02 hashes. Working local database unchanged; scratch schema removed.

Read-only production capture19/Sep/2026 13:41:46UTC compared exactly with13:16:47UTC: every captured target-manual row unchanged after the failed attempt, still49 active Parts,0 groups,0 SB,0 Check Sheet,6 legacy rows. Current snapshot is `production-preflight-current.json`; earlier snapshot archived. **Status remains partial / pending user v02 upload and production postflight.** Approved scope and per-FIG totals in the previous entry remain unchanged.

### 32-11-06 / ID41 — tested SQL handed off, production pending — 19/Sep/2026

User confirmed «1. да 2. да»: preserve all Parts; include only confirmed root composition and omit disputed SB/dependent links with an explicit report; do not infer1570C bearing compatibility or independent1520B/1570B alternatives. Recorded in run `storage/app/codex/manual-package-import/32-11-06/20260918-full/confirmed-decisions.json`. Previous pending-policy statements below are resolved, not new blockers. No application-code changes.

Final artifacts copied with SHA256 equality checks into **`C:/Airplane/32-11-06 49050 CRJ700-900/`**:

- `32-11-06_20260919_v01_import.sql` —8,163,274 bytes,SHA256 `559df1359a8bfdd21c1fe1c07f9c482cf39024dea7310a6403fe249f8a0f1028`.
- `32-11-06_20260919_v01_verify.sql` —SELECT-only,SHA256 `00f3abf9e267037a51177f929d1e539e03c273e5b14968b3ba580a708ec1ea47`.
- Matching `_README.md` and `_omitted-links.md` explain scope, import procedure and intentional exclusions. User imports only `_import.sql`, whole file; schema for Check Sheet included; no temporary tables. Final status must be SUCCESS.

| FIG | PDF Parts | ASSY | Oversize | Alternative | All groups | Expected LC/NDT/CAD/Paint/Stress/KIT | Production completion |
|---|---:|---:|---:|---:|---:|---|---|
|1|112|53|0|3|56|0/6/2/0/2/20|Pending user import/postflight|
|2|256|53|5|12|70|10/26/18/23/7/128|Pending user import/postflight|
|3|322|41|14|23|78|48/100/52/58/33/154|Pending user import/postflight|
|Total|690|147|19|38|204|58/132/72/81/42/302|Not complete|

Fresh read-only production baseline19/Sep/2026 13:16:47UTC:49 active Parts/0 archived,0 groups,0 SB,0 Check Sheet,6 legacy rows; exact manual41/32-11-06. `production-preflight-current.json` retains this snapshot and the prior18/Sep snapshot is archived. Expected after import692 active Parts =690 PDF + preserved0-0ECBush/0-0ECSleeve,643 additions and47 existing PDF IDs preserved. Both P/N corrections and five EFF corrections are guarded. Existing names/history/true flags remain. Expected LC58/CAD72 exceed source-required56/71 because existing true flags stay on.27 SB (15 recommended,10 mandatory,2 null), composite AC/OEM/AD identities kept. Check Sheet3 tasks/5 slots, contentSHA256 `b62c745abcf63bd8a851630cb130e2c76a042c63f94f887bf5e643f0346bbc17`; original PDF/XLSX SHA256 unchanged.

Graph204 groups/274 options/5604 coverages/939 nested; includes62 roots and38 cross-FIG aliases. Canonical and alias ASSY ordering covers both header records exactly once; alias headers are identity coverage, not an extra physical part. Five exact legacy IDs608/609/610/611/627 linked, all6 legacy rows preserved. `build_final_groups.py`/`groups-audit.json` verifies EFF membership, conditional1534 quantity8/4, guide quantities6/6/2/12, no cycles or duplicate direct members, and alias quantity1.

**Approved limitation, not full configuration certification:**45 positions/families across534 root/member pairs deliberately not linked; report and `omitted-links.json` preserve exact rows/reasons.1950D/E orderable and linked by confirmed EFF but internal composition opaque.1550 combination replacement and1520B/1570B compatibility remain manual controls; no fabricated KIT, unrestricted incompatible Alternative group or application-code enforcement added. Do not describe the package as complete across all possible SB states.

`sql-local-test.json` PASS for exact final SQL hashes:1344 SELECT checks,1470 actual application resolver/scope/quantity cases, first import SUCCESS, byte-identical repeat SUCCESS, five negative cases rolled back unchanged (wrong manual, conflicting P/N, SB, Check Sheet, modified group composition). IDs, true flags, EC-only rows, legacy and unrelated manual preserved. Tests used isolated local schema, removed after completion; working local DB and production unchanged. `handoff-manifest.json`, `reviewed-audit.json`, `expected-final.json`, `expected-checks.json` retain reproducible audit. **Status partial / handoff-ready for approved scope; user upload and exact production postflight still pending.**

### 32-11-06 FIG3 approvals and lower resolver audit — 18/Sep/2026

User answered «1. исправлять 2. оставить 3. да»: apply3-1950D/E EFF Q,S/R,T (preserve raw S,U/T,V), keep these heads as orderable Parts with no invented internal composition, pair grease adapters100/100A with180/180A and250/250A with340/340A. Persisted in manual41 run `20260918-full/confirmed-decisions.json`; applied by `build_lower_groups.py`. The earlier three-question blocker is resolved.

Current lower draft104 groups:47 ASSY,19 Oversize,38 Alternative;174 options,324 coverages,79 nested. FIG1=12 (9A/3Alt);FIG2=32 (15A/5O/12Alt);FIG3=60 (23A/14O/23Alt). Cycles/duplicate members0. `lower-resolver-tests.json`:470 PASS cases against actual application recursion (47 ASSY ×5 scopes ×multipliers1/2), in-memory models only, no DB connection. Final SQL has NOT been tested or generated.

Fresh read-only production capture22:11:35UTC18/Sep/2026 in `production-preflight-current.json`:manual41 exact32-11-06,49 active/0 archived Parts,0 groups,0 SB,0 Check Sheet,6 legacy rows. Resolver file SHA256 equals local. No newly unapproved P/N conflicts. Expected full Parts692 =690 PDF + existing-only0-0ECBush/0-0ECSleeve;643 new rows. `root-configurations-audit.json`:62 roots (24/20/18),38 unique cross-FIG P/N aliases; uncertain SB/dependent choices segregated, not guessed.

Awaiting policy for SB-dependent or otherwise underdetermined root members: user offered confirmed-only root membership with explicit omitted-link report, or full SB configuration evidence first. Also asked whether to retain3-1570C and incompatible bearing/gland variants as Parts without unsupported independent Alternative groups. Actual production resolver does not descend nested KIT and Alternative groups do not descend alternative ASSY compositions: no pretend KIT or five-way alternative for1550 complete seal versus1 O-ring+2backup rings. No application-code change authorized/performed. Status partial, no final handoff; ledger earlier64-group and pending1950/adapter statements superseded. Workbook remains718 mappings/0 conflicts,27 SB, Check Sheet3 tasks/5 slots pending integrated SQL.

### 32-11-06 FIG3 extraction, workbook approvals and lower groups — 18/Sep/2026

User approved the three prior FIG2 questions: preserveID6252 while correcting2-490 to49150-1 and create2-490A/49150-2;2-350A EFF BDFHKMP;2-1540R/S EFF U/V and roots2-1T/U. Decisions persisted in `storage/app/codex/manual-package-import/32-11-06/20260918-full/confirmed-decisions.json`; raw source EFF retained separately in `parts-reviewed-draft.json`. No production writes.

FIG3 all15 table pages and blank16 visually inspected. `3.csv`/`fig3-source-evidence.json`/`fig3-audit.json`/`fig3-transcription.txt`/`extract_fig3.py`:322 orderable Parts,316 new versus18/Sep snapshot,6 existing matches,0 new P/N conflicts; excludes non-orderable3-12 No Part Number while preserving its source evidence. Total manual690 PDF Parts (FIG1 112,FIG2 256,FIG3 322),643 additions; existing-only rows preserved. All PDF hashes rechecked unchanged.

`groups-lower-draft.json` from `build_lower_groups.py`:64 source-supported lower groups (FIG1 11,FIG2 21,FIG3 32) =39 ASSY,19 Original/Oversize,6 Alternative;92 options,228 coverages,47 nested. Graph/quantity checks PASS with0 cycles/duplicate members and no direct standalone family bushings. Not a complete group plan: root configurations, outer cylinders, pending adapter pairings and conditional seal/bearing-gland combinations remain.

Read-only XLS adapter in run `read_workbook.py`/`audit_workbook.py` handles SBs/PRL trailing space/LLPL and isolates32-11-06 from later32-10-02 sections. User confirmed all listed PDF-based flag remaps and skipping KIT3-12/3-940 without deleting existing data. `workbook-mappings-draft.json`:718 evidence mappings,0 unresolved workbook rows; required LC56/NDT132/CAD71/Paint81/Stress42/KIT302, additive only.27 SB (15 recommended,10 mandatory,2 null); correct J=REC/K=OPT; repeated AC identities retain distinct OEM/AD. Check Sheet remains previously extracted3 tasks/5 slots, pending final package integration/verification. Workbook hash unchanged.

Awaiting NEW FIG3 decisions (async questions sent):3-1950D/E EFF S,U/T,V versus available root codes, absent bare-cylinder P/N for these two heads, adapter100/100A→180/180A and250/250A→340/340A pairing. Source-only restrictions3-1520B↔1570B and seal1550 versus O-ring+two backup rings recorded, not silently flattened. Final SQL NOT ready; status partial. No local application DB or production changes. Pending-decisions.md contains current details; earlier FIG3/XLS extraction blockers below are superseded.

### 32-11-06 missing FIG1 drawing received — 18/Sep/2026

Read-only review of `//F519/backup4/ScanFolder/20260918174614288.pdf` (1 page,30455 bytes,SHA256 `5345d7e0ac3db408961c2fad474dde931ae3c05761fe0db4129675485be5c3b5`) confirms32-11-06/P/N49050, FIG1 Sheet1/4, printed10016, Apr08/22, Dressed Shock Strut Assembly. No cross-out. Evidence/render in manual41 run `20260918-full/drawing-fig1-sheet1/manifest.json`; immutable local audit copy `supplement-20260918174614288.pdf`. All drawings now present and reviewed: FIG1 4/4, FIG2 10/10, FIG3 7/7. Earlier missing-drawing statements are superseded. FIG3-prefixed callouts on FIG1 drawing are cross-references, not extra FIG1 rows. This sheet supplies no P/N/EFF decisions:2-490,2-350A,2-1540R/S remain pending. Full FIG3 table extraction, final groups/workbook reconciliation and tested SQL remain unfinished; status partial. No production modifications or new handoff.

### 32-11-06 supplemental drawings — 18/Sep/2026

Received `//F519/backup4/ScanFolder/20260918170017060.pdf` (42 pages, SHA256 `811c2db43e70654a8996a111178da4300acc6be69acf0a8a3a824be1ac4a073d`), visually confirmed manual32-11-06/Goodrich-Collins/P/N49050/ID41. Original unchanged; audit copy and300-DPI renders in `storage/app/codex/manual-package-import/32-11-06/20260918-full/drawings-20260918/`. Manifest records exact reviewed pages versus unreviewed duplicate table pages. FIG1 Sheets2–4, FIG2 all10 drawing sheets, FIG3 all7 drawing sheets visually reviewed; only FIG1 Sheet1/4 still missing. This supersedes the broad missing-drawings blocker below, not the unfinished FIG3 table extraction.

`composition-resolutions.json` resolves allocation for five lower ASSYs:2-980 Upper Guide,2-990 Lower Guide,2-1110/1120 Brackets,2-850 Manifold. Upper/lower guide bushings differ; do not split common child totals equally. Per-parent quantities verified against all13 corresponding expanded IPL totals and five head P/N identities (PASS). Components.units_assy stays the printed table quantity; group quantities are per one parent. FIG2 drawing shows two2-1460 positions but does not resolve printed oversize qty1. PN conflict2-490, EFF2-350A and2-1540R/S still require decisions. FIG3 Parts/full XLS/groups/final SQL remain incomplete. No new SQL handoff, production writes or completion claim.

### 32-10-02 and 32-11-10 production postflight PASS — 18/Sep/2026

User reported both packages uploaded. Fresh read-only current production audit at **20:26:38 UTC,18/Sep/2026** passed every exact check: manual58 /32-10-02 **238/238**; manual40 /32-11-10 **679/679**; zero discrepancies. Full evidence in each `20260918-full/production-postflight-20260918T202636Z.json`; combined summary `storage/app/codex/manual-package-import/uploaded-packages-audit-20260918T202636Z.json`; runner `verify_uploaded_20260918.py`. No production files/data changed by the audit.

| Manual / ID | FIG | Active Parts | ASSY / Alternative / Oversize | Options / coverages | Production flags LC/NDT/CAD/Paint/Stress/KIT | SB | Check Sheet | Completion |
|---|---|---:|---|---|---|---:|---|---|
| 32-10-02 /58 | 1 | 65 | 5 /13 /0 | 31 /82 | 0/11/5/0/0/0 | 2 | Exact JSON/hash verified;3 tasks/5 slots | ✓ complete; LLP/PAINT approved N/A |
| 32-11-10 /40 | 1 | 131 | 10 /14 /13 | 67 /364 | 8/22/20/17/0/59 | 2 | Exact JSON/hash verified;2 tasks/5 slots | ✓ complete |

Verified package identities, every expected IPL/P/N/name/quantity, previous IDs and archived rows, direct/nested group relationships and quantities/scopes, Original/Oversize links, additive process flags, SB/legacy retention and Check Sheet content/source hashes. Manual40 LC8/Paint17 exceed source-required LC7/Paint12 because existing true flags are intentionally preserved. Both manuals meet full current package completion criteria. Prior pending-upload statements below are historical and superseded. Manual41 /32-11-06 remains partial and is not part of this verification.

### 32-11-06 extraction and source blockers — 18/Sep/2026

Manual41 run `storage/app/codex/manual-package-import/32-11-06/20260918-full/` now contains visually reviewed FIG1 `1.csv` (112 rows, all8 pages;24 root configurations,20 FIG2 references) and FIG2 `2.csv` (256 rows, all13 table pages plus supplied drawing). Source evidence includes raw quantities, normalized quantities, indentation, EFF, NP, page references and source notes. `extract_fig1.py`, `extract_fig2.py`, `fig2-transcription.txt`, `fig1-audit.json`, `fig2-audit.json` reproduce/validate the extraction. No duplicate or crossed-out table pages found in FIG1/2. FIG3 full extraction remains pending; do not interpret rendered pages as completed Parts.

Fresh production snapshot18/Sep/2026: FIG1 matches12 existing IPL with zero PN conflicts;100 new source rows. FIG2 matches29 existing IPL;227 new source rows. Approved2-1530 ID6230 correction retained. Newly identified conflict2-490 ID6252 production49150-2 vs PDF49150-1; PDF49150-2 belongs to2-490A. User asked to approve ID-preserving correction; not yet approved in this entry. No DB writes.

Full ASSY handoff blocked by source ambiguity: only FIG1 drawing Sheet4/4 and FIG2 Sheet10/10 supplied; missing drawing Sheets1–3/1–9 and FIG3 drawings requested. Distinct guide assemblies2-980/990 share unallocated child totals6/6/2/12; brackets2-1110/1120 likewise share bushings. Do not assign to nearest header or divide equally without evidence. Also record printed EFF2-350A BDFHKLP, and2-1540R/S Y/Z vs rootsU/V; conditional qty2-1534 and original/oversize qty2-1460/1460A. Full details in `pending-decisions.md` and FIG2 audit. Standard analyzer rejects SBs alias, so source not missing; layout-aware offline validator used for CSV, full workbook adapter still pending. Status **partial / source questions**, no full production SQL yet.

### 32-11-10 full SQL handoff — 18/Sep/2026

Supersedes the pending manual40 decisions below. User explicitly approved the PDF correction at 1-510, all repair-P/N NDT/CAD mappings, and current lower ASSYs 1-200A/720A/900A for all four root heads; old ASSYs remain alternatives. Decisions in `storage/app/codex/manual-package-import/32-11-10/20260918-full/confirmed-decisions.json`.

Final tested artifacts saved with identical SHA256 under `C:/Airplane/32-11-10 190-70200 ERJ170 MLG/`: `32-11-10_20260918_v01_import.sql`, matching `_verify.sql` and `_README.md`. Full package includes the Check Sheet schema/template; no separate old Check Sheet SQL needed. FIG1: 131 Parts (114 new, 17 prior IDs preserved; archived ID9 unchanged), all numeric quantities, NP markers, 37 groups = 10 ASSY +14 Alternative P/N +13 Original/Oversize. Workbook-required flags: LC7/NDT22/CAD20/Paint12/KIT59, additive only; two SB and 12 historical legacy links retained. Check Sheet2 tasks/5 slots. Old lower ASSY headers are selectable alternatives, not extra physical members of current root composition. LH/RH roots remain distinct.

Evidence/run: `build_reviewed_package.py`, `source-evidence.json`, `workbook-mappings.json`, `groups-final.json`, `groups-audit.json`, `reviewed-audit.json`, `generate_final_sql.py`, `test_sql.php`, `sql-local-test.json`, `handoff-manifest.json`. Immutable PDF/XLS hashes rechecked. Local isolated scratch test PASS: 679 SELECT checks, byte-identical second import, 100 resolver assembly/scope/quantity cases, preservation of prior true flags, seven blocked/rollback cases. Scratch schema removed; working local and production DB unchanged. Latest production baseline18/Sep/2026 still17 Parts/0 groups/2 SB/no Check Sheet. Status **partial / SQL ready; user upload and production postflight pending**, not complete. Manual41 extraction remains in progress.

### 32-11-10 / 32-11-06 / 32-10-02 preparation — 18/Sep/2026

User requested complete production SQL for all three. Read-only current-production capture: `storage/app/codex/manual-package-import/32-11-10/20260918-full/production-preflight.json`. Exact manuals: 32-11-10 ID40 (17 active Parts, 0 groups, 2 SB); 32-11-06 ID41 (49/0/0); 32-10-02 ID58 (62/0/2). Manufacturer-suffixed variants were inspected only to avoid identity confusion, not selected for import. No production or local application database writes and no new full SQL handoff.

32-11-10 source: `C:/Airplane/32-11-10 190-70200 ERJ170 MLG/1.pdf` plus `190-70200-series.xlsx`. FIG1: all 8 pages visually inspected, 131 canonical Parts including orderable top ASSYs, numeric quantities and raw RF evidence; 114 additions planned, one production PN conflict at 1-510 (ID5608), prior archived ID9 preserved. Source manifest/hashes, 300-DPI renders, `1.csv`, `source-evidence.json`, `preflight-audit.json`, analyzer outputs and `pending-decisions.md` in the run. Groups/configuration composition still pending audit. XLS has LLP LH/RH plus singular NDT/CAD BUSH and CAD Stripping, requiring layout-aware reconciliation beyond generic analyzer (its reported missing LLP and flag totals are not final). Conflicts: 1-500 -001 vs PDF -003; BUSH sheet wrong PN at families 750,790,910; base IPL vs repair-PN process targeting needs confirmation. SB Excel-date serial 40909 resolves to existing AWD 2012-01-01; preserve existing two SB. Check Sheet previously prepared (2 tasks/5 slots), no production template yet. Status partial; awaiting source conflict decisions, group audit, full SQL tests/handoff and later production postflight.

32-11-06 / 32-10-02 update, 18/Sep/2026: user supplied the missing workbooks. This supersedes the earlier missing-XLS blocker, but does not approve the pending 32-11-10 corrections. Both remain partial; no final full production SQL or database writes.

| Manual / ID | FIG source coverage | Parts / groups audit | Workbook / Check Sheet | Completion |
|---|---|---|---|---|
| 32-11-10 / 40 | FIG 1: 131 Parts verified on production | 37 groups,67 options,364 coverages verified | All XLS flags preserved/applied;2 SB; Check Sheet exact JSON/hash verified | ✓ Complete,18/Sep/2026;679/679 checks |
| 32-11-06 / 41 | FIG1:112 Parts/56 groups; FIG2:256/70; FIG3:322/78; all drawings complete | v02 production postflight19/Sep PASS:692 Parts,204 groups,1344 exact SELECT checks |718 mappings,0 conflicts; LC58/NDT132/CAD72/Paint81/Stress42/KIT302;27 SB; Check Sheet3 tasks/5 slots exact JSON/hash verified | ✓ Complete for user-approved confirmed-only scope;45 positions/families and534 links deliberately omitted, Parts retained |
| 32-10-02 / 58 | FIG 1: 65 PDF Parts verified on production | 65 active Parts; 5 ASSY + 13 Alternative, 31 options / 65 direct / 17 nested coverages, exact quantities and IDs verified | 11 NDT / 5 CAD; 2 SB retained; LLP/PAINT approved N/A; Check Sheet exact JSON/hash verified | ✓ Complete, 18/Sep/2026 |

32-10-02 run: `storage/app/codex/manual-package-import/32-10-02/20260918-full/`. Source folder `C:/Airplane/32-10-02 Dumper MLG`; source manifest, renders, `1.csv`, `source-evidence.json`, `groups-draft.json`, `workbook-mappings.json`, `preflight-audit.json`, and Check Sheet intermediate SQL/JSON retained. New source Parts: 1-1 / 49800-3, 1-320 / 3923094-501, 1-470 / 49805-3. Pending decisions: ID605 / 1-70 production `ANS01A10-4`, PDF `AN501A10-4`, XLS PRL row13 `MS35266-59`; ID626 / 1-230 production `NS35266-60`, PDF/XLS `MS35266-60`. Preserve IDs/history. Missing LLP/PAINT tabs require scoped N/A confirmation, not invented mappings or clearing existing flags. CAD row with IPL 250 and P/N MS27612-5C resolves to source 1-250A. Check Sheet content SHA256 `d53e9c39154738735a0188bd0fb95a8ea9887e43e72ff0593395b78ea60146c0`.

32-11-06 run: `storage/app/codex/manual-package-import/32-11-06/20260918-full/`. Source folder `C:/Airplane/32-11-06 49050 CRJ700-900`; manifest and 300-DPI renders retained. FIG3 last page is intentionally blank. No claim of complete duplicate/crossed-out-page review or final Part count yet. PDF FIG2 page12 (printed10048) confirms 2-1530 P/N `49120-111`, versus production ID6230 `19120-111`; correction awaiting confirmation. Workbook has LLPL, STRESS RELIEF, CAD removal, NDT Bush/CAD Bush and embedded 32-10-02 NDT/CAD sections: isolate manual scope and do not treat missing exact parser sheet aliases as missing sources. Figure/configuration effectivity and cross-FIG ASSYs require full review. Existing EC-only Parts/history remain preserved. Check Sheet content SHA256 `b62c745abcf63bd8a851630cb130e2c76a042c63f94f887bf5e643f0346bbc17`.

Both new workbooks use `SBs` and requirement headers J=REC, K=OPT, L=MAN; do not use the generic analyzer's fixed J/K meaning. 32-11-06 SB aircraft numbers repeat across distinct OEM/AD records, so aircraft-number-only merging is unsafe. New Check Sheet SQL files in the run folders are intermediate templates only, not final full manual handoffs. Production postflight remains pending for all three packages.

18/Sep/2026 decision update (supersedes the pending P/N decisions for these three rows above): user confirmed "да сведи к pdf" in response to the three-row conflict table. Approved ID-preserving corrections: manual58 ID605 / 1-70 `ANS01A10-4` → `AN501A10-4`; manual58 ID626 / 1-230 `NS35266-60` → `MS35266-60`; manual41 ID6230 / 2-1530 `19120-111` → `49120-111`. The manual58 PRL row13 P/N `MS35266-59` is reconciled to the PDF identity `AN501A10-4`; its blank CODE still does not warrant KIT. Exact approvals are stored in each run's `confirmed-decisions.json`. These are approved plans, not production changes. Missing LLP/PAINT N/A and the earlier separate manual40 process-targeting decisions are not marked approved by this P/N confirmation. Full extraction/group audit, executable SQL tests and production verification remain pending as stated above.

### 32-10-02 full SQL handoff — 18/Sep/2026

**Production postflight PASS, 18/Sep/2026 at 19:50:07 UTC.** The user uploaded v01; direct read-only SSH succeeded without changing PowerShell policy or writing production files. All238 exact SELECT checks returned OK, zero discrepancies. Verified 65 active Parts, prior IDs and archived ID3004, quantities, additive flags, all18 groups/31 options/82 coverages (65 direct +17 nested), both SB and legacy links41/42, and the complete Check Sheet JSON/source/content hashes. Evidence: run `production-postflight-20260918.json`, `production-postflight-audit-20260918.json`, and `verify_production.py`. The prior pending/blocked-snapshot labels below are historical. Manual58 FIG1 is **complete**, including Check Sheet and approved LLP/PAINT N/A. Fresh companion snapshots confirm manual40 still17 active Parts/0 groups/2 SB/no Check Sheet and manual41 still49/0/0/no Check Sheet; their packages remain partial.

Final artifacts saved and SHA256 compared to tested originals in `C:/Airplane/32-10-02 Dumper MLG/`: `32-10-02_20260918_v01_import.sql`, matching `_verify.sql` (SELECT only), and `_README.md`. Run/generator/evidence/test logs: `storage/app/codex/manual-package-import/32-10-02/20260918-full/`. `handoff-manifest.json` fingerprints all three artifacts and verified immutable source hashes. No source documents, production or working-local application data changed.

FIG1 final plan: 65 active Parts from 62 prior active rows plus three new ASSY rows (1-1, 1-320, 1-470); IDs605/626 receive the approved PDF P/N corrections; all 65 quantities reconciled. Archived duplicate ID3004 remains archived, not restored over active 1-120. Existing unrelated basic fields and true flags are preserved. Graph: 5 ASSY + 13 Alternative P/N, 31 options, 65 direct / 17 nested coverages. All source rows are included transitively in root ASSY 49800-3 with no duplicate composition or cycles. PDF PRFD rows 1-250A/1-420A are the defaults. Legacy links41/42 remain unchanged and are referenced by matching new coverages. Source-required flags: NDT11, CAD5, KIT0; LLP/PAINT N/A explicitly approved. Both existing SB retained exactly. Check Sheet3 tasks/5 slots included with full content/source hash checks.

`sql-local-test.json` passed: first import, all238 standalone SELECT checks, byte-identical repeat, 50 application-resolver ASSY/scope/quantity cases, preservation of added prior LC/Paint/KIT flags, and seven rejection/rollback cases (wrong manual, changed PN, missing ID, foreign group-code ownership, conflicting Check Sheet, postflight corruption, continued execution after simulated DML error). Only the randomly named isolated local scratch schema was created and removed; working DB unchanged. SQL uses no temporary tables. It creates only the Check Sheet schema if absent BEFORE the guarded data transaction; an empty newly created table may remain after a rejected import. This caveat is documented in README.

Production baseline remains the saved SELECT snapshot from 18/Sep/2026. A new read-only snapshot attempt was stopped by Windows execution policy for `.ps1`; the restriction was not bypassed. Exact source-state guards and rollback tests cover unexpected target changes; the user must still perform the import and subsequent read-only production verification. Status **partial / SQL ready**, not complete. Manuals40/41 are not included in this SQL.

### New workbook stage: In-Process Check Sheet — 17/Sep/2026

Decision follow-up, 18/Sep/2026, manual 32-10-02 / ID58 only: user explicitly approved LLP and PAINT as N/A for this package and preservation of existing checkboxes. Recorded in `storage/app/codex/manual-package-import/32-10-02/20260918-full/confirmed-decisions.json`; the package audit now removes these two missing-stage blockers and retains additive-only flag changes. This supersedes the pending N/A question above. No production data changed. Final group review, full SQL validation/handoff and production postflight remain outstanding; the manual is not complete. This approval does not apply to other manuals or resolve the separate manual40 process-targeting questions.

The user added a mandatory manual-specific `IN PROCESS CHECK SHEET` stage for future imports. Historical complete labels below remain evidence for the earlier PDF/Parts/groups/flags/SB scope, **not** evidence that the new sheet is already installed. The HTML journal likewise still describes that earlier scope; include the separate check-sheet stage when answering overall/new-workflow completion questions.

Read-only production check 17/Sep/2026 confirmed the following exact manual identities and that `manual_in_process_check_sheets` does not yet exist. WO 107736 is ID 205, primary manual 68, P/N 2801A0000-03. No production writes. Six XLSX sources under `C:/Airplane` were reviewed, fingerprinted and parsed: 16 populated tasks / 30 total slots / eight legend entries per manual; no parser conflicts. Source headers retained in JSON, current WO identity supplied by AVIA; sample WO/P/N and stamps are not imported. No PDF, FIG, Parts, quantity, group, flags or SB changes in this task.

Run: `storage/app/codex/in-process-check-sheet/20260917/`. Each `<prefix>_v02_source.json` contains exact source filename/SHA256, task/stage/reference/stamp-label/legend content and source cells. `<prefix>_v02_import.sql` / `_verify.sql` are guarded handoff artifacts; `local-verification.json` records six exact local SELECT passes. Earlier v01 SQL in the run is superseded (native MySQL JSON normalization fix); it was not handed off or applied to production.

| Manual | ID | Source folder under C:/Airplane | Workbook | Artifact prefix | Tasks / slots | New-stage state |
|---|---:|---|---|---|---:|---|
| 32-11-01RM | 68 | 32-11-01RM 2801A0000 ERJ170 MLG | 2801_2802A0000-series.xlsx | 32-11-01RM | 3 / 5 | Local passed; production pending |
| 32-11-05 ELEB | 91 | 32-11-05 170-7015 ERJ170 MLG | 170-70150-series.xlsx | 32-11-05-ELEB | 3 / 5 | Local passed; production pending |
| 32-11-10 | 40 | 32-11-10 190-70200 ERJ170 MLG | 190-70200-series.xlsx | 32-11-10 | 2 / 5 | Local passed; production pending |
| 32-11-15RM | 78 | 32-11-15RM ERJ 195 MLG | 4259A_4260A0000-ser.xlsx | 32-11-15RM | 2 / 5 | Local passed; production pending |
| 32-21-02 | 42 | 32-21-02 ERJ 190 NLG | 190-70745-series.xlsx | 32-21-02 | 2 / 5 | Local passed; production pending |
| 32-21-04 Goodrich | 21 | 32-21-04 47200 Q400 | 47200-ser.xlsx | 32-21-04-Goodrich | 4 / 5 | Local passed; production pending |

Final source-folder filenames: `<prefix>_20260917_v02_check_sheet_import.sql` and `_verify.sql`; the identical `in_process_check_sheet_20260917_v01_schema.sql` creates the new table once. Code/SQL handoff: `output/in-process-check-sheet-20260917.zip`, instructions `docs/in-process-check-sheet.md`, targeted three-file patch in the run. Do not deploy unrelated dirty working-tree changes. Existing manual 95 (32-11-14RM) was not reprocessed: no workbook found in the six current local source folders; its new check-sheet stage is unverified, not N/A.

Local tests: 5 tests / 36 assertions, six guarded SQL imports repeated without duplication and exact JSON SELECT checks. Browser QA: WO107736 icon immediately before TDR Form; current WO/P/N; all six live local manual templates match extracted text/stage order; 1920×1080 and 390×844 form layout; Print works; sample and long Goodrich print outputs one Letter page; zero JS errors. Scripts/screenshots are under `C:/Users/Leo/.codex/visualizations/2026/08/13/019ffb4f-1112-7ec0-b975-c2c29de3d51a/check-sheet-*`. Production post-import verification remains pending the user's upload/import.

### Interactive journal — 17/Sep/2026

Follow-up after manual 78 production postflight: `output/manuals-production-20260915.html` now embeds the verified 17/Sep/2026 evidence for 32-11-15RM (15/15 FIG, workbook/SB and production true, zero blockers) and its 997 Parts / 143 groups / 13 SB, including per-FIG counts and the retained non-FIG repair part. All other manuals retain the dated inventory; the header explicitly labels this mixed snapshot. Previously only this Markdown ledger was updated, leaving the HTML with an empty FIG list and production=false: that stale data, not CSS, caused the non-green row. The original HTML now shows three completed manuals, sorts manual 78 into the completed block, and includes it in the green-only filter. Saved-copy refresh retains explicitly entered expected FIG counts. Verified using Playwright with isolated headless Chrome (Browser plugin absent), 1920×1080 and 390×844: full parent layout chain, green background, 15/15, 17/Sep/2026, 997/143/13, filter, expand, all 118 rows/26 columns, save/reload and unrelated user-entered FIG count preserved; zero console errors. Evidence: `C:/Users/Leo/.codex/visualizations/2026/08/13/019ffb4f-1112-7ec0-b975-c2c29de3d51a/test-journal78.cjs`, `journal78-completed.png`, `journal78-mobile.png`. No production writes or new production re-audit in this UI-only follow-up. Reload the original local HTML to see the update; separately downloaded older copies are not automatically replaced.

`output/manuals-production-20260915.html` now combines the unchanged production inventory snapshot dated 15/Sep/2026 with separately labelled package-audit evidence from this ledger. It is no longer a production-only completion report. The earlier production-only Markdown inventory remains unchanged. No new production query/import was performed for this UI change.

The journal has an editable expected FIG count, verified FIG count, XLS/SB completion, verification date, completed-only/incomplete filters, green completed rows sorted first, and one-line manual identities and toolbar. Expected counts are prefilled only for the two evidenced full packages: manual 91 = 1 and manual 95 = 15. Others remain unset for the user. A FIG counts as ready only after Parts and required groups pass production verification; production Parts/flag counts alone never qualify. The completion predicate requires an exact positive expected/verified FIG count match, XLS/SB and production verification, a known verification date, and no unresolved blockers. The displayed date is audit confirmation, not an invented exact finishing date.

Initial stage evidence is in the HTML's `initialProgress`; saved HTML copies store it in `script#journal-data`. The user saves edited expected counts using **Сохранить HTML** and continues using the downloaded self-contained copy. No browser storage or production writes are used. Before regenerating the journal, preserve the user's latest expected counts from their current saved copy; refresh the stage evidence from the ledger after future imports/verifications. Unknown completion is not a claim that work never started.

QA passed in headless installed Chrome via Playwright at 1920×1080 and 390×844: all 118 rows retained (117 active), 26 aligned columns, two complete rows sorted first, filter/search/expand, expected-count changes and invalid values, unknown-stage protection, saved-copy reload without duplicated columns, and clean JavaScript console. UI screenshots/test script are under `C:/Users/Leo/.codex/visualizations/2026/08/13/019ffb4f-1112-7ec0-b975-c2c29de3d51a/` (`manuals-journal-*.png`, `test-manual-journal.cjs`).

### Original production inventory — 15/Sep/2026

Production-only overview of **all** manuals, captured 15/Sep/2026: `output/manuals-production-20260915.html` (filterable FIG table) and `output/manuals-production-20260915.md` (all rows and per-FIG counts). Aggregate snapshot: `storage/app/codex/manual-package-import/production-inventory-20260915.json`. It contains 117 active manuals and one soft-deleted manual; 55 have no active Parts, 12 have Parts without the listed process flags or new groups, 43 have flags but no new groups, and 7 have new groups. Active totals: 9,035 Parts, 214 groups, 145 SB records. The production `manuals` schema has no package-completion marker, and no import tables were found. This production-only report therefore uses `?` for completion. Historical `complete` entries below depend on prior source audits and must not be presented as completion flags stored in production.

- `complete`: all supplied PDF FIGs are reconciled into Parts; all required ASSY, Alternative P/N, Original/Oversize, and KIT groups are audited; workbook flags and Service Bulletins have zero unresolved conflicts; the user-applied production import has passed a read-only postflight audit.
- `partial`: at least one stage or FIG remains unverified, source conflicts remain, the package did not contain PDFs/workbook, or current production differs from reviewed artifacts.
- Production counts below are a read-only snapshot taken on **15/Sep/2026**. They include pre-existing true flags because package imports are additive and never clear them.
- Per-FIG columns: `P` Parts, `L` Log Card, `N` NDT, `C` CAD, `S` Stress, `Pt` Paint, `K` PRL/KIT; group columns are `A` ASSY, `O` Original/Oversize, `Alt` Alternative P/N, `KG` KIT group, followed by group options/direct coverages/nested coverages.

## Summary

| Manual | ID | Status | PDF FIG coverage | Production Parts | New groups | Workbook/SB state | Evidence |
|---|---:|---|---|---:|---:|---|---|
| 32-11-05 ELEB | 91 | complete | FIG 1 | 57 | 17 | XLS applied; 0 SB | `storage/app/codex/manual-package-import/32-11-05-ELEB/`; `output/sql/32-11-05_ELEB_fig1_part_groups.sql`; `output/sql/32-11-05_ELEB_170-70150_process_flags.sql` |
| 32-11-14RM | 95 | complete | FIG 1–15 | 1,206 | 114 | XLS applied; 14 SB; production postflight passed | `storage/app/codex/manual-package-import/32-11-14RM/`; `database/scripts/import_32-11-14RM_standard_package.sql` |
| 32-11-15RM | 78 | complete | FIG 1–15: 988 PDF Parts + 9 retained existing rows; production verified 17/Sep/2026 | 997 | 143 | XLS flags/SB verified; 13 SB; zero postflight discrepancies | `storage/app/codex/manual-package-import/32-11-15RM/20260917-full/production-postflight-audit-20260917.json`; versioned v01 SQL in source folder |
| 32-21-02 | 42 | partial | FIG 1–10 extracted | 608 | 0 | analyzer retained 42 unresolved source issues; 9 SB currently in production | `storage/app/codex/manual-package-import/32-21-02/` |
| 32-21-04 Goodrich | 21 | partial | FIG 1A, 2A, 3, 4 extracted | 690 | 6 | workbook/SB present, but production differs from reviewed 704-Part PDF set | `storage/app/codex/manual-package-import/32-21-04/`; `storage/app/codex/manual-package-import/47200-ser/` |
| 32-21-06 | 54 | partial | FIG 1–3 imported | 509 | 31 | Parts/flags/20 SB production verification passed; groups audited only for FIG 3 | `storage/app/codex/manual-package-import/32-21-06/20260910_115025/` |

Production inventory totals from 15/Sep/2026 across these six tracked manuals (before the 17/Sep Legacy retirement and manual 78 full-package import): **3,977 Parts**, **207 groups**, **577 group options**, **554 direct coverages**, **296 nested coverages**, and **76 Service Bulletins**. These are historical totals, not current counts. The first three summary rows now meet the full `complete` definition after manual 78 postflight on 17/Sep/2026.

Production also contains new Part Groups for manuals `32-11-07` (ID 63) and `32-31-02` (ID 73), but no matching stored PDF→Parts→groups→workbook package was found under `storage/app/codex/manual-package-import/`; do not classify them as processed until their source evidence is located and audited.

## Local retirement of Legacy Group — 15/Sep/2026

Production SQL handoff and verified user import, **17/Sep/2026**: fresh read-only snapshot `storage/app/codex/legacy-production-20260917.json` confirms 77 legacy links (75 active / 2 soft-deleted), so the prior local retirement must not be reported as a production migration. Versioned cross-manual artifacts: `output/sql/legacy_retirement_production_20260917_v01.sql` and SELECT-only `output/sql/legacy_retirement_production_20260917_v01_verify.sql`. This is a cross-manual Legacy retirement, not a source-package import; no `C:/Airplane` source package is involved. SQL preserves Parts/quantities/ASSY/WO, backs up 77 links, creates 16 groups / 50 options / 50 coverages, reuses production groups 8/92/93 and removes the retired column/index. Tested on isolated local MariaDB 10.6.9 with source-change guard, repeat and interrupted-DDL retry. No new PDF/FIG extraction, workbook flags, SB or package completion changes. User import completed and read-only postflight passed for all nine rows below. Evidence: `storage/app/codex/legacy-production-postflight-20260917.json`. Field/index absent, migration marker present, backup 77 (75 active / 2 archived), 16 new groups / 50 new options / 50 coverages. All 77 Parts retain their prior manual/IPL/P/N/name/bushing/quantity/deleted state; reused groups #8/#92/#93 and their options match the preflight byte-for-byte. phpMyAdmin displayed #2014 on its subsequent `SET FOREIGN_KEY_CHECKS = ON`; it did not roll back or prevent this completed import. Do not rerun the import to address that client error. Package statuses remain unchanged.

| Manual | ID | FIG affected | Production SQL plan | Completion |
|---|---:|---|---|---|
| 32-11-08 | 37 | 1 | 1 Alternative + 10 Original/Oversize; archive 2 deleted-Part links | ✓ Legacy retirement verified 17/Sep/2026 |
| 32-21-02 | 42 | 9 | 1 Alternative | ✓ Legacy retirement verified 17/Sep/2026 |
| 32-21-03 Liebherr | 60 | 1 | 1 Alternative | ✓ Legacy retirement verified 17/Sep/2026 |
| 32-11-07 | 63 | 1 | reuse Alternative #8 | ✓ Legacy retirement verified 17/Sep/2026 |
| 32-21-06 Liebherr | 87 | 1 | 1 Original/Oversize | ✓ Legacy retirement verified 17/Sep/2026 |
| 32-21-05 Liebherr | 89 | 1 | 1 Original/Oversize | ✓ Legacy retirement verified 17/Sep/2026 |
| 32-11-05 ELEB | 91 | 1 | reuse Alternatives #92/#93 | ✓ Legacy retirement verified 17/Sep/2026 |
| 32-28-20 | 107 | 3 | 1 Alternative | ✓ Legacy retirement verified 17/Sep/2026 |
| 32-21-30 | 108 | 1 | automatic letter IPL families, no new groups | ✓ Legacy retirement verified 17/Sep/2026 |

Local cleanup correction, 17/Sep/2026: the earlier “0 populated legacy fields” result excluded soft-deleted Parts. Raw-table audit found archived Parts #482/#485; these two links were backed up to `storage/app/part-groups/legacy-retirement-20260917-113925-748370cb.json` and cleared before physically dropping the local field/index. New groups and component_assemblies were unchanged.

User confirmed the rules: letter-suffix IPLs group automatically; different numeric IPL variants use Alternative P/N; bushing original/repair sizes use Original/Oversize; ASSY remains a composition. The prior question whether `1-320` is ASSY is resolved: its `1-320/1-321/1-321A` variants remain Alternative P/N, not an ASSY conversion.

Applied locally only using `app/Console/Commands/RetireLegacyPartGroups.php`: 75 Parts / 25 old links, 18 groups created (6 Alternative, 12 Original/Oversize), one exact existing group reused (#8), 8 automatic letter-IPL families, 0 populated legacy fields remaining. Backup and exact component IDs: `storage/app/part-groups/legacy-retirement-20260915-180223-e7ba3d94.json`. Handoff: `docs/legacy-part-groups-retirement.md`. No PDF/workbook import, flags, SBs, ASSY composition or production data changed. This migration is not a renewed CMM engineering audit; all historical production package statuses/counts above remain unchanged.

| Manual | ID | FIG affected | Local created / reused | Remaining stage |
|---|---:|---|---|---|
| 32-11-08 | 37 | 1 | 1 Alternative + 10 Original/Oversize | production retirement verified 17/Sep/2026 |
| 32-21-03 Liebherr | 60 | 1 | 1 Alternative | production retirement verified 17/Sep/2026 |
| 32-11-07 | 63 | 1 | reused Alternative #8 | production retirement verified 17/Sep/2026 |
| 32-21-06 Liebherr | 87 | 1 | 1 Original/Oversize | production retirement verified 17/Sep/2026 |
| 32-21-05 Liebherr | 89 | 1 | 1 Original/Oversize | production retirement verified 17/Sep/2026 |
| 32-11-05 ELEB | 91 | 1 | 2 Alternative (production already has its own exact groups) | production retirement verified 17/Sep/2026 |
| 32-21-02 | 42 | 9 | 1 Alternative | production retirement verified 17/Sep/2026 |
| 32-28-20 | 107 | 3 | 1 Alternative | production retirement verified 17/Sep/2026 |
| 32-21-30 | 108 | 1 | no manual group needed; letter IPLs automatic | production retirement verified 17/Sep/2026 |

## 32-11-05 ELEB — complete

Source PDF FIG 1 contains 57 canonical Parts. The 17 groups comprise 6 ASSY, 3 Original/Oversize, and 8 Alternative P/N groups. Workbook source requirements were Log Card 6, NDT 4, CAD 2, Paint 6, KIT 23; the larger production true counts below include preserved earlier flags. All three workbook conflicts were reviewed and resolved.

| FIG | P | L | N | C | S | Pt | K | A | O | Alt | KG | Opt | Direct | Nested |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 57 | 8 | 8 | 2 | 0 | 10 | 26 | 6 | 3 | 8 | 0 | 37 | 54 | 20 |

Read-only production hierarchy check, 15/Sep/2026 (manual 91, FIG 1): components 5283/5284/5285 (`1-320`, `1-321`, `1-321A`) are options 302/303/304 of Alternative P/N group 93, `Spherical Bearing 1-320`. Lower Stay ASSY groups 99/100 (options 322/323, IPL `1-270`/`1-270A`) each reference option 302 with quantity 1. Side Stay groups 102/103 (options 326/327, IPL `1-1`/`1-1A`) reference the corresponding Lower Stay option with quantity 1. The Parts UI recursively displays all ancestor groups, explaining the four ASSY badges plus the direct alternative-group badge. These three components also retain legacy `kit_prl_choice_group=bearing_spherical_cedar_5644`; upper-position components 5248/5249/5250 use separate `bearing_spherical_cedar_9929`. Production KIT/PRL and STD grouping code still reads this legacy field; it is not a physical parent assembly and must not be cleared merely to remove the badge. This check explains current stored relationships, not a renewed engineering interchangeability or full PDF/workbook audit. No production changes or new import artifacts; prior completion status and package counts unchanged.

Local UI follow-up, 15/Sep/2026: manual 91 production snapshot (57 Parts / 17 groups) was rendered in memory against local Blade templates; no production or local import was performed. ASSY badges now target only their head component and the Assy column; Alternative P/N/Original-Oversize badges stay in Group. Focused group editing hides the global catalog and renders directly included groups as a collapsed hierarchy. Existing legacy fields and group types are preserved. The user's proposed interpretation of `1-320` as an ASSY conflicts with stored Alternative P/N group 93; conversion is unresolved and was not applied. QA artifacts: `C:/Users/Leo/.codex/visualizations/2026/09/03/01a067f7-31dc-7f71-acb6-359c0d6305b6/groups91-fixture.json`, `render-groups91-preview.php`, `groups91-qa.mjs` and `groups91-*.png`. Package completion/FIG coverage/workbook/SB counts unchanged; UI changes require manual production deployment.

## 32-11-14RM — complete

All 15 supplied FIG PDFs, Parts, full group hierarchy, workbook flags, and 14 existing Service Bulletins were verified on production. The crossed-out duplicate FIG 10 page 10151 was excluded. Source-required additive flags were Log Card 20, NDT 90, CAD 60, Paint 0, KIT 514; production retains additional earlier true flags.

| FIG | P | L | N | C | S | Pt | K | A | O | Alt | KG | Opt | Direct | Nested |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 81 | 0 | 0 | 0 | 0 | 0 | 53 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2 | 84 | 0 | 0 | 0 | 0 | 0 | 38 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 3 | 64 | 0 | 0 | 0 | 0 | 0 | 37 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 4 | 36 | 0 | 0 | 0 | 0 | 0 | 4 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 5 | 108 | 0 | 0 | 0 | 0 | 0 | 26 | 24 | 0 | 0 | 0 | 24 | 98 | 0 |
| 6 | 96 | 4 | 12 | 10 | 0 | 4 | 39 | 5 | 4 | 5 | 0 | 30 | 20 | 13 |
| 7 | 62 | 0 | 0 | 0 | 0 | 0 | 36 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 8 | 98 | 1 | 16 | 2 | 0 | 1 | 49 | 4 | 0 | 1 | 0 | 10 | 16 | 0 |
| 9 | 104 | 3 | 7 | 3 | 0 | 3 | 58 | 2 | 0 | 0 | 0 | 2 | 6 | 0 |
| 10 | 137 | 5 | 9 | 13 | 0 | 1 | 45 | 6 | 4 | 8 | 2 | 70 | 24 | 20 |
| 11 | 43 | 0 | 5 | 0 | 0 | 0 | 14 | 1 | 0 | 0 | 0 | 1 | 5 | 0 |
| 12 | 120 | 2 | 13 | 0 | 0 | 0 | 48 | 7 | 0 | 0 | 0 | 7 | 31 | 4 |
| 13 | 68 | 10 | 17 | 16 | 0 | 8 | 24 | 2 | 9 | 3 | 0 | 57 | 19 | 22 |
| 14 | 94 | 2 | 19 | 22 | 0 | 2 | 43 | 4 | 18 | 2 | 0 | 87 | 20 | 72 |
| 15 | 11 | 0 | 0 | 1 | 0 | 0 | 0 | 2 | 1 | 0 | 0 | 8 | 6 | 2 |
| **Total** | **1,206** | **27** | **98** | **67** | **0** | **19** | **514** | **57** | **36** | **19** | **2** | **296** | **245** | **133** |

## 32-11-15RM — complete

### User import verified on production — 17/Sep/2026

User reported the v01 import completed. Fresh SELECT-only production snapshot at `2026-09-17T20:53:57+00:00`: `production-postflight-20260917.json`; independent exact comparison: `production-postflight-audit-20260917.json`; user-readable per-FIG report: `production-postflight-20260917.md`, all under `storage/app/codex/manual-package-import/32-11-15RM/20260917-full/`. **PASS, zero discrepancies.** All 988 reviewed PDF Parts and nine retained existing-only Parts are present (997 active total). Confirmed exact IPL/P/N/name/quantity, prior IDs, six restored IDs, five still-deleted historical records, all 143 groups / 361 options / 235 direct and 139 nested coverages, scope sets, bushing family pointers, and absence of cycles. All 26 legacy component_assemblies records remain unchanged. User exception ID 8010 `2821-0222R` remains in ASSY 13-1B.

Verified group types: ASSY 60, Original/Oversize 35, Alternative P/N 44, KIT 4. Verified flags: LC 26, NDT 69, CAD 65, Stress 0, Paint 40, PRL/KIT 496. All 13 SB IDs and contents match. Previous true flags are retained. Every supplied FIG 1–15 has passed Parts, group and workbook mapping postflight. Manual completion is now **✓ complete**, including the documented user-approved PDF exception. No production writes were performed by the agent. The preparation/pre-import history below is retained for traceability; its pending labels and 907-Part/39-group snapshot no longer describe current production.

| FIG | PDF Parts verified | Parts/groups | XLS mappings | Production verified | Complete |
|---|---:|---|---|---|---|
| 1 | 65 | Passed | Passed | 17/Sep/2026 | ✓ |
| 2 | 71 | Passed | Passed | 17/Sep/2026 | ✓ |
| 3 | 47 | Passed | Passed | 17/Sep/2026 | ✓ |
| 4 | 18 | Passed; no manual group required | Passed | 17/Sep/2026 | ✓ |
| 5 | 90 | Passed | Passed | 17/Sep/2026 | ✓ |
| 6 | 75 | Passed | Passed | 17/Sep/2026 | ✓ |
| 7 | 46 | Passed; no manual group required | Passed | 17/Sep/2026 | ✓ |
| 8 | 80 | Passed | Passed | 17/Sep/2026 | ✓ |
| 9 | 81 | Passed | Passed | 17/Sep/2026 | ✓ |
| 10 | 105 | Passed | Passed | 17/Sep/2026 | ✓ |
| 11 | 28 | Passed | Passed | 17/Sep/2026 | ✓ |
| 12 | 110 | Passed | Passed | 17/Sep/2026 | ✓ |
| 13 | 67 | Passed, approved extra ID 8010 retained | Passed | 17/Sep/2026 | ✓ |
| 14 | 94 | Passed | Passed | 17/Sep/2026 | ✓ |
| 15 | 11 | Passed | Passed | 17/Sep/2026 | ✓ |
| **Total** | **988** | **997 active Parts / 143 groups** | **13 SB verified** | **17/Sep/2026** | **✓** |

### Preparation and pre-import history

The original standard-workbook package contained no numbered FIG PDFs. On 17/Sep/2026 the full FIG 1–15 package (100 pages) was received from `C:/Airplane/32-11-15RM ERJ 195 MLG`. Run: `storage/app/codex/manual-package-import/32-11-15RM/20260917-full/`, including source hashes, 300-DPI renders, OCR candidates, and fresh production snapshots. Full visual extraction, Parts reconciliation, group construction and workbook reconciliation are now complete; versioned SQL is ready for the user's manual production import. No production or working-local database data changed. Earlier `audit-pending.md` is historical, superseded by the v01 README and final JSON artifacts.

Fresh read-only production check 17/Sep/2026: 907 active Parts, 39 active groups (FIG 13: 15; FIG 14: 24), 13 SB. The workbook SHA256 matches the previously approved file (`D88BBAD65AC7C9AF21B7BC73C67A17C7C160253D4C64733C26F7E449F187449E`). `workbook-history-verification.json` confirms all 906 prior component IDs/IPL/P/N, previously true flags, and all 13 SB still match. Relative to that 09/Sep postflight, the extra active row is ID 3508 `14-12 / 2821-0102FS02`. Correction to the former ledger note: the unusual IPL is `2821-0222R` (ID 8010, same P/N), NOT `2821-0075`; it already existed in the prior workbook snapshot. `2821-0075` is a valid P/N at `12-110`. Preserve the extra non-PDF row pending a separate reviewed correction.

User confirmed on 17/Sep/2026: 12 conflicting P/N corrections across FIG 1/2/5/6/8/9/10, FIG 10 `10-230`→`10-230A` and `10-330`→`10-330A` while retaining IDs, restoration of five exact soft-deleted torque-link records, and correction of active `10-320A` ID 9099 from upper to lower ASSY name. Recorded in `user-resolutions.json`. Do not restore duplicate soft-deleted ID 3325 while ID 9099 is active; preserve historical references. FIG 10 crossed-out PDF pages 2 and 6 are excluded in favor of TR 32-20 replacement PDF pages 3 and 7 (20/Jan/2025).

Full detail-table visual extraction covers FIG 1–15: **988 source Parts**, 0 structural CSV issues in 15 per-FIG analyzer runs. Artifacts: `reviewed/1.csv`…`15.csv`, `reviewed/source-evidence.json`, `parts-canonical.csv`, `figure-audits/`, and `reviewed-audit.json`. The final plan contains 84 new rows, six restorations, 150 quantity reconciliations, and nine preserved existing-only rows, yielding 997 active Parts. The user separately confirmed all six additional decisions on 17/Sep/2026: `2-640` P/N 1840-0064→1840-0061; `9-30` AN3-11A→AN3-11; IPL moves `7-210`→`7-210B`, `7-250`→`7-250B`, `8-30`→`8-30A`; restoration of deleted ID 3350 `11-200/2821A0700-01`. In total 14 P/N corrections, five IPL moves and six restorations are approved. `user-resolutions.json` and `pending-decisions.json` record the decisions.

`groups-final.json` and `groups-audit.json` contain the validated graph: **143 groups** (60 ASSY, 35 Original/Oversize, 44 Alternative P/N, 4 KIT), 361 options, 235 direct and 139 nested coverages, zero cycles. All 39 existing group IDs and existing option/coverage IDs are preserved; 104 groups are added. Cross-FIG aliases link FIG 12 to the corresponding FIG 13/14/15 ASSYs without flattening their children. Existing FIG 14 coverage Qty for `14-5` changes 2→1 in four ASSYs because PDF AR normalizes to 1. Four direct `14-180B` coverages become nested references to the complete `14-180B/14-180A` alternative group, retaining coverage IDs. Eleven exact legacy composition references are retained/attached; legacy records and WO/TDR/Log Card history are untouched.

**User-approved PDF exception, 17/Sep/2026:** existing-only component ID 8010 (`2821-0222R`, same IPL and P/N) remains in the composition of `MPG-M78-F13-ASSY-1B`, exactly as on production. This inclusion is explicitly authorized, not established by PDF. The component and its historical references remain intact.

`workbook-final-audit/` reruns the previous layout-aware parser against the offline planned 997-Part target. `workbook-resolved.json` resolves 28 parser flags using previously approved decisions, this turn's PDF corrections, and an AC-only SB key collision (two distinct OEM bulletins share `190-32-0037 R4`). Zero unresolved conflicts. Add NDT only for new `10-239`; restored records retain their previous flags. Planned final flags: LC 26, NDT 69, CAD 65, Stress 0, Paint 40, KIT 496. All 13 SB rows match existing production data and are guarded, not updated.

Handoff: `C:/Airplane/32-11-15RM ERJ 195 MLG/32-11-15RM_20260917_v01_import.sql`, matching `_verify.sql` (SELECT only), and `_README.md`. Originals/generators and SHA256 are retained in the run (`handoff-manifest.json`, `generate_final_sql.py`, `expected-final.json`). SQL uses session-local staging tables, gated DML, and conditional COMMIT/ROLLBACK. `sql-local-test.json` proves first import, standalone 12-check SELECT audit, byte-identical second import, six fail-closed/rollback scenarios, and 320 application-resolver cases (64 bundles × five form scopes). Tests use a new isolated local scratch schema, removed afterward; no working application data changed. Production version/CTE support confirmed read-only: MariaDB 10.6.27; still 907 Parts / 39 groups. All 16 immutable source hashes rechecked. **Awaiting the user's import and production postflight; do not mark complete.**

| FIG | Visually reviewed PDF Parts | New rows planned | Restorations planned | New-package production completion |
|---|---:|---:|---:|---|
| 1 | 65 | 4 | 0 | Pending |
| 2 | 71 | 15 | 0 | Pending |
| 3 | 47 | 4 | 0 | Pending |
| 4 | 18 | 0 | 0 | Pending |
| 5 | 90 | 0 | 0 | Pending |
| 6 | 75 | 2 | 0 | Pending |
| 7 | 46 | 0 | 0 | Pending IPL moves |
| 8 | 80 | 9 | 0 | Pending |
| 9 | 81 | 8 | 0 | Pending |
| 10 | 105 | 38 | 5 | Pending |
| 11 | 28 | 3 | 1 | SQL ready; production import pending |
| 12 | 110 | 0 | 0 | Pending; preserve 4 existing-only rows |
| 13 | 67 | 0 | 0 | Groups audited; user-approved ID 8010 exception retained |
| 14 | 94 | 1 | 0 | Groups audited; quantity/complete-family changes in SQL |
| 15 | 11 | 0 | 0 | Four groups ready; production import pending |
| **Total** | **988** | **84** | **6** | **Partial** |

Planned group counts per FIG (not production facts):

| FIG | ASSY | Original/Oversize | Alternative | KIT | Stage |
|---|---:|---:|---:|---:|---|
| 1–4 | 0 | 0 | 0 | 0 | No manual composition groups required; automatic letter-IPL grouping retained |
| 5 | 24 | 0 | 4 | 0 | SQL ready |
| 6 | 4 | 4 | 5 | 0 | SQL ready |
| 7 | 0 | 0 | 0 | 0 | No manual composition groups required |
| 8 | 4 | 0 | 1 | 1 | SQL ready |
| 9 | 2 | 0 | 1 | 1 | SQL ready |
| 10 | 6 | 4 | 13 | 2 | SQL ready |
| 11 | 1 | 0 | 3 | 0 | SQL ready |
| 12 | 11 | 0 | 8 | 0 | SQL ready, including eight cross-FIG ASSY aliases |
| 13 | 2 | 9 | 4 | 0 | Existing graph audited, exception retained |
| 14 | 4 | 18 | 3 | 0 | Existing graph audited, complete nipple family added |
| 15 | 2 | 0 | 2 | 0 | SQL ready; size-selection PIN is Alternative, not Bushing |
| **Total** | **60** | **35** | **44** | **4** | **Production postflight pending** |

The following per-FIG production counts remain current as of the fresh snapshot; none proves completion of the new full PDF package.

| FIG | P | L | N | C | S | Pt | K | A | O | Alt | KG | Opt | Direct | Nested |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 61 | 0 | 0 | 1 | 0 | 0 | 53 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2 | 56 | 0 | 0 | 3 | 0 | 0 | 37 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 3 | 43 | 0 | 0 | 1 | 0 | 0 | 38 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 4 | 18 | 0 | 0 | 0 | 0 | 0 | 4 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 5 | 90 | 0 | 0 | 0 | 0 | 0 | 32 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 6 | 74 | 2 | 9 | 6 | 0 | 4 | 32 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 7 | 46 | 0 | 0 | 0 | 0 | 0 | 36 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 8 | 72 | 1 | 11 | 10 | 0 | 5 | 50 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 9 | 74 | 3 | 3 | 2 | 0 | 6 | 54 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 10 | 62 | 3 | 8 | 10 | 0 | 7 | 34 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 11 | 24 | 0 | 2 | 0 | 0 | 2 | 14 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 12 | 114 | 2 | 5 | 0 | 0 | 2 | 55 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 13 | 68 | 10 | 11 | 11 | 0 | 10 | 18 | 2 | 9 | 4 | 0 | 59 | 19 | 22 |
| 14 | 93 | 2 | 17 | 17 | 0 | 2 | 39 | 4 | 18 | 2 | 0 | 88 | 20 | 72 |
| 15 | 11 | 0 | 0 | 3 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| `2821` anomaly | 1 | 1 | 1 | 1 | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| **Total** | **907** | **24** | **67** | **65** | **0** | **39** | **496** | **6** | **27** | **6** | **0** | **147** | **39** | **94** |

## 32-21-02 — partial

FIG 1–10 and workbook were analyzed, but the retained audit has 42 unresolved source issues. The 15/Sep snapshot had no new Part Groups; on 17/Sep the verified Legacy retirement added one Alternative P/N group at FIG 9 (6 options / 6 direct coverages), without a new PDF reconciliation. Source CSV contains 589 rows; current production contains 608, so this manual must be reconciled again before it can be marked complete.

| FIG | Source P | Prod P | L | N | C | S | Pt | K |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 101 | 101 | 3 | 0 | 0 | 0 | 0 | 75 |
| 2 | 18 | 18 | 0 | 0 | 0 | 0 | 0 | 11 |
| 3 | 30 | 33 | 3 | 5 | 6 | 0 | 5 | 23 |
| 4 | 30 | 30 | 1 | 3 | 7 | 0 | 5 | 22 |
| 5 | 44 | 46 | 0 | 3 | 1 | 0 | 9 | 26 |
| 6 | 20 | 20 | 0 | 0 | 5 | 0 | 3 | 11 |
| 7 | 53 | 56 | 4 | 8 | 7 | 0 | 9 | 19 |
| 8 | 84 | 85 | 11 | 12 | 5 | 0 | 12 | 43 |
| 9 | 91 | 98 | 17 | 23 | 12 | 0 | 14 | 37 |
| 10 | 118 | 121 | 7 | 15 | 10 | 0 | 6 | 33 |
| **Total** | **589** | **608** | **46** | **69** | **53** | **0** | **63** | **300** |

As of 17/Sep/2026, FIG 9 has one Alternative P/N group / 6 options / 6 direct coverages from verified Legacy retirement. The older 15/Sep zero-group inventory above is superseded for this manual; package completion remains partial.

## 32-21-04 Goodrich — partial

The reviewed PDF set contains 704 Parts: FIG 1A 401, FIG 2A 33, FIG 3 30, FIG 4 240. Current production contains 690: FIG 1A 402, FIG 2A 33, FIG 3 30, FIG 4 225. Because of this `+1/-15` divergence and because only six groups exist, the manual requires a fresh PDF and full group reconciliation before completion.

| FIG | Source P | Prod P | L | N | C | S | Pt | K | A | O | Alt | KG | Opt | Direct | Nested |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1A | 401 | 402 | 42 | 25 | 0 | 0 | 13 | 231 | 1 | 0 | 0 | 0 | 1 | 3 | 0 |
| 2A | 33 | 33 | 0 | 0 | 0 | 0 | 0 | 22 | 1 | 0 | 1 | 0 | 13 | 13 | 0 |
| 3 | 30 | 30 | 0 | 11 | 0 | 0 | 4 | 10 | 2 | 0 | 0 | 0 | 2 | 10 | 0 |
| 4 | 240 | 225 | 50 | 52 | 16 | 0 | 15 | 132 | 1 | 0 | 0 | 0 | 1 | 8 | 0 |
| **Total** | **704** | **690** | **92** | **88** | **16** | **0** | **32** | **395** | **5** | **0** | **1** | **0** | **17** | **34** | **0** |

Production currently has 20 Service Bulletins.

## 32-21-06 — partial

PDF FIG 1–3, Parts, workbook flags, and 20 Service Bulletins passed production verification. The reviewed result intentionally preserves four existing-only components, so 505 source rows become 509 production Parts. The new group hierarchy was built and production-verified only for FIG 3; FIG 1–2 still require a documented group-composition audit before the whole manual can be called complete.

| FIG | Source P | Prod P | L | N | C | S | Pt | K | A | O | Alt | KG | Opt | Direct | Nested |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 43 | 45 | 2 | 0 | 0 | 0 | 1 | 11 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2 | 186 | 186 | 32 | 17 | 4 | 0 | 14 | 90 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 3 | 276 | 278 | 57 | 69 | 27 | 0 | 48 | 128 | 12 | 19 | 0 | 0 | 80 | 182 | 49 |
| **Total** | **505** | **509** | **91** | **86** | **31** | **0** | **63** | **229** | **12** | **19** | **0** | **0** | **80** | **182** | **49** |

## Update checklist

Whenever a manual is worked on, update both its summary row and per-FIG section with:

1. Exact manual number and production ID.
2. Source/run directory and manifest; retain PDF/workbook hashes in the run manifest.
3. FIGs supplied, extracted, imported, and group-audited.
4. Source and production Parts counts per FIG.
5. Group counts by type, options, direct coverages, and nested coverages per FIG.
6. Source-required workbook flags and current production true counts; Service Bulletin total.
7. Conflict count, user resolutions, SQL/handoff artifact, production postflight date, and any divergence.
8. Status change only after the evidence satisfies the definitions at the top of this file.
