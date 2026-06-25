# Aptoria v0.0.60 – Sandbox Scenario Templates & Guided Demo Flow

## Purpose

The Sandbox API workspace now has reusable scenario templates. The goal is to make the hosted public demo understandable without a sales call or a manual explanation.

Instead of telling a visitor to “try the app”, the guide gives four concrete paths:

1. **First smoke scan** – start with healthy JSON and captured evidence.
2. **Security leak review** – inspect protected vs public endpoints and a synthetic leaked token finding.
3. **Artifact import trace** – import OpenAPI/Postman/QA CSV/HAR samples and follow their evidence trace.
4. **Release gate decision** – walk from QA Cockpit to readiness and a release decision package.

## Public UI

Public guide:

```text
/demo-guide
```

Authenticated project guide:

```text
/projects/{project}/demo-guide
```

The project-scoped version can deep-link directly into:

- Safe Scan
- Endpoint Inventory
- Auth Profiles
- Import Center
- Evidence Repository
- Evidence Packs
- Findings
- QA Cockpit
- Release Readiness
- Release Gates
- Reports

## JSON endpoints

```text
GET /demo-api/scenarios
GET /demo-api/scenarios/{slug}
GET /demo-api/scenarios/{slug}/evidence.json
GET /demo-api/artifacts/scenario-templates.json
```

Available slugs:

```text
first-smoke-scan
security-leak-review
artifact-import-trace
release-gate-decision
```

## Scenario evidence

Each scenario can expose a small evidence JSON run sheet through:

```text
/demo-api/scenarios/{slug}/evidence.json
```

This is intentionally import/review friendly. It contains:

- scenario title and objective;
- expected demo outcome;
- recommended endpoints;
- recommended artifacts;
- reviewer run-sheet steps;
- generation timestamp.

## Demo project seed changes

The `aptoria:demo-api-project` / `aptoria:demo-reset` build now also adds scenario-related endpoints to the generated Sandbox API workspace project:

```text
GET /scenarios
GET /scenarios/security-leak-review
GET /scenarios/release-gate-decision/evidence.json
```

It also creates verified repository evidence for the release gate scenario run sheet.

## Recommended hosted demo flow

1. Visitor opens `/demo-guide`.
2. Visitor picks a scenario template.
3. Visitor signs in with the demo viewer account.
4. Visitor opens the project-scoped guide from the sidebar.
5. Visitor follows the selected run sheet.
6. The scenario leads them from endpoint/API proof to evidence/release decision context.

## Notes

This feature is not a separate demo engine. It is a lightweight guided layer over the existing Sandbox API workspace so the public hosted demo stays cheap, transparent and easy to reset.
