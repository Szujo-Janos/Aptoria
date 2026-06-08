# Aptoria v1.0.63 – Blade Asset URL Render Hotfix

Release date: 2026-06-08

## Purpose

v1.0.63 fixes the remaining PHPUnit failure reported after v1.0.61. The local test output showed `RiskAnalyzerTest > sensitive public endpoint signal is detected` failing because the calculated risk score was `8`, while the expected high-risk threshold was at least `35`.

## Root cause

The v1.0.57–v1.0.59 Settings wiring pass made the sensitive keyword risk signal configurable, but the default weight was left too low (`8`). The signal was detected correctly and the calculated level was high, but the numeric score no longer matched the historical QA expectation for a public sensitive endpoint.

## Fix

- Updated `risk.sensitive_keyword_weight` default from `8` to `35`.
- Marked the setting as `active`, because it directly affects the risk analyzer runtime score.
- Kept the Settings Center and report/release wiring changes intact.

## Release hygiene

- ZIP name: `aptoria-1.0.63.zip`
- Root folder: `aptoria-1.0.63/`
- Forbidden runtime files remain excluded.

## QA focus

- `C:\xampp\php\php.exe artisan test` should no longer fail with `Failed asserting that 8 is equal to 35 or is greater than 35`.
- `RiskAnalyzerTest` should pass.
- Existing Settings Center controls should remain available.
