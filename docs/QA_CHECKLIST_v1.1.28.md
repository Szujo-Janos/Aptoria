# Aptoria v1.1.28 QA Checklist – QA Cockpit Pass

## Installation

1. Install from `aptoria-1.1.28.zip` using the documented PowerShell template.
2. Run `php artisan migrate`.
3. Run `php artisan test`.
4. Run `php artisan aptoria:health`.

## Functional QA

1. Open a project and go to **Project → QA Cockpit**.
2. Confirm the top metric cards show open blockers, fixes waiting for retest, expiring accepted risks, stale scans, stale reports and endpoints without evidence.
3. Create or use a critical/high open finding and confirm it appears in the priority queue.
4. Set a finding to **Ready for retest** and confirm it appears in the retest queue.
5. Create an accepted risk that expires soon and confirm it appears in the expiring risks queue.
6. Use a project with old or missing scans and confirm the stale scan queue is populated.
7. Use a project with no approved report or an old approved report and confirm the stale report queue is populated.
8. Confirm endpoint evidence gaps link back to endpoint details.
9. Confirm the release snapshot shows readiness score, blockers and blind spot blockers.
10. Confirm quick action buttons open Blind Spots, Release Readiness, Release Decisions, Report approvals, Safe scan and Findings.

## Regression QA

1. Existing Release Readiness page still opens.
2. Existing Blind Spots page still opens.
3. Existing Client Audit Portal still opens.
4. Existing report exports still work.
