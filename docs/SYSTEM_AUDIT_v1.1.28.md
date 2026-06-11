# Aptoria v1.1.28 System Audit – QA Cockpit Pass

## Scope

Aptoria v1.1.28 adds the QA Cockpit: a project-level daily work cockpit that turns existing evidence, release readiness and blind spot data into actionable QA queues.

## Added / changed areas

- `app/Services/Cockpit/QaCockpitService.php`
- `app/Http/Controllers/QaCockpitController.php`
- `resources/views/qa_cockpit/index.blade.php`
- `tests/Feature/QaCockpitTest.php`
- Project route: `projects.qa-cockpit.index`
- Sidebar integration under Quality workflow
- English and Hungarian localization

## Evidence-first impact

The cockpit does not create a parallel task tracker. It aggregates existing release evidence and shows what a QA lead should check next:

- open blockers
- fixes waiting for retest
- accepted risks expiring soon
- stale scan evidence
- stale/missing approved reports
- endpoints without scan/assertion evidence
- release candidates needing a saved decision
- unacknowledged monitor alerts
- top blind spots
- recently changed high-risk endpoints

## Release ZIP exclusions

The release ZIP must not contain:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

`public/assets/aptoria-ui/vendor` must remain included.
