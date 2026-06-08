# GitHub Repository Checklist

Current version: **v1.0.65**

This checklist prepares Aptoria for GitHub use. v1.0.65 keeps the public repository hygiene layer aligned with current installation commands, source-available licensing and public QA gate expectations.

## Private repository checklist

- [ ] `C:\xampp\php\php.exe artisan test` is green locally.
- [ ] `README.md` shows the current version.
- [ ] `docs/QA_CHECKLIST.md` is current.
- [ ] `docs/INSTALLATION.md` is current.
- [ ] `docs/PORTFOLIO_SHOWCASE.md` exists.
- [ ] `.env` is not tracked.
- [ ] `vendor/` is not tracked.
- [ ] `database/database.sqlite` is not tracked.
- [ ] `storage/app/installed.lock` is not tracked.
- [ ] `storage/app/setup-token.txt` is not tracked.
- [ ] Runtime logs and cache files are not tracked.
- [ ] `.github/workflows/php.yml` exists.
- [ ] GitHub Actions workflow is green after push.
- [ ] Issue templates exist.
- [ ] Pull request template exists.
- [ ] `SECURITY.md` exists.
- [ ] `CONTRIBUTING.md` exists.
- [ ] `LICENSE` exists.
- [ ] `THIRD_PARTY_NOTICES.md` exists.
- [ ] `CREDITS.md` exists.
- [ ] `NOTICE.md` exists.

## Public repository checklist

- [ ] The owner accepts that the full source code will be visible.
- [ ] The repository description says this is a source-available API QA tool.
- [ ] The `LICENSE` file is kept with the repo.
- [ ] `THIRD_PARTY_NOTICES.md` is kept with the repo.
- [ ] The `CREDITS.md` file is kept with the repo.
- [ ] The `NOTICE.md` file is kept with the repo.
- [ ] No real customer/project data is included.
- [ ] No private URLs, credentials or tokens are included.
- [ ] Public issue template warns users not to post secrets.
- [ ] Public release ZIPs are attached through GitHub Releases if used.

Do not present Aptoria as enterprise-ready security tooling. Present it as a self-hosted API QA evidence, safe scan and release readiness workflow tool.

## Suggested local pre-push commands

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

## Suggested first public repository push

```powershell
$ProjectRoot = "C:\xampp\htdocs\aptoria"

cd $ProjectRoot

git init
git add .
git commit -m "Release Aptoria v1.0.65 portfolio-showcase-documentation-pass"

git branch -M main
git remote add origin https://github.com/Szujo-Janos/aptoria.git
git push -u origin main
```
