# NovaShop

A **Laravel 12** e-commerce demo: hierarchical categories, products with variants & attributes, brands, flash sales, reviews, address book (Leaflet / OpenStreetMap), **distance-based shipping**, **cart coupons**, **wishlist**, **product compare** (up to 4 items), **“frequently bought together”** from order history, **back-in-stock email + in-app notifications**, cart & checkout, orders (**COD / PayPal**), and an **admin** panel.

**Repository:** [https://github.com/KienCuongSoftware/NovaShop](https://github.com/KienCuongSoftware/NovaShop)

## Requirements

- **PHP** ^8.2  
- **Composer**  
- **Node.js** and **npm** (optional — Vite / Tailwind)  
- **MySQL** (or any Laravel-supported database)

## Installation

1. **Clone**
   ```bash
   git clone https://github.com/KienCuongSoftware/NovaShop.git
   cd NovaShop
   ```

2. **Dependencies**
   ```bash
   composer install
   ```

3. **Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Set `DB_*` in `.env`.

   | Feature | `.env` keys |
   |--------|-------------|
   | Google login | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |
   | PayPal | `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_MODE` |
   | Shipping warehouse (Haversine) | `SHIPPING_WAREHOUSE_LAT`, `SHIPPING_WAREHOUSE_LNG` |
   | **Email (e.g. back-in-stock)** | `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD` — use an **app password** for Gmail; **never commit secrets** |

4. **Migrations**
   ```bash
   php artisan migrate
   ```

5. **Storage link** (product images, brands, categories, avatars)
   ```bash
   php artisan storage:link
   ```

6. **(Optional) Seed data**  
   Creates a demo user `test@example.com` if missing, sample orders/addresses/logs, then **NovaShop feature samples** (coupons, wishlist, compare, stock inbox demo) when enough active products exist.
   ```bash
   php artisan db:seed
   ```
   Or only feature samples (needs products + at least one non-admin user):
   ```bash
   php artisan db:seed --class=NovaShopFeaturesSampleSeeder
   ```

7. **(Optional) Frontend build**
   ```bash
   npm install
   npm run build
   ```

## Running

```bash
php artisan serve
```

Open `http://localhost:8000`.

### Development (server + queue + logs + Vite)

```bash
composer run dev
```

## Features

### Storefront (guest & authenticated)

- **Home** — Products, sort/pagination, filters, search; **flash sale** by time slot; suggestions / recent behaviour where implemented.
- **Categories** — Tree (`/all-categories`); category pages with slug URLs, brand filter, search.
- **Product detail** — Gallery, variants, flash pricing, stock, **reviews** (pagination, star filter, AJAX partial); **add to cart**; **wishlist** / **compare** / **notify when back in stock**; **“Frequently bought together”** from `order_items` co-occurrence.
- **Auth** — Register, login, logout; **Google OAuth** (Socialite).
- **Profile** — Edit profile; avatar upload.
- **Address book** — CRUD; map picker (Leaflet + Nominatim); default address; lat/lng for shipping.
- **Wishlist** — Per-user list (`/wishlist`).
- **Compare** — Up to **4** products, attribute comparison table (`/compare`).
- **Cart** — Add/update/remove; variant-aware; **apply / remove coupon**; totals respect active flash sale prices where applicable.
- **Checkout** — Saved or new address + map; **shipping fee by distance**; shows subtotal, discount, shipping, total; **COD** or **PayPal**.
- **Orders** — History, filters, cancel where allowed; PayPal retry for unpaid/failed.
- **Stock alerts inbox** — `/notifications/stock`: rows after a back-in-stock email was sent (demo rows possible via seeder).

**Header (logged in):** quick links (heart / compare / bell / cart) with badges; account dropdown (profile, orders, addresses, logout) — wishlist/compare/alerts are **not** duplicated in the dropdown.

### Admin (`auth` + `admin` middleware)

- **Dashboard** — Stats, charts, recent orders.
- **Products** — CRUD, variants, attributes, images, flash sale linkage.
- **Categories** — Hierarchical `parent_id`, images on roots.
- **Brands** — CRUD, logos.
- **Attributes** — Attribute + values for variants.
- **Flash sales** — Time slots and line items.
- **Coupons / vouchers** — CRUD: percent or fixed amount, **minimum order**, optional **category scope** (descendants), validity window, max uses.
- **Orders** — List, filters, detail (**subtotal, discount, shipping**, distance), status updates.
- **Inventory logs** — Stock movements.
- **Users** — CRUD / search.
- **Admin profile** — Name, email, password, avatar.

### Business rules (summary)

- **Coupons:** Validated on cart and again at **place order**; discount stored on `orders` (`coupon_id`, `discount_amount`); usage counter incremented when applicable.
- **Shipping:** `ShippingFeeService` + `config/shipping.php` — Haversine from warehouse to checkout coordinates; fee tiers by km; defaults when coords missing.
- **Stock alerts:** On variant (or simple product) stock going **0 → &gt;0**, subscribers get **email** (`ProductBackInStockMail`) and can see history under **notifications/stock**.
- **Products with variants:** Aggregate price/stock from variants; inventory logs on checkout/cancel flows.

## Project structure (high level)

| Area | Paths |
|------|--------|
| HTTP | `WelcomeController`, `ProductController`, `CategoryController`, `BrandController`, `AttributeController`, `AuthController`, `UserController`, `AddressController`, `CartController`, `CheckoutController`, `OrderController`, `PayPalController`, `FlashSaleController`, `WishlistController`, `CompareController`, `StockNotificationController`, `StockAlertInboxController`, `AdminController`, `Admin\AdminCouponController`, `AdminOrderController`, `AdminInventoryLogController`, `AdminProfileController`, … |
| Models | `Category`, `Product`, `ProductVariant`, `ProductImage`, `ProductReview`, `Brand`, `Attribute`, `AttributeValue`, `User`, `Address`, `Cart`, `CartItem`, `Coupon`, `WishlistItem`, `CompareItem`, `StockNotificationSubscription`, `Order`, `OrderItem`, `Payment`, `FlashSale`, `FlashSaleItem`, `InventoryLog`, … |
| Services | `ShippingFeeService`, `CartPricingService`, `CouponService`, `StockNotificationService`, **`CatalogCache`**, **`OrderPlacementService`** (đặt hàng từ giỏ — web + API) |
| Mail | `app/Mail/ProductBackInStockMail.php`, `resources/views/emails/` |
| Config | `config/shipping.php` |
| Views | `resources/views/layouts/` (admin, user), `welcome.blade.php`, `products/`, `user/` (cart, checkout, orders, addresses, **wishlist**, **compare**, **stock-alerts**), `admin/` (including **coupons**), `partials/`, `emails/` |
| Routes | `routes/web.php` — auth, cart, coupons, wishlist, compare, stock notifications, checkout, PayPal, flash-sale JSON (`/api/flash-sale`), image routes, admin group; **`routes/api.php`** — REST v1 (see below) |

## REST API (v1) + Sanctum

Base path: **`/api/v1`**. Uses **Laravel Sanctum** (`User` has `HasApiTokens`). Run migrations (`personal_access_tokens`, v.v.):

```bash
php artisan migrate
```

### Public (có **ETag** + `Cache-Control: public, max-age=60` — trả **304** nếu `If-None-Match` khớp)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/categories` | Cây danh mục (đồng bộ cache với storefront). |
| GET | `/api/v1/products` | Phân trang: `per_page`, `sort`, `category` (slug), `q`, `price_min`, `price_max`. Có `has_variants`. |
| GET | `/api/v1/products/{slug}` | Chi tiết + variants. |

### Auth & tài khoản

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/v1/auth/login` | No | `{ email, password }` → `{ token, user }` (throttle 10/phút). **Admin** bị từ chối. |
| POST | `/api/v1/auth/logout` | Bearer | Thu hồi token hiện tại. |
| GET | `/api/v1/user` | Bearer | Profile tối giản. |

### Giỏ & thanh toán (Bearer)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/cart` | Dòng giỏ, `subtotal`, `discount`, `total`, coupon. |
| POST | `/api/v1/cart/items` | `{ product_id, product_variant_id?, quantity? }` |
| PATCH | `/api/v1/cart/items/{id}` | `{ quantity }` |
| DELETE | `/api/v1/cart/items/{id}` | Xóa dòng. |
| POST | `/api/v1/cart/coupon` | `{ code }` |
| DELETE | `/api/v1/cart/coupon` | Bỏ mã. |
| GET | `/api/v1/addresses` | Địa chỉ đã lưu (checkout). |
| GET | `/api/v1/checkout/shipping-fee` | `?lat=&lng=` — cùng logic web. |
| POST | `/api/v1/checkout` | Đặt hàng: `payment_method` `cod` \| `paypal`, hoặc `address_id`, hoặc `full_name`, `phone`, `shipping_address`, `lat`, `lng`, `notes`. Trả `{ order, next: { action, url } }` — **COD** / **PayPal** cần mở `url` trên trình duyệt đã **đăng nhập web** nếu muốn xem trang thành công PayPal. |

Logic đặt hàng dùng chung **`OrderPlacementService`** với `CheckoutController` (web).

## React SPA demo

- **URL:** [`/spa`](http://localhost:8000/spa) — đăng nhập bằng API token, xem grid sản phẩm, thêm giỏ (sản phẩm **không** biến thể).
- **Dev:** hai terminal — `php artisan serve` và `npm run dev`. Vite **proxy** `/api` → Laravel (`vite.config.js`), nên gọi `/api/v1/...` từ React không lỗi CORS.
- **Build:** `npm run build` — asset `resources/js/spa/main.jsx`.

## Redis & cache ứng dụng

- **`CatalogCache`** dùng `Cache` facade → đặt **`CACHE_STORE=redis`** (và cấu hình `REDIS_*`) để dùng Redis trong production.
- Có thể thêm **`SESSION_DRIVER=redis`** khi đã chạy Redis ổn định.
- API public: middleware **`AddWeakEtagPublicApi`** (ETag yếu + max-age ngắn).

## Caching & queues (incremental)

- **HTTP cache:** `CatalogCache` stores the **root category tree** (~10 min TTL) and **flash-sale “welcome” context** (~45s TTL). Keys are invalidated when `Category`, `FlashSale`, or `FlashSaleItem` changes (`AppServiceProvider`).
- **Mail:** `ProductBackInStockMail` implements **`ShouldQueue`** — use `QUEUE_CONNECTION=database` (or Redis) and run `php artisan queue:work` so back-in-stock email is sent asynchronously.

## Image URLs

Assets under `storage/app/public/` are exposed via named routes, e.g.:

- Products — `/images/products/{filename}`
- Brands — `/images/brands/{filename}`
- Categories — `/images/categories/{filename}`
- Avatars — `/images/avatars/{filename}`

Requires `php artisan storage:link`.

## Testing

```bash
php artisan test
```

Included: **API v1** (catalog, auth, cart, checkout, addresses), **SPA React** (`/spa`), **ETag** on public catalog endpoints, **`OrderPlacementService`**, tests trong `tests/Feature/Api/V1/*` + SQLite-friendly migrations. Chạy `php artisan migrate` để áp migration **`ensure_orders_coupon_columns`** nếu DB cũ thiếu `coupon_id` trên `orders`.

**Good next steps:** PayPal/IPN API tests, wishlist/compare JSON, cursor pagination, Inertia nếu muốn SSR + Vue/React trong Laravel.

## Roadmap / future improvements

| Area | Today | Possible direction |
|------|--------|-------------------|
| **API / SPA** | REST v1 đủ catalog + giỏ + checkout; SPA mẫu React. | App mobile / storefront React lớn, OAuth device flow. |
| **Blade vs React** | Blade là shop chính; `/spa` là demo. | Mở rộng SPA hoặc Inertia. |
| **Caching & queues** | Catalog cache, ETag public API, mail queue. | Redis production, monitor `failed_jobs`, chunk jobs. |
| **Tests** | Auth, cart, checkout COD, ETag, factories. | Webhooks PayPal, coupon edge cases. |

This project remains a **learning / demo** monolith; hardening infra for scale is optional follow-on work.

## Code style

[Laravel Pint](https://laravel.com/docs/pint):

```bash
./vendor/bin/pint
```

## License

Open-sourced under the [MIT License](LICENSE).
