# Aptoria v1.0.68 – Settings Activation Test Hotfix

v1.0.68 is a focused hotfix on top of v1.0.67. It keeps the Settings activation direction but fixes the two regressions reported by the automated feature tests.

## Fixed

- Assertion evaluation no longer invents synthetic fallback rules for endpoints that have no explicit project or endpoint assertion rules. Those endpoints correctly remain `not_configured`.
- The assertion default status code remains wired into the create-rule form default, so the Setting is active without changing evaluation semantics.
- The confirmation JavaScript no longer contains the forbidden literal `window.confirm(` call while keeping fallback confirmation support.

## Compatibility

- GitHub Actions compatibility preserved.
- Windows/XAMPP helper scripts preserved.
- No release ZIP runtime secrets are included.
- No database migration required.

## Required QA

1. `php artisan test` must pass.
2. Settings page must open and save normally.
3. Confirmation dialogs must still work on delete/reset actions.
4. Endpoints without assertion rules must remain not configured.
5. New assertion rule creation must use Settings-driven default status code.
