# Upgrade from 1.1.34 to the 0.0.x rebuild

> [!IMPORTANT]
> This file is kept for compatibility with older public hygiene checks that expected the historical `0.0.53` document name. The current package is `0.0.63`.

Aptoria `0.0.x` is not an in-place upgrade from `1.1.34`. It is a fresh evidence-first rebuild and should be installed as a replacement package.

## Recommended approach

1. Archive the old `1.1.34` repository or deployment if you still need it for historical reference.
2. Back up the old database and `.env` file separately.
3. Extract or clone the current `0.0.x` package into a clean folder.
4. Do not copy old runtime state into the new package.
5. Run the documented install/update script.
6. Run migrations against a fresh database.
7. Complete `/setup` in the browser.
8. Run `aptoria:health` and the test suite.

## Do not copy these from the old install

```text
.env
vendor/
node_modules/
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
storage/app/aptoria-license.json
storage/app/license-public.pem
storage/app/license-authority-public.pem
storage/app/license-runtime-lease.json
storage/app/license-install-id
public/storage/
bootstrap/cache/
```

## Why replacement is required

The `0.0.x` line introduces a different product architecture:

- project-scoped API QA workspace;
- endpoint inventory;
- safe scan evidence;
- normalized imports;
- checksum-backed Evidence Repository;
- native test evidence;
- QA cockpit;
- release gate workflow;
- decision package exports;
- portable/runtime license direction.

Those areas are not a schema-compatible continuation of the old `1.1.34` application.

For the current transition overview, also read:

- `TRANSITION_SUMMARY.md`
- `docs/GITHUB_REPLACEMENT_CHECKLIST.md`
- `docs/ARCHITECTURE_TRANSITION_MAP.md`
