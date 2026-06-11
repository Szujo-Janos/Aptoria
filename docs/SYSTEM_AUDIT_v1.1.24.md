# Aptoria v1.1.24 System Audit

## Release

Aptoria v1.1.24 – Evidence Graph Pass

## Scope

This pass adds an audit-oriented evidence graph layer over existing Aptoria project data. It does not execute new HTTP requests and does not store secrets. The graph is calculated from already stored endpoints, scan results, assertions, findings, finding evidence, release gates, risk acceptances, blind spots and release decision packages.

## Added capabilities

- Project-level **Evidence Graph** page.
- Endpoint-level **Endpoint Evidence Map**.
- Finding-level **Finding Evidence Chain**.
- Release-level **Release Evidence Graph**.
- Missing evidence link detection.
- Full QA Report **Evidence Graph Summary** section.
- English and Hungarian translations.
- Feature test coverage for graph summaries, UI pages and report integration.

## Safety notes

- No automatic scan is executed by the graph service.
- No destructive endpoint is called.
- Existing evidence is read and summarized only.
- Release ZIP exclusions remain unchanged.
