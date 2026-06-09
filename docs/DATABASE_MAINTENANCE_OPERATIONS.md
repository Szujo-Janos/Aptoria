# Database Maintenance Operations

Aptoria provides admin-only database maintenance tools under **Settings → Database maintenance**.

## Full database export

Use **Export full database** before risky maintenance, server migration, major upgrades or hard reset.

The export is a JSON file containing all Aptoria database tables and rows from the current installation.

It does not include:

- `.env`
- `APP_KEY`
- `vendor/`
- uploaded files from storage
- setup lock files
- the SQLite database file itself

Encrypted auth profile values are exported as stored in the database. They require the same `APP_KEY` after restore.

## Full database import / restore

Use **Import database** to restore a JSON export created by Aptoria.

The import is destructive. It replaces current database table data after validating that the schema hash matches the current installation.

Typed confirmation required:

```text
IMPORT DATABASE
```

## Hard reset

Use **Hard reset system** only when the installation must be wiped and returned to first-run setup.

Hard reset deletes application data and users, removes `storage/app/installed.lock`, logs the current user out and redirects to `/setup`.

Typed confirmation required:

```text
HARD RESET
```

Hard reset keeps `.env` and the database schema in place so the application remains bootable and setup can create a new admin user immediately.
