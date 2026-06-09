# Aptoria v1.0.90 - First-Run Setup Flow Pass System Audit

## Scope

This audit covers the first-run installation and onboarding flow introduced after v1.0.89. The goal is to make a fresh self-hosted install predictable: upload the release, prepare dependencies/runtime files, open the app, complete setup, then land on My Profile at first login.

## Findings addressed

- Normal application pages must not open before setup is finished and locked.
- An existing users table alone must not count as production-ready installation state.
- `/setup` must not remain an operational setup screen after setup is locked.
- Setup must not be lockable before an admin/user exists.
- The first successful login should guide the new admin to profile/report identity configuration before reports are generated.

## Implementation notes

- `SetupStateService::canUseApplication()` now separates app access from the broader `isInstalled()` diagnostic state.
- `EnsureApplicationIsInstalled` uses the setup lock as the application access gate.
- `AuthController` blocks login attempts until setup is usable and redirects the first successful login to `profile.show`.
- `EnsureSetupAccessIsAuthorized` closes setup after lock creation by redirecting GET requests and rejecting setup write attempts.
- User login tracking is stored in nullable `first_login_at` and `last_login_at` columns.

## Manual QA

1. Deploy to a clean folder without `.env`, SQLite DB, setup token or installed lock.
2. Open `/`, `/dashboard`, `/reports` and `/login`; confirm the browser reaches `/setup`.
3. Try finishing setup before creating an admin; confirm an error is shown.
4. Complete setup and create the lock.
5. Open `/setup`; confirm the setup wizard no longer opens.
6. Log in for the first time; confirm the profile page opens.
7. Log out and log in again; confirm the configured default landing page opens.

## Release hygiene

- README links to `CHANGELOG.md` instead of embedding release history.
- Release ZIP must not contain `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` or `storage/app/setup-token.txt`.
- `public/assets/aptoria-ui/vendor` remains packaged.
