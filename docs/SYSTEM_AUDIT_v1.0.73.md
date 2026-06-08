# Aptoria v1.0.73 - Documentation & Release Polish Hotfix

## Scope

This release polishes the public repository and release documentation after the v1.0.72 Settings Functional Audit Hotfix. The application behavior from v1.0.72 is intentionally preserved.

## Findings And Fixes

- README still contained a v1.0.68 ZIP-based PowerShell installation block. Updated it to v1.0.73.
- `docs/INSTALLATION.md` still described v1.0.65 as the current version and used v1.0.65 ZIP paths. Updated it to v1.0.73.
- `SERVER_INSTALLER.md` still referenced v1.0.42. Updated it to v1.0.73.
- `docs/QA_CHECKLIST.md` used a long descriptive ZIP name instead of the required short release ZIP name. Updated it to `aptoria-1.0.73.zip`.
- Public clone examples used lowercase `aptoria.git`. Updated them to the canonical `Aptoria.git` repository name.
- README documentation map still pointed to the v1.0.68 system audit as current. Updated it to this v1.0.73 audit.
- Removed leftover internal `status => active` metadata from `SettingService` because Settings UI status badges/counters are no longer part of the product contract.
- Added `ReleaseDocumentationConsistencyTest` to prevent stale release-version and ZIP-name references from returning.

## Automated Validation

Development package validation performed for this release:

- PHP syntax check on changed PHP files: passed.
- Release documentation consistency static check: passed.
- Forbidden release file scan: passed.

Full PHPUnit execution still requires Composer dependencies installed locally or in GitHub Actions.

## Release Hygiene

- `VERSION` is `1.0.73`.
- Release ZIP excludes root `vendor/`, `.env`, SQLite databases/backups, `storage/app/installed.lock`, `storage/app/setup-token.txt`, runtime cache files and generated storage content.
- Release ZIP keeps `.github/workflows/php.yml`, Windows/XAMPP scripts and local Aptoria UI vendor assets.

## Manual QA Focus

Use `docs/QA_CHECKLIST.md` for the full manual pass. The highest-priority checks are:

1. Confirm README and `docs/INSTALLATION.md` show v1.0.73 commands.
2. Confirm the ZIP name is `aptoria-1.0.73.zip`.
3. Confirm the ZIP root folder is `aptoria-1.0.73/`.
4. Run the normal Windows/XAMPP update script and PHPUnit suite.
5. Re-check Settings page localization and absence of placeholder/status wording.
