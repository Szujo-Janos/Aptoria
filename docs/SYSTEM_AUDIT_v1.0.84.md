# Aptoria v1.0.84 - Export Credits & Attribution Pass System Audit

## Scope

This release adds professional Aptoria attribution to reports and downloadable exports. The goal is to make shared QA artifacts identify the tool, version, public repository, author and source-available license terms.

## Changes

- Added `App\Services\Exports\ExportCreditService`.
- Added Markdown attribution footers to project, scan, compare, release readiness, QA release gate, custom QA and evidence Markdown reports.
- Added structured `generated_by` metadata to Settings, project Settings, snapshot and QA evidence JSON exports.
- Added `APTORIA_CREDITS.txt` to QA Evidence Pack ZIP exports.
- Added Aptoria product metadata to calendar `.ics` exports.
- Added attribution columns to endpoint inventory CSV export.
- Added `tests/Feature/ExportCreditTest.php`.

## Release Hygiene

- `VERSION` is `1.0.84`.
- Release ZIP root is `aptoria-1.0.84/`.
- Root `vendor/` is excluded.
- `.env` and local SQLite/runtime files are excluded.
- GitHub Actions, Windows/XAMPP scripts and local Aptoria UI vendor assets are retained.
