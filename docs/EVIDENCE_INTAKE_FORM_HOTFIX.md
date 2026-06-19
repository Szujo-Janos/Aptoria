# Aptoria v0.0.47 Hotfix - Evidence Intake Form Consistency

## Problem

The v0.0.47 Evidence Repository Foundation added a long Add Evidence modal. In real use, the modal could become too tall and the Evidence creation flow was not reliably scrollable.

The form also failed the Aptoria UI form rules in practice because several fields had no dedicated help text and validation feedback area.

## Decision

Evidence creation is treated as a complex editor, not a small create modal.

The Add Evidence action now opens a dedicated page:

```text
/projects/{project}/evidence/create
```

This keeps the flow readable, naturally scrollable and aligned with the Aptoria form standard.

## UI standard applied

The Evidence intake form now uses:

- one card panel shell;
- a short description in the panel header;
- grouped form sections;
- labels on every field;
- placeholders on every input/textarea where relevant;
- help text on every field;
- validation feedback slots on every field;
- Save / Cancel actions in the card footer;
- responsive layout;
- dark/light compatible panel styling.

## Sections

The intake page is split into:

1. Evidence identity
2. Evidence links
3. Proof content
4. Repository review context

## Permission rule

The create page requires `evidence.manage`, even though it is a GET route.

Read-only users can view Evidence Center, but cannot open the creation screen.
