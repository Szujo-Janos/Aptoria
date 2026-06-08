# Aptoria v1.0.60 – Layout Settings View Composer Hotfix

## Scope

v1.0.60 fixes the first PHPUnit failure reported after the v1.0.57–v1.0.59 Settings wiring pass.

## Root cause

The main application layout started using Settings-driven UI variables such as sidebar state, dashboard density and theme. In some feature-test rendering paths these variables were not reliably available when the compiled layout reached the `<body>` class list.

## Fix

- Added a `layouts.app` view composer in `AppServiceProvider`.
- Shared layout UI variables centrally before the Blade template is rendered.
- Kept defensive fallback assignments inside `resources/views/layouts/app.blade.php`.
- Added null-coalescing fallbacks on the body class expression.

## Result

Views that extend `layouts.app` no longer depend on a single inline Blade initialization block to provide Settings UI variables.

## Release hygiene

- ZIP name: `aptoria-1.0.60.zip`.
- Forbidden runtime files remain excluded.
- The Settings wiring from v1.0.57, v1.0.58 and v1.0.59 remains cumulative.
