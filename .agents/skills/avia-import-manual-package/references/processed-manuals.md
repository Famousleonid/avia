# AVIA processed manual ledger

Canonical history of manual-package work. Read this file before answering whether a manual or FIG has already been processed, and update it in the same task after every material import or verification stage.

## Status rules

### 08/Oct/2026 — multi-manual Traveler green-tab batch, PARTIAL SQL handoff

Source `C:/template traveler`: 94 immutable XLSX files, 957 sheets, 291 green tabs in 60 files. Of these, 283 are PART TRAVELER and 8 auxiliary tabs are excluded. Eleven previously processed 4259A/4260A green tabs were skipped; 272 new green Traveler tabs audited. Ready subset: 83 templates / 912 steps across 29 exact manuals, 63 active and 20 inactive for technical review. Remaining 189 tabs are blocked/pending, not executable SQL. User confirmed PCS-3200 maps to NDT-4 for new templates; existing conflicting catalog record unchanged.

Handoff: `C:/template traveler/SQL_20261008/batch-971d65226e/`. Workspace mirror: `output/traveler-batch-20261008/batch-971d65226e/`. Per-manual counts/status are in `manual-statistics.csv`; per-file colors/counts in `statistics.csv`; exact source hashes, cached inventories, matching and cell provenance in `storage/app/codex/traveler-import/batch-20261008/` and source-side `traveler-import-registry.json`. Each manual group contains insert-only guarded `01-import.sql`, SELECT-only `02-verify.sql`, and `templates.json`. No schema changes in this ready subset. Specification remains complete; Description contains only extra Step instructions. Non-green sources are never converted.

Both scoped production and local baseline fixtures passed isolated local SQL tests: 29 groups, 995 exact checks, 83 headers / 912 steps, idempotent rerun, preservation of existing catalog/manual links/templates/steps, and refusal to overwrite a manual edit. Scratch databases removed. Working local database NOT imported; production SQL NOT applied or postflight-verified. Production access was SELECT-only and limited to source-derived manual prefixes. Registry marks preparation separately from application and retains pending sheets to avoid skipping partially processed books.

Open decisions: TEXT storage for 83 source specifications exceeding 255 characters; multiple routes for identical manual/P/N; missing or ambiguous exact Parts/manual matches, sheet-name versus K5 P/N conflicts, unresolved technical process mappings. No Parts/FIG/ASSY/KIT/PRL/SB stage performed or full-manual completion claimed. This is a partial Traveler-only handoff.

### 08/Oct/2026 — 32-11-15RM (78), Description separation cleanup

User approved Specification=requirements, Description=only additional Step instructions. Local guarded transaction cleaned 164 existing steps in 13 templates, retaining header P/N (including manually edited 2821-0222R), availability, IDs, order, Traveler membership and catalog requirements. Existing WO/application snapshots not targeted. Technical qualifiers, material notes and manual additions retained. Runtime bulk/per-step save and template application now use the same conservative duplicate-removal helper; FIG/ZONE validation also considers selected Specification. Global Traveler import skill and source mapper updated; cached green-tab dry mapping still yields 12 templates/151 steps without copying specifications into descriptions.

Fresh SELECT-only production baseline: `storage/app/codex/traveler-import/32-11-15RM/20261008-description/production-before.json`. Handoff `output/traveler-description-20261008/`: guarded 163-step cleanup, SELECT verification, exact guarded rollback, snapshots and README. SQL tested in isolated local schema for apply, verification, repeat rejection, restoration and conflict rollback. Production remains unchanged by agent; user execution/verification PENDING. Disabled excluded 2821-0622 remains disabled; cleanup does not re-import its tab or delete its history. No Parts/FIG/package completion claim.

### 08/Oct/2026 — 32-11-15RM (78), Traveler v2 production postflight PASSED

Pinned current-production SELECT-only audit at 18:43:46 UTC passed all 165 checks: manual identity, 151 replaced steps in 12 template definitions plus all 13 preserved steps of excluded 2821-0622. Header/active/revision/name checks, exact Description line breaks, order, Traveler group membership, all 82 reused process texts/names and manual links match expected v2. Exactly 13 templates/164 steps remain: 5 active, 7 REVIEW, 1 disabled excluded template. 2821-0622 is inactive; its original step IDs/data remain byte-exact. Backup tables contain 13 original headers/164 original steps and match the captured before-state exactly.

Evidence: `output/traveler-32-11-15RM-20261008-v2/production-postflight.json` and `production-postflight-summary.json`, copied to `C:/template traveler/32-11-15RM_20261008_v2/`; source registry now records production_verified for SHA256 `1545395b36a73af7d28dd1fd5f3d539d2863b3804147220012735f48edb8dc61`. No production mutations by agent. This supersedes v2 execution-pending status below; source REVIEW issues remain. No global WO/TDR before/after snapshot was captured, so do not claim an independent full-history invariance audit. No new Parts/FIG/KIT/PRL/SB audit or full-manual completion claim.

### 08/Oct/2026 — 32-11-15RM (78), Traveler replacement v2 SQL prepared

Traveler-only scoped rebuild, not a new Parts/FIG/package audit. Source now `C:/template traveler/4259A_4260A0000-ser   F.xlsx`, SHA256 `1545395b36a73af7d28dd1fd5f3d539d2863b3804147220012735f48edb8dc61`, unchanged from original; cached raw inventory reused. Green P/N tabs only: 12 templates/151 steps (5 active, 7 REVIEW); no process-name prefix in Description, original line breaks/technical qualifiers retained, external steps group 1/AT standalone. Main Fitting original tab maps both 2821-0111 and 2822-0111. No parts/groups/flags/SB changes or full-manual completion claim.

Run `storage/app/codex/traveler-import/32-11-15RM/20261008-rebuild/`; artifacts `output/traveler-32-11-15RM-20261008-v2/` and handoff `C:/template traveler/32-11-15RM_20261008_v2/`. Global skill uses source-side `traveler-import-registry.json` with SHA/rules/cache/artifact hashes and separate prepared/applied statuses. SQL replaces existing 12 production template definitions in place (retains IDs, increments revisions), guards exact current headers/steps, saves transactional backup, and offers exact guarded rollback. Reuses 82 catalog processes; no catalog changes, WO/TDR process changes, or application snapshot updates. User separately approved disabling existing excluded 2821-0622 in TDR while retaining its 13 steps/history; the same SQL now sets active=0/increments revision only for that template. Backups contain 13 headers/164 original steps. Local edited P/N 2821-0222R untouched.

`sql-test.json`: isolated scratch DB replacement, dry-run rollback, 164 exact step checks (151 replaced + 13 preserved), excluded-template deactivation, identical repeat, exact backup restore including availability, changed-data rejection, protected catalog/history checks passed; scratch schema removed. `production-preflight.json`: current pinned production SELECT-only ready=1. Production/current working local DB unchanged; **v2 SQL execution and production postflight pending user**. Previous 07/Oct postflight describes v1, not v2 completion.

### 32-10-03 /79 — production import verified; historical WO scope side effect — 07/Oct/2026

Pinned read-only postflight at07/Oct/2026 20:51:20UTC passed546/546 handoff SELECT checks. Exact Parts/PN/names/quantities, all flags including expected false values,25groups/options,312coverages (29nested),3SB and16-ID LLP order match the delivered SQL. No cross-manual coverage links. Captured Unit/WO scope fields and three resolver hashes unchanged; no production writes by agent. Evidence `storage/app/codex/manual-package-import/32-10-03/20261007/production-postflight.json`, `production-postflight-summary.json`, `verify_production.py`.

| FIG | Parts | ASSY | Direct / nested | LC / NDT / CAD / Paint / KIT | Upload audit | Full completion |
|---|---:|---:|---|---|---|---|
| 1001 | 73 | 13 | 154 /12 | 8 /11 /13 /8 /23 | ✓ | Partial |
| 1002 | 70 | 12 | 129 /17 | 8 /10 /12 /8 /33 | ✓ | Partial |

One confirmed behavioral side effect, NOT a modified WO row: completed WO106840/ID81 has legacy NULL scope_type and Overhaul instruction. Its computed componentQuantities changed from NULL/whole manual before import to {12431:1}, because new head2309-2500-505 now matches the deployed legacyComponentQuantities PN lookup. Production function inspected directly (WorkorderPartScopeResolver.php lines299–335); it does not freeze legacy scope for completed WOs. Active108060/108061 retain explicit full_unit and NULL/whole-manual runtime quantities. No remediation was authorized or applied in this diagnostic turn. Earlier handoff statement about preserved history refers to stored records, not unchanged dynamic scope; do not claim this runtime effect absent.

Full-database before/after comparison is unavailable; cannot certify every unrelated row unchanged. Verified scope is delivered package, captured WO/Unit fields, no cross-manual links and deployed resolver hashes. SQL never writes TDR/log_cards/WO/legacy tables, but historical dynamic rendering may still be affected by the legacy scope lookup. Further diagnosis/fix of106840 remains open. User-deferred illustrated Check Sheet and approved conditional composition/repair exclusions remain open as before; no green complete label. Both HTML journals, journal JSON and Markdown updated only for79 to verified upload with CheckSheet/WO warning. No repeat SQL import required.

### 32-10-03 /79 — tested SQL ready, awaiting upload — 07/Oct/2026

Supersedes the in-progress entry below. User approved service IPLs for seven unnumbered heads, confirmed-only composition with the listed conditional omissions, and deferral of the illustrated Check Sheet. All decisions are persisted in `storage/app/codex/manual-package-import/32-10-03/20261007/reviewed-plan.json`; no remaining decisions within this SQL scope.

| FIG | PDF numbered Parts + service heads | ASSY groups/options | XLS LC/NDT/CAD/Paint/KIT | Production | Complete |
|---|---:|---:|---|---|---|
| 1001 | 69+4=73 | 13/13 | 8/11/13/8/23 | Upload pending | Partial |
| 1002 | 67+3=70 | 12/12 | 8/10/12/8/33 | Upload pending | Partial |
| Total | 143 | 25/25 | 16/21/25/16/56 | Upload pending | Partial |

312 composition links including25 self-head coverages,29 nested links and22 configuration-choice links. Twenty PDF bushings flagged, no unsupported Original/Oversize families. Ordinary suffix alternatives are not duplicated as groups. Same-P/N FIG aliases remain separate FIG-specific groups to prevent group-level LogCard union from leaking the other FIG's BOM. Seven service IPLs1001-0/0A/0B/0C and1002-0/0A/0B explicitly approved; REF quantity→1. All136 numbered Parts retain source quantities. Three SB/AD records:145-32-0004,145-32-0021,2021-02-02; the shared AD keeps both series applicability in one description. Initial manuals.log_card_order derives from10LLP source rows/16Parts; repeat preserves manually changed order by blocking instead of overwriting.

Approved omissions: no root automatic coverage of FIG1001 Axle80/80A + Cardan120/120A/120B conditional pair; no1001-180 bearing270/270A PRE/POST-SB coverage; no1002-155/155A auto-coverage because PDF quantities differ1/2; all Parts retained. Cardan serial branches FIG1002 and nameplates use explicit configuration choices, unselected choices cover nothing.24 workbook repair source rows/tokens absent from PDF excluded. Illustrated Check Sheet deferred separately by user, never substituted with an incomplete text-only template. Full manual remains partial even after this import until deferred work is closed.

Raw SQL tested in an isolated local production-schema fixture (working DB untouched):546 exact SELECT checks,945 runtime quantity/choice/PRL/STD/Work Scope cases,25LogCard composition groups,exact16Part/10family LLP order,identical repeat,7negative guard/postflight rollback cases. Sentinel manual120 and WO/Unit records preserved. Scratch DB removed after tests. Fresh pinned production SELECT snapshot at07/Oct/2026 20:32:53UTC still zero Parts/groups/SB/CheckSheet,orderNULL;3 deployed resolver hashes equal tested local files. Active108060/108061 already full_unit; completed106840 and all history unchanged. No production writes, schema changes or PHP deployment.

Handoff folder `C:/Airplane/32-10-03/`: `32-10-03_20261007_v01_import.sql` SHA256`f084c36dcc9444173fbe7381fa7cac71da131e16f6fb92df6307934009e96c84`; SELECT-only `_verify.sql` SHA256`6e47ac2fb7e9cc98c587a75ab3e21acbbba5459528cf9f41d2f5e0db84de2b89`; `_README.md` with mappings/omissions/instructions. Whole-file import, backup first, require error-free final_status SUCCESS. No DDL, temporary tables, deletes, legacy or WO writes. Both HTML journals and journal JSON updated only for79 to SQL-ready/upload-pending, production counts remain0; no green completion claim. Handoff/source hashes and tests in `handoff-manifest.json`, SQL generator and verification scripts in the run folder.

### 32-10-03 /79 — PDF and workbook reconciliation in progress — 07/Oct/2026

Exact target is Main Side Strut / ELEB, manual79; separate 32-10-03 Safran/manual120 is not in scope. Pinned SELECT-only snapshot at07/Oct/2026 20:02:27UTC: zero Parts, groups, options, coverages, legacy assemblies, SB and Check Sheet for79. No production writes or handoff SQL yet.

| FIG / series | Numbered PDF Parts | Groups | Reviewed XLS flags LC / NDT / CAD / Paint / KIT | Production postflight | Completion |
|---|---:|---|---|---|---|
| 1001 /2309-2500 | 69 | Composition pending | 8 /11 /13 /8 /23 | Not imported | Partial |
| 1002 /2309-3500 | 67 | Composition pending | 8 /10 /12 /8 /33 | Not imported | Partial |

Source PDF `//F519/backup4/ScanFolder/20261007152727640.pdf`, two workbooks `2309-2500-ser.(Overhaul).xlsx` and `2309-3500-ser.(Overhaul).xlsx` from `//F519/backup4/SHOP/PERSONAL FOLDERS/Leonid/WO packages/`. Immutable copies/hashes, all10 rendered pages, visually reviewed transcription with quantity/indent/EFF/page evidence and numbered CSVs are in `storage/app/codex/manual-package-import/32-10-03/20261007/`. FIG1001 pages1–5/printed1011–1015; FIG1002 pages6–10/printed1016–1020. No crossed or duplicate pages. Staging PDF aliases1001.pdf/1002.pdf both contain the unchanged full source, not split figures; `pdf-extraction-evidence.json` records actual page ranges. Every numbered Part has numeric PDF qty1 or2. Ten PDF bushings per FIG; no unsupported oversize groups inferred.

User confirmed exclusion of repair PNs absent from PDF;24 workbook source rows/tokens recorded in `reviewed-workbook-plan.json`. Approved mappings: NDT1001-440→460, NDT1001-80A→2309-3719-003, CAD1001-450→MFI043H7B024, PRL3500 150/155→150A/155A,230→230A,250→250A. KIT only actual source CODE=KIT, quantities remain PDF. LLP source order retained as10 source rows/16 component mappings; production schema has log_card_order, exact baseline value still to capture before SQL. Four SB source rows include shared AD2021-02-02 in both series; reviewed merging still pending. No sample stamps/results/WO identities imported.

Check Sheet has identical four tasks in both workbooks; standard parser misses B16/B24 and two EMF orientation illustrations anchored in drawing3. Reviewed text retained, but template is NOT import-ready and no incomplete text-only template released. User asked whether to defer illustrated sheet or include renderer work. Other pending decisions: service IPL identifiers for seven head rows whose printed ITEM is dash; confirmed-only automatic coverage excluding conditional FIG1001 axle/Cardan pair, PRE/POST-SB bearing and FIG1002-155/155A qty1vs2. No completion claim; final SQL/local tests and user handoff remain pending.

