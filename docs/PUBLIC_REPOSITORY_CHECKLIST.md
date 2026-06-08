# Public Repository Checklist

Current version: **v1.0.65**

This checklist is the final pre-push gate before publishing Aptoria as a public GitHub repository.

## Required before the first public push

- [ ] `LICENSE` exists and clearly says the project is source-available, not open-source.
- [ ] `THIRD_PARTY_NOTICES.md` lists bundled frontend libraries and license notes.
- [ ] README includes a Credits and copyright section.
- [ ] `CREDITS.md` exists and credits the project owner and third-party notices.
- [ ] `NOTICE.md` exists and states project ownership/copyright.
- [ ] `.env` is not present.
- [ ] `vendor/` is not present.
- [ ] `database/database.sqlite` is not present.
- [ ] `storage/app/installed.lock` is not present.
- [ ] `storage/app/setup-token.txt` is not present.
- [ ] No production endpoint URLs, private domains, tokens, passwords or customer data are committed.
- [ ] README shows the current release and public-safe positioning.
- [ ] `docs/PORTFOLIO_SHOWCASE.md` is present and public-safe.
- [ ] SECURITY.md tells users not to post secrets in public issues.
- [ ] CONTRIBUTING.md explains the early-beta contribution rules.
- [ ] GitHub issue templates are present.
- [ ] Pull request template is present.
- [ ] GitHub Actions PHP workflow is present.
- [ ] GitHub Actions workflow is green after the first public push.
- [ ] Dependabot config is present for GitHub Actions and Composer metadata.

## Recommended local checks

```powershell
cd "C:\xampp\htdocs\aptoria"

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan aptoria:security-audit

git status
```

## Public repository positioning

Use this wording in the GitHub description:

```text
Self-hosted Laravel tool for API QA evidence, safe endpoint scans, regression monitoring and release readiness review.
```

Suggested topics:

```text
laravel api-testing qa security-review regression-monitoring self-hosted php sqlite release-readiness
```

## What should not be promised publicly yet

Do not present Aptoria as an enterprise-ready scanner or a replacement for professional security tooling. It is currently a self-hosted QA evidence and workflow tool in early beta.
