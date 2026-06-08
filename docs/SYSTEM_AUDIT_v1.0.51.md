# Aptoria v1.0.51 System Audit — Portfolio Showcase Documentation Pass

This audit covers the v1.0.51 cumulative release built from vv1.0.50.

## Scope

v1.0.51 adds portfolio/showcase documentation for public GitHub presentation while keeping the runtime code unchanged from the QA gate release line.

## Release validation matrix

| Area | Status | Notes |
| --- | --- | --- |
| Version metadata | PASS | `VERSION` and config fallback resolve to `1.0.51`. |
| Portfolio showcase | PASS | `docs/PORTFOLIO_SHOWCASE.md` documents the public demo narrative and screenshot rules. |
| README presentation | PASS | README now links the portfolio showcase and screenshot placeholders. |
| Public checklist | PASS | GitHub/public readiness docs mention the portfolio-safe showcase layer. |
| Runtime behavior | UNCHANGED | No controller, route, migration or UI runtime behavior was intentionally changed. |

## Public repository posture

Aptoria remains source-available, not open-source. Public repository presentation must keep ownership, license, credits and third-party notices visible.

## Local QA expectations

Run the Windows/XAMPP update template from `README.md` or `docs/INSTALLATION.md`, then verify:

```powershell
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan aptoria:security-audit
```

## Release package rules

The cumulative ZIP must not contain `.env`, root `vendor/`, `database/database.sqlite`, `storage/app/installed.lock`, `storage/app/setup-token.txt`, Composer installer artifacts or runtime logs/cache files.
