# Aptoria v1.0.95 – System Audit

## Release

**Version:** v1.0.95  
**Title:** Project Onboarding Wizard Stabilization Pass  
**Base:** v1.0.94 – Project Onboarding Wizard Pass

---

## Scope

This release is a consolidated review and stabilization pass for the v1.0.94 onboarding work. It intentionally avoids a chain of small hotfixes by fixing the known test regressions and tightening the guided onboarding validation in one cumulative package.

---

## Fixed issues

- Fixed the QA Coverage Matrix regression test that failed because the new global `/system/health` navigation link contained the same `/health` text as the endpoint path used in the coverage test.
- Updated the coverage assertion to check the actual endpoint row markup instead of any global navigation URL.
- Stabilized the System Health page title so the page itself consistently exposes `System health diagnostics`, not only the panel header.
- Made the System Health storage category assertion robust through normal escaped output handling.

---

## Onboarding validation hardening

The guided project wizard now validates credential requirements according to the selected auth profile type:

- Bearer auth requires a token.
- Basic auth requires username and password.
- Custom header auth requires header name and header value.
- No-auth remains valid without credentials.

This prevents the wizard from creating a half-configured auth profile when the user selected an auth type that needs credentials.

---

## Regression coverage

Updated or added tests cover:

- QA Coverage Matrix endpoint filtering without being confused by global navigation links.
- System Health page visibility and translated category rendering.
- Guided Project Wizard rollback when Bearer auth is selected without a token.
- Existing onboarding flow: project, environment, auth profile, endpoint, first safe scan, first snapshot and report links.

---

## QA focus

Before accepting this release, run:

```powershell
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan aptoria:health
C:\xampp\php\php.exe artisan test
```

Manual checks:

- Open **Projects → Guided Project** and complete a non-production setup.
- Confirm the completion page links to the first scan, first snapshot and full project reports.
- Try Bearer auth without a token and confirm the wizard blocks submission.
- Open **System Health** and confirm the diagnostics page renders.
- Open a QA Coverage Matrix gap filter and confirm resolved endpoint rows disappear correctly.

---

## Release hygiene

The release ZIP must not contain local runtime state:

- no root `vendor/`;
- no `.env`;
- no `database/database.sqlite`;
- no `storage/app/installed.lock`;
- no `storage/app/setup-token.txt`.

The frontend vendor assets under `public/assets/aptoria-ui/vendor` remain included.
