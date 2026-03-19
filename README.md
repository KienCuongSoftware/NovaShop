# NovaShop

A Laravel 12 e-commerce demo application: hierarchical categories, products (variants & attributes), brands, flash sales, product reviews, address book with OpenStreetMap/Leaflet, distance-based shipping fees, cart, orders (COD / PayPal), and an admin panel.

**Repository:** [https://github.com/KienCuongSoftware/NovaShop](https://github.com/KienCuongSoftware/NovaShop)

## Requirements

- **PHP** ^8.2
- **Composer**
- **Node.js** and **npm** (optional, for Vite/frontend)
- **MySQL** (or another Laravel-compatible database)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/KienCuongSoftware/NovaShop.git
   cd NovaShop
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
   Configure the database in `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).  
   For Google login: set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.  
   For PayPal: set `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_MODE`.  
   For shipping: optionally set `SHIPPING_WAREHOUSE_LAT`, `SHIPPING_WAREHOUSE_LNG` (default: Hanoi).

4. **Run migrations**
   ```bash
   php artisan migrate
   ```

5. **Create storage link** (product images, brand logos, category images, avatars)
   ```bash
   php artisan storage:link
   ```

6. **(Optional) Seed sample data**
   ```bash
   php artisan db:seed
   ```

7. **(Optional) Frontend**
   ```bash
   npm install
   npm run build
   ```

## Running the Application

```bash
php artisan serve
```

Visit `http://localhost:8000`. The home page shows products, flash sale, and suggestions; use **All categories** to browse by category; category pages support brand filter and search by name.

### Development (queue + logs)

```bash
composer run dev
```

Runs the web server, queue worker, Pail (logs), and Vite dev server concurrently.

## Features

### Frontend (guest & logged-in)

- **Home** — Product listing with sort, pagination, category/brand filters, search by name; flash sale by time slot; “Today’s suggestions” block.
- **All categories** — Tree of parent/child categories.
- **Category page** — Products in that category (and descendants); brand filter; search by name; slug in URL.
- **Search** — By product name.
- **Product detail** — Images, variants (color/size, etc.), price, stock, flash sale; **reviews with pagination** (filter by stars, AJAX); add to cart.
- **Auth** — Register, login, logout; Google OAuth (Socialite).
- **Profile** — View/edit profile; avatar upload.
- **Address book** — Add/edit/delete addresses; **pick location on map** (Leaflet + OpenStreetMap, Nominatim search, reverse geocoding, current location); set default address.
- **Cart** — Add, update quantity, remove; choose variant (color/size) when applicable.
- **Checkout** — Choose saved address or enter new one (with map); **shipping fee by distance** (Haversine from warehouse to address); COD or PayPal.
- **Orders** — Order history, filter by status/shipping, search; cancel order; pay via PayPal for unpaid orders.

### Admin (auth + admin middleware)

- **Dashboard** — Stats (products, orders, users, categories, revenue); charts (orders by status, last 30 days); latest orders.
- **Products** — CRUD; category, brand, images; **variants** (color/size attributes, price, stock, SKU, per-variant images); flash sale by variant; search, filter by category.
- **Categories** — CRUD; hierarchical (parent_id); image for root only; unique name per sibling; manage children on edit page.
- **Brands** — CRUD, logo upload; unique name.
- **Attributes** — CRUD attributes (e.g. Color, Size) and values; used by product variants.
- **Flash sales** — CRUD flash sales by time slot; add/edit/remove items (variant + sale price, quantity); paginated list.
- **Orders** — List, filter by status/shipping, search by ID/phone/address/name/email; order detail; **update status** (processing, shipping, completed, cancelled); show shipping fee & distance when present.
- **Inventory logs** — View stock logs (import/export/adjust); filter by type, search by source/note/product name/order ID.
- **Users** — List, create, edit, view; search by name/email.
- **Account** — Dedicated admin profile page (edit name, email, password, avatar).

Admin list pages share a common search bar (rounded inputs, red search button) and use 7 items per page for main lists.

### Business rules

- **Brands:** Unique name.
- **Categories:** Unique name among siblings (same parent); image only for root categories.
- **Products:** May have **variants** (price and stock per variant); displayed price/stock = min/sum from variants when present; images may be product-level or per variant.
- **Orders:** Standard statuses (unpaid, pending, processing, shipping, completed, cancelled, etc.); separate **shipping_status**; address/phone snapshot stored; **shipping fee** and **distance** stored per order; stock deducted and **inventory_logs** written on place/cancel.
- **Addresses:** Store lat/lng (Leaflet/Nominatim); used for shipping fee (Haversine from warehouse, tiered fee by km in `config/shipping.php`).

## Project structure (main parts)

| Path | Description |
|------|-------------|
| `app/Http/Controllers/` | `WelcomeController`, `ProductController`, `CategoryController`, `BrandController`, `AttributeController`, `AuthController`, `UserController`, `AddressController`, `CartController`, `CheckoutController`, `OrderController`, `PayPalController`, `FlashSaleController`, `AdminController`, `AdminOrderController`, `AdminInventoryLogController`, `AdminProfileController` |
| `app/Models/` | `Category`, `Product`, `ProductVariant`, `ProductImage`, `ProductReview`, `Brand`, `Attribute`, `AttributeValue`, `User`, `Address`, `Cart`, `CartItem`, `Order`, `OrderItem`, `Payment`, `FlashSale`, `FlashSaleItem`, `InventoryLog` |
| `app/Services/` | `ShippingFeeService` (Haversine distance, fee by km tiers) |
| `config/shipping.php` | Warehouse coordinates, fee tiers by km, default fee when no coordinates |
| `resources/views/` | `layouts/` (admin, user), `welcome.blade.php`, `all-categories.blade.php`, `products/`, `partials/` (leaflet-address-picker), `admin/` (dashboard, products, categories, brands, attributes, flash_sales, orders, inventory_logs, users, profile), `auth/`, `user/` (cart, orders, checkout, addresses), `profile/` |
| `routes/web.php` | Welcome, categories, search; auth; profile, address book, cart, checkout, orders, PayPal; flash sale API; image routes (products, brands, categories, avatars); admin group |

## Image storage

Files are stored under `storage/app/public/` and served via custom routes:

- **Products** — `/images/products/{filename}`
- **Brand logos** — `/images/brands/{filename}`
- **Category images** — `/images/categories/{filename}` (root categories only)
- **Avatars** — `/images/avatars/{filename}`

Run `php artisan storage:link` so `public/storage` points to `storage/app/public`.

## Testing

```bash
php artisan test
```

## Code style

The project uses [Laravel Pint](https://laravel.com/docs/pint):

```bash
./vendor/bin/pint
```

## License

This project is open-sourced under the [MIT License](LICENSE).
