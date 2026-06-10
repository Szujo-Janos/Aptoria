# Aptoria v1.1.13 – Scheduled Monitor Runner Pass System Audit

**Version:** v1.1.13  
**ZIP:** `aptoria-1.1.13.zip`  
**Focus:** Scheduled monitor / cron-ready runner

## Summary

Aptoria v1.1.13 strengthens scheduled regression monitoring for unattended execution. Monitors can now be bound to a project regression test suite, the Artisan runner can filter by project, environment, suite and monitor, and scheduled runs can save machine-readable JSON summaries for audit evidence.

## Implementation review

| Area | Status | Notes |
| --- | --- | --- |
| Version metadata | PASS | `VERSION`, README and installer docs resolve to `1.1.13`. |
| Monitor model | PASS | `api_monitors.test_suite_id` links optional suite scope to a monitor. |
| UI | PASS | Monitor create/edit/list views expose suite scope beside environment and baseline settings. |
| CLI runner | PASS | `aptoria:run-monitors` supports project, environment, suite, monitor, force, dry-run, JSON and saved-output options. |
| Result persistence | PASS | Monitor database summary remains updated; command summaries can be saved under `storage/app/monitor-runs/` or a custom output path. |
| Windows / cron compatibility | PASS | Runner remains a plain Artisan command suitable for Windows Task Scheduler or cron. |
| Localization | PASS | English and Hungarian labels were added for suite scope and runner guidance. |
| Test coverage | PASS | Scheduled runner tests cover dry-run, project filtering, environment/suite filtering and saved JSON output. |
| Release hygiene | PASS | Release ZIP excludes runtime secrets and dependencies while preserving `public/assets/aptoria-ui/vendor`. |

## Manual QA checklist

1. Install from `aptoria-1.1.13.zip`.
2. Run migrations.
3. Create a monitor with an environment and test suite selected.
4. Execute `php artisan aptoria:run-monitors --project=<slug> --dry-run --json`.
5. Execute `php artisan aptoria:run-monitors --environment=staging --suite="Smoke Regression" --dry-run`.
6. Execute `php artisan aptoria:run-monitors --save-json` and confirm the JSON file is written.
7. Run `php artisan test`.

## Notes

The runner is intentionally non-interactive and safe for scheduled execution. It only runs enabled monitors that are due unless `--force` is provided.
