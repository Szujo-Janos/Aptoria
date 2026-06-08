# Aptoria v1.0.57 – Settings Wiring Pass I

## Scope

v1.0.57 activates the first group of Settings Center controls so saved preferences influence runtime behavior instead of remaining UI-only metadata.

## Activated areas

- Default login landing route now uses `app.default_landing_page`.
- New-project redirect now uses `app.default_project_view`.
- Dashboard scan trend range uses `app.default_dashboard_range_days`.
- Dashboard widgets respect saved UI visibility switches.
- Scan creation exposes enabled scan profiles.
- SafeProbeService applies enabled scan profile runtime limits.
- SafeProbeService uses configured allowed methods while still respecting safe/destructive method guards.
- Dangerous query/path keyword guards are enforced before HTTP probing.
- Fail-fast scan limits and total scan runtime limit are enforced.
- Risk scoring uses configured risk weights for key signals.

## Release hygiene

- ZIP name stays short: `aptoria-1.0.57.zip`.
- Forbidden runtime files remain excluded.