Additional pinned SELECT-only snapshot `production-scope.json` at07/Oct/2026 20:10:11UTC confirms log_card_order=NULL. Active108060/108061 are explicitly full_unit; completed106840 has legacy NULL scope and currently whole-manual runtime coverage. No WO edits authorized or made. `composition-draft.json` contains18 lower ASSY options representing12 distinct physical P/Ns,80 direct/nested links (7nested); checked references/cycles offline, NOT yet runtime-tested or released. Same-P/N FIG aliases retain per-FIG composition; no suffix-only alternative groups. Pending omissions remain explicitly unapproved in the draft. Files and user decisions are persisted; no final production SQL yet.

### 32-51-03 /97 — production upload verified — 01/Oct/2026

Pinned SELECT-only audit at01/Oct/2026 18:37:30UTC:79/79 exact handoff checks passed, zero differences. Additional comparison confirmed all35 old IDs and unrelated identity/name/legacy fields,9legacy links,Unit and WO scope fields unchanged. Exact final flags:LC11 (7source +4preserved),NDT9,CAD0,Paint1,KIT24,is_bush3; no unexpected flags. Five ASSY groups/options and16direct coverages, exact variant restrictions, quantities and2OEM SB match handoff. Deployed resolver hashes unchanged. No production writes by Codex.

| FIG | Production Parts | ASSY / options / direct / nested | Workbook flags / SB | Production verification | Full completion |
|---|---:|---|---|---|---|
| 1 | 53 | 5 / 5 / 16 / 0 | Verified / 2 SB | ✓ 79/79 | Partial: Check Sheet deferred |

Evidence: `storage/app/codex/manual-package-import/32-51-03/20261001/verify_production.py`, `production-postflight.json`, `production-postflight-summary.json`. Import scope complete; no repeat upload required. Full package remains partial solely for explicitly deferred illustrated In-Process Check Sheet. Earlier SQL-ready entry is historical and superseded by this verification.

### 32-51-03 /97 — FIG1 + XLS SQL ready — 01/Oct/2026

| FIG | Parts extracted | ASSY / options / direct / nested | XLS source flags | SB | Check Sheet | Production postflight | Completion |
|---|---:|---|---|---:|---|---|---|
| 1 | 53 (35 preserved IDs + 18 new) | 5 / 5 / 16 / 0 | LC7, NDT9, CAD0, Paint1, KIT24 | 2 | Deferred by user | Pending user upload | Partial |

Source `C:/Airplane/32-51-03/`: `1.pdf` six pages (FIG1 table1005–1007/1008, alphabetical index1003, drawing1004, last intentionally blank), `52350-series.xlsx`. Every supplied page visually reviewed at300DPI. Detailed 1-190 AR overrides alphabetical index qty1, both normalize1. All53 IPL/P/N/quantities reconciled; no PN conflicts. Root unit configuration rows1/1A/1B/1C preserved without inventing a root BOM. Physical subassemblies130,290,290A,320,320A have16direct members; explicit PDF EFF290/290A and320/320A enforce exact310/360 variants via expand_ipl_family=0. No oversize supplied; original bushings160/330/340 marked, ordinary suffix families not duplicated as groups. Legacy9records retained, valid403/404 referenced. Component5314/5315 quantities corrected from erroneous IPL strings to1; old legacy free-text records preserved.

Run `storage/app/codex/manual-package-import/32-51-03/20261001/` contains source-manifest,rendered,1.csv,parts.csv,extraction-evidence,workbook.json,analysis,reviewed-plan,production snapshots,generator,79SELECT checks,sql-local-test,handoff-manifest. Analyzer's two LLP prefix issues resolved by visually/read-source confirmed name+pattern: Cylinder52352-10_=>105/107 and Housing52351-10_=>103/105; Piston52355-=>1/3. Seven source LC flags plus four existing root LC flags preserved=>11final. NineNDT,24explicitKIT,onePaint,twoOEMSB52350-32-71/-74 with the same source AC670BA-32-037 kept distinct. PRL ALT blank CODE never carried down. No unsupported existing KIT marks.

User explicitly chose "SQL остальных данных, Check Sheet позже" on01/Oct/2026. `IN PROCESS CHECK SHEET (2)` has four tasks and LH/RH hydraulic-port orientation images with variable-height block; importer/renderer needs extension before this separate stage. No text-only incomplete template imported. Current production lacks newer manuals.log_card_order column; handoff changes LLP membership only, not UI order configuration.

Pinned read-only production snapshot01/Oct/2026 18:26:17UTC:35Parts,0groups,9legacy,0SB,0CheckSheet. Scope diagnostic18:34:48UTC: active107971/107972Repair remain unrestricted;108023/108025 explicitly full_unit; no active scope change needed. Completed107804Overhaul has legacy inference and gains a match52352-7 after import; no historical WO records modified. Runtime coverage/composition hashes match local. Working DB and production untouched. Isolated raw SQL test passed79exact checks,5composition resolver checks,exact flags,identical repeat,wrong-manual/PN/quantity/composition and injected-postflight rollback.

Final handoff saved and hash-verified in source folder: `32-51-03_20261001_v01_import.sql` SHA256`6036007e4b6b8493eb04623f14189c03889fb60b761535f729adce38cb992d2e`, SELECT-only `_verify.sql` SHA256`b5fb2a6bfc5914cba09d36061909fe8ef7904472f74b0d263c1536fd56a190c9`, `_README.md`. PDF SHA256`0a5960fae6b2b7d2a309f35995d7d364ea8e64b14e9372308751dd6b47f1daff`; XLSX SHA256`8bae6147fe43ae4600dca6fb92f89741b275fd7a1226aa0c84df7f618d7fdf22`. No unresolved conflicts within delivered SQL scope; full completion withheld pending upload/postflight and deferred CheckSheet.

### 32-11-34 /76 — production upload verified — 29/Sep/2026

Fresh pinned SELECT-only audit at29/Sep/2026 21:33:09UTC passed738/738checks with zero differences from delivered v01SQL. Active Parts521 plus preserved deleted ID2990;52groups (38ASSY+14oversize),72options,2847coverages;28legacy and6SB unchanged;CheckSheet absent. Existing55IDs and exact process flags preserved (LC27,NDT22,CAD19,Paint21,KIT0,Stress0);PDF bush classification30. Deployed four coverage/composition/configuration/scope resolvers match locally tested code. WO107870/355,107871/356,107777/243 have explicit full_unit, no component/option restriction, and actual runtime componentQuantities=null (whole manual). Other recorded WO/Unit scope guards passed. No production writes by Codex.

| FIG | Production Parts | ASSY | Original/Oversize | Options | Coverages | Import verified | Full manual completion |
|---|---:|---:|---:|---:|---:|---|---|
| 1 | 258 | 18 | 3 | 24 | 952 | ✓ | — |
| 2 | 263 | 20 | 11 | 48 | 1895 | ✓ | — |
| Total | 521 | 38 | 14 | 72 | 2847 | ✓ | Partial |

Evidence `storage/app/codex/manual-package-import/32-11-34/20260929-pdf/`:verify_production.py,production-postflight.json,production-postflight-summary.json,journal-postflight-update.json,handoff-manifest.json. Both HTML journal copies,Markdown and journal JSON now show actual production521Parts/52groups and `Загрузка ✓; нет XLS, исключения ASSY`, verification29/Sep/2026; other manuals' data/progress unchanged. No repeat import needed. Earlier awaiting-upload entries are historical, superseded here. Full completion is NOT claimed: XLS/CheckSheet absent and approved five FIG1 internal-composition/FIG2 conditional-pair omissions remain. No all-database unchanged assertion: verification scope is the package and preserved baseline records, not an unavailable full-database before/after snapshot.

### 32-11-34 /76 — PDF-only SQL ready, awaiting user upload — 29/Sep/2026

Final files saved and hash-verified in `C:/Airplane/32-11-34/`: `32-11-34_20260929_v01_import.sql`, SELECT-only `_verify.sql`, `_README.md`, and unchanged `1-2.pdf`. Import SHA256 `8354bc28abf2ebd047d71d2274b2b60400dd324a443963ba9af95941c6b4d9e3`; verify SHA256 `871a6b1a50e80c511d6ed37e0dcfd52a04f5b310d6173f2570222db0829b2ba0`. No production writes, migrations, DDL, temporary tables, deletions or PHP deployment. Run whole file after backup; require error-free final_status SUCCESS.

| FIG | PDF Parts | New Parts | ASSY | Original/Oversize | Options | Coverages | XLS / production | Complete |
|---|---:|---:|---:|---:|---:|---:|---|---|
| 1 | 258 | 237 | 18 | 3 | 24 | 952 | XLS absent; upload pending | — |
| 2 | 263 | 230 | 20 | 11 | 48 | 1895 | XLS absent; upload pending | — |
| Total | 521 | 467 | 38 | 14 | 72 | 2847 | Partial | — |

Source UNC `//F519/backup4/ScanFolder/1-2.pdf`,617691bytes,SHA256`ec0939ffca44b08df9e6726a62b618c858f9cfa3d74eec84cb87f8796711169e`,22pages,all visually reviewed at300DPI. Printed FIG1 tables10028–10037 and FIG2 tables10045–10054; only drawing sheet14/14 FIG1 and sheet1/7 FIG2 supplied. No crossed duplicates. Raw quantities/EFF/indentation/page evidence retained. Numeric PDF quantities used; AR/RF/blank→1. Existing matched names retained; conflicting LH/RH head labels are not used to guess applicability: explicit P/N/EFF drive the graph.

Run `storage/app/codex/manual-package-import/32-11-34/20260929-pdf/`: source-manifest, reviewed-tokens, parts-evidence,1.csv/2.csv/parts.csv,component-plan,groups-draft,independent groups-audit,confirmed-decisions,expected-checks,expected-final,sql-local-test,handoff-manifest. CSV validated separately because standard workbook analyzer requires XLS; no fictional workbook. SQL tested against isolated production-schema fixtures:738exact SELECT checks,identical full raw-file repeat,2160quantity/scope/variant resolver cases,52LogCard composition cases,10negative guards and postflight rollback PASS. Independent PDF/EFF recursion passed216graph cases. Scratch removed; working DB untouched.

Fresh pinned read-only production snapshot29/Sep/2026 21:24:44UTC unchanged:54active+1deleted Parts,0groups,28legacy rows,6SB,0CheckSheet. Preserve all55 IDs, deleted duplicate2990, legacy/SB and existing process flags. Expected521active/522totalParts. No XLS flags or Check Sheet added. Ordinary letters not duplicated as Alternative groups. Six physical FIG2 head P/Ns include their FIG1 IPL aliases as options with identical composition, not duplicate physical assemblies. Lower Torque Link2-1370/1370A uses explicit choice slots; no selection does not cover either incompatible child composition.

User approved explicit `scope_type=full_unit` only for active WO107870/ID355,107871/ID356,107777/ID243, preserving pre-import whole-manual coverage. Adding absent head P/Ns would otherwise activate legacy Overhaul single-component inference. Done-at/scope guards block import if these WOs changed/completed; completed WOs, other WOs,9Units,TDR/history unchanged. Transactional-engine and unexpected-trigger guards include workorders.

Approved omissions, all Parts retained: no internal compositions for1-1480,1-1630/1640,1-1750/1760; no upper automatic coverage of conditional bearing/gland2-1540/A+2-1590/A(SB32-007) or seal/back-up2-1570/A/B/C/D. Missing drawings/quantity allocation remain future composition work, not unresolved permission blockers. Partial until XLS stages, composition exceptions and post-upload production audit are resolved; do not mark green. Both HTML journal copies, journal JSON and Markdown updated for76only with actual54productionParts/0groups and label `SQL готов; ожидает заливки`; other manuals unchanged.

### 32-30-23 /135 — reported EFF link checked — 29/Sep/2026

User reported missing21500-107 ->2-200A/21510-3. Fresh pinned snapshot19:24:55UTC in `20260929-full/production-eff-diagnostic.json` proves the link EXISTS for active PDF head21501-107 (EFF D), group884: option2010/1-2C coverage16480 and option2011/2-1C coverage16598 both nest2-200A/21510-3 (EFF BCDE),qty1. Both heads also nest2-95A and2-415. PDF pages10015/10021 visually rechecked:21500-107 row1-1C explicitly DELETED; active row1-2C is21501-107. Existing unit629/WO107997 still uses21500-107/full_unit, intentionally preserved. No21500-107 group exists; do not silently equate/rename these P/Ns. `eff-diagnostic.md` records exact evidence. Local editor shows nested links separately under collapsed Included groups; user's screen remains unobserved, so no UI root cause claimed. No data/code changes or duplicate links; this targeted check does not replace prior224-check audit.

### 32-30-23 /135 — production upload verified — 29/Sep/2026

After user reported upload, pinned current-production SELECT audit at29/Sep/2026 17:42:12UTC passed224/224 exact checks. No differences from `32-30-23_20260929_v01_import.sql`:179activeParts,10ASSY,15options,1175coverages,3SB,1CheckSheet. Per-FIG:FIG1=54Parts/5upperASSY/18KIT/2LC;FIG2=125Parts/5lowerASSY/42KIT/7LC/46NDT/4CAD/12Paint. Full flag totalsLC9/NDT46/CAD4/Paint12/KIT60; no unexpected flag differences. Existing6componentIDs,2legacy rows and explicit full_unit unit629/WO107997 scope pass preservation checks. CheckSheet exact JSON/source/content hashes pass; deployed coverage/composition/configuration resolvers match locally tested versions. No production mutations by Codex.

Evidence in `storage/app/codex/manual-package-import/32-30-23/20260929-full/`: `production-postflight.json`, `production-postflight-summary.json`, `verify_production.py`, `journal-postflight-update.json`, updated `handoff-manifest.json`. Both `output/manuals-production-journal.html` and compatibility `output/manuals-production-20260915.html`, Markdown and journal JSON refreshed for135only; other manual data/progress unchanged. Current label: `Загрузка ✓; исключения ASSY FIG1`, expectedFIG2, Parts2/2, fully covered FIG2=1/2. Do not repeat import.

SQL upload is complete within the agreed scope; full composition remains partial, not a green full-manual completion: the14approved mixed-washer/RFsensor Parts in FIG1 are intentionally excluded from upper ASSY automatic coverage. They are present in Parts, not missing from the import. External-CMM internals remain outside this source package. Prior awaiting-upload entries below are historical and superseded by this verification.

### 32-30-23 /135 — SQL ready, awaiting user upload — 29/Sep/2026

User approved ten PRL KIT mappings to PDF P/N and confirmed-only upper ASSY coverage. Final tested files saved in `C:/Airplane/32-30-23/`: `32-30-23_20260929_v01_import.sql`, SELECT-only `_verify.sql`, `_README.md`. Import SHA256 `6455d8ceb659d93c9ab085de8827d2c10b3f1b7cb8ced89165994f70e6d34337`; verify SHA256 `129c0d0a03104753b67bcfed9226c2882fbdbb7694ccb4b65c362a96c8732be5`. No migration, DDL, temp tables, deletions or PHP deployment required. Run whole file after backup; require error-free final_status SUCCESS.

| FIG | PDF Parts | New | ASSY groups | Production verified | Complete |
|---|---:|---:|---:|---|---|
| 1 | 54 | 52 | 5 heads spanning FIG1+2 | Awaiting upload | — |
| 2 | 125 | 121 | 5 lower | Awaiting upload | — |
| Total | 179 | 173 | 10 | Pending | — |

Run `storage/app/codex/manual-package-import/32-30-23/20260929-full/`: confirmed-decisions.json, workbook-plan.json, groups-draft.json, independent PDF/EFF groups-audit.json, expected-checks.json, expected-final.json, handoff-manifest.json, sql-local-test.json and reproducible scripts. 15options/1175coverages: five head P/Ns each have FIG1/FIG2 IPL aliases with identical composition, not two physical nested units. Complete uniform ordinary letter families use one representative, no redundant Alternative groups. Exact EFF and unequal variant quantities use existing compatibility support. Lower ASSY2-95/95A,2-200/200A,2-415 nested explicitly.

