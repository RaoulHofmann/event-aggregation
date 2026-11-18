# Google Calendar: inbound only

Google Calendar supplies events to EventNest. EventNest never creates, changes or deletes Google events. Calendar API traffic is GET-only. OAuth code exchange and token renewal use POST exclusively at `https://oauth2.googleapis.com/token`.

## Installation

1. In https://console.cloud.google.com/ create a project and enable **Google Calendar API**.
2. In **Google Auth Platform**, enter branding/support details, select an audience (External for accounts outside one Workspace organisation), and add test users while in Testing.
3. Add these read-only scopes under Data Access:
   - `https://www.googleapis.com/auth/calendar.calendarlist.readonly`
   - `https://www.googleapis.com/auth/calendar.events.readonly`
4. Create a **Web application** OAuth client. Add the exact redirect URI `https://event-aggregator.fly.dev/integrations/google/callback`. A server-side flow needs no JavaScript origin. For development add the exact local callback separately.
5. Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` as application secrets, never source files or chat messages. On Fly:

   ```sh
   flyctl secrets set --app event-aggregator \
     GOOGLE_CLIENT_ID='YOUR_CLIENT_ID' \
     GOOGLE_CLIENT_SECRET='YOUR_CLIENT_SECRET' \
     GOOGLE_REDIRECT_URI='https://event-aggregator.fly.dev/integrations/google/callback'
   ```

6. Apply public and tenant migrations during release. The existing Fly release command runs both. Ensure the production callback is available before connecting.
7. As a workspace owner, open **Integrations → Connect Google**, approve both read-only permissions, choose calendars, optionally select an existing event template and field mappings, then **Save and import**.

External apps in Testing generally receive refresh tokens that expire after seven days with these scopes. For long-term public access, complete Google's production consent/verification requirements (including verified domains and privacy information if required). Reconnect when authorization expires or is revoked. Disconnect drops local credentials; users can also revoke EventNest from their Google account's third-party permissions.

## Import behaviour

- Up to 10 readable calendars per connection. Calendar ID and event ID jointly identify an import, so overlapping IDs in different calendars cannot collide. Reconnecting the original account keeps the same imported IDs.
- Bounded full refresh: 90 days back through one year ahead. Google expands repeating series and exceptions (`singleEvents=true`). Each occurrence becomes an ordinary editable EventNest event, with exclusive all-day end dates and the source timezone preserved. This first version does not mirror Google RRULEs or offer incremental/webhook sync.
- Imports default to **draft**, including events from private calendars. Explicit local publication is protected on refresh. No attendees or conferencing credentials are imported.
- Built-in fields always map. Optional template field mapping validates value types; missing/incompatible answers produce source-snapshot warnings and remain editable locally.
- Local field changes, templates/custom answers, and schedule changes survive refresh. Local deletion is a soft deletion that later refreshes do not undo.
- Google cancellations update the source snapshot; local status overrides win. A missing item alone never deletes anything: after a complete list, known in-window events missing from that response are checked individually. Confirmed 404/410 after calendar access validation marks that import cancelled. Permission failures and incomplete pages fail safely without inferring deletions. Imports outside the rolling window remain stored and aren't continuously reconciled until back in scope.
- Deselected calendars and disconnected accounts keep previously imported events.
- Refreshes are queued every 15 minutes while scheduler and worker run. Fly scale-to-zero means automatic refresh is not guaranteed while the app sleeps; use Sync now or configure always-on workers before relying on unattended freshness.
- `/api/v1/events?source=google_calendar` and `/api/v1/occurrences?source=google_calendar` filter imports; omit source to include native events and all providers. Use `status=published` for public consumption.

## Security and troubleshooting

Tokens are encrypted at rest, hidden from browser props, and excluded from job payloads. OAuth state and PKCE are single-use, time-bound and bound to the initiating owner and workspace. Reconnect requires the original primary Google account. Network failures and provider error bodies are replaced with safe messages.

`redirect_uri_mismatch`: match the callback exactly in Google and Fly, including HTTPS, hostname and path. `access_denied`: add the account as a test user and grant both permissions. Offline access missing: remove EventNest from Google account permissions, then reconnect. Never share OAuth codes, refresh tokens or client secrets.

Cloud development egress needs accounts.google.com (browser), oauth2.googleapis.com and www.googleapis.com. Test coverage uses HTTP fixtures; live consent/import requires the configured Google project and an owner completing consent in their browser.
