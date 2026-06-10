# Aptoria v1.1.6 System Audit

## Release

**Aptoria v1.1.6 – Schema Drift Detector Pass**

## Scope

This release adds response JSON schema extraction and schema drift detection against the previous completed scan for the same endpoint. It identifies added fields, removed fields, type changes and nullability changes. Detected drift is surfaced in scan results, Endpoint Inventory, findings/evidence and reports.

## Safety

- No destructive HTTP methods are introduced.
- The detector only uses already captured safe GET/HEAD response previews.
- Response previews remain subject to existing masking and storage settings.
- Findings use masked evidence summaries.

## Release hygiene

- `vendor/` is excluded from the release ZIP.
- `.env` is excluded.
- `database/database.sqlite` is excluded.
- `storage/app/installed.lock` is excluded.
- `storage/app/setup-token.txt` is excluded.
- `public/assets/aptoria-ui/vendor` remains included.
