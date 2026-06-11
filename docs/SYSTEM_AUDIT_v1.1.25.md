# Aptoria v1.1.25 System Audit

Release: **v1.1.25 - Contract Reality Check Pass**

## Scope

This release adds a Contract Reality Check layer on top of OpenAPI contract validation. It compares the documented contract with stored scan evidence and Aptoria endpoint metadata so release reviewers can see where the API reality differs from the expected contract.

## Added / changed

- Added Project → Contract Reality page.
- Added `ContractRealityService` summary layer.
- Added OpenAPI security vs endpoint auth requirement check.
- Added no-auth comparison awareness for contract/auth boundary mismatches.
- Added undocumented top-level response field detection.
- Added sensitive-looking undocumented field escalation.
- Added Contract Reality summary to Release Readiness and Full QA reports.
- Added English and Hungarian translations.
- Added `ContractRealityCheckTest` feature coverage.

## Release safety

The release uses stored scan evidence. It does not execute extra HTTP requests during contract reality analysis.

## Required verification

Run:

```powershell
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
```

Then open **Project → Contract Reality** and verify the latest contract validation is summarized with auth mismatches, undocumented response fields, missing documented endpoints and undocumented endpoints.
