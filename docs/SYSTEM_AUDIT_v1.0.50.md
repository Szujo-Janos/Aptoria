# Aptoria v1.0.50 System Audit — GitHub Actions Public QA Gate

This audit covers the v1.0.50 cumulative release built from vv1.0.49.

## Scope

v1.0.50 adds a public GitHub Actions QA gate so the public repository can show automated release hygiene and test status.

## Release validation matrix

| Area | Status | Notes |
| --- | --- | --- |
| Version metadata | PASS | `VERSION` and config fallback resolve to `1.0.50`. |
| GitHub Actions workflow | PASS | `.github/workflows/php.yml` now runs a public repository QA gate on push and pull request. |
| Forbidden runtime files | PASS | The workflow fails if `.env`, `vendor/`, SQLite runtime DB or setup lock files are committed. |
| Composer validation | PASS | The workflow validates Composer metadata before installing dependencies. |
| PHP/test gate | PASS | The workflow performs PHP syntax checks, migrations, PHPUnit tests and the Aptoria security audit. |

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
