# Aptoria v1.1.20 – System Audit

## Release focus

Aptoria v1.1.20 adds a QA verification layer to findings so that issue lifecycle is not just a status label. Findings can now carry ownership, due dates, priority, verification status, retest state, verification metadata, linked release gate context and comments.

## Main changed areas

- Finding model lifecycle and verification state handling.
- Finding create/edit/detail/list UI.
- Finding comments timeline.
- Release Readiness scoring, blockers and warnings.
- Full QA report finding verification summary.
- English and Hungarian localization.
- Feature tests for owner, due date, verification transitions, comments, retest evidence and report integration.

## Release rules checked

- Release ZIP excludes root `vendor/`.
- Release ZIP excludes `.env`.
- Release ZIP excludes `database/database.sqlite`.
- Release ZIP excludes `storage/app/installed.lock`.
- Release ZIP excludes `storage/app/setup-token.txt`.
- `public/assets/aptoria-ui/vendor` remains included.
- README references CHANGELOG.md rather than embedding a long changelog.
- Current ZIP naming uses `aptoria-1.1.20.zip`.

## Recommended post-install checks

1. Run migrations.
2. Run the full test suite.
3. Open a finding and confirm ownership and verification panels render.
4. Move a finding through Ready for retest, Retest failed, Fixed and Verified.
5. Export a full QA report and confirm the Finding Verification Summary appears.
