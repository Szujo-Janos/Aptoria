# Aptoria v1.1.29 QA Checklist – Workflow Consolidation & Permission Hardening Pass

1. Install from `aptoria-1.1.29.zip` using the documented PowerShell template.
2. Run `php artisan migrate`.
3. Run `php artisan test`.
4. Open **Project → Release Workflow** and confirm the guided flow shows QA Cockpit, Blind Spots, Release Readiness, Release Gate, Release Decision, Report Approval and Client Portal steps.
5. Confirm the next-best-action panel points to the first incomplete workflow step.
6. Create a restricted Client Portal role without evidence package permission and confirm direct `/evidence-summary.json` and `/evidence-package.zip` URLs return 403.
7. Create a portal role without acknowledgement permissions and confirm report/release/risk acknowledgement POST requests return 403.
8. Create or update a release decision, accepted risk and API behavior link; confirm Audit Log records each model.
9. Check that newly added project modules keep the project sidebar group open/active.
10. Export or approve a report after the workflow review to confirm the release handoff path still works.
