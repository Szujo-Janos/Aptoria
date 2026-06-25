# Aptoria Private License Issuer Tool

This directory contains the **private-side issuer toolkit** for Aptoria licenses.

It is intentionally separate from the runtime License Guard. The Aptoria application can generate a license request and verify a signed license, but it must never contain the issuer private key.

## Files

```text
issue-license.php       Reads a license request and signs aptoria-license.json.
verify-license.php      Verifies a generated license with the public key.
generate-keypair.php    Creates an RSA private/public keypair for the issuer.
examples/               Non-secret sample request for documentation/testing.
```

## Safe key rule

Never commit or ship:

```text
*-private.pem
private-key.pem
keys/
out/
*.issued.json
```

The included `.gitignore` blocks common issuer secrets/output files.

## 1. Generate issuer keys

```powershell
php tools\license-issuer\generate-keypair.php --out=tools\license-issuer\keys --name=aptoria-license
```

This creates:

```text
tools/license-issuer/keys/aptoria-license-private.pem
tools/license-issuer/keys/aptoria-license-public.pem
```

Only the public key is installed into an Aptoria runtime. The private key stays with the issuer.

## 2. Receive a request

The customer/runtime generates:

```powershell
php artisan aptoria:license-request --output=license-request.json
```

or portable:

```powershell
.\get-license-request.bat
```

## 3. Issue a license

```powershell
php tools\license-issuer\issue-license.php `
  --request=license-request.json `
  --private-key=tools\license-issuer\keys\aptoria-license-private.pem `
  --output=aptoria-license.json `
  --subject="Customer Demo" `
  --issued-to="customer@example.com" `
  --edition=portable `
  --expires=2027-06-20 `
  --binding=machine_or_usb `
  --features="portable_usb,evidence_repository,import_adapter,native_test_evidence,release_gate,client_portal"
```

## 4. Verify before sending

```powershell
php tools\license-issuer\verify-license.php `
  --license=aptoria-license.json `
  --public-key=tools\license-issuer\keys\aptoria-license-public.pem `
  --request=license-request.json
```

## Binding modes

| Mode | Meaning |
|---|---|
| `machine` | License must match the machine fingerprint. |
| `usb` | License must match the portable drive fingerprint. |
| `machine_or_usb` | Either fingerprint is accepted. Recommended default for portable releases. |
| `none` | Signed license is not bound to hardware. Use only for internal/demo/trial cases. |

## Output

The generated file is the customer-facing license:

```text
aptoria-license.json
```

The customer installs it through **Program Settings → License Management** or copies it to:

```text
storage/app/aptoria-license.json
```

## Web UI for testing

A basic admin-only web interface is available while this tool still lives inside the Aptoria test build:

```text
Program Settings -> Private License Issuer
/program-settings/license-issuer
```

The web UI can generate test keypairs, issue `aptoria-license.json` from a runtime request and verify a signed license before sending it to the user. It uses the shared core in `tools/license-issuer/src/LicenseIssuerCore.php`.

This is temporary. Later, move the entire `tools/license-issuer` folder into a separate private repository/tool and exclude it from customer runtime builds.

## Windows/XAMPP OpenSSL troubleshooting

RSA key generation uses PHP OpenSSL first. On Windows/XAMPP, PHP may have OpenSSL enabled but still fail because `openssl.cnf` is not auto-discovered.

The issuer core now searches common XAMPP config paths and falls back to `openssl.exe` when possible. If it still fails, set:

```powershell
set APTORIA_OPENSSL_CONF=C:\xampp\apache\conf\openssl.cnf
```

or run from PowerShell:

```powershell
$env:APTORIA_OPENSSL_CONF = "C:\xampp\apache\conf\openssl.cnf"
```

Then retry keypair generation from the web UI or CLI.