Source flags45NDT/4CAD/10Paint/7LC/60KIT; baseline-preserving totals46NDT/4CAD/12Paint/9LC/60KIT. KIT only explicit CODE. PDF quantities retained, including2-265E=2,2-405=1; AR/RF=1 with raw evidence.3SB OEM700-32-002,700-32-007 R1,700-32-017. Check Sheet2tasks(stages5/7)+3blanks+8legend; contentSHA256 `0701d8ef6f5ca5bd570c645f8e7e7479177428db8e388c76413b263ec1fcc818`; no sample identity/stamps.

Fresh pinned read-only production baseline29/Sep/2026 17:31:52UTC unchanged:6Parts/0groups/0SB/0CheckSheet/2legacy. Preserve IDs10705–10710, legacy634/635 and explicit full_unit unit629/WO107997. Isolated production-schema test:224SELECT checks,150PRL/STD quantity-scope cases,10Log Card composition cases, identical raw-file repeat,9negative guards and injected postflight rollback PASS. Extra existing flags/unrelated manual preserved. Scratch removed; working local DB and production untouched.

Approved omissions from upper ASSY automatic coverage:1-25/25A/26/26A,1-50/50A/51/51A,1-115/115A/116/116A,1-80/135. All14 Parts imported; external harness/tube internals not inferred. Full installed BOM with disputed links remains uncertified. Current status partial/pending production audit, not green/complete. Earlier pending-decision notes below are historical, superseded by this handoff.

### 32-30-23 /135 — XLS mapped, decisions pending — 29/Sep/2026

User supplied `C:/Airplane/32-30-23/21500 (21501)-ser..xlsx` and requested fullSQL. SourceSHA256d64f0a16616b09a2499d8dcd2e39b9a3791a881e1c07d6074a13facbf1e8bc4e. Run `storage/app/codex/manual-package-import/32-30-23/20260929-full/`:standard analyzer outputs, workbook-cells.json, workbook-plan-pending.json, reconcile_workbook.py, review-status.md. Fresh pinned SELECT17:08:59UTC:6activeParts unchanged,0groups,0SB,0CheckSheet; existing6LC/4NDT/2Paint preserved.179PDFParts from prior stage unchanged.

Workbook reviewed:45NDT (explicit P/N prefixes resolved across their numericIPL family),4CAD (both21512-1/-2),10Paint,7LLPLprimary-member flags,50unambiguousKIT+10pending P/N remaps. LLPL actual sheet resolves analyzer missingLLP;125missingFIG warnings are filename false positives because1.pdf contains FIG2. ExplicitKIT ALTrow50 mapped2-280A;PRL66maps2-430and235;blankCODE trailing rows excluded.3SB OEM700-32-002/700-32-007R1/700-32-017;CheckSheet2tasks5/7+3blankslots+8legend, source instructions retained, sample stamps/WOidentity not imported. No final import-ready count claimed until decisions/reconciliation.

User confirmation requested for10PRL P/N conflicts (retain PDF and mapKIT), and confirmed-only top-level coverage omitting mixedARwasher sets/RFexternalCMMsensors pending composition evidence. Lower5ASSY reviewed;upper5configuration mapping/aliasing/quantity/runtime scope checks and testedSQL still pending. No final SQL issued or DB changed;partial. See review-status.md for exact proposed mappings and next steps.

### 32-30-23 /135 — PDF stage extracted — 29/Sep/2026 — XLS pending, no import

User requested PDF first, workbook later. Source `C:/Airplane/32-30-23/1.pdf` (488239bytes, SHA256`fc4c0e53708c50eddf06c2a502bd772bbf9eed7ff79460866dc56e0ebb1cd2c4`) contains TWO printed FIGs1/2,12pages10013–10024, all02/May/2025, Triumph Yakima21501series. All12pages visually reviewed:4drawings/8tables, no duplicate/crossed pages. Five DELETED21500-series headings excluded. Run `storage/app/codex/manual-package-import/32-30-23/20260929-pdf/`: source manifest, renders, reviewed-rows.psv,1.csv/2.csv/parts.csv, parts-evidence.json, component-plan.json, assy-plan.json, pdf-review.json, README.md, reproducible build_pdf_stage.py.

179reviewed Part rows:FIG1=54,FIG2=125; includes5physical head P/Ns repeated as RF head references in FIG2, not10physical heads. Numeric quantities retained;13AR+11RF→1 with raw evidence.2-265E qty2 retained;2-405 qty1 retained despite drawing. PART No. column retained including1-36M85052-2-8 vs conflicting TRUE P/N M85052/2-6 note. EFF A–E/indentation/pages recorded. Pinned production SELECT29/Sep15:38:47UTC:manual135Side Brace Actuator Assy MLG,6active Parts (2FIG1/4FIG2),0groups; all6IPL/P/N/qty exact, IDs10705–10710 retained in plan;173proposed new rows. No identity conflicts, no DB changes. Baseline only selected identity/qty fields, not full flags/history backup.

Five lower ASSY compositions reviewed:2-95/95A,2-200/200A,2-415; attaching parts remain parent-level. Five head configurations drafted by EFF, not import-ready: need cross-FIG head alias mapping, mixedARwasher quantities (up to2per bolt), per-variant quantity2-265E, RFsensor ownership/externalCMM boundary and pre-import WOscope audit. No invented oversize groups (none shown) or redundant ordinary-letter alternative groups. Workbook analyzer not run because it requires missing XLS; separate PDF validation passes. Flags/SB/Check Sheet pending XLS and untouched. No SQL handoff yet; partial, not production-complete. Next: accept user's workbook, reconcile flags/SB/CheckSheet and resolve BOM implementation details, then tested guarded SQL handoff.

### 32-11-01RM /68 — bushing group presence checked — 29/Sep/2026

Pinned SELECT-only current-production check at12:46:01UTC confirms28active `oversize` groups (IDs738–765), each with active options linked to non-deleted Components: FIG8=4, FIG10=5, FIG11=19. Examples:8-230 includes8-230/231/232/233/234;10-80 includes10-80/81/82/83/84;11-10 includes11-10/11/12/13/14. Deployed ManualController loads groups/options and maps badges by option component_id; Parts row template renders non-ASSY groups in Group, distinct from Assy. No missing bushing-group records established. User reports groups not visible, but exact page/IPL and rendered browser state remain unconfirmed; do not label the UI issue fixed. No production mutations; full1631-check import suite not rerun.

### 32-11-01RM /68 — production status refreshed — 25/Sep/2026 — imported within approved scope, full composition not certified

Pinned SELECT-only production snapshot 25/Sep/2026 17:38 UTC: manual identity `32-11-01RM`/ID68; 1048 active and 87 archived Parts; active FIG counts 1=95, 2=116, 3=47, 4=25, 5=97, 6=156, 7=107, 8=115, 9=65, 9A=71, 10=46, 11=97, 12=11. Active groups 138 (98 ASSY, 28 oversize, 12 alternative), 294 options, 4230 coverages. Active flags LC30/NDT69/CAD98/Paint36/KIT527; 17 SB. Check Sheet ID8 exists with previously verified content SHA256 `ba0ab872564a2d1667ad645d5cc28dd0b24c535648d1237d882648925d0f9243`. These exact aggregates match the 23/Sep postflight, which passed 1631/1631 detailed SELECT checks; the detailed check suite was not rerun in this status turn. Source folder still contains all 13 FIG PDFs (1–12 plus 9A), workbook, final import and SELECT-only verification SQL.

No repeat production import is indicated by this inventory check. PDF Parts, reviewed quantities, workbook flags/SB and Check Sheet were imported and verified within the agreed scope. Preserve 126 existing-only active Parts, 93 previously true KIT flags without a current explicit PRL match, all legacy/history rows, and the user-approved FIG6 crossed-out page10118 exception. Full installed ASSY composition remains **not certified/partial**: 145 ambiguous parent/member entries were deliberately omitted from the high-level assemblies under the confirmed decision, including variant-dependent lower ASSY/SB configurations. This is not evidence of lost SQL rows. The separate five-WO Work Scope incident was repaired and verified 24/Sep; it did not change manual package contents. Production unchanged by Codex in this status check.

### 32-11-01RM /68 — runtime scope regression found — 24/Sep/2026

Repair production verified24/Sep/2026 08:43:41Toronto: user executed scoped SQL08:42:32, activity42479–42483. All five WO100500/107500/107608/107459/107775 now full_unit; deployed read-only partial matches all61saved lines to candidates again. Exact pre/post row payloads unchanged for61bushing lines,105processes,20batches,202TDRs,3LogCards,186STD rows. Files:storage/app/codex/scope-repair-before-20260924.json and scope-repair-after-20260924.json. Only expected WO scope_type/updated_at changes. Existing normal process-only display excludes rows without assignments:100500 has2process-bearing rows of3saved;107775 has2of28; no lost assignments. Five-WO incident repaired; global legacy inference issue/87other WO audit remains unresolved, no PHP change or production writes by agent.

24/Sep follow-up: user authorized repair preparation. Saved full five-WO preflight (workorders,bushing lines/processes/batches,TDR,LogCard,STD) at storage/app/codex/scope-repair-before-20260924.json. Prepared output/sql/scope_repair_32-11-01RM_20260924_v01.sql + SELECT-only verify + README. Exact SQL passed isolated MariaDB10.6 raw execution,5negative guards,mid-operation rollback,repeat/audit/preserved payload tests; target transaction tables verified InnoDB. User execution pending; production untouched. Global PHP inference removal paused for user choice because87additional other-manual legacy WO currently have inferred Part scopes; broad default-full change would affect these and requires separate audit/preservation, not an unreviewed fix. No application code change shipped.

Read-only all-WO audit (466 including4soft-deleted;8primary manual68) against pre-import production-current.json23/Sep16:52:49UTC found imported head Parts changed legacy Overhaul inferred scope from full manual to a single matching Component. Five WO still affected:100500,107500(closed),107608,107459(closed),107775. Their61saved bushing lines/105process assignments remain but deployed partial filters all out. W107736 also affected initially; activity42468 records Ad ministr27 explicitly setting full_unit24/Sep08:21:17Toronto, outside this audit;15lines/49processes now pass.107723(Repair) and108001(explicit full_unit) scope unchanged. No external WO scope refs to manual68Components/options. Existing STD snapshots at107500/107608/107775 remain; deployed read-time rebuild may discard them under wrong scope, so print/rebuild methods were not invoked. Package field/FK postflight remains valid but does NOT imply runtime WO compatibility: incident unresolved for five WO. Evidence/report:storage/app/codex/import-scope-audit-20260924.md and corresponding JSON;import-scope-followup-20260924.json. No production mutations or app code fixes. Future import acceptance must compare inferred Work Scope for existing WO when adding/restoring head Parts, not only IDs/FKs and explicit scope fields.

### 32-11-01RM /68 — production verified — 23/Sep/2026 — agreed import complete, composition exceptions retained

User reported SQL upload. Pinned SELECT-only production postflight17:47:23UTC passed1631/1631 with0failures; expanded boundary audit17:49:40UTC found0exact Component-field differences,1104oldIDs preserved,26restores+31creates,1048active+87archived Parts,138groups/294options/4230coverages. Flags LC30/NDT69/CAD98/Paint36/KIT527; CheckSheet exact JSON/source/content hash verified. All17SB and57legacy rows match baseline exactly; historical FK reference counts (including TDR) unchanged. Approved page10118 exclusions and all126existing-only active Parts retained. Per-FIG production Parts/groups:1=95/11,2=116/0,3=47/0,4=25/14,5=97/30,6=156/9,7=107/4,8=115/17,9=65/9,9A=71/9,10=46/9,11=97/23,12=11/3.

Evidence in run20260923-full:production-postflight.json,production-after-import.json,production-boundary-audit.json,production-verification-report.md. SQL exact hash matches tested artifact; updates restricted to Components manual68/exactIDs, insert targets restricted to that manual's Parts/groups/options/coverages/CheckSheet;0cross-manual links and0DBtriggers on modified tables. No full pre-import snapshot of other manuals exists: do not claim bytewise unchanged whole production DB; boundary evidence and preserved target-history checks support the limited conclusion. Production was read only during this audit.

Both output/manuals-production-journal.html and manuals-production-20260915.html updated for manual68 only, preserving every other manual/progress setting; status "Загрузка ✓; согласованные исключения ASSY". All13 FIG imported-package checks pass, but full installed composition is not certified:145 omitted parent/member entries and excluded FIG6page10118 remain explicit; do not label fully green without these qualifications. Handoff manifest updated toproduction_verified_confirmed_links_only; no repeat import required.

### 32-11-01RM /68 — tested full-package SQL handoff — 23/Sep/2026 — awaiting user import, confirmed-links scope

User approved LLP32 both6-731/6-660B, CAD(BUSHING)29/30 PDF P/N remapping and omission/reporting of ambiguous head-ASSY links (`1 да 2 да`). Final run `storage/app/codex/manual-package-import/32-11-01RM/20260923-full/`; handoff folder `C:/Airplane/32-11-01RM 2801A0000 ERJ170 MLG/`: `32-11-01RM_20260923_v01_import.sql`, `_verify.sql` (SELECT only), `_report.md`. No new schema/DDL/temp tables. Do not mark production complete/green before postflight; conditional composition omissions remain explicit, not a certified complete SB configuration.

922 reviewed PDF Parts:865 active-ID reconciliations,26 exact archived-ID restorations,31 creates. Expected1048 active+87 archived=1135total;126 existing-only active Parts preserved. 138groups=98ASSY+28oversize+12alternative,294options,4230edges. Per-FIG source Parts/groups:1=95/11,2=106/0,3=37/0,4=15/14,5=58/30,6=134/9,7=94/4,8=105/17,9=63/9,9A=63/9,10=44/9,11=97/23,12=11/3. FIG2/3 direct members belong to head groups, not invented standalone assemblies.

XLS-required flags LC23/NDT47/CAD92/Paint25/KIT434; expected minimum final flags with old flags retained30/69/98/36/527. 93existing KIT flags not supported by current mapped PRL remain unchanged and are listed in report. All17SB and57legacy rows preserved exactly. Check Sheet3tasks atstages5/5/7,2blankslots,8legend; XLS SHA256071ea8ba3983501a55d2d7cff76d556eda584e6f6a2a2a97bd42b7fd57d9791b, content SHA256ba0ab872564a2d1667ad645d5cc28dd0b24c535648d1237d882648925d0f9243. Page10118 exception remains unchanged with no new coverage/flags/qty inferred.

Exact raw SQL/comments passed1631SELECTchecks, identical repeat,980coverage quantity/scope cases,98LogCardcomposition cases,7rollback guard scenarios, preservation tests. Isolated scratch DB removed, working DB untouched. Import SHA2565e9f62e1bca19cb398f8d311529d5c9e344c72c7415c50f6e50943705e482bc4; verify SHA256578d67585151d723e5b2c4cae5e6d456fc6f5eaab9d3e3ec01db4be2c63fcc16. Fresh pinned production snapshot23/Sep/2026 16:52:49UTC exactly matches baseline (991active,113archived,0newgroups,17SB,57legacy,0CheckSheet). No production writes or post-import claim. `verify_production.py` prepared for SELECT-only audit after user upload.

### 32-11-01RM /68 — restore/reference audit and lower-group draft — 23/Sep/2026 — partial

Run20260923-full: pinned production read-only schema/FK audit at15:57:13UTC confirms components and57legacy rows unchanged from baseline. `component-plan.json` reconciles922 source Parts:865 active updates,26 exact archived-ID restores,31 creates,1048 expected active rows; no identity conflicts. Restored1-90 ID3779 and5-130 ID2268 have existing TDR references, preserved by ID. All unselected historical rows retained. `groups-draft.json` currently28oversize+12alternative+74ASSY /296edges, not final root composition. Exact legacy references linked where identity matches; incorrect historical legacy P/N retained without using them as authority. Existing-only letter variants excluded from new restricted PDF coverage. User approved six XLS remaps; LLP32 andCAD(BUSHING)29/30 remain pending. Requested confirmation to omit/report ambiguous top-level variant links rather than infer configuration. No database writes, no full SQL handoff, no raw SQL tests yet; status remains partial. See current `review-status.md` and `production-schema-references.json` for evidence.

