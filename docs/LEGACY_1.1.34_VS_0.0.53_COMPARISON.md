# Aptoria 1.1.34 vs 0.0.53 comparison

This document compares the archived `aptoria-1.1.34` package with the current `aptoria-0.0.53` rebuild.

## Executive summary

`v1.1.34` was a larger early-beta API QA / audit / release-readiness application with many modules, older UI assumptions and a broader monitor/report/test-management scope.

`v0.0.53` is not a linear continuation of that branch. It is a cleaner evidence-first rebuild focused on standalone API QA evidence, repository evidence, import adapters, native test evidence, QA cockpit, release gates and checksum-backed decision packages.

The old version should be archived. The new version should replace it as the public GitHub baseline.

## Codebase comparison

| Area | v1.1.34 old | v0.0.53 current | Meaning |
| --- | ---: | ---: | --- |
| Total files | 666 | 406 | The new line is smaller and more focused. |
| Controllers | 52 | 39 | Several old workflow modules were removed/deferred. |
| Models | 39 | 41 | New release/evidence/test models replaced parts of old workflow models. |
| Services | 55 | 36 | Service layer is flatter and more focused in the rebuild. |
| Migrations | 61 | 57 | Schema is different; do not run as an in-place DB upgrade. |
| Blade views | 112 | 94 | New UI is leaner and standardized around Aptoria UI rules. |
| Feature tests | 72 | 30 | Test coverage was reset around the new foundation modules. |
| Docs | 151 | 19+ | Old documentation was broad; new docs are workflow-specific and should grow from here. |

## Product direction change

| Topic | v1.1.34 | v0.0.53 |
| --- | --- | --- |
| Product positioning | Broad API QA / audit / regression monitor / release-readiness suite | Evidence-first API QA and release decision platform |
| Main value | Many modules around API audit and workflow | Normalized evidence, verified proof, release gates and decision packages |
| External tools | Import/support features existed in multiple places | Explicit Import Adapter Layer: external tools feed Aptoria evidence instead of being cloned |
| Test model | Older test-suite/test-case/test-result direction | Native Test Evidence model tied directly to Evidence Repository |
| Release decision | Several release readiness / gate / workflow concepts | Release Gate Workflow + Release Gate Decision Package |
| Evidence | Finding evidence and evidence packs | Central Evidence Repository with checksum, lifecycle, verify/archive/restore |
| UI | Larger legacy/Homer-style application surface | New Aptoria UI shell, official SVG logo, semantic icon policy, scrollable modal standards |
| GitHub baseline | Historical / archived | Current public source-available baseline |

## Rebuilt or retained concepts

| Concept | Status in 0.0.53 |
| --- | --- |
| Projects | Rebuilt |
| Environments | Rebuilt |
| Auth profiles | Rebuilt |
| Endpoint inventory | Rebuilt |
| Safe scan / safe probe evidence | Rebuilt foundation |
| Assertions | Rebuilt foundation |
| Snapshots / compare | Rebuilt as endpoint snapshot foundation |
| Findings | Rebuilt with deduplication / retest / risk acceptance direction |
| Evidence | Rebuilt as central Evidence Repository |
| Evidence packs | Rebuilt with standardized HTML/PDF/ZIP exports |
| Report visual standard | Rebuilt and strengthened |
| Client portal | Rebuilt foundation |
| Calendar | Retained as simplified foundation |
| Audit log | Retained/rebuilt foundation |
| User/project access | New in 0.0.x foundation |
| Import adapters | New explicit layer |
| Native test evidence | New domain layer |
| QA Cockpit / blind spots | New foundation |
| Release gates | New foundation |
| Release gate decision package | New in 0.0.53 |

## Deferred / not carried forward as active baseline

The following old 1.1.34 areas are not treated as complete active modules in the current 0.0.53 baseline:

- scheduled monitors and alert delivery as a full operations module;
- API behavior map;
- evidence graph visualization;
- database maintenance/export/import/reset UI;
- old project wizard flow;
- old release workflow implementation;
- old QA coverage/blind spot module implementations;
- old monitor alert follow-up workflow;
- old report builder variants that do not follow the new report visual standard.

These ideas are not necessarily rejected, but they should be rebuilt only if they support the new evidence-first release decision direction.

## Database compatibility

The migration names, model assumptions and workflow tables differ between the two packages.

**Do not apply 0.0.53 over an existing 1.1.34 database as an in-place upgrade.**

Use one of these strategies:

1. Fresh install with a new database.
2. Keep the old 1.1.34 database as an archive.
3. Manually export important legacy information before replacement.
4. Re-import normalized endpoints/evidence/test data through the new Import Adapter Layer where possible.

## Final decision

Use `v0.0.53` as the new GitHub baseline and archive `v1.1.34` as historical code.
