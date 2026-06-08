# Aptoria v1.0.41 System Audit — Dashboard Project-Style Calendar Preview Pass

## Scope

v1.0.41 is a UI/workflow polish release on top of v1.0.40. It changes the global dashboard presentation so it follows the same workspace pattern as the Project Details page and adds lightweight calendar previews to both surfaces.

## Changed areas

- `app/Http/Controllers/DashboardController.php`
  - Adds global upcoming calendar preview data.
  - Adds calendar summary counters for open, due-today and overdue items.
- `app/Http/Controllers/ProjectController.php`
  - Adds project-scoped calendar preview data for the Project Details page.
  - Adds project-scoped calendar summary counters.
- `resources/views/dashboard/index.blade.php`
  - Replaces the old dashboard hero with a project-style overview panel.
  - Adds Calendar preview to the dashboard.
- `resources/views/projects/show.blade.php`
  - Adds project-scoped Calendar preview.
- `resources/views/calendar/_preview.blade.php`
  - New reusable preview component for dashboard and project workspaces.
- `public/assets/aptoria/css/app.css`
  - Adds dashboard project-style and calendar preview styling.
- `tests/Feature/DashboardCalendarPreviewTest.php`
  - Adds regression coverage for dashboard and project calendar previews.

## Release hygiene

Expected release ZIP exclusions remain unchanged:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

Expected release ZIP inclusions remain unchanged:

- `.env.example`
- `.env.production.example`
- `.env.testing`
- `public/assets/aptoria-ui/vendor`

## QA focus

- Dashboard top section visually matches the Project Details workspace pattern.
- Dashboard Calendar preview renders upcoming events.
- Project Details Calendar preview is project-scoped.
- Preview links open the relevant calendar/day views.
- Calendar colors and tone markers remain readable.
- Existing calendar noise cleanup and seed suppression behavior from v1.0.40 remains intact.
