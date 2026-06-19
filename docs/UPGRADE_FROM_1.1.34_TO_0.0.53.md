# Replacing Aptoria 1.1.34 with Aptoria 0.0.53

This is the official transition note for replacing the old Aptoria 1.1.34 package with the current 0.0.53 rebuild.

## Decision

The old `1.1.34` line is discarded as the active codebase.

The new `0.0.53` line becomes the active GitHub baseline.

This is a **fresh replacement**, not a database migration.

## Why replacement instead of upgrade?

The new line changes the product core:

- from broad API QA / monitor / report workflow;
- to evidence-first API QA and release decision support;
- with project access, evidence repository, import adapters, native test evidence, QA cockpit, release gates and decision packages.

The database structure, workflow assumptions, UI shell, permissions and report/export logic are different enough that an in-place upgrade would be unsafe.

## What to do with old deployments

Before deleting or overwriting an old deployment:

1. Back up the old project folder.
2. Back up the old database.
3. Export any important reports, evidence packs or screenshots.
4. Save any endpoint lists or OpenAPI/CSV artifacts that can be re-imported.
5. Keep the old ZIP as an archive only.

## Replacement flow for local Windows/XAMPP deployment

Use a new clean project root or clear the old one intentionally.

```powershell
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-0.0.53-github-transition.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_0.0.53_github"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue
Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force
Copy-Item "$TempPath\aptoria\*" $ProjectRoot -Recurse -Force

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate

C:\xampp\php\php.exe artisan test

C:\xampp\php\php.exe artisan serve
```

## GitHub repository replacement flow

One practical approach:

```powershell
cd "E:\GitHub projects\Aptoria\repo"

git checkout -b replace-legacy-with-0.0.53

git rm -r .
# Copy the contents of the new aptoria/ folder into the repository root.

git add .
git commit -m "chore: replace legacy Aptoria with evidence-first rebuild"
git tag v0.0.53
```

Then review the repository before pushing:

```powershell
git status
git diff --stat HEAD~1..HEAD
```

## Do not carry these over from 1.1.34

Do not copy these from the old folder into the new repo:

```text
.env
vendor/
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
public/storage/
bootstrap/cache/*.php
old generated reports
old local evidence exports
old customer/project runtime data
```

## What may be manually re-used

These can be manually reintroduced if still relevant:

- endpoint CSV files;
- OpenAPI JSON contracts;
- QA CSV files;
- Postman/Newman output artifacts;
- public documentation ideas;
- screenshots for portfolio use, if they contain no secrets/customer data.

## First smoke test after replacement

1. Open the app.
2. Complete setup.
3. Log in with the setup admin.
4. Change the temporary/default password.
5. Create or import a project.
6. Add endpoints or import OpenAPI/QA CSV data.
7. Create evidence or native test evidence.
8. Open QA Cockpit.
9. Create a Release Gate.
10. Generate a Release Gate Decision Package.
11. Export HTML/PDF/ZIP.

## Known limitation

No automatic legacy database migration is included in this replacement package.
