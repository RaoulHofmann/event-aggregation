# Privacy Policy

This policy explains how EventJunction handles information when you use its website, workspaces, integrations, or API.

## Information we handle

- **Accounts and workspaces.** We store your name, email address, password hash, workspace memberships, invitations, and account approval status. Platform administrators can review and manage accounts and workspaces. We record actions such as approvals and suspensions.
- **Event content.** We store events you create or import, including titles, descriptions, dates, time zones, locations, and any custom field values you choose to enter. Only include personal information that you have permission to use.
- **Connected services.** When you connect a provider, we store the credentials or private feed URL needed to keep the connection working. Integration credentials are encrypted in storage. We also keep connection settings, sync results, and source identifiers used to match imported events.
- **Support tickets.** We store the subject and message you submit with your account and workspace so platform administrators can respond to your enquiry.
- **Operation and security.** We use session and security cookies to keep you signed in and protect requests. Technical logs and error reports may include request details, IP addresses, and device or browser information. We use these records to operate and secure the service and diagnose problems.

## How we use and share information

We use this information to provide the service, review registrations, manage access, import events, respond to enquiries, and maintain reliability and security. We do not sell personal information or use imported calendar data for advertising.

Workspace members and people with a valid API token and the appropriate permissions can access data in the applicable workspace. A team may use the API to display event information on its own website or app. Review what you import and what you share through those destinations.

Service providers help us host, store, deliver, and monitor the service. These may include Fly.io for hosting, Supabase for database hosting, and providers for error monitoring or email (such as Resend) where configured. If enabled, Cloudflare Turnstile helps screen registrations for automated abuse. They may process information outside your country. We may also disclose information where required by law or to address security incidents or abuse.

## Google Calendar

When a workspace owner connects Google Calendar, EventJunction requests two read-only permissions. One lists the calendars the Google account can read, so the owner can choose which calendars to connect. The other retrieves events from the selected calendars for import and refresh.

We store the Google account’s primary calendar identifier to recognise the connection, the selected calendar names and identifiers, and the OAuth tokens needed for scheduled imports. Imported records include event titles, descriptions, locations, dates, times, time zones, status, source links, and the identifiers needed to match events during refresh. We do not store structured attendee lists, attachments, or conferencing credentials from the Google Calendar API. Event descriptions and locations may contain personal information entered by event organisers. EventJunction never writes changes to Google Calendar.

Google events arrive as drafts. Workspace members and API clients with the applicable permissions can access imported records. A workspace may share events through an API client it authorises, or publish events and enable a public widget that displays titles, dates, times, and locations. Choose calendars and review event details before sharing them, including before giving someone an API key that can access drafts. We use Google data to provide and maintain the Calendar import and these user-facing event features.

EventJunction’s use and transfer of information received from Google APIs to any other app will adhere to the [Google API Services User Data Policy](https://developers.google.com/terms/api-services-user-data-policy), including the Limited Use requirements.

## How we protect sensitive data

- **Encryption in transit.** EventJunction’s public site and its connections to Google use HTTPS. Our production application is configured to require TLS for connections to its database.
- **Protection of stored credentials.** We encrypt Google OAuth access and refresh tokens and other integration credentials before storing them in the database. Google tokens are kept on the server and used to authorise imports from the calendars you select.
- **Workspace access controls.** Workspaces store event data in separate database schemas. The application checks workspace membership and permissions before granting access. Only workspace owners can connect or manage Google Calendar integrations. Imported records start as drafts, and the public widget only includes published events from a workspace that has enabled it.

## Your choices and retention

- **Disconnect Google Calendar** under Integrations to stop future imports and clear the stored connection credentials. You can also revoke EventJunction’s access on the [Google account connections page](https://myaccount.google.com/connections). Disconnecting keeps previously imported events and the connection’s settings in your workspace.
- **Delete imported events** from the Events page to remove them from the workspace interface, API, and public widget. Event deletion retains a record in storage to prevent the next sync from bringing the event back. You can request permanent deletion of stored Google data using the privacy contact below.
- **Manage access** through your workspace’s membership settings and revoke API tokens that are no longer needed.
- **Delete your account** from your profile where that option is available. For access, correction, deletion, or other privacy requests, use the contact information below. If your information was added by a workspace, its owner can also help with the records they manage.

We keep information for as long as needed to provide the service and for legitimate operational, security, or legal purposes. Copies may remain temporarily in backups or logs. Where applicable law gives you additional privacy rights, you can exercise them by contacting us.

## Changes

We’ll post updates to this policy here. Where required, we’ll provide additional notice of material changes.
