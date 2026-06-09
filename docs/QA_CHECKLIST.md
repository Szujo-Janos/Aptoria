# Aptoria v1.0.82 QA Checklist

Release: **v1.0.82 - Optional Vendor Plugin Guard Hotfix**
ZIP: `aptoria-1.0.82.zip`

## PowerShell Validation

- [ ] Run `C:\xampp\php\php.exe artisan optimize:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan view:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan config:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan route:clear`.
- [ ] Run `C:\xampp\php\php.exe artisan migrate`.
- [ ] Run `C:\xampp\php\php.exe artisan test`.
- [ ] Start the app with `C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000`.

## JavaScript / UI Checks

- [ ] Open the landing page and confirm the CSS loads.
- [ ] Open the login page and confirm the console does not show `metisMenu is not a function`.
- [ ] Open the dashboard and confirm the sidebar menu still expands/collapses.
- [ ] Confirm no `ERR_CONNECTION_CLOSED` asset errors appear under `http://127.0.0.1:8000`.
- [ ] Confirm Aptoria logo and favicon still render.

## Release Package Hygiene

- [ ] ZIP root folder is `aptoria-1.0.82/`.
- [ ] ZIP contains `VERSION` with `1.0.82`.
- [ ] ZIP contains `public/assets/aptoria-ui/js/aptoria-ui.js`.
- [ ] ZIP contains `public/assets/aptoria-ui/vendor`.
- [ ] ZIP does not contain root `vendor/`.
- [ ] ZIP does not contain `.env`.
- [ ] ZIP does not contain `database/database.sqlite`.
- [ ] ZIP does not contain `storage/app/installed.lock`.
- [ ] ZIP does not contain `storage/app/setup-token.txt`.
