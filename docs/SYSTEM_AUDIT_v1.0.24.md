# Aptoria v1.0.24 System Audit

Base package audited: `aptoria-1.0.23.zip`

## Scope reviewed

- Laravel application structure, routes, controllers, models and services.
- Blade layouts, landing page, login/setup layout and Aptoria custom CSS.
- English and Hungarian language files.
- Release ZIP hygiene requirements.
- Existing feature-test coverage presence.

## Inventory snapshot

- Controllers: 31
- Models: 25
- Migrations: 28
- Blade views: 74
- Feature tests: 27
- Approximate web route declarations: 95
- English translation leaf keys: 2326
- Hungarian translation leaf keys: 2326

## Checks performed

| Area | Result | Notes |
| --- | --- | --- |
| PHP syntax lint | PASS | `php -l` passed for app, bootstrap, config, database, public, resources, routes and tests PHP files. |
| EN/HU translation parity | PASS | English and Hungarian translation key counts match. |
| Literal missing translation keys | FIXED | Added missing keys for auth profile placeholder, endpoint placeholder and project health assertion labels. |
| Release hygiene | PASS | The generated v1.0.24 release excludes root `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` and `storage/app/setup-token.txt`; it keeps `public/assets/aptoria-ui/vendor`. |
| UI framework direction | PASS | Aptoria UI/Bootstrap direction preserved; no Tailwind, shadcn, Bento, React or Bootstrap-removal work added. |
| Security baseline | REVIEWED | Existing controls include auth-only admin routes, admin middleware, setup lock/token protection, login throttling, security headers and safe scan defaults. |
| Test execution | NOT RUN HERE | The release ZIP intentionally has no root `vendor/`, and Composer is not available in this sandbox, so PHPUnit could not be executed here. Run the local commands below after dependency install. |

## v1.0.24 changes made

- Roboto is now the primary UI font for:
  - authenticated admin layout,
  - login and setup layout,
  - public landing page,
  - first-run preflight/installer fallback page.
- Added explicit CSS override after Aptoria UI assets so Aptoria UI’s default Open Sans stack does not win over Aptoria typography.
- Kept code blocks on monospace fonts.
- Added Google Fonts loading in layouts. If the browser is offline, the UI falls back to Helvetica/Arial.
- Added missing English/Hungarian translation keys found during the audit.

## Findings for a later hardening pass

1. **Automated tests must be run locally after Composer install.** The package contains tests, but this audit environment cannot run PHPUnit without dependencies.
2. **Content Security Policy is not yet defined.** Current headers cover frame, MIME sniffing, referrer, permissions and HSTS-on-HTTPS. A future CSP pass should be added carefully, especially because Roboto currently loads from Google Fonts.
3. **Some historical docs still mention older milestone versions.** This is not runtime-breaking, but a later documentation cleanup should update `docs/MVP_PLAN.md` and historical checklist text to avoid confusion.
4. **Scheduled monitors still require OS scheduler setup.** This is already documented, but Windows Task Scheduler QA should remain part of every deployment checklist.
5. **Production safety depends on final deployment configuration.** Verify `APP_DEBUG=false`, setup lock, changed admin password, HTTPS and non-public storage/database paths after deployment.

## Recommended local QA command set

```powershell
cd C:\xampp\htdocs\aptoria
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan migrate --force
C:\xampp\php\php.exe artisan test
```

## Manual QA checklist

- Login page uses Roboto and still shows SweetAlert/validation feedback.
- Setup page uses Roboto and all setup actions remain visible.
- Dashboard/sidebar/header/footer use Roboto.
- Landing page uses Roboto and remains responsive.
- Code blocks and JSON/API previews stay monospace.
- English and Hungarian language switch still works.
- Auth profile create form shows a normal placeholder instead of a raw translation key.
- Endpoint create/edit form shows a normal placeholder instead of a raw translation key.
- Project health widget shows proper assertion labels.
