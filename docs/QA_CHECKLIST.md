# Aptoria 0.0.43 QA checklist

## Setup smoke test

1. Delete the old project folder before copying the ZIP.
2. Open `/dashboard` before install: it must redirect to `/setup`.
3. `/setup` shows the white loader first, then the Aptoria guided setup flow.
4. The setup topbar centers the official `logo-color.svg` logo; the version/kicker stays on the left and language/theme actions stay on the right.
5. Tabler icons render at normal size in the welcome cards, environment checks, automatic action rows and install card. No tiny/broken icon placeholders are acceptable.
6. Environment checks animate row by row, replace spinners with status icons and re-enable the Continue button at the end.
7. Install confirmation opens a styled SweetAlert2 modal, not a browser-native confirm box.
8. After SweetAlert confirmation, the install progress white overlay appears and cycles through progress messages.
9. Install completes and writes `storage/app/installed.lock`.
10. `/setup` redirects away after installation.
11. First login redirects to `/profile` for password/profile confirmation.

## Workspace smoke test

1. Dashboard loads after login.
2. Topbar shows the official Aptoria logo, mega menu, project switcher, language switch, theme toggle and user menu.
3. Create a project with base URL, environment label, QA owner and release goal.
4. After create, the new project becomes the current project.
5. Project list shows the project in the template-style table with an action dropdown.
6. Project Activate Workspace action selects the project and opens its workspace.
7. Project detail page shows metrics, readiness checklist, module grid and audit activity.
8. Sidebar shows the active project context.
9. QA module links opened from a project use `/projects/{project}/modules/{module}`.
10. Global module links still work when no project is selected.
11. Project create/update/delete writes audit events.
12. HU/EN language switch still works.
13. Light/dark theme toggle is still visible.

## v0.0.3 environment/auth smoke test

1. Open a project and use the sidebar Environments link.
2. Create a dev/staging environment with a Base URL.
3. The environment appears in the responsive DataTables table.
4. Actions dropdown opens without clipping.
5. Quick preview modal shows the environment details.
6. Make Default changes the default badge and writes a toast.
7. Production environment shows a production warning badge.
8. Delete uses SweetAlert confirmation.
9. Open Auth Profiles from the sidebar.
10. Create None, Bearer, Basic or Custom Header auth profile.
11. Secret fields are not printed back in the form.
12. Masked preview is shown instead of raw token/password/header value.
13. Make Default changes the default auth profile.
14. Open Project Settings and verify default environment/auth selectors.
15. Scan safety switches persist: confirmation, safe methods only, private networks.
16. Project detail readiness and metrics use real environment/auth counts.

## v0.0.7 release readiness smoke test

1. Create a project, environment and at least one GET endpoint.
2. Run a Safe Scan.
3. Create at least one evidence item or finding from a scan result.
4. Open Release Readiness from the sidebar.
5. Verify the score, blocker/warning count and check table.
6. Click Evaluate readiness and confirm with SweetAlert.
7. Save a snapshot with an optional decision note.
8. Open the saved snapshot and verify the check table remains panel-fitting and `w-100`.

## v0.0.9 client portal smoke test

1. Generate at least one report for a project.
2. Open Client Portal from the sidebar.
3. Create a portal link with reports and readiness permissions.
4. The portal link appears in a panel-fitting `w-100` table.
5. Actions dropdown opens without clipping.
6. Open the public portal link in a new tab.
7. Public page shows only the selected review scope.
8. Download HTML, Markdown and JSON report files from the public page.
9. If acknowledgement is required, submit the acknowledgement form.
10. Return to the internal Client Portal page and verify last viewed / acknowledged state.
11. Deactivate the link and verify the public link no longer works.

## v0.0.11 empty workspace UX smoke test

1. Start with an installed system and no projects in the database.
2. Dashboard loads and shows the no-project CTA instead of an empty/broken workspace.
3. Sidebar still shows Dashboard, Projects and project-scoped module links.
4. Sidebar no-project card shows Create Project with a full-width button.
5. Open Environments from the sidebar without a project; the placeholder page loads.
6. Open Auth Profiles from the sidebar without a project; the placeholder page loads.
7. Open Calendar, Reports, Release Readiness and Client Portal without a project; each explains that a project is required.
8. Topbar project switcher shows No active project and a Create Project action.
9. Create the first project.
10. Sidebar switches to the active project context and live project module links work again.

