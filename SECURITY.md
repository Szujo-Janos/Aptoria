# Security Policy

Aptoria v1.0.2 is a clean public source package containing the application and license manager source only.

## Supported version

| Version | Status |
| --- | --- |
| `v1.0.2` | Current public clean package |

## Sensitive files that must not be published

Do not commit or distribute runtime secrets, production license material or generated local state:

```text
.env
database/database.sqlite
storage/app/aptoria-license.json
storage/app/license-public.pem
storage/app/license-private.pem
storage/app/license-authority-private.pem
storage/app/license-authority-registry.json
storage/app/license-runtime-lease.json
storage/app/license-install-id
storage/app/installed.lock
storage/app/setup-token.txt
license-issuer/config.php
license-issuer/storage/*.json
license-issuer/storage/*.jsonl
license-issuer/storage/*.pem
license-issuer/storage/*.zip
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
bootstrap/cache/*.php
```

## License manager note

The license manager code is included for review and controlled operation. Production signing keys, issued licenses and authority registry data are intentionally excluded.

## Reporting

Report security issues privately to the project maintainer. Do not publish exploit details or production secrets in public issues.
