# Monitor Notifications & Alerting Operations

Release: **Aptoria v1.0.39 – Calendar UX, Activity Noise Reduction & Visual Timeline Hotfix**

Aptoria scheduled monitors can now create alert records when a monitor state changes. This is intentionally lightweight and safe for a self-hosted Laravel/XAMPP installation.

## What triggers an alert

An alert is created when the monitor status changes into one of these states:

- `failed`
- `warning`
- `regression_detected`

A recovery alert is also created when the previous status was non-healthy and the new status becomes `healthy`, if **Alert when recovered** is enabled on the monitor.

Repeated runs with the same failing status do not create duplicate dashboard alerts. This avoids notification spam when a broken API stays broken for several scheduled runs.

## Alert channels

### Dashboard alert record

When **Show warnings on dashboard** is enabled, Aptoria stores a `monitor_alert_events` record. The monitor list shows the last alert time and last alert status.

### Webhook JSON

If a monitor has a valid HTTP(S) webhook URL, Aptoria sends a JSON POST payload on the same state-change events.

Example payload:

```json
{
  "app": "Aptoria",
  "version": "1.0.39",
  "event": "monitor_status_changed",
  "monitor_id": 12,
  "monitor": "Daily staging regression watch",
  "project_id": 3,
  "project": "Example API",
  "environment": "Staging",
  "status": "regression_detected",
  "previous_status": "healthy",
  "severity": "critical",
  "message": "Regression detected on 2 endpoint(s).",
  "scan_run_id": 44,
  "snapshot_id": 19,
  "compare_run_id": 7,
  "next_run_at": "2026-06-08 10:15:00",
  "alert_email": "qa@example.com",
  "triggered_at": "2026-06-07T15:45:00+00:00"
}
```

Webhook delivery status is stored on the alert event as `sent`, `failed`, `skipped` or `recorded`.

## Email field

The monitor form includes an **Alert email** field. From v1.0.39 Aptoria sends a Laravel Mail notification on monitor state changes when mail is configured. Local installs default to the `log` mailer; production should use SMTP.

## Recommended Windows Task Scheduler command

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50
```

For CI/scheduler logs:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50 --json
```

For strict scheduled regression gates:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50 --fail-on-warning --fail-on-regression
```

## QA checks

- Create a monitor with dashboard alerts enabled.
- Add a webhook URL only if the target endpoint is trusted.
- Force a monitor run against an inactive project or a known failing endpoint.
- Confirm a monitor alert record is created.
- Confirm webhook delivery is marked `sent`, `failed` or `skipped`.
- Confirm repeated same-status failures do not create duplicate state-change alerts.
