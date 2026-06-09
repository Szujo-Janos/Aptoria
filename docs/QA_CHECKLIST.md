# Aptoria v1.0.86 QA Checklist

Release: **v1.0.86 - Export Credit Setting Runtime Hotfix**
ZIP: `aptoria-1.0.86.zip`

## Export Credit Checks

- [ ] Generate a full project QA Markdown report and confirm the footer contains Aptoria, version, repository, author and license summary.
- [ ] Generate a custom QA report and confirm the same Aptoria attribution footer appears.
- [ ] Generate a release readiness Markdown report and confirm the attribution footer appears.
- [ ] Generate a QA release gate Markdown report and confirm the attribution footer appears.
- [ ] Generate a snapshot compare Markdown report and confirm the attribution footer appears.
- [ ] Export snapshot JSON and confirm the `generated_by` metadata block is present.
- [ ] Export global Settings JSON and confirm the `generated_by` metadata block is present.
- [ ] Export project Settings JSON and confirm the `generated_by` metadata block is present.
- [ ] Export QA Evidence Pack ZIP and confirm `APTORIA_CREDITS.txt` is included.
- [ ] Export calendar `.ics` and confirm the calendar metadata references Aptoria.
- [ ] Export endpoint inventory CSV and confirm the Aptoria attribution columns are present.

## Regression Checks

- [ ] Dashboard loads without JavaScript console errors.
- [ ] Profile page still loads and saves profile data.
- [ ] Settings page still saves and exports.
- [ ] Reports, QA Evidence, Release Readiness and Calendar pages still load.
- [ ] Favicon and Aptoria logo still render correctly.

## PowerShell Validation

- [ ] Run `C:\xampp\php\php.exe artisan optimize:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan view:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan config:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan route:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan migrate`.
- [ ] Run `C:\xampp\php\php.exe artisan test`.
- [ ] Start the app with `C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000`.

## Release Package Hygiene

- [ ] ZIP root folder is `aptoria-1.0.86/`.
- [ ] ZIP contains `VERSION` with `1.0.86`.
- [ ] ZIP contains `docs/SYSTEM_AUDIT_v1.0.86.md`.
- [ ] ZIP contains `.github/workflows/php.yml`.
- [ ] ZIP contains Windows/XAMPP PowerShell scripts.
- [ ] ZIP contains `public/assets/aptoria-ui/vendor`.
- [ ] ZIP does not contain root `vendor/`.
- [ ] ZIP does not contain `.env`.
- [ ] ZIP does not contain `database/database.sqlite`.
- [ ] ZIP does not contain `storage/app/installed.lock`.
- [ ] ZIP does not contain `storage/app/setup-token.txt`.
