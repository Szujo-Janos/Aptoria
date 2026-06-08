# Aptoria v1.0.53 – GitHub Actions Cache Path Hotfix

## Scope

This hotfix stabilizes the public GitHub Actions QA gate by explicitly creating Laravel writable runtime directories before running PHPUnit.

## Root cause

On GitHub Actions, empty or ignored runtime directories may be absent after checkout. Laravel then fails with `Please provide a valid cache path` during view rendering.

## Changes

- Added a dedicated workflow step to create:
  - `bootstrap/cache`
  - `storage/app/public`
  - `storage/app/private`
  - `storage/framework/cache/data`
  - `storage/framework/sessions`
  - `storage/framework/testing`
  - `storage/framework/views`
  - `storage/logs`
- Applied writable permissions to `bootstrap/cache` and `storage`.
- Kept public release hygiene checks unchanged.
- Updated version metadata to `1.0.53`.

## Release hygiene

The release ZIP must still exclude local/runtime files such as `.env`, root `vendor/`, `database/database.sqlite`, `storage/app/installed.lock`, and `storage/app/setup-token.txt`.
