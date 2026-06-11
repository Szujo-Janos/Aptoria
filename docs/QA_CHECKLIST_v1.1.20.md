# Aptoria v1.1.20 – Finding Verification & Ownership QA Checklist

## Install / update

1. Extract `aptoria-1.1.20.zip` into the configured XAMPP project root.
2. Run the Windows update script.
3. Run `php artisan optimize:clear`.
4. Run `php artisan migrate`.
5. Run `php artisan test`.

## Finding ownership

1. Open **Project → Findings**.
2. Create a finding with owner, priority and due date.
3. Confirm the finding list shows owner, due date, priority and verification status.
4. Use the owner filter and confirm only matching findings remain.
5. Use the overdue filter and confirm overdue, unverified findings are listed.

## Verification workflow

1. Move a finding to **Ready for retest**.
2. Confirm retest required and verification status update automatically.
3. Move it to **Retest failed**.
4. Confirm Release Readiness treats the retest failure as a blocker.
5. Move it back to **Fixed**, then to **Verified**.
6. Confirm `verified by`, `verified at`, last retest and pass result are saved.
7. Reopen a verified finding and confirm reopened count increases.

## Evidence and comments

1. Add **Retest evidence** to a finding.
2. Confirm the finding detail verification panel shows retest evidence present.
3. Add a QA / retest comment.
4. Confirm the comments timeline shows user, type, timestamp and body.

## Reports

1. Open **Project → Release Readiness** and confirm the Finding Verification Summary appears.
2. Export the full QA report with release readiness and findings/evidence sections.
3. Confirm owner, due date, verification status and retest evidence appear in the report.
