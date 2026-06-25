# Aptoria Private License Issuer Web UI

Version: v0.0.57 hotfix

## Purpose

The private license issuer tool now has a basic admin-only web interface inside Aptoria for testing. The tool remains physically separated under:

```text
tools/license-issuer/
```

This keeps the issuer logic easy to move into a separate private repository later.

## Admin route

```text
Program Settings -> Private License Issuer
/program-settings/license-issuer
```

Only system admins can open this screen.

## What the web UI can do

- Generate an RSA issuer keypair under `tools/license-issuer/keys`.
- Issue `aptoria-license.json` from an Aptoria license request JSON.
- Verify a signed license before sending it to a runtime user.
- Download the generated issuer public key.
- Show the current runtime license request preview for local testing.

## Safety rules

The private key is still private issuer material.

Never ship these to customers:

```text
tools/license-issuer/keys/
tools/license-issuer/out/
*-private.pem
*.issued.json
aptoria-license.json
```

The current source/testing ZIP may include the issuer scripts and web UI, but generated key and output folders remain runtime-only and excluded.

## Test flow

1. Open **Program Settings -> Private License Issuer**.
2. Generate a test keypair.
3. Copy/download the public key and install it under **License Management**.
4. Use the current request preview or upload a request file.
5. Issue a license.
6. Verify the generated license with the public key and original request.
7. Upload the license under **License Management**.

## Later split

When the license workflow is stable, move this folder out of customer/runtime builds:

```text
tools/license-issuer/
```

The runtime should keep only:

- license request generation;
- public key installation;
- signed license upload;
- signature/fingerprint validation.

## Windows/XAMPP OpenSSL key generation note

If the web UI shows `Unable to generate RSA keypair`, the most common cause is not a missing issuer feature, but PHP OpenSSL not finding `openssl.cnf` under Windows/XAMPP.

The issuer now tries, in order:

1. PHP OpenSSL with the default configuration.
2. PHP OpenSSL with `APTORIA_OPENSSL_CONF` or `OPENSSL_CONF`.
3. Common XAMPP locations such as:
   - `C:\xampp\apache\conf\openssl.cnf`
   - `C:\xampp\apache\bin\openssl.cnf`
   - `C:\xampp\php\extras\ssl\openssl.cnf`
4. The external `openssl` / `openssl.exe` binary as a fallback.

If automatic detection still fails, set one of these environment variables before starting Aptoria:

```powershell
$env:APTORIA_OPENSSL_CONF = "C:\xampp\apache\conf\openssl.cnf"
```

or in `.env` for a persistent local configuration:

```env
APTORIA_OPENSSL_CONF=C:\xampp\apache\conf\openssl.cnf
```

Then clear caches and restart the local server.
