# Aptoria v1.1.23 System Audit

## Release identity

Aptoria v1.1.23 – API Behavior Map Pass

## Scope

This release adds API behavior intelligence on top of the endpoint inventory. It detects producer/consumer relationships, path parameter dependencies, destructive endpoints, auth boundary hints and suggested API call sequences.

## Main changed areas

- Endpoint behavior metadata columns.
- `endpoint_behavior_links` dependency table.
- API Behavior Map project page.
- Endpoint detail behavior panel.
- Full QA Report Builder API Behavior Map section.
- English and Hungarian translations.
- Feature tests for behavior dependency detection and destructive endpoint markers.

## Safety notes

The behavior map is calculated from stored endpoint inventory only. It does not execute HTTP requests, does not mutate remote APIs and does not require credentials.

## Release packaging audit

Release ZIP must exclude local runtime artifacts and include static UI vendor assets:

- excluded: root `vendor/`
- excluded: `.env`
- excluded: `database/database.sqlite`
- excluded: `storage/app/installed.lock`
- excluded: `storage/app/setup-token.txt`
- included: `public/assets/aptoria-ui/vendor`
