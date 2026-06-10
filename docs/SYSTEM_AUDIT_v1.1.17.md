# Aptoria v1.1.17 System Audit - Demo Project Sample Data Generator Pass

## Scope

This pass adds a post-installation demo project generator so a fresh Aptoria instance can be evaluated immediately with realistic, synthetic QA data.

## Implemented

- Authenticated **Demo Project** page with import, re-import/reset and remove actions.
- CLI generator: `php artisan aptoria:demo-project`.
- JSON CLI output for deployment verification: `php artisan aptoria:demo-project --json`.
- Removal mode: `php artisan aptoria:demo-project --remove`.
- Summary cards for demo environments, auth profiles, endpoints, scans, snapshots, diffs, findings, evidence, test suites, test cases, release gates, monitors and alerts.
- Navigation integration in the sidebar and user menu.
- Audit log events for demo import and removal.
- Existing setup-time demo import remains available before setup lock.

## Safety

- Demo data uses synthetic stored evidence only.
- Importing the demo project does not contact external API targets.
- Re-importing replaces only the fixed demo project slug: `northstar-commerce-demo-review`.
- Removing the demo project deletes only the dedicated demo project.

## Validation

- PHP syntax check should pass for all application, route, config, database and test PHP files.
- Feature tests cover web import, remove and CLI import/remove workflows.
- Release ZIP excludes runtime state and keeps `public/assets/aptoria-ui/vendor`.