### 32-11-01RM /68 — all13 FIG row extracts assembled — 23/Sep/2026 — partial

Run20260923-full now contains922 visually reviewed PDF Parts in `parts.csv`, with raw source quantities/indentation/applicability in per-FIG TSV or9A evidence. Counts: FIG1=95,2=106,3=37,4=15,5=58,6=134,7=94,8=105,9=63,9A=63,10=44,11=97,12=11. Analyzer executed for every FIG and aggregate. User additionally approved8-215B/8-320B AS15001-IP→AS15001-1P and9-310B M274262013D→M2742620130D with IDs/history preserved. User approved six specific XLS row mappings in confirmed-decisions.json. Offline workbook reconciliation fixes package-specific sharedFIG9/9A and splitCAD parsing; global analyzer has not been changed. LLP32 and CAD(BUSHING)29/30 approval remains pending. Approved page10118 preservation remains excluded from writes. ASSY graphs, full SQL and raw SQL tests remain unfinished; no DB import or green completion.

Important correction to earlier SB audit: matching byAC+OEM resolves all17 source rows against existingIDs. AC170-32-0021 R2 has distinctOEM recordsIDs2/3; AC170-32-0060 R1 has distinctOEM recordsIDs5/10. Do NOT overwriteIDs3/10 with another source row as the earlier faultyAC-only comparison proposed. All17 metadata rows match exceptID13 AWD date representation43776 versus11/7/2019 (same07/Nov/2019); preserve stored representation. This supersedes the earlier SB-conflict plan, not user data.

### 32-11-01RM /68 — FIG6 excluded-page decision — 23/Sep/2026 — partial

User explicitly answered `как в продакшин` for crossed-out printed page10118 /6.pdf page10, for which no uncrossed replacement is supplied. Preserve existing production data, quantities and relationships for6-735,6-736,6-740,6-750,6-760,6-770,6-780A,6-780B,6-790; do not create new ASSY coverage or infer missing data from that page. Scoped decision persisted in `storage/app/codex/manual-package-import/32-11-01RM/20260923-full/confirmed-decisions.json`. This resolves the source-page question by an approved exclusion, not by certifying its PDF content. No production changes; full SQL handoff remains pending.

### 32-11-01RM /68 — six FIG Parts extracts recorded — 23/Sep/2026 — partial

Run `storage/app/codex/manual-package-import/32-11-01RM/20260923-full/`: manually reviewed per-row source evidence and canonical CSVs now cover FIG1=95, FIG2=106, FIG9A=63, FIG10=44, FIG11=97, FIG12=11 (416 source Parts). `reviewed-parts/*.source.tsv` retains printed page, raw quantity, hierarchy, applicability and notes; FIG9A has separate evidence JSON. `build_reviewed.py` compares these to the pinned23/Sep snapshot without DB writes. FIG1:84 active matches,11 missing/archived candidates (restore audit pending); FIG2:106 matches,10 existing-only REF headers preserved; FIG9A:60 matches,1 approved PN correction,2 archived assembly candidates and8 preserved REF headers; FIG10/11/12:44/97/11 matches and13/16/5 quantity differences respectively. FIG10's2 existing-only repair rows are preserved. No unapproved active PN conflicts in these six extracts. Analyzer executed for each FIG; whole-workbook missing matches in a single-FIG run and known CAD parser limitation remain unresolved package issues, not final validation. FIG3–9 still require canonical row review; all composition graphs and final workbook reconciliation remain pending. No final full SQL, no local/production DB import, no green completion. Existing production counts,17 SB and absent Check Sheet are unchanged from the snapshot; no fresh postflight claimed.

### 32-11-01RM /68 — FIG9A P/N correction approved — 23/Sep/2026 — partial

User confirmed `исправить по PDF`: include9A-310B correction M274262130D→M2742620130D with existingID5198/history preserved in the planned full SQL. Recorded in20260923-full/confirmed-decisions.json andreviewed-parts/9A-evidence.json. This supersedes the pending identity decision below; source9A.csv already contains the PDF number. No production/local DB changes and no final full SQL delivered. Remaining package extraction, graph/workbook reconciliation and tests are unchanged.

### 32-11-01RM /68 — decisions approved, FIG9A Parts transcribed — 23/Sep/2026 — partial

User confirmed `1. по PDF 2. по xls`: approved6-732B→2801-0707 and8-233→1840-0351 preserving IDs/history, plus existingSB170-32-0021 R2 and170-32-0060 R1 metadata fromXLS preserving IDs. Scoped decisions saved in20260923-full/confirmed-decisions.json; no DB writes. FIG9A63Parts visually transcribed with quantities fromprinted10158–10161, `reviewed-parts/9A.csv` and9A-evidence.json. Analyzer run analysis-fig9A:63Parts,24whole-workbook issues (not final flags; knownCAD limitation). Comparison to23/Sep production snapshot:60identity matches with no quantity changes;1newPNconflict9A-310B ID5198 M274262130D→PDF M2742620130D awaiting approval;2archived-only ASSY430A/B require reference-aware restoration;8existing non-orderable REF08configuration headers retained outside imported orderable Parts. Per-FIG group construction and remaining12FIG canonical reviews unfinished; no full SQL or green completion. Prior named decisions no longer pending.

### 32-11-01RM /68 — FIG9A source gap closed — 23/Sep/2026 — partial

Received `//F519/backup4/ScanFolder/20260923082603454.pdf` (4pages,95797bytes,SHA2563e7a891ffe3f6679dcec471cb79958a6a02df9ec65f4765b9e0b845d52617baf). Rendered all four at300DPI and visually reviewed:10157(blank),10158,10159,10160. Previously missing10158/10160 now present; repeated10159 matches original9A.pdf page2 and is counted once. Original9A.pdf supplies10161. Source gap resolved, not full FIG completion. Evidence in `storage/app/codex/manual-package-import/32-11-01RM/20260923-full/supplement-20260923082603454/`; source files unchanged. Confirmed9A-310B P/N M2742620130D qty2 (310A predecessor M274262130D), and350A/B references to10-1A/B. Remaining Parts/quantity/ASSY audit and prior P/N/SB decisions still pending; no SQL handoff or DB writes this step. Earlier missing-page note below is superseded by this entry.

### 32-11-01RM /68 — full-package preparation — 23/Sep/2026 — partial, awaiting source/decisions

User authorized completing the full local package and production SQL handoff. Fresh pinned SELECT snapshot23/Sep/2026 12:12:39UTC confirms991 active/113 archived Parts,0 new groups/options/coverages,57 legacy rows,17 SB,0 Check Sheet. No database writes. Run `storage/app/codex/manual-package-import/32-11-01RM/20260923-full/`:manifest,production snapshot,300-DPI renders and OCR for all95 pages,contact sheets visually inspected across all13 FIG. FIG6 PDF page9/printed10117 is crossed out; use replacement PDF page7/TR32-07. Critical source gap:9A.pdf contains10157(blank),10159,10161 but lacks tables10158/10160; requested from user, do not infer from FIG9 or database. OCR candidates are explicitly unreviewed, not canonical Parts or completion evidence. Fresh workbook diagnostic correctly handles split FIG/ITEM and whitespace PN:84 exact CAD matches,57 missing flags,9 unresolved positions; global analyzer unchanged. Asked confirmation for6-732B2601-0707→2801-0707 and8-2331840-0302RS20→1840-0351 (RS20 belongs8-234), and the two existing SB/XLS conflicts. CAD rows29/30 swap11-80…83/11-90…93 families; production identities agree with PDF. Full Parts/quantity/graph audit, reconciled mappings, SQL generation and local raw SQL tests remain pending. No final full SQL delivered, no change to per-FIG completion/green status. See `review-status.md` for exact continuation state.

### ASSY editor coverage-mode rollback — 22/Sep/2026

User rejected the per-member IPL family/Exact part only UI. Local Blade and controller restored to their pre-feature state; request field is no longer accepted/serialized or submitted. Read-only current production check confirms the column exists and37 exact links have already been imported (`32-21-02/20260922-exact/rollback-production-state.json`); this narrow check is not full import postflight certification. Retained model/resolver/migration compatibility to avoid broadening those links; no database changes or production deploy. Ordinary editing preserves imported false values and new links use default family behavior.49 tests PASS (26/361 PartGroups,23/141 LogCard/Scope/Legacy); local manual54 browser desktop1600x1000/mobile390x844 PASS, no JS errors, no save/delete. A full backend/schema rollback is deferred pending an applicability-based replacement, not falsely reported complete. Do not deploy the old exact_code.zip, which contains the rejected UI; deploy current local project files instead.

### 32-11-01RM /68 — production stage audit — 22/Sep/2026 — partial

User requested status and missing work, not an import. Current pinned production SELECT snapshot at22/Sep/2026 14:39:28UTC:991 active Parts plus113 archived rows;0 Part Groups/options/coverages;57 legacy assembly records;17 SB;0 Check Sheet templates. No production writes. Sources `C:/Airplane/32-11-01RM 2801A0000 ERJ170 MLG/`:13 numbered FIG PDFs (1–12 plus9A),95 pages, `2801_2802A0000-series.xlsx` SHA256071ea8ba3983501a55d2d7cff76d556eda584e6f6a2a2a97bd42b7fd57d9791b. FIG1 header visually confirms32-11-01RM and2801A0000/2802A0000. This is NOT a completed PDF row/quantity/composition audit.

| FIG | PDF pages | Active production Parts | New groups | PDF reconciliation complete |
|---|---:|---:|---:|---|
|1|8|84|0|?|
|2|10|116|0|?|
|3|4|47|0|?|
|4|4|25|0|?|
|5|8|67|0|?|
|6|14|152|0|?|
|7|10|100|0|?|
|8|14|114|0|?|
|9|6|63|0|?|
|9A|3|69|0|?|
|10|4|46|0|?|
|11|8|97|0|?|
|12|2|11|0|?|

Production flags:LC29/NDT66/CAD32/Paint32/KIT466. Workbook-vs-active-production diagnostic (inventory CSV explicitly NOT PDF extraction) identifies57 directly mapped missing KIT flags +2 remap-only KIT candidates,2 missing NDT flags9-90A/B; these are candidates pending PDF review, not authorized import. Previously prepared KIT removal for6-490/1840-0081 is not reflected:kit remains1, PRL F456 blank. Existing importer misses split FIG(A)/ITEM(B) CAD layouts and returns0 CAD incorrectly; independent read-only diagnostic resolves80 unique IPL/P/N matches across CAD tabs,53 missing CAD flags,13 unresolved row/item mappings. No parser or flags changed in this status task. Original analyzer issues28 include3 preserved EC/repair IPLs outside its regex, not3 invalid physical parts. Remaining25 workbook matching issues require source review; don't infer missing Parts purely from them.

SB14 rows match;2 substantive mismatches170-32-0021 R2 and170-32-0060 R1 (OEM references/descriptions/applicability);170-32-0088 R1 differs in Excel date representation43776 vs11/7/2019, not automatically a new bulletin. Check Sheet source has3 populated tasks plus2 empty slots, parser issues0; production absent. Existing standalone Check Sheet and KIT-removal handoff files are in source folder but do not complete this package.

Evidence `storage/app/codex/manual-package-import/32-11-01RM/20260922-audit/`:source-manifest.json,production.json,target-active.json,analysis/analysis.json,findings.json,capture.py,prepare_audit.py,summarize.py. Pending:full13-FIG PDF Parts/quantity reconciliation;new ASSY/Original-Oversize graph replacing reliance on legacy links;reviewed workbook mappings including split-column CAD;resolve2 SB conflicts;Check Sheet import;guarded full SQL and postflight. Completion remains partial, no green certification.

### Green manuals: IPL PDF quantity reconciliation — SQL ready, production pending — 21/Sep/2026

User requested quantity-only SQL for every green manual in the current HTML journal. Completion predicate selected IDs40/41/58/78/91/95. Fresh pinned read-only production capture at21/Sep/2026 18:14:09UTC contains3159 rows including soft-deleted; exact active PDF scope3130,18 additional active rows retained. Immutable PDF SHA256 hashes match original reviewed manifests. Run: `storage/app/codex/manual-package-import/ipl-quantities/20260921/` (`journal-scope.json`, `production.json`, `source-hashes.json`, `final-rows.json`, `visual-quantity-review.json`, `preserved-rows.json`, `packages.json`, `sql-local-test.json`).

| Manual / ID | FIG | PDF Parts checked | Quantity changes prepared | Correction status |
|---|---|---:|---:|---|
|32-10-02 /58|1|65|0|Already matches PDF at snapshot|
|32-11-10 /40|1|131|0|Already matches PDF at snapshot|
|32-11-06 /41|1/2/3:112/256/322|690|0|Already matches PDF;2 EC-only rows retained|
|32-11-15RM /78|1–15|988|9, all FIG14|Awaiting user import/postflight;9 extra rows retained|
|32-11-05 ELEB /91|1|57|2, root RF→1|Awaiting user import/postflight|
|32-11-14RM /95|1–15|1199|127|Awaiting user import/postflight;7 extra/legacy rows retained|
|Total|36 FIG|3130|138|SQL handoff, not production-applied|

Per-FIG checked/changes for95:1=80/8;2=84/0;3=63/0;4=36/0;5=106/0;6=96/14;7=62/0;8=97/2;9=103/8;10=136/21;11=43/8;12=120/16;13=68/16;14=94/27;15=11/7. Per-FIG checked for78:65,71,47,18,90,75,46,80,81,105,28,110,67,94,11; only FIG14 changes9. Full36-FIG table at `output/sql/ipl-quantities-20260921/per_figure_audit.csv`.

Root causes: older imports retained AR/RF/null and propagated original bushing/sibling/other-manual quantities; old95 canonical builder explicitly used32-11-15 fallback, so raw95 OCR and rendered PDF were rechecked instead. All changed/blank95 source cells and omitted-source rows were visually reconciled, rejecting false OCR blanks with real numeric2/4. FIG10 temporary revision used; crossed-out older page excluded. Additional95 identities1-250A,3-390,5-70,5-80,8-100-RS,9-550RS,10-240B are preserved without guessing from another IPL.78 ID8010 is preserved. No flags/groups/SB/Check Sheet/WO/history updated or freshly re-certified in this narrow quantity pass.

Handoff: `output/sql/ipl-quantities-20260921/all_green_manuals_ipl_quantities_20260921_v01_import.sql` and SELECT-only `_verify.sql`; corresponding individual manual files prepared for source folders. Changes only `components.units_assy`, numeric PDF quantities retained; AR/RF/letters/blank→1. Runtime family capacity/technician allocation remains separate, not a reason to overwrite source quantity. Exact manual/ID/IPL/P/N + observed-or-final guards, row locking, InnoDB transaction/postflight rollback, no DDL/temp tables. Whole raw SQL passed isolated local tests:3130 expected quantities,138 changed fields only,36 FIG checks, repeat, six manual files, six negative guard cases, omitted-update rollback and partial retry. Production and local working DB unchanged. Existing package completion is historical; this corrective handoff is pending and must not be reported as already applied. Do not rerun superseded full imports after this correction.

Individual quantity import/SELECT files copied to all six confirmed `C:/Airplane/<manual folder>/` locations; copy SHA256 matched workspace handoffs. Combined file remains in `output/sql/ipl-quantities-20260921/`. User upload and read-only postflight are the next step.

### 32-11-15RM /78 — focused KIT audit PASS — 21/Sep/2026

