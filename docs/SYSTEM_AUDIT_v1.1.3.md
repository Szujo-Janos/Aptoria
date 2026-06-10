# Aptoria v1.1.3 – Sensitive Data Detector Pass System Audit

## Scope

This release adds automated sensitive data detection to safe GET/HEAD probe results. The detector inspects response headers and response bodies before storage, records only masked summaries, raises risk signals, and creates findings with HTTP evidence when sensitive-looking data is found.

## Implemented checks

- Sensitive response headers such as Authorization, Set-Cookie, token and secret related headers.
- Sensitive JSON field names such as password, token, access_token, refresh_token, api_key, secret and private_key.
- JWT and Bearer token patterns.
- Private key material markers.
- Email and phone number patterns.
- Debug trace / stack trace / SQLSTATE style leakage.

## Safety

Detected values are masked before being stored in scan result summaries and finding evidence. Normal response body previews continue to use the existing secret masking pipeline.

## QA focus

- Run safe probes against known sample payloads.
- Confirm sensitive data flags appear in scan results and Endpoint Inventory.
- Confirm open findings and evidence are created.
- Confirm raw secrets are not visible in the UI, report previews or stored detector metadata.
