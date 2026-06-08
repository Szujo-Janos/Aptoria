# Aptoria v1.0.65 – Settings Full Wiring Pass

## Scope

v1.0.65 fixes a Blade layout rendering regression reported after v1.0.63. The browser showed raw layout setup code above the application header, which means layout-level Blade/PHP setup directives were leaking into the rendered response.

## Root cause

The previous asset hotfix replaced Blade asset expressions with compiled-style PHP echo statements inside `.blade.php` files while the layout still used inline `@php(...)` setup directives. In the affected environment this combination caused part of the layout setup block to be emitted as text instead of being safely compiled and executed.

## Changes

- Restored asset, logo, favicon, CSS and JavaScript URLs to normal Blade `{{ asset(...) }}` expressions.
- Replaced the app layout's inline title setup directives with a proper block `@php ... @endphp` section.
- Kept the v1.0.61 layout fallback fixes.
- Kept the v1.0.62 risk scoring default fix.
- Kept the v1.0.57–v1.0.59 Settings wiring changes.

## Expected result

- No raw `@php`, `$aptoria...` or setup code is visible above the UI.
- Asset URLs render as normal paths such as `/assets/aptoria-ui/vendor/bootstrap/css/bootstrap.min.css`.
- The Settings page loads its CSS and JavaScript assets without encoded `%7B%7B asset(...) %7D%7D` URLs.

## Release package

- ZIP name: `aptoria-1.0.65.zip`
- Root folder: `aptoria-1.0.65/`
- Runtime/local files remain excluded.