Fresh pinned read-only production snapshot21/Sep/2026 16:25:23UTC compared every enabled KIT against the immutable original `C:/Airplane/32-11-15RM ERJ 195 MLG/4259A_4260A0000-ser.xlsx`, SHA256 `d88bbad65ac7c9af21b7bc73c67a17c7c160253d4c64733c26f7e449f187449e`. Original PRL cell values read directly from XLSX XML:458 explicit KIT cells (457 ordinary rows plus ALT helper row370 supporting12-120),32 blank-CODE rows468–499 representing34 exact IPL/P/N Parts. All34 have KIT0. All496 current KIT1 Parts have explicit source KIT evidence:488 prior reviewed mappings plus8 recorded user-approved PDF/P/N resolutions, each rechecked against actual CODE and current Part identity. No extra/missing flags or unresolved source rows. The previous broad audit's57 strict-matcher issues for this manual are now resolved by the already reviewed mappings; they are not unchecked extra KIT marks.

| FIG | KIT source / production | Blank-CODE Parts | Incorrect KIT | KIT audit |
|---|---:|---:|---:|---|
|1|53 /53|0|0|✓|
|2|37 /37|1|0|✓|
|3|38 /38|0|0|✓|
|4|4 /4|0|0|✓|
|5|32 /32|5|0|✓|
|6|32 /32|6|0|✓|
|7|36 /36|0|0|✓|
|8|50 /50|6|0|✓|
|9|54 /54|5|0|✓|
|10|34 /34|0|0|✓|
|11|14 /14|1|0|✓|
|12|55 /55|4|0|✓|
|13|18 /18|3|0|✓|
|14|39 /39|3|0|✓|
|15|0 /0|0|0|✓|
|Total|496 /496|34|0|✓ KIT only|

Evidence/run: `storage/app/codex/manual-package-import/32-11-15RM/20260921-kit-audit/` (`verify.py`, `production.json`, `source-cells.json`, `audit.json`, `report.md` with all34 blank-CODE Parts). No corrective KIT SQL needed. No production/local business data, source workbook or old SQL changed. PDF/ASSY/SB/Check Sheet/other flags were not re-audited; historical package completion remains unchanged. This confirms actual database flags, not a fresh rendered-form or specific-WO diagnosis.

### PRL explicit CODE KIT correction — SQL ready, production pending — 21/Sep/2026

User authorized corrective production SQL after confirming that only explicit PRL CODE `KIT` enables the checkbox; blank/K/other codes do not. Read-only pinned production snapshot and immutable workbook extraction for nine source folders are retained in `storage/app/codex/manual-package-import/kit-explicit-code/20260921/` (`production.json`, `sources.json`, `audit.json`, `plan.json`). This is a narrow checkbox reconciliation, not a fresh PDF/group/SB/Check Sheet completion audit. Historical completion evidence remains; this correction is pending user upload/postflight. Production and working local database unchanged.

| Manual / ID | FIG / exact rows | KIT before → proposed | Handoff / correction status |
|---|---|---:|---|
|32-11-01RM /68|FIG6: ID2556,6-490,1840-0081; PRL!F456 blank|466 →465|1 removal; awaiting user import|
|32-11-05 ELEB /91|FIG1: IDs5283/5284/5285,1-320/1-321/1-321A; PRL!F27 blank|26 →23|3 removals; awaiting user import|
|32-10-02 /58|No unambiguous blank/other-code KIT correction|0 →0|No DML|
|32-11-06 /41|No unambiguous blank/other-code KIT correction|302 →302|No DML|
|32-11-10 /40|No unambiguous blank/other-code KIT correction|59 →59|No DML|
|32-11-14RM /95|No unambiguous blank/other-code KIT correction|514 →514|No DML|
|32-11-15RM /78|No unambiguous blank/other-code KIT correction|496 →496|No DML|
|32-21-02 /42|No unambiguous blank/other-code KIT correction|300 →300|No DML|
|32-21-04 Goodrich /21|Only PRL1 applies; PRL2/3 other manuals excluded|395 →395|No DML|

All four changed Parts match exact manual/IPL/P/N; original CODE cells independently checked in XLSX XML (including merged-cell ambiguity guard), evidence `independent-source-check.json`. No other matching explicit-KIT row overrides these blanks. Do not blanket-clear flags for Parts absent from PRL. Preserve308 existing KIT rows without an exact match in this strict audit (including previously approved P/N remaps and rows outside PRL). Additional32-11-01RM findings:50 unambiguous missing explicit KIT flags plus6 candidates needing matching review; excluded from this removal-only SQL and recorded for a separate reconciliation. Source/PDF conflict rules remain active.32-21-06 workbook absent from its C:/Airplane folder, so not audited here. Later embedded manual sections excluded from each primary-manual source.

Combined handoff: `output/sql/kit_explicit_code_20260921_v01_import.sql`, SELECT-only `_verify.sql`, `_README.md`, optional deliberate reversal `_undo.sql` (not an ordinary import). Corresponding per-manual `_kit_code_import.sql` / `_verify.sql` copies belong in `C:/Airplane/32-11-01RM 2801A0000 ERJ170 MLG/` and `C:/Airplane/32-11-05 170-7015 ERJ170 MLG/`. Use the combined import OR separate files, not both. Guarded exact identities/InnoDB, row locking, single transaction, postflight-controlled COMMIT/ROLLBACK; no DDL/temp tables/deletes, no group/WO/history/other-checkbox changes. Raw combined and separate SQL passed isolated local tests, exact preservation of5580 captured component rows except four kit fields, SELECT verification, idempotent repeat, reversal, partial retry and seven negative guards/rollback cases; scratch removed (`sql-local-test.json`). Do not rerun historical additive package imports after this correction: they can restore prior true flags. Old source/handoff files are retained unchanged for audit.

### Log Card explicit technician ASSY choice — local only — 21/Sep/2026

Follow-up to WO108010: the user requested a technician choice when the metal part has several own ASSY identities. Desktop create/edit and mobile create now expose `Select your ASSY` with IPL/P/N from valid manual ASSY groups. The WO-scoped group remains the initial suggestion; the technician may explicitly select another own ASSY containing that component. Plain BOM ancestors without an own-assembly relation are excluded. The posted `assy_selection_explicit` flag is validated against component membership, manual, group type and own assembly identity, then persisted with canonical group/P/N/IPL. Saved explicit identities are not replaced by automatic received-scope projection. Unmarked historical erroneous rows retain the prior scoped correction behavior. No schema migration or production data/code writes.

Validation:55 related tests PASS, including technician desktop create/reopen/update, mobile template/store, out-of-scope implicit rejection and invalid explicit rejection. Browser QA used Playwright (Browser plugin unavailable), actual Laravel-rendered transactional test fixtures and current public assets at `avia.loc` URLs, with write requests intercepted; local working WO108010 is absent. Desktop1440x1000 and mobile390x844: visible selector, correct WO default, selecting -02 instead of suggested -03, correct submitted group/explicit flag, desktop edit restoration and mobile confirmation all passed; no JS page errors. Server persistence separately passed through real testing-DB routes. Screenshots/scripts are outside the repository under `C:/Users/Leo/.codex/visualizations/2026/08/13/019ffb4f-1112-7ec0-b975-c2c29de3d51a/` (`log-card-assy-desktop.png`, `log-card-assy-mobile.png`). Production deployment and live production UI verification remain pending the user's deployment.

### WO108010 Log Card ASSY mismatch — local code fix, not deployed — 21/Sep/2026

User authorized a local fix after the diagnosis below. `LogCardAssemblyIdentity::groupsForWorkorder` now limits primary-manual Log Card ASSY candidates to the received WO scope option and reachable nested groups before shared components are assigned. Desktop and mobile builders and save validation use this restriction; unrelated manual groups retain their existing behavior. Incoming identity uses received scope, not a later R&M modified scope. Full-unit/no-explicit-option behavior is unchanged.

For active saved component-choice rows, an unambiguous scoped own-assembly identity is projected into the displayed/printed incoming row without database writes on GET. The next explicit full-card save persists the corrected group/P/N/IPL. Serial fields are preserved; completed WO rows and explicit outgoing QA rows are not remapped to the received scope. Ordinary BOM membership alone still does not make a parent P/N the component's own ASSY. No production deployment or saved-row repair was performed.

Regression fixture reproduces component2821-0111/IPL14-210A shared by ASSY2821A0100-02/IPL14-1A and ASSY2821A0100-03/IPL14-1C, with both legacy assembly relations. Verified both scope directions, draft web/mobile builders, active saved-row display/print, wrong-group save rejection, explicit corrected save, preserved serials, completed/history behavior, received versus modified scope, nested groups and incoming/outgoing QA separation. Final related run:52 tests PASS (`LogCardAssemblyIdentityTest`, `AndroidApiTest`, `MobileLogCardWebTest`, `PartGroupsTest`, `WorkorderPartScopeTest`); five edited PHP files lint clean. Additional `LogCardProtectionTest`:7 pass,1 existing authentication failure (401 instead of423), also reproduced with the four pre-fix HEAD classes loaded in memory via local diagnostic `storage/app/codex/log-card-scope/verify_baseline.php`. This unrelated access-test issue was not changed.

### WO108010 Log Card ASSY mismatch — diagnosis only — 21/Sep/2026

Read-only production diagnosis at14:26:27UTC: WO108010/ID495, unit607 P/N2821A0100-03, manual95/32-11-14RM, selected scope option499/group167/IPL14-1C is correct. Saved Log Card226 instead contains component6135/2821-0111/IPL14-210A with group165/P/N2821A0100-02/IPL14-1A. Both ASSY groups165/167 legitimately contain this component in current composition; legacy component_assemblies537/538 also provide both P/Ns. Controller/service/view hashes match local inspected code.

Root cause reproduced with the deployed read-only `buildLogCardAssyChoiceGroups` method and actual component6135: it returns only group165/-02. Groups are ordered by ID, the first group claims a component through `assignedComponentIds`, and this method is not given the WO's selected ASSY scope. Group167/-03 is then skipped for that shared component. Saved-row rendering retains the wrong saved -02; `LogCardAssemblyIdentity::cleanRows` accepts it because it is a valid general legacy link, without WO-specific selection validation. Correct target for this WO is component2821-0111 with ASSY2821A0100-03/IPL14-1C, not a global replacement of the valid -02 variant.

Evidence: `storage/app/codex/manual-package-import/letter-family-cleanup/20260921/wo108010-log-card-diagnostic.json`; reproducible diagnostic `diagnose_wo108010.py`. No application code, database rows or saved Log Card modified. Correction requires a separate authorized code fix (WO-aware ASSY selection and save validation) plus reviewed repair of existing affected saved rows. The previous93-group migration postflight remains PASS; this is a distinct unresolved Log Card behavior issue, not a new PDF/package import or reason to merge/delete either real ASSY.

### Automatic IPL-family cleanup — production verified — 21/Sep/2026

User reported the SQL uploaded. Pinned read-only production postflight at **21/Sep/2026 13:29:19UTC**: **18/18 exact delivered SELECT checks OK**, zero row differences against the reviewed final plan. Both required PHP files match the handed-off SHA256 byte-for-byte (`PartGroupCoverageResolver.php` and `ManualPartGroupCompositionResolver.php`). The prerequisite deployment and database migration are complete; no repeat import required. No production writes by agent.

| Manual / ID | Active groups before → after | Groups per FIG now | Retired groups | Retargeted ASSY/KIT edges | Corrective migration |
|---|---|---|---:|---:|---|
|32-10-02 /58|18 → 7|1:7|11|11|✓|
|32-11-05 ELEB /91|17 → 11|1:11|6|6|✓|
|32-11-06 /41|204 → 174|1:53; 2:59; 3:62|30|431|✓|
|32-11-10 /40|37 → 23|1:23|14|24|✓|
|32-11-14RM /95|114 → 108|5:24; 6:11; 8:5; 9:2; 10:18; 11:1; 12:7; 13:13; 14:24; 15:3; other FIG:0|6|10|✓|
|32-11-15RM /78|143 → 117|5:24; 6:10; 8:6; 9:4; 10:18; 11:2; 12:12; 13:13; 14:24; 15:4; other FIG:0|26|13|✓|

Verified93 soft-retired groups,495 correctly retargeted edges, preserved edge IDs/quantities/scopes, all202 retired-group options retained, all16 planned exceptions unchanged. Full group/option/coverage rows (including unrelated groups) match the reviewed final state. Target manual and component identity/name/quantity/bushing/deletion fields match preflight. No new references to retired groups/options in scoped history tables or Log Card JSON, and no multiple-variant TDR order conflicts. This is a corrective group audit, not a new PDF/XLS/flags/SB/Check Sheet extraction or full package re-audit; previous package evidence and approved scope limitations remain valid.

Evidence under `storage/app/codex/manual-package-import/letter-family-cleanup/20260921/`: `postflight-2026-09-21T132919_0000.json`, `postflight-audit-2026-09-21T132919_0000.json`, read-only runner `verify_production.py`. `handoff-manifest.json` status is `production_verified`.

Both visible HTML journal paths (`output/manuals-production-journal.html`, `output/manuals-production-20260915.html`) and Markdown now have the corrected per-manual/per-FIG group counters. Data-only refresh retains existing expected FIG inputs, original package-audit dates, checkbox/SB/Check Sheet counts, six completed green rows and all unrelated manuals. Header/details explicitly distinguish the19/Sep general inventory from21/Sep group counters. Evidence `journal-refresh-audit.json`, `verified-data-*.json`; prior reports backed up in this run; refresher `update_verified_journal.py`. JSON roundtrip, exact totals, completion predicate and unchanged unrelated rows/expected counts verified; no layout/behavior changes or new browser-render claim. Global active-group total is500, previously593. The pending handoff entry below is historical and superseded by this successful postflight.

### Cross-manual automatic IPL-family cleanup — handoff pending — 21/Sep/2026

User requested production correction SQL for the new automatic suffix rule. Read-only pinned production snapshot `storage/app/codex/manual-package-import/letter-family-cleanup/20260921/production-2026-09-21T122413_0000.json`: 16 manuals with groups, 5802 Parts, 594 group rows (593 active), 1228 options, 7208 coverages. Of109 same-base Alternative P/N groups,93 qualify for soft retirement;495 incoming ASSY/KIT edges retain their IDs, quantities/scopes and now reference the same representative component.202 option rows and all internal coverage rows are preserved. No production writes. This is a cross-manual corrective patch, not a repeat PDF/XLS import.

| Manual / ID | Affected FIG | Groups soft-retired by SQL | Incoming edges retargeted | Exceptions retained | State |
|---|---|---:|---:|---:|---|
|32-10-02 /58|1|11|11|2|awaiting user import/postflight|
|32-11-05 ELEB /91|1|6|6|0|awaiting user import/postflight|
|32-11-06 /41|1,2,3|30|431|8|awaiting user import/postflight|
|32-11-10 /40|1|14|24|0|awaiting user import/postflight|
|32-11-14RM /95|6,10,13|6|10|2|awaiting user import/postflight|
|32-11-15RM /78|5,6,10,11,12,13,14|26|13|4|awaiting user import/postflight|

The16 exclusions remain intentionally: explicit configuration subsets (A/B versus C/D etc.), bushings and two58 options whose internal quantities2/10 affect Work Scope. Exact IDs/IPLs/reasons are in `audit.json` and the handoff README. No eligible group/option has recorded FK or Log Card JSON history references. Supplementary read-only TDR check `production-order-conflicts.json` at12:39:03UTC found zero WOs ordering multiple distinct variants of a retiring family; SQL guards against new conflicts/references.

Handoff is `output/sql/ipl_letter_families_20260921_v01_import.sql`, companion SELECT-only `_verify.sql`, guarded `_rollback.sql` (not for ordinary upload), and `_README.md`. Prerequisite: deploy current `app/Services/PartGroupCoverageResolver.php` AND `app/Services/ManualPartGroupCompositionResolver.php`; production lacked the previous automatic nested-family expansion when captured. New code preserves explicit subset boundaries and Log Card/mobile membership after replacing nested groups with direct representatives. SQL cannot verify PHP deployment.

