# Portable Runtime and License Guard

Aptoria prepares a portable/customer runtime direction where a local installation can be activated with a signed license package.

This document describes the public-safe runtime concept. It does not include private signing procedures.

## Runtime goal

The portable runtime is intended to support:

- local/self-hosted Aptoria usage;
- simple activation package upload;
- optional online license authority checks;
- limited offline grace for guarded builds;
- clear runtime status in the UI.

## Local activation files

A valid activated runtime may contain local runtime files such as:

```text
storage/app/aptoria-license.json
storage/app/license-public.pem
storage/app/license-authority-public.pem
storage/app/license-runtime-lease.json
storage/app/license-install-id
```

These files are generated or installed locally. They must not be committed to GitHub or shipped in a generic public release ZIP.

## Activation package

The recommended customer flow uses one ZIP file:

```text
aptoria-activation.zip
```

with:

```text
aptoria-license.json
license-public.pem
```

The same package can be uploaded from:

```text
/license/activate
Program Settings -> License Management
```

## Online authority mode

For guarded portable/customer builds, Aptoria can be configured to request a runtime lease from `aptoria.dev`.

Relevant environment keys:

```env
APTORIA_LICENSE_REQUIRED=true
APTORIA_LICENSE_MODE=online_authority
APTORIA_LICENSE_AUTHORITY_URL=https://aptoria.dev
APTORIA_LICENSE_OFFLINE_GRACE_HOURS=72
```

The public runtime verifies signed authority responses with a public key. Server-side license state, revocation and runtime lease signing remain outside this public repository package.

## Public repository hygiene

Never commit:

```text
.env
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
storage/app/aptoria-license.json
storage/app/license-public.pem
storage/app/license-authority-public.pem
storage/app/license-runtime-lease.json
storage/app/license-install-id
public/storage/
bootstrap/cache/
```

## Local checks

Useful local status routes and commands may include:

```text
/license/activate
/license/status.json
/license/request.json
```

The exact deployment method depends on the target runtime and hosting model.
