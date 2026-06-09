# Aptoria v1.0.93 System Audit

Release: **v1.0.93 - System Health Diagnostics Pass**

## Scope

This audit covers the new admin-only System Health diagnostics feature added after v1.0.92.

## Added

- `App\Services\System\SystemHealthService`
- `App\Http\Controllers\SystemHealthController`
- `/system/health` admin web page
- `/system/health.json` machine-readable JSON endpoint
- `aptoria:health` artisan command
- System Health navigation links in the sidebar, top toolbar and user menu
- Regression tests for service output, page rendering, JSON export and navigation reachability

## Diagnostic coverage

The diagnostics cover:

- PHP version and required PHP extensions
- Composer lock and `vendor/autoload.php` readiness
- APP_KEY, APP_URL, APP_ENV, APP_DEBUG and timezone
- setup lock presence
- bundled Aptoria UI vendor asset presence
- storage and bootstrap cache writability
- database connectivity, core table existence, table/row summary and SQLite foreign key integrity
- security posture checks from the existing security audit service
- database export payload readiness
- VERSION and README/CHANGELOG policy
- scheduled monitor and health artisan command registration
- queue/mail configuration visibility

## Safety

The feature is read-only. It does not modify database rows, settings, environment files, setup lock files or runtime folders.

## Packaging hygiene

The v1.0.93 release ZIP must remain cumulative and must not include:

- `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

`public/assets/aptoria-ui/vendor` must remain included.
