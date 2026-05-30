# Quantum RO Apply Audit

Generated: 2026-05-29 16:16:37

Rules used:

- STD list only when PN is exactly `NDT` or `CAD`.
- `Nital Etch`, `CAD Plate`, `CAD Removal`, `Passivation`, and `Stress Relief` are treated as detail/WO processes, not STD lists.
- Bushing rows are deferred for the later bushing sync step.
- WO matching normalizes Quantum `W107733` to avia `107733`.
- Vendor matching uses current MySQL collation, same as Laravel queries.

Totals:

| Metric | Count |
|---|---:|
| Quantum buffer rows | 2080 |
| Ready rows | 150 |
| Not ready / needs decision rows | 1930 |
| Quantum vendors | 22 |
| Vendors with one avia match | 22 |
| Vendors with no/ambiguous avia match | 0 |

Status counts:

| Status | Rows | Meaning |
|---|---:|---|
| `BUSHING_NO_WO_DEFERRED` | 38 | Bushing sync is intentionally deferred. |
| `DETAIL_COMPONENT_NOT_FOUND` | 69 | WO exists, but PN did not match a TDR component. |
| `DETAIL_MULTIPLE_TDR_PROCESSES` | 253 | Multiple candidate detail process rows. |
| `DETAIL_NO_TDR_PROCESS` | 4 | Component exists, but no process row exists. |
| `DETAIL_PROCESS_MULTIPLE_TARGETS` | 36 | Mapped process has multiple candidate rows. |
| `DETAIL_PROCESS_TARGET_NOT_FOUND` | 55 | Mapped process does not exist under WO. |
| `DETAIL_PROCESS_UNMAPPED` | 4 | Process-like Quantum row has no mapping rule yet. |
| `NO_WO_IN_QUANTUM` | 52 | Quantum did not provide WO. |
| `READY_DETAIL_UNIQUE_TDR_PROCESS` | 22 | Ready to apply into one detail tdr_process row. |
| `READY_PROCESS_UNIQUE_TDR_PROCESS` | 27 | Ready to apply into one mapped process row. |
| `READY_STD_EXISTING_TARGET` | 101 | Ready to apply into existing STD row. |
| `STD_TARGET_MISSING_CREATE_NEEDED` | 70 | Destination is known, but target STD row must be created first. |
| `WO_NOT_IN_AVIA` | 1349 | WO from Quantum is not present in avia. |

Unresolved CSV:

`quantum_ro_apply_unresolved_20260529_161636.csv`

