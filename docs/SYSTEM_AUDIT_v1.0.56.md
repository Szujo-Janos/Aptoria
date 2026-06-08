# Aptoria v1.0.56 – Settings Expansion & Release Readiness Controls

## Scope

v1.0.56 expands the Settings Center from a small configuration page into a full operational control panel.

## Main changes

- Added new Settings groups:
  - Scan Profiles
  - Release Readiness
- Expanded existing groups:
  - General
  - HTTP Scan Behavior
  - Probe Safety
  - Risk Engine
  - Assertions
  - Snapshots & Retention
  - Reports & Exports
  - Dashboard & UI
  - Security & Privacy
- Added setting status labels:
  - Active
  - Prepared
  - Planned
- Added generic metadata-driven validation in `SettingsController`.
- Added setting metadata for ranges and select options in `SettingService`.
- Wired additional UI identity/body-class settings into the main layout.
- Extended sensitive value masking with configured sensitive header and JSON field names.
- Added `docs/SETTINGS_FUNCTIONAL_AUDIT.md`.

## Safety

The patch does not weaken the safe probe engine. POST, PUT, PATCH and DELETE remain blocked for automatic safe probes by default.

The patch does not remove the GitHub Actions public QA gate or CI security audit preconditions.

## Release ZIP policy

The release package remains cumulative and uses the short ZIP name:

`aptoria-1.0.56.zip`

## Manual QA checklist

- Settings page opens.
- All setting groups are visible.
- Active/Prepared/Planned badges render.
- Saving settings succeeds.
- Reset all defaults succeeds.
- Reset a single group succeeds.
- Export JSON includes the new groups.
- Main layout still loads CSS/JS assets.
- DevTools has no vendor asset 404.
- GitHub Actions public QA gate passes after push.
