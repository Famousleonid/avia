# Dark theme palette and rollback map

Last audited: 14/Jul/2026.

This file is the project memory for the admin dark theme. The canonical palette is defined in `public/css/custom_bootstrap.css`; page styles must consume these tokens instead of introducing new hard-coded dark grays.

## Current palette: Aviation Night

| Token | Value | Purpose |
| --- | --- | --- |
| `--avia-bg` | `#141b24` | application/page background |
| `--avia-sidebar` | `#192431` | sidebar and footer |
| `--avia-surface` | `#1e2a37` | cards and main surfaces |
| `--avia-surface-raised` | `#263544` | headers and raised surfaces |
| `--avia-modal` | `#293746` | modal surface |
| `--avia-panel` | `#202c38` | tables and nested panels |
| `--avia-input` | `#16202a` | inputs and Select2 controls |
| `--avia-border` | `#405267` | borders and separators |
| `--avia-hover` | `#304154` | row/control hover |
| `--avia-active` | `#315f8c` | active navigation and rows |
| `--avia-primary` | `#5795d6` | primary actions |
| `--avia-info` | `#4db6be` | informational accent |
| `--avia-success` | `#45d19a` | clear emerald success/status accent |
| `--avia-warning` | `#d8a64e` | warnings |
| `--avia-danger` | `#d96c75` | destructive/error state |
| `--avia-text` | `#e8eef5` | primary text |
| `--avia-text-secondary` | `#b0becc` | secondary text |
| `--avia-text-muted` | `#8293a5` | muted text |

## Previous dark palette (rollback reference)

Before Aviation Night, colors were not centralized. These are the actual legacy values that appeared across the project:

| Area | Previous value |
| --- | --- |
| Bootstrap/page background and Select2 dropdown | `#121212` |
| application/content background | `#232525` |
| sidebar, footer and many raised blocks | `#343A40` |
| table/modal surface | `#2B3035` / `#2d3030` |
| input/table surface | `#212529` |
| nested surface | `#2D2D2D` |
| borders | `#495057` |
| active/primary | `#0d6efd` |
| success/status green | `#198754` |
| main text | `#f8f9fa` / `#dee2e6` |
| muted text | `#adb5bd` |

## What was changed

- Bootstrap dark variables, sidebar/navigation variables and modal variables were mapped to `--avia-*` in `public/css/custom_bootstrap.css`.
- Shared admin cards, tables, forms, dropdowns, Select2, hover/active states and buttons were mapped in `public/css/admin-theme.css`.
- Admin master/embed layouts, sidebar and footer use the shared tokens.
- Page-level dark overrides in Marketing, Quality, Vendor Tracking, Training, Mains/Photos and standalone measurement views were mapped to the same palette.
- Marketing `Existing` and other success states use the clearer emerald success token.

## Safe rollback

Do not reset whole files: several affected Blade files contain unrelated functional changes. To restore the previous visual theme, change only the values of `--avia-*` in `public/css/custom_bootstrap.css` using the table above. Page-level rules now reference those tokens and will follow the rollback automatically. If exact historical inconsistency is required, use Git history for this document's audit date and revert only theme-related hunks.

## Rule for future pages

Never add `#121212`, `#232525`, `#343A40`, `#2B3035`, `#2D2D2D`, `#212529`, or `#495057` inside a dark-theme selector. Use the semantic `--avia-*` token matching the element's role.
