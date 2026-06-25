# Aptoria v0.0.60 – Sandbox API workspace Foundation

## Purpose

The Sandbox API workspace lets visitors test Aptoria against a real JSON API target instead of only looking at a prebuilt dashboard.

The intended public setup is:

```text
aptoria.dev          marketing / product site
demo.aptoria.dev     Aptoria demo UI
api-demo.aptoria.dev  optional standalone demo API target later
```

In v0.0.58 the demo API is built into the Aptoria Laravel app under:

```text
/demo-api/*
```

This keeps the first public trial cheap and easy to host. Later, the same endpoint contract can be moved to `api-demo.aptoria.dev`.

## Demo API endpoints

```text
GET /demo-api/health
GET /demo-api/users
GET /demo-api/users/1
GET /demo-api/orders
GET /demo-api/orders/1001
GET /demo-api/products
GET /demo-api/reports/summary
GET /demo-api/security/public-profile
GET /demo-api/security/private-account
GET /demo-api/security/leaky-token-example
GET /demo-api/errors/server-error
GET /demo-api/errors/slow-response
GET /demo-api/scenarios
GET /demo-api/scenarios/first-smoke-scan
GET /demo-api/scenarios/security-leak-review
GET /demo-api/scenarios/artifact-import-trace
GET /demo-api/scenarios/release-gate-decision
GET /demo-api/scenarios/release-gate-decision/evidence.json
```

Protected endpoint:

```http
GET /demo-api/security/private-account
Authorization: Bearer <demo-token>
```

Intentional demo problems:

- `/security/leaky-token-example` exposes synthetic token-like fields.
- `/errors/server-error` returns HTTP 500.
- `/errors/slow-response` sleeps for more than one second.

## Demo artifacts

```text
GET /demo-api/artifacts/openapi.json
GET /demo-api/artifacts/postman-collection.json
GET /demo-api/artifacts/qa-results.csv
GET /demo-api/artifacts/jira-issues.csv
GET /demo-api/artifacts/browser-network.har
GET /demo-api/artifacts/scenario-templates.json
```

These are designed for the Import Adapter Layer.

## Building the project

From the admin UI:

```text
Program Settings → Sandbox API workspace → Build sandbox API project
```

From CLI:

```powershell
C:\xampp\php\php.exe artisan aptoria:demo-api-project
```

This creates:

- `Aptoria Sandbox API workspace` project;
- sandbox environment;
- public and bearer auth profiles;
- endpoint inventory;
- guided scenario template endpoints;
- assertion rules;
- verified repository evidence;
- native test suite and test runs;
- intentional finding examples;
- read-only demo user.

Demo viewer account:

```text
demo@aptoria.dev
aptoria-demo-2026
```

## Public demo mode

Enable demo mode in `.env` on public demo deployments:

```env
APTORIA_DEMO_MODE=true
APTORIA_DEMO_API_BASE_URL=https://demo.aptoria.dev/demo-api
APTORIA_DEMO_API_TOKEN=aptoria-demo-token
APTORIA_DEMO_ALLOWED_TARGETS=demo.aptoria.dev,api-demo.aptoria.dev
```

When `APTORIA_DEMO_MODE=true`, selected destructive/admin operations are blocked, including user management, license management, license administration, project deletes, client portal mutation and membership mutation.

Safe scan target restriction is controlled by `APTORIA_DEMO_ALLOWED_TARGETS`. When set, safe scan blocks targets outside the configured host list.

## Recommended public demo workflow

1. Deploy the Aptoria demo app to `demo.aptoria.dev`.
2. Set `APTORIA_DEMO_MODE=true`.
3. Set `APTORIA_DEMO_ALLOWED_TARGETS=demo.aptoria.dev` for the first same-app sandbox.
4. Run migrations.
5. Run `php artisan aptoria:demo-api-project`.
6. Share the demo viewer account from the login page.
7. Let visitors run Safe Scan against the prebuilt Sandbox API workspace.

## Security notes

Do not allow unrestricted scanning from a public demo. Public demo deployments must restrict safe scan targets through `APTORIA_DEMO_ALLOWED_TARGETS`.

Do not expose the License administration in public demo mode. The demo middleware blocks it by default.


## Guided scenario templates

Added in v0.0.60, the sandbox exposes structured demo scenarios through `/demo-api/scenarios`. The public `/demo-guide` page uses these templates to give visitors a concrete path through the product instead of a generic feature list.

Scenario slugs:

```text
first-smoke-scan
security-leak-review
artifact-import-trace
release-gate-decision
```

Each scenario also has an evidence run sheet endpoint at `/demo-api/scenarios/{slug}/evidence.json`.
