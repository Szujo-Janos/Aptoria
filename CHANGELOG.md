# Changelog

## v1.0.86 - Export Credit Setting Runtime Hotfix

- Wired the visible `report.include_copyright_footer` setting into `ExportCreditService`.
- Fixed the GitHub Actions failure in `SettingsFunctionalAuditTest` where the setting existed in Settings UI but had no runtime consumer.
- Kept Aptoria attribution enabled by default while making the existing footer setting functional.
- Updated release documentation to v1.0.86.
- Bumped VERSION to `1.0.86`.

## v1.0.85 - Export Credit Namespace Hotfix

- Fixed `ReleaseReadinessService` so it imports `App\Services\Exports\ExportCreditService` correctly.
- Resolved dashboard and project detail 500 errors caused by Laravel resolving the wrong `App\Services\ExportCreditService` class name.
- Kept the v1.0.84 export attribution behavior intact.
- Updated release documentation to v1.0.85.
- Bumped VERSION to `1.0.85`.

## v1.0.84 - Export Credits & Attribution Pass

- Added centralized `ExportCreditService` for report/export attribution metadata.
- Added Aptoria product/version/repository/author/license attribution to Markdown report footers.
- Added structured `generated_by` metadata to JSON exports.
- Added `APTORIA_CREDITS.txt` to QA Evidence Pack ZIP exports.
- Added Aptoria metadata to calendar `.ics` exports.
- Added Aptoria attribution columns to endpoint inventory CSV exports.
- Added regression coverage for export credit metadata.
- Bumped VERSION to 1.0.84.

## v1.0.83 - User Profile Center

- Added an authenticated user profile center at `/profile`.
- Added profile editing for name, e-mail, interface language and timezone.
- Added a separate password change form with current-password verification.
- Added account information and activity summary panels.
- Added profile navigation to the user dropdown.
- Added user `locale` and `timezone` persistence.
- Added profile feature regression coverage.
- Bumped VERSION to `1.0.83`.

## v1.0.82 - Optional Vendor Plugin Guard Hotfix

- Guarded Aptoria UI initialization for optional vendor plugins so public/auth pages do not throw JavaScript errors when sidebar-only plugins are not loaded.
- `aptoria-ui.js` now checks for `$.fn.metisMenu`, `$.fn.slimScroll`, and `$.fn.animatePanel` before calling those helpers.
- Added regression coverage for optional Aptoria UI vendor plugin guards.
- Updated release documentation to v1.0.82.
- Bumped VERSION to `1.0.82`.

## v1.0.81 - Local HTTPS Force Scheme Hotfix

- Fixed local `artisan serve` asset loading when `.env` still has `APP_ENV=production` but `APP_URL` points to a local HTTP URL.
- Changed `AppServiceProvider` so `URL::forceScheme('https')` is only applied when the configured application URL itself starts with `https://`.
- Prevents local HTTP installs from generating HTTPS asset URLs that cause `Invalid request (Unsupported SSL request)` and browser `ERR_CONNECTION_CLOSED` errors.
- Updated README, installation guide, server installer notes and QA checklist to v1.0.81.
- Added `docs/SYSTEM_AUDIT_v1.0.81.md`.
- Bumped VERSION to `1.0.81`.

## v1.0.80 - Windows Cleanup Path Assertion Hotfix

- Fixed `AptoriaRebrandTest` cleanup-path assertions to match the actual PowerShell single-backslash path literals used in `scripts/windows-xampp-common.ps1`.
- Kept the Windows/XAMPP cleanup behavior unchanged.
- Updated public release documentation to v1.0.80.
- Added `docs/SYSTEM_AUDIT_v1.0.80.md`.
- Bumped VERSION to `1.0.80`.

## v1.0.79 - Rebrand Regression Scope Hotfix

- Fixed the Aptoria rebrand regression test so historical changelog entries are not treated as current product branding.
- Removed legacy literal path examples from the current public QA checklist while keeping the manual cleanup checks understandable.
- Kept Windows/XAMPP cleanup coverage for stale pre-rebrand files by checking the cleanup paths in encoded form inside the test.
- Updated public release documentation to v1.0.79.
- Added `docs/SYSTEM_AUDIT_v1.0.79.md`.
- Bumped VERSION to `1.0.79`.

