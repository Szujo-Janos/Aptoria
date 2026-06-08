# Aptoria v1.0.29 System Audit

Release: **v1.0.29 – Monitor Notifications & Alerting Pass**  
Base branch: clean v1.0.24 line, continuing from v1.0.28.

## Scope

v1.0.29 focuses on operational alerting for scheduled monitoring. It keeps the established Blade/Aptoria UI runtime direction.

## Added

- `monitor_alert_events` table for dashboard/webhook alert history.
- Alerting fields on scheduled monitors:
  - `alert_email`
  - `alert_webhook_url`
  - `alert_on_recovery`
  - `last_alert_at`
  - `last_alert_status`
- `App\Models\MonitorAlertEvent`.
- `App\Services\Monitors\MonitorAlertService`.
- Scheduled monitor runner summary now reports:
  - `alerts`
  - `alert_failures`
- Monitor create/edit form now exposes alert settings.
- Monitor lists show last alert time/status.
- `tests/Feature/MonitorAlertingTest.php`.
- `docs/MONITOR_ALERTING_OPERATIONS.md`.

## Alerting behavior

Alerts are state-change based:

- healthy/never-run → warning/regression/failed creates an alert.
- warning/regression/failed → another non-healthy state creates an alert.
- warning/regression/failed → healthy creates a recovery alert when enabled.
- repeated same-status failures are not duplicated.

## Release hygiene

Release ZIP must exclude:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

Release ZIP must keep:

- `.env.example`
- `.env.production.example`
- `.env.testing`
- `public/assets/aptoria-ui/vendor`

## Manual verification

Run locally after installing dependencies:

```powershell
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan test --filter=MonitorAlertingTest
C:\xampp\php\php.exe artisan aptoria:run-monitors --dry-run --json
```
