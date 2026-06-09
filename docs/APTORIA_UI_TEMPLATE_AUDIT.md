# Aptoria UI Template Integration Audit - Aptoria v1.0.11

This hardening pass keeps the Aptoria UI admin template as the visual foundation of Aptoria and removes avoidable template integration issues.

## Changes

- Enabled `sidebar-scroll` on the main authenticated layout so long project navigation remains usable on smaller screens.
- Loaded `static_custom.css` after Aptoria UI `style.css` and before Aptoria overrides.
- Removed the external Google Fonts import from `public/assets/aptoria-ui/css/style.css` to keep the app self-hosted/offline-friendly.
- Added the missing local image assets referenced by the Aptoria UI stylesheet:
  - `public/assets/aptoria-ui/css/img/green.png`
  - `public/assets/aptoria-ui/css/img/green@2x.png`
  - `public/assets/aptoria-ui/images/landing/header.jpg`
- Extended the auth/setup layout with the same core Aptoria UI support assets used by the admin layout where useful: FontAwesome, Toastr, SweetAlert, iCheck and `aptoria-ui.js`.
- Kept Laravel routes, scan logic, assertion logic, evidence pack logic and setup business logic unchanged.

## Manual QA checklist

- Main dashboard loads with Aptoria UI header, sidebar and panel styling.
- Sidebar opens/collapses with the top-left hamburger icon.
- Long project module menu scrolls instead of overflowing.
- Login page keeps the Aptoria UI panel styling.
- Setup page keeps the Aptoria UI panel styling.
- Browser developer tools show no 404 requests for Aptoria UI `green.png`, `green@2x.png` or `landing/header.jpg`.
- Browser developer tools show no request to Google Fonts from Aptoria UI CSS.
- Toast/SweetAlert flash feedback still works.
- DataTables and Chart.js pages still render.
