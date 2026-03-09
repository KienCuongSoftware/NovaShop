# Learn Laravel

A Laravel 12 application demonstrating CRUD operations for **Categories** and **Products**, with image upload support and Bootstrap-based UI.

## Requirements

- **PHP** ^8.2
- **Composer**
- **Node.js** and **npm** (optional, for frontend tooling)
- **MySQL** (or another database supported by Laravel)

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Learn_Laravel
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Configure your database in `.env` (e.g. `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

4. **Run migrations**
   ```bash
   php artisan migrate
   ```

5. **Create storage link** (for product images)
   ```bash
   php artisan storage:link
   ```

6. **(Optional) Install frontend dependencies and build**
   ```bash
   npm install
   npm run build
   ```

## Running the Application

```bash
php artisan serve
```

Visit `http://localhost:8000`. The home page redirects to the Categories index.

### Development (with queue and logs)

```bash
composer run dev
```

This runs the web server, queue worker, log tail (Pail), and Vite dev server concurrently.

## Features

- **Categories:** Create, read, update, delete categories.
- **Products:** Full CRUD with category association, optional image upload, price, quantity, and active flag.
- **UI:** Bootstrap 4 layout, confirmation modals for delete actions, flash messages for success feedback.

## Project Structure (relevant parts)

- `app/Http/Controllers/` — `CategoryController`, `ProductController`
- `app/Models/` — `Category`, `Product`
- `resources/views/` — Blade views under `layouts/`, `categories/`, `products/`
- `routes/web.php` — Web routes (resource routes for categories and products)
- `database/migrations/` — Tables for categories, products, cache, jobs, users

## Testing

```bash
php artisan test
```

## Code Style

The project uses [Laravel Pint](https://laravel.com/docs/pint) for PHP code style:

```bash
./vendor/bin/pint
```

## License

This project is open-sourced software licensed under the [MIT License](LICENSE).

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for details on how to contribute and our code of conduct.

## Security

For security-related issues, see [SECURITY.md](SECURITY.md).
