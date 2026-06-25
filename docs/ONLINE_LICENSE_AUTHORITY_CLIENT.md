# Online License Authority Client Foundation

Aptoria now prepares the guarded portable runtime direction where the customer-side app can ask `aptoria.dev` for a short-lived signed runtime lease.

This is not a full DRM solution. If PHP source is shipped to a customer, a determined attacker can still modify code. The goal is to make casual copy/edit bypasses much harder by moving the decisive license state to a server controlled by the product owner.

## Modes

```text
APTORIA_LICENSE_MODE=local_package
APTORIA_LICENSE_MODE=online_authority
APTORIA_LICENSE_MODE=hybrid
```

- `local_package`: local signed activation package only.
- `online_authority`: local license must be valid and a signed runtime lease from the authority is required.
- `hybrid`: same client foundation, intended for later mixed policy rules.

Runtime blocking still depends on:

```text
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

## Runtime lease request

When a guarded runtime needs verification, Aptoria sends a lease request containing:

- product and app version;
- license ID and edition;
- install ID;
- machine and USB fingerprints;
- runtime metadata;
- basic manifest hash for important runtime files.

The request does not include private signing keys.

## Expected authority response

The authority should return a signed JSON object:

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

The signature is verified locally with the authority public key. The private signing key remains on `aptoria.dev`.

## Offline grace

If the lease expires but the cached lease is still inside the configured grace period, the runtime can continue in `offline_grace` state.

Default:

```text
APTORIA_LICENSE_OFFLINE_GRACE_HOURS=72
```

## Runtime files

Do not commit these files:

```text
storage/app/license-authority-public.pem
storage/app/license-runtime-lease.json
storage/app/license-install-id
```

## Next server-side work

The `aptoria.dev` License Authority server should provide:

- license registry;
- device activation registry;
- runtime lease signing;
- revocation;
- heartbeat / last-seen tracking;
- optional manifest evaluation;
- admin UI for license/device status.