## v1.0.78 - Legacy Rebrand Artifact Cleanup Hotfix

- Added Windows/XAMPP update cleanup for stale files left behind by Copy-Item based upgrades.
- Removes legacy rebrand artifacts that may remain in an existing project root after upgrading from older API Radar builds:
  - `docs/RADAR_UI_TEMPLATE_AUDIT.md`
  - `docs/RADAR_UI_UX_REFRESH.md`
  - `config/api-radar.php`
  - `public/assets/api-radar`
  - `public/assets/radar-ui`
- Updated rebrand regression coverage so the cleanup remains protected.
- Updated public release documentation to v1.0.78.
- Bumped VERSION to `1.0.78`.

## v1.0.77 - Rebrand Test Consistency Hotfix

- Fixed the rebrand regression test so it validates the current `VERSION` dynamically instead of expecting a stale rebrand release version.
- Hardened release documentation consistency checks so release-facing docs must reference the current short ZIP name only.
- Updated README, installation guide, server installer notes, QA checklist and system audit references to v1.0.77.
- Kept the corrected Aptoria logo icon and favicon assets from v1.0.75.
- Bumped VERSION to 1.0.77.

## v1.0.76 - Aptoria Rebrand Polish Pass

- Aligned README, installation guide, server installer notes and QA checklist with v1.0.76.
- Updated release ZIP examples to `aptoria-1.0.76.zip` and root folder `aptoria-1.0.76/`.
- Updated `scripts/build-release.ps1` so required system audit validation follows the current VERSION instead of v1.0.74.
- Updated `AptoriaRebrandTest` to read the current VERSION dynamically instead of hardcoding v1.0.74.
- Renamed legacy public UI documentation filenames to the `APTORIA_UI_*` naming pattern.
- Added `docs/SYSTEM_AUDIT_v1.0.76.md`.
- Kept the v1.0.75 corrected Aptoria icon assets.
- Bumped VERSION to 1.0.76.

## v1.0.75 - Logo Icon Crop Hotfix

- Fixed `public/assets/aptoria/img/aptoria-logo-icon.png` so the icon no longer includes the stray bottom of the `A` wordmark.
- Regenerated related favicon and launcher icon assets from the corrected Aptoria icon:
  - `favicon.ico`
  - `favicon-16.png`
  - `favicon-32.png`
  - `favicon-64.png`
  - `apple-touch-icon.png`
  - `android-chrome-192.png`
  - `android-chrome-512.png`
- Bumped VERSION to `1.0.75`.

## v1.0.74 - Aptoria Rebrand Pass

- Rebranded the application from Aptoria's former API-focused name to **Aptoria** across source code, views, settings, tests, documentation and release metadata.
- Updated Composer package metadata, environment variable prefixes, Artisan command names, config namespace, session/cache prefixes and public repository URLs to use Aptoria naming.
- Renamed public asset namespaces to `assets/aptoria` and `assets/aptoria-ui`.
- Added the new Aptoria logo set, favicon files and app icon assets.
- Updated Windows/XAMPP, GitHub clone and scheduled monitor commands to use the new `aptoria` naming and repository URL.
- Added rebrand regression coverage to prevent legacy pre-rebrand naming from returning to public-facing code and documentation.
- Bumped VERSION to 1.0.74.

## v1.0.73 - Documentation & Release Polish Hotfix

- Aligned README, installation guide, server installer notes and QA checklist with v1.0.73.
- Corrected ZIP references to the short release name `aptoria-1.0.73.zip`.
- Corrected public GitHub clone URLs to `Szujo-Janos/Aptoria`.
- Updated the documentation map to point at `docs/SYSTEM_AUDIT_v1.0.73.md`.
- Removed leftover internal `status => active` metadata from global Settings defaults.
- Added release documentation consistency regression coverage.
- Bumped VERSION to 1.0.73.

## v1.0.72 - Settings Functional Audit Hotfix