## v0.0.13 auth profile tester smoke test

1. Create or open a project with a base URL or at least one environment with a base URL.
2. Open Auth Profiles from the sidebar.
3. Verify the new Test authentication panel appears above the auth profile table.
4. Select an environment, auth profile and GET method.
5. Use a safe test path such as `/health` or `/api/me`.
6. Click Run auth test and confirm the SweetAlert prompt.
7. Verify the Bootstrap toast appears after redirect.
8. Verify the Latest test result panel shows state, URL, status, response time and content type.
9. Test an expected status mismatch and verify it becomes a warning.
10. Test without an auth profile and verify the UI clearly says No authentication.
11. Open Audit Log and verify an auth profile test event was recorded.
12. Confirm the auth profile table remains panel-fitting, `w-100` and Actions dropdown-safe.

## v0.0.15 endpoint quick test history smoke test

1. Open a project with at least one GET endpoint.
2. Open Endpoint Inventory.
3. Verify the table has a Latest quick test column.
4. Before running a test, the endpoint should show Not tested.
5. Use the endpoint Actions dropdown and run Run quick test.
6. Confirm the SweetAlert prompt.
7. Verify the Bootstrap toast appears after redirect.
8. Verify the latest result panel still appears at the top.
9. Verify the endpoint row now shows the latest quick test badge and HTTP summary.
10. Verify the Endpoint quick test history table contains the new run.
11. Run a second endpoint quick test and verify the history table is ordered newest first.
12. Open Release Readiness and verify the quick test coverage / latest failure checks appear in the readiness table.
13. Confirm all endpoint tables remain panel-fitting, `w-100`, and Actions dropdown-safe.


## v0.0.16 endpoint quick test evidence detail smoke test

1. Open a project with at least one GET endpoint.
2. Run an Endpoint Quick Test from the Endpoint Inventory Actions dropdown.
3. Verify the latest quick test badge appears in the endpoint row.
4. Click View evidence from the latest quick test cell.
5. Verify the Endpoint quick test evidence detail page opens without error.
6. Check the top hero shows the run state badge and checked-at timestamp.
7. Verify endpoint method, endpoint name, path and target URL are shown.
8. Verify environment and auth profile context are shown.
9. Verify expected status, actual HTTP result, expected content type and actual content type are visible.
10. Verify response time and response size are displayed.
11. If a body preview exists, verify it is masked and short.
12. Click Copy evidence and verify the Bootstrap toast appears.
13. Return to Endpoint Inventory and open evidence from the history table row.
14. Confirm endpoint tables remain panel-fitting, `w-100`, and Actions/dropdown-safe.

## v0.0.17 endpoint batch quick test smoke test

1. Open a project with at least two active GET/HEAD endpoints.
2. Open Endpoint Inventory.
3. Verify the page action button Run safe endpoint tests appears beside New endpoint.
4. Click Run safe endpoint tests and confirm the SweetAlert prompt.
5. Verify a Bootstrap toast appears after redirect.
6. Verify the Safe endpoint test batch result panel appears.
7. Check the passed / warning / failed / skipped counters.
8. Verify recent batch items link to Endpoint Quick Test Evidence detail pages.
9. Verify the Endpoint quick test history table now contains one persisted row per tested endpoint.
10. Verify inactive, excluded and non-GET/HEAD endpoints are not executed by the batch action.
11. Open Audit Log and verify an endpoint batch quick test event was recorded.
12. Confirm the endpoint table and history table remain panel-fitting, `w-100`, and Actions/dropdown-safe.

## v0.0.18 endpoint batch evidence summary smoke test

1. Open a project with at least two active GET/HEAD endpoints.
2. Open Endpoint Inventory.
3. Run **Run safe endpoint tests** from the page header.
4. Confirm with SweetAlert.
5. Verify the batch result panel shows passed / warning / failed / skipped counters.
6. Open **View batch evidence** from the result panel.
7. The Endpoint Batch Test Evidence page must load without layout overflow.
8. Verify the batch summary card shows total, status, duration and completed-at data.
9. Verify the endpoint evidence table uses `w-100` and compact Actions dropdowns.
10. Open a single endpoint evidence record from the batch detail table.
11. Verify the single endpoint evidence page links back to the parent batch evidence.
12. Copy the Markdown batch evidence and confirm the Bootstrap toast appears.
13. Return to Endpoint Inventory and verify the Endpoint batch quick test history table contains the batch.
14. Open Release Readiness and verify batch evidence checks appear.

