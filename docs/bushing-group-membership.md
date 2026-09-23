# Bushing membership

From 22/Sep/2026, Bushing Processes accepts an active component when either:

- its legacy `is_bush` flag is set; or
- it is an active option in an active `Bushing: Original / Oversize` (`oversize`) group belonging to the same manual.

The group name alone and ordinary Alternative P/N/ASSY/KIT membership do not classify a part as a bushing. The group's PRL/STD cross-out scopes do not control bushing availability. Workorder part-scope filtering still applies.

Selection, save validation, PRL/KIT generation and KIT cross-outs use the same classification. Creating, saving or deleting a Bushing group leaves `is_bush` unchanged. The checkbox is independent and no longer requests Initial Bushing IPL. Existing legacy Initial values are preserved when toggling the flag. Previously imported groups also work without resaving. Source IPL quantities, existing selections and process history are not rewritten. No migration is required.

Explicit Bushing groups share a quantity allowance from their original option. Imported groups with legacy `standard` option kinds use their ordered first numeric standard option. This keeps AR/blank oversize quantities separate from the group's order allowance. Original and oversize selections together cannot exceed that allowance.

When creating a group, add the original first, then its oversizes. Existing explicit original identity is preserved when editing as long as that member remains in the group. Neither the Bushing checkbox nor a common legacy Initial Bushing IPL is required for explicit groups. Standalone flagged bushings retain the legacy grouping behavior.

Read-only local check for manual54 (32-21-06): group41 includes3-430D/E with allowance2; group42 includes3-435/435A/435B with allowance2. This does not correct the separately reported missing `components.units_assy` values or deploy code to production.
