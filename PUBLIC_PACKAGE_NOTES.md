# Aptoria public GitHub package notes

This package is the public, user-facing GitHub version of Aptoria `0.0.80`.

It keeps the application source and the runtime license activation module, but removes private activation administration tooling and internal documentation that should not be published.

Removed from the public package:

- private activation administration application folder;
- private activation and key-management operational documentation;
- internal deployment and smoke-test documentation;
- private hosting profile templates;
- scripts intended for Aptoria infrastructure deployment rather than end-user evaluation.

Kept in the public package:

- Laravel application source;
- user-facing setup and runtime files;
- public source-available license and notices;
- product documentation that explains what Aptoria does without exposing private license operations.

Do not publish generated runtime files, customer data, secrets, keys, activation artifacts or private deployment notes.
