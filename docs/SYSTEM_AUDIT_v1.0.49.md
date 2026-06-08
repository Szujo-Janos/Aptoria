# Aptoria v1.0.49 System Audit — Public README Installation Command Polish

This audit covers the v1.0.49 cumulative release built from vv1.0.48.

## Scope

v1.0.49 aligns public README and installation documentation with the exact Windows/XAMPP PowerShell workflow used for local release testing and public repository onboarding.

## Release validation matrix

| Area | Status | Notes |
| --- | --- | --- |
| Version metadata | PASS | `VERSION` and config fallback resolve to `1.0.49`. |
| README install commands | PASS | Windows/XAMPP ZIP and Git clone workflows now use the exact XAMPP PHP executable pattern. |
| Installation docs | PASS | `docs/INSTALLATION.md` mirrors the same PowerShell command structure. |
| Composer license metadata | PASS | `composer.json` now uses `proprietary`, matching the source-available license posture. |
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
