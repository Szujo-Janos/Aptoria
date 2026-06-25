# License Activation Recovery Flow

Aptoria keeps one public activation page outside the protected app modules:

```text
/license/activate
```

The page is intentionally simple. In normal use the user uploads **one activation package** and continues.

## Recommended activation package

The private license issuer should send one ZIP file containing:

```text
aptoria-license.json
license-public.pem
```

The user uploads that single ZIP file on `/license/activate`. Aptoria extracts the public key, validates the signed license, installs both files, then redirects to login/dashboard.

## Supported package formats

Aptoria accepts:

- ZIP package with `aptoria-license.json` and `license-public.pem`
- JSON package with:
  - `public_key`
  - `license`
- plain signed `aptoria-license.json` only when the public key is already installed

## Manual fallback

The same files can still be copied manually:

```text
storage/app/license-public.pem
storage/app/aptoria-license.json
```

This remains useful for locked-down or portable runtime setups.

## License Management inside the app

Program Settings → License Management uses the same simplified workflow:

1. Download license request.
2. Send it to the license issuer.
3. Receive one activation package.
4. Upload that package.

Separate public key / signed license upload is still available, but only under Advanced manual install.
