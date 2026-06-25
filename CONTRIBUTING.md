# Contributing to Aptoria

Aptoria is currently a **source-available MVP / foundation project**. Public visibility is intended for review, portfolio presentation and non-commercial local evaluation unless the license changes explicitly.

> [!IMPORTANT]
> Contributions should preserve Aptoria's evidence-first API QA and release-decision direction. Do not reshape the project into a Postman, Newman, Jira, Datadog or full test-management clone.

## Contribution principles

| Principle | Expectation |
| --- | --- |
| Scope | Keep changes focused and small. |
| Product direction | Preserve the evidence-first workflow. |
| Runtime target | Keep Windows/XAMPP compatibility. |
| Documentation | Update docs when workflows, setup, exports or permissions change. |
| Repository hygiene | Do not commit runtime state, secrets, databases, setup locks or generated reports. |
| UI consistency | Keep UI changes aligned with `docs/UI_WORKFLOW_STABILIZATION_PASS.md` and `docs/icon-registry.md`. |

## Before opening a pull request

- [ ] The change is small enough to review.
- [ ] The evidence-first product direction is preserved.
- [ ] Windows/XAMPP compatibility has not been broken.
- [ ] UI changes follow the Aptoria card/table/modal/action patterns.
- [ ] Icon changes use meaningful semantic icons, not generic repeated placeholders.
- [ ] Documentation was updated when behavior changed.
- [ ] No runtime files, secrets, generated exports or local databases are included.
- [ ] Local checks have been run.

## Required local checks

Run these from the local Aptoria project root:

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
storage/app/aptoria-license.json
storage/app/license-public.pem
public/storage/
bootstrap/cache/*.php
```

## Documentation rules

When changing a workflow, also review the relevant documentation:

| Area | Documentation |
| --- | --- |
| Windows/XAMPP install | `docs/INSTALL_WINDOWS_XAMPP.md`, `SERVER_INSTALLER.md` |
| UI behavior | `docs/UI_WORKFLOW_STABILIZATION_PASS.md` |
| Icons | `docs/icon-registry.md` |
| Evidence repository | `docs/EVIDENCE_REPOSITORY_FOUNDATION.md` |
| Import adapters | `docs/IMPORT_ADAPTER_LAYER.md` |
| Release gates | `docs/RELEASE_GATE_WORKFLOW_FOUNDATION.md`, `docs/RELEASE_GATE_REPORT_DECISION_PACKAGE.md` |
| Report exports | `docs/REPORT_VISUAL_STANDARD.md` |

## Security and privacy

Do not include live credentials, private customer data, production API request samples, bearer tokens, basic-auth credentials, SQLite runtime databases or generated evidence/report exports in public changes.
