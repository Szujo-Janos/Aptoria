# License Activation Package Workflow

This document describes the public, customer-facing Aptoria license activation flow.

Aptoria uses a signed activation package so a portable/customer runtime can be activated without exposing private signing material.

## Public activation flow

```text
license request -> activation package -> upload -> verified runtime
```

1. The user downloads a license request from Aptoria.
2. The request is sent to the product owner / license authority.
3. The user receives one activation package.
4. The package is uploaded on `/license/activate` or in Program Settings -> License Management.
5. Aptoria verifies the package and continues when the license is valid.

## Recommended activation package

The recommended package is a ZIP file:

```text
aptoria-activation.zip
```

It should contain:

```text
aptoria-license.json
license-public.pem
```

Aptoria installs and verifies these files automatically. The user should not need to paste keys or upload multiple files during normal activation.

## Supported public package formats

Aptoria accepts:

- ZIP package with `aptoria-license.json` and `license-public.pem`;
- JSON package containing `public_key` and `license`;
- plain signed `aptoria-license.json` only when the public key has already been installed.

## Recovery page

If license enforcement blocks the protected workspace, Aptoria keeps this route available:

```text
/license/activate
```

This prevents a valid user from being locked out of activation.

## Admin management

After activation, administrators can review license status under:

```text
Program Settings -> License Management
```

The normal workflow remains one activation package upload. Separate manual public-key/license upload is kept only as an advanced fallback.

## Online authority direction

Guarded portable/customer builds may also be configured to request a short-lived signed runtime lease from `aptoria.dev`.

The public runtime can cache this lease for a configured offline grace period. The authority-side private signing material and license registry are not part of this repository package.

See:

- `docs/ONLINE_LICENSE_AUTHORITY_CLIENT.md`
- `docs/LICENSE_ACTIVATION_RECOVERY_FLOW.md`

## Public repository rule

Do not commit runtime activation files:

```text
storage/app/aptoria-license.json
storage/app/license-public.pem
storage/app/license-authority-public.pem
storage/app/license-runtime-lease.json
storage/app/license-install-id
```
