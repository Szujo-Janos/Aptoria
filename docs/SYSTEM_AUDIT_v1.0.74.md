# Aptoria v1.0.74 - Aptoria Rebrand Pass

## Scope

This release rebrands the application from its former API-focused name to **Aptoria** while preserving the v1.0.73 functionality, Settings audit work, GitHub Actions QA gate, Windows/XAMPP support, local self-hosted installation flow and release hygiene rules.

## Findings And Fixes

- Replaced public-facing product identity with Aptoria across README, installation docs, QA checklist, system audit docs, language files, Blade views, setup screens, mail subjects and release metadata.
- Updated Composer package metadata to `szujo-janos/aptoria`.
- Updated environment variable prefixes from `APTORIA` legacy replacements where applicable and kept `.env.example`, `.env.testing` and runtime config aligned.
- Renamed the configuration namespace from `the legacy config namespace` to `config('aptoria.*')` and renamed `the legacy config file` to `config/aptoria.php`.
- Renamed public asset namespaces to `public/assets/aptoria` and `public/assets/aptoria-ui`.
- Added the new Aptoria logo, app icon and favicon assets.
- Updated Artisan command names to `aptoria:version`, `aptoria:security-audit`, `aptoria:run-monitors` and `aptoria:calendar-cleanup-setup-noise`.
- Updated GitHub clone URLs to `https://github.com/Szujo-Janos/Aptoria.git`.
- Updated ZIP naming to `aptoria-1.0.74.zip` and ZIP root to `aptoria-1.0.74/`.
- Added automated rebrand consistency coverage to prevent legacy public product naming and asset namespaces from returning.

## Automated Validation

- PHP syntax check passed for modified PHP files.
- Static release hygiene check passed for forbidden runtime files.
- Static asset check confirmed the Aptoria app assets and Aptoria UI vendor assets are present.
- Full Laravel test execution should be run after installing Composer dependencies locally with `php artisan test`.

## Release Hygiene

- `VERSION` is `1.0.74`.
- Release ZIP excludes root `vendor/`, `.env`, SQLite databases/backups, `storage/app/installed.lock`, `storage/app/setup-token.txt`, runtime cache files and generated storage content.
- Release ZIP keeps `.github/workflows/php.yml`, Windows/XAMPP scripts and local Aptoria UI vendor assets.

## Manual QA Focus

1. Confirm the header, login/setup pages, dashboard, Settings, reports and release pages show Aptoria.
2. Confirm no public-facing pre-rebrand branding remains in the running UI.
3. Confirm the new logo appears in the browser header/sidebar and setup page.
4. Confirm `php artisan aptoria:version` prints the Aptoria version.
5. Confirm `php artisan aptoria:security-audit` runs.
6. Confirm scheduled monitor docs use `aptoria:run-monitors`.
7. Confirm Settings save/export still works after the rebrand.
