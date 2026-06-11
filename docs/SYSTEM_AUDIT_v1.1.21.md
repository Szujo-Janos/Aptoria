# Aptoria v1.1.21 System Audit

Release: **Risk Acceptance Ledger Pass**

## Scope

Aptoria now treats Accepted risk as an audit object, not only a finding status. The release adds a dedicated risk acceptance ledger with expiry, justification, mitigation and release-scope fields.

## Implemented

- New `risk_acceptances` table.
- New `RiskAcceptance` model.
- New `RiskAcceptanceLedgerService`.
- New project-level Risk Ledger page.
- Finding detail risk acceptance form.
- Automatic sync to legacy finding accepted risk fields.
- Release Readiness blocker/warning/score integration.
- Full QA Report Builder and standard report export summary.
- English/Hungarian localization.
- Feature tests for creation, filters and release/report impact.

## Release safety

- Expired active accepted risks are release blockers.
- Accepted risks without expiry are warnings.
- Expiring accepted risks are warnings.
- Renewing an accepted risk marks the previous active record as renewed.

## Exclusions

- No Jira/Linear-style generic task management was added.
- Existing finding accepted risk fields remain for backward compatibility.
