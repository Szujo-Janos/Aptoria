# Changelog

Aptoria release history for the `0.0.x` evidence-first rebuild.

| Public line | Latest release | Legacy line |
| --- | --- | --- |
| `0.0.x` | `v0.0.53` | `v1.1.34` archived / replaced |

> [!NOTE]
> Releases are listed newest first. Hotfix entries are kept next to the release they stabilize.

> [!IMPORTANT]
> The `0.0.x` line is a fresh evidence-first rebuild. It is not an in-place database upgrade from the legacy `1.1.34` line.

## Release index

- [v0.0.53 - Release Gate Report & Decision Package](#v0053---release-gate-report-decision-package)
- [v0.0.52 - QA Cockpit / Coverage / Blind Spot Foundation](#v0052---qa-cockpit-coverage-blind-spot-foundation)
- [v0.0.51 - UI & Workflow Stabilization Pass](#v0051---ui-workflow-stabilization-pass)
- [v0.0.50 Hotfix - Table Auto Sizing & Auth Logo Alignment](#v0050-hotfix---table-auto-sizing-auth-logo-alignment)
- [v0.0.50 - Release Gate Workflow Foundation](#v0050---release-gate-workflow-foundation)
- [v0.0.49 Hotfix - Native Test Modal Form Workflow](#v0049-hotfix---native-test-modal-form-workflow)
- [v0.0.49 - Native Test Evidence Model](#v0049---native-test-evidence-model)
- [v0.0.48 - Import Adapter Layer Foundation](#v0048---import-adapter-layer-foundation)
- [v0.0.47 Hotfix - Evidence Intake Form Consistency](#v0047-hotfix---evidence-intake-form-consistency)
- [v0.0.47 - Evidence Repository Foundation](#v0047---evidence-repository-foundation)
- [v0.0.46 Hotfix - Semantic Access Icon Pass](#v0046-hotfix---semantic-access-icon-pass)
- [v0.0.46 Hotfix - User Onboarding for Project Access](#v0046-hotfix---user-onboarding-for-project-access)

---

## v0.0.53 - Release Gate Report & Decision Package

- Added `ReleaseGateDecisionPackageService` to turn a release gate into a fixed decision package instead of a loose workflow screen.
- Added release-gate-linked report versions through `report_versions.release_gate_id` for traceability from Reports back to the exact gate.
- Added a Release Gate Decision Package panel on gate detail pages with report generation and HTML/PDF/JSON/ZIP exports.
- Decision package reports include project context, gate status, final decision, score, blocker/warning counts, verified evidence, gate items, source state and gate timeline.
- Added formatted PDF export for release gate decision packages using the same branded report renderer family as Evidence Pack PDFs.
- Added dependency-free ZIP export containing README, Markdown report, standardized HTML, formatted PDF, structured JSON and checksum manifest.
- Package report versions can be reviewed, approved and delivered through the existing Reports / Client Portal workflow after generation.
- Added audit and release gate timeline events for decision package creation.
- Added EN/HU translations, documentation and feature coverage for the decision package foundation.

## v0.0.52 - QA Cockpit / Coverage / Blind Spot Foundation

- Added the QA Cockpit as a project-scoped evidence quality command view.
- Added `QaCockpitService` to calculate QA confidence score, scan/test/evidence coverage, verified evidence ratio and blocker health from existing Aptoria data.
- Added endpoint-level coverage matrix showing safe scan proof, quick/native test proof, verified evidence, findings and per-endpoint coverage score.
- Added blind spot detection for missing endpoint inventory, missing scan/test/evidence proof, unverified evidence, open high/critical findings, failed native test runs, missing readiness/gate and missing fixed exports.
- Added QA Cockpit sidebar/topbar navigation with semantic `scan-search` icon and project access permission integration.
- Added documentation and feature coverage for the cockpit foundation.

## v0.0.51 - UI & Workflow Stabilization Pass

- Stabilized the current UI surface before adding more large feature modules.
- Added shared scrollable modal behavior for long Aptoria form dialogs so modal headers/footers remain usable while the body scrolls.
- Reinforced form-section and workflow-section styling for consistent modal/page forms across Native Test, Release Gate and future modules.
- Updated project workspace module metadata to point implemented modules to real project routes instead of placeholder pages.
- Added QA Cockpit to workspace navigation and fixed topbar snapshot navigation to the real snapshot route.
- Added documentation for the table/form/modal/workflow stabilization rules.

## v0.0.50 Hotfix - Table Auto Sizing & Auth Logo Alignment

- Replaced the old fixed/equal resource table column fallback with content-driven auto column sizing across Aptoria resource tables.
- Kept action/status columns compact and nowrap while allowing long descriptive cells to wrap or truncate safely inside the panel.
- Fixed the Release Gate item table so the Review action button is no longer squeezed by a fixed action column.
- Switched DataTables auto-width calculation back on so initialized tables size columns from rendered content.
- Centered the auth/login logo with a dedicated auth brand wrapper and explicit auto margins.
- Added documentation for the new table sizing and auth logo rules.

## v0.0.50 - Release Gate Workflow Foundation

- Added release gate workflow tables: `release_gates`, `release_gate_items` and `release_gate_events`.
- Added `ReleaseGateWorkflowService` to freeze readiness checks, evidence repository state, native test state and open high/critical finding risk into reviewable release gates.
- Added Release Gates UI with dashboard metrics, gate list, gate detail, item review workflow and final go / conditional go / no-go decision modal.
- Gate items preserve the automated state while allowing reviewer manual state and notes for transparent release decisions.
- A plain Go decision is blocked while effective blocker items remain; reviewers must clear/waive blockers or use Conditional go with a note.
- Added release gate project permissions, sidebar/topbar navigation, EN/HU translations, documentation and feature coverage.

## v0.0.49 Hotfix - Native Test Modal Form Workflow

- Restored the Native Test Evidence forms to the expected Aptoria modal workflow for suite creation, case creation and test run recording.
- Added scrollable XL modal shells for long native test forms so large procedure/result/finding sections stay usable without leaving the current context.
- Kept the full form standard inside the modals: sectioned layout, labels, placeholders, help text, validation feedback and save/cancel actions.
- Added validation-error modal reopening through `_native_test_modal` so failed submissions return to the same modal instead of leaving the user on a dead state.
- Added generic scrollable form modal CSS for future long Aptoria modal forms.

## v0.0.49 - Native Test Evidence Model

- Added native `test_suites`, `test_cases` and `test_runs` domain tables so Aptoria can manage reusable QA procedures without depending on Postman/Newman/Jira.
- Added a Native Test Evidence UI with test suite list, suite detail, case detail and dedicated standards-compliant create/record forms.
- Recording a native test run now automatically creates `test_result` Evidence Repository proof with checksum and lifecycle history.
- Failed native test runs can optionally create linked findings, preserving both test evidence and defect context.
- Added native links from `finding_evidence` back to `test_case_id` and `test_run_id` for release traceability.
- Added `tests.view` and `tests.manage` project permissions and sidebar navigation with semantic test icons.
- Added EN/HU translations, documentation and icon renderer coverage for Native Test Evidence.

## v0.0.48 - Import Adapter Layer Foundation

- Added the first explicit Import Adapter Layer so external tools feed Aptoria instead of being cloned inside it.
- Added OpenAPI JSON adapter support that normalizes contract operations into endpoints, response assertions and contract evidence.
- Added Generic QA CSV adapter support for manual QA/test rows, defects and evidence sheets.
- Generic failing test-result rows now create both a `test_case` finding and `test_result` evidence.
- Import evidence creation now goes through the Evidence Repository checksum/lifecycle path instead of a plain first-or-create record only.
- Replaced the large Import Center modal with a dedicated, scrollable, standards-compliant import intake page.
- Added adapter cards with semantic source icons for Postman, Newman, Jira CSV/JSON, OpenAPI, QA CSV and HAR.
- Updated Import Center tables and details with source/entity icons rather than repeated generic import marks.
- Added source samples, EN/HU translations, documentation and feature coverage for OpenAPI and QA CSV adapters.

## v0.0.47 Hotfix - Evidence Intake Form Consistency

- Replaced the oversized Add Evidence modal with a dedicated scrollable Evidence intake page because the form is a complex editor with request/response excerpts and repository review context.
- Reworked the Evidence intake form into the required Aptoria structure: panel, description, grouped form sections, fields, save/cancel actions.
- Added labels, placeholders, help text and validation feedback slots for every Evidence intake field in both English and Hungarian.
- Added form-section styling aligned with the existing Aptoria card/panel UI, with responsive and dark/light-compatible behavior.
- Added regression coverage to prevent the non-scrollable modal from returning for Evidence creation.

## v0.0.47 - Evidence Repository Foundation

- Strengthened the Evidence Center into a project-scoped repository with repository status, integrity status, checksum algorithm, reviewer/archive metadata and repository notes.
- Added deterministic SHA-256 checksum handling through `EvidenceRepositoryService` so captured QA proof can be checked for silent content changes.
- Added `evidence_lifecycle_events` with created, verified, archived and restored events for per-evidence audit history.
- Changed legacy evidence delete behavior to archive instead of hard-delete so release evidence is not lost during team workflows.
- Added Evidence detail screen with checksum panel, linked objects panel, lifecycle timeline and verify/archive/restore actions.
- Updated Evidence Center UI with repository metrics, assurance panel, filters, semantic icons and Actions dropdowns.
- Added evidence review permission so Reviewer and Release Approver roles can verify evidence without full evidence management rights.
- Updated Evidence Pack manifests and Markdown output with verified/archived evidence counts and evidence checksums.
- Added EN/HU translations, documentation and regression coverage for the repository foundation.

## v0.0.46 Hotfix - Semantic Access Icon Pass

- Reworked the v0.0.46 Users and Project Access icon usage so global account administration, project access control, project roles, password status and membership actions no longer reuse the same generic people icon.
- Added semantic role icons for project admin, QA engineer, reviewer, release approver and read-only viewer cards and table rows.
- Updated sidebar, topbar, project actions and Program Settings entry icons to distinguish global Users from project-scoped access control.
- Expanded the local Aptoria icon renderer with the missing Lucide/Tabler-compatible symbols used by the app, preventing blank/fallback icons on the new access screens and existing workflow screens.
- Added an icon availability audit to the hotfix QA so every static and service-driven icon used by the views has a renderer definition.

## v0.0.46 Hotfix - User Onboarding for Project Access

- Added a global Users page for system admins with account list, create user modal, edit user modal and temporary password reset action.
- Added Project Access flow to create a new local Aptoria user and immediately add that user to the current project with a project role.
- New user accounts are created with `role=user`, `password_change_required=true` and a one-time temporary password shown only in the current session.
- Added EN/HU translations, sidebar/topbar navigation entry and Program Settings link for user onboarding.
- Added audit events for user creation, user updates and temporary password resets.
- Added regression coverage for system-admin user creation and project-admin create-user-and-add flow.

## v0.0.46 - Project Access & Membership Foundation

- Added a project-scoped membership model with project admin, QA engineer, reviewer, release approver and read-only viewer roles.
- Added the `project_memberships` migration with owner backfill so existing project owners become locked project admins.
- Added `ProjectAccessService` and route-level project access middleware to protect project pages and nested project resources.
- Scoped project lists, dashboard project switching and current-project context to the projects the signed-in user can actually access.
- Added a Project Access screen with a standardized Aptoria card/table/actions UI and EN/HU translations.
- Added member add/update/remove flows for existing Aptoria users, with owner membership locked against accidental removal.
- Added access-aware project action visibility in the Projects table, Project detail page, sidebar and user menu.
- Added audit events for project member add/update/remove actions.
- Added regression coverage for owner membership creation, non-member denial, read-only restrictions and project admin member assignment.

## v0.0.45 - Report & Evidence Export Standardization

### v0.0.45 Hotfix - Evidence PDF Logo Fix

- Fixed the Evidence Pack PDF header so it renders the real Aptoria logo instead of the temporary `APT` placeholder mark.
- Added a PDF-safe logo raster derivative generated from the official `logo-color.svg` asset for the dependency-free PDF renderer.
- Updated Evidence Pack PDF regression coverage to verify the embedded image stream and prevent the placeholder mark from returning.

### v0.0.45 Hotfix - Evidence ZIP/PDF Delivery Fix

- Fixed Evidence Pack ZIP downloads so they always return a real `.zip` archive instead of falling back to Markdown when `ZipArchive` is unavailable.
- Added a dependency-free stored ZIP writer for local XAMPP environments without the PHP zip extension.
- Reworked Evidence Pack PDF generation from plain text output into a visibly formatted document with branded header, metadata table, summary cards, section headings, bordered tables and footer checksum.
- Added regression coverage for real ZIP output and formatted PDF markers.

- Standardized Evidence Pack HTML downloads through the shared `ReportVisualStandardService`.
- Evidence Pack HTML exports now use the mandatory `data-aptoria-report-standard="report-visual-standard-v1.1"` shell with header, metadata table, KPI summary strip, evidence notice, numbered sections and footer.
- Added standardized Evidence Pack PDF downloads with fixed report-standard metadata, checksum, project context, selected sections and evidence summary.
- Updated Evidence Pack ZIP generation to include the standardized `report.html` and `report.pdf` alongside README, manifest and checksum files.
- Refreshed the Evidence Pack detail screen with a dedicated standardized export panel and HTML/PDF/ZIP actions.
- Added EN/HU translation keys for standardized Evidence Pack export labels, status labels and evidence-summary copy.
- Added regression coverage to verify Evidence Pack HTML/PDF exports use the shared report standard.

## v0.0.44 - Standalone Security & First-run Hardening

- Added a shared password policy for setup-created admins and profile password changes.
- First-login password change now blocks reusing the current/default password and other obvious/default passwords.
- Added mixed-case, number, symbol and 12-character password requirements to the profile password modal with UI policy hints.
- Added login throttling for failed authentication attempts.
- Added global security headers: frame blocking, nosniff, referrer policy, permissions policy and CSP report-only.
- Added authenticated session inactivity timeout middleware with a configurable Program Settings value.
- Added a first-run security hardening panel to the setup wizard, aligned with the existing Aptoria setup UI.
- Hardened `.env.example` defaults by disabling debug output and lowering log verbosity.
- Added regression coverage for password hardening, security headers and session timeout setting persistence.

## v0.0.43 - Report Visual Standard Foundation

### v0.0.43 Hotfix - Desktop-only Shell Guard

- Removed the sidebar logo image; sidebar branding now keeps text only.
- Removed the topbar hamburger/collapse button so the sidebar is not intentionally hidden on supported desktop widths.
- Added a global desktop-only white-screen overlay for widths below XXL / 1400 px.
- Added EN/HU translation keys for the desktop-only workspace message.
- Added regression coverage for sidebar branding, topbar controls and the desktop-only guard.

### v0.0.43 Hotfix - Report Visual Polish Pass

- Replaced the placeholder `A` report mark with the real Aptoria logo embedded into exported HTML as a standalone base64 image.
- Fixed unresolved translation-key leakage in report metadata, including `messages.projects.project`.
- Reworked the generated report body into a professional fixed structure: Executive Summary, Evidence Summary, Findings & Risk, Release Decision, optional Approval Sign-off and Technical Appendix.
- Demoted stored inner report headings so exports no longer show a second oversized document title below the header.
- Updated the report standard marker to `report-visual-standard-v1.1` and expanded regression coverage for logo, translation and structure checks.

- Added `ReportVisualStandardService` as the shared HTML export wrapper for report versions.
- Standardized report exports around the professional report layout: header, meta table, KPI summary strip, notice block, numbered sections and footer.
- Internal report downloads and public Client Portal report downloads now use the same HTML visual standard.
- Added `docs/REPORT_VISUAL_STANDARD.md` as the mandatory future rule for every report/export.
- Added QA checklist coverage for report visual consistency, print preview and public portal downloads.

## v0.0.42 - Finding Deduplication & Merge Workflow

### v0.0.42 Hotfix - Release Readiness Simulation Method Fix

- Fixed Rule Builder simulation form method handling when the save form contains Laravel method spoofing.
- The simulation endpoint now accepts both POST and PUT so the preview button cannot trigger a MethodNotAllowed error from the shared rules form.
- Added regression coverage for the method-spoofed simulation request.

- Added finding duplicate candidate detection.
- Added merge workflow that moves evidence to the primary finding.
- Duplicate findings are retained as merged trace records.
- Added candidate score, merge notes, dismiss workflow and audit events.
- Findings page now links to the deduplication workflow.
- Cumulatively includes v0.0.40 profiles/simulation and v0.0.41 evidence pack exports.
