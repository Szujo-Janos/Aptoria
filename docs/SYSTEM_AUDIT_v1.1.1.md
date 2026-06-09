# Aptoria v1.1.1 System Audit – Endpoint Inventory Pass

## Scope

This release adds a dedicated project Endpoint Inventory page for audit-oriented endpoint catalogue review.

## Implemented

- New `EndpointInventoryController` and `EndpointInventoryService`.
- New route: `/projects/{project}/endpoint-inventory`.
- New view: `resources/views/endpoints/inventory.blade.php`.
- Project sidebar and project detail links to Endpoint Inventory.
- Summary metrics: total endpoints, scan coverage, risk review queue, open findings, auth-required endpoints and average response time.
- Filters: search, method, risk, environment, auth state, scan state, finding state, coverage gap, source, endpoint status and sort.
- Columns: method, path, environment, auth, risk, latest scan, HTTP status, response time, open findings, source, coverage flags and actions.
- English/Hungarian localization.
- Regression test: `EndpointInventoryTest`.

## Safety

The feature is read-only except for existing probe/detail/edit actions. It does not send API requests until the existing explicit probe button is clicked.

## Release hygiene

The release ZIP must not include root `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` or `storage/app/setup-token.txt`.
