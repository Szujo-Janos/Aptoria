# Aptoria v1.0.92 – Database Import FK Restore Hotfix

Date: 2026-06-09

## Scope

This audit covers the v1.0.92 hotfix for the full database import/restore flow introduced in v1.0.91. The failing case was a SQLite foreign key violation during restore when exported child rows were inserted before their parent rows.

## Findings

- The v1.0.91 import path validated the export schema correctly, but used natural table-name ordering for restore.
- Natural ordering can place child tables such as `auth_profiles` before parent tables such as `projects`.
- In test and SQLite environments where foreign key enforcement remains active inside the broader test transaction, this produced `SQLSTATE[23000]: FOREIGN KEY constraint failed`.

## Changes

- Added dependency-aware table ordering based on database foreign key metadata.
- Import now inserts parent tables before child tables.
- Import and hard reset now delete rows in reverse dependency order.
- SQLite restore/reset now performs `PRAGMA foreign_key_check` after the operation.
- Auto-increment reset after import now targets only tables that have an `id` column.

## Expected behavior

- Full database export still produces the same structured JSON format.
- Full database import restores exported projects, users, auth profiles, endpoints, scans, reports and settings without foreign key violations.
- Hard reset still clears application data and returns the system to setup mode.
- Existing schema hash validation and typed confirmation requirements remain unchanged.

## Release hygiene

- Root `vendor/` is excluded.
- `.env` is excluded.
- `database/database.sqlite` is excluded.
- `storage/app/installed.lock` is excluded.
- `storage/app/setup-token.txt` is excluded.
- `public/assets/aptoria-ui/vendor` remains included.
