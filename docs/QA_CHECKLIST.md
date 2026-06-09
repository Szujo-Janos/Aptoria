# Aptoria v1.0.83 QA Checklist

Release: **v1.0.83 - User Profile Center**
ZIP: `aptoria-1.0.83.zip`

## PowerShell Validation

- [ ] Run `C:\xampp\php\php.exe artisan optimize:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan view:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan config:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan route:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan migrate`.
- [ ] Run `C:\xampp\php\php.exe artisan test`.
- [ ] Start the app with `C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000`.

## Profile Center Checks

- [ ] Log in and open the user dropdown.
- [ ] Confirm `My Profile` / `Profilom` is visible.
- [ ] Open `/profile`.
- [ ] Confirm account information, session timeout, setup status and Aptoria version are visible.
- [ ] Update name, e-mail, language and timezone.
- [ ] Confirm the changed values persist after reload.
- [ ] Change the password with the correct current password.
- [ ] Confirm a wrong current password is rejected.
- [ ] Confirm the dashboard, settings and logout links still work from the user dropdown.

## Existing Feature Regression

- [ ] Dashboard loads without console errors.
- [ ] Settings save/export still works.
- [ ] Safe GET/HEAD scan still works.
- [ ] Reports and release readiness pages still load.
- [ ] English remains the default UI language.
- [ ] Hungarian is selectable and profile labels are localized.

## Release Package Hygiene

- [ ] ZIP root folder is `aptoria-1.0.83/`.
- [ ] ZIP contains `VERSION` with `1.0.83`.
- [ ] ZIP contains `docs/SYSTEM_AUDIT_v1.0.83.md`.
- [ ] ZIP contains `app/Http/Controllers/UserProfileController.php`.
- [ ] ZIP contains `resources/views/profile/show.blade.php`.
- [ ] ZIP contains `public/assets/aptoria-ui/vendor`.
- [ ] ZIP does not contain root `vendor/`.
- [ ] ZIP does not contain `.env`.
- [ ] ZIP does not contain `database/database.sqlite`.
- [ ] ZIP does not contain `storage/app/installed.lock`.
- [ ] ZIP does not contain `storage/app/setup-token.txt`.
