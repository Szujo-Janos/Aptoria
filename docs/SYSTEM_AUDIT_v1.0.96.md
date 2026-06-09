# Aptoria v1.0.96 – System Audit

**Release:** Auth Profile Tester Pass  
**Version:** v1.0.96  
**Base:** cumulative from v1.0.95  

## Scope

This release completes the Auth Profile Tester roadmap item. It keeps the existing Guided Project Wizard and System Health work, then improves auth profile validation feedback by allowing an admin to send a safe test request from the auth profile edit screen.

## Implemented changes

- Added endpoint-based auth test mode for saved GET/HEAD endpoint inventory.
- Kept custom URL test mode for direct GET/HEAD auth checks.
- Applied stored Bearer, Basic and Custom Header profiles to the outgoing test request.
- Displayed structured result data after a test:
  - target
  - request method and URL
  - HTTP status
  - duration
  - content type
  - masked auth summary
  - masked response headers
  - masked response preview
- Classified auth test result state:
  - 2xx/3xx: passed
  - 401/403: failed
  - other 4xx/5xx: needs review
  - connection/blocked/incomplete profile: failed or incomplete
- Added English and Hungarian translation keys for the tester UI and result panel.
- Added regression tests for Bearer header application, unauthorized response classification and result rendering.

## Safety notes

- Auth tests are limited to GET and HEAD.
- Localhost/private/reserved targets remain blocked by `NetworkTargetGuard`.
- Secrets are masked in UI output.
- The test does not store response data permanently; it is shown as a session flash result.

## Files changed

- `app/Http/Controllers/AuthProfileController.php`
- `app/Services/Auth/AuthProfileTestService.php`
- `resources/views/auth_profiles/edit.blade.php`
- `resources/lang/en/messages.php`
- `resources/lang/hu/messages.php`
- `tests/Feature/AuthProfileTesterTest.php`
- `VERSION`
- `CHANGELOG.md`
- `README.md`
- `docs/INSTALLATION.md`
- `docs/QA_CHECKLIST.md`
- `SERVER_INSTALLER.md`

## Release hygiene expectations

The release ZIP must not include:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`

The release ZIP must keep:

- `public/assets/aptoria-ui/vendor`