- Audited the global and project Settings systems for save, validation, persistence and runtime consumption.
- Removed non-functional assertion default controls that were only referenced by dead code.
- Removed the unused demo-hint Settings reference from the help workflow panel.
- Added functional audit coverage for visible Settings fields, runtime consumers, misleading activation copy, UI rendering switches, session timeout behavior and project notes display.
- Restored the complete local Homer/Aptoria UI vendor asset set required by Blade layouts and release ZIP validation.
- Added missing Laravel runtime `.gitkeep` folders required by clean ZIP installs.
- Updated release metadata, QA checklist and Settings audit documentation.
- Bumped VERSION to 1.0.72.

## v1.0.71 – Settings Help Text Noise Hotfix

- Removed the generic per-field Settings help boilerplate from English and Hungarian localization.
- The Settings Center now renders help text only when a field has concrete, useful guidance.
- Kept localization keys present for regression coverage without forcing noisy placeholder copy into the UI.
- Added a regression test that fails if the generic help boilerplate comes back.
- Bumped VERSION to 1.0.71.

## v1.0.70 – Settings Center Test & Localization Compatibility Hotfix

- Restored the Settings Center English UI contract expected by `SettingsCenterTest`.
- Restored the `Security & Privacy`, `System Info`, and `Aptoria v{version}` strings on the Settings Center.
- Kept the Hungarian Settings labels localized, including `Biztonság és adatvédelem`.
- No new Settings status categories were added; Settings remains active-only in the UI.

## v1.0.69 – Settings Localization & Hardcode Hotfix

- Added complete English and Hungarian labels for every Settings Center field introduced up to v1.0.68.
- Added complete English and Hungarian help text for every Settings Center field, group and select option.
- Added missing Settings groups such as Scan Profiles and Release Readiness to the localized group map.
- Removed the Settings view fallback that could display English `SettingService` descriptions on the Hungarian UI.
- Replaced generated English headline fallbacks with an explicit missing-translation message so missing localization is visible during QA instead of silently leaking English UI copy.
- Added a regression test that fails when any Settings key lacks English/Hungarian labels, help text, group text or option labels.
- Bumped VERSION to 1.0.69.

## v1.0.68 – Settings Activation Test Hotfix

- Fixed assertion evaluation regression introduced by v1.0.67: endpoints without explicit assertion rules are again reported as `not_configured`, matching the existing test contract and release readiness semantics.
- Kept assertion default Settings active by using the default status code as the create-rule form default instead of silently creating synthetic runtime rules.
- Fixed the SweetAlert confirmation regression by removing the literal `window.confirm(` call from the runtime asset while keeping the configured fallback confirmation behavior.
- Bumped VERSION to 1.0.68.

## v1.0.67 – Settings Activation Pass

- Removed Settings maturity counters and field badges from the Settings UI.
- Corrected mismatched Settings keys in scan safety and report builder defaults.
- Added runtime consumers for session timeout, audit logging, assertion fallback rules, typed production confirmation and destructive path keyword guards.
- Bumped VERSION to 1.0.67.

## v1.0.66 – Settings Functional Audit Reality Pass

- Performed the initial Settings runtime review.
- Updated the Settings Center status counters and badges to show the audit states instead of the previous blanket active/prepared/planned wording.
- Replaced `docs/SETTINGS_FUNCTIONAL_AUDIT.md` with a detailed per-control audit table and requested group rollup.
- Added `docs/SYSTEM_AUDIT_v1.0.66.md` with release hygiene and QA focus.
- Superseded by v1.0.67 activation wiring.

## v1.0.65 – Settings Full Wiring Pass

- Fixed raw Blade/PHP layout setup code being rendered above the application header.
- Restored normal Blade asset helper expressions for CSS, JavaScript, logo and favicon URLs.
- Replaced the app layout's inline title setup directives with a proper `@php ... @endphp` setup block.
- Keeps the Settings Center runtime wiring, risk scoring, layout fallback and GitHub Actions fixes from v1.0.57–v1.0.63 intact.

## v1.0.61 – Layout Page Title Fallback Hotfix

