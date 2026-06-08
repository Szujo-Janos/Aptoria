# Aptoria v1.0.69 – Settings Localization & Hardcode Hotfix

## Scope

This release is a focused localization and hardcode cleanup pass for the Settings Center after the v1.0.67–v1.0.68 Settings activation work.

## Findings fixed

- Several newly added Settings keys had no `messages.settings.fields.*` translation.
- Several Settings groups and select option values had no localized label.
- The Settings Blade view could fall back to generated English headline labels.
- The Settings Blade view could fall back to English `SettingService` descriptions when Hungarian help text was missing.

## Changes

- Added complete English and Hungarian field labels for all current SettingService defaults.
- Added complete English and Hungarian help text for all current SettingService defaults.
- Added localized Settings group labels and help text for all current SettingService groups.
- Added localized option labels for every Settings select value.
- Replaced the English fallback behavior with an explicit missing-translation marker.
- Added `SettingsLocalizationTest` to prevent future Settings localization regressions.

## Release hygiene

- `VERSION` updated to `1.0.69`.
- No runtime secrets or local install files are intended for the release archive.
- GitHub Actions compatibility is preserved.
- XAMPP/Windows compatibility is preserved.
