# Third-party notices

Aptoria bundles selected third-party frontend libraries for self-hosted operation and uses Composer-managed PHP dependencies for the Laravel backend.

This notice is informational and does not replace the original licenses of the third-party projects.

## Aptoria application code

Aptoria application code, documentation and product identity are covered by the project `LICENSE`, `NOTICE.md` and `CREDITS.md` files unless a file states otherwise.

## Ownership and credits

Project ownership and application-level credits are documented in `NOTICE.md` and `CREDITS.md`. This file is only for third-party dependency and bundled runtime asset notices.

## PHP dependencies

Laravel and related PHP packages are managed through Composer. See:

```text
composer.json
composer.lock
```

These dependencies are installed locally and are intentionally not shipped as the root `vendor/` directory in release ZIPs.

## Bundled frontend runtime assets

Runtime UI assets live under:

```text
public/assets/aptoria-ui
```

Application-specific assets live under:

```text
public/assets/aptoria
```

The bundled `public/assets/aptoria-ui/vendor` tree currently contains these frontend libraries:

| Component | Bundled path | License note from bundled asset/header |
| --- | --- | --- |
| Bootstrap 3.3.7 | `vendor/bootstrap` | MIT |
| Normalize.css bundled through Bootstrap | `vendor/bootstrap/css/bootstrap.min.css` | MIT |
| Glyphicons Halflings bundled through Bootstrap | `vendor/bootstrap/fonts` | Bootstrap 3 distribution assets |
| jQuery 2.2.0 | `vendor/jquery` | jQuery license reference in header |
| Font Awesome 4.7.0 | `vendor/fontawesome` | Font: SIL OFL 1.1; CSS: MIT |
| DataTables 1.10.10 | `vendor/datatables` | SpryMedia/DataTables license reference in header |
| Chart.js 2.5.0 | `vendor/chartjs` | MIT |
| Toastr | `vendor/toastr` | Third-party notification library; verify upstream license before separate redistribution |
| SweetAlert | `vendor/sweetalert` | Third-party alert library; verify upstream license before separate redistribution |
| MetisMenu 2.4.0 | `vendor/metisMenu` | MIT |
| SlimScroll 1.3.8 | `vendor/slimScroll` | MIT/GPL dual license |
| jQuery Sparkline 2.1.2 | `vendor/sparkline` | New BSD License |
| iCheck 1.0.2 | `vendor/iCheck` | MIT |
| Animate.css | `vendor/animate` | MIT |

## Public repository guidance

The full source can be made public as a source-available project if the owner accepts source visibility and keeps this notice with the repository.

Before repackaging, reselling, rebundling or redistributing Aptoria outside the original repository/release ZIP flow, review the original licenses of all bundled third-party assets.
