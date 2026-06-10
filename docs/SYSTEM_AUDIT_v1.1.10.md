# Aptoria v1.1.10 – Evidence Attachment Pass System Audit

**Version:** v1.1.10  
**Release title:** Evidence Attachment Pass  
**Base:** v1.1.9 Finding Lifecycle Pass

## Scope

This release extends finding evidence from simple notes/links into an auditable attachment workflow. It builds directly on the v1.1.9 finding lifecycle work.

## Implemented checks

| Area | Status | Notes |
|---|---|---|
| Version metadata | PASS | `VERSION`, config fallback and public installation docs resolve to `1.1.10`. |
| Evidence data model | PASS | `finding_evidence` now supports attachment metadata, request/response excerpts, cURL command, captured timestamp and captured-by user. |
| Migration safety | PASS | New migration adds columns only when missing and supports existing v1.1.9 databases. |
| Finding evidence UI | PASS | Finding detail page supports evidence type, label, captured timestamp, URL, file upload, note, request excerpt, response excerpt and cURL command. |
| Attachment handling | PASS | Attachments are stored under `storage/app/private/finding-evidence/{project}/{finding}` on the local disk and can be downloaded from the finding page. |
| Attachment audit data | PASS | Original name, MIME type, size and SHA-256 checksum are stored/displayed. |
| Deletion cleanup | PASS | Deleting evidence also removes the stored attachment file. |
| Reports | PASS | Release readiness, full project report and QA Evidence pack expose evidence and attachment counts/details. |
| Localization | PASS | English and Hungarian labels added for the new evidence fields and active evidence types. |
| Tests | PASS | Feature coverage added for evidence upload, request/response evidence, download and deletion cleanup. |
| Release packaging | PASS | ZIP excludes runtime/private files while preserving `public/assets/aptoria-ui/vendor`. |

## Security notes

- Uploaded evidence is stored on the Laravel local disk under `storage/app/private`, not under `public/storage`.
- Evidence downloads are routed through the authenticated/admin project finding route.
- Release ZIP packaging excludes `storage/*` runtime contents except `.gitkeep` placeholders, so uploaded evidence is not shipped in releases.
- Attachment size is limited by validation to 10 MB.

## Manual QA focus

1. Add request/response evidence to a finding.
2. Attach a screenshot or JSON response file.
3. Confirm filename, size, MIME type and SHA-256 are visible.
4. Download the attachment.
5. Delete the evidence and confirm the attachment is removed.
6. Export release readiness and full project reports and confirm evidence/attachment data appears.
