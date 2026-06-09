# Aptoria v1.0.13 - Professional Aptoria UI/UX Refresh

This release upgrades the Aptoria interface to use the Aptoria UI visual language more consistently across the whole application.

## Goals

- Keep the existing Laravel workflows intact.
- Reuse Aptoria UI layout conventions, panels, buttons, labels, tables and navigation behavior.
- Improve the professional look of project, scan, assertion, evidence, release gate and test execution screens without changing database logic.
- Preserve Windows/XAMPP compatibility and server first-run behavior from the 1.0.10 line.

## Included changes

- Professional content shell using Aptoria UI `normalheader` and `hpanel` patterns.
- Automatic page title / breadcrumb bar for all authenticated application pages.
- Project context indicator when a project route is active.
- Icon-enhanced sidebar navigation using Aptoria UI + FontAwesome conventions.
- Sidebar footer showing safe QA mode context.
- Refined Aptoria UI panel spacing, borders, heading accents and hover states.
- Polished table, form, button, label, pagination, alert and code block styling.
- Better dashboard hero and KPI card polish.
- Auth/setup background and panel polish using Aptoria UI assets.
- Responsive adjustments for smaller screens.

## Not changed

- No scan engine logic changes.
- No assertion engine logic changes.
- No migrations.
- No release gate business logic changes.
- No Evidence Pack export logic changes.

## QA focus

- Verify sidebar open/close behavior.
- Verify project module navigation.
- Verify long project menus remain scrollable.
- Verify dashboard, project detail, endpoint list, scan result, snapshot compare, test execution, QA Evidence and release gate screens.
- Verify login and setup screens.
- Verify mobile/tablet width does not break core workflow actions.
