# Aptoria v1.1.22 System Audit

## Release

Aptoria v1.1.22 – Release Decision Room Pass

## Scope

This release turns Release Readiness from a score/report into a saved release decision workflow. It adds a project-level Release Decision Room and a persisted Release Decision Package for Go / No-Go / Conditional Go / Pending evidence / Blocked decisions.

## Added components

- `release_decisions` database table.
- `App\Models\ReleaseDecision`.
- `App\Services\ReleaseDecisions\ReleaseDecisionRoomService`.
- `App\Http\Controllers\ReleaseDecisionController`.
- Project navigation entry: **Project → Release Decision Room**.
- Decision package exports: Markdown, HTML, PDF and JSON.

## Decision package contents

- decision status, owner, timestamp and notes;
- release readiness score, grade, blockers and warnings;
- latest scan / snapshot / compare / contract validation / release gate IDs;
- finding state snapshot;
- blind spot summary and top blind spots;
- accepted risk ledger summary and captured accepted risks;
- score component snapshot;
- package checksum.

## Report integration

- Release Readiness page shows the latest release decision package.
- Release Readiness markdown includes latest decision metadata.
- Full QA Report Builder includes a Release Decision section when release readiness is included.

## Safety / packaging

Release ZIP excludes runtime/private files:

- `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

`public/assets/aptoria-ui/vendor` remains included.

## QA focus

Run migrations, finalize at least one decision package, export all formats, and confirm the package can be used as audit evidence for the release decision.
