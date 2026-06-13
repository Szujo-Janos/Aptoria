# Aptoria v1.1.32 – Language & UI Text Audit Hotfix

## Scope

This hotfix reviews the v1.1.32 project files for visible localization issues introduced around the internal project membership and release workflow work.

## Fixed

- Project permission abilities no longer render as raw internal codes such as `project.view`, `members.manage`, `release.finalize` or `report.approve` on the Members & Roles screen.
- The current project permissions panel now renders localized permission labels.
- The role permission matrix now renders localized permission labels.
- Project role labels are localized through the language files instead of being displayed from hardcoded model constants.
- Project membership access-denied and audit summaries now use localized messages.
- Admin/workspace authorization abort messages now use language keys.
- Previous v1.1.32 fixes remain in place:
  - `messages.common.open` no longer leaks into UI.
  - Decision Room Blade bootstrap code no longer renders as raw text.
  - Members & Roles icon uses a Font Awesome 4 compatible icon.
  - Page-load fade/panel animations stay disabled while `$.fn.animatePanel` remains available as a no-op compatibility guard.

## Audit checks run

- PHP syntax lint over `app/`, `routes/`, `database/`, `resources/lang/`, and Blade views.
- English/Hungarian language leaf parity check.
- Static `messages.*` translation path usage check across PHP and Blade files.
- View-level hardcoded text scan.
- Raw internal permission-code scan for visible Blade output.

## Result

- English language leaves: 4374
- Hungarian language leaves: 4374
- Missing English language paths used statically: 0
- Missing Hungarian language paths used statically: 0
- PHP/Blade syntax lint errors: 0

