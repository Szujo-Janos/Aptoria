# Aptoria v1.1.23 QA Checklist

Release: **v1.1.23 - API Behavior Map Pass**
ZIP: `aptoria-1.1.23.zip`

## Install / migration

- [ ] Install from `aptoria-1.1.23.zip` using the documented PowerShell template.
- [ ] Run `php artisan migrate`.
- [ ] Run `php artisan optimize:clear`, `view:clear`, `config:clear`, `route:clear`.
- [ ] Run `php artisan test`.

## API Behavior Map

1. Create or import endpoints such as `POST /orders`, `GET /orders/{id}`, `PATCH /orders/{id}` and `DELETE /orders/{id}`.
2. Open **Project → API Behavior Map**.
3. Confirm producer, consumer, dependency, destructive and sequence candidate counters are visible.
4. Confirm `POST /orders → GET /orders/{id}` appears as a path parameter dependency.
5. Confirm `DELETE /orders/{id}` is marked as destructive.
6. Open an endpoint detail page and confirm the **Endpoint behavior** panel appears.
7. Use **Refresh behavior map** and confirm the page returns without errors.
8. Export a Full QA Report and confirm **API Behavior Map Summary** is present when the section is selected.

## Release ZIP exclusions

- [ ] No root `vendor/` directory.
- [ ] No root `.env` file.
- [ ] No `database/database.sqlite`.
- [ ] No `storage/app/installed.lock`.
- [ ] No `storage/app/setup-token.txt`.
- [ ] `public/assets/aptoria-ui/vendor` remains included.
