# Move EventJunction's database to Supabase

This is a fresh-start cutover. Fly continues to run Laravel, the API, and import workers. Laravel still manages sign-in, workspaces, and API tokens. Existing users, events, templates, connections, and tokens stay in the old database. Google Calendar and Humanitix can import again after the new workspace is connected.

## Prepare the new database

1. Create a dedicated Supabase project. Choose a region close to the Fly app, which currently runs in Sydney. Disable the Supabase Data API for this project because browsers access the database through Laravel. Keep the database URL on the server.
2. In Supabase's **Connect** panel, copy the **Direct connection** URL if the Fly machine can reach it. Otherwise, use the **Session pooler** URL on port **5432** for IPv4. Keep the username and host as provided. Use the database password, with special characters percent-encoded in the URL. The transaction pooler (port 6543) is unsuitable because EventJunction sets a session-level PostgreSQL search path.
3. Require TLS. For staging and production, use `DB_SSLMODE=verify-full` and set `DB_SSLROOTCERT` to the trusted CA certificate path for the selected Supabase endpoint **inside the app machine**, with the certificate installed there (including in Fly release machines). Remove any conflicting `sslmode` query parameter from the connection URL because URL options take precedence. Keep the password out of source control and logs.
4. Configure a separate staging instance with `DB_CONNECTION=pgsql` and the new `DATABASE_URL`. Run the following using the same database role that Fly will use. The probe rolls back its temporary schema and tables. It checks permissions and namespace resolution, so still select the connection mode explicitly.

   ```sh
   php artisan eventjunction:check-postgres
   php artisan migrate --force
   php artisan teams:migrate
   ```

The database role must be able to create tables in `public` and create workspace schemas. Use a dedicated project and a server-only role. The Fly release runs both migrations, then provisions the initial platform administrator. On an empty database, the release requires `EVENTJUNCTION_ADMIN_EMAIL` and `EVENTJUNCTION_ADMIN_PASSWORD` (16–72 bytes) as Fly secrets. Bootstrap never promotes an existing account or resets an existing admin password.

For Fly, the CA file can be mounted using a base64-encoded Fly secret and this app configuration. Add the mount only when switching to a deployment that has the secret staged.

```toml
[[files]]
  guest_path = "/etc/eventjunction/supabase-ca.crt"
  secret_name = "SUPABASE_DB_CA"
```

Set `DB_SSLROOTCERT=/etc/eventjunction/supabase-ca.crt`. Stage the database URL securely through Fly secrets and keep credentials out of shell history. Verify that the CA file is available to both the app and its release command before cutover.

## Verify and switch Fly

- Back up the old database, including all workspace schemas, and retain the previous `DATABASE_URL` securely. Keep the existing `APP_KEY` and provider settings on Fly.
- Test registration, creation of two separate workspaces, and data isolation on staging. Check one API read using a newly minted token and one inbound import. To test live Google consent on a staging hostname, register that exact callback URI and configure it there.
- Keep staging fixtures out of production. Use a fresh target project for the actual cutover so staging accounts and events cannot appear in production. For an empty live app where a staging test is unnecessary, use the normal Fly release directly. Run the probe against the final target too.
- When ready to cut over, stage the new `DATABASE_URL`, TLS settings, and CA file on the production Fly app, then deploy so the release command runs before new app machines serve traffic. Check `/up`, sign in with the bootstrap administrator, open /admin, verify approval and workspace creation, and reconnect integrations. Once you can sign in, you can remove the bootstrap credentials from Fly secrets. Preserve the old database during verification.
- If rollback is needed, restore the previous database URL **and** its previous TLS settings, then redeploy. Writes in the new database will stay there. Import credentials, API tokens, and accounts from either database do not follow a connection switch.

Supabase Auth is a separate project. Adopting it later will require mapping Supabase identities to Laravel users and preserving workspace membership, invitations, and API access.
