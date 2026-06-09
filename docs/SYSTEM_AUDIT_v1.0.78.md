# Aptoria v1.0.78 - System Audit

## Summary

This hotfix fixes a rebrand regression caused by stale files left behind in existing Windows/XAMPP project roots.

## Root Cause

The standard update flow copies the new release files over the existing project using `Copy-Item -Recurse -Force`. That operation overwrites changed files, but it does not delete files that were removed or renamed in the new release.

After the Aptoria rebrand, old documentation files such as `docs/RADAR_UI_TEMPLATE_AUDIT.md` could remain on disk and fail `AptoriaRebrandTest`, even though the release ZIP itself no longer shipped those files.

## Fix

- Added legacy rebrand artifact cleanup to the Windows/XAMPP common update helpers.
- The update script now removes known stale Aptoria rebrand leftovers from an existing project root.
- Regression coverage verifies that the cleanup list stays present in the update helper.

## Release Hygiene

- `VERSION` is `1.0.78`.
- Release ZIP root is `aptoria-1.0.78/`.
- Release ZIP keeps GitHub Actions and Windows/XAMPP scripts.
- Release ZIP keeps `public/assets/aptoria-ui/vendor`.
- Release ZIP excludes local runtime files such as `.env`, root `vendor/`, SQLite runtime databases, setup locks and setup tokens.
