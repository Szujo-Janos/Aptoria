# Scheduled Monitoring Operations

Release: **Aptoria v1.0.39 – Calendar UX, Activity Noise Reduction & Visual Timeline Hotfix**

Aptoria monitors are application-level jobs. The application stores each monitor's `next_run_at` timestamp and only runs enabled monitors that are due. The operating system scheduler only needs to call the runner regularly.

## Default Windows/XAMPP command

Use this command in Windows Task Scheduler:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50
```

Recommended interval: every **5–15 minutes**.

## Safe dry-run check

Before enabling the scheduled task, run:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --dry-run
```

This lists matching due monitors without sending any API requests or creating snapshots.

## Useful options

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --project=project-slug
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --monitor=12
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --force
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --json
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --fail-on-regression
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --fail-on-warning
```

### Option behavior

- `--limit=50` limits how many enabled matching monitors are inspected.
- `--project=` accepts a project id or slug.
- `--monitor=` runs or previews one monitor id.
- `--force` ignores `next_run_at` and runs matching enabled monitors immediately.
- `--dry-run` lists matching due monitors without executing scans.
- `--json` prints machine-readable output for logs/CI wrappers.
- `--fail-on-regression` returns exit code 1 if a regression is detected.
- `--fail-on-warning` returns exit code 1 for warnings or regressions.

## Linux cron example

```bash
*/10 * * * * cd /var/www/aptoria && /usr/bin/php artisan aptoria:run-monitors --limit=50 >> storage/logs/monitor-runner.log 2>&1
```

## Evidence created by monitor runs

Depending on monitor settings, a run can create:

- a safe scan run;
- a snapshot;
- a snapshot comparison;
- a regression summary stored on the monitor;
- dashboard monitor warning indicators.

## Exit codes

- `0`: no failed monitor run was detected.
- `1`: at least one monitor failed, or the selected fail-on option was triggered.

For scheduled background use, the default exit behavior is intentionally conservative: regressions are visible in Aptoria, but only hard monitor failures return non-zero unless `--fail-on-regression` or `--fail-on-warning` is used.


## Alerting counters

From v1.0.29+ the monitor runner summary includes `alerts` and `alert_failures`. In v1.0.39 alert events may come from dashboard, email or webhook delivery. Use `--json` for scheduler logs or CI processing. See `docs/MONITOR_ALERTING_OPERATIONS.md`.
