# Aptoria v1.1.19 – System Audit

## Release theme

**QA Blind Spot Detector Pass** turns Aptoria further toward an evidence-first API QA and release decision platform. The release does not try to duplicate request builders, issue trackers or observability tools. It focuses on the missing-evidence layer: what is still unknown before a responsible release decision.

## Implemented scope

- Added `QaBlindSpotDetectorService` for project-level blind spot aggregation.
- Added Project → Blind Spots page with summary cards, filters and a detailed table.
- Detects:
  - endpoint without scan evidence
  - endpoint without assertion rules
  - auth-required endpoint without no-auth comparison evidence
  - fixed finding without retest evidence
  - accepted risk without expiry date
  - expired / soon-expiring accepted risk
  - stale scan evidence
  - release context without recent release report / release gate snapshot
- Added accepted risk expiry and justification fields to findings.
- Added `Retest evidence` as an active finding evidence type.
- Integrated Blind Spot Summary into:
  - Release Readiness screen
  - Release Readiness markdown export
  - Full QA Report Builder executive/technical profiles
  - standard full project QA report export
- Added English and Hungarian translations.
- Added feature tests for the detector, release readiness integration and report builder integration.

## Database changes

New migration:

```text
database/migrations/2026_06_11_000000_add_accepted_risk_expiry_to_findings_table.php
```

New nullable fields on `findings`:

```text
accepted_risk_expires_at
accepted_risk_note
```

## Release packaging rules

The release ZIP must exclude local/runtime files:

- `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The bundled Aptoria UI vendor assets must remain present:

```text
public/assets/aptoria-ui/vendor
```

## Short QA checklist

1. Create an endpoint and do not run a scan. It should appear under Project → Blind Spots as endpoint without scan.
2. Add an assertion rule to that endpoint. The missing assertion blind spot should disappear.
3. Mark an auth-required endpoint as scanned without no-auth comparison evidence. The auth comparison blind spot should appear.
4. Mark a finding as Fixed without adding retest evidence. It should appear as an unverified fix.
5. Add Retest evidence to the Fixed finding. The unverified fix blind spot should disappear.
6. Mark a finding as Accepted risk without expiry. It should appear as a release-blocking risk expiry blind spot.
7. Add an expired accepted-risk date. It should become an expired accepted risk blind spot.
8. Open Release Readiness and verify that Blind Spot Summary is visible and affects blockers/warnings.
9. Generate executive and technical reports and verify the Blind Spot Summary section.
10. Run migrations and feature tests after installation.
