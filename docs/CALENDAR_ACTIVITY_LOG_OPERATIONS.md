# Calendar Activity Log Operations

Aptoria v1.0.39 extends the QA Operations Calendar with immutable system activity entries.

## Purpose

The calendar is now both an operations planner and a lightweight audit timeline. Business model changes are automatically written to the calendar as system activity entries, so operators can see what was created, updated or deleted around the same time as QA tasks, monitor alerts and release checkpoints.

## Logged actions

The calendar activity logger records Eloquent model lifecycle events:

- created
- updated
- deleted

The logger covers Aptoria's main domain models, including projects, environments, auth profiles, endpoints, assertion rules, monitors, scan results, snapshots, comparison runs, findings, release gates, test suites, test cases, alert events and calendar events.

## Immutable entries

System-generated activity entries are stored in `calendar_events` with:

- `event_type = activity_log`
- `status = completed`
- `priority = low`
- `is_system_locked = true`
- `activity_action`
- `activity_subject_type`
- `activity_subject_id`
- `activity_route`
- `activity_payload`

Locked activity log entries are shown in the calendar but cannot be edited, completed or deleted from the UI.

## UI placement

The calendar action buttons now live in the calendar panel header:

- Create calendar event
- Export `.ics`
- JSON feed

This keeps the operation controls attached to the calendar module instead of floating in the global page header.

## Safety

Activity logging is best-effort. If the calendar table is not migrated yet, or a logging error occurs, the original business action is not blocked.
