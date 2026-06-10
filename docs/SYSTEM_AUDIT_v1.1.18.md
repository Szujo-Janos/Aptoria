# Aptoria v1.1.18 System Audit - Navigation & Profile Menu Cleanup Pass

## Scope

This audit covers the navigation information architecture cleanup and profile dropdown simplification introduced in Aptoria v1.1.18.

## Verified changes

- Global sidebar navigation is grouped by user task: Projects, Release & reports, Operations, Audit & admin, and Help & workflow.
- Current-project navigation is grouped by setup, API inventory, QA workflow, risk/evidence, release/reporting and automation/audit.
- Project-specific monitors are accessible from the project module list.
- Global monitor alerts are available under Operations.
- User/profile dropdown contains only account-level actions and one logout divider.
- Profile report identity copy clarifies default identity versus project-level branding overrides.

## Release hygiene

- Root vendor directory must not be included in the release ZIP.
- `.env`, `database/database.sqlite`, `storage/app/installed.lock` and `storage/app/setup-token.txt` must not be included.
- `public/assets/aptoria-ui/vendor` must remain in the release ZIP because the UI depends on these bundled assets.
