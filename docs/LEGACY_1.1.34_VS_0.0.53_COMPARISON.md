# Legacy 1.1.34 vs current 0.0.x comparison

> [!NOTE]
> This file name is retained for compatibility with older public hygiene checks that referenced `0.0.53`. The current public package is `0.0.63`.

## Summary

The legacy `1.1.34` line is archived. The current `0.0.x` line is the active evidence-first rebuild.

| Topic | Legacy `1.1.34` | Current `0.0.x` |
| --- | --- | --- |
| Main purpose | Older Aptoria application line | API QA evidence and release decision platform |
| Architecture | Legacy application structure | Fresh Laravel rebuild with project-scoped QA modules |
| UI | Older interface direction | Dedicated Aptoria UI direction |
| Evidence | Not the primary product core | Evidence Repository with checksum-backed records |
| API workflow | Limited compared with current direction | Endpoint inventory, safe scans, imports and native test evidence |
| Release approval | Not the central workflow | Release Gate and Decision Package workflow |
| Reports | Earlier report/export direction | Report Visual Standard and evidence pack exports |
| Access | Legacy account/project assumptions | Project membership and role foundation |
| License/runtime | Not the current portable authority model | Local activation package plus optional aptoria.dev lease direction |
| Upgrade path | Historical reference only | Fresh install / repository replacement |

## Operational conclusion

Do not attempt to migrate a live `1.1.34` database directly into `0.0.x`. Use the old line as an archive and install the current package into a clean environment.
