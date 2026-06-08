# Aptoria v1.0.71 – Settings Help Text Noise Hotfix

## Scope

This release fixes Settings Center help text noise introduced during localization coverage work.

## Changes

- Removed generic per-field help boilerplate from English and Hungarian Settings localization.
- Settings field help is now rendered only when the text is non-empty and field-specific.
- Localization keys remain present for every Settings field so regression tests still catch missing labels/keys.
- Added regression coverage to prevent the generic boilerplate from returning.
- Updated release metadata to `1.0.71`.

## Verification

- `VERSION` is `1.0.71`.
- No generic Settings help text remains in English or Hungarian localization.
- Settings view no longer renders empty/missing field help blocks.
- PHP syntax checks pass for the changed files.
- Release ZIP excludes runtime-only files and directories.

## QA focus

1. Switch to Hungarian.
2. Open Settings.
3. Confirm the generic sentence under every field is gone.
4. Confirm only useful help remains under selected fields such as User-Agent, risk keywords and response preview limits.
5. Save Settings.
6. Run the Settings-related feature tests.
