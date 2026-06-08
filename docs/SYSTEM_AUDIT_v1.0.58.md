# Aptoria v1.0.58 – Release Readiness Settings Wiring

## Scope

v1.0.58 connects the Release Readiness settings to the actual readiness summary and saved release gate evaluation logic.

## Activated release controls

- Minimum successful scan requirement.
- Failed assertions blocking.
- Critical risk/finding blocking.
- High risk review warnings.
- Regression blocking.
- Required QA evidence.
- Required snapshot when enabled.
- Required generated gate/report snapshot when enabled.
- Configurable minimum coverage percentage.

## Result

Release Readiness is now policy-driven by the Settings Center instead of only using hard-coded thresholds.

## Release hygiene

- ZIP name: `aptoria-1.0.58.zip`.
- Forbidden runtime files remain excluded.
