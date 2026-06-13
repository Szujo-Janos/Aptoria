# Aptoria v1.1.31 System Audit – Internal Roles & Project Memberships Pass

Aptoria v1.1.31 adds the internal project membership layer required before deeper workflow, release decision and client handoff hardening. The release keeps system admins fully unrestricted, while allowing non-admin users to work only inside assigned projects and only through role-granted actions.

## Changed files

- `app/Models/ProjectMembership.php`
- `app/Models/Project.php`
- `app/Models/User.php`
- `app/Services/Access/ProjectAccessService.php`
- `app/Http/Middleware/EnsureWorkspaceAccess.php`
- `app/Http/Controllers/ProjectMembershipController.php`
- `app/Http/Controllers/Controller.php`
- `app/Http/Controllers/ProjectController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/ReleaseReadinessController.php`
- `app/Http/Controllers/FindingController.php`
- `app/Http/Controllers/FindingEvidenceController.php`
- `app/Http/Controllers/FindingCommentController.php`
- `app/Http/Controllers/RiskAcceptanceController.php`
- `app/Http/Controllers/ReleaseDecisionController.php`
- `app/Http/Controllers/ReportVersionController.php`
- `app/Http/Controllers/QaReleaseGateController.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/Audit/AuditLogService.php`
- `bootstrap/app.php`
- `routes/web.php`
- `database/migrations/2026_06_13_000000_create_project_memberships_table.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/DemoQaProjectSeeder.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/projects/index.blade.php`
- `resources/views/projects/show.blade.php`
- `resources/views/project_memberships/index.blade.php`
- `resources/views/report_versions/index.blade.php`
- `resources/views/report_versions/show.blade.php`
- `resources/views/release_decisions/index.blade.php`
- `resources/lang/en/messages.php`
- `resources/lang/hu/messages.php`
- `tests/Feature/InternalProjectMembershipsTest.php`
- `CHANGELOG.md`
- `README.md`
- `docs/INSTALLATION.md`
- `docs/QA_CHECKLIST.md`
- `docs/QA_CHECKLIST_v1.1.31.md`
- `scripts/install-aptoria-1.1.31-windows-template.ps1`

## Functional audit

- Added `project_memberships` with project/user uniqueness, inviter tracking, role, notes and joined timestamp.
- Added role definitions for Project admin, QA engineer, Reviewer, Release approver and Read-only viewer.
- Added a central permission map for project view/manage, member management, settings, endpoints, scans, monitors, tests, findings, evidence, risk acceptance, release finalization, report generation/review/approval, portal management and exports.
- Replaced the internal app middleware alias so system admins keep full access and non-admin members can access assigned workspace routes.
- Added **Project → Members & Roles** with member listing, email-based add, role update, member removal, permission matrix and current permissions display.
- Scoped project index, dashboard, reports and release readiness landing views to projects visible to the authenticated user.
- Hardened critical POST/PATCH/DELETE actions with server-side permission checks in release decisions, report versions, risk acceptances, findings, evidence and QA release gate flows.
- Updated project navigation and selected action toolbars so restricted actions are hidden or replaced with a clear restricted state.

## Security / permission notes

- System admins continue to bypass project membership restrictions.
- Project owners are treated as Project admins even if a membership row is missing.
- Non-admin users without any project membership or owned project cannot enter internal workspace routes.
- Read-only users can view assigned project pages but cannot mutate release evidence or workflow state.
- Release approver permission is intentionally separate from QA engineer permission so QA users cannot finalize release decisions or approve reports by accident.

## Audit coverage

- Member added events are recorded.
- Member removed events are recorded.
- Member role changed events are recorded.
- Denied project action attempts are recorded with requested ability, role and user e-mail metadata.

## Regression focus

- Existing admin users must still see all projects, setup/admin operations and global audit/admin menus.
- Project owners should receive Project admin membership during project creation and seeding.
- Existing projects are backfilled to Project admin membership during migration when an owner exists.
- Public Client Audit Portal token access remains unchanged and independent from internal project memberships.
