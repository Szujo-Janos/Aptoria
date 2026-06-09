# Aptoria v1.1.0 System Audit

## Release

**Aptoria v1.1.0 – Postman Compatibility, Globals & Newman Import Pass**

## Scope

This cumulative release combines the planned Postman compatibility/globals pass and Newman results import pass.

## Added

- Postman Globals JSON import support.
- Postman compatibility metadata for v2.0/v2.1 review.
- Unsupported Postman auth/script warnings in preview.
- Newman JSON report import.
- Newman JUnit XML report import.
- Test suite, test case and test result creation from Newman execution rows.
- Optional finding and evidence creation for failed Newman assertions.

## Safety

- Postman secrets remain masked in preview/request metadata.
- Newman import does not execute external requests; it only reads pasted report payloads.
- Failed Newman assertion evidence is stored as log evidence linked to generated findings.

## Verification

- PHP syntax check must pass.
- Translation key parity must pass for English and Hungarian.
- Full `php artisan test` should be run in the installed project with vendor dependencies available.
