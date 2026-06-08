# Aptoria v1.0.65 – Settings Full Wiring Pass

## Summary

This release completes the Settings Center activation pass started in v1.0.56–v1.0.59. Previously prepared or planned settings are now promoted to active operational policy values, with runtime helper methods exposing scan concurrency, production confirmation, retention, report defaults and security policy controls.

## Scope

- Promoted remaining `Prepared` and `Planned` settings to `Active`.
- Added runtime accessors for scan concurrency and typed production confirmation.
- Added runtime accessors for retention and cleanup policy.
- Added runtime accessors for report/export defaults.
- Added runtime accessors for security/privacy policy.
- Preserved previous Blade layout and asset rendering hotfixes.
- Preserved risk analyzer sensitive keyword score behavior.

## Release hygiene

- Short ZIP naming retained: `aptoria-1.0.65.zip`.
- Forbidden runtime files are excluded from the release package.
- Source-available public repository documentation remains intact.

## QA focus

- Settings page renders and saves every group.
- Settings export includes all groups.
- Settings status badges show active policy state.
- Dashboard, project pages and reports still render without raw Blade/PHP output.
- `php artisan test` should remain green after cache/view/config/route clear.
