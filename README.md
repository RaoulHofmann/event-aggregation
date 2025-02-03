# EventNest

EventNest is a modern event management platform designed to simplify event discovery, organization, and integration. Built with an API-first approach, EventNest allows developers to seamlessly integrate event data into their applications. The platform supports dynamic event templates, customizable fields, and real-time updates, making it a powerful tool for managing events of any scale.

## Features

* **API-First Design**: Easily integrate EventNest into your applications using our robust API.
* **Dynamic Event Templates**: Create and manage custom event templates with flexible fields.
* **Upcoming SDK**: Simplify integration with our upcoming SDK (coming soon).
* **Customizable Fields**: Define custom fields for event templates to suit your specific needs.

## Technologies Used

* **Frontend**: Vue.js, Tailwind CSS
* **Backend**: Laravel (API)
* **Database**: MySQL or PostgreSQL
* **Other Tools**: Inertia.js, Vite

## Getting Started

### Prerequisites

* PHP 8.1 or higher
* Composer
* Node.js 16 or higher
* MySQL or PostgreSQL

### Installation

1. **Clone the Repository**:
```bash
git clone https://github.com/your-username/eventnest.git
cd eventnest
```

2. **Install Backend Dependencies**:
```bash
composer install
```

3. **Install Frontend Dependencies**:
```bash
npm install
```

4. **Set Up Environment Variables**:
   * Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
   * Update the `.env` file with your database credentials and other settings.

5. **Generate Application Key**:
```bash
php artisan key:generate
```

6. **Run Migrations**:
```bash
php artisan migrate
```

7. **Seed the Database (Optional)**:
```bash
php artisan db:seed
```

8. **Compile Frontend Assets**:
```bash
npm run dev
```

9. **Start the Development Server**:
```bash
php artisan serve
```

10. **Access the Application**: Open your browser and navigate to `http://localhost:8000`.

## Usage

1. **Create Event Templates**:
   * Use the provided interface to create event templates with custom fields.

2. **Add Events**:
   * Add events using the dynamic forms generated from your event templates.

3. **View Events**:
   * Events are grouped by their template, and each group is displayed in a dynamically generated table.

4. **Integrate with API**:
   * Use the API to fetch, filter, and manage events programmatically.

## API Documentation

The API documentation is available at `/api/docs` (if using a tool like Swagger) or in the `docs` folder of the repository.

## Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Commit your changes and push to your branch.
4. Submit a pull request with a detailed description of your changes.

## License

This project is licensed under the MIT License. See the LICENSE file for details.
