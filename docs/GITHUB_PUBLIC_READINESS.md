# Aptoria GitHub Public Readiness

Current version: **v1.0.65 – Settings Full Wiring Pass**

Aptoria can now be published as a **source-available** public GitHub repository if the owner intentionally wants the full application code visible.

Public visibility is useful for portfolio proof, code review, issue tracking and release history. It does not mean the project is open-source. The current repository license keeps commercial redistribution and hosted resale restricted.

## Recommended repository split

- **Private repository:** full development workspace, experiments, internal notes and unfinished branches.
- **Public repository:** stable source-available release line, README, docs, issue templates, GitHub Actions, sample reports and cumulative release ZIPs.
- **Public demo/portfolio page:** screenshots, feature overview and sample outputs without private data.

## v1.0.65 readiness status

- Product-facing UI uses Aptoria branding.
- Runtime UI assets live under `public/assets/aptoria-ui`.
- The old visible template brand is not required in normal UI surfaces.
- A source-available `LICENSE` is present.
- `NOTICE.md` and `CREDITS.md` provide explicit ownership and attribution context.
- `THIRD_PARTY_NOTICES.md` names bundled frontend libraries.
- `docs/PUBLIC_REPOSITORY_CHECKLIST.md` provides the final pre-push checklist.
- GitHub issue templates, pull request template, PHP workflow and Dependabot config are present.
- No Node, Tailwind, React or frontend build requirement was added.

## Before pushing public

Run the local QA command block from `docs/PUBLIC_REPOSITORY_CHECKLIST.md`, then manually verify:

- no `.env` file;
- no root `vendor/` directory;
- no runtime SQLite database;
- no setup lock;
- no setup token;
- no private API endpoints or credentials in screenshots/docs/sample data.

## Suggested GitHub visibility decision

Use **private** while actively experimenting with architecture or UI direction.

Use **public** only for a stable release line that you are comfortable showing as source-available work.


## GitHub Actions Public QA Gate

The repository includes `.github/workflows/php.yml` to check public release hygiene, Composer metadata, PHP syntax and the PHPUnit suite on push and pull request.


## Portfolio showcase

The public presentation layer is documented in `docs/PORTFOLIO_SHOWCASE.md`. Keep screenshots and demo text free from credentials, private URLs and customer data.
