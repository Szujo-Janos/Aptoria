# Aptoria v0.0.50 Hotfix - Table Auto Sizing & Auth Logo Alignment

## Purpose

This hotfix corrects two UI regressions found after the Release Gate Workflow foundation:

1. Release Gate item tables and other resource tables could still use fixed/equal column sizing, which made action columns too narrow and left content columns stretched.
2. The login/auth logo could appear left-aligned because the SVG logo is rendered as a block element without an explicit auto margin.

## Table rule

Aptoria resource tables must now use content-driven column sizing:

- `table-layout: auto` for resource tables.
- Action columns use natural compact width.
- Status/action controls stay nowrap.
- Long text cells wrap or truncate inside the cell instead of forcing fixed column percentages.
- Table wrappers keep horizontal overflow available only when genuinely needed.

This replaces the older fixed-width fallback that was originally added to prevent panel overflow but later caused dense tables to become visually cramped.

## Auth logo rule

The auth/login logo uses `.aptoria-auth-brand` and `.aptoria-auth-logo` with explicit centered flex alignment and auto margins.

## QA checklist

- Release Gate item table action buttons are fully visible.
- Release Gate item table columns size naturally to content instead of equal fixed widths.
- Resource tables still remain inside their card panels.
- Actions column remains compact and right-aligned.
- Long endpoint/checksum/path/error values do not break the page.
- Login logo is centered above the tagline.
- Dark/light mode remains unaffected.
