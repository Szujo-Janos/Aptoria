<p align="center">
  <img src="public/assets/aptoria-ui/assets/images/logo-color.svg" alt="Aptoria logo" width="320">
</p>

<h1 align="center">Aptoria</h1>

<p align="center">
  <strong>Evidence-first API QA, coverage and release-decision platform.</strong><br>
  A Laravel-based QA workspace for API review, evidence handling, release readiness and license-aware local usage.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.0.2-blue" alt="Version 1.0.2">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/SQLite-default-003B57?logo=sqlite" alt="SQLite default">
  <img src="https://img.shields.io/badge/source--available-public_review-lightgrey" alt="Source available">
</p>

---

## About Aptoria

Aptoria is a QA-focused application for API testing support, endpoint review, evidence collection, coverage tracking and release-decision preparation.

The project is designed for practical QA workflows where API checks, imported evidence, findings, reports and release readiness decisions need to be reviewed in one place.

This public package contains the Aptoria application source and the license manager components required for local review and development.

## Included components

```text
app/                 Laravel application code
bootstrap/           Laravel bootstrap files
config/              Application configuration
database/            Migrations, factories and seeders
license-issuer/      License manager / license issuer components
public/              Public entry point and assets
resources/           Blade views, language files and UI resources
routes/              Web, API and console routes
storage/             Empty runtime directory structure with .gitkeep files
```

Essential project files are also included:

```text
artisan
composer.json
.env.example
.gitignore
LICENSE
NOTICE.md
SECURITY.md
CHANGELOG.md
CREDITS.md
THIRD_PARTY_NOTICES.md
CONTRIBUTING.md
VERSION
start-aptoria.bat
get-license-request.bat
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

## Project status

Aptoria v1.0.2 is a source-available public review package. It is suitable for code review, local testing and portfolio presentation.

It is not a hosted SaaS distribution and does not include production infrastructure, production databases, issued licenses or signing secrets.

## License

Aptoria is source-available, not open-source.

See:

```text
LICENSE
NOTICE.md
CREDITS.md
THIRD_PARTY_NOTICES.md
```
