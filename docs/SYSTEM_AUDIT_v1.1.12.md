# Aptoria v1.1.12 – Project Report Branding Pass System Audit

**Version:** v1.1.12  
**ZIP:** `aptoria-1.1.12.zip`  
**Scope:** project/client-specific report identity and branding overrides for exported QA reports.

## Summary

Aptoria v1.1.12 adds project-level report branding so each API QA workspace can carry its own client identity without changing the global user profile. Project branding now supports client name, organization/company, prepared-by override, role/title override, confidentiality label, disclaimer and an optional logo for HTML report exports.

## Implementation audit

| Area | Status | Notes |
|---|---|---|
| Version metadata | PASS | `VERSION`, config fallback and installation docs resolve to `1.1.12`. |
| Database migration | PASS | New project report branding fields are stored on `projects`. |
| Project UI | PASS | Create/edit project form exposes report branding fields and logo upload/removal. |
| Validation | PASS | Branding text fields are length-limited; logo upload is constrained to PNG/JPG/JPEG/WEBP/SVG up to 2 MB. |
| Identity resolution | PASS | Project-level values override profile report identity only for the current project. Empty project fields fall back to profile values. |
| HTML reports | PASS | Cover metadata uses project branding and embeds the project logo as a data URI when configured. |
| PDF reports | PASS | PDF cover metadata includes project/client, prepared-by, role/title, confidentiality and disclaimer text. |
| Markdown reports | PASS | Executive, Technical, Full Project, Release Readiness, QA Release Gate, QA Evidence, Scan and Snapshot Compare reports include branding context. |
| Localization | PASS | English and Hungarian labels added for the project branding UI. |
| Regression coverage | PASS | Feature coverage added for branding overrides and logo persistence/report embedding. |
| ZIP hygiene | PASS | Release package excludes local runtime artifacts and keeps `public/assets/aptoria-ui/vendor`. |

## QA focus

1. Install from `aptoria-1.1.12.zip`.
2. Run migrations.
3. Edit a project and fill all report branding fields.
4. Upload a report logo.
5. Export Executive and Technical HTML reports.
6. Confirm project branding overrides profile identity.
7. Export Markdown/PDF reports and confirm branding/disclaimer metadata appears.
8. Remove the logo and confirm HTML reports fall back to the Aptoria logo.

## Known constraints

- PDF export remains a lightweight internal renderer; it includes the branding text, but does not render bitmap/SVG logos.
- Logo upload is stored privately and embedded into generated HTML reports rather than served through a public URL.