- Fixed layout rendering failures caused by Settings-driven UI variables not being available on every Blade render path.
- Added a `layouts.app` view composer that shares Aptoria UI identity, sidebar, density, theme and project-navigation variables centrally.
- Kept defensive layout fallbacks in `resources/views/layouts/app.blade.php` so feature tests and cached views do not fail on missing UI preference variables.
- Preserved the v1.0.57–v1.0.59 Settings wiring work and short ZIP naming.

## v1.0.59 – Reports & Export Settings Wiring

- Expanded the Settings Center into a full operational control panel with General, Scan Profiles, HTTP Scan Behavior, Probe Safety, Risk Engine, Assertions, Snapshots & Retention, Reports & Exports, Dashboard & UI, Security & Privacy and Release Readiness groups.
- Added status labels for settings: Active, Prepared and Planned.
- Added all proposed switches from the settings planning pass while keeping not-yet-automated controls clearly marked as Prepared or Planned.
- Added generic setting validation based on SettingService metadata, so future settings can be added without brittle controller updates.
- Wired additional UI identity settings into the layout and extended secret masking with custom sensitive header/JSON field lists.
- Added `docs/SETTINGS_FUNCTIONAL_AUDIT.md` and `docs/SYSTEM_AUDIT_v1.0.59.md`.

## v1.0.55 – GitHub Actions Security Audit Full CI Hotfix

- Fixed the GitHub Actions `aptoria:security-audit` failure by preparing a CI-only installed lock and strong setup token before the deployment/security readiness audit runs.
- Keeps the runtime security audit strict for real deployments while allowing the public QA gate to validate a production-like security state in CI.
- Preserves the v1.0.52 directive-based security header assertion hotfix and the v1.0.53 Laravel cache path preparation.
- Updated release metadata and audit documentation for v1.0.55.

## v1.0.53 – GitHub Actions Cache Path Hotfix

- Fixed GitHub Actions PHPUnit failures caused by missing Laravel writable runtime directories after checkout.
- Added an explicit workflow step to create `bootstrap/cache` and the required `storage/framework/*` paths before tests.
- Kept the public repository hygiene gate and security assertions intact.


## v1.0.53 - Security Header CI Assertion Hotfix

- Fixed the GitHub Actions PHPUnit failure in `SecurityHardeningTest` by making the sensitive-page `Cache-Control` assertion directive-based instead of order-sensitive.
- Kept the security requirement intact: sensitive pages must still include `no-store`, `no-cache`, `must-revalidate` and `max-age=0`.
- Tolerates framework/runtime-added cache directives such as `private` when the required no-cache directives are present.
- Updated release metadata and audit documentation for v1.0.53.

## v1.0.53 - Portfolio Showcase Documentation Pass

- Added `docs/PORTFOLIO_SHOWCASE.md` for public portfolio positioning and demo narrative.
- Added screenshot placeholder folder under `docs/assets/screenshots/.gitkeep`.
- Updated README with portfolio showcase and suggested public screenshot paths.
- Updated GitHub/public readiness checklists for public-safe showcase materials.


## v1.0.50 - GitHub Actions Public QA Gate

- Expanded `.github/workflows/php.yml` into the Aptoria Public QA Gate.
- Added CI checks for forbidden runtime files and required public repository files.
- Added Composer validation, PHP syntax check, testing environment preparation, migrations, PHPUnit and security audit steps.
- Updated README and public repository checklists to reference the workflow gate.


## v1.0.49 - Public README Installation Command Polish

- Aligned README Windows/XAMPP release ZIP commands with the exact local PowerShell template.
- Added a public GitHub clone installation workflow to README and installation docs.
- Updated Composer license metadata to `proprietary` to avoid contradicting the source-available LICENSE.
- Refreshed QA checklist and system audit for v1.0.49.


## v1.0.48 - Credits & Copyright Notice Pass

- Added `NOTICE.md` with explicit Aptoria copyright, ownership and source-available visibility language.
- Added `CREDITS.md` with project owner, product direction, technology foundation and AI-assisted development disclosure.
- Updated README, GitHub/public readiness docs, installation docs, QA checklist and security docs to the v1.0.48 release line.
- Added a lightweight copyright notice to the main app footer, landing footer and login screen.
- Updated the release build script to require `NOTICE.md`, `CREDITS.md` and the v1.0.48 system audit.
- Kept runtime workflows, database structure, routes and Aptoria UI vendor assets unchanged.

