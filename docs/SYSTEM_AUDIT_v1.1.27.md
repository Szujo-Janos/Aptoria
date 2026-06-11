# Aptoria v1.1.27 System Audit – Client Audit Portal Pass

## Scope

v1.1.27 adds a limited client/reviewer handoff portal for evidence-first release decision review. The feature is token-based, project-scoped and read-only by default.

## Added objects

- `client_portal_accesses`
- `client_portal_acknowledgements`
- `ClientPortalAccess`
- `ClientPortalAcknowledgement`
- `ClientAuditPortalService`
- `ClientAuditPortalController`

## Access model

Portal links are scoped to exactly one project. A public token can view only approved report versions, release decisions, accepted risks, finding summaries and evidence package exports for that project. Admin-only modules such as settings, auth profiles, environment secrets, debug details and unrelated projects are not exposed.

## Roles

- Client viewer: read-only handoff view.
- Client approver: read-only view plus report / release / accepted risk acknowledgements.
- External reviewer: read-only review view with report acknowledgement capability.

## Release evidence value

The client portal closes the handoff gap between internal QA evidence and external release decision review. It provides a controlled way to show what was approved, what risks were accepted, which release decision was made, and which evidence package supports the handoff.

## Security notes

- Tokens should be shared only with intended reviewers.
- Expiry dates should be used for client handoff links.
- Revoked links become unavailable.
- Only approved report versions are exposed.
- Project isolation is enforced on report and release decision export routes.
