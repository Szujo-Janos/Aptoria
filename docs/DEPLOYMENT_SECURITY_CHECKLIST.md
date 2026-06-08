# Deployment Security Checklist

Use this checklist before putting Aptoria on any reachable server.

## Before opening the site

- [ ] Upload only the intended release package.
- [ ] Do not upload an old `.env` from another machine.
- [ ] Create `.env` from `.env.production.example` or review the existing `.env` manually.
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] Set `APP_URL` to the public HTTPS URL.
- [ ] Generate a strong `APP_KEY`.
- [ ] Leave `APTORIA_SETUP_TOKEN` blank to let Aptoria generate `storage/app/setup-token.txt`, or set a long random value with at least 32 characters.
- [ ] Do not use placeholder setup tokens such as `change-this-long-random-setup-token`.
- [ ] Confirm `.env` is not downloadable from the web.
- [ ] Confirm `database/database.sqlite` is not downloadable from the web.
- [ ] Confirm the web server document root points to `public/` where possible.

## First run

- [ ] Open `/setup?setup_token=...` only once. After acceptance, Aptoria removes `setup_token` from the URL and keeps the token in the session.
- [ ] Run dependency installation through SSH/cPanel Terminal where possible.
- [ ] Run migrations.
- [ ] Create or replace the admin user with a strong password.
- [ ] Import demo data only if needed.
- [ ] Finish setup and confirm `storage/app/installed.lock` exists.

## After setup

- [ ] Log in successfully.
- [ ] Open Settings → Security status.
- [ ] Resolve failed checks.
- [ ] Run `php artisan aptoria:security-audit`.
- [ ] For stricter release review, run `php artisan aptoria:security-audit --fail-on-warning`.
- [ ] Confirm `SESSION_SECURE_COOKIE=true` on HTTPS production.
- [ ] Confirm `SESSION_HTTP_ONLY=true` and `SESSION_SAME_SITE=lax` or `strict`.
- [ ] Remove or protect any temporary setup token notes.
- [ ] Confirm scan safety defaults still block private networks.
- [ ] Confirm reports/evidence exports do not expose auth secrets.

## Ongoing operation

- [ ] Use strong admin credentials.
- [ ] Back up the database before upgrades.
- [ ] Keep release ZIPs free of `.env`, `database.sqlite`, `installed.lock`, `setup-token.txt`, and root `vendor/` unless creating a separate deploy package.
- [ ] Review Security status after every deployment.
- [ ] Review web server logs and Laravel logs after upgrades.

## Scheduled monitor operation

- [ ] Run `php artisan aptoria:run-monitors --dry-run` before enabling an OS scheduler.
- [ ] On Windows/XAMPP, schedule `C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50` every 5–15 minutes.
- [ ] Review monitor results in the global monitor overview after the first scheduled run.
- [ ] Use `--fail-on-regression` or `--fail-on-warning` only when the scheduler/CI wrapper should treat regressions as failed jobs.