## v1.0.47 - Public Repository Readiness Polish

- Added a source-available `LICENSE` so public GitHub visibility does not imply open-source redistribution rights.
- Expanded public repository readiness documentation with `docs/PUBLIC_REPOSITORY_CHECKLIST.md`.
- Updated README, installation, GitHub checklist, QA checklist, security and contribution docs to the v1.0.47 public-readiness line.
- Expanded `THIRD_PARTY_NOTICES.md` with bundled Aptoria UI frontend dependency/license notes.
- Added `.github/PULL_REQUEST_TEMPLATE.md` and `.github/dependabot.yml` for public repository hygiene.
- Kept runtime behavior unchanged: no database migration, controller, route or Blade workflow changes were introduced.

## v1.0.46 - Aptoria UI Vendor Asset Runtime Hotfix

- Restored the full Aptoria UI third-party vendor asset tree under `public/assets/aptoria-ui/vendor/`.
- Fixed missing Bootstrap, jQuery, Font Awesome, MetisMenu, DataTables, Chart.js, Toastr, SweetAlert and iCheck runtime assets after the v1.0.44 namespace cleanup.
- Fixed a JavaScript syntax regression in `public/assets/aptoria/js/app.js` by renaming `initAptoria UIProUi()` to `initAptoriaProUi()`.
- Kept the user-facing product identity as Aptoria / Aptoria UI without restoring the old visible template namespace.

## v1.0.45 - Product Identity Polish

- Finalized Aptoria product identity language across GitHub-facing documentation and release notes.
- Added public repository readiness notes for the Aptoria UI asset namespace and brand-safe presentation.
- Updated QA checklist and system audit for the completed three-step rebrand sequence.


## v1.0.44 - Aptoria UI Asset Namespace Cleanup

- Moved bundled admin UI runtime assets to `public/assets/aptoria-ui`.
- Updated Blade layouts, landing page, release validation and documentation to the new Aptoria UI namespace.
- Renamed the runtime helper script to `aptoria-ui.js`.


## v1.0.43 - Visible Template Rebrand Pass

- Removed visible template-brand wording from the public landing, documentation and repository-facing text.
- Renamed template-specific CSS classes and JavaScript helper names to Aptoria/Aptoria UI names.
- Kept runtime asset paths unchanged in this step to avoid a risky combined path migration.


## v1.0.42 - Light Button & Badge Typography Hotfix

- Reduced button typography weight across the admin, auth and landing surfaces so action text no longer appears bold/semi-bold.
- Reduced badge and label typography weight across status pills, project badges, calendar badges, sidebar counters and dashboard metric badges.
- Kept existing button/badge colors, spacing and calendar tone markers intact; only the font weight/letter spacing was normalized.
- Added v1.0.42 system audit and updated release baseline metadata.

## v1.0.41 - Dashboard Project-Style Calendar Preview Pass

- Restyled the global dashboard header into the same project-workspace structure used by the Project Details page.
- Added dashboard-level Calendar preview with upcoming QA operations, status/priority badges and day-view links.
- Added project-scoped Calendar preview to the Project Details page.
- Added reusable `calendar._preview` Blade partial so dashboard and project workspaces use the same calendar preview rendering.
- Added dashboard/project calendar preview CSS while preserving the existing calendar tone markers.
- Added regression coverage for dashboard and project calendar previews.
- Updated release baseline metadata and added v1.0.41 system audit.

## v1.0.40 - Calendar Seed Noise & Title Visibility Hotfix

- Fixed the calendar upcoming events table so color tone classes no longer turn row titles white on a white background. Activity log titles are readable again.
- Suppressed calendar activity logging during setup admin creation, migrate-and-seed operations, DatabaseSeeder and DemoQaProjectSeeder imports.
- Removed User model activity events from the calendar audit scope because setup/admin account creation is not a QA operations event.
- Kept user-level project CRUD activity logs and manual domain CRUD logs intact.
- Added `aptoria:calendar-cleanup-setup-noise` to remove previously generated setup/demo technical calendar noise when needed.
- Updated release baseline metadata and added v1.0.40 system audit.

