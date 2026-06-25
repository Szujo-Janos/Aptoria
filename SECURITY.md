# Security Policy

Aptoria is a self-hosted QA evidence and release decision tool. The current public replacement line is **v0.0.63** and should be treated as an active **MVP / foundation release**, not a hardened enterprise product.

> [!WARNING]
> Do not use the legacy `v1.1.34` package as the active deployment baseline. It is archived and replaced by the current `0.0.x` line.

## Supported versions

| Version line | Status | Security handling |
| --- | --- | --- |
| `0.0.x` | Active foundation line | Security review and fixes target the latest package. |
| `v0.0.63` | Current public replacement baseline | Supported as the current public package. |
| `v1.1.34` | Archived legacy line | Replaced; not recommended as an active deployment baseline. |

## Do not publish secrets

Never commit or publish:

```text
.env
API tokens
Bearer tokens
Basic auth passwords
production URLs that are not public
customer data
SQLite runtime databases
storage/app/setup-token.txt
storage/app/installed.lock
storage/app/aptoria-license.json
storage/app/license-public.pem
private signing keys
generated evidence/report exports containing customer data
```

## Deployment notes

- Keep `APP_DEBUG=false` outside local development.
- Use HTTPS in production.
- Change temporary/default credentials immediately.
- Do not expose `/setup` publicly without a strong setup token.
- Keep `vendor/` out of the repository and install Composer dependencies locally.
- Treat generated report/evidence exports as potentially sensitive project data.
- Back up `.env` together with database exports when moving or restoring an installation, because encrypted values may depend on the same `APP_KEY`.

## Local development safety

Use local/demo data whenever possible. Do not use real production API tokens, customer payloads or private target URLs in public screenshots, issues, reports or sample exports.

## Reporting a security issue

Report private security concerns directly to the repository owner. Do not include live credentials, API tokens, production request samples or exploitable target details in public issues.

When reporting, include:

| Field | What to provide |
| --- | --- |
| Summary | Clear description of the issue. |
| Affected area | Route, module, command, export or workflow. |
| Impact | What could be exposed, changed or bypassed. |
| Safe reproduction | Minimal steps using non-sensitive sample data. |
| Suggested fix | Optional, but useful if known. |

> [!IMPORTANT]
> Do not attach customer databases, live `.env` files, real API credentials or generated evidence packages containing private data.
