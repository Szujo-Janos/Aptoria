# Aptoria v1.0.91 – Database Import/Export & Hard Reset Pass

## Scope

This audit covers the v1.0.91 maintenance pass focused on full database backup/restore and full application hard reset behavior.

## Implemented changes

- Added an admin-only Database maintenance tab under Settings.
- Added full database JSON export for all Aptoria database tables and rows.
- Added full database import/restore from an Aptoria JSON export.
- Added schema hash validation before restore, preventing accidental import into a different schema.
- Added typed restore confirmation: `IMPORT DATABASE`.
- Added hard reset with typed confirmation: `HARD RESET`.
- Hard reset deletes application data and users, preserves migration metadata, removes `storage/app/installed.lock`, logs the current user out and redirects to `/setup`.
- Added warnings that `.env`, APP_KEY, vendor files, storage uploads and setup lock files are not part of the JSON database export.
- Added regression tests for export payload, restore, import confirmation and hard reset lock removal.

## Expected first-run behavior after hard reset

After hard reset:

1. Normal authenticated pages are no longer usable because the setup lock is removed.
2. The next browser request redirects to `/setup`.
3. The existing `.env` and database schema remain in place.
4. A new admin user must be created from setup.
5. Setup must be finished again before normal use.

## Safety notes

- Full database import is intentionally destructive and replaces current table data.
- The JSON export is a database backup, not a full filesystem backup.
- Encrypted auth profile values require the same `APP_KEY` when restored.
- Hard reset does not delete `.env`; this keeps the Laravel app bootable and avoids breaking APP_KEY-dependent runtime behavior.

## Release validation

- PHP syntax check must pass for the new controller, service, route file, translations and tests.
- Release ZIP must exclude `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` and `storage/app/setup-token.txt`.
- `public/assets/aptoria-ui/vendor` must remain included.
