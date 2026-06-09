# Aptoria v1.0.76 - Rebrand Polish System Audit

## Scope

This release polishes the Aptoria rebrand after v1.0.74 and the v1.0.75 icon crop hotfix. It focuses on public documentation consistency, release builder validation and rebrand regression coverage.

## Findings And Fixes

- README and installation documentation still referenced v1.0.74 after the v1.0.75 icon hotfix. Updated them to v1.0.76.
- `SERVER_INSTALLER.md` still referenced v1.0.74 package names. Updated it to v1.0.76.
- `scripts/build-release.ps1` still required the v1.0.74 system audit document. Updated the release validation to require the current `docs/SYSTEM_AUDIT_v$Version.md`.
- `AptoriaRebrandTest` hardcoded version 1.0.74. Updated it to read the current VERSION file.
- Public documentation still contained legacy pre-rebrand UI document filenames. Renamed them to the `APTORIA_UI_*` naming pattern.
- Corrected awkward rebrand wording left behind by broad text replacement in changelog/system audit history.

## Validation

- Modified PHP files passed PHP syntax checks.
- Static release-documentation checks confirm current version and short ZIP naming.
- Static legacy-brand checks confirm the pre-rebrand product name and technical namespaces do not remain in source/documentation files.

## Release Hygiene

- `VERSION` is `1.0.76`.
- Release ZIP root is `aptoria-1.0.76/`.
- Release ZIP name is `aptoria-1.0.76.zip`.
- Root `vendor/`, `.env`, SQLite runtime databases, `storage/app/installed.lock` and `storage/app/setup-token.txt` are excluded.
- GitHub Actions, Windows/XAMPP scripts and `public/assets/aptoria-ui/vendor` remain included.
