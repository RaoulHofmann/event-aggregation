# Registration alerts

EventJunction uses Resend to email the first active platform administrator when a registration is pending review. The worker uses the email address on that account at delivery time. The message includes the applicant’s name and email and links to the pending list in Administration. It is sent by Laravel’s queue worker. This feature does not change the mailer used by other application emails.

1. In Resend, add and verify a sending domain you control using the DNS records shown in the dashboard. Use a sender on that verified domain, such as `registrations@eventjunction.app` if you verified the apex.
2. Create a sending-only Resend API key for the verified domain.
3. Set these secrets on the production Fly app `event-aggregator`. Use the real values in Fly settings and keep the key out of source control and chat.

   | Setting | Value |
   | --- | --- |
   | `RESEND_KEY` | Sending-only key from Resend |
   | `MAIL_FROM_ADDRESS` | Address on the verified sending domain |
   | `MAIL_FROM_NAME` | Optional sender name, such as EventJunction |

4. Once the code and secrets are live, submit one test registration and check the pending list and inbox. Approved or removed registrations no longer waiting for review are skipped.

Signups are checked for a filled hidden field, a form that was opened and allowed time to render, and more than 12 attempts per IP in 10 minutes. Registrations blocked by these checks never reach the mail queue. Only 10 registration email delivery attempts, including retries, are allowed in an hour. Entries that exceed this budget still appear in Administration. If Resend is unconfigured or no platform administrator is active, registrations still work and alerts are skipped.

For a stronger challenge, create a **managed Cloudflare Turnstile widget** for `eventjunction.app`. Set both `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` together on Fly. With Turnstile enabled, the server requires a valid challenge for the canonical site hostname before it creates an account. Leave both settings unset to use the built-in checks only.
