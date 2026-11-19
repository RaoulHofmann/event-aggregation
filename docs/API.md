# EventJunction API

See the [API guide](https://eventjunction.app/docs/api) and [interactive Swagger reference](https://eventjunction.app/api/documentation) for supported endpoints.

### Keys and write access

- **Read all** uses the account's current workspace unless the key also has write permissions. **Events and occurrences only** uses the workspace selected when the key was created and can read the v1 events and occurrences endpoints.
- A new read-only key can optionally save an encrypted copy that its owner can reveal in API Access. Write-enabled keys are shown once. Old keys were stored as hashes and cannot be recovered.
- Write permissions are separate for create and update. They belong to the selected workspace, and the key owner must still have the relevant workspace role. Owners and admins can write templates and fields. Editors can write events.

| Resource | Create | Replace | Permission prefix |
| --- | --- | --- | --- |
| Events | `POST /api/v1/events` | `PUT /api/v1/events/{id}` | `events` |
| Event templates | `POST /api/v1/event-templates` | `PUT /api/v1/event-templates/{id}` | `templates` |
| Custom fields | `POST /api/v1/field-templates` | `PUT /api/v1/field-templates/{id}` | `templates` |

Append `:create` or `:update` to the permission prefix. POST returns `201`, a `Location` header, and a `data` object. PUT returns `200` with the updated `data` object. Fetch the current record through GET at the corresponding item URL with a read permission. PUT replaces editable fields. Event PUT requires `title` and `status`, and any custom answers are supplied as `custom_fields` keyed by stable field ID. New events default to drafts. Imported event updates stay local and use the editor's existing override rules. Deletion remains a workspace action.

`GET /api/v1/events` returns native and imported events for the workspace accessible to the key. Supply `Authorization: Bearer <token>` and `Accept: application/json` without session cookies. Create a key with `read` or `events:read` permission in API Access. Workspace membership is checked before route binding or tenant queries.

Filters: `source=eventnest|humanitix|google_calendar|ics|csv|wordpress|eventbrite`, `status=draft|published|cancelled`, inclusive ISO 8601 `starts_after` and `starts_before`, one-based `page`, and `per_page` (1–100, default 25). Results contain `data`, `links` and `meta`. Rate limit: 60 requests/minute per authenticated user. Native EventJunction records continue to use the `eventnest` source value for API compatibility.

Each event includes canonical title, description, start/end timestamps, IANA timezone, location, status, source metadata, template and custom fields. UTC timestamps and the event timezone are separate. Drafts are included for authenticated integrations; use `status=published` for a public listing.

The legacy `/api/events` remains available with template ID/name filtering and grouped responses. Imported events can be edited locally with protected overrides. Regenerate Swagger documentation with `php artisan l5-swagger:generate`.

### Calendar occurrences

`GET /api/v1/occurrences?from=2027-03-01T00:00:00Z&to=2027-04-01T00:00:00Z`

Uses the same workspace membership and read-token permissions as `/api/v1/events`. Both bounds are required; `to` is exclusive and the window may be at most 93 days. Optional `search` (title), `source`, `template` (ID), and `status` filters apply before expansion. Windows exceeding 500 candidate events or 5,000 occurrences return 422; narrow filters/window. It includes events overlapping the window and rescheduled occurrences moved into it. Hidden/deleted events are excluded. Response: `{ "data": [...] }` sorted by start.

Each item includes stable `id` (`event_id@original-local-date`), `event_id`, `occurrence_date`, title, UTC `starts_at`/`ends_at`, local `start_date`/`end_date`, IANA timezone, `all_day`, status, provider source, source URL, template ID, custom fields and `recurring`. All-day end dates are exclusive. Single events also have occurrence IDs. `/api/v1/events` remains a paginated collection of source event/series records, now with additive `all_day` and `recurrence` fields; it does not expand repeats.

Native recurrence supports `frequency` daily/weekly/monthly/yearly, `interval` 1–100 and a required finite `count` 1–1000. Optional `until` is an inclusive local date. Weekly rules use `weekdays` (ISO Monday=1 through Sunday=7). Monthly rules use `monthly_mode` date or weekday (same ordinal weekday as the start). Invalid month-end/leap-day dates and nonexistent DST wall times are skipped. Timed events preserve elapsed duration; all-day series preserve local-day duration. Ambiguous fall-back times follow the timezone library's offset selection. Rules scan at most 1,000 frequency periods; use shorter series for very sparse patterns.

The authenticated management UI uses POST `/events/preview` to preview eight occurrences within ten years without persisting them, and POST `/events/{event}/exceptions` to skip or reschedule a valid `occurrence_date`. Exceptions require `cancelled`; rescheduling also requires start/end in the series timezone (or explicit offsets). Exception identity remains the original date. Imported events can be edited locally, including local recurrence exceptions; no writes are sent to their provider. A series with exceptions cannot change its rule/start/timezone/all-day mode; create a new series instead. “This and following” splitting, exception undo UI and provider recurrence mapping remain follow-ups.

Google Calendar imports use `source=google_calendar`. They arrive as drafts; publish locally before using a published-only API feed. Google expands repeating instances within a rolling 90-day-past/one-year-future import window. Each instance is an ordinary editable event in EventJunction; native recurrence remains separate. See [Google setup and semantics](GOOGLE_CALENDAR.md).
