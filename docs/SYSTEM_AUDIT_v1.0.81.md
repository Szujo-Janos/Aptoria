# Aptoria v1.0.81 - Local HTTPS Force Scheme Hotfix

## Scope

This hotfix addresses local Windows/XAMPP and `php artisan serve` installs where the environment may still use `APP_ENV=production` while the local URL is plain HTTP.

## Finding

`AppServiceProvider` previously forced HTTPS for every production environment. On a local `artisan serve` instance this can make Laravel generate HTTPS asset URLs even though the built-in server only accepts HTTP. Browser DevTools then reports `ERR_CONNECTION_CLOSED`, while the server logs `Invalid request (Unsupported SSL request)`.

## Fix

Aptoria now forces HTTPS only when both conditions are true:

1. `APP_ENV=production`
2. `APP_URL` starts with `https://`

Local HTTP installs such as `http://127.0.0.1:8000` are no longer forced to HTTPS by the URL generator.

## Manual QA Focus

1. Clear Laravel caches.
2. Start `artisan serve` on `127.0.0.1:8000`.
3. Open `http://127.0.0.1:8000`.
4. Confirm CSS, JS, logo and favicon assets load without `ERR_CONNECTION_CLOSED`.
5. Confirm the serve console no longer logs `Invalid request (Unsupported SSL request)` during normal HTTP navigation.

## Release Hygiene

- `VERSION` is `1.0.81`.
- Release ZIP keeps GitHub Actions, Windows/XAMPP scripts and Aptoria UI vendor assets.
- Release ZIP excludes root `vendor/`, `.env`, SQLite runtime databases, setup locks and setup tokens.
