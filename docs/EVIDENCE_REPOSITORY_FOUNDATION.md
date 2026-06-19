# Aptoria v0.0.47 – Evidence Repository Foundation

## Purpose

The Evidence Center is now treated as a central repository of QA proof, not only as attachments next to findings.

Aptoria is not meant to replace Postman, Jira or Newman. Those tools can still create useful raw inputs, but Aptoria must keep the evidence trail that explains:

- what was observed,
- where it came from,
- which project/finding/endpoint/scan it belongs to,
- who captured or reviewed it,
- whether it is still active or archived,
- whether the stored evidence body has silently changed.

## New repository fields

The `finding_evidence` table now includes repository metadata:

- `repository_status`: `active`, `verified`, `archived`
- `integrity_status`: `current`, `changed`
- `checksum_algorithm`: currently `sha256-v1`
- `repository_notes`: review notes, archive reason or release decision context
- `reviewed_by_user_id`, `reviewed_at`
- `archived_by_user_id`, `archived_at`

## Lifecycle trail

A new table stores evidence lifecycle events:

```text
evidence_lifecycle_events
```

Recorded actions in this foundation pass:

- `created`
- `verified`
- `archived`
- `restored`

This is intentionally separate from the global audit log. The global audit log records system activity. The evidence lifecycle trail records the history of a specific evidence object.

## Checksum behavior

When evidence is created, Aptoria calculates a deterministic SHA-256 checksum from the meaningful evidence material:

- project/finding/endpoint/scan links
- evidence type
- title
- source label
- content
- URL
- request excerpt
- response excerpt
- captured timestamp
- captured user

Repository status, review notes and archive status are not part of the checksum. They can change during review without making the captured proof look tampered with.

## Delete behavior

Evidence is no longer hard-deleted from the repository workflow.

The legacy delete route now archives the evidence item instead. This preserves the proof record, checksum and lifecycle history for release reviews and later audits.

## Role behavior

The project access layer now distinguishes evidence review from evidence management.

- QA engineer: can manage and review evidence.
- Reviewer: can view and verify evidence.
- Release approver: can view and verify evidence.
- Read-only viewer: can view evidence only.
- Project admin/system admin: full access.

## UI rules

The Evidence Center now uses the standard Aptoria admin UI pattern:

- KPI cards
- repository assurance panel
- filter row
- full-width table
- Actions dropdown
- detail screen
- checksum panel
- linked object panel
- lifecycle timeline

Relevant icons are used instead of generic repeated icons:

- Evidence repository: `folder-check`
- Checksum/integrity: `fingerprint`
- Lifecycle: `file-delta`
- Verified: `badge-check`
- Archived: `archive`
- Restored: `archive-restore`
- Linked finding: `bug`
- Linked endpoint: `route`
- Scan proof: `scan-eye`

## QA checklist

1. Create or open a project.
2. Open Evidence Center.
3. Confirm the sidebar Evidence icon is `folder-check`.
4. Confirm the Evidence Center shows repository metrics.
5. Add evidence manually.
6. Confirm the detail page opens after save.
7. Confirm the detail page shows SHA-256 checksum.
8. Confirm lifecycle contains `Evidence created`.
9. Verify the evidence.
10. Confirm repository status changes to Verified.
11. Confirm reviewed-by and reviewed-at are visible.
12. Archive the evidence.
13. Confirm it is not deleted from the database and appears under Archived filter.
14. Restore the evidence.
15. Confirm it returns to Active.
16. Use a Reviewer role and confirm they can verify evidence but cannot create/archive it.
17. Confirm Evidence Pack manifest includes verified/archived evidence counts.
18. Check dark/light mode.
19. Check table width and Actions dropdown alignment.
