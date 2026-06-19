# Aptoria v0.0.51 – UI & Workflow Stabilization Pass

## Purpose

This pass stabilizes the workflow surface before more feature expansion. The goal is not to redesign the product, but to make the existing screens predictable and consistent.

## Rules reinforced

- Tables use content-driven automatic column sizing.
- Action columns stay compact and remain the last column.
- Long values wrap or truncate inside the panel instead of breaking the layout.
- Long forms stay modal-based when they are normal CRUD workflows.
- Large modals must be scrollable, with header and footer remaining usable.
- Form sections keep the Aptoria pattern: panel, description, grouped fields, help text, validation feedback and save/cancel actions.
- Navigation links must point to real implemented modules instead of placeholders when a module exists.
- Icons must remain semantic and tied to the function, not copied randomly from other modules.

## Stabilized areas

- QA workspace navigation now includes QA Cockpit as the central evidence-quality view.
- Topbar links avoid placeholder module routes when real project routes are available.
- Project workspace module metadata reflects active implemented modules more accurately.
- Scrollable modal CSS now covers the shared `aptoria-scrollable-form-dialog` class used by Native Test and Release Gate forms.
- Coverage tables use the standard resource table shell and compact Actions column.

## QA checklist

- Open sidebar and topbar with and without a selected project.
- Verify implemented modules open real screens instead of placeholder pages.
- Open Native Test and Release Gate modals on a smaller desktop viewport and confirm the body scrolls.
- Check Actions columns in Release Gate, Evidence, Native Test, Import Center and QA Cockpit tables.
- Confirm dark/light mode does not break modal backgrounds or section borders.
