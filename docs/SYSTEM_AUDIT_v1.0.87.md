# Aptoria v1.0.87 - HTML & PDF Report Export Pass System Audit

## Scope

This audit covers the v1.0.87 report export pass. The release adds HTML and PDF companions for core Markdown reports while keeping the existing Markdown/JSON/CSV/ZIP exports intact.

## Key changes

- Added `App\Services\Reports\ReportPresentationService` for reusable report rendering.
- Added `App\Services\Reports\SimplePdfReportRenderer` for dependency-free PDF output.
- Added HTML/PDF routes for full project, release readiness, QA release gate, scan, snapshot compare and custom report builder outputs.
- Added report center and detail-page links for the new formats.
- Preserved Aptoria credit/attribution metadata in HTML and PDF outputs.
- README no longer embeds release-history/changelog sections; release history remains in `CHANGELOG.md`.

## Release hygiene

The release ZIP excludes local runtime artifacts: root `vendor/`, `.env`, `database/database.sqlite`, `storage/app/installed.lock` and `storage/app/setup-token.txt`. Bundled public UI vendor assets remain under `public/assets/aptoria-ui/vendor`.

## QA notes

Run the PHPUnit suite locally after installation and manually download a full project report in Markdown, HTML and PDF formats. Open the HTML in a browser and the PDF in a PDF viewer to confirm branding, content readability and footer attribution.
