# Aptoria v1.1.27 QA Checklist – Client Audit Portal Pass

## Install / migration

- [ ] Install `aptoria-1.1.27.zip` with the documented PowerShell template.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan test`.
- [ ] Confirm the release ZIP does not contain `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` or `storage/app/setup-token.txt`.
- [ ] Confirm `public/assets/aptoria-ui/vendor` is still included.

## Client portal workflow

1. Open a project and go to **Project → Client Portal**.
2. Create a **Client viewer** portal link.
3. Open the generated public token URL in a private browser window.
4. Confirm it shows only approved reports, release decisions, accepted risks, finding summary and evidence exports for that project.
5. Confirm draft/unapproved report versions are not visible from the portal.
6. Create a **Client approver** portal link.
7. Confirm report, release decision and accepted risk acknowledgement buttons are visible.
8. Submit acknowledgements and verify they appear in the portal acknowledgement timeline.
9. Revoke the portal link from the admin page.
10. Confirm the revoked token URL returns 404 / is unavailable.

## Security / isolation checks

- [ ] Portal token cannot open another project's report version.
- [ ] Portal token cannot open draft report versions.
- [ ] Portal token cannot expose settings, auth profiles, debug data or admin screens.
- [ ] Evidence package routes respect the `evidence_package` permission.
- [ ] Report acknowledgement requires the `approve_reports` permission.
- [ ] Release acknowledgement requires the `acknowledge_release` permission.
- [ ] Accepted risk acknowledgement requires the `approve_risks` permission.
