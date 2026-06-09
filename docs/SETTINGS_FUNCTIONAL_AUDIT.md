# Aptoria v1.0.73 Settings Functional Audit

The Settings Center is a product-control surface. In v1.0.72 every visible global Settings field is expected to be saveable, validated, persisted and consumed by runtime code. Project Settings use the same rule.

## Audit Result

- Global Settings defaults: 128
- Visible global Settings fields: 127
- Runtime-only compatibility Settings: 1 (`scan.delay_between_requests_ms`)
- Project Settings fields: 10
- Missing runtime consumers found by audit: 0
- English remains the default UI language.
- Hungarian remains selectable through the existing locale switch.

## Fixed In v1.0.72

- Removed assertion default controls that only appeared in a dead private helper and did not affect runtime behavior:
  - `assertions.default_max_response_time_ms`
  - `assertions.default_max_risk_score`
  - `assertions.default_content_type`
  - `assertions.default_required_response_headers`
  - `assertions.default_forbidden_response_headers`
  - `assertions.default_body_must_contain`
  - `assertions.default_body_must_not_contain`
- Removed the unused `app.enable_public_demo_hints` runtime lookup from the help workflow screen.
- Kept `assertions.default_status_code` because it is used by the assertion rule creation flow.
- Kept `scan.delay_between_requests_ms` as a hidden compatibility fallback mirrored from `scan.rate_limit_ms`.
- Wired project notes to the project detail summary so saved `project.notes` has a visible runtime effect.
- Added `.gitkeep` files for required Laravel runtime folders in clean ZIP installs.

## Regression Coverage

`tests/Feature/SettingsFunctionalAuditTest.php` verifies:

1. Every visible global Settings field renders in the Settings form.
2. Every visible field can be submitted and persisted.
3. The runtime-only compatibility key is persisted but not shown in the UI.
4. Every visible global key has a runtime consumer outside the Settings form/controller.
5. The Settings page does not show misleading activation copy in English or Hungarian.
6. UI Settings change rendered dashboard classes and visibility.
7. Session timeout Settings expire inactive sessions.

Additional coverage:

- `SettingsLocalizationTest` verifies English and Hungarian labels for active Settings.
- `ProjectSettingsTest` verifies project Settings save/export and project notes display.
- `AssertionEvaluationTest` verifies endpoints without explicit assertion rules remain `not_configured`.

## Product Rule

Do not add Settings controls as placeholders. A new Settings control is acceptable only when validation, persistence, runtime consumption and a QA path are included in the same release.


## v1.0.73 Documentation Polish Note

The v1.0.73 package keeps the v1.0.72 Settings runtime behavior and audit result, while aligning public documentation, installation commands, ZIP naming and release references with the current package. Leftover internal `status => active` Settings metadata was removed from `SettingService` because user-facing Settings maturity/status labels are not part of the product UI contract.


## v1.0.74 Rebrand Note

The Settings system remains functionally unchanged by the Aptoria rebrand. Labels, defaults, export metadata and documentation references were updated to the Aptoria product identity.


## v1.0.76 Rebrand Polish Note

The Settings system remains behaviorally unchanged. This pass aligns release documentation, rebrand tests and current public metadata after the Aptoria rename.
