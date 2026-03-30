# NovaShop

A **Laravel 12** e-commerce demo: hierarchical categories, products with variants & attributes, brands, flash sales, **review moderation** (approved reviews on the storefront), address book (Leaflet / OpenStreetMap), **distance-based shipping** + **delivery date range** hints (`config/delivery.php`), **cart coupons** (including **VIP**, **first-time buyer**, and **birthday window** rules), **wishlist** & **compare** (up to 4 items; optional share links), **“frequently bought together”** from order history, **back-in-stock email** + in-app **stock alert inbox**, **Elasticsearch-backed search** (optional), **DB search synonyms** (admin), **AI product assistant** (`/ai-chat`, OpenAI), cart & checkout, orders (**COD / PayPal**), **customer cancel / return requests**, **email OTP** verification after registration, and an **admin** dashboard (KPIs, revenue chart, top SKUs, cancel rate).

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
   | Delivery estimate (UI / emails) | `DELIVERY_PROCESSING_MIN`, `DELIVERY_PROCESSING_MAX`, `DELIVERY_KM_PER_DAY`, `DELIVERY_MAX_TRANSIT_DAYS`, `DELIVERY_BUFFER_DAYS`, `DELIVERY_PREVIEW_ASSUMED_KM` (see `config/delivery.php`) |
   | AI chat (`/ai-chat`) | `OPENAI_API_KEY`, `OPENAI_MODEL` (optional tool / history tunables in `.env.example`) |
   | Elasticsearch (optional search) | `ELASTICSEARCH_ENABLED`, `ELASTICSEARCH_HOST`, `ELASTICSEARCH_PRODUCTS_INDEX`, … |
   | PayPal stock hold | `STOCK_RESERVATION_TTL_MINUTES` |
   | **Email (OTP, back-in-stock, …)** | `MAIL_*` — use an **app password** for Gmail; **`QUEUE_CONNECTION=database`** + `php artisan queue:work` (or `composer run dev`) if you rely on **queued** mail such as back-in-stock |

4. **Migrations**
   ```bash
   php artisan migrate
   ```

5. **Storage link** (product / brand / category / avatar / review images)
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

- **Home** — Products, sort/pagination, filters, search (DB + optional Elasticsearch; **synonyms** from admin); **flash sale** by time slot; category-based suggestions from recent views where implemented.
- **Categories** — Tree (`/all-categories`); category pages with slug URLs, brand filter, search.
- **Product detail** — Gallery, variants, flash pricing, stock, **shipping / delivery estimate** snippet, **reviews** (only **approved** reviews; pagination, star filter, AJAX partial); submit review only if the user has a **completed, delivered** purchase of that product; **add to cart**; **wishlist** / **compare** / **notify when back in stock**; **“Frequently bought together”** from `order_items` co-occurrence.
- **Auth** — Register, login, logout; **Google OAuth** (Socialite); **email verification via OTP** (`email.verified.otp` middleware on shopping routes).
- **Profile** — Name, email, **date of birth** (optional; used for birthday coupons), avatar, password.
- **AI assistant** — `/ai-chat` (logged-in history; throttled public `send`); requires `OPENAI_API_KEY`.
- **Address book** — CRUD; map picker (Leaflet + Nominatim); default address; lat/lng for shipping.
- **Wishlist** — Per-user list (`/wishlist`).
- **Compare** — Up to **4** products, attribute comparison table (`/compare`).
- **Cart** — Add/update/remove; variant-aware; **apply / remove coupon**; totals respect active flash sale prices where applicable.
- **Checkout** — Saved or new address + map; **shipping fee by distance**; shows subtotal, discount, shipping, total; **COD** or **PayPal**.
- **Orders** — History, detail; **cancel** or **request return / refund** where allowed (restock / status rules); PayPal retry for unpaid/failed; **order status change** emails use synchronous `Mail::send` (no queue worker required).
- **Stock alerts inbox** — `/notifications/stock`: rows after a back-in-stock email was sent (demo rows possible via seeder).

