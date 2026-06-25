# Aptoria v0.0.61 – Live/Sandbox Workspace Separation & Sandbox Safety Banner

Aptoria now separates real project work from guided sandbox/demo exploration at the project and UI level.

## Goal

The previous demo flow was technically safe, but it could still feel too close to normal production-style work. v0.0.61 adds a clear workspace boundary so users always know whether they are working in a real QA project or in a synthetic sandbox.

## Workspace modes

Aptoria supports two workspace modes:

- **LIVE** – real projects, real QA evidence and release decisions.
- **SANDBOX** – synthetic demo projects, guided scenarios and safe test data.

The active mode is stored in the session and is visible in the topbar switch.

## Project-level separation

Projects now have a `workspace_type` field:

```text
live | sandbox
```

Existing projects default to `live`. Demo builders create sandbox projects automatically.


## Legacy demo migration

During upgrade, existing v0.0.58–v0.0.60 demo projects are reclassified as sandbox workspaces:

- `Aptoria Full Demo Project` → `Aptoria Guided Demo Sandbox` when the new slug is still free.
- `Aptoria Live Demo API Sandbox` → `Aptoria Sandbox API` when the new slug is still free.

If the new sandbox project already exists, the legacy project is still marked as `sandbox` without forcing a rename.

## Topbar switch

The topbar includes a visible mode switch:

```text
LIVE | SANDBOX
```

When LIVE is active, the project switcher lists live projects only. When SANDBOX is active, it lists sandbox projects only.

## Safety banner

When SANDBOX mode is active, Aptoria shows a persistent warning strip below the topbar:

```text
SANDBOX MODE ACTIVE
This is a safe test workspace. Actions only affect synthetic demo data and cannot damage live projects.
```

The banner is intentionally sticky rather than a disappearing toast, because mode awareness must stay visible while the user navigates.

## Demo project names

The demo builders now use sandbox-oriented names:

- `Aptoria Guided Demo Sandbox`
- `Aptoria Sandbox API`

This avoids mixing “Live Demo” wording with real live operation.

## Audit trail

Mode switching is recorded in the audit log as a workspace event:

```text
workspace_mode_switched_to_live
workspace_mode_switched_to_sandbox
```

## Safe reset behavior

The sandbox reset/build workflow remains limited to the named sandbox demo projects. It does not touch other live projects.
