# Aptoria v0.0.49 Hotfix – Native Test Modal Form Workflow

## Purpose

Native Test Evidence forms must follow the Aptoria modal workflow where practical, while still staying usable when the form is long.

This hotfix restores modal-based forms for:

- creating a native test suite;
- creating a native test case;
- recording a native test run.

## UX rule

Forms should be modal-based when they are a normal create/edit action inside the current context.

Long forms must not become fixed-height, non-scrollable modals. They must use:

- `modal-xl`
- `modal-dialog-scrollable`
- `.aptoria-scrollable-form-dialog`
- sticky header/footer behavior through the shared CSS
- scrollable `.aptoria-scrollable-form-body`

## Form standard retained

The modal body still keeps the required Aptoria form structure:

- grouped form sections;
- clear section headers with semantic icons;
- labels on every field;
- placeholders;
- help text;
- validation feedback placeholders;
- Save / Cancel actions in the footer.

## Validation behavior

Every native test modal form includes:

```html
<input type="hidden" name="_native_test_modal" value="...">
```

If validation fails, the old modal ID is read and the same modal is reopened automatically.

## Fallback routes

The existing dedicated create routes remain as safe fallback/deep-link routes, but the primary UI buttons now open modals again.
