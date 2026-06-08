# Aptoria v1.0.55 – GitHub Actions Security Audit Full CI Hotfix

## Purpose

v1.0.55 closes the remaining GitHub Actions security audit failure that appeared after the v1.0.54 CI state patch.

The previous workflow correctly created a temporary `storage/app/installed.lock` file before running `php artisan aptoria:security-audit`, but the security status service still used the runtime setup gate method. That method intentionally returns `false` while Laravel is running in PHPUnit/testing mode, so the deployment audit reported `Setup lock` as failed even though the CI lock file existed.

## Fix

- Added `SetupStateService::hasLockFile()` for physical lock-file checks.
- Kept `SetupStateService::isLocked()` unchanged so PHPUnit/setup wizard routes are not blocked by local runtime locks.
- Updated `SecurityStatusService` to use the physical lock-file check for the deployment/security audit.
- Added explicit GitHub Actions precondition checks for:
  - `storage/app/installed.lock`
  - `APP_DEBUG=false`
  - strong CI-only `APTORIA_SETUP_TOKEN`
- Replaced current public release ZIP references with the short `aptoria-1.0.55.zip` naming format.

## Release hygiene

The release ZIP must still exclude `.env`, root `vendor/`, `node_modules/`, `database/database.sqlite`, `storage/app/installed.lock`, `storage/app/setup-token.txt`, Composer installer artifacts, runtime logs and runtime cache files.

## Expected GitHub Actions result

After pushing v1.0.55, the public QA gate should no longer fail on:

- invalid Laravel view/cache path;
- order-sensitive `Cache-Control` header assertions;
- `APP_DEBUG` state during the security audit;
- missing or weak setup token during CI;
- `Setup lock` while running the audit in testing mode.
