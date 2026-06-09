# Aptoria v1.0.94 – System Audit

## Release

**Version:** v1.0.94  
**Title:** Project Onboarding Wizard Pass  
**Base:** v1.0.93 – System Health Diagnostics Pass

---

## Scope

This release completes the originally selected first roadmap item: **Project onboarding wizard**.

The goal is to prevent new projects from staying empty or half-configured after creation. The guided flow now creates the first usable QA workspace and immediately prepares first-run evidence.

---

## Implemented changes

- Extended the guided project wizard beyond project/environment/auth/endpoint setup.
- Added onboarding actions for:
  - first safe scan;
  - first baseline snapshot;
  - first full project report readiness check.
- Added completion page at `/projects/{project}/wizard/complete`.
- The completion page links to:
  - project detail;
  - first scan;
  - first snapshot;
  - full project Markdown report;
  - full project HTML report;
  - full project PDF report.
- Added validation so the wizard cannot finish with an empty/invalid endpoint payload.
- The wizard now stores the selected environment and auth profile as project scan defaults.
- Production environments are not auto-scanned from the wizard.
- Updated English and Hungarian translations.
- Added feature test coverage for:
  - project creation;
  - endpoint import;
  - initial scan;
  - initial snapshot;
  - completion page;
  - report links;
  - invalid empty endpoint payload rejection.

---

## Safety notes

The automatic onboarding scan uses the normal safe scan engine. Destructive HTTP methods remain blocked by existing scan safety settings.

If the selected environment is marked as production, the wizard creates the project but skips the automatic first scan. The user must start a production scan later from the normal scan screen with explicit confirmation.

---

## QA focus

- Guided wizard should no longer leave an empty project.
- A non-production guided project should end with at least:
  - one project;
  - one environment;
  - one auth profile;
  - one endpoint;
  - one scan run;
  - one snapshot;
  - ready report export links.
- Empty endpoint payloads must be rejected and rolled back.
- Existing project, scan, snapshot, report, system health and database maintenance functions must remain unchanged.

---

## Release hygiene

The release ZIP must not contain local runtime state:

- no root `vendor/`;
- no `.env`;
- no `database/database.sqlite`;
- no `storage/app/installed.lock`;
- no `storage/app/setup-token.txt`.

The frontend vendor assets under `public/assets/aptoria-ui/vendor` remain included.
