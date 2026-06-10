# Aptoria v1.1.4 – Broken Auth Comparison Scan Pass System Audit

## Scope

This release adds a safe broken-auth comparison check for auth-required GET/HEAD endpoints. When a complete auth profile is available, Aptoria runs the normal authenticated safe probe and then repeats the same non-destructive request without credentials.

## Main changes

- Auth-required GET/HEAD endpoints are compared with and without credentials.
- Unauthenticated 2xx/3xx responses are flagged as possible broken authentication.
- Unauthenticated responses with sensitive-looking data are escalated.
- Same authenticated/no-auth response fingerprints are escalated.
- Scan results store a masked comparison summary.
- Endpoint Inventory shows broken-auth state and can filter by broken-auth findings.
- Full project reports include latest scan broken-auth result counts.
- Automatic finding and masked HTTP evidence are created when broken auth is detected.

## Safety model

- Only GET/HEAD safe probes are used.
- POST/PUT/PATCH/DELETE remain blocked by the safe scan engine.
- Existing private network and localhost guards remain in place.
- Unauthenticated response previews are masked before storage.
- Values in evidence remain masked.

## QA focus

- Run a protected GET endpoint with a valid bearer/basic/custom header auth profile.
- Confirm Aptoria sends one auth request and one no-auth comparison request.
- Confirm a 401/403 no-auth response is marked protected.
- Confirm a 200 no-auth response is flagged as broken auth.
- Confirm broken-auth finding and HTTP evidence are created.
- Confirm Endpoint Inventory filter `Broken auth detected` works.
- Confirm English and Hungarian UI do not show raw translation keys.

## Release hygiene

- Release ZIP must not contain root `vendor/`.
- Release ZIP must not contain `.env`.
- Release ZIP must not contain `database/database.sqlite`.
- Release ZIP must not contain `storage/app/installed.lock`.
- Release ZIP must not contain `storage/app/setup-token.txt`.
- Bundled UI vendor assets under `public/assets/aptoria-ui/vendor` must remain included.
