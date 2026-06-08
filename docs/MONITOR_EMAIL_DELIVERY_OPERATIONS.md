# Monitor Email Delivery Operations

Release: **Aptoria v1.0.39 – Calendar UX, Activity Noise Reduction & Visual Timeline Hotfix**

Aptoria can send monitor status-change alerts through Laravel Mail when a monitor has an **Alert email** value.

## When email alerts are sent

Email follows the same state-change rule as dashboard and webhook alerts:

- healthy / never run → warning, regression detected or failed
- warning / regression / failed → another problem status
- warning / regression / failed → healthy, when recovery alerts are enabled

The same problem status is not sent repeatedly on every run. This avoids alert spam.

## Local development defaults

`.env.example` uses:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=aptoria@example.com
MAIL_FROM_NAME="Aptoria"
```

This writes mail to Laravel logs instead of sending real mail.

## Production SMTP

Configure `.env` from `.env.production.example`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=change-this-user
MAIL_PASSWORD=change-this-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=aptoria@example.com
MAIL_FROM_NAME="Aptoria"
```

Then clear config cache:

```powershell
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan config:clear
```

## Alert history

Each delivery attempt is stored in `monitor_alert_events` with:

- channel: dashboard, email or webhook
- severity
- status and previous status
- delivery status
- delivery message
- delivered timestamp

Open **Project → Monitors → Alerts** to review a monitor's alert history.

## QA commands

```powershell
C:\xampp\php\php.exe artisan test --filter=MonitorAlertingTest
C:\xampp\php\php.exe artisan aptoria:run-monitors --dry-run --json
```
