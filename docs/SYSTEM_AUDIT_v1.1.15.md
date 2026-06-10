# Aptoria v1.1.15 System Audit

Release: **v1.1.15 - System Health Diagnostics Pass**

## Scope

v1.1.15 strengthens the admin diagnostics area so Aptoria can be checked after installation, before scheduled monitoring is enabled, and before a QA workspace is handed over to another machine or server.

## Added / changed

- Expanded System Health diagnostics with cache, import/export, reporting/evidence and queue categories.
- Added temporary cache write/read/delete probe.
- Added import/export storage and PHP temporary upload directory checks.
- Added reporting and evidence storage checks for generated reports, project logos and finding attachments.
- Added DOMPDF availability note while keeping the built-in PDF renderer as the primary dependency-free engine.
- Added queue-specific readiness checks for database queue tables and sync queue mode.
- Added richer environment information: PHP binary, SAPI, loaded php.ini, OS, memory limit, execution time, upload/post limits and mail transport.
- Added CLI diagnostics examples directly on the System Health page.
- Extended JSON/CLI health evidence and regression coverage.

## Files touched

- `app/Services/System/SystemHealthService.php`
- `resources/views/system/health.blade.php`
- `resources/lang/en/messages.php`
- `resources/lang/hu/messages.php`
- `tests/Feature/SystemHealthDiagnosticsTest.php`
- `docs/QA_CHECKLIST.md`
- `docs/INSTALLATION.md`
- `SERVER_INSTALLER.md`
- `README.md`
- `CHANGELOG.md`
- `VERSION`

## Release ZIP hygiene

The release package must still exclude local/runtime state:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`
- runtime cache/session/log files

`public/assets/aptoria-ui/vendor` must remain included because it is the bundled UI asset dependency.

## Manual QA focus

- Open `/system/health` as admin.
- Export `/system/health.json`.
- Run `php artisan aptoria:health` and `php artisan aptoria:health --json`.
- Confirm cache, import/export, reporting/evidence and queue checks are present.
- Confirm warnings include actionable recommended fixes.