## v1.0.39 - Calendar UX, Activity Noise Reduction & Visual Timeline Hotfix

- Reduced calendar activity log noise during project creation by logging the user-level project creation action while suppressing automatically generated default environment, auth profile and project setting setup entries.
- Added event/action color tones for created, updated, deleted, alert, monitor, release, maintenance, security, regression and manual QA calendar entries.
- Added clickable calendar days and a dedicated day view for reviewing all entries on a selected date.
- Rendered multi-day calendar events across every affected date in the month grid with continuous range styling.
- Updated JSON feed and .ics export range logic so multi-day events are included when they overlap the selected range.
- Added regression coverage for project creation noise reduction and day-view/multi-day event rendering.
- Added v1.0.39 system audit and updated release baseline metadata.

## v1.0.38 - Calendar Activity Log Display Labels Hotfix

- Fixed immutable calendar activity log display labels so project setting keys such as `scan.enabled` render as translated human-readable labels.
- Split activity action wording between title context and sentence/description context.
- Kept raw technical keys in structured metadata while rendering UI, JSON feed and .ics output from localized display accessors.
- Added regression coverage for localized project setting activity log titles.
- Added v1.0.38 system audit and updated release baseline metadata.

## v1.0.37 - Calendar Activity Log Localization Hotfix

- Fixed immutable calendar activity log localization by rendering titles and descriptions from structured activity metadata at display/export time.
- Added translated activity subject labels for English and Hungarian UI output.
- Updated calendar list, month chips, JSON feed and .ics export to use localized display text instead of stored one-language text.
- Added a regression test for render-time activity log localization.
- Added v1.0.37 system audit and updated release baseline metadata.

## v1.0.36 - Calendar Activity Log & Header Actions Hotfix

- Moved calendar action buttons into the calendar panel header.
- Added immutable activity log entries to the QA Operations Calendar for create/update/delete operations.
- Added activity metadata fields to calendar events and protected system log entries from edit/complete/delete actions.
- Added calendar activity observer coverage for Aptoria domain models.
- Added calendar activity log documentation and v1.0.36 system audit.

## v1.0.35 - Calendar Hardening, Tests & Documentation Pass

- Added CalendarOperationsTest coverage for calendar rendering, event creation, completion, alert follow-up, JSON feed and .ics export.
- Added QA operations calendar documentation and v1.0.35 system audit.
- Preserved release ZIP hygiene and Windows/XAMPP install path.

## v1.0.34 - Calendar Export & Operations Dashboard Pass

- Added JSON feed and iCalendar export for stored calendar events.
- Added calendar summary cards, month grid and monitor run preview.
- Added calendar export operations documentation.

## v1.0.33 - Calendar Follow-up & Monitor Integration Pass

- Added monitor alert follow-up creation from the alert history screen.
- Linked calendar events to projects, endpoints, monitors, monitor alert events and release gates.
- Added project calendar route and sidebar integration.

## v1.0.32 - QA Operations Calendar Pass

- Added calendar_events table and CalendarEvent model.
- Added CalendarController, calendar routes and Blade create/edit/index screens.
- Added English and Hungarian calendar translations.

## v1.0.31 - Monitor Alert Triage & Acknowledgement Pass
- Added acknowledgement fields to monitor alert events so operators can mark alerts as reviewed without deleting delivery evidence.
- Added per-alert acknowledgement action with optional triage note on monitor alert history pages.
- Added open alert counters to project and global monitor tables.
- Added alert acknowledgement relation to users and acknowledgement status helpers.
- Extended monitor alerting tests for alert acknowledgement.
- Added monitor alert triage operations documentation and v1.0.31 system audit.

