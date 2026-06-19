# Architecture transition map

This document maps the old 1.1.34 architecture direction to the current 0.0.53 rebuild.

## Core aggregate

The project remains the root workspace object.

Most operational data is project-scoped:

- endpoints;
- environments;
- auth profiles;
- safe scans;
- findings;
- evidence;
- import runs;
- native tests;
- release gates;
- reports;
- client portal access.

## New 0.0.x layers

### Access layer

`ProjectAccessService`, `project_memberships` and user onboarding form the first team-use foundation.

### Evidence layer

`EvidenceRepositoryService` turns evidence into checksum-backed, lifecycle-audited repository objects.

### Import adapter layer

`ExternalQaImportService` normalizes external artifacts into Aptoria entities instead of copying external tools.

### Native test evidence layer

`NativeTestEvidenceService` gives Aptoria its own test suites, cases and runs while still accepting imported test results.

### QA cockpit layer

`QaCockpitService` calculates evidence quality, coverage and blind spots from existing proof.

### Release gate layer

`ReleaseGateWorkflowService` freezes source state into reviewable gate items.

### Decision package layer

`ReleaseGateDecisionPackageService` converts a gate into a fixed report/version/exportable package.

## Design principle

External tools may produce useful data, but Aptoria owns the release evidence model.

```text
Postman / Newman / Jira / OpenAPI / CSV / HAR
              ↓
        Import Adapter Layer
              ↓
Endpoint / Assertion / Finding / Evidence / Test Run
              ↓
       QA Cockpit + Release Gate
              ↓
     Decision Package + Report Export
```
