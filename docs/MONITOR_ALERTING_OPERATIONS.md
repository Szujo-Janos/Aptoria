# Monitor Alerting Operations

Aptoria v1.1.14 supports monitor-level dashboard, email and webhook notifications.

## Channels

Each monitor can use any combination of:

- dashboard alert history;
- email delivery through Laravel Mail;
- HTTP(S) webhook JSON delivery.

## Trigger rules

The monitor form includes configurable trigger checkboxes for:

- critical findings;
- high findings;
- HTTP 5xx responses;
- sensitive data exposure;
- broken auth / unauthenticated access;
- schema drift;
- recovery to healthy.

A scheduled monitor run evaluates these signals after the safe scan and optional regression suite execution.

## Alert fingerprinting

Aptoria stores a `last_alert_fingerprint` on the monitor. This reduces repeated notification spam for unchanged problem states while still allowing a new alert when the trigger mix or counts change.

## Test notification

Open **Project → Monitors → Alerts** for a monitor and click **Send test notification**.

Expected result:

- a dashboard alert event is recorded;
- email is sent if `alert_email` is configured and mail works;
- webhook JSON is posted if `alert_webhook_url` is configured.

## Global alert center

Open `/monitor-alerts` or the **Open alerts** shortcut from the monitor pages.

Available filters:

- channel;
- severity;
- only open alerts.

## CLI evidence

```powershell
C:\xampp\php\php.exe artisan aptoria:run-monitors --limit=50 --save-json
```

Saved summaries are written under `storage/app/monitor-runs/` and contain alert counts plus scan alert signal metadata.
