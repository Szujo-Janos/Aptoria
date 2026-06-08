# Aptoria v1.0.63 – Blade Asset URL Render Hotfix

## Purpose

v1.0.63 fixes a frontend asset rendering regression reported after v1.0.62. Browser DevTools showed URLs such as `%7B%7B%20asset('assets/aptoria-ui/...')%20%7D%7D`, which means the asset helper expression was reaching the browser as literal text instead of a resolved URL.

## Fix

The Blade templates that load core CSS, JavaScript, icons and logo assets now use explicit PHP escaped output for asset URLs. This makes the asset tags resistant to accidental literal `{{ asset(...) }}` output in cached or nested layout render paths.

Updated view areas:

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/auth.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/landing/index.blade.php`
- `resources/views/setup/index.blade.php`

## Expected result

DevTools Network should request resolved paths such as:

- `/assets/aptoria-ui/vendor/bootstrap/css/bootstrap.min.css`
- `/assets/aptoria-ui/vendor/jquery/jquery.min.js`
- `/assets/aptoria/css/app.css?v=1.0.63`

It should not request encoded literal Blade expressions such as `%7B%7B asset(...) %7D%7D`.

## Release hygiene

- ZIP name: `aptoria-1.0.63.zip`
- Root folder: `aptoria-1.0.63/`
- Runtime/local files remain excluded from the release package.
