# EventJunction

**EventJunction** is a modern, multi-tenant event management platform designed to simplify event discovery, organization, and integration. Built with an API-first approach, EventJunction enables teams to manage their events in isolated environments using **PostgreSQL schemas for per-team data separation**, ensuring secure and scalable multi-tenancy.

## Key Highlights

- **Multi-Tenant Architecture**: Supports isolated environments for each team using PostgreSQL schemas—each team gets its own schema, ensuring data separation and scalability.
- **API-First Design**: Easily integrate EventJunction into your applications with our robust and well-documented API.
- **Dynamic Event Templates**: Create and manage custom event templates with flexible and dynamic fields.
- **Customizable Fields**: Tailor events to your specific use case with configurable fields per template.
- **Real-Time Updates**: Stay in sync with live updates and changes to event data.
- **Upcoming SDK**: Developer-friendly SDK coming soon to further simplify integration.

## Technologies Used

- **Frontend**: Vue.js, Tailwind CSS  
- **Backend**: Laravel (API)  
- **Database**: PostgreSQL (multi-tenancy via schemas) or MySQL (single-tenant only)  
- **Other Tools**: Inertia.js, Vite

## Development and roadmap

Requires PHP 8.5, Composer 2, Node/npm, and PostgreSQL for workspace schemas. Set `DB_CONNECTION=pgsql` and `DATABASE_URL` in an ignored local .env file, preserving APP_KEY. Run `composer install`, `npm ci`, `php artisan migrate`, `php artisan teams:migrate`, and `npm run build`. Start Laravel, the queue worker and scheduler as described in [docs/HUMANITIX.md](docs/HUMANITIX.md).

Run tests only against a separate database: RefreshDatabase resets tables. See [TODO.md](TODO.md), [docs/API.md](docs/API.md), and [docs/HUMANITIX.md](docs/HUMANITIX.md). Humanitix is fixture-tested; live validation requires network access and an authorized key.

## Demo

A live demo is available at: [https://event-aggregator.fly.dev](https://event-aggregator.fly.dev)  
_⚠️ Note: The server uses on-demand hosting and may take a few seconds to spin up after inactivity._

username: test@example.com
password: password

## Usage

1. **Create Event Templates**  
   Use the web interface to define event templates with custom fields for each tenant or use case.

2. **Add Events**  
   Add events using dynamic forms generated from your configured templates.

3. **View Events**  
   Events are displayed in automatically generated tables, grouped by their template.

4. **Integrate with the API**  
   Use the API to programmatically fetch, filter, and manage events across different tenants.

For a fresh-start move to managed PostgreSQL, see the [Supabase cutover guide](docs/SUPABASE.md).

## API Documentation

API documentation is available at `/api/docs` (e.g., via Swagger) or can be found in the `docs` directory of the repository.

## Contributing

We welcome contributions from the community:

1. Fork the repository.  
2. Create a feature or bugfix branch.  
3. Commit your changes and push the branch.  
4. Submit a pull request with a detailed description.

## License

This project is licensed under the MIT License. See the [LICENSE](./LICENSE) file for more information.
