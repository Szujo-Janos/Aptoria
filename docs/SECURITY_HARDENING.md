# Aptoria Security Hardening

Aptoria can be used locally or on a self-hosted server. v1.0.39 keeps the deployment baseline without changing the product workflow.

## Security controls

- Setup routes are token-protected on non-local hosts.
- Setup tokens must be long, non-placeholder values. The default minimum length is 32 characters.
- Accepted setup tokens are moved into the session and removed from the URL on the next GET request.
- Login is rate limited to reduce brute-force attempts.
- Authenticated admin pages require an administrator user role.
- Security headers are added to web responses:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: same-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
  - `Cross-Origin-Opener-Policy: same-origin`
  - `Cross-Origin-Resource-Policy: same-origin`
  - `X-Permitted-Cross-Domain-Policies: none`
  - HSTS on HTTPS requests
  - CSP report-only baseline for visibility without breaking the Aptoria UI UI
- Sensitive pages such as login, setup and settings use no-store cache headers.
- Release defaults are production-safe: `APP_DEBUG=false`, `APP_ENV=production`, `LOG_LEVEL=warning`.
- Sensitive headers and token-like values are masked in UI/storage/export paths.
- Network target validation blocks localhost/private/reserved/cloud metadata targets unless explicitly permitted by project settings where applicable.

## Setup token

For non-local setup, either leave the token blank so Aptoria generates `storage/app/setup-token.txt`, or set a strong value manually:

```env
APTORIA_SETUP_TOKEN=use-a-long-random-token-with-at-least-32-characters
```

Then open:

```text
https://your-domain.example/aptoria/setup?setup_token=use-a-long-random-token-with-at-least-32-characters
```

After the token is accepted, Aptoria stores it in the session and redirects to the same setup page without the `setup_token` query parameter.

If no `.env` token exists, Aptoria creates `storage/app/setup-token.txt`. Read that file through SSH/FTP/control panel and use it as the setup token. Do not publish it.

## Security audit command

Run this after deployment:

```bash
php artisan aptoria:security-audit
```

For stricter checks that fail on warnings too:

```bash
php artisan aptoria:security-audit --fail-on-warning
```

## Production checklist

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Use a strong `APP_KEY`.
- Finish setup so `storage/app/installed.lock` exists.
- Change the default admin password.
- Use HTTPS and set `APP_URL=https://...`.
- Set `SESSION_SECURE_COOKIE=true` on HTTPS production.
- Keep `SESSION_HTTP_ONLY=true`.
- Keep `SESSION_SAME_SITE=lax` or `strict`.
- Keep `.env`, `database/database.sqlite`, `storage/`, and `vendor/` outside public download paths.
- Keep `scan.allow_private_networks=false` unless there is a specific internal testing reason.
- Do not enable browser dependency install without setup-token protection.

## Local XAMPP notes

The Windows/XAMPP installer may still create a local-friendly `.env` with:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
```

This is acceptable for local development, but not for a public server.
