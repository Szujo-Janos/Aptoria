# Aptoria v1.0.36 System Audit — Calendar Activity Log & Header Actions Hotfix

## Scope

Built cumulatively from v1.0.35. This release fixes the calendar action-button placement and turns the QA Operations Calendar into an immutable activity timeline for create/update/delete operations.

## Changes

- Moved calendar action buttons from the global page action area into the calendar panel header.
- Added activity log metadata fields to `calendar_events`.
- Added `activity_log` calendar event type.
- Added immutable system-locked calendar entries for model create/update/delete events.
- Registered calendar activity observers for Aptoria domain models.
- Prevented editing, completing or deleting system-locked activity log entries.
- Added calendar UI badges for immutable log entries.
- Added tests for activity log creation and delete protection.
- Added `docs/CALENDAR_ACTIVITY_LOG_OPERATIONS.md`.

## Release hygiene

The release ZIP must not include:

- `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The Aptoria UI vendor assets remain included because this clean branch still uses the Aptoria UI UI foundation.

## QA focus

- Run `php artisan migrate` to add activity-log columns.
- Run `php artisan test`.
- Create/edit/delete a project or endpoint and verify immutable calendar log entries appear.
- Verify system activity entries cannot be deleted.
- Verify calendar action buttons are in the calendar panel header.
