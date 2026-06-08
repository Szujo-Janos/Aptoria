# Aptoria v1.0.40 System Audit — Calendar Seed Noise & Title Visibility Hotfix

## Summary

v1.0.40 is a focused hotfix on top of v1.0.39. It fixes two calendar regressions observed after a clean reset/demo import:

- activity titles in the upcoming events table could become invisible because table row tone classes inherited white text color while the row background was reset to white;
- setup/demo import operations produced technical activity log noise such as created user, environment, auth profile and endpoint records.

## Changes

- Calendar table row tone CSS now colors only markers, chips and pills, not the full table row text.
- Activity titles remain readable in the upcoming events table.
- User model calendar auditing is disabled; setup/admin account creation is not treated as a QA operation.
- Setup admin creation, migrate-and-seed, DatabaseSeeder and DemoQaProjectSeeder now run with calendar activity recording suppressed.
- Project default setting seeding remains event-suppressed.
- A cleanup command was added for old noisy records:

```powershell
C:\xampp\php\php.exe artisan aptoria:calendar-cleanup-setup-noise
C:\xampp\php\php.exe artisan aptoria:calendar-cleanup-setup-noise --force
```

## Release hygiene

The release ZIP must exclude:

- `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The release ZIP keeps `public/assets/aptoria-ui/vendor` because this branch is still the Aptoria UI private-repo line.
