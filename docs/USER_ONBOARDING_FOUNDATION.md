# Aptoria User Onboarding Foundation

## Purpose

Project Access cannot be useful on its own if a teammate account has to be inserted manually into the database. This hotfix adds the missing onboarding layer between global users and project memberships.

## Scope

The current implementation is intentionally local/self-hosted and invitation-link free:

1. A system admin can open **Users** and create a local Aptoria user account.
2. Aptoria generates a one-time temporary password and shows it only after save.
3. The created user has `password_change_required = true`.
4. First login forces the user through the existing password-change flow.
5. Project admins can also use **Project Access → Create user and add** to create a normal user and immediately assign a project role.

## System roles

- `admin`: instance/system owner. Can manage users and global settings.
- `user`: normal Aptoria account. Access comes from project memberships.

## Project roles

Project roles remain project-scoped and are managed on the Project Access screen:

- Project admin
- QA engineer
- Reviewer
- Release approver
- Read-only viewer

## Security rules

- Temporary passwords are shown once through session flash data.
- New users must change their password on first login.
- A user created from Project Access is always created as system `user`, not as global `admin`.
- The last system admin cannot be demoted from the Users screen.
- A system admin cannot demote their own account from the Users screen.

## What this does not do yet

- No e-mail invitation delivery.
- No signed invite links.
- No account disable/delete state.
- No password reset e-mail flow.
- No external identity provider integration.

These belong in a later collaboration/security pass.
