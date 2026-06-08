# Aptoria v1.0.38 System Audit — Calendar Activity Log Display Labels Hotfix

## Scope

v1.0.38 fixes the remaining calendar activity log localization/display issue. The v1.0.37 fix translated the action and subject type, but raw technical subject names such as `scan.enabled` were still displayed for project settings. v1.0.38 keeps those raw keys in structured metadata while rendering human-readable labels from the active UI language.

## Key changes

- Added display-time translation for project setting activity names.
- Added display-time translation hook for global setting activity names.
- Split activity action labels into title and sentence contexts.
- Added translated label maps for project setting keys in English and Hungarian.
- Extended calendar regression tests for project setting activity log display labels.

## Expected examples

English UI:

- `Created project setting: Safe scan enabled`
- `Automatic calendar audit entry for created project setting #101.`

Hungarian UI:

- `Létrehozva projektbeállítás: Safe scan engedélyezése`
- `Automatikus naptárnapló bejegyzés: létrehozott projektbeállítás #101.`

The record still stores `scan.enabled` in `activity_payload` for audit accuracy.

## Release hygiene

The release ZIP must not contain:

- `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The Aptoria UI vendor assets remain included because this branch still uses the Aptoria UI UI line.

## QA focus

- Switch UI language between English and Hungarian on the Calendar page.
- Verify existing immutable activity logs render according to the current language.
- Verify project setting activity logs do not display raw keys such as `scan.enabled` in the user-facing title.
- Run `CalendarOperationsTest` and the full PHPUnit suite locally.
