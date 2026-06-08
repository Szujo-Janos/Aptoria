# Aptoria QA Operations Calendar

Version: v1.0.39

The QA Operations Calendar turns Aptoria from a passive evidence store into a lightweight operations planner. It is not a Google Calendar clone; it is scoped to API QA work.

## Event types

- Manual QA task
- Regression retest
- Release checkpoint
- Maintenance window
- Alert follow-up
- Security review
- Monitor run preview

## Links to Aptoria evidence

A calendar event can optionally link to:

- project
- endpoint
- scheduled monitor
- monitor alert event
- QA release gate

This keeps calendar items connected to the stored QA context instead of becoming detached notes.

## Main views

- global calendar: `/calendar`
- project calendar: `/projects/{project}/calendar`
- event create/edit forms
- monthly grid view
- upcoming events table
- scheduled monitor next-run preview

## Alert follow-up workflow

From a monitor alert history page, create a follow-up event with a due date. This is intended for:

- retesting a failed monitor
- scheduling regression investigation
- confirming recovery after acknowledgement
- tracking operational accountability after alert triage

## Exports

- JSON feed: `/calendar/feed.json`
- iCalendar export: `/calendar/export.ics`

The `.ics` export is one-way and intentionally simple. It can be imported into external calendar clients without introducing OAuth or two-way synchronization complexity.
