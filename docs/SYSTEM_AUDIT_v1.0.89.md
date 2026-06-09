# Aptoria v1.0.89 - Professional Report Layout Pass System Audit

Date: 2026-06-09

## Scope

This audit covers the v1.0.89 professional report layout pass. The release replaces the previous colorful/report-branding presentation with a cleaner technical audit report style for generated HTML and PDF exports.

## Changes reviewed

- HTML report exports now use a restrained professional QA / audit report header with minimal color, no dashboard-style gradient treatment and a narrower report canvas.
- HTML report metadata now includes Project, Organization / client, Base URL, Generated timestamp and Aptoria version.
- Organization / client is sourced from the authenticated user's profile Report identity organization field.
- If the organization field is blank, the HTML/PDF metadata shows `Not configured` rather than omitting the field or breaking the layout.
- Prepared by and Role / title remain profile-driven Report identity fields in the report header.
- HTML report bodies now strip the leading Markdown H1 when it matches the report title, avoiding duplicate titles in exported reports.
- PDF report exports now include a professional QA / audit report label, report title, Project, Organization / client, Base URL, Prepared by, optional Role / title, Generated timestamp and Aptoria version near the top.
- PDF output includes simple separator rules for a more report-like structure while keeping the renderer dependency-free.
- README remains free of embedded changelog sections and links to CHANGELOG.md for release history.

## Report identity fields used

- `report_display_name` -> Prepared by
- `report_role_title` -> Role / title
- `report_organization` -> Organization / client
- `report_github_url` -> export credit metadata
- `report_website_url` -> export credit metadata

## Release hygiene

- Release ZIP must not include root `vendor/`.
- Release ZIP must not include `.env`.
- Release ZIP must not include `database/database.sqlite`.
- Release ZIP must not include `storage/app/installed.lock`.
- Release ZIP must not include `storage/app/setup-token.txt`.
- `public/assets/aptoria-ui/vendor` must remain included.

## QA focus

1. Save Report identity fields in My Profile, including Organization.
2. Generate Full Project HTML and PDF reports.
3. Confirm Organization / client appears in the report metadata.
4. Confirm blank Organization shows as `Not configured`.
5. Confirm the HTML/PDF layout is clean, technical and print-friendly.
6. Confirm report bodies do not duplicate the leading Markdown title or Markdown credit footer.
7. Confirm README links to CHANGELOG.md instead of embedding release history.