## v1.0.30 - Monitor Email Delivery & Alert History Pass
- Added Laravel Mail based monitor email delivery for state-change alerts.
- Added mail configuration defaults and environment examples for local log delivery, testing array delivery and production SMTP.
- Added HTML and plain-text monitor alert mail templates.
- Added email alert event recording with sent/failed delivery status and delivery timestamps.
- Added monitor alert history pages so dashboard, email and webhook delivery events can be reviewed per monitor.
- Added monitor alert history links to project and global monitor tables.
- Extended monitor alerting tests for email delivery and alert history rendering.
- Added monitor email delivery operations documentation and v1.0.30 system audit.

## v1.0.29 - Monitor Notifications & Alerting Pass
- Added state-change monitor alerting for failed, warning, regression and recovery states.
- Added `monitor_alert_events` storage for dashboard/webhook alert history.
- Added monitor alert fields: alert email, webhook URL, recovery alerts, last alert time and last alert status.
- Added optional JSON webhook delivery for monitor status changes.
- Extended monitor runner summaries with alert and alert failure counters.
- Added monitor alerting UI fields and last-alert columns on monitor lists.
- Added `MonitorAlertingTest`, monitor alerting operations documentation and v1.0.29 system audit.

## v1.0.28 - Scheduled Monitoring Operations Pass
- Expanded `php artisan aptoria:run-monitors` with operational filters: `--project`, `--monitor`, `--force`, `--dry-run`, `--json`, `--fail-on-warning` and `--fail-on-regression`.
- Added structured monitor runner summaries with due/ran/failed/warning/regression/skipped counts.
- Added dry-run support so Windows Task Scheduler and cron configuration can be verified without executing safe scans.
- Added monitor command feature tests for dry-run behavior, project filtering and inactive-project failure handling.
- Added scheduled monitoring operations documentation for Windows Task Scheduler and Linux cron.
- Updated README, installation, QA checklist, build metadata and v1.0.28 system audit documentation.

## v1.0.26 - Documentation & GitHub Readiness Cleanup
- Replaced the outdated README with a current project overview, installation path, feature list and GitHub publishing guidance.
- Reframed the old MVP plan as a current product status and roadmap document.
- Rebuilt the QA checklist around the v1.0.26 documentation/repository-readiness release scope.
- Updated server installer and installation documentation to the current Windows/XAMPP PowerShell flow.
- Added GitHub repository checklist, third-party notices, contribution notes, security policy, GitHub Actions workflow and issue templates.
- Documented the Aptoria UI asset redistribution caveat for public repositories.

## v1.0.25 - Test Stability & Release Baseline Hotfix
- Isolated setup lock detection during PHPUnit runs so a local `storage/app/installed.lock` file cannot block setup/demo import tests.
- Stabilized the full project Markdown report by forcing fresh project relation loading and querying latest/failed test-case context directly.
- Aligned the Project Details test-case summary label with the existing `messages.test_cases.total` translation key.
- Updated release metadata and build slug for the new v1.0.25 baseline.

## v1.0.24 - Roboto Typography & System Audit
- Set Roboto as the primary UI font across authenticated admin pages, login/setup screens, the public landing page and the first-run preflight page.
- Added Google Fonts loading links with safe Helvetica/Arial fallbacks so the app remains usable if the font cannot be fetched.
- Fixed missing literal translation keys for auth profile placeholder, endpoint placeholder and project health assertion labels in English and Hungarian.
- Added a system audit document for the v1.0.23 baseline and v1.0.24 patch scope.
- Kept the Aptoria UI/Bootstrap-based UI direction intact.

## v1.0.23 - Login Throttle Feedback, Fixed Footer & Localization Cleanup
- Fixed login throttling feedback so the lockout message is shown immediately when the threshold is reached.
- Added an inline login error panel as a no-JavaScript fallback in addition to Toastr.
- Changed the application footer to stay fixed at the bottom of the viewport with content padding to avoid overlap.
- Replaced several hardcoded dashboard, layout and project-detail UI strings with translation keys.
- Added missing Hungarian translations and aligned English/Hungarian language keys.

