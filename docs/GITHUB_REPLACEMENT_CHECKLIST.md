# GitHub replacement checklist

This checklist is for replacing the public Aptoria repository with the current `0.0.x` evidence-first rebuild package.

> [!WARNING]
> This is not an in-place upgrade from the legacy `1.1.34` line. Use a fresh working copy and a fresh database unless you are intentionally preserving an old archive for reference.

## Before pushing

- [ ] Back up the old repository or export it as an archive if it must be kept.
- [ ] Confirm the release package contains `README.md`, `LICENSE`, `NOTICE.md`, `CREDITS.md`, `THIRD_PARTY_NOTICES.md`, `SECURITY.md`, `.env.example`, `composer.json`, `artisan`, `VERSION`, and the transition documentation.
- [ ] Confirm runtime files are not present: `.env`, `vendor/`, `node_modules/`, `database/database.sqlite`, `storage/app/installed.lock`, `storage/app/setup-token.txt`, generated license files, runtime lease files, `public/storage/`, and `bootstrap/cache/`.
- [ ] Confirm private license issuer keys and generated customer licenses are not present.
- [ ] Confirm the public documentation describes Aptoria as source-available, not open-source.
- [ ] Confirm the current version is shown consistently in `VERSION`, `README.md`, and `CHANGELOG.md`.

## Recommended replacement flow

1. Clone the existing GitHub repository to a temporary folder.
2. Remove the old tracked application files, but keep `.git`.
3. Copy the extracted `aptoria-0.0.x` package contents into the repository root.
4. Run the public hygiene check locally where possible.
5. Review `git status` before committing.
6. Commit with a clear replacement message.
7. Push and wait for GitHub Actions to finish.

## Local smoke check

```powershell
$ProjectRoot = "C:\xampp\htdocs\aptoria"

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan aptoria:health
C:\xampp\php\php.exe artisan test
```

## After push

- [ ] GitHub Actions public hygiene passes.
- [ ] Repository landing page renders the README logo and badges correctly.
- [ ] Public files are visible at repository root.
- [ ] No runtime/local/customer files are visible in the repository.
- [ ] The project can be cloned into a clean folder and installed from the documented instructions.
