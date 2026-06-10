# Aptoria v1.1.11 – Executive / Technical Report Split Pass System Audit

**Version:** v1.1.11  
**ZIP:** `aptoria-1.1.11.zip`  
**Focus:** Split QA reporting into separate executive and technical report profiles.

## Audit summary

| Area | Status | Notes |
|---|---|---|
| Version metadata | PASS | `VERSION`, config fallback and public installation docs resolve to `1.1.11`. |
| Executive report profile | PASS | Dedicated MD/HTML/PDF exports use a short decision-focused section set. |
| Technical report profile | PASS | Dedicated MD/HTML/PDF exports use detailed QA/developer evidence sections. |
| Request/response evidence | PASS | Technical report can include endpoint-level URL, HTTP, timing, content type, size, security flags and body/error preview. |
| Report center UI | PASS | Project Reports includes separate Executive Report and Technical Report panels. |
| Custom report builder | PASS | Builder includes an optional technical request/response evidence table checkbox. |
| Localization | PASS | English and Hungarian labels were added for the new report profiles and UI copy. |
| Regression coverage | PASS | Feature coverage verifies executive and technical exports contain distinct content. |
| Release ZIP hygiene | PASS | Release package excludes runtime-only files and keeps `public/assets/aptoria-ui/vendor`. |

## Implemented changes

- Added report profile constants and profile presets to `FullQaReportBuilderService`.
- Added Executive Report profile:
  - executive summary
  - release readiness
  - release gate snapshot
  - recommendations
  - appendix/safety notes
- Added Technical Report profile:
  - release readiness
  - QA coverage
  - test execution
  - test suites and cases
  - findings and evidence with details
  - OpenAPI contract validation
  - scan/snapshot/regression evidence
  - endpoint inventory
  - technical request/response evidence table
  - recommendations and appendix
- Added dedicated project report routes:
  - `executive.md`, `executive.html`, `executive.pdf`
  - `technical.md`, `technical.html`, `technical.pdf`
- Added project report center cards for the two report types.
- Added optional technical details toggle to the custom report builder.
- Added regression test coverage for profile separation.

## Manual QA checklist

1. Install from `aptoria-1.1.11.zip`.
2. Run `php artisan migrate`.
3. Run `php artisan aptoria:health`.
4. Run `php artisan test`.
5. Open **Project → Reports**.
6. Export Executive MD/HTML/PDF and confirm the report is concise and decision-focused.
7. Export Technical MD/HTML/PDF and confirm the report includes endpoint, finding, evidence, contract and request/response details.
8. Open the custom report builder and test the technical request/response evidence checkbox.
9. Confirm existing Full Project, Release Readiness and QA Evidence pack exports still work.

## Release notes

This release keeps the existing full project report intact, but adds two explicit export paths for real QA handoff workflows: one for management/release decisions and one for developer/QA evidence review.
