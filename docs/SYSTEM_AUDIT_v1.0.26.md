# Aptoria v1.0.26 System Audit

Release: **v1.0.26 – Documentation & GitHub Readiness Cleanup**  
Base: **v1.0.25 – Test Stability & Release Baseline Hotfix**

This release focuses on repository documentation and publishing readiness. It does not intentionally add new runtime functionality.

---

## Summary

Aptoria is currently a post-MVP / early beta Laravel application for self-hosted API QA workflow management.

The system already includes project management, endpoint inventory, safe scans, assertions, snapshots, test cases, contract validation, findings, release readiness, QA evidence packs and release gates.

The main gap before broader repository sharing was documentation drift. Several primary docs still described older milestones as if they were current. v1.0.26 aligns the main documentation with the actual release line.

---

## Runtime scope changed

No deliberate runtime feature change.

Expected runtime behavior should match v1.0.25.

---

## Documentation changes

Updated or added:

- `README.md`
- `docs/MVP_PLAN.md`
- `docs/INSTALLATION.md`
- `docs/QA_CHECKLIST.md`
- `SERVER_INSTALLER.md`
- `docs/GITHUB_REPOSITORY_CHECKLIST.md`
- `THIRD_PARTY_NOTICES.md`
- `CONTRIBUTING.md`
- `SECURITY.md`
- `.github/workflows/php.yml`
- `.github/ISSUE_TEMPLATE/bug_report.md`
- `.github/ISSUE_TEMPLATE/feature_request.md`

---

## Release hygiene expectation

The release ZIP must not contain:

- root `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`
- runtime caches/logs

The release ZIP should contain:

- `.env.example`
- `.env.production.example`
- `.env.testing`
- `composer.json`
- `composer.lock`
- `scripts/update-windows-xampp.ps1`
- `public/assets/aptoria-ui/vendor`

---

## GitHub status

Private GitHub repository readiness is improved.

Public GitHub repository readiness remains conditional because the current UI line still bundles Aptoria UI admin template assets. Public release requires license confirmation or asset replacement/removal.

---

## Recommended next step

Next logical release:

```text
v1.0.28 – Scheduled Monitoring Operations Pass
```

Focus areas:

- production `.env` checklist;
- HTTPS and server root guidance;
- setup lock/token review;
- scheduler documentation;
- secret scanning guidance;
- public/private repository decision.

