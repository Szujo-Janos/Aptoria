# Aptoria v1.1.8 System Audit

## Release

Aptoria v1.1.8 – Release Readiness Score Pass

## Scope

This release enhances the existing release readiness dashboard with an explicit weighted score model. The final score is now backed by visible components for evidence, endpoint coverage, QA coverage, assertion health, regression execution, snapshot regression status, security/auth readiness, open findings, contract validation and report/sign-off readiness.

## Verification notes

- PHP syntax check should pass for application, route, migration, view and test files.
- Public release documentation must reference `aptoria-1.1.8.zip`.
- ZIP hygiene must exclude root `vendor/`, `.env`, SQLite runtime database and setup lock files.
- `public/assets/aptoria-ui/vendor` must remain bundled.

## Manual QA focus

1. Open Project → Release Readiness.
2. Confirm the weighted score breakdown table is visible.
3. Confirm the component point totals add up to 100.
4. Export the release readiness Markdown report and confirm the score breakdown section is included.
5. Switch English/Hungarian and verify no raw translation keys are visible.
