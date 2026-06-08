# Monitor Alert Triage Operations

Release: **Aptoria v1.0.39 – Calendar UX, Activity Noise Reduction & Visual Timeline Hotfix**

This document explains how to review and acknowledge scheduled monitor alerts after dashboard, email or webhook delivery has recorded an event.

## Purpose

Monitor alerts are state-change based. They are created when a monitor moves into a problem state, changes between problem states, or recovers when recovery alerts are enabled. v1.0.39 includes a lightweight triage loop so an operator can mark an alert event as reviewed without deleting the audit trail.

## Alert history

Open a monitor from:

- Project → Monitors → Alerts
- Global Monitor overview → Alerts

The history table shows:

- created time
- channel: dashboard, email or webhook
- severity
- current and previous monitor status
- delivery status and delivery message
- delivered time
- acknowledgement status
- acknowledgement note and user when present

## Acknowledging an alert

For an open alert event, enter an optional note and click **Acknowledge**.

Recommended notes are short operational comments, for example:

- `Known staging outage, backend team notified.`
- `Regression confirmed, release gate blocked.`
- `False positive after environment restart.`

Acknowledgement stores:

- `acknowledged_at`
- `acknowledged_by`
- `acknowledgement_note`

It does not modify the original alert payload, delivery status, scan evidence, snapshot or compare result.

## Operational meaning

Acknowledgement means: **a human has reviewed the alert event.**

It does not mean:

- the monitor is healthy again
- the regression is fixed
- the release is approved
- the underlying finding is closed

Use the monitor's next run, scan evidence, compare result, findings and release readiness dashboard for the actual quality decision.

## QA checks

```powershell
C:\xampp\php\php.exe artisan test --filter=MonitorAlertingTest
C:\xampp\php\php.exe artisan aptoria:run-monitors --dry-run --json
```

Manual checks:

1. Trigger or seed an alert event.
2. Open Project → Monitors → Alerts.
3. Verify the alert is shown as open.
4. Add a short acknowledgement note.
5. Submit the acknowledgement form.
6. Verify the row now shows acknowledged time and user.
7. Verify the original delivery status remains unchanged.
