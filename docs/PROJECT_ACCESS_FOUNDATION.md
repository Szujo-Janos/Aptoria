# Aptoria Project Access & Membership Foundation

## Purpose

Aptoria is moving from single-admin local use toward controlled team use. This foundation adds project-scoped access before the full release-approval workflow is expanded.

This is not a Jira-style permission clone. The goal is narrower:

- keep API QA evidence isolated per project;
- prevent accidental cross-project access;
- make each user's project role visible in the UI;
- protect evidence, reports and release decision routes before collaborative workflows arrive.

## Roles

| Role | Purpose |
|---|---|
| Project admin | Full project control, settings, member management and destructive actions |
| QA engineer | Endpoint, scan, assertion, finding and evidence operations |
| Reviewer | Read/review evidence and findings without broad write access |
| Release approver | Release readiness, report approval and client portal handoff duties |
| Read-only viewer | View-only access to project evidence and reports |

The system administrator remains a global operator for local/self-hosted administration. The project owner is always locked as a project admin.

## Current scope

Implemented in v0.0.46:

- `project_memberships` table;
- owner membership backfill during migration;
- project access service;
- project route access middleware;
- nested project-resource ownership checks;
- Project Access UI;
- add/update/remove project member flows for existing users;
- scoped project switcher and project dashboard lists;
- audit events for membership changes.

## Current limitations

Still intentionally not included:

- invitation e-mails;
- user account creation UI;
- granular per-field permissions;
- report sign-off role enforcement beyond foundation checks;
- organization/team tenant model;
- external SSO.

These should come after the evidence repository and release gate workflow stabilize.

## QA checklist

- Existing project owners appear as locked project admins after migration.
- A non-member non-admin user cannot open `/projects/{project}`.
- A read-only viewer can open the project but cannot edit it.
- Project Access appears only for users with member-management access.
- Adding an existing user creates an active membership.
- Removing a member does not delete the user account.
- The owner membership cannot be edited or removed.
- Project switcher lists only visible projects for non-admin users.
