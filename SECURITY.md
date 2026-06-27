# Security Policy

Aptoria is a self-hosted QA evidence and release decision tool. The public GitHub package is intended for review, evaluation, portfolio presentation and controlled local testing.

## Supported version line

| Version line | Status | Security handling |
| --- | --- | --- |
| `0.0.x` | Active foundation line | Fixes target the latest public package. |
| `0.0.80` | Current public package | Supported as the current user-facing package. |

## Do not publish secrets

Never commit or publish:

```text
.env
API tokens
Bearer tokens
Basic auth passwords
customer data
SQLite runtime databases
storage/app/setup-token.txt
storage/app/installed.lock
storage/app/aptoria-license.json
storage/app/license-public.pem
storage/app/license-private.pem
storage/app/license-*.pem
storage/app/*lease*.json
generated evidence/report exports containing customer data
```

## Deployment and local safety

- Keep `APP_DEBUG=false` outside local development.
- Use HTTPS for any publicly reachable deployment.
- Change temporary/default credentials immediately.
- Do not expose setup routes publicly without protection.
- Keep `vendor/` out of the repository and install Composer dependencies locally.
- Treat generated report/evidence exports as potentially sensitive project data.
- Use local/demo data in screenshots, issues and public reports.

## License and activation safety

The public package includes the runtime activation module, but private activation operations and key handling must remain private. Do not publish activation packages, generated license files, key files, registry files or internal deployment notes.

## Reporting a security issue

Report private security concerns directly to the repository owner. Do not include live credentials, API tokens, production request samples or exploitable target details in public issues.

When reporting, include:

| Field | What to provide |
| --- | --- |
| Summary | Clear description of the issue. |
| Affected area | Route, module, command, export or workflow. |
| Impact | What could be exposed, changed or misused. |
| Safe reproduction | Minimal steps using non-sensitive sample data. |
| Suggested fix | Optional, but useful if known. |
