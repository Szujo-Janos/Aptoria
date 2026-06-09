# Aptoria v1.0.38 System Audit — Calendar Activity Log Display Labels Hotfix

Base branch: clean cumulative v1.0.24 lineage through v1.0.36.

## Scope

v1.0.38 fixes the immutable calendar activity log localization issue. Activity log records keep structured metadata in `activity_action`, `activity_subject_type`, `activity_subject_id` and `activity_payload`; the UI now renders activity titles and descriptions from that metadata using the current application locale.

## Functional changes

- Calendar activity log titles are localized at render time.
- Calendar activity log descriptions are localized at render time.
- Activity subject names such as project, environment, auth profile and project setting are translated via `messages.calendar.activity_subjects`.
- Existing database records created in Hungarian or English are displayed in the currently selected language without data migration.
- Calendar month chips, upcoming event tables, JSON feed and `.ics` export use localized display text.
- Immutable/system-locked protection remains unchanged.

## Release checks

- PHP syntax lint: PASS in the build environment.
- English/Hungarian translation key parity maintained.
- Release ZIP excludes root `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` and `storage/app/setup-token.txt`.
- `public/assets/aptoria-ui/vendor` remains included for the current Aptoria UI line.

## Manual QA focus

1. Create a project while Hungarian is active.
2. Open Calendar and verify the activity log text is Hungarian.
3. Switch to English and verify the same activity log row changes to English.
4. Verify the immutable badge also follows the active language.
5. Verify `.ics` and JSON feed use the currently active language.