## v0.0.43 report visual standard smoke test

1. Generate a Full project QA report from Reports.
2. Download HTML and verify it opens as a standalone professional report document.
3. Confirm the root element contains `data-aptoria-report-standard="report-visual-standard-v1.1"`.
4. Confirm the report has the Aptoria header, metadata table, five-cell summary strip, notice block, numbered report section and footer.
5. Confirm checksum is visible in the metadata table and footer.
6. Approve a report with sign-off details, download HTML again and verify the approval/sign-off section appears.
7. Create a Client Portal link for an approved report and download the HTML report from the public portal.
8. Confirm the public portal HTML download uses the same report visual standard.
9. Open browser print preview and verify tables stay inside the page.
10. Check a narrow/mobile viewport and verify header, summary strip and footer stack cleanly.


## Official logo QA

1. Topbar, login, landing, setup loader, setup wizard, client portal and HTML report exports use `assets/aptoria-ui/assets/images/logo-color.svg`; the sidebar intentionally keeps text-only branding without a logo image.
2. No old logo PNG references remain: `aptoria-logo.png`, `aptoria-logo-sm.png`, `aptoria-logo-white.png`, `logo.png`, `logo-sm.png`, `logo-black.png`.
3. HTML report export embeds `data:image/svg+xml;base64` and displays the official Aptoria logo.
4. The exported report must not fall back to a one-letter placeholder mark.


## Desktop-only shell QA

1. Sidebar brand area shows text only: application name and tagline. It must not render a logo image in the sidebar.
2. Topbar must not render the hamburger/collapse button. Sidebar remains fixed on supported screens.
3. The application must be blocked below XXL width with the white-screen-style desktop-only message.
4. At 1400 px and wider, the normal Aptoria UI is usable.
5. Below 1400 px, the overlay must say that Aptoria requires an XXL / 1400 px or wider workspace.


## v0.0.43 setup wizard redesign smoke test

1. Open `/setup` on a 1400 px or wider desktop viewport.
2. Verify the official `logo-color.svg` is centered at the top with the Setup Wizard/version subtitle below it.
3. Verify the theme and language controls sit on the top-right as compact pills.
4. Verify the main setup card uses a five-step wizard: Welcome, Environment Check, Database & App Config, Default Admin, Install & Finalize.
5. Verify the active step has a blue highlighted row and the previous steps become completed check circles.
6. Open the Environment Check step and verify rows animate with spinners, progress bars and status badges.
7. Continue to Database & App Config and verify automatic runtime/database/app-key/migration/default-setting tasks render as polished rows with normal-size Tabler icons.
8. Continue to Default Admin and verify temporary credentials are shown in a dedicated premium card.
9. Continue to Install & Finalize and verify there is no separate Installation status card in the sidebar.
10. Tick the confirmation checkbox; the Back and Install buttons must be on their own row below the install text/checkbox.
11. Click install and verify SweetAlert2 appears with Aptoria styling, not a native browser confirm.
12. Confirm installation and verify the white install progress overlay animates through the setup messages.
13. Narrow the browser below 1400 px and verify the desktop-only white-screen message blocks the setup UI.


## v0.0.60 sandbox scenario templates smoke test

1. Open `/demo-guide` and verify the Scenario templates card appears above the old walkthrough.
2. Click each scenario card: First smoke scan, Security leak review, Artifact import trace and Release gate decision.
3. Confirm the selected card highlights and the right-side guided run sheet changes.
4. Open the Scenario evidence JSON button and verify `/demo-api/scenarios/{slug}/evidence.json` returns readable JSON.
5. Open `/demo-api/scenarios` and verify it returns four scenario templates.
6. Open `/demo-api/artifacts/scenario-templates.json` and verify it returns the same template list as an import artifact.
7. Rebuild the Sandbox API workspace from Program Settings or `aptoria:demo-reset`.
8. Open the generated project and verify the project-scoped Demo Guide links steps into Safe Scan, Import Center, Evidence, QA Cockpit, Release Readiness, Release Gates and Reports.
9. Verify the generated endpoint inventory contains scenario endpoints such as `/scenarios`, `/scenarios/security-leak-review` and `/scenarios/release-gate-decision/evidence.json`.
10. Verify Evidence Repository contains the verified “Guided release gate scenario run sheet” evidence item.

