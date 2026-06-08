# Aptoria v1.0.25 System Audit

Base package audited: `aptoria-1.0.24.zip`

## Scope

This release is a stabilization patch. It does not add a new product feature.

## Changes made

- `SetupStateService::isLocked()` now ignores the local setup lock while Laravel is running PHPUnit. This keeps production setup locking intact, but prevents a developer machine's `storage/app/installed.lock` from breaking setup wizard tests.
- Project Details now renders the existing `messages.test_cases.total` label, so the test-case summary is visible and translated.
- Full project Markdown export reloads project relations instead of relying on potentially stale previously-loaded relations.
- Full project Markdown export queries latest test results and failed/blocked test cases directly, so newly created test context appears reliably in reports.
- Version metadata moved to `1.0.25`.

## Checks performed in this sandbox

| Area | Result | Notes |
| --- | --- | --- |
| PHP syntax lint | PASS | `php -l` passed for all PHP files in app, bootstrap, config, database, public, resources, routes and tests. |
| EN/HU translation parity | PASS | English and Hungarian translation leaf-key counts match. |
| Release hygiene | PASS | The generated release excludes root `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` and `storage/app/setup-token.txt`; it keeps `public/assets/aptoria-ui/vendor`. |
| PHPUnit | NOT RUN HERE | The release ZIP intentionally does not include root `vendor/`; run `php artisan test` locally under XAMPP. |

## Expected local verification

```powershell
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan test --filter=SelfInstallerTest
C:\xampp\php\php.exe artisan test --filter=DemoQaProjectSeederTest
C:\xampp\php\php.exe artisan test --filter=TestSuitesAndCasesTest
```

## Manual QA focus

- Setup page remains available and still shows the demo import action during tests/unlocked setup.
- Demo import can be triggered from the setup wizard when migrations are ready.
- Project Details shows the test-case total summary.
- Endpoint Details shows linked test cases.
- Full project Markdown report includes Test Suites, Latest Test Results and Failed / Blocked Test Cases.
