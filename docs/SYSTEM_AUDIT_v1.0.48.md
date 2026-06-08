# Aptoria v1.0.48 System Audit — Credits & Copyright Notice Pass

This audit covers the v1.0.48 cumulative release built from v1.0.47.

## Release goal

v1.0.48 strengthens public repository readiness by adding explicit ownership, copyright and attribution files while keeping runtime behavior unchanged.

## Scope

| Area | Status | Notes |
| --- | --- | --- |
| Version metadata | PASS | `VERSION` and config fallback now resolve to `1.0.48`. |
| Copyright notice | PASS | `NOTICE.md` was added with source-available ownership language. |
| Credits | PASS | `CREDITS.md` was added with owner, product direction, technology foundation and AI-assisted development disclosure. |
| README positioning | PASS | README now includes a Credits and copyright section and references `LICENSE`, `NOTICE.md`, `CREDITS.md` and `THIRD_PARTY_NOTICES.md`. |
| UI footer notice | PASS | Main app footer, landing footer and login screen show a lightweight `© 2026 János Szujó` notice. |
| Third-party notices | PASS | Third-party notices now point to the ownership and credits files. |
| Runtime behavior | PASS | No database migration, route, controller or workflow changes were introduced. |
| Asset stability | PASS | The `public/assets/aptoria-ui` vendor asset path from v1.0.46 remains unchanged. |

## Release hygiene

The release ZIP must continue to exclude:

- `.env`
- root `vendor/`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`
- runtime cache/session/log files

## Local QA required after install

```powershell
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
```

## Manual browser checks

- App footer shows `Aptoria v1.0.48 · © 2026 János Szujó`.
- Landing footer shows `v1.0.48 · © 2026 János Szujó`.
- Login screen shows `v1.0.48 · © 2026 János Szujó`.
- Browser console has no missing Aptoria UI vendor assets.
- Public repository files exist: `LICENSE`, `NOTICE.md`, `CREDITS.md`, `THIRD_PARTY_NOTICES.md`.
