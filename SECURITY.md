# Security Policy

Aptoria is a self-hosted QA evidence and release decision tool. The current public replacement line is **v0.0.53** and should be treated as an active MVP / foundation release, not a hardened enterprise product.

## Supported version

Security review and fixes currently target the latest 0.0.x package.

```text
v0.0.53
```

The legacy `v1.1.34` package is archived and replaced. It should not be used as the active deployment baseline.

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
```

## Deployment notes

- Keep `APP_DEBUG=false` outside local development.
- Use HTTPS in production.
- Change temporary/default credentials immediately.
- Do not expose `/setup` publicly without a strong setup token.
- Keep `vendor/` out of the repository and install Composer dependencies locally.
- Treat generated report/evidence exports as potentially sensitive project data.

## Reporting a security issue

Report private security concerns directly to the repository owner. Do not include live credentials, API tokens, production request samples or exploitable target details in public issues.
