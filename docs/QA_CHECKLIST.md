# Aptoria v1.0.74 QA Checklist

Release: **v1.0.74 - Aptoria Rebrand Pass**
ZIP: `aptoria-1.0.74.zip`

## PowerShell Validation

- [ ] Run `C:\xampp\php\php.exe artisan optimize:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan view:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan config:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan route:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan migrate`.
- [ ] Run `C:\xampp\php\php.exe artisan test`.
- [ ] Start the app with `C:\xampp\php\php.exe artisan serve`.
- [ ] Log in with a valid admin user.
- [ ] Confirm the dashboard/footer shows `Aptoria v1.0.74`.

## Settings Functional Audit

- [ ] Open Settings.
- [ ] Confirm all Settings tabs render without raw translation keys.
- [ ] Confirm no misleading activation copy such as `Partial`, `Not wired`, `Broken`, `Prepared`, or `Coming soon` appears on the Settings page.
- [ ] Change a General setting and save.
- [ ] Change a Scan setting and save.
- [ ] Change a Probe Safety setting and save.
- [ ] Change a Risk Engine setting and save.
- [ ] Change an Assertions setting and save.
- [ ] Change a Snapshot/Evidence setting and save.
- [ ] Change a Report/Export setting and save.
- [ ] Change a Dashboard/UI setting and save.
- [ ] Change a Security/Privacy setting and save.
- [ ] Change a Release Readiness setting and save.
- [ ] Export Settings JSON and confirm the changed values are present.
- [ ] Reset one Settings group and confirm defaults are restored.

## Runtime Behavior Checks

- [ ] Set dashboard theme to dark and confirm the app shell changes to the dark theme.
- [ ] Set table density to compact and confirm tables render tighter spacing.
- [ ] Disable scan summary cards and confirm dashboard KPI cards are hidden.
- [ ] Set session timeout low enough for manual testing and confirm inactive sessions are logged out.
- [ ] Enable/disable audit logging and confirm audit-aware actions follow the setting.
- [ ] Change report defaults and confirm generated report sections/timestamps/footer follow the setting.
- [ ] Change scan safety/path keyword settings and confirm unsafe scan URLs are skipped.

## Project Settings Checks

- [ ] Open a project's Settings page.
- [ ] Save project notes.
- [ ] Return to the project detail page and confirm the notes are visible.
- [ ] Change project scan limit and confirm scan creation shows the project-level value.
- [ ] Change project response body preview retention and confirm scan evidence follows the setting.
- [ ] Export project Settings JSON and confirm saved values are present.

## Existing Feature Regression

- [ ] Endpoint inventory still loads.
- [ ] OpenAPI/CSV import preview still works.
- [ ] Safe GET/HEAD scan still runs.
- [ ] Destructive methods are still skipped automatically.
- [ ] Scan progress modal still appears.
- [ ] Scan details still show risk summary and per-result risk badges.
- [ ] Assertion rule create/edit still works.
- [ ] Snapshot creation and compare still work.
- [ ] Reports, QA Evidence, Release Readiness and Calendar pages still load.
- [ ] English is the default UI language.
- [ ] Hungarian is selectable and Settings labels remain localized.

## Release Package Hygiene

- [ ] ZIP root folder is `aptoria-1.0.74/`.
- [ ] ZIP contains `VERSION` with `1.0.74`.
- [ ] ZIP contains `docs/SYSTEM_AUDIT_v1.0.74.md`.
- [ ] ZIP contains `docs/SETTINGS_FUNCTIONAL_AUDIT.md`.
- [ ] ZIP contains `.github/workflows/php.yml`.
- [ ] ZIP contains Windows/XAMPP PowerShell scripts.
- [ ] ZIP contains `public/assets/aptoria-ui/vendor`.
- [ ] ZIP contains `public/assets/aptoria-ui/vendor/jquery/jquery.min.js` and `public/assets/aptoria-ui/vendor/bootstrap/js/bootstrap.min.js`.
- [ ] ZIP does not contain root `vendor/`.
- [ ] ZIP does not contain `.env`.
- [ ] ZIP does not contain `database/database.sqlite` or SQLite backups.
- [ ] ZIP does not contain `storage/app/installed.lock`.
- [ ] ZIP does not contain `storage/app/setup-token.txt`.
