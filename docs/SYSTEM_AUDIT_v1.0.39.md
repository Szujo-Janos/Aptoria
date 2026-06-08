# Aptoria v1.0.39 System Audit — Calendar UX, Activity Noise Reduction & Visual Timeline Hotfix

## Scope

v1.0.39 improves the QA Operations Calendar so it behaves as a usable operational timeline instead of a noisy technical event dump. The release is cumulative from the clean v1.0.24 branch and builds on v1.0.38.

## Calendar activity noise reduction

Project creation now records the user-level project creation event while suppressing automatic setup records created as part of that workflow:

- default environment
- default auth profile
- default project settings

Manual creation or update of user-facing records remains auditable. Project setting updates outside the default seeding path can still be recorded.

## Calendar UX

- Calendar days are clickable and open a dedicated day view.
- Event tones are color-coded by action/type: create, update, delete, alert, monitor, release, maintenance, security, regression and manual QA.
- Multi-day events are expanded across all affected days in the month grid.
- Multi-day chips use start/middle/end segment classes to make the event appear as a continuous visual process.
- Upcoming events and day view use the same tone system.

## Export behavior

JSON feed and .ics export now use overlap-based range filtering, so events that started before the selected range but are still active inside it are included.

## Checks performed in build environment

- PHP syntax lint: PASS
- English/Hungarian translation key parity should remain equal.
- Release ZIP hygiene must continue to exclude `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` and `storage/app/setup-token.txt`.

Full PHPUnit execution still needs to be run locally after installing Composer dependencies.
