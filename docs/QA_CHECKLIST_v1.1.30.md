# Aptoria v1.1.30 QA Checklist – Client Portal Handoff Visibility Polish

1. Install from `aptoria-1.1.30.zip` using the documented PowerShell template.
2. Run `php artisan migrate` and `php artisan test`.
3. Open **Project → Client Portal**.
4. Confirm the new **Role default permissions** matrix is visible under the create form.
5. Switch the role selector between Client viewer, Client approver and External reviewer and confirm the permission checkboxes update to the selected role defaults.
6. Create one Client viewer portal link and open it in a new browser tab.
7. Confirm the fixed public header shows the Aptoria logo, portal title, project name and role label.
8. Confirm the **Current client-safe release snapshot** is visible even if no approved report or release decision exists yet.
9. Confirm the new **Role access summary** lists visible and restricted content/actions for the link.
10. Create or edit a restricted portal link with all content permissions disabled and open it.
11. Confirm the portal still shows the release snapshot and displays the warning that no client content sections are enabled.
12. Create a Client approver portal link and confirm acknowledgement permissions are marked as visible.
13. Confirm restricted links still cannot download evidence packages or post acknowledgements without the required permission.
