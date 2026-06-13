# Aptoria v1.1.29 System Audit – Workflow Consolidation & Permission Hardening Pass

Aptoria v1.1.29 is a consolidation and hardening release. It does not add another large isolated module; it connects the existing evidence-first release workflow and closes permission/audit gaps identified during system review.

## Main changes

- New Project → Release Workflow page.
- Client Audit Portal backend permission enforcement for evidence package downloads.
- Client Audit Portal acknowledgement permission enforcement by acknowledgement type.
- Audit observer coverage for `RiskAcceptance`, `ReleaseDecision` and `EndpointBehaviorLink`.
- Project navigation active-state coverage for QA Cockpit, Release Workflow, Blind Spots, Contract Reality, API Behavior, Evidence Graph, Risk Ledger and Release Decisions.
- Feature tests for workflow consolidation and permission hardening.

## Release evidence impact

The release workflow now presents the intended operating path:

1. QA Cockpit triage
2. Blind Spots
3. Release Readiness
4. Release Gate
5. Release Decision Room
6. Report Approval
7. Client Portal handoff

This reduces duplicated-feeling navigation and makes the evidence-first release decision process more explicit.

## Security / permission notes

Client portal UI visibility is no longer the only protection for evidence package and acknowledgement workflows. Direct requests are now checked server-side against the portal permissions.
