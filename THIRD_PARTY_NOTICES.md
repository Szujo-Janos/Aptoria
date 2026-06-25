# Third-party notices

Aptoria bundles selected frontend runtime assets for self-hosted operation and uses Composer-managed PHP dependencies for the Laravel backend.

This notice is informational and does not replace the original licenses of third-party projects.

> [!NOTE]
> Third-party components remain under their own upstream licenses. Review upstream licenses before redistributing bundled assets separately from Aptoria.

## PHP dependencies

Laravel and related PHP packages are managed through Composer.

| Area | Source |
| --- | --- |
| PHP dependency manifest | `composer.json` |
| Local dependency install path | `vendor/` |
| Release/GitHub package rule | `vendor/` is intentionally not shipped or committed |

Dependencies are installed locally and are intentionally not shipped as the root `vendor/` directory in release or GitHub transition ZIPs.

## Bundled frontend runtime assets

Runtime UI assets live under:

```text
public/assets/aptoria-ui
```

The current bundled asset tree includes minified UI/runtime files and plugins such as:

| Component / asset family | Bundled path |
| --- | --- |
| Aptoria UI runtime CSS/JS | `public/assets/aptoria-ui/assets/css`, `public/assets/aptoria-ui/assets/js` |
| DataTables | `public/assets/aptoria-ui/assets/plugins/datatables` |
| FullCalendar | `public/assets/aptoria-ui/assets/plugins/fullcalendar` |
| SweetAlert2 | `public/assets/aptoria-ui/assets/plugins/sweetalert2` |
| Tabler-compatible icon/font support | `public/assets/aptoria-ui/assets/fonts/tabler`, `public/assets/aptoria-ui/assets/css/aptoria-tabler-icons.css` |
| Aptoria official logo assets | `public/assets/aptoria-ui/assets/images/logo-color.svg`, `logo-color-pdf.jpg` |

## Redistribution reminder

Review upstream project licenses before redistributing bundled assets separately from Aptoria. Some third-party assets may have attribution, notice or license-copy requirements outside Aptoria's own application license.

## Application code

Aptoria application code, documentation and product identity are covered by:

- `LICENSE`
- `NOTICE.md`
- `CREDITS.md`

unless a file states otherwise.
