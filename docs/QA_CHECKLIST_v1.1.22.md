# Aptoria v1.1.22 QA Checklist

Release: **v1.1.22 - Release Decision Room Pass**
ZIP: `aptoria-1.1.22.zip`

## Install / smoke

- [ ] Install from `aptoria-1.1.22.zip` using the documented PowerShell template.
- [ ] Run `php artisan optimize:clear`.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan aptoria:health`.
- [ ] Run `php artisan test`.

## v1.1.22 – Release Decision Room Pass

1. Open a project and go to **Project → Release Decision Room**.
2. Confirm the hero shows recommended decision, release score, blockers, warnings, blind spots and accepted risks.
3. Confirm the evidence chain shows latest scan, snapshot, compare, contract validation and release gate IDs.
4. Finalize a **Go**, **No-Go**, **Conditional Go**, **Pending evidence** or **Blocked** decision with notes.
5. Confirm a Release Decision Package detail page opens after saving.
6. Confirm the package contains decision owner, timestamp, score, blockers, warnings, blind spots, accepted risks and checksum.
7. Export the package as Markdown, HTML, PDF and JSON.
8. Open Project → Release Readiness and confirm the latest release decision panel is visible.
9. Export a Full QA Report and confirm the Release Decision section is included.
10. Confirm English and Hungarian labels are available for the new Decision Room UI.

## Regression checks kept from recent releases

- [ ] Project → Blind Spots still detects scan/assertion/auth/retest/risk/report evidence gaps.
- [ ] Project → Findings still supports owner, due date, verification status, retest result and comments.
- [ ] Project → Risk Ledger still shows active, missing expiry, expiring soon and expired accepted risks.
- [ ] Release Readiness still includes Blind Spot Summary and Risk Acceptance Ledger Summary.
- [ ] Full QA Report Builder still exports executive and technical reports.
- [ ] Sidebar project navigation remains grouped and contains Decision Room under Release & reports.