## v0.0.61 – Live/Sandbox Workspace Separation & Sandbox Safety Banner

1. Run migrations and clear caches.
2. Open the topbar and confirm the LIVE/SANDBOX switch is visible on desktop width.
3. In LIVE mode, confirm the Projects page lists only live projects.
4. Create a new project in LIVE mode and confirm its workspace type is LIVE.
5. Switch to SANDBOX mode from the topbar.
6. Confirm the persistent sandbox safety strip appears below the topbar.
7. Confirm the project switcher no longer mixes live projects into the sandbox list.
8. Build the guided demo sandbox from Program Settings.
9. Confirm the created project is named `Aptoria Guided Demo Sandbox` and has SANDBOX workspace type.
10. Build the Sandbox API workspace from Program Settings or run `aptoria:demo-reset`.
11. Confirm the created project is named `Aptoria Sandbox API` and has SANDBOX workspace type.
12. Open a sandbox project and confirm sidebar badges show SANDBOX for project-scoped modules.
13. Switch back to LIVE mode and confirm the sandbox warning strip disappears.
14. Confirm user management and license management still show LIVE badges.

## v0.0.62 - Dashboard Editor Revert Smoke Test

1. Run migrations and clear caches.
2. Open `/dashboard` on a desktop-sized browser window, 1400 px or wider.
3. Confirm there is no **Desktop Dashboard Layout Editor** toolbar.
4. Confirm there is no **Edit dashboard**, **Save layout**, **Cancel** or **Reset default** dashboard editor control.
5. Confirm dashboard cards render in the normal fixed Bootstrap layout.
6. Confirm no drag handles, resize buttons, green placeholders or modal-grey editor overlay appear.
7. Confirm the active LIVE/SANDBOX workspace switch still works from the topbar.
8. Confirm the global desktop-only guard still blocks browser widths below 1400 px.

## License Activation Recovery Flow

- Enable or simulate `APTORIA_LICENSE_REQUIRED=true` with no valid license.
- Open `/dashboard`; it should redirect to `/license/activate`.
- `/license/activate` must show the current license state, machine fingerprint and USB fingerprint.
- Download `/license/request.json`; it should return the license request JSON.
- Paste an invalid public key; the form must reject it.
- Paste a valid public key; it should be saved and return to the activation page.
- Upload malformed JSON; the form must reject it.
- Upload a signed license that does not match the public key/fingerprint; it must be rejected.
- Upload a valid signed `aptoria-license.json`; it should redirect to login or dashboard.
- After activation, Program Settings → License Management should still work as the normal admin management screen.

## Simplified License Activation

- Enable or simulate `APTORIA_LICENSE_REQUIRED=true` with no valid license.
- Open `/dashboard`; it should redirect to `/license/activate`.
- The activation page should show one primary file upload, not separate public-key and license forms.
- Download `/license/request.json`; it should return the license request JSON for the issuer.
- Upload a ZIP missing `aptoria-license.json`; it must be rejected.
- Upload a ZIP missing `license-public.pem` when no public key exists; it must be rejected.
- Upload a ZIP containing both `aptoria-license.json` and `license-public.pem`; it should install both and redirect to login/dashboard.
- Upload a plain signed `aptoria-license.json` when the public key is already installed; it should work.

## Simplified License Management

- Open Program Settings → License Management.
- The main screen should show license status cards and one primary activation package upload.
- The main screen should not show separate public key and signed-license forms by default.
- Download request should still work.
- Upload an invalid activation package; it must be rejected.
- Upload a valid activation ZIP/JSON; it should install and return to License Management.
- Open Advanced manual install; the old separate public-key and license-file forms should be available only there.

## Online License Authority Client Foundation

- Keep `APTORIA_LICENSE_MODE=local_package`; normal local activation should behave as before.
- Set `APTORIA_LICENSE_MODE=online_authority` and `APTORIA_LICENSE_REQUIRED=true`.
- With no valid local license, `/dashboard` should still redirect to `/license/activate`.
- With a valid local license but no cached lease, runtime should require online verification.
- License Management should show an Online License Authority status block.
- `storage/app/license-runtime-lease.json` must not be present in the release ZIP.
- `storage/app/license-install-id` must not be present in the release ZIP.
- README must use the professional GitHub format and point release history to `CHANGELOG.md`.
