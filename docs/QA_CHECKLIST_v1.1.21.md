# Aptoria v1.1.21 QA Checklist

Release: **v1.1.21 - Risk Acceptance Ledger Pass**
ZIP: `aptoria-1.1.21.zip`

## Install / smoke

- [ ] Install from `aptoria-1.1.21.zip` using the documented PowerShell template.
- [ ] Run `php artisan optimize:clear`.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan aptoria:health`.
- [ ] Run `php artisan test`.

## v1.1.21 – Risk Acceptance Ledger Pass

1. Open a finding and record an accepted risk with expiry date, reason, business justification, mitigation note, evidence requirement, release scope and expiry action.
2. Confirm the finding status changes to Accepted risk and the legacy accepted risk expiry/note fields are populated.
3. Open Project → Risk Ledger and confirm the accepted risk appears in the ledger.
4. Create one accepted risk without expiry and confirm it appears under Without expiry.
5. Create one expired accepted risk and confirm it appears under Expired.
6. Open Release Readiness and confirm the Risk Acceptance Ledger Summary appears.
7. Confirm expired accepted risks create a release blocker.
8. Confirm accepted risks without expiry and expiring soon create warnings.
9. Export a Full QA Report and confirm Risk Acceptance Ledger Summary appears.
10. Switch English/Hungarian UI and confirm the new labels are translated.
