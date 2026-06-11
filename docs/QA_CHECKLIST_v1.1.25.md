# Aptoria v1.1.25 QA Checklist

Release: **v1.1.25 - Contract Reality Check Pass**
ZIP: `aptoria-1.1.25.zip`

## Required checks

- [ ] Install from `aptoria-1.1.25.zip` using the documented PowerShell template.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan test`.
- [ ] Run or create an OpenAPI contract validation with scan evidence.
- [ ] Open **Project → Contract Reality**.
- [ ] Confirm auth contract mismatches are visible when OpenAPI security and endpoint metadata disagree.
- [ ] Confirm undocumented response fields are visible when real JSON response fields are absent from the OpenAPI schema.
- [ ] Confirm missing documented endpoints and undocumented inventory endpoints remain visible.
- [ ] Open **Project → Release Readiness** and confirm Contract Reality mismatches affect blockers/warnings.
- [ ] Export a Full QA Report and confirm **Contract Reality Check** appears.

## Release ZIP exclusions

- [ ] No root `vendor/` directory.
- [ ] No root `.env` file.
- [ ] No `database/database.sqlite`.
- [ ] No `storage/app/installed.lock`.
- [ ] No `storage/app/setup-token.txt`.
- [ ] `public/assets/aptoria-ui/vendor` remains included.
