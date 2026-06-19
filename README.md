<p align="center">
  <img src="public/assets/aptoria-ui/assets/images/logo-color.svg" alt="Aptoria logo" width="320">
</p>

# Aptoria

**Aptoria** is a self-hosted, evidence-first API QA and release decision platform built with Laravel.

Current release: **v0.0.53**  
Repository line: **0.0.x evidence-first rebuild**  
Legacy line replaced: **v1.1.34**

Aptoria is not a Postman, Newman, Jira, Datadog or full test-management clone. Its role is to collect the release-critical evidence these tools often leave scattered: endpoint inventory, safe scan proof, imported QA results, native test evidence, findings, verified evidence, release gate review and checksum-backed decision packages.

## What Aptoria does

Aptoria helps answer release questions such as:

- Which API endpoints are in scope?
- Which endpoints have safe scan evidence?
- Which imported QA artifacts became findings, assertions or repository evidence?
- Which test cases and test runs support the release decision?
- Which evidence items are verified and checksum-backed?
- Which high/critical findings still block release?
- Which blind spots remain before sign-off?
- Can the release gate be frozen into an auditable decision package?
- Can the result be exported as HTML/PDF/JSON/ZIP and later delivered through a client portal?

## Current 0.0.53 feature line

- First-run setup and standalone security hardening
- Project access and local user onboarding foundation
- Endpoint inventory and safe scan evidence foundation
- Assertion and endpoint snapshot foundation
- Finding workflow with deduplication / merge support
- Evidence Repository with SHA-256 checksums and lifecycle events
- Import Adapter Layer for Postman/Newman/Jira/OpenAPI JSON/QA CSV/HAR-style inputs
- Native Test Evidence model: test suites, test cases and test runs
- QA Cockpit with coverage and blind-spot signals
- Release Gate Workflow foundation
- Release Gate Report & Decision Package exports
- Report Visual Standard for professional evidence documents
- HTML/PDF/JSON/Markdown/ZIP export paths
- Client Portal delivery foundation
- English/Hungarian localization direction

## Legacy replacement notice

This repository package is prepared to replace the old `aptoria-1.1.34` branch. The old branch is treated as archived historical code. The 0.0.x line is a rebuild with a cleaner evidence-first product direction, new UI rules and different database migrations.

This is a **fresh replacement**, not an in-place database upgrade.

Read before replacing an existing repository:

- `docs/UPGRADE_FROM_1.1.34_TO_0.0.53.md`
- `docs/LEGACY_1.1.34_VS_0.0.53_COMPARISON.md`
- `docs/GITHUB_REPLACEMENT_CHECKLIST.md`

## Install notes

The ZIP intentionally excludes runtime/local files:

- `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`
- `node_modules/`

Install dependencies locally:

```powershell
cd C:\xampp\htdocs\aptoria
composer install
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan serve
```

For Windows/XAMPP update flow, see `docs/INSTALL_WINDOWS_XAMPP.md`.

## License

Aptoria is source-available, not open-source. See `LICENSE`, `NOTICE.md`, `CREDITS.md` and `THIRD_PARTY_NOTICES.md`.
