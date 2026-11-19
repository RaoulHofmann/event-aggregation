# Inbound event imports

Workspace owners can open **Integrations**. New events arrive as drafts. All sources can use a workspace template and optional field mapping. Remote reads never create, update, or delete source events. Editing a field in EventJunction protects that field on refresh, and deleting a local copy keeps it hidden.

| Source | Setup |
| --- | --- |
| Calendar feed (`ics`) | Paste the final HTTPS iCalendar URL. An unlisted link can act as a password. The URL is encrypted and never shown back. Recurring instances and exceptions are expanded in the import window. |
| CSV (`csv`) | Upload a UTF-8 CSV with `title` as a header. Optional columns are `starts_at`, `ends_at`, `timezone`, `location`, `description`, `all_day`, and `external_id`. Preview errors before importing. Use ISO dates, and provide an exclusive end date for multi-day events. Use the same `external_id` on later uploads to update the same row. Rows without IDs match by content. |
| WordPress (`wordpress`) | Enter the public HTTPS site root for a site with The Events Calendar’s `/wp-json/tribe/events/v1/events` endpoint enabled. Only the public endpoint is read. |
| Eventbrite (`eventbrite`) | Enter your Eventbrite organisation ID and personal OAuth token with access to that organisation. EventJunction reads the organisation’s own event listings and venue details. Use an account with appropriate access. |

The homepage and workspace integrations page both have step-by-step guides. For Humanitix and Google Calendar, see their [Humanitix setup](HUMANITIX.md) and [Google Calendar setup](GOOGLE_CALENDAR.md) documentation.

For WordPress and Eventbrite, select **Create a template for this source** under **Event template**. EventJunction creates an editable workspace template and links it to the connection for future imports. WordPress maps the listing link, organiser and categories. Eventbrite maps the ticket link, venue name and capacity. Fields stay empty when the source omits a value. You can also map these values to an existing template.

## Outlook calendar feed

1. In Outlook on the web, open **Settings → Calendar → Shared calendars → Publish a calendar**.
2. If publishing is available, choose the calendar and the level of detail you want to share. Use the generated **ICS** link. Treat it as private.
3. In EventJunction, open **Integrations → Calendar feed → Add connection**, paste the HTTPS ICS link, and select **Save and queue import**. Publishing availability depends on the account and organisation settings.

## Sync and limits

- The queue worker refreshes configured connections on the existing 15-minute schedule. Use **Sync now** to request an immediate refresh. CSV is a one-time import with a 1 MB and 500-row limit.
- Calendar feeds and WordPress use a rolling window from 90 days ago through one year ahead. Eventbrite imports the organisation's event list. Remote imports are limited to 2,000 events per run with bounded pagination.
- Use a public HTTPS endpoint for feeds and WordPress. Redirects and private-network addresses are rejected. Enter the final destination URL.
- Disconnecting or deselecting a calendar keeps imported events in EventJunction. Missing upstream records are not treated as deletions. Where a source reports an explicit cancellation, known copies are marked cancelled unless their status was overridden locally.
- The new provider keys are available in the Events source picker and in the `source` filter on both v1 event endpoints. IDs are scoped to the connection.
- Real-provider verification requires an authorised Eventbrite token and target feed URLs. Credentials belong in Fly secrets or the workspace connection form.

### CSV example

```csv
title,starts_at,ends_at,timezone,location,description,all_day,external_id
Garden workshop,2026-12-03T10:00,2026-12-03T11:30,Australia/Perth,Community garden,Bring gloves,false,workshop-2026-12
Open day,2026-12-05,2026-12-06,Australia/Perth,Library,Drop in any time,true,open-day-2026
```