Additional sequential regression run PASS:16 tests in LogCardAssemblyIdentityTest, MobileLogCardWebTest and WorkorderPartScopeTest (40 feature tests total including PartGroupsTest). Initial additional run was blocked before tests by PHP temporary-directory permissions; approved rerun passed. No parallel PHP test suites.

Generator/capture/audit/test sources, `plan.json`, `sql-local-test.json` and finalized `handoff-manifest.json` are in the run directory above. SQL has no DDL, temporary tables or physical deletes; exact source/target signatures, transaction, idempotent repeat and guarded rollback. Local24 PartGroups feature tests pass. Final raw-SQL PASS:1455 PRL/STD coverage cases,555 Work Scope calculations and291 Log Card compositions identical before/after retirement with updated resolvers. Exact rows, repeat, rollback/repeated rollback pass. Seven negative cases block unchanged or roll back entirely (changed qty/manual, new history/Log Card/Part, multiple ordered variants, omitted UPDATE). Isolated scratch database removed after testing; working local database unchanged. Import SHA256 `b216d278aa609a2af053e4ca1a7124b2f2005f01cc02176c3a4087559fdcef0e`. Previous PDF Parts/quantity/XLS flags/SB/Check Sheet counts and historical completion evidence below are unchanged; do not mark this corrective migration applied or replace production group counters until post-import SELECT verification.

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
| 32-21-02 | 42 | 32-21-02 ERJ 190 NLG | 190-70745-series.xlsx | 32-21-02 | 2 / 5 | Exact JSON/hash production PASS 21/Sep/2026 |
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
| 32-11-34 | 76 | partial; import verified 29/Sep/2026 | FIG1=258 / FIG2=263 Parts verified; approved composition omissions | 521 active +1 deleted | 52 production | No XLS;6 existing SB preserved;no Check Sheet;738/738 checks passed | `storage/app/codex/manual-package-import/32-11-34/20260929-pdf/production-postflight.json` |
| 32-11-05 ELEB | 91 | complete | FIG 1 | 57 | 17 | XLS applied; 0 SB | `storage/app/codex/manual-package-import/32-11-05-ELEB/`; `output/sql/32-11-05_ELEB_fig1_part_groups.sql`; `output/sql/32-11-05_ELEB_170-70150_process_flags.sql` |
| 32-11-14RM | 95 | complete | FIG 1–15 | 1,206 | 114 | XLS applied; 14 SB; production postflight passed | `storage/app/codex/manual-package-import/32-11-14RM/`; `database/scripts/import_32-11-14RM_standard_package.sql` |
| 32-11-15RM | 78 | complete | FIG 1–15: 988 PDF Parts + 9 retained existing rows; production verified 17/Sep/2026 | 997 | 143 | XLS flags/SB verified; 13 SB; zero postflight discrepancies | `storage/app/codex/manual-package-import/32-11-15RM/20260917-full/production-postflight-audit-20260917.json`; versioned v01 SQL in source folder |
| 32-21-02 | 42 | partial composition; import verified | Parts FIG1–10 verified; full cycle FIG2–7; exact links FIG1/10 present | 631 | 87 | 24/Sep production: 951/967 original checks pass; 16 old composition/count checks intentionally superseded by 37 exact links; XLS/9SB/Check Sheet remain verified; root ASSY links FIG8/9/10 pending | `storage/app/codex/manual-package-import/32-21-02/20260921-full/production-postflight-20260924.json` |
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

### Quantity-only XLS audit — 21/Sep/2026 (open reconciliation)

Read-only production snapshot `2026-09-21T17:41:16+00:00`, manual ID 78, compared against PRL column E of `C:/Airplane/32-11-15RM ERJ 195 MLG/4259A_4260A0000-ser.xlsx` (SHA256 `d88bbad65ac7c9af21b7bc73c67a17c7c160253d4c64733c26f7e449f187449e`). Run: `storage/app/codex/manual-package-import/32-11-15RM/20260921-xls-quantity-audit/`; evidence: `production.json`, `matched-rows.csv`, `quantity-differences.csv`, `part-number-totals.csv`, `identity-issues.json`, `summary.json`, `report.md`, reproducible `audit.py`.

509 component options matched; 18 quantity discrepancies (17 bushings), including 16 production quantities of 1 versus XLS 2/4 and two production quantities of 2 versus XLS 1. Seven discrepancies are P/N-confirmed size variants under a shared base IPL; eleven match exact IPL/P/N. Another 36 source options have identity discrepancies (including spelling/punctuation and alternative P/Ns), retained separately without automatic correction. No database, source, flag or prior import SQL changes. Existing PDF-based completion evidence below is retained; the new XLS quantity reconciliation remains **open/partial**, not a claim that PDF quantities were incorrectly transcribed.

| FIG | XLS options matched | Quantity discrepancies | XLS quantity status |
|---|---:|---:|---|
| 1 | 52 | 0 | Matched subset agrees |
| 2 | 37 | 0 | Matched subset agrees |
| 3 | 38 | 0 | Matched subset agrees |
| 4 | 4 | 0 | Matched subset agrees |
| 5 | 35 | 0 | Matched subset agrees |
| 6 | 41 | 3 | Open |
| 7 | 36 | 0 | Matched subset agrees |
| 8 | 55 | 0 | Matched subset agrees |
| 9 | 58 | 0 | Matched subset agrees |
| 10 | 37 | 5 | Open |
| 11 | 15 | 0 | Matched subset agrees |
| 12 | 39 | 0 | Matched subset agrees; identity issues separate |
| 13 | 21 | 2 | Open |
| 14 | 41 | 8 | Open |
| 15 | 0 | — | No PRL quantity rows |

Fresh production already has quantity 2 for 14-13/2821-0102RS01 and 14-14/2821-0102RS20; earlier diagnostic in this conversation had 1 for 14-14. This audit performed no updates. Blank CODE on trailing PRL rows remains blank and does not authorize KIT. Seven discrepant size variants have explicit KIT in the workbook but false production KIT flags; retained as a separate observation only.


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

## 32-21-02 — complete in agreed scope

### Production completion verified — 09/Oct/2026

User requested post-upload verification. Pinned current-production SSH, SELECT/SHOW and file-hash reads only:270/270 completion checks PASS at17:07:58UTC; full snapshot at17:08:08UTC (13:08 Toronto). All4 delivered PHP/migration files match handoff SHA256; all9 inspected relevant server files match current local files. Production now631active Parts plus1archived historical row,93groups,167options,4277coverages,9SB,1exactCheckSheet;47legacy records preserved. All original Parts identities/quantities/flags,groups/options/4031links,SB,CheckSheet and legacy rows match guarded baseline; all246newlinks and6Camgroups/options match final plan. This supersedes the pending-upload status below. No production writes by Codex.

FIG1–10 now complete in the agreed package scope. Per-FIG Parts unchanged115/18/33/30/46/20/56/86/102/125; groups23/1/0/0/2/1/1/14/21/30. Prior exact FIG1/10 fixes remain; new Upper Torque Link/Main Fitting/Sliding Tube choices,chosen washer quantities,Cam conditional seal bundles,all8 axle candidates and root-only conditional Spacer verified. User-approved all-four-tubes applicability and thickest-washer decisions remain explicit package decisions, not generic aircraft certification. Repair-only omissions remain as previously agreed. Log Card order was preserved,not reset or newly audited; rendered production UI was not tested in this read-only check.

Evidence in `storage/app/codex/manual-package-import/32-21-02/20261009-completion/`: `postflight.json`, `production-after-import.json`, `production-completion-summary.json`, updated `handoff-manifest.json`; source handoff remains in `C:/Airplane/32-21-02 ERJ 190 NLG/`. Local HTML journals and inventory updated only for manual42 to10/10,93groups,09/Oct/2026,complete; other manual data and HTML/JS/CSS unchanged,previous files backed up in this run. Independent record assertions passed. No repeat import needed.

### Completion package validated and handed off — 09/Oct/2026 — awaiting user production import

Final handoff saved without overwriting prior versions and SHA256 byte-verified in `C:/Airplane/32-21-02 ERJ 190 NLG/`: `32-21-02_20261009_v01_completion_schema.sql`, `_import.sql`, SELECT-only `_verify.sql`, four-file `_code.zip`, `_README.txt`. Run `storage/app/codex/manual-package-import/32-21-02/20261009-completion/` retains `handoff-manifest.json`, `final-plan.json`, confirmed decisions, production snapshot, preflight, generators and test evidence. Import SHA256 `4e680a12baff55d6b8f6033a0407f0eff1b756e6402a64e8802ee961950eae5c`. Deploy schema and code BEFORE data; do not execute the superseded24/Sep config SQL. New production postflight script: `verify_production.py --postflight` (SELECT-only; run after user reports import).

Final isolated verification `completion-sql-test.json`:raw SQL SUCCESS,270/270 SELECT checks,identical repeat,all original rows preserved;2930 PRL/STD scope/quantity cases,293 Work Scope cases,293 Log Card compositions,all4 standalone tubes without Spacer,and all6 exact Cam bundles PASS. Five invalid-state cases rollback unchanged (manual,PartPN,old edge,new edge,companion condition). Scratch removed. Standalone schema SQL and repeat PASS. Latest targeted6feature tests PASS; the earlier unrelated PRL-view failure remains documented in `feature-test-results.json` and README. No production writes, no working local business-data import, no fresh browser QA. This is validated handoff completion, not production-import completion.

| FIG | Existing production Parts | Current production groups | This handoff | Completion |
|---|---:|---:|---|---|
|1|115|23|Root choices and root-only conditional Spacer|SQL ready; production pending|
|2|18|1|Unchanged|Previously verified|
|3|33|0|Unchanged|Previously verified|
|4|30|0|Unchanged|Previously verified|
|5|46|2|Unchanged|Previously verified|
|6|20|1|Unchanged|Previously verified|
|7|56|1|Unchanged|Previously verified|
|8|86|14|3 Upper Torque Link choices/root;washer372x2,147x1|SQL ready; production pending|
|9|102|15|6 new Cam ASSY;exact seal sets;8 axle candidates;root Spacer conditions|SQL ready; production pending|
|10|125|30|4 Main Fitting choices/root|SQL ready; production pending|

Workbook flags,9SB and1CheckSheet remain previously verified/preserved; no new flag mapping or Log Card display-order reset. Expected after successful import631active Parts/93groups/167options/4277coverages. Manual remains partial/non-green until the user installs the package and the fresh production postflight passes.

### Completion decisions confirmed and new package built — 09/Oct/2026 — validation in progress

User confirmed: use the thickest single washer at each selected position (8-60→8-372 qty1,8-290→8-372 qty1,8-140→8-147 qty1); Cam ordered as ASSY automatically includes its matching seals; all four Sliding Tube options are allowed for every root, including190-70453-411; Spacer belongs only to the complete root strut, not to a separately ordered Sliding Tube. Decisions stored in `20261009-completion/confirmed-decisions.json`. This supersedes the three pending questions and the old tube-nested spacer proposal. Cam root effectivity remains as printed; its broadening was not requested. Existing Parts quantities/flags and Alternative group264 remain unchanged.

Built `32-21-02_20261009_v01_completion_{schema,import,verify}.sql`, four-file `_code.zip`, `_README.txt` in the run directory. Expected additions:6 Cam ASSY/6 options/246 coverages; final631 active Parts/93 groups/167 options/4277 coverages/9SB/1CheckSheet. FIG1 gets root configuration links and conditional root-only Spacer; FIG8 selected washer totals372x2 and147x1 plus3 Upper Torque Link candidates per root; FIG9 gets8 exact axle candidates across4 tubes,6 Cam seal bundles with51 root candidate links,root Spacer conditions; FIG10 gets4 Main Fitting candidates per root. FIG2–7 unchanged. No new Parts or checkbox assignments. The old24/Sep config SQL is superseded and must not be imported with this package.

Application support adds nullable `manual_part_group_coverages.requires_selected_option_ids`, filtered against selected sibling ASSY options. For PRE tubes390/390A the root offers spacer140 or140A qty2; for POST390B/390C it includes only140A qty2. With no tube choice neither companion is active; separate tube procurement has no Spacer. Controller clears stale dependent selections. Four deployment files:coverage model,WorkorderAssyConfiguration service,its controller,and migration2026_10_09_120000. Targeted tests6PASS including all6 Cam automatic TDR bundles and root-vs-standalone Spacer. Broader suite55PASS/1FAIL:PartGroupsTest PRL-view assertion line358; the same failure reproduced with the pre-change HEAD configuration service (26PASS/1FAIL). This existing failure is not a green full-suite claim. No browser QA was performed in this data/backend task.

Raw data SQL270 SELECT checks,identical repeat and original-row preservation already passed in an isolated scratch DB; exhaustive runtime/negative checks still running at this entry. Schema SQL initial/repeat and missing-prerequisite refusal PASS. Fresh pinned read-only preflight15:54:13UTC confirms all8 baseline identity/preservation checks pass. Working main DB data and production unchanged. Do not mark complete or handoff validated until the next final validation entry and user production import/postflight.

### Completion preparation reopened — 09/Oct/2026 — awaiting three source/workflow decisions

User authorized preparation and questions. Run `storage/app/codex/manual-package-import/32-21-02/20261009-completion/`: fresh pinned SELECT/SHOW snapshot `production.json` at15:05:12UTC, exact current schema and eight relevant deployed server-code SHA256 values (all match local), `completion-plan.json`, `confirmed-root-links.csv` (77 previously user-approved Upper Torque Link/Main Fitting candidate links), `sb07-axle-pairs.csv` (8 document-supported pairs), `cam-companions.csv` (16 conditional co-use rows). Existing631 active Parts/87 groups/161 options/4031 links/9SB/1CheckSheet unchanged; no production writes and no business-data import locally. Status remains partial; no new final SQL handed off.

Visually rechecked full-scan pages71/74/75/76/83–86/88/89 (printed10107/10110–10112/10119–10122/10124–10125) and SB07 PDF pages16–18. SB07 flowcharts resolve axle pairs:9-390→540/541;390A→540A/541A;390B→542/543;390C→542A/543A. They also restrict documented root families:190-70453-401/-405/-601/-607/-609/-611→190-70465-401/-601; root-605/-407/-409/-613→tube-403/-603. Root-411 is absent from these supplied flowcharts. Therefore the old SQL's all-four-tubes-for-every-root assumption requires explicit resolution; do not present that broad applicability as source-confirmed. User asked whether to follow paired restrictions or confirm all four. Existing user permission for all Upper Torque Link/Main Fitting variants is retained, not requested again.

Other pending questions: per-position washer quantities/independent thickness for8-60/290 versus shared360–372 list and single/stack rule for8-140→141–147; desired WO Cam choice with its exact seal set, preserving existing Alternative group. Cam companion map is explicit in IPL, but USED WITH does not establish a purchased Cam assembly. Likewise spacer9-140/A is a root-level co-use part, not a documented child of purchased Sliding Tube; old nested-spacer representation must be reviewed before final handoff. SB07 pre-side permits spacer-001/-003, post-side-003 only. Current questions and constraints are recorded in completion-plan.json; no answers inferred.

Old24/Sep raw SQL now successfully tested against current production snapshot/schema in a separate local scratch database:127 additions, all4031 prior edges and Parts/flags/groups/options/SB/CheckSheet/legacy preserved, identical successful repeat, wrong manual/parentPN guards rollback unchanged; scratch dropped. Evidence `old-config-sql-test.json`, runner `test_existing_sql.php`. This validates mechanics only, not the unresolved source applicability above. `AssyConfigurationChoiceTest`4PASS, run singly with workspace temp directory (initial launcher temp permission issue resolved). No new UI verification or full-manual completion claim.

### Fresh production status — 09/Oct/2026 — partial

