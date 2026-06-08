# Aptoria v1.0.28 System Audit

Release: **v1.0.28 – Scheduled Monitoring Operations Pass**  
Base: **v1.0.27 – Deployment & Security Hardening Pass**

## Scope

This release focuses on scheduled monitoring operations. It does not introduce a new UI framework or change the Aptoria UI runtime asset direction.

## Added / changed

- `aptoria:run-monitors` now supports operational filters and output modes:
  - `--project=`
  - `--monitor=`
  - `--force`
  - `--dry-run`
  - `--json`
  - `--fail-on-warning`
  - `--fail-on-regression`
- The monitor runner now reports checked, due, ran, failed, warning, regression and skipped counts.
- Dry-run mode can validate Windows Task Scheduler or cron setup without executing external HTTP requests.
- JSON output can be used by wrappers, CI jobs or log processors.
- Added feature tests for monitor runner command behavior.
- Added `docs/SCHEDULED_MONITORING_OPERATIONS.md`.

## Release hygiene

The release package should not include:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The Aptoria UI vendor assets remain bundled in this private/internal branch.

## Risk notes

- Monitor runs can execute real GET/HEAD requests against configured project environments. Use `--dry-run` before enabling OS-level scheduling.
- The default command returns non-zero only for failed monitor runs. Use `--fail-on-regression` or `--fail-on-warning` when a stricter CI-style behavior is desired.
- Public GitHub publication still requires resolving the Aptoria UI asset redistribution question.

## Recommended local checks

```powershell
C:\xampp\php\php.exe artisan test --filter=ScheduledMonitorCommandTest
C:\xampp\php\php.exe artisan aptoria:run-monitors --dry-run
C:\xampp\php\php.exe artisan aptoria:run-monitors --dry-run --json
C:\xampp\php\php.exe artisan aptoria:security-audit
```
