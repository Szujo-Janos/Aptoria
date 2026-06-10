# Aptoria v1.1.7 System Audit - Regression Test Suite Builder Pass

## Scope

This release adds a Regression Test Suite Builder to turn endpoint inventory into executable QA suites.

## Added

- Suite Builder page under project Test Suites.
- Generated hybrid test cases with execution order and builder metadata.
- Optional expected-status assertion rules.
- Optional required JSON path assertion rules.
- One-click suite run that uses existing safe GET/HEAD probe behavior and records test case results.
- English/Hungarian localization and feature tests.

## Safety

- Automatic execution still respects safe probe constraints.
- Non-GET/HEAD, inactive or excluded endpoints are recorded as blocked/skipped rather than executed destructively.
- Existing auth, network guard, sensitive-data and schema-drift protections remain in the safe probe pipeline.

## Release hygiene

Release ZIP excludes root vendor/, .env, database/database.sqlite and setup lock files while keeping public/assets/aptoria-ui/vendor.