Pinned SSH SELECT/SHOW-only audit at 15:01:37 UTC (11:01 Toronto), manual ID42. Evidence: `storage/app/codex/manual-package-import/32-21-02/20260921-full/production-postflight-20261009.json`, reproducible script `verify_production_20261009.py`. No production writes. Active Parts by FIG1–10 remain 115/18/33/30/46/20/56/86/102/125 =631, plus1 archived row;87 groups/161 options/4031 coverages, including37 exact links;9 SB and1 Check Sheet. Current active flags: LLP49/NDT72/CAD54/Paint66/KIT302 (these totals include preserved flags and are not source-only counts). The original967 checks still yield951 PASS and the same16 historical composition/count differences caused by the37 exact additions; no new failed assertions. Parts identities/quantities, required workbook flags, SB and Check Sheet checks pass. FIG2–7 reviewed completion remains confirmed; FIG1 exact fastener stage confirmed but root configuration into FIG8–10 remains incomplete.

The `choice_slot` production column now exists, unlike24/Sep preflight, but the prepared configuration data is absent: no conditional links on root options1683–1693 and no expected axle/spacer links on tube options1674/1675. Thus the24/Sep configuration handoff cannot be considered imported successfully. Its121 root candidate links plus4 axle candidates and2 spacer links remain pending. FIG8 washer applicability and FIG9 Cam/seals/old-tube axle details remain unresolved per source audit; FIG10 root Main Fitting selection remains pending. Source and handoff folder unchanged: `C:/Airplane/32-21-02 ERJ 190 NLG/`. Status stays partial, not green; this status check does not newly certify UI behavior or Log Card display order.

### Conditional ASSY configuration handoff prepared — 24/Sep/2026

User approved a per-WO technician choice for the physically included lower ASSY, not a separate TDR order; user explicitly allowed all four Main Fitting variants as candidates in each root ASSY. Local code now uses `manual_part_group_coverages.choice_slot` plus `workorder_assy_configuration_choices` (migration `2026_09_24_120000_add_assy_configuration_choices.php`); existing Parts have **no** new per-Part selector. TDR has an ASSY composition modal. Procurement PRL/STD, Work Scope and Log Card use the selected nested BOM; unselected alternatives do not enter the BOM, but their IPL head alternatives are crossed out in procurement. Changing a parent clears unreachable lower choices. The manual Part Group editor preserves and labels imported conditional links instead of deleting them on save. Local main and isolated testing migrations ran. Browser QA at 1440 and 1024 px with a mocked read-only slot passed (three candidates, modal within viewport, no page JS errors); no local production-like WO selection was created.

Source-folder handoff: `C:/Airplane/32-21-02 ERJ 190 NLG/32-21-02_20260924_v01_config_{import,verify}.sql` and `_README.md`; workspace source/QA under `storage/app/codex/manual-package-import/32-21-02/20260924-config/`. Import adds 121 conditional nested links from 11 FIG1 `1-780…K` root options to FIG8 `8-150…B` (3 each), FIG9 `9-390…C` (4 each), FIG10 `10-30…C` (4 each); four exact SB07 axle candidates and two exact `9-140A` spacer links (qty 2) on FIG9 `9-390B/C`. It does not add Parts, checkbox flags, TDR orders or WO selections. Pinned SELECT-only production preflight `24/Sep/2026 21:16:27 UTC`: 11 roots and 11 candidate lower options present, zero relevant root/tube links, `choice_slot` absent. A later pinned SELECT confirms all 22 option IPL/P/N identities match the handoff guards. SQL syntax checked in isolated testing DB with expected safe rollback; **not imported on production**. User must deploy code, run migration, then import whole SQL and run SELECT-only verification. FIG8 washers, FIG9 Cam/seals and old-tube axle details remain unresolved, so FIG8/9/10 and the manual remain **partial/non-green**. Do not update the production-only journal to complete before post-import audit.

### SB configuration-flow audit — 24/Sep/2026

Local/read-only verification of the requested four paths recorded in `storage/app/codex/manual-package-import/32-21-02/20260924-sb-configuration-audit.md`. `PartGroupsTest.php` 27 PASS; `LogCardAssemblyIdentityTest.php` 12 PASS (run sequentially). Lower ASSY purchase/cross-outs, quantity counting, and explicit Log Card ASSY identity work in their isolated tests. Production snapshot shows zero direct/nested links from all eleven root1-780…K options to Upper Torque Link8-150…B, Sliding Tube9-390…C, Main Fitting10-30…C, or SB07 spacer9-140/A; all four Sliding Tube lower groups also have zero axle/spacer links. Current root ordering therefore cannot select or traverse the requested conditional lower configuration. Adding every candidate child as an ordinary coverage would union mutually exclusive nested BOMs in procurement, Work Scope, and Log Card; SB07 tube P/N still allows two axle P/N. No local data/code or production changes, no new SQL handoff; status remains partial/non-green. The user-facing choice between an included child and a separately ordered child needs a reviewed representation before safe SQL.

### SB02/SB07 source review — 24/Sep/2026

User copied `sb2.pdf` and `sb7.pdf` into `C:/Airplane/32-21-02 ERJ 190 NLG/`. Read-only visual review; no local or production import SQL executed. SHA256: SB02 `FEDDF003F24E71F5DC1040FA69D2502CA98478433C465A8DFB261E3DDC15820E`; SB07 `DB80986BD151A980E83593D51E77BBF311327E469D32746986CEE54F65F4273D`. Raster evidence under `tmp/pdfs/32-21-02-sb/`.

SB02 is Embraer `190-32-0057` / OEM Liebherr `190-70453-32-02`. Reidentification table (PDF p26): Main Fitting `190-70500-401→-601` and `-403→-405`; Shock Strut `190-70453-401/-603→-605`. This is a configuration transition, not permission to include both Main Fittings in one ordered root. SB02 also requires concurrent OEM SB `190-70453-32-04/-05`; repair-only bushings/sleeve are not factory ASSY links.

SB07 is Embraer `190-32-0069` / OEM Liebherr `190-70453-32-07`. Reidentification table (PDF pp16–17): Sliding Tube `190-70465-401` + axle `190-70460-303/-305` → tube `190-70465-601`; tube `190-70465-403` + axle `190-70460-309/-311` → tube `190-70465-603`; spacer `190-70491-001` + any of those four oversized axles → spacer `190-70491-003`. Fleet maintenance record or physical axle inspection determines applicability; SB07 does not establish one unconditional root BOM. The older normal-axle configurations remain possible.

These documents settle P/N identity and conditional pairs, but not which variant is on a particular workorder. Existing resolver expands direct IPL families and nested ASSY options, but a simple set of all candidate links would mix variant-specific nested composition. Do not write/add such root links until the choose-one configuration mechanism is verified with the actual TDR/Log Card/PRL flows. Remaining FIG8 selected washers and FIG9 Cam/seal combinations are not resolved by SB02/SB07. Status stays partial; no new SQL handoff yet.

### Upper Torque Link applicability confirmed by user — 24/Sep/2026

User confirms `8-150 / 8-150A / 8-150B` are one ordinary letter-suffix IPL family (no separate Alternative P/N group) and **all three ASSY variants are eligible in every upper/root ASSY** `1-780…1-780K`. This is a choose-one lower ASSY variant for a given physical position, not three simultaneous assemblies. Existing production contains three distinct lower `assy` groups 698/699/700 (options1665/1666/1667), because their member compositions differ; do not delete or flatten them. Do not insert a fourth Alternative P/N group. A plain direct base-component link is insufficient if it does not traverse the chosen variant's own child composition. Implementation/SQL must preserve variant-specific nesting and cross-outs/Log Card/quantity, then verify per root and variant. User will supply service bulletins; no production data/code changes or handoff from this confirmation alone. Other unresolved FIG8 washers,FIG9 axle/Cam/SB07,FIG10 root Main Fitting remain pending.

### Fresh production verification — 24/Sep/2026

Pinned SSH, SELECT-only at 19:08:59 UTC; evidence `storage/app/codex/manual-package-import/32-21-02/20260921-full/production-postflight-20260924.json`. Production manual ID42 remains active. Parts FIG1–10: 115/18/33/30/46/20/56/86/102/125 = 631 active (FIG1 includes the deliberately preserved extra 1-451B), plus one archived history row. All original PDF identities, quantities, workbook flags,9SB and one In-Process Check Sheet remain confirmed by 951 unchanged checks.87 active groups,161 options,4031 coverages;37 exact `expand_ipl_family=0` links present (33 in FIG1 PRE/POST SB06 root ASSY,4 in FIG10 lower Main Fitting), with expected exact IPLs and quantities. Production Part Group coverage/composition resolvers and model contain exact-link support and their SHA256 hashes match local files.47 legacy assembly records retained.

The original 21/Sep postflight 967 checks now return 951 PASS and16 expected FAIL: fifteen prior exact-composition snapshots for the affected ASSY groups plus the former3994-coverage total. This is not evidence of a regression; the37 additions explain every changed assertion. Do not keep quoting967/967 as a current result. Full safe-scope Parts/XLS/SB/Check Sheet import is on production, but manual is **not complete**: root Shock Strut1-780 still lacks selected Upper Torque Link/washer applicability (FIG8), axle→Sliding Tube, Cam/seals, SB07 spacer/tube composition (FIG9), and choice of one Main Fitting variant at root (FIG10). Repair-only entries are intentionally excluded from factory ASSY. FIG1 exact links are now resolved. FIG2–7 have complete reviewed composition. Keep journal row non-green/partial until remaining root configurations are evidenced and verified. No production writes or deployment performed in this audit.

### Exact-member support and additive SQL prepared — 22/Sep/2026

Handoff saved and byte-hash checked in `C:/Airplane/32-21-02 ERJ 190 NLG/`: three SQL files, codeZIP (5PHP/Blade files plus migration), README.49feature testsPASS (26PartGroups +23LogCard/Scope/Legacy), browser saved-mode/rerender/desktop1600×1000/mobile390×844PASS,no page errors. Local QA created groups1999/2000 under codex admin; form auto-named them190-70500-401. After explicit user authorization on22/Sep/2026, both were soft-deleted locally with exact ID/code/manual/creator and unused-link guards (`20260922-exact/cleanup_local_qa.php`, PASS). Parts, retained option/coverage history and production unchanged. User deploys code from the local project, not the ZIP; handoff remains schema SQL then updated project code then exact_import.sql (37 links), pending production verification.

Run `storage/app/codex/manual-package-import/32-21-02/20260922-exact/`. Fresh pinned SELECT snapshot22/Sep/2026 13:48:49UTC confirms631activeParts/87groups/3994coverages. Production unchanged. Local code/schema adds per-coverage `expand_ipl_family` (default1 preserves legacy;0 exact IPL only) to procurement coverage, nested composition and Log Card membership; editor round-trips the flag and older clients preserve restrictions. Work Scope already uses exact direct rows, unchanged.

PDF pages10050/10051/10133 visually reconfirmed. Prepared37additive exact links in15existingASSY:33PRE/POST SB06 fastener links for1-5…K (PRE2/4/2,POST1/2/1),4MainFitting links10-30/30B→35 and30A/30C→35A. Isolated raw SQL1137checksPASS,identical repeat,450scope/qty runtime cases,45LogCard compositions,3rollback scenarios; existing1031target-groupedges and allParts/flags/history preserved. Local working manual data not overwritten. Schema migration applied locally; production handoff requires schemaSQL→PHP/Blade copy→dataSQL. Artifacts `32-21-02_20260922_v01_exact_{schema,import,verify}.sql`,codeZIP,README. New links pending user upload and production postflight; do not mark green or count these as deployed. BothHTMLjournal notes updated without changing completion. Remaining FIG8rootTorqueLink/washers,FIG9axle/tube/Cam/seals/SB07,FIG10rootMainFitting variant need configuration evidence; no guessed links.

### Production upload verified; HTML journal refreshed — 21/Sep/2026

User reported upload. Pinned read-only exact audit at21/Sep/2026 21:09:37UTC: **967/967 PASS, zero differences** (`production-postflight.json`). Fresh full manual snapshot `production-after-import.json`:631 active Parts,1 archived historical row,87 active groups,161 options,3994 coverages,9SB,1exactCheckSheet. All630 PDF identities/quantities and flags verified;607 matched IDs preserved,23newParts,extra1-451B retained. Cam/legacy/history unchanged. No production mutation performed by agent.

This verifies the delivered safe-scope SQL, not every omitted assembly relationship. Full-cycle FIG2–7 confirmed6/10; Parts extraction/import FIG1–10 confirmed10/10. Remaining composition restrictions are FIG1 PRE/POST singleton letter quantities, FIG8 upperTorqueLink/selectedwashers, FIG9 axle/tube/SB07/Cam/seals, FIG10 MainFitting variant/metal35vs35A. Repair-only items remain deliberately outside factory bundles. Keep status partial; no unqualified green full-manual claim.

Both `output/manuals-production-journal.html` and `output/manuals-production-20260915.html`, plus Markdown inventory, updated in this same task. Old42source-issues/608Parts/1group/missingCheckSheet removed from current row; now expected10FIG,Parts631,groups87,SB9,XLS✓,CheckSheet✓,date21/Sep/2026,status «Загрузка ✓; остались связи ASSY». Other manuals and user expected-FIG fields unchanged. Root cause of stale journal: the previous handoff updated the ledger/run manifest but not the independent embedded JSON in HTML. Backups and refresh evidence: `journal-before-postflight-*`, `journal-postflight-update.json`. Prior pending-upload entries below are historical and superseded by this verification.

### Full package SQL ready — 21/Sep/2026; awaiting user upload

Run `storage/app/codex/manual-package-import/32-21-02/20260921-full/`. Final artifacts `32-21-02_20260921_v01_import.sql`, `_verify.sql` (SELECT-only), `_report.md`; handoff folder `C:/Airplane/32-21-02 ERJ 190 NLG/`. Do not mark green until fresh post-import audit. Production unchanged; latest pinned read-only preflight21/Sep/2026 20:35:43UTC matches baseline exactly.

| FIG | Reviewed PDF Parts | New groups | Preserved groups | Production verified |
|---|---:|---:|---:|---|
| 1 | 114 | 23 | 0 | Pending upload |
| 2 | 18 | 1 | 0 | Pending upload |
| 3 | 33 | 0 | 0 | Pending upload |
| 4 | 30 | 0 | 0 | Pending upload |
| 5 | 46 | 2 | 0 | Pending upload |
| 6 | 20 | 1 | 0 | Pending upload |
| 7 | 56 | 1 | 0 | Pending upload |
| 8 | 86 | 14 | 0 | Pending upload |
| 9 | 102 | 14 | 1 Cam | Pending upload |
| 10 | 125 | 30 | 0 | Pending upload |
| Total | 630 | 86 | 1 | Pending upload |

Expected631 active Parts (630PDF+preserved1-451B),23 creates,607 matched active IDs preserved,1 archived row preserved. Source includes11 equipped roots1-5…K; common cross-FIG shock-strut roots reuse1-780…K. 45newASSY+32oversize+9Alternative,155newoptions/3988newcoverages plus existing Cam6options/6coverages. FIG3/4 parts included in root graph, no invented lowerASSY. Source flags LLP24/NDT72/CAD52/Paint52/KIT299; preserve old true flags and47legacy rows.9SB preserved exactly. Check Sheet2tasks stages5/7,3emptyblocks,8legend,blankstampfields. Source XLS SHA25669a90b58263addbb40fa464b21c1481b5b4aba3730d5a832a031f9caf56c36c8; content SHA25601c8840b3cacce279d5d0e1c8b6b385eea096e36f90ea6f97a4650161427e3a4.

Raw full-file SQL/comments,967SELECTchecks,identical repeat,450actualcoveragecases,45LogCardcompositioncases,6rollback scenarios allPASS in isolated local DB; scratch removed, workingDB untouched. SQL SHA256aa02357144393f6856caa0e18e6d7c747faacc9ddef8d9dcad473746e075d879. Source snapshots, decisions, per-FIG evidence, final CSV, expected JSON and tests in run folder. `verify_production.py` is prepared but must run only after user reports upload.

