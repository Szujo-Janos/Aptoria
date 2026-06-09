# Aptoria v1.0.98 – System Audit

**Version:** v1.0.98 – Postman Import Max Pass

## Scope

This release expands the Postman import workflow beyond endpoint inventory. It keeps the existing CSV, JSON, OpenAPI/Swagger and Postman Collection import flow, and adds Postman Environment aware enrichment.

## Implemented

- Postman Collection JSON import remains available.
- Postman Environment JSON can be pasted alongside the collection.
- `{{variable}}` values are resolved for base URL, auth and request metadata where appropriate.
- URL path variables are preserved as Aptoria placeholders such as `{userId}`.
- Import preview shows collection, environment, base URL, variable count, auth profile candidates, response examples, assertion candidates and suite candidates.
- Optional environment creation from Postman `baseUrl`.
- Optional auth profile creation from Postman Bearer, Basic, API key and auth header patterns.
- Optional assertion rules from response examples and simple `pm.test` patterns.
- Optional test suite/test case creation from Postman folders.
- Secret masking remains enforced in preview and request metadata.

## Safety

- Import preview does not send API requests.
- Remote import still blocks localhost, private and reserved network targets.
- Automatic safe scans remain GET/HEAD focused.
- Tokens and API keys are masked in preview.

## Manual QA focus

1. Preview a Postman Collection without environment JSON.
2. Preview the same collection with matching Postman Environment JSON.
3. Confirm environment, auth profile, assertion and test suite creation options.
4. Verify endpoint paths preserve placeholders.
5. Verify imported auth secrets are not displayed raw.
6. Verify generated assertion rules and test cases link to the correct endpoints.
