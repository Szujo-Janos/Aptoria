# Aptoria v1.1.18 QA Checklist

Release: **v1.1.18 - Navigation & Profile Menu Cleanup Pass**
ZIP: `aptoria-1.1.18.zip`

## Install / smoke

- [ ] Install from `aptoria-1.1.18.zip` using the documented PowerShell template.
- [ ] Run `php artisan optimize:clear`.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan aptoria:health`.
- [ ] Run `php artisan test`.

## Navigation checks

- [ ] Open the dashboard and confirm the sidebar is grouped into Projects, Release & reports, Operations, Audit & admin, and Help & workflow.
- [ ] Open any project and confirm project-specific items are grouped under Current project, API inventory, Quality workflow, Risk & evidence, Release & reports, and Automation & audit.
- [ ] Confirm project Monitors are available inside the current project group.
- [ ] Confirm global Monitor Alerts are under Operations, not hidden in the profile menu.
- [ ] Confirm System Health, Settings, Audit Log and Demo Project are grouped under Audit & admin.
- [ ] Open the user/profile dropdown and confirm it only contains account actions: My Profile, Default Report Identity, Settings, Help, and Sign out.
- [ ] Confirm the profile dropdown has no blank menu rows and no duplicate operational links such as Calendar, Monitors, Release Readiness or Demo Project.
- [ ] Open Profile and confirm the report identity section is labelled Default Report Identity and explains project override behavior.
