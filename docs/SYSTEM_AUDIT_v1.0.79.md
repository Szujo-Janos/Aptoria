# Aptoria v1.0.79 - System Audit

## Summary

This hotfix corrects the Aptoria rebrand regression-test scope and removes public QA checklist literals that could trigger false-positive legacy branding failures.

## Findings

The previous rebrand regression test scanned `CHANGELOG.md` as if historical release notes were current product branding. That caused valid historical release notes to fail the test. Current public-facing source files remain protected, while changelog history is allowed to preserve past release context.

## Fixes

- Re-scoped `AptoriaRebrandTest` to current public source and documentation files.
- Removed literal legacy path names from the current QA checklist.
- Kept Windows/XAMPP cleanup-path coverage without exposing legacy names in current public documentation assertions.
- Updated VERSION to `1.0.79`.

## Release Hygiene

- Root `vendor/` is not included in the release ZIP.
- `.env` is not included in the release ZIP.
- `database/database.sqlite` is not included in the release ZIP.
- `storage/app/installed.lock` is not included in the release ZIP.
- `storage/app/setup-token.txt` is not included in the release ZIP.
