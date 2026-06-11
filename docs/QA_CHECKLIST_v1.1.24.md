# Aptoria v1.1.24 QA Checklist

Release: **v1.1.24 - Evidence Graph Pass**
ZIP: `aptoria-1.1.24.zip`

## Required checks

- [ ] Install from `aptoria-1.1.24.zip` using the documented PowerShell template.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan test`.
- [ ] Open **Project → Evidence Graph**.
- [ ] Confirm endpoint evidence maps show scan results, assertions, findings and evidence counts.
- [ ] Open an endpoint evidence map and confirm missing scan/assertion links are shown when absent.
- [ ] Open a finding evidence chain and confirm linked endpoint, scan/contract evidence, retest evidence and accepted risk ledger status are shown.
- [ ] Open **Release Evidence Graph** and confirm scan, snapshot, release gate, release decision, accepted risk and blind spot nodes are visible.
- [ ] Export a Full QA Report and confirm **Evidence Graph Summary** appears.

## Release ZIP exclusions

- [ ] No root `vendor/` directory.
- [ ] No root `.env` file.
- [ ] No `database/database.sqlite`.
- [ ] No `storage/app/installed.lock`.
- [ ] No `storage/app/setup-token.txt`.
- [ ] `public/assets/aptoria-ui/vendor` remains included.
