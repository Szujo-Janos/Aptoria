# Aptoria product positioning

Aptoria should not compete with Jira or Postman directly.

## Problems in current QA/API tooling

### Postman / Newman

Good for sending requests and running collections, but weak as a long-term release evidence ledger.

Missing layer:

- endpoint-level QA coverage view
- release-blocking evidence summary
- finding and retest lifecycle
- accepted-risk ledger
- client-ready decision package

### Jira / Linear

Good for tickets and workflow, but weak for API evidence structure.

Missing layer:

- scan result to finding relationship
- endpoint inventory coverage
- release readiness scoring
- proof of what was actually tested
- audit-ready QA package

### OpenAPI

Good for contract description, but it does not prove runtime behaviour.

Missing layer:

- contract vs reality comparison
- undocumented implemented endpoints
- documented but untested endpoints
- runtime response proof

### Monitoring tools

Good for uptime and alerts, but not enough for QA release decisions.

Missing layer:

- release-specific blockers/warnings
- QA evidence chain
- retest status
- evidence versioning

## Aptoria's role

Aptoria is the evidence and release decision layer around existing QA/API tools.

Core sentence:

> Endpoint inventoryból biztonságos scan evidence készül, ebből assertion, snapshot, finding, evidence és release readiness épül, majd ezekből auditálható release döntési csomag készül.
