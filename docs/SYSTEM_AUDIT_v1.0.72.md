# Aptoria v1.0.72 - Settings Functional Audit Hotfix

## Scope

This release audits the Settings system in the current Aptoria codebase and removes controls that were visible but had no runtime effect. It preserves the existing endpoint inventory, safe GET/HEAD scan engine, scan modal, reports, snapshots, release readiness, QA evidence, calendar, setup flow, Windows/XAMPP scripts, GitHub Actions and local `public/assets/aptoria-ui/vendor` assets.

## Findings And Fixes

- Clean ZIP runtime folders were missing in the extracted project and could break cache/view initialization. Added `.gitkeep` files under the required Laravel `storage/framework/*` and `storage/logs` paths.
- Several assertion default Settings fields were exposed even though they were only referenced by dead code. Removed those fields from the active Settings defaults and removed the unused helper code.
- `project.notes` was saveable but not visible anywhere after saving. Added it to the project detail Settings summary.
- Dashboard theme, table density and sidebar state now have visible CSS behavior through the app layout classes.
- The help workflow page no longer checks the removed `app.enable_public_demo_hints` key.
- The extracted v1.0.71 source had an empty `public/assets/aptoria-ui/vendor` tree while layouts referenced local files from it. Restored the complete 32-file local asset set and added regression coverage.
- Added automated audit coverage for Settings rendering, persistence, runtime references, misleading UI copy, UI behavior, session timeout behavior and project notes display.

## Automated Validation

- `php artisan test --filter='SettingsFunctionalAuditTest|SettingsCenterTest|SettingsLocalizationTest|ProjectSettingsTest|AssertionEvaluationTest'`
- Result during development: 19 passed, 1599 assertions.
- `php artisan test`
- Full result: 99 passed, 2266 assertions.
- `php artisan migrate --seed` completed against an isolated temporary SQLite database.
- `php artisan serve --host=127.0.0.1 --port=8142 --no-reload` reached the Laravel `Server running` state.

## Release Hygiene

- `VERSION` is `1.0.72`.
- `config/aptoria.php` reads `VERSION` as the release source of truth.
- Release ZIP excludes root `vendor/`, `.env`, SQLite databases/backups, `storage/app/installed.lock`, `storage/app/setup-token.txt`, runtime cache files and generated storage content.
- Release ZIP keeps `.github/workflows/php.yml`, Windows/XAMPP scripts and local Aptoria UI vendor assets.

## Manual QA Focus

Use `docs/QA_CHECKLIST.md` for the full manual pass. The highest-priority checks are:

1. Save one field in every Settings group.
2. Confirm Settings export JSON contains saved values.
3. Confirm no misleading activation copy appears on Settings.
4. Confirm dashboard UI settings change visible rendering.
5. Confirm session timeout follows the configured value.
6. Confirm project notes appear on project detail after saving.
7. Confirm endpoint inventory and safe GET/HEAD scan still work.
