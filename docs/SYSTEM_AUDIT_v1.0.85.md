# Aptoria v1.0.85 - Export Credit Namespace Hotfix

## Scope

This hotfix stabilizes the v1.0.84 export attribution pass by fixing a namespace import regression in release readiness dependency resolution.

## Fixed

- `app/Services/ReleaseReadinessService.php` now imports `App\Services\Exports\ExportCreditService`.
- Laravel no longer attempts to resolve the non-existent `App\Services\ExportCreditService` class when rendering dashboard and project detail pages.
- The centralized export credit service and report attribution behavior from v1.0.84 remain unchanged.

## QA Focus

1. Run `php artisan test`.
2. Confirm `DashboardCalendarPreviewTest` passes.
3. Open dashboard and project detail pages.
4. Generate a release readiness report and confirm the Aptoria credit footer is still present.

## Release Hygiene

- VERSION is `1.0.85`.
- Release ZIP keeps GitHub Actions and Windows/XAMPP scripts.
- Release ZIP excludes root `vendor/`, `.env`, SQLite runtime databases, setup locks and setup tokens.
