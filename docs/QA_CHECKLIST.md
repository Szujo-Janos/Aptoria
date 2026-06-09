# Aptoria v1.1.0 QA Checklist

Release: **v1.1.0 - Postman Compatibility, Globals & Newman Import Pass**
ZIP: `aptoria-1.1.0.zip`

## Install / update

- [ ] Install from `aptoria-1.1.0.zip` using the documented PowerShell template.
- [ ] Run `php artisan optimize:clear`.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan aptoria:health`.
- [ ] Run `php artisan test`.

## Postman Globals / compatibility

- [ ] Open a project.
- [ ] Go to **Endpoints → Import Endpoints**.
- [ ] Select **Postman Collection JSON**.
- [ ] Use the Postman collection, environment and globals samples.
- [ ] Preview import.
- [ ] Confirm that globals count, schema, variables and warnings render without raw translation keys.
- [ ] Confirm secrets are masked.
- [ ] Confirm unsupported auth/script warnings appear when applicable.

## Newman import

- [ ] Open **Test Execution** for a project.
- [ ] Click **Import Newman results**.
- [ ] Preview the Newman JSON sample.
- [ ] Confirm import.
- [ ] Confirm test suite, test cases and test results are created.
- [ ] Confirm failed assertions can create findings and evidence.
- [ ] Repeat with the JUnit XML sample.

## Hygiene

- [ ] ZIP root folder is `aptoria-1.1.0/`.
- [ ] ZIP contains `VERSION` with `1.1.0`.
- [ ] ZIP does not contain root `vendor/`.
- [ ] ZIP does not contain `.env`.
- [ ] ZIP does not contain `database/database.sqlite`.
- [ ] ZIP does not contain `storage/app/installed.lock`.
- [ ] ZIP does not contain `storage/app/setup-token.txt`.
- [ ] ZIP keeps `public/assets/aptoria-ui/vendor`.
