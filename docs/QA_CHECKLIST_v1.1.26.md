# Aptoria v1.1.26 QA Checklist

Release: **v1.1.26 - Report Versioning & Approval Pass**  
ZIP: `aptoria-1.1.26.zip`

## Installation

- [ ] Install from `aptoria-1.1.26.zip` using the documented PowerShell template.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan optimize:clear`, `view:clear`, `config:clear`, and `route:clear`.
- [ ] Run `php artisan test`.

## Report Versioning & Approval

- [ ] Open **Project → Reports** and confirm the Report Versioning & Approval panel is visible.
- [ ] Open **Project → Report approvals**.
- [ ] Create a Technical report draft.
- [ ] Open the created report version detail page.
- [ ] Confirm the status is Draft and a checksum is visible.
- [ ] Confirm source scan IDs, finding state rows, release gates, release decisions and evidence IDs are captured where available.
- [ ] Download the report version as Markdown, HTML, PDF and JSON.
- [ ] Mark the report as Reviewed.
- [ ] Approve the report and confirm approved by / approved at are populated.
- [ ] Archive a report version and confirm it is no longer actionable.

## Report integration

- [ ] Export a Technical report after an approved version exists.
- [ ] Confirm the latest approved report checksum is visible in the generated report header.
- [ ] Open Audit Log and confirm report version create / approve / archive events are recorded.
