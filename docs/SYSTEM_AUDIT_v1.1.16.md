# Aptoria v1.1.16 System Audit

## Release

Aptoria v1.1.16 - Audit Log Activity Timeline Pass

## Scope

This pass adds a dedicated audit log and activity timeline layer for tracking important Aptoria workflow events. It complements the calendar activity entries with an audit-specific table, filterable UI and JSON export.

## Implemented

- `audit_logs` database table with project, user, event type, action, severity, subject, request metadata, before/after values and metadata.
- `AuditLog` model.
- `AuditLogService` with sensitive value masking and safe failure behavior.
- `AuditLogObserver` for auditable Eloquent model changes.
- Explicit authentication login/logout audit events.
- Explicit database export/import and hard reset request events.
- Report generation audit event from report downloads.
- Global `/audit-log` timeline.
- Project-specific `projects/{project}/audit-log` timeline.
- JSON export for global and project audit timelines.
- Navigation integration.
- English and Hungarian localization.
- Feature tests for model activity logging, project timeline filtering, JSON export and auth events.

## Release hygiene

- Root `vendor/` is excluded from release ZIP.
- `.env` is excluded from release ZIP.
- `database/database.sqlite` is excluded from release ZIP.
- `storage/app/installed.lock` is excluded from release ZIP.
- `storage/app/setup-token.txt` is excluded from release ZIP.
- `public/assets/aptoria-ui/vendor` remains included.
