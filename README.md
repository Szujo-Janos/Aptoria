<p align="center">
  <img src="public/assets/aptoria-ui/assets/images/logo-color.svg" alt="Aptoria logo" width="320">
</p>

<h1 align="center">Aptoria</h1>

<p align="center">
  <strong>Evidence-first API QA, coverage and release-decision platform.</strong><br>
  A clean public source package containing the Aptoria app and the license manager only.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.0.2-blue" alt="Version 1.0.2">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/source--available-review_only-lightgrey" alt="Source available">
</p>

---

## What is included

This GitHub clean package intentionally contains only the product source needed for the application and the license manager:

```text
app/                 Laravel application code
bootstrap/           Laravel bootstrap files
config/              Application configuration
database/            Migrations, factories and seeders
license-issuer/      Standalone license manager / license authority stub
public/              Public entry point and assets
resources/           Blade views, language files and UI resources
routes/              Web, API and console routes
storage/             Empty runtime directory structure with .gitkeep files
```

Essential project files are also included: `artisan`, `composer.json`, `.env.example`, `.gitignore`, `LICENSE`, `NOTICE.md`, `SECURITY.md`, `CHANGELOG.md`, `CREDITS.md`, `THIRD_PARTY_NOTICES.md`, `start-aptoria.bat` and `get-license-request.bat`.

## What is intentionally excluded

This package is not a VPS snapshot and not a server backup. It does not include:

```text
vendor/
node_modules/
.env
composer.lock, until generated from a clean Composer environment
database/database.sqlite
storage runtime files
logs, caches and compiled views
issued license files
license signing private keys
production public/private key material
VPS Nginx / Cloudflare backups
subdomain deployment smoke output
old release ZIP files
large internal planning docs
GitHub workflow experiments
```

## License safety

The license manager source is included, but no production signing secrets are included.

Do not commit or publish:

```text
license-issuer/config.php
license-issuer/storage/*.json
license-issuer/storage/*.pem
storage/app/aptoria-license.json
storage/app/license-public.pem
storage/app/license-private.pem
storage/app/license-authority-private.pem
```

## Local installation

Requirements:

```text
PHP 8.2+
Composer
SQLite extension enabled
```

Basic setup:

```bash
composer install
cp .env.example .env
php artisan key:generate
mkdir -p database
touch database/database.sqlite
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Windows / XAMPP users can also use:

```bat
start-aptoria.bat
```

To generate a local license request:

```bat
get-license-request.bat
```

## Public project status

Aptoria v1.0.2 is a source-available public portfolio / review package. It is suitable for code review, local testing and GitHub presentation. It is not a hosted SaaS distribution and does not include production infrastructure secrets.

## License

Aptoria is source-available, not open-source. See `LICENSE`, `NOTICE.md`, `CREDITS.md` and `THIRD_PARTY_NOTICES.md`.
