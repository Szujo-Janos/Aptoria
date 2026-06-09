# Aptoria v1.0.77 - Rebrand Test Consistency Hotfix

## Scope

This release fixes stale rebrand regression expectations that could remain after the Aptoria rename and icon hotfix.

## Findings And Fixes

- `AptoriaRebrandTest` now validates the current `VERSION` format dynamically instead of expecting a stale exact version.
- `ReleaseDocumentationConsistencyTest` now checks release-facing docs against the current short ZIP name derived from `VERSION`.
- README, installation guide, server installer notes and QA checklist now reference v1.0.77.
- Corrected Aptoria icon and favicon assets are retained.

## Manual QA Focus

1. Run `php artisan test`.
2. Confirm `AptoriaRebrandTest` passes.
3. Confirm `ReleaseDocumentationConsistencyTest` passes.
4. Confirm README and installation docs show `aptoria-1.0.77.zip`.
5. Confirm no old hardcoded rebrand version appears in the tests.

## Release Hygiene

- `VERSION` is `1.0.77`.
- Release ZIP keeps GitHub Actions, Windows/XAMPP scripts and local Aptoria UI vendor assets.
- Release ZIP excludes root `vendor/`, `.env`, SQLite runtime databases, setup locks and setup tokens.
