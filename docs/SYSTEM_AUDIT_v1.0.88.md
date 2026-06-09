# Aptoria v1.0.88 - Report Branding & Author Profile Polish System Audit

Date: 2026-06-09

## Scope

This audit covers the v1.0.88 report presentation polish pass. The release focuses on making generated HTML and PDF reports look more professional, reducing repeated Aptoria wording in report headers, separating project metadata, and adding profile-driven report preparer identity.

## Changes reviewed

- HTML report header now uses the Aptoria logo as the primary brand element and no longer repeats the Aptoria product name as a separate large text block.
- HTML report metadata is shown in a separated meta bar with Project, Base URL, Generated and Aptoria version fields.
- HTML report footer uses smaller, structured credit lines instead of one dense paragraph.
- Markdown credit footers are stripped before rendering HTML/PDF so the report body does not duplicate the footer credit.
- PDF reports now include project name, base URL, generated timestamp, Aptoria version and Prepared by metadata near the top.
- Profile now contains Report identity fields used by generated reports.
- Report identity fields are optional except the display name fallback, which uses the authenticated user's name.
- README remains free of embedded changelog sections and links to CHANGELOG.md for release history.

## New profile fields

- `report_display_name`
- `report_role_title`
- `report_organization`
- `report_github_url`
- `report_website_url`

## Release hygiene

- Release ZIP must not include root `vendor/`.
- Release ZIP must not include `.env`.
- Release ZIP must not include `database/database.sqlite`.
- Release ZIP must not include `storage/app/installed.lock`.
- Release ZIP must not include `storage/app/setup-token.txt`.
- `public/assets/aptoria-ui/vendor` must remain included.

## QA focus

1. Run migrations and confirm the new profile report identity fields are added.
2. Save report identity data in My Profile.
3. Generate Full Project HTML and PDF reports.
4. Confirm Prepared by and Organization metadata appear correctly.
5. Confirm the HTML footer is compact and not duplicated in the report body.
6. Confirm the README has no embedded changelog section.
