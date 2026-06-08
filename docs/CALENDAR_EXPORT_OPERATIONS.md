# Calendar Export Operations

Version: v1.0.39

Aptoria provides two calendar export surfaces.

## JSON feed

Route:

```text
/calendar/feed.json
```

Supported query filters:

```text
project_id=<id>
month=YYYY-MM
from=YYYY-MM-DD
to=YYYY-MM-DD
```

Use this for dashboards, custom scripts or future integrations.

## iCalendar export

Route:

```text
/calendar/export.ics
```

The generated `.ics` file includes stored calendar events from the selected range. Virtual monitor run previews are intentionally not exported as persisted appointments, because they are operational previews based on `next_run_at`.

## Recommended use

- Use the internal calendar as the source of truth.
- Export `.ics` for external visibility only.
- Do not treat external calendar edits as synced changes.
- Keep release checkpoints and alert follow-ups in Aptoria if they affect QA evidence or release sign-off.
