# Aptoria v1.1.30 System Audit – Client Portal Handoff Visibility Polish

Aptoria v1.1.30 improves the public Client Audit Portal handoff experience without changing the evidence permission model introduced in v1.1.29. The release focuses on making portal roles understandable and preventing restricted links from appearing empty.

## Changed files

- `app/Services/ClientPortal/ClientAuditPortalService.php`
- `app/Http/Controllers/ClientAuditPortalController.php`
- `resources/views/client_portal/index.blade.php`
- `resources/views/client_portal/public.blade.php`
- `resources/lang/en/messages.php`
- `resources/lang/hu/messages.php`
- `tests/Feature/ClientAuditPortalTest.php`
- `CHANGELOG.md`
- `README.md`
- `SERVER_INSTALLER.md`
- `docs/INSTALLATION.md`
- `docs/QA_CHECKLIST.md`
- `docs/QA_CHECKLIST_v1.1.30.md`
- `scripts/install-aptoria-1.1.30-windows-template.ps1`

## Functional audit

- Public client portal header now includes the Aptoria horizontal logo, project name, fixed header behavior and current role label.
- Public client portal dashboard now includes a role access summary generated from the actual permission set stored on the token.
- Restricted portal links now keep the client-safe release snapshot visible and clearly explain that content sections are disabled.
- Admin-side portal creation now includes a role default permission matrix and updates permission checkboxes when switching between viewer, approver and reviewer links.
- Existing backend permission enforcement remains unchanged for report downloads, evidence package downloads and acknowledgement actions.

## Security / permission notes

- The new role access summary is informational only; it does not grant access.
- Download and acknowledgement routes continue to enforce server-side permission checks.
- The public portal still remains project-scoped through the token-bound `ClientPortalAccess` model.

## Regression focus

- Client viewer links should show content permissions but no approval actions by default.
- Client approver links should show both content and acknowledgement actions.
- External reviewer links should show report acknowledgement but not release/risk acknowledgement by default.
- Fully restricted links should not look broken or blank; they should show only the client-safe snapshot and restricted capability list.
