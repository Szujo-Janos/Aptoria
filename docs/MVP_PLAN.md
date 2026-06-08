# Aptoria Product Status & Roadmap

Current version: **v1.0.65 – Settings Full Wiring Pass**  
Current status: **post-MVP / early beta**

Aptoria started as an MVP for self-hosted API QA and regression monitoring. The application has now moved beyond a minimal MVP and contains a broad QA workflow layer: endpoint inventory, safe scanning, assertions, snapshots, test cases, findings, contract validation, release readiness and evidence export.

This document replaces the old milestone-style MVP plan. The goal now is release discipline, documentation alignment and controlled hardening.

---

## Product definition

Aptoria is a self-hosted Laravel tool for:

- API endpoint visibility;
- safe non-destructive API scan evidence;
- assertion and regression review;
- manual/automated QA test case context;
- findings and release evidence;
- release readiness decisions.

The short positioning remains:

> Self-hosted API QA / Security Review / Regression Monitor.

---

## Current status

### Completed beyond MVP

- Project and environment management
- Auth profile management
- Endpoint inventory
- CSV/JSON/OpenAPI import
- Safe GET/HEAD scanning
- Endpoint probing
- Assertion rules
- Response body assertions
- Snapshot and compare workflow
- Regression evaluation
- Scheduled monitor configuration
- Test suites and test cases
- Test execution dashboard
- QA coverage matrix
- OpenAPI contract validation
- Findings & Evidence Center
- Full QA report builder
- QA evidence pack export
- QA release gate
- First-run setup wizard
- English/Hungarian UI
- Windows/XAMPP helper scripts

### Still early beta

The system is useful, but should still be treated as early beta because:

- production deployment hardening still needs more review;
- scheduled monitor execution now has baseline Windows/Linux documentation, but live production use still needs environment-specific validation;
- role/permission separation is not a full enterprise access model;
- notifications are not yet a complete workflow;
- public GitHub publishing now has a source-available license posture and expanded third-party notices;
- documentation must remain synchronized with releases.

---

## Current release baseline

The current clean baseline remains:

```text
v1.0.24 – Roboto Typography & System Audit
```

The active public-readiness release line is now:

```text
v1.0.42 – Light Button & Badge Typography Hotfix
v1.0.46 – Aptoria UI Vendor Asset Runtime Hotfix
v1.0.48 – Credits & Copyright Notice Pass
```

Future work must continue from the current stable release line unless a different base is explicitly selected.

---

## Immediate roadmap

### Completed public-readiness work

- Aptoria product identity is visible across normal UI surfaces.
- Runtime assets load from `public/assets/aptoria-ui`.
- Vendor asset 404 regressions were fixed in v1.0.46.
- Public repository license, notices, checklist and GitHub templates were added in v1.0.47.
- Explicit copyright notice, project credits and README attribution were added in v1.0.48.

### Next candidate areas

- screenshot-ready demo workflow;
- sample public reports;
- clearer report presentation and PDF-ready output;
- role/permission model;
- better onboarding demo project.

---

## Non-goals for the immediate phase

Do not start large UI rewrites, framework migrations or feature expansion before the following are stable:

- green local test suite;
- current documentation;
- predictable release ZIP build;
- clear GitHub publishing decision and repository license posture;
- deployment/security checklist.

---

## MVP conclusion

MVP as a development target is no longer the right label. The product should now be described as:

```text
post-MVP / early beta self-hosted API QA workflow tool
```

