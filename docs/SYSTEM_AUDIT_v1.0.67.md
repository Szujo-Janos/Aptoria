# Aptoria v1.0.67 – Settings Activation Pass

v1.0.67 replaces the previous Settings audit presentation with functional activation. The Settings UI no longer exposes maturity categories; it shows product controls only.

## Key changes

- Removed Settings maturity counters and per-field maturity badges from the UI.
- Corrected mismatched Settings keys in scan safety and report builder code.
- Added session timeout middleware driven by `security.session_timeout_minutes`.
- Gated calendar activity logging with `security.enable_audit_log`.
- Added fallback assertion rules from global assertion defaults.
- Wired destructive path keyword protection to the saved Settings keys.
- Wired typed production confirmation to its Settings switch.
- Kept Windows/XAMPP and GitHub Actions compatibility.

## Release expectation

Settings Center is now treated as an operational configuration surface. New Settings keys should not be added unless their runtime consumer is implemented in the same release.
