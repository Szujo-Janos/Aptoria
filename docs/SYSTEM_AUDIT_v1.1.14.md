# Aptoria v1.1.14 System Audit

Release: **Email / Webhook Notification Pass**

## Scope

v1.1.14 turns the existing monitor alert foundation into a broader notification workflow for scheduled API QA monitoring.

## Implemented

- Monitor notification trigger settings for:
  - critical findings;
  - high findings;
  - HTTP 5xx responses;
  - sensitive data exposure;
  - broken auth / unauthenticated access;
  - schema drift.
- Dashboard, email and webhook delivery channels remain monitor-level configurable.
- Test notification workflow from the monitor alert history page.
- Global alert center at `/monitor-alerts` with channel/severity/open filters.
- Alert trigger summary stored in payload JSON and shown in alert history.
- Alert fingerprinting to reduce repeated notification spam for unchanged trigger states.
- Scheduled monitor runner summaries include alert counts and scan alert signal metadata.
- English/Hungarian translations added.
- Feature coverage extended for trigger fingerprint alerts and test notification delivery.

## Database changes

Migration added:

- `2026_06_10_001400_add_notification_trigger_fields_to_api_monitors.php`

New monitor fields:

- `alert_on_critical_finding`
- `alert_on_high_finding`
- `alert_on_http_5xx`
- `alert_on_sensitive_data`
- `alert_on_broken_auth`
- `alert_on_schema_drift`
- `last_alert_fingerprint`

## Files touched

- `app/Http/Controllers/ApiMonitorController.php`
- `app/Models/ApiMonitor.php`
- `app/Models/MonitorAlertEvent.php`
- `app/Services/Monitors/MonitorAlertService.php`
- `app/Services/Monitors/ScheduledMonitorService.php`
- `database/migrations/2026_06_10_001400_add_notification_trigger_fields_to_api_monitors.php`
- `resources/views/monitors/_form.blade.php`
- `resources/views/monitors/alerts.blade.php`
- `resources/views/monitors/global-alerts.blade.php`
- `resources/views/monitors/global-index.blade.php`
- `resources/views/monitors/index.blade.php`
- `resources/views/emails/monitors/alert.blade.php`
- `resources/views/emails/monitors/alert-text.blade.php`
- `resources/lang/en/messages.php`
- `resources/lang/hu/messages.php`
- `tests/Feature/MonitorAlertingTest.php`

## Release hygiene

The release ZIP must not contain:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The UI vendor assets under `public/assets/aptoria-ui/vendor` must remain included.

## Manual QA focus

- Monitor edit notification trigger persistence.
- Test notification delivery.
- Alert history trigger summary display.
- Global alert center filters.
- Scheduled monitor triggered alert creation.
- Full regression test run after migration.
