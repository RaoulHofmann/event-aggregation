# EventNest API

`GET /api/v1/events` returns native and imported events for the authenticated user's current workspace. Supply `Authorization: Bearer <token>` and `Accept: application/json`. Create a token with `read` permission in API access. Workspace membership is checked before route binding or tenant queries.

Filters: `source=eventnest|humanitix`, `status=draft|published|cancelled`, inclusive ISO 8601 `starts_after` and `starts_before`, one-based `page`, and `per_page` (1–100, default 25). Results contain `data`, `links` and `meta`. Rate limit: 60 requests/minute per authenticated user.

Each event includes canonical title, description, start/end timestamps, IANA timezone, location, status, source metadata, template and custom fields. UTC timestamps and the event timezone are separate. Drafts are included for authenticated integrations; use `status=published` for a public listing.

The legacy `/api/events` remains available with template ID/name filtering and grouped responses. Native events are managed in EventNest. Imported events are edited at their source. Regenerate Swagger documentation with `php artisan l5-swagger:generate`.
