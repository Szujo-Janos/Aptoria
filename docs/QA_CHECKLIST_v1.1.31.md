# Aptoria v1.1.31 QA Checklist – Internal Roles & Project Memberships Pass

1. Install from `aptoria-1.1.31.zip` using the documented PowerShell template.
2. Run `php artisan migrate` and `php artisan test`.
3. Log in as a system admin and create two projects.
4. Open **Project → Members & Roles** on the first project.
5. Confirm the project owner is shown as **Project admin** and the current project header shows the active project role.
6. Add an existing user to the first project as **QA engineer**.
7. Log in as that user and confirm only the assigned project appears in **Projects** and the second project is not visible.
8. As the QA engineer, create or edit a finding and add evidence.
9. As the QA engineer, try to approve a report or finalize a release decision.
10. Confirm the system blocks the action with a 403 response or restricted UI state.
11. Change the same user to **Release approver** from **Members & Roles**.
12. Confirm the user can finalize a release decision but still cannot manage project members.
13. Add another user as **Read-only viewer**.
14. Confirm the read-only user can open the assigned project but cannot create findings, evidence, accepted risks, reports or release decisions.
15. Open the audit log and confirm membership changes and denied project action attempts are recorded.