**Header (logged in):** quick links (heart / compare / bell / cart) with badges; account dropdown (profile, orders, addresses, logout). **Liên hệ** links to the on-page footer (`/#site-footer`).

### Admin (`auth` + `admin` middleware)

- **Dashboard** — Product/order/user/category counts, **30-day revenue** line chart, **top SKUs** (completed orders), **cancel rates** (30-day and all-time), recent orders.
- **Products** — CRUD, variants, attributes (**Livewire** component for attribute values on the edit form), images, flash sale linkage.
- **Categories** — Hierarchical `parent_id`, images on roots.
- **Brands** — CRUD, logos.
- **Attributes** — Attribute + values for variants.
- **Flash sales** — Time slots and line items.
- **Coupons / vouchers** — CRUD: percent or fixed amount, **minimum order**, optional **category scope** (descendants), validity window, max uses; **user segment** (all vs **VIP** via `users.is_vip`), **first order only** (no prior orders), **optional minimum completed orders**, **birthday window** (± days; requires `users.birthday`).
- **Orders** — List, filters, detail (**subtotal, discount, shipping**, distance), status updates.
- **Inventory logs** — Stock movements.
- **Users** — CRUD / search; **admin flag**, **VIP flag**, **birthday** (for birthday coupons).
- **Search synonyms** — Map keywords to extra query terms (`ProductSearchService` + admin CRUD).
- **Product reviews** — Queue of pending reviews: approve / reject (with optional email on reject).
- **Admin profile** — Name, email, password, avatar.

### Business rules (summary)

- **Coupons:** Validated on cart and again at **place order** (`CouponService`: segment VIP, first order, birthday window, min completed orders, category subtotal, min order amount); discount stored on `orders` (`coupon_id`, `discount_amount`); usage counter incremented when applicable.
- **Shipping:** `ShippingFeeService` + `config/shipping.php` — Haversine from warehouse to checkout coordinates; fee tiers by km; defaults when coords missing. **Delivery preview** on product / order views uses `config/delivery.php` and km saved on the order when available.
- **Stock alerts:** On variant (or simple product) stock going **0 → &gt;0**, subscribers get **queued** email (`ProductBackInStockMail` implements `ShouldQueue`) and can see history under **notifications/stock** — run a **queue worker** in production (or `composer run dev` locally).
- **Order emails:** **`OrderStatusChangedMail`** is **not** queued; sent with `Mail::send` in the order observer so status updates still notify without `queue:work`.
- **Products with variants:** Aggregate price/stock from variants; inventory logs on checkout/cancel flows.

## Project structure (high level)

| Area | Paths |
|------|--------|
| HTTP | `WelcomeController`, `ProductController`, `ProductReviewController`, `CategoryController`, `BrandController`, `AttributeController`, `AuthController`, `AiChatController`, `UserController`, `AddressController`, `CartController`, `CheckoutController`, `OrderController`, `PayPalController`, `FlashSaleController`, `WishlistController`, `CompareController`, `ListSharePublicController`, `StockNotificationController`, `StockAlertInboxController`, `AdminController`, `Admin\AdminCouponController`, `Admin\AdminSearchSynonymController`, `Admin\AdminProductReviewController`, `AdminOrderController`, `AdminInventoryLogController`, `AdminProfileController`, … |
| Models | `Category`, `Product`, `ProductVariant`, `ProductImage`, `ProductReview`, `SearchSynonym`, `Brand`, `Attribute`, `AttributeValue`, `User`, `Address`, `Cart`, `CartItem`, `Coupon`, `WishlistItem`, `CompareItem`, `StockNotificationSubscription`, `Order`, `OrderItem`, `Payment`, `FlashSale`, `FlashSaleItem`, `InventoryLog`, `AiChatMessage`, … |
| Services | `ShippingFeeService`, `CartPricingService`, `CouponService`, `StockNotificationService`, **`ProductSearchService`**, **`CatalogCache`**, **`OrderPlacementService`** (web + API checkout) |
| Mail | `ProductBackInStockMail`, `OrderStatusChangedMail`, `EmailVerificationOtpMail`, `ProductReviewRejectedMail`; views under `resources/views/emails/` |
| Config | `config/shipping.php`, `config/delivery.php`, `config/services.php` (Elasticsearch, OpenAI, …) |
| Views | `resources/views/layouts/` (admin, user), `welcome.blade.php`, `products/`, `profile/`, `ai-chat/`, `user/` (cart, checkout, orders, addresses, **wishlist**, **compare**, **stock-alerts**), `admin/` (coupons, **search_synonyms**, …), `livewire/`, `partials/`, `emails/` |
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
| GET | `/api/v1/search/suggestions` | Gợi ý tìm kiếm (throttle). |

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

