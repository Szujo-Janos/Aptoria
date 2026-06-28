# Aptoria License Issuer

This folder is the separate Aptoria license issuer intended for `admin.aptoria.dev` / `license.aptoria.dev` deployment.

Point the subdomain document root to:

```text
license-issuer/public
```

Do not point the public web root to the repository root or to the customer Aptoria application.

## Host separation

v0.0.71 adds explicit host-boundary enforcement for shared issuer deployments.

Configure the allowed admin and API hosts in `config.php`:

```php
'admin_hosts' => ['admin.aptoria.dev'],
'api_hosts' => ['license.aptoria.dev'],
```

Expected behavior:

```text
admin.aptoria.dev   -> issuer admin UI only
license.aptoria.dev -> /api/license/* only
```

So these must be true:

```text
https://admin.aptoria.dev/login                         allowed
https://admin.aptoria.dev/api/license/runtime-lease     404
https://license.aptoria.dev/api/license/authority/status allowed
https://license.aptoria.dev/login                       404
https://license.aptoria.dev/registry/export             404
```

Unknown/local hosts remain usable for local testing unless they are listed in `admin_hosts` or `api_hosts`.

## Setup

1. Copy `config.example.php` to `config.php`.
2. Set `admin_password_hash`.
3. Confirm `admin_hosts` and `api_hosts` match the deployed subdomains.
4. Make `license-issuer/storage` writable.
5. Open `admin.aptoria.dev` and generate the signing key pair.
6. Import a customer `license-request.json` from Aptoria License Management.
7. Create or update the license record.
8. Download the activation package and send it to the customer.
9. Point customer Aptoria runtime lease checks to `https://license.aptoria.dev/api/license/runtime-lease`.

Generated private/runtime files must stay out of Git:

```text
license-issuer/config.php
license-issuer/storage/license-authority-private.pem
license-issuer/storage/license-authority-public.pem
license-issuer/storage/license-authority-registry.json
```

## Public API

The API host serves only the runtime lease API:

```text
GET  /api/license/authority/status
POST /api/license/runtime-lease
```

## Hardening added in v0.0.74

The runtime authority API now includes:

- request size limit;
- JSON content-type enforcement;
- file-based rate limiting;
- strict runtime lease request validation;
- safe machine-readable error codes with `request_id`;
- signed lease payloads with canonical `request_hash` and policy metadata;
- API request logs and rejected request logs;
- issuer admin audit logs;
- no PHP session cookie for `/api/license/*` responses;
- basic Apache security headers in `public/.htaccess`.

Config options:

```php
'max_request_bytes' => 32768,
'require_json_content_type' => true,
'rate_limit_enabled' => true,
'rate_limit_window_seconds' => 60,
'rate_limit_max_requests' => 60,
'trust_proxy_headers' => false,
```

Logs are written below `license-issuer/storage/logs/`:

```text
authority-requests.jsonl
authority-abuse.jsonl
issuer-admin-audit.jsonl
```

These logs must not be published or committed.

See also:

```text
docs/LICENSE_AUTHORITY_HARDENING.md
```

## Deployment smoke checks added in v0.0.75

After DNS, HTTPS and `config.php` are in place, verify issuer host boundaries from the repository root:

```powershell
.\scripts\smoke-subdomains.ps1
```

or:

```bash
bash scripts/smoke-subdomains.sh
```

Issuer-specific expected results:

```text
https://license.aptoria.dev/api/license/authority/status -> 200
https://license.aptoria.dev/login                       -> 404
https://license.aptoria.dev/api/license/runtime-lease    -> 415 for text/plain smoke POST
https://admin.aptoria.dev/login                         -> 200
https://admin.aptoria.dev/api/license/authority/status   -> 404
```

See:

```text
docs/SUBDOMAIN_DEPLOYMENT.md
docs/HOSTING_CHECKLIST.md
docs/DEPLOYMENT_SMOKE_TESTS.md
```

## Runtime diagnostics added in v0.0.77

After logging in on `admin.aptoria.dev`, open:

```text
/diagnostics
/diagnostics.json
```

The diagnostics page validates:

- `admin_hosts` and `api_hosts` are configured;
- admin and API host lists do not overlap;
- authority URL uses HTTPS;
- runtime lease endpoint is `/api/license/runtime-lease`;
- `admin_password_hash` is configured;
- issuer storage is writable;
- signing keys and registry are present;
- rate limiting and JSON content-type enforcement are enabled.

These diagnostics routes must stay unavailable on the API-only host:

```text
https://license.aptoria.dev/diagnostics      -> 404
https://license.aptoria.dev/diagnostics.json -> 404
```


## API method behavior

`POST /api/license/runtime-lease` is the runtime lease endpoint. `GET /api/license/runtime-lease` returns JSON `method_not_allowed` with HTTP 405. Unknown `/api/license/*` paths return JSON `not_found` with HTTP 404. API paths must not redirect to `/login`.
