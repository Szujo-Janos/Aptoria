# Aptoria v1.1.5 – Baseline Diff Viewer Pass System Audit

## Scope

This release extends saved snapshot comparisons into a richer baseline-vs-current diff viewer.

## Implemented

- Snapshot metadata now stores response body preview hash, excerpt and derived JSON schema paths.
- Compare runs detect status, response time, header, body preview, response size, schema, sensitive-data and unauthenticated-access drift.
- Compare summaries include breaking-change and category counters.
- Compare UI shows diff group and breaking indicators.
- Endpoint Inventory links directly to snapshot comparison.
- Full project and compare reports include richer diff summary information.

## Safety

- No new destructive HTTP behavior was added.
- Comparisons use stored snapshot evidence only.
- Body previews remain truncated/derived from existing stored safe-probe evidence.

## Manual QA

1. Create a baseline snapshot.
2. Create a current snapshot after changing response status/body/header/security signals.
3. Run snapshot compare.
4. Confirm diff categories and breaking counters.
5. Export compare report and full project report.