Confirmed safe-omission policy applied, not a complete certified SB configuration: axle→tube uncertain links, Cam+conditional seals, root→tube/spacerSB07, root→upperTQ and mainfitting configuration, selected/sharedwashers,repair-onlyinserts omitted. Also omit10-35/35A→mainfitting and1-510/520/530letterquantity branches because current automatic letter expansion cannot safely encode singleton applicability. Lower assemblies still available separately. Exact IPLs/reasons in report and groups-audit.json. All Parts retained. No unresolved user decisions for this safe scope. These omissions remain recorded for future completion and must not be hidden by a green full-composition claim.

### Full scan/package reopened — 21/Sep/2026, listed corrections approved

Progress update21/Sep/2026: all full-scan FIG1–10 table identities and quantities reviewed:103/18/33/30/46/20/56/86/102/125 detail Parts (619 total;11 orderable root rows still to integrate). Approved column P/N rather than TRUE PN/ORDER OVERLGTH notes; approved10-90/91 MFI corrections. Workbook reviewed mapping has zero unresolved issues: LLP24, NDT72, CAD52, Paint52, explicit KIT299. PRL46 maps only1-415A; PRL50 typo maps1-415B. Preserve existing1-451B and all old true flags, including unsupported-by-current-PRL KIT1-415/1-451B/9-470. User approved omission/reporting of uncertain ASSY edges and preservation of existing Cam group without root nesting. New composition graph, guarded SQL and isolated raw-file tests remain pending. This update supersedes the initial pending-extraction statements below; production unchanged, no complete status or SQL handoff yet.

User confirmed the two decision sets recorded in `pending-decisions.json`: "1. да по PDF 2. да по PDF". The two production P/N corrections and listed workbook remaps below are approved; IDs/history/existing true flags must be preserved. User also explicitly permitted this package's local OCR process-scoped PowerShell execution on21/Sep/2026. Final extraction, composition audit and SQL testing remain pending; this is not a completed handoff.

User supplied `20260921152630296.pdf`; source folder `C:/Airplane/32-21-02 ERJ 190 NLG/` contains that104-page full IPL scan (printed10037–10140, FIG1–10 with drawings), ten separate FIG PDFs/66 pages, and `190-70745-series.xlsx`. Run: `storage/app/codex/manual-package-import/32-21-02/20260921-full/`. Source hashes/roles in `package-manifest.json`; timestamp-named PDF is a full scan, not an extra FIG. Every full-scan page inventoried via overview; selected conflict pages reviewed at300DPI. Full per-row extraction and configuration graph audit are not complete.

Fresh pinned SELECT-only capture in `production.json`: manual42,608 active Parts+1 deleted history row, one active FIG9 Alternative group/6 options/6 coverages,47 legacy assembly records,9 SB, Check Sheet absent. SB normalized fields agree with workbook. Required workbook tabs present. Check Sheet:2 tasks at stages5/7,3 empty slots,8 legend entries, no parser issues; source SHA256 `69a90b58263addbb40fa464b21c1481b5b4aba3730d5a832a031f9caf56c36c8`. NDT Feedback/trailing PRL explicitly belong to32-53-09 and are excluded.

`pending-decisions.json` records visually confirmed production PN corrections requiring approval: ID6490/6-30 `170573008-901`→`170-73808-901` (printed10088); ID6770/10-280 `170-70478-001`→`170-70473-001` (printed10137). Workbook remap approval also required for listed NDT/CAD/Paint PN typos. Historical CSV is only a conflict-discovery candidate, not final PDF extraction: old9-140 is wrong while PDF/production/XLS agree;4 `.1` quantity artifacts require visual review. Candidate analyzer's30 issues are not a final package audit. No SQL handoff ready, no local/production business data changed. All FIG1–10 remain pending fresh Parts/ASSY/quantity verification and final SQL tests. Preserve historical evidence below.

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

### W107974 Log Card ASSY option selection — local fix 22/Sep/2026

Local WO463/manual54 reproduces the reported case: component3723, IPL3-425/P/N52103-101 has own ASSY identities52103-1 and52103-3. Group52/M54-F3-PISTON stores these as options145/146 (plus147/52103-1001 belonging to the other bare part). Log Card incorrectly inspected only the first option. Local code now enumerates eligible individual options, requires the technician's choice when ambiguous, and saves `assy_option_id` in the existing Log Card JSON alongside P/N/IPL. No new database column or import SQL. Ordinary and mobile UI tested on local WO463 without saving business data; selection146/52103-3 available, no JS errors. Save/reopen/print, invalid selections, legacy unambiguous requests and QA locks passed28 sequential feature tests. Evidence: `storage/app/codex/log-card-52103-diagnostic.php`, `tests/Feature/LogCardAssemblyIdentityTest.php`; browser screenshots in the current Codex visualization directory (`log-card-piston-desktop.png`, `log-card-piston-mobile-form.png`). Production was not changed or reverified in this task; package/FIG completion status and counts remain unchanged.

### W107990 focused quantity diagnosis — 22/Sep/2026

Pinned production SELECT confirmed WO473/manual54: active3754 IPL3-435A P/N52175-3 and3755 IPL3-430E P/N52120-11SR both have null units_assy. PDF3 page7/printed10045 visually verified:435A=2;430E=AR; original430D/52120-11=2. Deployed create-form and BushingPrlGrouping capacity fall back to1. Explicit oversize group41 contains430D/E, but430D is not is_bush, so the filtered bushing family excludes its quantity2. Group42 alternatives likewise have2 outside the bushing filter. Missing catalog quantity and filtered family allowance are separate issues; do not overwrite430E PDF AR with inferred2. Evidence `storage/app/codex/manual-package-import/32-21-06/20260922-w107990/` production.json,page7.png,report.md. Diagnostic only, no data/code changes; correction remains pending. Manual54 was not in the six-green-manual quantity handoff. Historical full-stage status remains partial.

PDF FIG 1–3, Parts, workbook flags, and 20 Service Bulletins passed production verification. The reviewed result intentionally preserves four existing-only components, so 505 source rows become 509 production Parts. The new group hierarchy was built and production-verified only for FIG 3; FIG 1–2 still require a documented group-composition audit before the whole manual can be called complete.

| FIG | Source P | Prod P | L | N | C | S | Pt | K | A | O | Alt | KG | Opt | Direct | Nested |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | 43 | 45 | 2 | 0 | 0 | 0 | 1 | 11 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 2 | 186 | 186 | 32 | 17 | 4 | 0 | 14 | 90 | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| 3 | 276 | 278 | 57 | 69 | 27 | 0 | 48 | 128 | 12 | 19 | 0 | 0 | 80 | 182 | 49 |
| **Total** | **505** | **509** | **91** | **86** | **31** | **0** | **63** | **229** | **12** | **19** | **0** | **0** | **80** | **182** | **49** |

## Update checklist

### 08/Oct/2026 — 32-11-15RM (78), Traveler display names cleaned locally

User requested removal of source prefix `XLS F - ` from template names. Renamed exactly 13 local templates, incrementing their revisions; no changes to steps, active/REVIEW states, TDR processes or prior application snapshots (hashes checked). Before-state: `storage/app/codex/traveler-import/32-11-15RM/20261007/titles-before-20261008.json`. Future plan builder omits the prefix; original import manifests/SQL remain historical artifacts and must not be rerun after this correction. Production handoff: `output/traveler-32-11-15RM-20261007/03-clean-template-names.sql` (rerunnable name-only update plus SELECT verification); production execution pending user, no production changes. Source/hash and all previous package-stage statuses unchanged.

### 07/Oct/2026 — 32-11-15RM (78), Traveler production postflight PASSED

Read-only verification on current production 72.251.11.16 at 18:31 America/Toronto: 13 templates / 164 steps, exactly 6 active and 7 inactive REVIEW, all revision 1. All 164 byte-exact step/specification/process-name/order/source-group checks returned exact_matches=1; no missing manual_processes links or extra templates. Evidence: `output/traveler-32-11-15RM-20261007/production-verification-20261007.json`. Source/hash remain `//F519/backup4/SHOP/WO Packages/4259A_4260A0000-ser   F.xlsx`, SHA256 `1545395b36a73af7d28dd1fd5f3d539d2863b3804147220012735f48edb8dc61`. Production description is TEXT, Traveler QR setting enabled. Read-only source inspection confirms single-group application, reapply-after-deletion logic, 11px process print text and paginated table installed; no production apply/print action was invoked. Source group numbers still match original import; deployed application combines external steps into one Traveler as subsequently requested. Corrected local `02-verify.sql` summary GROUP BY for MariaDB ONLY_FULL_GROUP_BY; second full production SELECT pass succeeded. No production mutations. REVIEW blockers (blank Zone/FIG/EC, conditional operations/missing vendor) remain unchanged; these seven templates cannot be applied until reviewed. Traveler import verified only; no new Parts/FIG/group/STD/KIT/SB audit or full-manual completion claim.

### 07/Oct/2026 — 32-11-15RM (78), Traveler local import and production SQL handoff

Supersedes the pending Traveler audit below, not other manual stages. User chose original Main Fitting tab without(2), all steps/internal standalone/external grouped, and corrected three32-11-14RM references to32-11-15RM. Local import:13 templates/164 steps;6 active,7 inactive REVIEW (blank Zone/FIG/EC or conditional Shot peening/missing vendor). Main Fitting produces exact2821-0111 and2822-0111 definitions. Added83 exact process definitions and88 manual links; no existing catalog/template rows changed; WO/TDR/TDR processes/Parts/groups hashes unchanged. Source/hash unchanged from audit below. Versioned handoff: `output/traveler-32-11-15RM-20261007/{00-schema.sql,01-import.sql,02-verify.sql,manifest.json,IMPORT.txt,sql-test.json,local-verification.json}`. Source backup/audit/local before-after: `storage/app/codex/traveler-import/32-11-15RM/20261007/`. Isolated MySQL test passed schema install, dry-run rollback,164 exact source matches, repeat idempotence and edited-template rejection. Working local DB passed164 matches and application FIG/ZONE validation. Production snapshot lacks template tables; production SQL execution and postflight PENDING USER. No production writes; no Parts/FIG/group/STD/KIT/SB import or package completion claim. Global skill `avia-traveler-workbook` has read-only extraction and audit helpers plus case-specific mapping notes.

### 07/Oct/2026 — 32-11-15RM (78), Traveler workbook audit, import pending

Traveler-only stage; no Parts/FIG/group/STD/KIT/SB import or full-package completion claim. Source: `//F519/backup4/SHOP/WO Packages/4259A_4260A0000-ser   F.xlsx`, SHA256 `1545395b36a73af7d28dd1fd5f3d539d2863b3804147220012735f48edb8dc61`. Artifacts: `storage/app/codex/traveler-import/32-11-15RM/20261007/{source.xlsx,workbook.json,local-before.json,production-before.json,audit.json}`. All 29 sheets inventoried, 13 PART TRAVELER sheets extracted; 11 exact full-P/N matches in production manual78. Two Main Fitting sheets use wildcard `282__-0111`, differing routes (16 versus15 steps); actual manual P/N2821-0111 and2822-0111 exist. Source blockers: internal-versus-external route grouping not yet confirmed; Main Fitting version unresolved; 2225-0014 and2821-0001 machining references32-11-14RM; blank Zone/FIG/EC; conditional shot peening. Other16 sheets require separate classification, not automatically imported. Production SELECT/SHOW snapshot confirms template tables absent. No local DB import or production data SQL yet; awaiting material route choices. Global skill `avia-traveler-workbook` prepared with read-only extraction/audit helpers. Existing manual completion status unchanged.

Whenever a manual is worked on, update both its summary row and per-FIG section with:

1. Exact manual number and production ID.
2. Source/run directory and manifest; retain PDF/workbook hashes in the run manifest.
3. FIGs supplied, extracted, imported, and group-audited.
4. Source and production Parts counts per FIG.
5. Group counts by type, options, direct coverages, and nested coverages per FIG.
6. Source-required workbook flags and current production true counts; Service Bulletin total.
7. Conflict count, user resolutions, SQL/handoff artifact, production postflight date, and any divergence.
8. Status change only after the evidence satisfies the definitions at the top of this file.

### 24/Sep/2026 — 32-11-14RM, production manual 95: W108010 / FIG 14 diagnostic
Read-only production verification: WO 495 uses ASSY option 499 (14-1C, 2821A0100-03). Component 7649 / IPL 14-134 / PN 2225-0109RS20 exists, is_bush=1, bush_ipl_num=14-130, not deleted. Oversize group 173 contains only options 518–521 / IPL 14-130 through 14-133. No option for component 7649, including soft-deleted entries. Coverage 929 links ASSY option 499 to original option 518. Deployed candidate query includes 14-134; WorkorderPartScopeResolver and bushingGroupsForManual exclude it. Unresolved: missing explicit FIG 14 oversize-group membership. No production changes or handoff; no PDF/workbook re-audit. This scoped diagnostic does not assert overall completion.

### 24/Sep/2026 — W108010 manual reassignment follow-up, 32-11-15RM (78)
Read-only verification after production changed at 07:23:24 America/Toronto: WO 495 now unit 609 / manual 78 / scope option 174 / group 63 / component 3503, all consistent with 32-11-15RM. Prior diagnostic accurately described earlier unit 607 / manual 95 / option 499. History: initially unit 609; changed to 607 on 18/Sep; restored 609 on 24/Sep. All 31 STD item rows and their component/process references belong to manual 78; package [78]. FIG14 IPL14-134 component3569 now included by deployed bushing selector. No saved bushing header/lines. Remaining cross-manual inconsistency: TDR2485 references component6135 (manual95,14-210A,2821-0111), while manual78 counterpart is3591. LogCard226 retains manual95 header/component6135/group165 and ASSY PN2821A0100-02. All15 TDR process rows reference13 distinct process definitions available in manual95 but absent from manual78; no RO/start/finish on these15 rows. Deployed WO update rebuilds STD items only, does not remap existing TDR/Log Card. Other populated WO-linked tables: mains, general task status, STD headers, print marks, media sequences. No production writes or SQL handoff. Original manual95 group173 omission remains a separate catalog finding; do not treat adding its missing option as the fix for this now-manual78 WO. Completion not asserted.

### 24/Sep/2026 — W108010 scoped SQL handoff (not executed)
User authorized full transfer to 32-11-15RM, with unmatched processes re-entered by technician. Artifacts: output/w108010-manual-transfer-20260924/{00-preflight.sql,01-apply.sql,02-rollback.sql,03-verify.sql,backup-before.json,expected-after.json,technician-reentry.csv,README.md}. TDR2485 component6135->3591; LogCard226 manual95->78/component3591/ASSYgroup63/option174 (received WO scope). Exact text/type matches kept: row5049 definition465->824 Local blending; row5080 definition728->169 AMS2649; old FIG descriptions cleared. Other13 unstarted process rows removed by guarded transactional SQL for manual re-entry; full backup and guarded rollback provided. No machining steps, STD source references or transfers depend on these records at audit. Isolated local MySQL test passed apply, changed-data rejection, repeat rejection, exact rollback. Read-only production SELECT preflight: all4 guards ready=1. MariaDB10.6 production execution/postflight pending user import. No manual catalog changes, package completion not asserted.

### 24/Sep/2026 — W108010 production postflight PASSED
User executed SQL; read-only verification saved to output/w108010-manual-transfer-20260924/production-postflight.json. WO495 unit609/manual78/scope174 correct. TDR2485 component3591/IPL14-210A/PN2821-0111/SN L1381/qty1 correct. LogCard226 manual78/component3591/group63/ASSYoption174/PN2821A0100-03 correct. Exactly2 process rows remain:5049->[824] Local blending,5080->[169] AMS2649; both definitions belong to manual78, old FIG descriptions cleared. All13 explicitly removed process IDs absent. 31 STD items manual78; zero mismatched component/process manual links. Bushing14-134 component3569 is included by deployed selector. Scoped link transfer complete; technician still must re-enter13 processes and FIG references for2 retained rows. This does not assert manual package completion.
