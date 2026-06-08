# Aptoria v1.0.31 System Audit

Release: **v1.0.31 – Monitor Alert Triage & Acknowledgement Pass**

Baseline chain:

`v1.0.24 → v1.0.25 → v1.0.26 → v1.0.27 → v1.0.28 → v1.0.29 → v1.0.30 → v1.0.31`

This release continues the clean v1.0.24 branch. It keeps the established Blade/Aptoria UI runtime direction.

## Scope

v1.0.31 adds a lightweight operational triage layer for monitor alerts:

- acknowledgement fields on `monitor_alert_events`
- acknowledgement relation to the reviewing user
- per-monitor alert history acknowledgement form
- open alert counters on monitor lists
- test coverage for alert acknowledgement
- operational documentation for alert triage

## Database changes

New migration:

- `2026_06_04_003000_add_acknowledgement_fields_to_monitor_alert_events_table.php`

Added columns:

- `acknowledged_at`
- `acknowledged_by`
- `acknowledgement_note`

## Release hygiene

The release ZIP must not contain:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The Aptoria UI vendor assets remain present in this branch:

- `public/assets/aptoria-ui/vendor`

## Validation performed in packaging environment

- PHP syntax lint: expected PASS
- English/Hungarian translation key parity: expected PASS
- Release ZIP exclusion rules: expected PASS

Full PHPUnit execution must be run locally because the release ZIP intentionally excludes `vendor/`.

## Recommended local validation

```powershell
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan test --filter=MonitorAlertingTest
C:\xampp\php\php.exe artisan aptoria:run-monitors --dry-run --json
```
