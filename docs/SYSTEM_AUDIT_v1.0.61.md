# Aptoria v1.0.61 – Layout Page Title Fallback Hotfix

## Purpose

v1.0.61 fixes the remaining PHPUnit failures reported after v1.0.60. The uploaded full test output showed that all 29 failing tests shared the same root cause: `Undefined variable $aptoriaPageTitle` in `resources/views/layouts/app.blade.php`.

## Changes

- Added `aptoriaPageTitle` to the central `layouts.app` view composer.
- Added a defensive fallback inside the layout title assignment.
- Added a final fallback at the `<h2>` render point.
- Kept the Settings Center wiring and release-readiness/export changes from v1.0.57–v1.0.59.
- Kept the v1.0.60 layout view composer fixes.

## Release package

- ZIP name: `aptoria-1.0.61.zip`
- Root folder: `aptoria-1.0.61/`

## QA focus

- `php artisan test` should no longer fail with `Undefined variable $aptoriaPageTitle`.
- Layout-rendered pages should open without 500 errors.
- Settings, dashboard, project details, reports, calendar and help pages should render normally.
