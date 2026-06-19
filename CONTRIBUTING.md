# Contributing to Aptoria

Aptoria is currently a source-available MVP/foundation project. Public visibility is for review, portfolio and non-commercial local evaluation unless the license changes explicitly.

## Contribution rules

- Keep changes focused and small.
- Preserve the evidence-first product direction.
- Do not turn Aptoria into a Postman, Newman, Jira or Datadog clone.
- Keep Windows/XAMPP compatibility.
- Update documentation when workflows change.
- Do not commit runtime state, secrets, databases, setup locks or generated reports.
- Keep UI changes aligned with `docs/UI_WORKFLOW_STABILIZATION_PASS.md` and `docs/icon-registry.md`.

## Required local checks

```powershell
cd "C:\xampp\htdocs\aptoria"

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
```

## Release ZIP hygiene

Never commit or ship these files:

```text
.env
vendor/
node_modules/
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
public/storage/
bootstrap/cache/*.php
```
