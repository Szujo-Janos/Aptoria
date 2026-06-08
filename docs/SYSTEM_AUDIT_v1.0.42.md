# Aptoria v1.0.42 System Audit — Light Button & Badge Typography Hotfix

## Scope

v1.0.42 is a focused UI typography hotfix on top of v1.0.41. It does not add new workflow features. It normalizes button, badge and label font weight so the interface no longer presents action/control text as heavy bold or semi-bold.

## Changes

- Added a late CSS override in `public/assets/aptoria/css/app.css` for admin/auth surfaces.
- Added a matching landing-page override in `public/assets/aptoria/css/landing.css`.
- Targeted `.btn`, `.label`, `.badge`, soft badges, workflow badges, project badges, sidebar counters, calendar badges and calendar upcoming table badges.
- Preserved all existing colors, contrast classes, borders, spacing, icons and calendar activity tones.

## Release hygiene

The release package must continue to exclude:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The Aptoria UI vendor asset directory is still intentionally included for the current UI baseline.

## QA focus

- Buttons should no longer look bold/semi-bold.
- Badges and labels should use lighter/normal text.
- Status colors must remain visible.
- Calendar event colors and immutable-log badges must remain readable.
- Dashboard, Project Details, Calendar, Login and Landing surfaces must keep their layout.
