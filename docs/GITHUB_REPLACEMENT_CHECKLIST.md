# GitHub replacement checklist

Use this checklist before pushing the new Aptoria 0.0.53 line over the old GitHub repository.

## Repository content

- [ ] `README.md` describes v0.0.53, not the old 1.1.34 line.
- [ ] `VERSION` contains `0.0.53`.
- [ ] `LICENSE`, `NOTICE.md`, `CREDITS.md`, `SECURITY.md` and `THIRD_PARTY_NOTICES.md` are present.
- [ ] `docs/UPGRADE_FROM_1.1.34_TO_0.0.53.md` is present.
- [ ] `docs/LEGACY_1.1.34_VS_0.0.53_COMPARISON.md` is present.
- [ ] `CHANGELOG.md` starts with v0.0.53.

## Forbidden files

Confirm these are absent:

```text
.env
vendor/
node_modules/
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
public/storage/
bootstrap/cache/*.php
*.log
```

## Local checks

```powershell
cd "C:\xampp\htdocs\aptoria"

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
```

## Browser smoke test

- [ ] Setup opens on a fresh install.
- [ ] Login logo is centered.
- [ ] Default/temporary password change is enforced.
- [ ] Project Access and Users flow works.
- [ ] Evidence Repository can create/verify/archive evidence.
- [ ] Import Adapter preview works.
- [ ] Native Test Evidence can record a test run.
- [ ] QA Cockpit opens and shows coverage/blind spots.
- [ ] Release Gate can be created.
- [ ] Release Gate Decision Package can be generated and exported.
- [ ] HTML/PDF/ZIP exports open correctly.

## Recommended commit

```text
chore: replace legacy Aptoria with evidence-first rebuild
```

## Recommended release title

```text
Aptoria v0.0.53 – Evidence-first rebuild and legacy replacement
```
