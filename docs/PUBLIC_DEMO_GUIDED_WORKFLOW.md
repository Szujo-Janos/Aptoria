# Aptoria Public Demo Guided Workflow

Version: **v0.0.60**

This document describes the public demo workflow built on top of the Sandbox API workspace.

## Purpose

The public demo is not a read-only screenshot gallery. It lets visitors test Aptoria against a real JSON API target while keeping dangerous admin and unrestricted scanning actions blocked.

Recommended public layout:

```text
aptoria.dev          marketing / landing
www.aptoria.dev      redirect to aptoria.dev
demo.aptoria.dev     Aptoria demo app
```

For the first hosted demo, the demo API can run inside the same Laravel app under:

```text
/demo-api/*
```

Later it can be split to:

```text
api-demo.aptoria.dev
```

## Public guide

The public guide is available at:

```text
/demo-guide
```

It shows:

- demo login credentials;
- demo API base URL;
- live JSON endpoints;
- intentional error/sensitive/slow endpoints;
- import artifact URLs;
- suggested walkthrough steps;
- public demo guardrail state;
- selectable scenario templates;
- scenario-specific run sheets;
- scenario evidence JSON links.

Authenticated users with project access can open the project-scoped guide at:

```text
/projects/{project}/demo-guide
```

This version links directly into Safe Scan, Import Center, Evidence Center, QA Cockpit and Release Gates for the selected project.

## Demo reset

Admins can rebuild the sandbox project through:

```text
Program Settings -> Sandbox API workspace
```

or from CLI:

```powershell
C:\xampp\php\php.exe artisan aptoria:demo-reset
```

The reset only replaces the project with slug:

```text
aptoria-live-demo-api
```

Other projects are not touched.

## Public demo mode

For a public hosted instance, enable:

```env
APTORIA_DEMO_MODE=true
APTORIA_DEMO_API_BASE_URL=https://demo.aptoria.dev/demo-api
APTORIA_DEMO_ALLOWED_TARGETS=demo.aptoria.dev
```

When public demo mode is enabled, Aptoria blocks destructive admin and license/user-management actions and can restrict safe scan targets to the configured allowlist.

## Demo walkthrough

0. Pick a scenario template on `/demo-guide`: smoke scan, security leak review, artifact import trace or release gate decision.
1. Open `/demo-guide`.
2. Sign in with the demo viewer account.
3. Open the Sandbox API workspace project.
4. Run Safe Scan on the demo endpoints.
5. Import OpenAPI / Postman / QA CSV / HAR / scenario template artifacts from `/demo-api/artifacts/*`.
6. Review Evidence Repository records and checksums.
7. Open QA Cockpit and inspect coverage/blind spots.
8. Create or inspect a Release Gate.
9. Generate a Decision Package in non-read-only/internal demo mode.

## Guardrails

Do not expose the public demo with unrestricted scan targets. Keep the target allowlist narrow.

Recommended blocked areas for public demos:

- License Management
- Private License Issuer
- User Management
- Database maintenance
- Project deletion
- Client portal mutation
- Runtime/system sensitive exports



## Scenario template endpoints

```text
GET /demo-api/scenarios
GET /demo-api/scenarios/{slug}
GET /demo-api/scenarios/{slug}/evidence.json
GET /demo-api/artifacts/scenario-templates.json
```

These endpoints allow the public guide, docs and import tests to use the same scenario source instead of duplicating demo instructions.
