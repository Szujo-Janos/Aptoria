# Aptoria public QA checklist

Use this checklist when reviewing the public/user-facing package. Keep all test data local and non-sensitive.

## Repository hygiene

- `.env` is not committed.
- `vendor/` and `node_modules/` are not committed.
- Runtime SQLite databases are not committed.
- Generated evidence/report exports are not committed.
- License files, key files and activation artifacts are not committed.
- No real API tokens, bearer tokens, passwords or customer payloads are present.

## Local setup

1. Copy `.env.example` to `.env`.
2. Run `composer install`.
3. Run `php artisan key:generate`.
4. Create `database/database.sqlite`.
5. Run `php artisan migrate --force`.
6. Run `php artisan serve`.
7. Open `http://127.0.0.1:8000`.

## Core application review

- First-run/setup flow works in local evaluation.
- Login flow works after setup.
- Project dashboard loads.
- Environment and auth profile screens load.
- Endpoint inventory screens load.
- Safe scan screens load with non-sensitive/local targets only.
- Evidence repository screens load.
- Finding review and triage screens load.
- Native test evidence screens load.
- Release readiness and release gate screens load.
- Report/export screens load without exposing real data.
- Audit log screens load.

## Import review

Use only sample/non-sensitive files.

- OpenAPI import preview can be reviewed.
- Postman collection input can be reviewed.
- Newman result input can be reviewed.
- Jira/CSV-style input can be reviewed.
- HAR/network capture input can be reviewed.
- Conflict/mapping previews do not expose private data.

## Security review

- `APP_DEBUG=false` for non-local use.
- Public web root points to `public/`, not the repository root.
- Generated reports and evidence exports are treated as private project data.
- License activation artifacts are not published.
- Screenshots and issues use demo/local data only.

## Release package review

Before publishing a public GitHub package, confirm that it does not include:

```text
.env
vendor/
node_modules/
database/*.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
storage/app/aptoria-license.json
storage/app/license-*.pem
storage/app/*lease*.json
public/storage
generated evidence exports
generated report exports
customer data
real credentials
```
