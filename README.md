# NovaShop

A Laravel 12 e-commerce–style application with **categories** (hierarchical), **products**, **brands**, admin panel, user authentication (including Google OAuth), shopping cart, and orders.

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
   For Google login, set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `.env`.

4. **Run migrations**
   ```bash
   php artisan migrate
   ```

5. **Create storage link** (for product images, brand logos, category images, avatars)
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

Visit `http://localhost:8000`. The home page shows the product listing (welcome). Use **All categories** to browse by category; category pages support filtering by brand and search by product name.

### Development (with queue and logs)

```bash
composer run dev
```

Runs the web server, queue worker, log tail (Pail), and Vite dev server concurrently.

## Features

### Frontend (guest & logged-in)

- **Home** — Product listing with sort, pagination, category/brand filters, and text search (phrase match on product name).
- **All categories** — Tree of categories (parent/child).
- **Category page** — Products in that category (and descendants); brand filter in sidebar; optional brand in URL (slug).
- **Search** — Query by product name.
- **Product detail** — Single product view.
- **Auth** — Register, login, logout; Google OAuth (Laravel Socialite).
- **Profile** — View/edit profile; optional avatar upload.
- **Cart** — Add, update quantity, remove items.
- **Orders** — Order history and checkout.

### Admin (auth + admin middleware)

- **Dashboard** — Overview.
- **Products** — Full CRUD; category (two-step parent/child), brand, image, price, quantity, active; filter by root category; search by name.
- **Categories** — Full CRUD; hierarchical (parent_id); **image only for root categories** (hidden when creating/editing a child); **unique name per same parent level**.
- **Brands** — Full CRUD with logo upload; **unique brand name**.
- **Users** — List, create, edit, view; search by name/email.

All admin list pages use a shared search bar and consistent styling (rounded inputs, alignment).

### Business rules

- **Brands:** Name must be unique.
- **Categories:** Name must be unique among siblings (same parent); image field only for root categories; converting a root to a child clears its image.
- **Products:** Optional category and brand; images stored in `storage/app/public/products`.

## Project Structure (relevant parts)

| Path | Description |
|------|-------------|
| `app/Http/Controllers/` | `WelcomeController` (front listing, category, search), `ProductController`, `CategoryController`, `BrandController`, `UserController`, `AuthController`, `CartController`, `OrderController`, `AdminController` |
| `app/Models/` | `Category`, `Product`, `Brand`, `User`, `Cart`, `CartItem`, `Order`, `OrderItem` |
| `resources/views/` | `layouts/` (admin, user), `welcome.blade.php`, `all-categories.blade.php`, `products/`, `admin/` (dashboard, products, categories, brands, users), `auth/`, `user/` (cart, orders), `profile/` |
| `routes/web.php` | Web routes: welcome, categories, search; auth; cart & orders (auth); admin resource routes; storage image routes for products, brands, categories, avatars |

## Image storage

Files are stored under `storage/app/public/` and served via custom routes:

- **Products** — `/images/products/{filename}`
- **Brand logos** — `/images/brands/{filename}`
- **Category images** — `/images/categories/{filename}` (root categories only)
- **User avatars** — `/images/avatars/{filename}`

Run `php artisan storage:link` so `public/storage` points to `storage/app/public`.

## Testing

```bash
php artisan test
```

## Code style

The project uses [Laravel Pint](https://laravel.com/docs/pint) for PHP code style:

```bash
./vendor/bin/pint
```

## License

This project is open-sourced software licensed under the [MIT License](LICENSE).
