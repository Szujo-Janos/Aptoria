# Security Policy

Aptoria is a self-hosted QA evidence and regression monitoring tool. It is currently post-MVP / early beta and should be deployed carefully.

## Supported version

Security review and fixes currently target the latest release line:

```text
v1.0.49
```

Older ZIPs should be treated as historical development packages.

## Do not publish secrets

Never post the following in public GitHub issues, discussions, pull requests or screenshots:

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

## Reporting a security issue

For private/internal use, report security concerns directly to the repository owner or through a private channel. If the repository is public, do not include live credentials, tokens, production request samples or exploitable target details in public issues.

## Deployment notes

- Keep `APP_DEBUG=false` outside local development.
- Use HTTPS in production.
- Run `C:\xampp\php\php.exe artisan aptoria:security-audit` before exposing the app.
- Restrict `/setup` with a strong setup token on non-local hosts.
- Change default admin credentials immediately after first setup.
- Keep dependency updates under review.
