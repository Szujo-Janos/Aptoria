# Aptoria v1.0.28 System Audit

Release: **v1.0.28 – Scheduled Monitoring Operations Pass**  
Base: **v1.0.26 – Documentation & GitHub Readiness Cleanup**

## Scope

This release is a deployment/security baseline pass. It does not add a new QA workflow feature. The goal is to make the current v1.0.26 line safer to install, review and prepare for private GitHub/server deployment.

## Changed areas

- Expanded baseline HTTP security headers.
- Added no-store cache headers for sensitive pages: login, setup and settings.
- Added CSP report-only visibility without enforcing a policy that could break the Aptoria UI UI.
- Strengthened setup-token validation:
  - minimum length configurable through `APTORIA_SETUP_TOKEN_MIN_LENGTH`;
  - known placeholder tokens are rejected;
  - generated setup tokens remain long random values;
  - accepted query tokens are moved into the session and removed from the URL.
- Expanded Settings → Security status checks for production HTTPS, secure cookies, HTTP-only cookies, SameSite and log level.
- Added `php artisan aptoria:security-audit` and `--fail-on-warning` mode.
- Expanded first-run environment checks with deployment/security reminders.
- Updated `.env.production.example` so setup token is blank by default and generated securely when needed.
- Updated deployment/security documentation and QA checklist.

## Expected runtime behavior

- Local XAMPP development remains supported.
- Existing admin/login/project workflows should behave like v1.0.26.
- Non-local setup still requires a setup token.
- A valid setup token submitted through `?setup_token=...` should redirect once to the same setup URL without the token parameter.
- Weak or placeholder setup tokens should not authorize setup access.
- Security status may show additional warnings on local machines; that is expected.

## Release hygiene

The release ZIP must not contain:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`
- generated Laravel cache files

The Aptoria UI asset vendor directory remains part of this branch:

- `public/assets/aptoria-ui/vendor`

Public GitHub publication still requires a final Aptoria UI license decision.
