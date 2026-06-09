# Aptoria v1.0.97 – System Audit

## Release

**Version:** v1.0.97 – API Collection Import Pass

## Scope

This release extends endpoint inventory import into a fuller API collection import workflow. It keeps the existing safe design: import preview and confirm import only parse and write endpoint inventory. They do not send API requests.

## Added / changed

- Added Postman Collection JSON support.
- Kept CSV, JSON endpoint list and OpenAPI/Swagger JSON/YAML import support.
- Added Postman folder recursion and request extraction.
- Extracted method, path, name, auth requirement, expected status and expected content type from collection data where available.
- Converted Postman `:id` path parameters to Aptoria/OpenAPI-style `{id}` placeholders.
- Stored imported request header/body metadata on endpoints.
- Masked sensitive header values and common token/password body fields before storing metadata.
- Added Postman sample buttons to endpoint import and guided project wizard.
- Added regression tests for preview, import, masking and translation rendering.

## Safety notes

- Import does not scan endpoint URLs.
- Remote URL fetch remains blocked for localhost, private and reserved network targets.
- State-changing methods are imported as inventory only and remain skipped by safe scan mode.

## Release hygiene

- Release ZIP must not include root `vendor/`.
- Release ZIP must not include `.env`.
- Release ZIP must not include `database/database.sqlite`.
- Release ZIP must not include `storage/app/installed.lock`.
- Release ZIP must not include `storage/app/setup-token.txt`.
- `public/assets/aptoria-ui/vendor` must remain included.
