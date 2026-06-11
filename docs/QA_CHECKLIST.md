# Aptoria v1.1.28 QA Checklist

Release: **v1.1.28 - QA Cockpit Pass**  
ZIP: `aptoria-1.1.28.zip`

## Required checks

- [ ] Install from `aptoria-1.1.28.zip` using the documented PowerShell template.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan optimize:clear`, `view:clear`, `config:clear`, and `route:clear`.
- [ ] Run `php artisan test`.
- [ ] Open **Project → Reports** and confirm the QA Cockpit panel is visible.
- [ ] Open **Project → QA Cockpit**.
- [ ] Create an Executive, Technical, Release Readiness or Full Project report draft.
- [ ] Confirm the saved version has a checksum and source evidence snapshot.
- [ ] Mark a report version as Reviewed.
- [ ] Approve a report version and confirm approved by / approved at metadata is populated.
- [ ] Export the saved version as Markdown, HTML, PDF and JSON.
- [ ] Confirm Audit Log records report version create / approve / archive events.

## Release ZIP exclusions

- [ ] No root `vendor/` directory.
- [ ] No root `.env` file.
- [ ] No `database/database.sqlite`.
- [ ] No `storage/app/installed.lock`.
- [ ] No `storage/app/setup-token.txt`.
- [ ] `public/assets/aptoria-ui/vendor` remains included.

## v1.1.20 Finding Verification & Ownership check

After installing v1.1.20 or later, open **Project → Findings**, create or edit a finding, assign an owner, due date, priority, verification status and retest requirement. Move the finding through **Ready for retest → Retest failed → Fixed → Verified**, add retest evidence and a finding comment, then confirm Release Readiness and full QA reports show the verification summary.

## v1.1.21 Risk Acceptance Ledger check

After installing v1.1.21 or later, run migrations, open a finding, record an accepted risk, then open **Project → Risk Ledger**. Confirm missing expiry, expiring soon and expired accepted risk decisions are visible and that Release Readiness includes the Risk Acceptance Ledger Summary.

## v1.1.22 Release Decision Room check

After installing v1.1.22 or later, open **Project → Release Decision Room**, finalize a Go / No-Go / Conditional Go decision package, and confirm Markdown, HTML, PDF and JSON exports are available.

## v1.1.23 API Behavior Map check

After installing v1.1.23 or later, create related endpoints such as `POST /orders`, `GET /orders/{id}` and `DELETE /orders/{id}`. Open **Project → API Behavior Map** and confirm producer / consumer links and destructive endpoint markers are visible.

## v1.1.24 Evidence Graph check

After installing v1.1.24 or later, open **Project → Evidence Graph** and confirm endpoint, finding and release evidence graph views show linked scans, assertions, findings, evidence attachments, accepted risks, release gates and release decisions.

## v1.1.25 Contract Reality Check

After installing v1.1.25 or later, run migrations and create or open an OpenAPI contract validation with scan evidence. Open **Project → Contract Reality** and confirm auth contract mismatches, undocumented response fields, missing documented endpoints and undocumented inventory endpoints are visible.

## v1.1.28 QA Cockpit

After installing v1.1.28 or later, open **Project → QA Cockpit** and confirm blockers, retest work, stale evidence, endpoint gaps and release decision queues are visible.
