# Online License Authority Client Foundation

Aptoria prepares an optional online license authority direction for guarded portable/customer runtimes.

In this model, the local Aptoria runtime can ask `aptoria.dev` for a short-lived signed runtime lease. The goal is to keep the decisive license state on infrastructure controlled by the product owner while keeping the customer-side activation workflow simple.

## License modes

```env
APTORIA_LICENSE_MODE=local_package
APTORIA_LICENSE_MODE=online_authority
APTORIA_LICENSE_MODE=hybrid
```

- `local_package`: local signed activation package only.
- `online_authority`: local license plus online runtime lease verification.
- `hybrid`: reserved for mixed policy rules.

Runtime blocking is controlled by:

```env
APTORIA_LICENSE_REQUIRED=true
```

## Authority configuration

```env
APTORIA_LICENSE_AUTHORITY_URL=https://aptoria.dev
APTORIA_LICENSE_AUTHORITY_LEASE_ENDPOINT=/api/license/runtime-lease
APTORIA_LICENSE_AUTHORITY_TIMEOUT_SECONDS=8
APTORIA_LICENSE_OFFLINE_GRACE_HOURS=72
APTORIA_LICENSE_AUTHORITY_PUBLIC_KEY=
APTORIA_LICENSE_AUTHORITY_PUBLIC_KEY_PATH=storage/app/license-authority-public.pem
APTORIA_LICENSE_RUNTIME_LEASE_FILE=storage/app/license-runtime-lease.json
```

## Runtime lease concept

A runtime lease is a short-lived signed permission document. The local runtime can cache it for a limited offline grace period.

The authority response should include:

```json
{
  "payload": {
    "lease_id": "lease_...",
    "license_id": "APT-...",
    "status": "valid",
    "issued_at": "2026-06-25T12:00:00Z",
    "valid_until": "2026-06-26T12:00:00Z"
  },
  "signature": "base64-signature"
}
```

The local runtime verifies the signature with the configured authority public key.

## Offline grace

If the authority is temporarily unavailable, Aptoria can continue only while a previously valid cached lease remains inside the configured grace period.

Default:

```env
APTORIA_LICENSE_OFFLINE_GRACE_HOURS=72
```

## Runtime files

Do not commit these files:

```text
storage/app/license-authority-public.pem
storage/app/license-runtime-lease.json
storage/app/license-install-id
```

## Server-side authority scope

The `aptoria.dev` authority side is expected to manage:

- license registry;
- allowed devices/fingerprints;
- activation limits;
- revocation;
- runtime lease signing;
- heartbeat / last-seen tracking;
- optional runtime manifest evaluation.

Authority-side private signing material and customer license records are not part of this public repository package.
