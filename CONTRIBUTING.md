# Contributing to Aptoria

Aptoria is currently a post-MVP / early beta project.

The repository may be public for review, portfolio and source-available evaluation, but Aptoria is not an open-source project unless the owner later changes the license explicitly.

## Contribution rules

Before opening a pull request:

- keep changes focused and small;
- describe the QA workflow or user-facing behavior affected;
- update documentation when behavior changes;
- do not commit local runtime state;
- do not commit secrets, API tokens, passwords or production endpoint data;
- do not introduce Node/Tailwind/React/build-chain requirements unless that direction is approved first;
- preserve Windows/XAMPP compatibility.

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

Never commit or ship these files in a release ZIP:

```text
.env
vendor/
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
public/storage/
bootstrap/cache/*.php
```

Release ZIPs must remain cumulative and must keep the runtime application installable on Windows/XAMPP.
