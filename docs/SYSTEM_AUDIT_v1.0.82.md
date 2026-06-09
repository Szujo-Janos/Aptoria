# Aptoria v1.0.82 - Optional Vendor Plugin Guard Hotfix

## Scope

This hotfix fixes a JavaScript initialization error visible after the Aptoria rebrand when pages load the shared Aptoria UI script without every sidebar-only vendor plugin.

## Fixes

- `public/assets/aptoria-ui/js/aptoria-ui.js` now checks that optional jQuery plugins exist before calling them.
- `metisMenu`, `slimScroll`, and `animatePanel` initialization are guarded.
- Public/auth pages no longer fail with `$(...).metisMenu is not a function` when the sidebar menu plugin is not loaded.
- Dashboard/sidebar pages still initialize `metisMenu` when the vendor plugin and `#side-menu` are present.

## Regression Coverage

- Added `tests/Feature/AptoriaUiAssetTest.php` to verify optional vendor plugin guards remain in the shared Aptoria UI asset.

## Release Hygiene

- `VERSION` is `1.0.82`.
- Release ZIP excludes root `vendor/`, `.env`, SQLite databases/backups, setup locks, setup token files and runtime cache files.
- Release ZIP keeps GitHub Actions, Windows/XAMPP scripts and Aptoria UI vendor assets.
