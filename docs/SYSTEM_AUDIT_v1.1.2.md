# Aptoria v1.1.2 – Environment Manager Pass System Audit

## Scope

This release adds a dedicated project Environment Manager on top of the cumulative v1.1.1 endpoint inventory build.

## Added

- Project Environment Manager page.
- Environment type support: local, dev, staging, production, custom.
- Default environment selection from the manager.
- Environment-level auth profile display and default handoff.
- Environment usage metrics: endpoints, scan runs and snapshots.
- Environment matrix section in full project report output.
- English and Hungarian localization for environment manager labels.
- Regression coverage through `EnvironmentManagerTest`.

## Release hygiene

The release ZIP must not contain `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` or `storage/app/setup-token.txt`. Bundled UI vendor assets under `public/assets/aptoria-ui/vendor` remain included.

## Manual QA

1. Open a project.
2. Open Project → Environments.
3. Create dev/staging/production environments.
4. Set one environment as default.
5. Assign an auth profile to an environment.
6. Confirm project details and full project reports include environment context.
7. Run `php artisan test`.