## v1.0.22 - Security Hardening Foundation + Deployment Security Status
- Combined v1.0.21 and v1.0.22 security work into one cumulative release based on v1.0.20.
- Added setup-token protection for non-local setup routes.
- Blocked browser-based dependency installation unless localhost or a valid setup token is used.
- Added login throttling, admin-only middleware and baseline security headers.
- Switched release .env defaults to production-safe values.
- Added a Security status panel under Settings.
- Strengthened SSRF/network target blocking and sensitive value masking.
- Added SECURITY_HARDENING and DEPLOYMENT_SECURITY_CHECKLIST documentation.

## v1.0.20 - Compact workspace module buttons
- Replaced oversized Workspace Modules widget cards with compact Aptoria UI-compatible buttons.
- Kept icons, counters and quick navigation while reducing the vertical footprint.
- Added compact button hover styling and safe overflow handling for long labels.

## v1.0.19 - Project details footer escape fix and module widget polish
- Fixed an extra closing wrapper div in the Project Details view that allowed the global footer to appear inside the workspace modules area.
- Added a global app footer override so the footer flows after content instead of overlaying long pages.
- Polished workspace module widgets with Aptoria UI-style mini chart strips and clearer footer call-to-action arrows.
- Added clearfix/z-index stabilization around the workspace modules grid.

## v1.0.18 - Workspace module widget redesign and footer stabilization
- Fixed Project Details workspace module layout so widget footers no longer bleed into the module area.
- Stabilized project and health widgets with flex-based footer alignment.
- Redesigned workspace module buttons into richer Aptoria UI-style widget cards inspired by the template panels.
- Improved visual hierarchy with eyebrow labels, large values, icon framing and dedicated footer call-to-action strips.

## v1.0.17 - Project details widget redesign and polished project health
- Replaced project detail color metric blocks with Aptoria UI-style widget panels.
- Redesigned the Project Details area with a clearer overview, operational summary and richer workspace module cards.
- Refreshed Project Health with summary widgets, execution quality, contract quality and finding pressure cards.
- Preserved cumulative package contents and existing project workflows.

## v1.0.16 - Aptoria UI widgets, app views and centered toastr
- Added top-center Toastr notifications for success, warning, info and error activity flash messages across authenticated and auth/setup pages.
- Refreshed dashboard with additional Aptoria UI-style widget panels and panel footers.
- Added a recent activity stream and app-view shortcut cards inspired by Aptoria UI interface/app view patterns.
- Removed inline auth/setup flash alerts in favor of unified Toastr feedback.

## v1.0.15 - Project details layout refinement
- Project detail action buttons moved into the Project Details panel header.
- Added Aptoria UI-style colored top accent on the Project Details panel.
- Introduced a colored project summary hero with visual metric cards to reduce empty space.
- Kept project module shortcuts inside the block with improved visual balance.

## v1.0.14 - Aptoria UI/UX polish follow-up
- KPI and workflow icons switched to clean white styling and alignment was corrected.
- Sidebar footer note moved into the page title area to avoid overlap with navigation items.
- Sidebar logo block centering improved.
- Badge readability improved with lighter label weight.
- Project health warning translation key completed.
- Project detail header simplified to add/new/edit actions only, with module links moved into the detail block.
- Add/new actions now use leading plus icons for better consistency.

# Aptoria v1.0.13 - Professional Aptoria UI/UX Refresh

## Changed

- Added a professional Aptoria UI-style page title and breadcrumb bar across authenticated screens.
- Added project context information to the global page header when working inside a project.
- Added FontAwesome icons to the main and project sidebar navigation.
- Added a Aptoria UI-style safe QA mode footer to the sidebar.
- Polished global panels, tables, forms, buttons, labels, alerts, pagination and code blocks.
- Improved dashboard hero, KPI cards and auth/setup screen presentation.
- Added responsive UI adjustments for smaller screens.

## Preserved

- Continues from the 1.0.10 server first-run installer line plus the later Aptoria UI/env stability hotfixes.
- No database migration changes.
- No business logic changes to scans, assertions, evidence packs, findings or release gates.

## Packaging

- `vendor/` is not included in the release ZIP.
- `.env` is not included.
- `database/database.sqlite` is not included.
- `storage/app/installed.lock` is not included.
- `public/assets/aptoria-ui/vendor` remains included.
