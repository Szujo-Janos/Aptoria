# First-Run Environment Stability - Aptoria v1.0.12

## Problem

When running Aptoria through `php artisan serve`, Laravel watches `.env` and restarts the development server whenever that file is modified.

In v1.0.11 the first-run preflight could rewrite `APP_URL=http://127.0.0.1:8000` back to the same value on each request. This changed the `.env` file timestamp even though the content was effectively unchanged. After login, the browser loaded several CSS/JS assets, the server detected the `.env` timestamp change, restarted, and Chrome displayed `ERR_CONNECTION_RESET`.

## Fix

The preflight now only updates `APP_URL` when the detected URL is different from the stored value. It also skips `.env` writes when the generated content is identical to the existing file content.

## QA check

Run the application with:

```powershell
C:\xampp\php\php.exe artisan serve
```

Then log in and verify:

- the browser does not show `ERR_CONNECTION_RESET`;
- the PowerShell console does not repeatedly print `Environment modified. Restarting server...`;
- `/login`, dashboard, assets and project pages load normally.
