# Aptoria v1.0.86 - Export Credit Setting Runtime Hotfix

## Scope

This hotfix stabilizes the v1.0.84/v1.0.85 export attribution work by wiring the visible `report.include_copyright_footer` setting to the runtime export credit service.

## Fixed issue

`SettingsFunctionalAuditTest` failed in GitHub Actions because `report.include_copyright_footer` was visible on the Settings page but was not referenced by runtime application code outside the settings UI.

## Runtime behavior

- `ExportCreditService` now reads `report.include_copyright_footer` through `SettingService`.
- The setting defaults to enabled, so Aptoria attribution remains present by default.
- If the setting is disabled, Markdown/text credit footers are skipped cleanly.
- JSON structured metadata remains available for machine-readable export provenance.

## Release hygiene

- VERSION is `1.0.86`.
- Release ZIP root is `aptoria-1.0.86/`.
- Root `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` and `storage/app/setup-token.txt` are excluded.
- `public/assets/aptoria-ui/vendor` remains included.
