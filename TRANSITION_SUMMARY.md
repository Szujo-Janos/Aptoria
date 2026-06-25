# Aptoria Transition Summary

This package is prepared as a **GitHub replacement package** for the Aptoria repository.

> [!WARNING]
> This package is not an in-place database upgrade. Use a fresh database and archive the old deployment data separately.

## Replacement overview

| Area | Value |
| --- | --- |
| Old active line | `aptoria-1.1.34` |
| New active line | `aptoria-0.0.53` |
| Strategy | Discard old code as the active baseline and replace repository contents with the new evidence-first rebuild. |
| Database approach | Fresh database recommended. Do not treat this as an automatic migration from `1.1.34`. |

## Read first

- `README.md`
- `docs/UPGRADE_FROM_1.1.34_TO_0.0.53.md`
- `docs/LEGACY_1.1.34_VS_0.0.53_COMPARISON.md`
- `docs/GITHUB_REPLACEMENT_CHECKLIST.md`
- `docs/ARCHITECTURE_TRANSITION_MAP.md`

## Before replacing an existing deployment

- [ ] Back up the old repository/package if you still need it.
- [ ] Export any old database that must be preserved.
- [ ] Back up the matching `.env` file if encrypted values may be needed later.
- [ ] Use a clean target folder for the new `0.0.53` package.
- [ ] Run the documented install/update script.
- [ ] Run migrations on a fresh database.
- [ ] Run `aptoria:health` and the test suite.
- [ ] Complete `/setup` in the browser.

## Important warning

The legacy `1.1.34` line is treated as archived historical code. The `0.0.x` line is a fresh evidence-first rebuild with a cleaner product direction, new UI rules and different database migrations.
