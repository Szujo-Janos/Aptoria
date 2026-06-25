# Aptoria v0.0.55 – Portable USB Runtime & License Guard Foundation

## Purpose

Aptoria can now run with a native license guard layer for portable or controlled self-hosted deployments. This is not meant to be unbreakable DRM. It is a practical first protection layer that prevents a copied folder from running normally when license enforcement is enabled and the signed license is missing, expired, invalid or bound to another machine/USB drive.

## Runtime mode

Normal development installs can keep license enforcement disabled:

```env
APTORIA_LICENSE_REQUIRED=false
```

Portable/runtime starts should enable it:

```env
APTORIA_LICENSE_REQUIRED=true
```

The bundled `start-aptoria.bat` sets `APTORIA_LICENSE_REQUIRED=true` for the current process before starting Laravel's local server.

## License file location

Default license file:

```text
storage/app/aptoria-license.json
```

Default public key file:

```text
storage/app/license-public.pem
```

Both can be overridden:

```env
APTORIA_LICENSE_FILE=storage/app/aptoria-license.json
APTORIA_LICENSE_PUBLIC_KEY_PATH=storage/app/license-public.pem
APTORIA_LICENSE_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

Do not commit real customer license files or private signing keys.

## License format

A license document contains a canonical payload and a base64 RSA/SHA-256 signature over that payload:

```json
{
  "payload": {
    "license_id": "APT-PORTABLE-0001",
    "product": "aptoria",
    "edition": "portable",
    "subject": "Customer or internal owner",
    "issued_at": "2026-06-20T00:00:00Z",
    "expires_at": "2027-06-20T00:00:00Z",
    "features": [
      "portable_usb",
      "evidence_repository",
      "release_gate"
    ],
    "fingerprint_binding": {
      "mode": "machine_or_usb",
      "fingerprints": [
        "sha256:REPLACE_WITH_MACHINE_OR_USB_FINGERPRINT"
      ]
    }
  },
  "signature": "BASE64_RSA_SHA256_SIGNATURE"
}
```

Supported binding modes:

| Mode | Meaning |
|---|---|
| `none` | Signature and expiry are checked, but runtime fingerprint is not enforced. |
| `machine` | The current machine fingerprint must match. |
| `usb` | The current portable drive fingerprint must match. |
| `machine_or_usb` | Either the machine or portable drive fingerprint may match. |

## Fingerprint commands

Show current runtime fingerprints:

```powershell
C:\xampp\php\php.exe artisan aptoria:license-fingerprint
```

JSON output:

```powershell
C:\xampp\php\php.exe artisan aptoria:license-fingerprint --json
```

Check license status:

```powershell
C:\xampp\php\php.exe artisan aptoria:license-status
```

## Middleware behavior

The global `EnsureLicenseIsValid` middleware runs after setup-state checking. It skips setup, language switching, assets, `/up` and `/license/invalid`.

When enforcement is disabled, the app continues to run and the admin UI shows the current license state as a warning/information card.

When enforcement is enabled and the license is invalid, normal HTML requests are redirected to:

```text
/license/invalid
```

JSON requests receive HTTP 402 with the license state.

## Admin UI

The dashboard and Program Settings show a License Guard status card with:

- current state;
- enforcement mode;
- license ID;
- expiry;
- binding mode;
- machine fingerprint;
- USB/portable drive fingerprint.

## Portable launcher

Use:

```text
start-aptoria.bat
```

The launcher:

1. creates runtime folders if missing;
2. creates `database/database.sqlite` if missing;
3. sets `APTORIA_LICENSE_REQUIRED=true` for the process;
4. points to `storage/app/aptoria-license.json`;
5. clears caches;
6. runs migrations;
7. starts Aptoria on `http://127.0.0.1:8000`.

## Security limitations

This foundation prevents casual copying and accidental unlicensed runtime starts. It does not make PHP source code impossible to alter. A stronger commercial build can later add:

- signed build manifests;
- encrypted license payload sections;
- online activation;
- license revocation;
- obfuscated release artifacts;
- hardware-backed keys where available.

## QA checklist

- With `APTORIA_LICENSE_REQUIRED=false`, the app loads and the license status card says not enforced.
- With `APTORIA_LICENSE_REQUIRED=true` and no license file, `/dashboard` redirects to `/license/invalid`.
- `/license/invalid` shows machine and USB fingerprints.
- `aptoria:license-fingerprint` prints machine and USB fingerprints.
- `aptoria:license-status` returns failure only when enforcement is enabled and license is invalid.
- A signed license with matching fingerprint and valid expiry allows the runtime.
- An expired, malformed or mismatched license is blocked.

## v0.0.56 license request/admin management addendum

v0.0.56 adds the operational license flow around the guard:

- `get-license-request.bat` generates `license-request.json` next to the runtime.
- `php artisan aptoria:license-request --output=license-request.json` does the same from CLI.
- Admins can open **Program Settings -> License Management** to download a license request, paste the public key and upload a signed `aptoria-license.json`.
- `/license/invalid` also exposes a license request download so a blocked portable runtime can still provide the fingerprints required for issuance.

The user receives two possible artifacts from the issuer:

```text
license-public.pem      # public verification key; not secret
aptoria-license.json    # signed license bound to machine/USB fingerprints
```

The runtime stores them here:

```text
storage/app/license-public.pem
storage/app/aptoria-license.json
```

If license enforcement is already active and the admin UI is blocked, copy both files manually to those paths and restart Aptoria.

---

## v0.0.57 private issuer addendum

The portable license guard now has a matching private issuer toolkit:

```text
tools/license-issuer/
```

Use it to generate RSA keys, sign `aptoria-license.json` from a customer `license-request.json`, and verify the result before delivery.

The runtime still only verifies licenses. It does not contain the issuer private key.

See:

```text
docs/PRIVATE_LICENSE_ISSUER_TOOL.md
```
