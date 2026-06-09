# Aptoria v1.0.80 - System Audit

## Summary

This hotfix corrects the Aptoria rebrand regression test that validates Windows/XAMPP cleanup paths.

## Findings And Fixes

- The Windows cleanup script correctly used PowerShell path literals such as `docs\RADAR_UI_TEMPLATE_AUDIT.md`.
- The test encoded expectations with doubled backslash characters, so it looked for a different string than the one present in the script.
- `AptoriaRebrandTest` now decodes the single-backslash PowerShell path literals and validates the cleanup script correctly.
- No runtime behavior was changed.

## Release Hygiene

- `VERSION` is `1.0.80`.
- Release ZIP keeps GitHub Actions, Windows/XAMPP scripts and Aptoria UI vendor assets.
- Release ZIP excludes root `vendor/`, `.env`, SQLite runtime databases, setup lock files and setup token files.
