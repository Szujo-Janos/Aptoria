# Aptoria v1.0.59 – Reports & Export Settings Wiring

## Scope

v1.0.59 connects saved Reports & Exports settings to report generation defaults and export filtering.

## Activated report controls

- Default report type changes the report builder section selection.
- Executive summary, release readiness, QA evidence and endpoint inventory sections can be enabled/disabled by settings.
- Failed-endpoints-only export mode filters endpoint CSV and scan markdown rows.
- Generated report timestamps use the configured date/time formatting service.
- Report footer copyright is appended when enabled.

## Result

Reports & Exports settings now change generated output instead of only being saved metadata.

## Release hygiene

- ZIP name: `aptoria-1.0.59.zip`.
- Forbidden runtime files remain excluded.
