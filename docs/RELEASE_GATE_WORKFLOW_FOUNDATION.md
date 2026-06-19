# Aptoria v0.0.50 — Release Gate Workflow Foundation

## Purpose

The release gate workflow is not a Jira workflow clone and it is not a Postman/Newman execution layer. It is the Aptoria decision layer that freezes the current QA evidence state and turns it into a reviewable release gate.

A release gate connects:

- release readiness checks,
- Evidence Repository verification state,
- native test run results,
- open high/critical finding risk,
- external import/contract/readiness signals,
- human reviewer overrides,
- final go / conditional go / no-go decision.

## New domain objects

```text
release_gates
release_gate_items
release_gate_events
```

## Workflow

1. A project user with release management permission creates a release gate.
2. Aptoria creates a fresh release readiness run.
3. Readiness checks become release gate items.
4. Additional source-state items are added for verified evidence, native tests and high/critical findings.
5. Reviewers can review individual items and add a manual state plus note.
6. The gate recalculates blockers/warnings after every review.
7. A release approver can finalize the gate as:
   - Go,
   - Conditional go,
   - No-go.

## Decision rule

A plain `Go` decision is not allowed while effective blocker items remain. In that case the reviewer must either:

- clear/waive the blockers with reviewer notes, or
- use `Conditional go` and explain the release context.

## UI standard

The new forms use Aptoria modal workflow:

- New release gate: scrollable XL modal.
- Gate item review: scrollable modal.
- Finalize gate: scrollable modal.

Every form keeps:

- label,
- placeholder,
- help text,
- validation feedback,
- grouped form sections,
- fixed modal footer actions.

## Permissions

- Read-only/project readers can view gates.
- Release management users can create gates and review items.
- Release approvers can finalize gates.
- System/project admins retain full access.

## QA checklist

- Create a gate from Release Gates.
- Confirm a readiness run is linked.
- Confirm gate items are generated.
- Review an item and set a manual state.
- Confirm blocker/warning counters recalculate.
- Try finalizing as Go while a blocker exists: this must fail.
- Finalize as Conditional go with a note: this must succeed.
- Confirm audit log and gate timeline events are created.
