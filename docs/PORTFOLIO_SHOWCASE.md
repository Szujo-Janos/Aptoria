# Aptoria Portfolio Showcase

Current version: **v1.0.51**

Aptoria is a source-available, self-hosted Laravel application for API QA evidence, safe endpoint scanning, regression monitoring, release readiness review and lightweight security checks.

This document is written for public portfolio presentation. It should not include real credentials, private customer data, production URLs or confidential client screenshots.

## One-sentence pitch

Aptoria helps QA engineers collect endpoint evidence, compare API changes, track release blockers and keep release readiness decisions in one self-hosted workspace.

## Problem statement

Small QA teams often keep API evidence across separate tools: Postman collections, spreadsheets, Markdown notes, screenshots, issue trackers and manual release checklists. This makes it difficult to answer simple release questions quickly:

- Which endpoints changed?
- Which checks failed?
- Which findings are still open?
- Which release gate items block the release?
- What evidence can be exported for review?

Aptoria consolidates these signals into a local Laravel application.

## Target users

- Manual QA engineers who need structured API evidence.
- QA leads who need release readiness summaries.
- Developers who want lightweight endpoint visibility before release.
- Freelancers who need a self-hosted QA reporting workspace.
- Small teams that are not ready for heavy enterprise test management platforms.

## Core workflow

1. Create a project.
2. Add environments and endpoints manually or through import.
3. Run safe GET/HEAD endpoint scans.
4. Add assertions and capture failures.
5. Save snapshots and compare changes.
6. Register findings and evidence.
7. Track QA tasks and release checkpoints.
8. Generate release readiness and QA evidence reports.

## Feature highlights

### Endpoint inventory

Project-based endpoint management with method, path, risk and metadata fields.

### Safe scanning

GET/HEAD-only scanning workflow with private/internal target guardrails and response metadata capture.

### Assertions and regression checks

Status, timing, size, HTTPS/header and JSON/body assertions with stored results and regression comparison support.

### Findings and evidence

A lightweight evidence center for issue severity, reproduction details, attachments/links and release impact notes.

### Release readiness

Release gate decisions can combine scan results, findings, coverage, contract checks and manual QA status.

### QA calendar

Calendar-based QA operations planning for release checkpoints, retests, maintenance windows and alert follow-ups.

### Reports and exports

Markdown reports and ZIP/CSV/JSON exports provide reviewable release evidence.

## Suggested screenshots

Store public-safe screenshots here:

```text
docs/assets/screenshots/dashboard.png
docs/assets/screenshots/project-details.png
docs/assets/screenshots/reports.png
docs/assets/screenshots/release-readiness.png
docs/assets/screenshots/qa-evidence.png
docs/assets/screenshots/calendar-preview.png
```

Screenshot rules:

- Use demo/sample data only.
- Do not show private URLs, tokens, customer names or real credentials.
- Prefer English UI for public portfolio screenshots.
- Keep browser tabs and desktop notifications out of screenshots.

## Public repository positioning

Use wording like this:

```text
Source-available self-hosted API QA, security review and regression monitoring application built with Laravel.
```

Avoid calling it an enterprise security scanner or an open-source product.

## Suggested README badges

Add badges only after the GitHub Actions workflow has run successfully:

```md
![Aptoria Public QA Gate](https://github.com/Szujo-Janos/aptoria/actions/workflows/php.yml/badge.svg)
```

## Demo narrative

A good public demo story:

1. Show the dashboard as the command center.
2. Open one sample project.
3. Show endpoint inventory and safe scan history.
4. Open failed assertions or findings.
5. Show release readiness status.
6. Export or preview a report.

## What not to show publicly

- Production API base URLs.
- Bearer tokens, API keys or webhook secrets.
- Real customer project names.
- Private security findings from real systems.
- Local `.env` or SQLite database files.

## Ownership note

Aptoria is designed and maintained by János Szujó. The repository is source-available for review, portfolio presentation and non-commercial local evaluation. It is not an open-source license grant.
