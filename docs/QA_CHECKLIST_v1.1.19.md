# Aptoria v1.1.19 – QA Checklist

## Install / update

1. Extract `aptoria-1.1.19.zip` into the configured XAMPP project root.
2. Run the Windows update script.
3. Run `php artisan optimize:clear`.
4. Run `php artisan migrate`.
5. Run `php artisan test`.

## Blind Spot Detector

1. Create or import an endpoint without running a scan.
2. Open Project → Blind Spots.
3. Confirm that endpoint without scan and endpoint without assertion are listed.
4. Add an assertion rule and confirm that the assertion blind spot disappears.
5. Use an auth-required endpoint and confirm no-auth comparison detection appears when evidence is missing.
6. Mark a finding as Fixed without retest evidence and confirm the unverified fix blind spot.
7. Add Retest evidence and confirm the unverified fix blind spot disappears.
8. Mark a finding as Accepted risk with no expiry and confirm the risk expiry blind spot.
9. Add an expired date and confirm expired accepted risk handling.
10. Confirm the Release Readiness page and exported reports show Blind Spot Summary.
