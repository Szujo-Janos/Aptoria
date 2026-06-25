# Aptoria v0.0.54 – Client Portal Decision Handoff Foundation

## Purpose

Aptoria already creates checksum-backed Release Gate Decision Packages. This pass makes those packages visible in the public Client Portal as a dedicated handoff surface instead of treating them as just another report row.

This keeps the product direction clear:

- Postman/Newman/Jira can feed evidence into Aptoria.
- Aptoria freezes the release decision into a release gate package.
- The Client Portal hands that package to external reviewers or clients.

Aptoria does not become a ticket tracker or API runner clone. It becomes the evidence handoff layer around those tools.

## What changed

### New service

```text
app/Services/ClientPortalDecisionHandoffService.php
```

The service finds approved release gate decision packages and prepares public-safe summary data:

- final decision
- release version
- target environment
- score / grade
- blocker and warning counts
- verified evidence count
- test run count
- high/critical finding state
- checksum
- approval metadata

### New portal permission

```text
decision_package
```

This is separate from the broader `reports` permission. A portal link can now expose the fixed decision package as the primary artifact without broadly exposing all approved reports.

### Public portal panel

The public portal now renders a dedicated **Decision Packages** card when the portal token allows `decision_package`.

Each package shows:

- approved package badge
- gate title
- checksum preview
- final decision
- release score
- blocker count
- verified evidence count
- test run count
- decision note when available
- HTML/PDF/JSON/ZIP downloads

### ZIP delivery

The public report download route now supports:

```text
/client-portal/{token}/reports/{reportVersion}/download/zip
```

ZIP is allowed only for approved release-gate-linked decision package reports.

## Security rules

- Public access is token-based.
- Expired or inactive portal tokens are rejected.
- A token scoped to one report cannot download a different report.
- ZIP delivery requires either `reports` permission or `decision_package` permission for a release gate package.
- Only approved report versions are public-deliverable.
- Public downloads still update report delivery/download counters.

## QA checklist

1. Create a release gate.
2. Finalize it.
3. Generate a Release Gate Decision Package report.
4. Mark the report as reviewed, then approved.
5. Create a client portal delivery link from the approved report.
6. Open the public portal link.
7. Confirm the Decision Packages panel is visible.
8. Confirm final decision, score, blockers, verified evidence and checksum are visible.
9. Download HTML, PDF, JSON and ZIP from the public portal.
10. Confirm ZIP contains the release gate report, decision package JSON and checksum manifest.
11. Create a portal link without `decision_package` permission and confirm the panel is hidden.
12. Keep reports permission on and confirm normal approved report downloads still work.
13. Restrict a token to a single report and confirm it cannot download another report.
14. Confirm acknowledgement still records reviewer name, decision and comment.

## Follow-up

The next natural hardening step is a Client Portal role preset pass:

- client viewer
- external reviewer
- release approver
- restricted evidence reviewer

Each preset should apply a safe default permission set.
