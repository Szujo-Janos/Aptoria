# Aptoria v1.1.32 – Members & Roles UX Review

## Goal

The Members & Roles screen now separates four concepts that were previously mixed together:

1. Internal Aptoria users
2. Project membership
3. Project role assignment
4. Effective project permissions

## New page structure

- Project access summary
- How project membership works
- Add existing Aptoria user
- Create new user and add to project
- Current project members
- Role permission matrix
- My current project permissions

## Expected user flow

1. Open Current Project → Members & Roles.
2. Check the available internal users list.
3. Pick a role and add an existing user directly.
4. If the person is not listed, create a new internal user and add them to the project in one action.
5. Review or edit project roles in the Current project members table.

## QA checklist

1. Open Members & Roles as a Project admin.
2. Confirm that existing internal users not yet in the project are visible.
3. Add an existing user with QA engineer role.
4. Confirm that the user moves to Current project members.
5. Create a new internal user from the right-side form.
6. Confirm that the new user appears as a project member.
7. Confirm that role labels and permission labels are translated in English and Hungarian.
8. Confirm that no raw permission codes are visible in user-facing panels.
