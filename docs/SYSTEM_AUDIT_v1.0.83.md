# Aptoria v1.0.83 - User Profile Center System Audit

## Scope

This release adds the first dedicated authenticated profile center to Aptoria.

## Changes

- Added `UserProfileController`.
- Added `/profile`, profile update and profile password update routes.
- Added `resources/views/profile/show.blade.php`.
- Added user `locale` and `timezone` columns.
- Added profile link to the authenticated user dropdown.
- Login now applies the stored user locale when available.
- Locale middleware can use the authenticated user's stored locale as a fallback.
- Added `UserProfileTest`.

## Manual QA Focus

1. Open the profile page from the top-right user menu.
2. Save profile details.
3. Change password.
4. Confirm locale preference is applied.
5. Confirm dashboard/settings regressions stay stable.

## Release Hygiene

- `VERSION` is `1.0.83`.
- Release ZIP root is `aptoria-1.0.83/`.
- Release ZIP excludes root `vendor/`, `.env`, SQLite databases, setup locks and setup tokens.
- Release ZIP keeps GitHub Actions, Windows/XAMPP scripts and Aptoria UI vendor assets.
