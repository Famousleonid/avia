# Quantum RO Remaining Apply Audit

Generated: 2026-05-29 16:20:24

| Metric | Count |
|---|---:|
| Buffer rows before cleanup | 2080 |
| Deleted rows: empty WO or WO not in avia | 1439 |
| Buffer rows after cleanup | 641 |
| Ready rows | 150 |
| Remaining unresolved rows | 491 |
| Quantum vendors in remaining rows | 16 |
| Vendor unmatched/ambiguous | 0 |

Files:

- Deleted rows backup: `quantum_ro_deleted_missing_wo_20260529_162022.csv`
- Remaining unresolved rows: `quantum_ro_remaining_unresolved_20260529_162022.csv`

Status counts after cleanup:

| Status | Rows |
|---|---:|
| `DETAIL_COMPONENT_NOT_FOUND` | 69 |
| `DETAIL_MULTIPLE_TDR_PROCESSES` | 253 |
| `DETAIL_NO_TDR_PROCESS` | 4 |
| `DETAIL_PROCESS_MULTIPLE_TARGETS` | 36 |
| `DETAIL_PROCESS_TARGET_NOT_FOUND` | 55 |
| `DETAIL_PROCESS_UNMAPPED` | 4 |
| `READY_DETAIL_UNIQUE_TDR_PROCESS` | 22 |
| `READY_PROCESS_UNIQUE_TDR_PROCESS` | 27 |
| `READY_STD_EXISTING_TARGET` | 101 |
| `STD_TARGET_MISSING_CREATE_NEEDED` | 70 |
