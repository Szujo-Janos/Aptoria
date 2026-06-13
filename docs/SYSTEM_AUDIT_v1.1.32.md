# Aptoria v1.1.32 System Audit – Release Workflow State Machine Pass

Aptoria v1.1.32 turns the existing Release Workflow page from a static navigation checklist into a persistent, state-driven release workflow. The goal is to make the application explain what is complete, what is blocked, which evidence is missing and what the next required action is before a release can be finalized.

## Changed files

- `app/Models/ReleaseWorkflow.php`
- `app/Models/ReleaseWorkflowStep.php`
- `app/Models/Project.php`
- `app/Http/Controllers/ReleaseWorkflowController.php`
- `app/Services/ReleaseWorkflow/WorkflowConsolidationService.php`
- `app/Services/ReleaseDecisions/ReleaseDecisionRoomService.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/Audit/AuditLogService.php`
- `database/migrations/2026_06_13_000100_create_release_workflows_table.php`
- `database/migrations/2026_06_13_000200_create_release_workflow_steps_table.php`
- `resources/views/release_workflow/show.blade.php`
- `resources/lang/en/messages.php`
- `resources/lang/hu/messages.php`
- `routes/web.php`
- `tests/Feature/WorkflowConsolidationPermissionHardeningTest.php`
- `tests/Feature/ReleaseDecisionRoomTest.php`
- `docs/QA_CHECKLIST.md`
- `docs/QA_CHECKLIST_v1.1.32.md`
- `docs/SYSTEM_AUDIT_v1.1.32.md`
- `CHANGELOG.md`
- `README.md`
- `docs/INSTALLATION.md`
- `SERVER_INSTALLER.md`

## Functional audit

- Added `release_workflows` to persist the current project-level workflow summary, progress, blocker count, missing evidence count, next step and snapshot JSON.
- Added `release_workflow_steps` to persist step state, computed state, manual skip state, skip reason, blocker reasons, completion criteria and evidence summary.
- Replaced the seven-row workflow overview with a 15-step state machine:
  1. Project setup complete
  2. Endpoint inventory reviewed
  3. Latest scan completed
  4. Blind spots reviewed
  5. Critical / High findings triaged
  6. Fixed findings retested
  7. Accepted risks reviewed
  8. Release readiness calculated
  9. Release gate evaluated
  10. Release decision finalized
  11. Report generated
  12. Report reviewed
  13. Report approved
  14. Client handoff prepared
  15. Client acknowledgement received
- Added state labels: Not started, In progress, Blocked, Needs review, Ready, Completed and Skipped with reason.
- Added release pre-check output that lists workflow failures blocking final release sign-off.
- Added next-action resolution so the user is guided to the first incomplete / blocked step.
- Added manual skip-with-reason and reopen actions for users with release finalization permission.
- Added workflow snapshot data into release decision packages.
- Added audit logging for skipped and reopened workflow steps.

## Security / permission notes

- Viewing the release workflow still uses project access checks from v1.1.31.
- Skip / reopen actions require the existing `release.finalize` project permission.
- Read-only users can inspect workflow state but cannot skip or reopen steps.
- Manual skipped steps require a non-empty reason and remain visible as manual overrides.

## Regression focus

- Existing release decision, report approval, release gate and client portal pages remain the source pages for their workflow actions.
- Existing QA Cockpit, Blind Spots and Release Readiness calculations are reused rather than duplicated.
- Release Decision packages remain exportable as Markdown, HTML, PDF and JSON, with the workflow snapshot added under `release_workflow_snapshot`.
- Existing internal project role restrictions from v1.1.31 remain active.
