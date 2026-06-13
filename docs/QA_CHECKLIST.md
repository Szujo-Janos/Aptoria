# Aptoria v1.1.32 QA Checklist – Release Workflow State Machine Pass

1. Install from `aptoria-1.1.32.zip` using the documented PowerShell template.
2. Run `php artisan migrate` and `php artisan test`.
3. Open an empty or newly created project and go to **Project → Release Workflow**.
4. Confirm that the workflow shows 15 steps from **Project setup complete** through **Client acknowledgement received**.
5. Confirm that an empty project shows blocked / not started states for endpoint inventory, scan evidence, release gate, release decision and report steps.
6. Add or import at least one endpoint.
7. Reopen **Project → Release Workflow** and confirm the endpoint inventory step no longer blocks because of missing inventory.
8. Run a safe scan.
9. Confirm **Latest scan completed** changes to Completed when the scan succeeds, or Blocked when the latest scan fails.
10. Create a Critical or High finding without evidence.
11. Confirm the workflow shows blocker / missing evidence signals for triage or evidence-related steps.
12. Move a finding to Fixed without retest verification and confirm the workflow still blocks.
13. Add retest evidence and move the finding to Verified.
14. Confirm the fixed / retested blocker is cleared.
15. Create a release gate and verify that its decision is reflected in the workflow.
16. Create a release decision and verify that the decision package contains a `release_workflow_snapshot` section.
17. Generate a report version, mark it reviewed, then approve it.
18. Confirm report generated, report reviewed and report approved steps update correctly.
19. Create a client portal link and confirm **Client handoff prepared** becomes Completed or Ready depending on portal availability.
20. Use a Release approver / Project admin account to skip **Client acknowledgement received** with a clear reason.
21. Confirm the step changes to **Skipped with reason** and the reason is visible in the workflow table.
22. Reopen the skipped step and confirm it recalculates to its computed state.
23. Open the audit log and confirm workflow skip / reopen events are recorded.

## Hotfix check – stale v2 test cleanup

1. Copy the hotfix ZIP over the existing local project.
2. Run `php artisan optimize:clear`.
3. Run `php artisan test --filter=UiFoundationLayoutTest`.
4. Run the full `php artisan test` suite.
5. Confirm the test output no longer expects `aptoria-v2-page-shell`, `aptoria-v2-actionbar` or `aptoria-v2-next-action`.
6. Confirm Dashboard and Project Details still render with the v1.1.32 layout and workspace buttons.

