# Aptoria v1.0.30 System Audit

Release: **v1.0.30 – Monitor Email Delivery & Alert History Pass**

## Baseline

This release continues the clean branch:

`v1.0.24 → v1.0.25 → v1.0.26 → v1.0.27 → v1.0.28 → v1.0.29 → v1.0.30`

It keeps the established Blade/Aptoria UI runtime direction.

## Scope

v1.0.30 completes the first practical alert delivery loop for scheduled monitors:

- dashboard alert records
- webhook delivery
- Laravel Mail email delivery
- per-monitor alert history view

## Added files

- `app/Mail/MonitorAlertMail.php`
- `config/mail.php`
- `resources/views/emails/monitors/alert.blade.php`
- `resources/views/emails/monitors/alert-text.blade.php`
- `resources/views/monitors/alerts.blade.php`
- `docs/MONITOR_EMAIL_DELIVERY_OPERATIONS.md`

## Updated areas

- `MonitorAlertService` now sends email alerts when `alert_email` is configured.
- `MonitorAlertEvent` has an `email` channel constant.
- Project and global monitor tables link to alert history.
- `.env.example`, `.env.production.example` and `.env.testing` include mail settings.
- `MonitorAlertingTest` covers email delivery and alert history rendering.

## Release hygiene

The release ZIP must exclude:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The Aptoria UI vendor assets remain included because this clean branch is still the Aptoria UI UI branch.

## Remaining risks

- Email delivery depends on correct SMTP configuration in production.
- Public GitHub distribution still requires a final decision on Aptoria UI asset licensing.
- Full PHPUnit execution must be confirmed on the local XAMPP project with Composer dependencies installed.
