# Aptoria License Issuance Flow

Current version: **v0.0.57**

This document describes how an Aptoria user receives, installs and validates a signed license file.

## What v0.0.56 adds

v0.0.55 introduced the license guard runtime check. v0.0.56 makes it usable by adding:

- a license request JSON format;
- a public/admin license management screen;
- license request download;
- signed license upload;
- public key installation;
- a `get-license-request.bat` helper;
- an `aptoria:license-request` artisan command.

The private signing key must stay outside the Aptoria runtime and outside the public repository.

## User workflow

### 1. Generate a license request

From the admin UI:

```text
Program Settings -> License Management -> Download license request
```

Or from a portable package:

```powershell
.\get-license-request.bat
```

Or manually:

```powershell
C:\xampp\php\php.exe artisan aptoria:license-request --output=license-request.json
```

The request contains hashed machine and portable-drive fingerprints. It does not unlock the application by itself.

### 2. Send the request to the license issuer

The user sends `license-request.json` to the license issuer.

The request contains:

- product and version;
- requested edition;
- PHP/OS runtime metadata;
- license file path hints;
- machine fingerprint;
- USB / portable drive fingerprint;
- requested feature list.

It intentionally avoids raw hostname and raw volume details.

### 3. Issuer signs a license

The issuer uses a private tool and private key to sign a license payload.

The output file must be named or delivered as:

```text
aptoria-license.json
```

The private key must never be copied into the runtime package.

### 4. User installs the public key

If the public key is not already installed through `.env`, the admin opens:

```text
Program Settings -> License Management
```

Then pastes the PEM public key into the **License public key** form.

The public key is stored at:

```text
storage/app/license-public.pem
```

The public key is not secret. It only verifies signatures.

### 5. User uploads the signed license file

On the same License Management page, upload the signed `aptoria-license.json` file.

The application validates the file before storing it:

- valid JSON;
- payload and signature fields exist;
- product is `aptoria`;
- public key is available;
- RSA/SHA-256 signature is valid;
- expiry date is valid and not expired;
- machine/USB binding matches the current runtime.

If valid, it is stored at:

```text
storage/app/aptoria-license.json
```

Existing license files are backed up with a timestamp suffix before replacement.

### 6. Enable enforcement

For portable runtime, `start-aptoria.bat` enables:

```env
APTORIA_LICENSE_REQUIRED=true
```

For a server install, set this in `.env`:

```env
APTORIA_LICENSE_REQUIRED=true
```

Then clear caches:

```powershell
C:\xampp\php\php.exe artisan optimize:clear
```

## Recovery when the runtime is blocked

When license enforcement is enabled and the license is invalid, Aptoria redirects to:

```text
/license/invalid
```

That page allows the user to download a fresh license request JSON.

If the admin UI is blocked, manually copy the files:

```text
storage/app/license-public.pem
storage/app/aptoria-license.json
```

Then restart the runtime.

## License request command

```powershell
C:\xampp\php\php.exe artisan aptoria:license-request
C:\xampp\php\php.exe artisan aptoria:license-request --output=license-request.json
```

## License status command

```powershell
C:\xampp\php\php.exe artisan aptoria:license-status
C:\xampp\php\php.exe artisan aptoria:license-status --json
```

## Fingerprint command

```powershell
C:\xampp\php\php.exe artisan aptoria:license-fingerprint
C:\xampp\php\php.exe artisan aptoria:license-fingerprint --json
```

## Security boundary

This is a guard layer, not unbreakable DRM. Since Aptoria is PHP code, a determined attacker with source access can remove checks. The goal is to stop casual copying, make portable packages accountable, and keep every official runtime tied to a signed license artifact.

---

## v0.0.57 private issuer addendum

`v0.0.57` adds the private-side issuer toolkit under:

```text
tools/license-issuer/
```

This toolkit is for the license issuer, not for normal runtime users. It contains:

```text
issue-license.php
generate-keypair.php
verify-license.php
examples/license-request.example.json
```

Typical issuer flow:

```powershell
php tools\license-issuer\generate-keypair.php --out=tools\license-issuer\keys --name=aptoria-license

php tools\license-issuer\issue-license.php `
  --request=license-request.json `
  --private-key=tools\license-issuer\keys\aptoria-license-private.pem `
  --output=aptoria-license.json `
  --subject="Customer Demo" `
  --edition=portable `
  --expires=2027-06-20 `
  --binding=machine_or_usb

php tools\license-issuer\verify-license.php `
  --license=aptoria-license.json `
  --public-key=tools\license-issuer\keys\aptoria-license-public.pem `
  --request=license-request.json
```

The private key must stay outside public/customer packages. Only the public key and signed `aptoria-license.json` are installed into Aptoria runtimes.
