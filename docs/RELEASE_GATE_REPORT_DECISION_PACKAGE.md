# Aptoria v0.0.53 – Release Gate Report & Decision Package

## Purpose

A release gate is useful only if its decision can be frozen, exported, reviewed and handed off later. v0.0.53 adds the first release gate decision package layer.

The goal is not to turn Aptoria into Jira, Postman or Newman. Those tools can still provide tickets, API runs or imported QA data. Aptoria now packages the final release gate state into a fixed evidence-backed decision artifact.

## What the package contains

A decision package captures:

- project name and base URL
- release gate title, version and target environment
- gate profile
- automated decision
- final human decision
- release score and grade
- blocker / warning / passed item counts
- verified evidence count
- native test run state
- open high/critical finding state
- all gate items with automated, manual and effective state
- reviewer notes
- gate lifecycle timeline
- checksum-backed report version data

## Storage model

The generated package is stored as a normal `ReportVersion` record with:

- `type = release_decision`
- `release_gate_id`
- `release_readiness_run_id`
- `content_markdown`
- `content_html`
- `data_json`
- `checksum`

This means the package can later use the existing report review, approval and client delivery workflow.

## Exports

Release gate detail pages support:

- HTML
- PDF
- JSON
- ZIP
- Markdown

ZIP files contain:

```text
README.md
release-gate-report.md
release-gate-report.html
release-gate-report.pdf
decision-package.json
checksum.sha256
```

The ZIP writer is dependency-free and does not require PHP `ZipArchive`, matching the Evidence Pack export behavior.

## UI rules

The package creation form follows the Aptoria modal form rules:

- modal workflow
- scrollable body
- fixed header/footer
- labeled fields
- placeholders/help text
- validation feedback
- semantic icons

## QA checklist

- Create or open a release gate.
- Finalize it as Go, Conditional go or No-go.
- Open the gate detail page.
- Use **Create package report**.
- Confirm that a ReportVersion opens.
- Download HTML and verify the report standard shell.
- Download PDF and verify branded formatted content.
- Download JSON and verify gate items/source state are present.
- Download ZIP and verify all expected files exist.
- Open the report version and approve it.
- Create a client portal delivery link from the approved report.
