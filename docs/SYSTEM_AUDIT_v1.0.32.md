# Aptoria System Audit v1.0.32

## Scope

This audit belongs to the cumulative QA Operations Calendar suite that ends at v1.0.35.

## Branch lineage

v1.0.24 clean baseline → v1.0.25 test stability → v1.0.26 documentation cleanup → v1.0.27 deployment/security hardening → v1.0.28 scheduled monitoring operations → v1.0.29 alerting → v1.0.30 email delivery/history → v1.0.31 alert triage → v1.0.32 calendar suite.

## Summary

- Calendar data model added.
- Laravel Blade calendar screens added.
- Monitor alert follow-up workflow added.
- Monitor next-run preview added.
- JSON and .ics export surfaces added.
- Calendar feature tests added.
- Release ZIP hygiene rules preserved.

## Release hygiene

The release ZIP must not contain:

- root vendor/
- .env
- database/database.sqlite
- storage/app/installed.lock
- storage/app/setup-token.txt

The Aptoria UI vendor assets remain present because this clean branch is still Aptoria UI.
