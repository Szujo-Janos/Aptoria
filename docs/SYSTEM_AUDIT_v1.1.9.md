# Aptoria v1.1.9 – Finding Lifecycle Pass System Audit

## Scope

This release adds an auditable finding lifecycle workflow on top of the existing Findings & Evidence module.

## Added capabilities

- Canonical lifecycle statuses: Open, Confirmed, In progress, Fixed, False positive, Accepted risk and Reopened.
- Legacy Triaged and Closed values remain readable for backwards compatibility, but the active UI uses the canonical lifecycle list.
- Quick lifecycle transition form on finding details.
- Lifecycle notes for review, fix, accepted-risk and false-positive context.
- Changed-at and changed-by tracking.
- Reopened count tracking.
- Finding lifecycle history table.
- Dedicated `finding_lifecycle_events` audit table.
- Findings index now surfaces fixed, accepted-risk, false-positive and reopened counts.
- Release readiness score checks now show lifecycle state counts and treat Reopened as open release risk.
- Release readiness and full QA reports include lifecycle status breakdowns.

## Safety

- Invalid status transitions are rejected.
- Closed, fixed, false-positive and accepted-risk findings can be reopened.
- Reopened findings are treated as open findings for QA coverage and release-readiness logic.
- Existing triaged/closed statuses are preserved to avoid breaking old data.

## Verification

- PHP syntax check should pass for app, routes, resources/lang, database and tests.
- Full `php artisan test` should be executed in the installed environment where `vendor/` is available.