- Mặc định `.env.example` dùng **`CACHE_STORE=database`** / **`SESSION_DRIVER=database`** — không cần Redis để chạy local.
- **`CatalogCache`** dùng `Cache` facade → đặt **`CACHE_STORE=redis`** (và `REDIS_*`) nếu muốn Redis trong production.
- Có thể thêm **`SESSION_DRIVER=redis`** khi đã chạy Redis ổn định.
- API public: middleware **`AddWeakEtagPublicApi`** (ETag yếu + max-age ngắn).

## Caching & queues (incremental)

- **HTTP cache:** `CatalogCache` stores the **root category tree** (~10 min TTL) and **flash-sale “welcome” context** (~45s TTL). Keys are invalidated when `Category`, `FlashSale`, or `FlashSaleItem` changes (`AppServiceProvider`).
- **Mail:** `ProductBackInStockMail` implements **`ShouldQueue`** — with `QUEUE_CONNECTION=database` (or Redis), run **`php artisan queue:work`** (included in **`composer run dev`**) so back-in-stock (and any other queued jobs) actually send. **Order status** mail is **synchronous** (see business rules).

## Image URLs

Assets under `storage/app/public/` are exposed via named routes, e.g.:

- Products — `/images/products/{filename}`
- Brands — `/images/brands/{filename}`
- Categories — `/images/categories/{filename}`
- Avatars — `/images/avatars/{filename}`
- Review photos — `/images/reviews/{filename}`

Requires `php artisan storage:link`.

## Testing

```bash
php artisan test
```

Included: **API v1** (catalog, auth, cart, checkout, addresses), **SPA React** (`/spa`), **ETag** on public catalog endpoints, **`OrderPlacementService`**, tests trong `tests/Feature/Api/V1/*` + SQLite-friendly migrations. Chạy `php artisan migrate` để áp migration **`ensure_orders_coupon_columns`** nếu DB cũ thiếu `coupon_id` trên `orders`.

**Good next steps:** PayPal/IPN API tests, wishlist/compare JSON endpoints, more coupon / segment integration tests, Inertia nếu muốn SSR + Vue/React trong Laravel.

## Roadmap / future improvements

| Area | Today | Possible direction |
|------|--------|-------------------|
| **API / SPA** | REST v1 đủ catalog + giỏ + checkout; SPA mẫu React. | App mobile / storefront React lớn, OAuth device flow. |
| **Blade vs React** | Blade là shop chính; `/spa` là demo. | Mở rộng SPA hoặc Inertia. |
| **Caching & queues** | Catalog cache, ETag public API, mail queue. | Redis production, monitor `failed_jobs`, chunk jobs. |
| **Tests** | Auth, cart, checkout COD, ETag, factories. | Webhooks PayPal, Elasticsearch/synonym coverage, coupon segments. |

This project remains a **learning / demo** monolith; hardening infra for scale is optional follow-on work.

## Code style

[Laravel Pint](https://laravel.com/docs/pint):

```bash
./vendor/bin/pint
```

## License

Open-sourced under the [MIT License](LICENSE).
