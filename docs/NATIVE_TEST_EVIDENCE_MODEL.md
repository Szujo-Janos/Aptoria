# Aptoria v0.0.49 - Native Test Evidence Model

## Goal

Aptoria must not become a Postman, Newman or Jira clone. The Native Test Evidence model gives Aptoria its own test domain so it can work standalone while still accepting external test artifacts through the Import Adapter Layer.

## Added domain objects

- `test_suites` - release/workflow scoped groups of test cases.
- `test_cases` - reusable manual/imported/hybrid test definitions.
- `test_runs` - concrete execution results.
- `finding_evidence.test_case_id` and `finding_evidence.test_run_id` - links repository evidence back to native test proof.

## Evidence flow

Recording a native test run always creates a `test_result` evidence record through `EvidenceRepositoryService`. This means every native run receives:

- deterministic SHA-256 checksum,
- repository status,
- integrity status,
- lifecycle `created` event,
- test case/run traceability.

When a run is marked `fail`, Aptoria can also create a finding automatically. This is optional because not every failed exploratory test should become a release blocker immediately.

## UI rules

Large test forms are dedicated pages, not oversized modals:

- Create test suite
- Create test case
- Record test run

Each form uses the Aptoria form standard:

- panel header,
- short description,
- grouped sections,
- labels,
- placeholders,
- help text,
- validation feedback,
- save/cancel footer.

## Permissions

Project permissions added:

- `tests.view`
- `tests.manage`

Role behavior:

- Project admin: full access.
- QA engineer: manage native tests.
- Reviewer: view native tests.
- Release approver: view native test evidence for release decisions.
- Read-only viewer: view native tests only.

## Why this matters

External tools can execute requests, track tickets or produce result files. Aptoria now owns the durable QA proof layer:

```text
Test suite -> Test case -> Test run -> Repository evidence -> Optional finding -> Release decision
```

This is the standalone capability that keeps Aptoria useful even when no external tool is connected.
