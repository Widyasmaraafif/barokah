# Barokah Multi-Seller Marketplace — Software Development Specification

**Change ID:** `create-barokah-marketplace-spec`
**Stack:** Laravel (PHP 8.4) + Vue.js (Inertia v3) + Relational DB (MySQL/PostgreSQL) | Currency MYR/RM | PayNet (FPX, DuitNow) | Shipping: Fixed Rate + External API | i18n: English default + Malay via GTranslate
**Source of Truth:** Marketplace concept document provided by client (authoritative). Any conflicting requirement defaults to that document.

---

## 1. Project Overview

### Why
Malaysian multi-seller marketplace for physical products (initial: Keripik, Hijab, Kerudung) needs a scalable platform where multiple sellers list products, buyers (including guests) purchase via a direct Buy flow, and payments (FPX/DuitNow via PayNet) and shipping (Fixed Rate + optional external API) are handled centrally with extensible admin-managed settings.

### What Changes (Greenfield)
- New Laravel + Vue.js (Inertia) monolith marketplace application.
- Multi-role system: Admin, Seller (opt-in from user profile), Buyer/Customer (guest or authenticated).
- Product/Category catalog with seller ownership.
- Direct Buy flow: Product Detail → Buy → Buyer Information → Shipping → Payment → Order Confirmation (no mandatory cart).
- Order system supporting multi-seller order with per-seller order items.
- PayNet payment abstraction (FPX/DuitNow) with status/callback handling.
- Shipping abstraction (Fixed Rate + External Shipping API).
- Admin area (dashboard, users, sellers, products, categories, orders, payments, shipping, settings).
- Centralized dynamic Settings system (branding, currency, marketplace, checkout, shipping, payment, localization, contact, SEO, email) with public/private visibility and caching.
- Shopee-inspired marketplace UI per design reference `C:\Users\ASUS\Downloads\design.md` (desktop homepage: utility bar, search header, hero grid, quick services, flash/best-seller/live UI, categories, recommendations; listing + product detail; mobile bottom nav) with original Barokah branding. Velocity reference retained only for checkout/admin interaction patterns where design.md is silent.

### Impact
- Affected capabilities: All — greenfield project.
- Affected code: New modules only; existing Fortify auth scaffold extended (see §12).

---

## 2. Goals and Scope

### Goals
- Launch a production-ready multi-seller marketplace for physical goods in Malaysia.
- Enable seller self-registration via "active as seller" activation.
- Enable guest checkout while preserving purchase history for registered users.
- Abstract payment and shipping so PayNet credentials/endpoints and shipping provider can change without refactoring order architecture.
- Keep all configurable business/branding/currency/payment/shipping values admin-editable (no hard-coding).

### In Scope
- User/Seller/Product/Category/Order/OrderItem/Payment/Shipping/Customer Information/Admin management as enumerated in source requirements.
- Fixed Rate shipping + ShippingService abstraction for external API.
- PayNet FPX/DuitNow integration skeleton (placeholders until credentials/docs provided).
- MYR/RM currency with centralized formatter.
- GTranslate-based Malay support (English default).
- Admin Settings UI with 11 categories.

### Out of Scope (unless TBC resolved)
- Cart backend (direct Buy flow is mandatory; design reference shows cart icon/buttons as UI placeholder only — TBC, see §24 item 3).
- Digital products, subscriptions, auctions.
- Native mobile apps.
- Promo/flash-sale engine, best-seller ranking, live-streaming backend, ratings/reviews backend (design shows these as UI only; backends TBC — see §24 items 13, 21–25).
- Quick-service destinations (finance/pay-later/games UI tiles are placeholders; no backend assumed — TBC).

### Success Metrics (TBC thresholds)
- Order creation success rate, payment callback reconciled, seller activation flow completed, settings change propagation without redeploy.

---

## 3. Source Requirements (Traceability)

Source document mandates:
- Roles: Admin (full access), Seller (register→activate as seller→sell), Buyer (guest allowed, optional register for history).
- Products: Keripik, Hijab, Kerudung initial; physical only.
- Buy flow sequence explicitly fixed.
- Buyer form fields: Name, Address, State, Post Code, Phone Number (exactly these; additions TBC).
- Payment: PayNet gateway, FPX + DuitNow methods only.
- Shipping: Fixed Rate (configurable) + optional external Shipping API via abstraction.
- Admin sections: Dashboard, User, Seller, Product, Category, Order, Payment, Shipping Settings, Marketplace Settings.
- Frontend: Shopee-like desktop homepage; other pages/mobile follow Velocity reference (no proprietary copy).
- Language: English default, Malay via GTranslate.
- Settings: 20-section centralized Settings requirements (see §7/§17).

Any requirement not in source document is marked **TBC** in §24 and not assumed.

---

## 4. User Roles

### 4.1 Admin
- Full access to website content and marketplace data.
- Manages users, sellers, products, categories, orders, payments, shipping settings, marketplace settings.
- Accesses Admin Settings UI (11 categories) and Dashboard.
- Authentication required; `admin` role gate.

### 4.2 Seller
- Registers/logs in as normal user, then activates "active as seller" from profile (opt-in flag / Seller profile creation).
- After activation, can create/manage own products (ownership-enforced).
- Views own orders (derived from order items belonging to seller) and own products.
- Cannot manage other sellers' products/orders.
- Same auth as buyer; additional `seller` role/capability.

### 4.3 Buyer / Customer
- May purchase without login (guest checkout).
- May register/login to retain purchase history and prefill buyer information.
- Purchases physical products online via Buy flow.
- Guest orders linked by email/phone; authenticated orders linked to `user_id` (nullable).

### Role Matrix
| Capability | Guest | Auth User | Seller | Admin |
|---|---|---|---|---|
| Browse products/categories | Y | Y | Y | Y |
| Direct Buy / Checkout | Y | Y | Y | - |
| View own order history | - | Y | Y | Y (all) |
| Activate as seller | - | Y | - | - |
| Manage own products | - | - | Y | Y |
| Manage all products/users/sellers/orders | - | - | - | Y |
| Manage Settings | - | - | - | Y |

---

## 5. Functional Requirements

### Requirement: Authentication & Roles
The system SHALL support registration, login, email verification, password reset, and role assignment (admin/seller/buyer). Seller activation SHALL be an explicit user action from profile.
#### Scenario: Seller activation
- **WHEN** authenticated user toggles "Active as Seller" and submits required seller profile
- **THEN** system creates Seller record linked to user, assigns seller capability, and allows product creation

### Requirement: Product & Category Management
The system SHALL allow sellers (and admins) to manage products with fields: seller ownership, category, name, slug, description, price, stock, images, status, timestamps. No additional fields without TBC.
#### Scenario: Create product
- **WHEN** seller submits valid product payload with category and price/stock
- **THEN** product is created with unique slug, status `draft` or `active` per validation, and seller_id = current user

### Requirement: Direct Buy Flow
The system SHALL provide a sequential flow: Product Detail → Buy → Buyer Information → Shipping → Payment → Order Confirmation. Buy button SHALL initiate checkout for that product without requiring cart.
#### Scenario: Guest direct buy
- **WHEN** guest clicks Buy on product detail, completes Buyer Information (5 required fields), selects shipping, selects FPX/DuitNow
- **THEN** order is created in `pending_payment`, payment intent created via PayNet abstraction, user redirected to PayNet, and confirmation shown after callback

### Requirement: Multi-Seller Order
The system SHALL support a single customer order containing products from different sellers; each order item SHALL retain seller_id and product snapshot.
#### Scenario: Multi-seller order
- **WHEN** buyer purchases products from 2 sellers in one checkout (TBC if multi-product checkout allowed beyond single Buy — see §24) or via multiple order items
- **THEN** order aggregates totals while order items carry seller_id for seller-scoped views

### Requirement: Payment (PayNet FPX/DuitNow)
The system SHALL abstract PayNet via PaymentService, support FPX and DuitNow methods, handle pending/success/failed states, callbacks/webhooks, and idempotent reconciliation.
#### Scenario: Successful payment
- **WHEN** PayNet callback reports success with valid signature
- **THEN** payment status → `paid`, order status → `paid`/`processing`, buyer sees confirmation

### Requirement: Shipping Calculation
The system SHALL support Fixed Rate shipping (admin-configurable) and an optional External Shipping API via ShippingService abstraction without changing order architecture.
#### Scenario: Fixed rate
- **WHEN** checkout requests shipping quote for address/postcode
- **THEN** ShippingService returns fixed fee from settings

### Requirement: Admin Management
The system SHALL provide admin CRUD for users, sellers, products, categories, orders, payments, shipping/marketplace settings, and dashboard metrics.

### Requirement: Dynamic Settings System (Centralized)
The system SHALL provide a database-backed, cached, validated Settings system with public/private visibility, default values, and Admin UI (11 categories). All configurable values (branding, currency, etc.) SHALL be read via SettingsService/CurrencyFormatter, never hard-coded in Vue or backend.
#### Scenario: Update currency
- **WHEN** admin changes currency.symbol from RM to MYR
- **THEN** all price displays via formatPrice() reflect new symbol after cache invalidation, without code deploy

Detailed settings categories are specified in §7.12 / §17.11 and §24.

### Requirement: Localization
The system SHALL default to English, support Malay via GTranslate, and structure frontend text for clean translation (keys, not hard-coded strings).

---

## 6. User Flows

### 6.1 Guest Direct Buy (Primary)
1. Buyer visits Product Detail (Shopee-like, see §18)
2. Clicks **Buy** → Buyer Information form (Name, Address, State, Post Code, Phone Number) + optional email for receipt (TBC if required)
3. Shipping step → ShippingService quote (Fixed Rate or API) → select method
4. Payment step → select FPX or DuitNow → create order (pending_payment) + payment intent
5. Redirect to PayNet → PayNet callback/webhook → update payment/order status
6. Order Confirmation page (order number, totals in MYR, shipping, next steps)

### 6.2 Registered Buyer Buy
As above, but Buyer Information prefilled from profile; order linked to user_id; history retained.

### 6.3 Seller Activation & Product Creation
Register/Login → Profile → Activate "Active as Seller" → Seller profile created → Create Product (category, name, description, price, stock, images, status) → Product live.

### 6.4 Admin Order/Payment Management
Admin → Orders → filter by status/seller/payment → view order + items + payment + shipping → update order status (with authorization).

---

## 7. System Architecture

### 7.1 High-Level
- Monolith Laravel + Inertia Vue SPA; relational DB; server-side payment/shipping services; cached SettingsService; public settings endpoint for frontend.
- No microservices in Phase 1; services abstract external integrations.

### 7.2 Directory Structure (Proposed)
```
app/
  Models/ (User, Seller, Category, Product, ProductImage, Order, OrderItem, Payment, ShippingRate, Setting)
  Services/ (SettingsService, CurrencyFormatter/PriceFormatter, PaymentService, PayNetGateway, ShippingService, FixedRateShippingProvider, ExternalShippingProvider)
  Http/
    Controllers/ (Web/Inertia + Api)
    Requests/ (FormRequests)
    Resources/ (Api Resources)
  Policies/ (ProductPolicy, OrderPolicy, SellerPolicy, SettingPolicy)
  Enums/ (OrderStatus, PaymentStatus, PaymentMethod, ShippingMethod, SettingType)
  Jobs/ (ProcessPaymentCallback, ExpirePendingOrder)
  Notifications/ (OrderConfirmed, PaymentFailed)
config/
  marketplace.php (defaults, non-secret)
  paynet.php (env-backed, server-only)
  shipping.php
resources/js/
  pages/ (Home, Product/Show, Checkout/*, Seller/*, Admin/*, Settings/*)
  components/ (marketplace, checkout, product, admin)
  stores/ (settings, checkout, seller)
  services/ (priceFormatter, settingsService, api)
  composables/
routes/
  web.php (Inertia)
  api.php (REST)
  admin.php (admin group)
database/migrations/
```

### 7.3 Backend Modules
- Auth & RBAC, Seller, Catalog (Category/Product), Order, Payment, Shipping, Settings, Admin, Localization.

### 7.4 Frontend Modules
- Marketplace (listing/home/product) per design.md component tree: `UtilityBar, MainHeader (BrandLogo, SearchBox, SuggestedKeywords, CartButton[TBC]), HeroPromo (HeroCarousel, SidePromos), QuickServices[TBC], FlashSaleSection (DealCard[] [TBC backend]), BestSellerSection (BestSellerCard[] [TBC ranking]), LiveSection (LiveCard[] [placeholder]), CategorySection (CategoryTile[]), RecommendationSection (ProductCard[]), Footer`.
- Checkout (4-step), Seller Dashboard, Admin (11 settings sections + CRUD), Shared (currency/branding via settings store + design tokens as CSS variables / Tailwind theme).

### 7.5 API Structure
- Versioned `/api/v1` (or `/api` with version header — TBC). Auth: Sanctum (SPA). Public + authenticated + admin-scoped routes. See §11.

### 7.6 Services / Separation of Concerns
- Controllers thin; business logic in Services; validation in FormRequests; authorization in Policies; transformation in Resources.
- PaymentService and ShippingService are strategy-pattern abstractions.

### 7.7 Error Handling
- Centralized exception handling; validation errors 422 with field bag; payment/shipping provider errors mapped to domain exceptions; webhook failures retried via queue; order expiration via scheduled job.

---

## 8. Backend Architecture

### 8.1 Controllers
- `ProductController`, `CategoryController`, `Seller\ProductController`, `CheckoutController`, `OrderController`, `PaymentController` (create/callback), `ShippingController` (quote), `Admin\*Controller` (User, Seller, Product, Category, Order, Payment, Settings), `SettingsController` (public + admin).

### 8.2 Requests / Validation
- FormRequests: `StoreProductRequest`, `UpdateProductRequest`, `BuyerInformationRequest` (name, address, state, postcode, phone), `CheckoutRequest`, `UpdateSettingsRequest` (per group, typed).
- Rules: required, string/max, numeric/min, exists, file/mimes/size for images, postcode/phone Malaysia format (TBC regex).

### 8.3 Resources / Transformers
- `ProductResource`, `CategoryResource`, `OrderResource` (with items, payment, shipping), `SellerResource`, `SettingResource` (public only filters private).

### 8.4 Models & Relationships
See §10. Eloquent with guarded/fillable, casts, enums, scopes.

### 8.5 Policies / Authorization
- `ProductPolicy`: seller owns product; admin overrides.
- `OrderPolicy`: buyer owns order (user_id or guest token) vs seller sees items of own seller_id vs admin all.
- `SettingPolicy`: admin only.

### 8.6 Error Handling
- Domain exceptions: `PaymentFailedException`, `ShippingQuoteException`, `InsufficientStockException`.
- Global handler renders Inertia error pages or JSON per request type.

---

## 9. Frontend Architecture

### 9.1 Stack
- Vue 3 + Inertia v3, Vite, TypeScript, Tailwind, Wayfinder for typed routes, Pinia or composable stores for settings/checkout.

### 9.2 Pages (resources/js/pages)
- `Home.vue` (per design.md §34 tree), `Product/Show.vue`, `Product/Index.vue` (listing + filter sidebar + sort toolbar per design §18), `Checkout/BuyerInformation.vue`, `Checkout/Shipping.vue`, `Checkout/Payment.vue`, `Checkout/Confirmation.vue`, `Seller/Dashboard.vue`, `Seller/Products/*`, `Admin/*`, `Admin/Settings/*`.

### 9.3 Components
- `marketplace/UtilityBar.vue`, `marketplace/MainHeader.vue` (`BrandLogo`, `SearchBox`, `SuggestedKeywords`, `CartButton` [TBC placeholder]), `marketplace/HeroPromo.vue` (`HeroCarousel`, `SidePromos`), `marketplace/QuickServices.vue` [TBC content], `marketplace/FlashSaleSection.vue` + `DealCard.vue` [UI only], `marketplace/BestSellerSection.vue` + `BestSellerCard.vue` [UI only], `marketplace/LiveSection.vue` + `LiveCard.vue` [placeholder], `marketplace/CategorySection.vue` + `CategoryTile.vue`, `marketplace/RecommendationSection.vue`, `product/ProductCard.vue`, `product/Price.vue` (uses formatPrice), `Branding` (logo/site name from settings), `CheckoutSteps`, `BuyerForm`, `ShippingQuote`, `PaymentMethodSelector`, `layout/MobileBottomNav.vue` [Cart/Live/Feed tabs TBC].

### 9.4 State
- `useSettingsStore` (fetches `/api/settings/public`, cached, reactive), `useCheckoutStore` (multi-step state, persisted), `useCurrency` (formatPrice).

### 9.5 API Layer
- Inertia visits for web; `useHttp` / fetch for API; Wayfinder typed actions for controller calls.

### 9.6 i18n
- Frontend text via translation keys (e.g., `t('checkout.buyer_name')`); GTranslate injected for Malay (see §19). No hard-coded display strings.

---

## 10. Database Design

### 10.1 ERD (Textual)
```
User 1—0..1 Seller
Seller 1—* Product
Category 1—* Product
Category self-referencing (parent_id) TBC
Product 1—* ProductImage
Product 1—* OrderItem
Order 1—* OrderItem
Order 1—1 Payment
Order 1—0..1 Shipping (or fields on order)
User 1—* Order (nullable for guest)
Seller 1—* OrderItem (denormalized seller_id)
Setting (key-value with group, type, is_public)
```

### 10.2 Tables

**users** (extends existing)
- id, name, email (unique), phone (TBC nullable), email_verified_at, password, two_factor columns (existing), is_admin (bool/TBC or role), is_active_as_seller (bool, default false), remember_token, timestamps

**sellers**
- id, user_id (FK unique), store_name, slug (unique), description, status (pending/active/suspended enum), timestamps

**categories**
- id, name, slug (unique), parent_id (FK self nullable, TBC), description, is_active, sort_order, timestamps

**products**
- id, seller_id (FK), category_id (FK), name, slug (unique), description (text), price (decimal 10,2 — stored in MYR minor units or decimal; TBC), stock (integer), status (draft/active/inactive/archived enum), created_at, updated_at
- Indexes: seller_id, category_id, slug, status

**product_images**
- id, product_id (FK), path, sort_order, is_primary, timestamps

**orders**
- id, order_number (unique, human-readable), user_id (FK nullable, guest null), customer_name, customer_address, customer_state, customer_post_code, customer_phone, customer_email (nullable, TBC), currency_code (default MYR), subtotal, shipping_fee, total, status (enum), payment_status (enum or via payment), shipping_method (enum: fixed, external), shipping_provider (nullable), notes (nullable), expired_at (nullable), timestamps

**order_items**
- id, order_id (FK), product_id (FK nullable if product deleted), seller_id (FK), product_name_snapshot, product_slug_snapshot, price_snapshot, quantity, subtotal, timestamps
- Snapshot preserves name/price at purchase time.

**payments**
- id, order_id (FK unique), payment_gateway (default paynet), payment_method (fpx/duitnow enum), amount, currency, status (pending/paid/failed/expired/cancelled), transaction_id (provider ref, nullable), payload (json, server-only), callback_payload (json), paid_at, failed_at, timestamps

**shipping_rates / shipping_quotes** (TBC single table)
- Minimal: shipping_fee stored on orders; Fixed Rate config via settings. Optional `shippings` table for external API quote history: id, order_id, provider, method, fee, payload, timestamps

**settings**
- id, key (unique, e.g., branding.site_name), value (text/json), type (string/integer/boolean/json/color/image), group (branding/currency/marketplace/checkout/shipping/payment/localization/contact/seo/email), is_public (bool), created_at, updated_at

**Additional TBC tables:** `carts` if cart introduced, `reviews` if ratings enabled, `media` if polymorphic.

### 10.3 Relationships
- User hasOne Seller; Seller belongsTo User; Seller hasMany Products; Product belongsTo Seller/Category; Product hasMany Images/OrderItems; Order hasMany OrderItems, hasOne Payment; OrderItem belongsTo Order/Product/Seller.

---

## 11. API Specification

Base: `/api/v1` (prefix TBC). Auth: Sanctum SPA (cookie) or token for external; guest endpoints public. All responses JSON via Resources. Errors: 422 validation, 401 unauth, 403 forbidden, 404 not found, 409 conflict (stock), 500 provider error.

### 11.1 Authentication
| Method | URI | Auth | Role | Request | Response | Validation | Errors |
|---|---|---|---|---|---|---|---|
| POST | /api/v1/register | No | Guest | name, email, password, password_confirmation | UserResource + 201 | required, email unique, password confirmed | 422 |
| POST | /api/v1/login | No | Guest | email, password | 200 + user | required | 401 |
| POST | /api/v1/logout | Yes | Any | - | 204 | - | 401 |
| POST | /api/v1/seller/activate | Yes | User | store_name, description | SellerResource 201 | store_name required unique | 422, 403 if already seller |

### 11.2 Products (Public)
| Method | URI | Auth | Role | Request | Response | Validation | Errors |
|---|---|---|---|---|---|---|---|
| GET | /api/v1/products | No | - | ?category&search&sort&page | Paginated ProductResource | - | - |
| GET | /api/v1/products/{slug} | No | - | - | ProductResource (with images, seller, category) | - | 404 |
| GET | /api/v1/categories | No | - | - | CategoryResource[] | - | - |

### 11.3 Seller Products
| Method | URI | Auth | Role | Request | Response | Validation | Errors |
|---|---|---|---|---|---|---|---|
| GET | /api/v1/seller/products | Yes | Seller | ?status | Paginated ProductResource | - | 403 |
| POST | /api/v1/seller/products | Yes | Seller | category_id, name, description, price, stock, images[], status | ProductResource 201 | category exists, name required, price numeric min 0, stock integer min 0, images mimes/size | 422, 403 |
| PUT | /api/v1/seller/products/{id} | Yes | Seller | same | ProductResource | ownership | 403, 404 |
| DELETE | /api/v1/seller/products/{id} | Yes | Seller | - | 204 | ownership | 403 |

### 11.4 Orders
| Method | URI | Auth | Role | Request | Response | Validation | Errors |
|---|---|---|---|---|---|---|---|
| POST | /api/v1/orders | Optional | Guest/User | product_id, quantity, buyer {name,address,state,post_code,phone,email?}, shipping_method | OrderResource 201 (pending_payment) | buyer fields required (5), product exists, stock | 422, 409 |
| GET | /api/v1/orders | Yes | User | - | OrderResource[] (own) | - | 401 |
| GET | /api/v1/orders/{order_number} | Yes/Guest token | Buyer/Seller/Admin | - | OrderResource | ownership or seller item or admin | 403, 404 |
| GET | /api/v1/seller/orders | Yes | Seller | - | Orders containing seller items | - | 403 |
| GET | /api/v1/admin/orders | Yes | Admin | ?status&seller | Paginated OrderResource | - | 403 |

### 11.5 Payments
| Method | URI | Auth | Role | Request | Response | Validation | Errors |
|---|---|---|---|---|---|---|---|
| POST | /api/v1/orders/{order_number}/payments | Optional | Buyer | payment_method: fpx OR duitnow | {redirect_url or qr payload} + payment status pending | method in [fpx,duitnow], order pending_payment | 422, 404, 409 |
| POST | /api/v1/payments/callback | No (signed) | PayNet | provider payload + signature | 200 | signature valid, order exists | 400 invalid signature, 404 |
| POST | /api/v1/payments/webhook | No (signed) | PayNet | provider payload + signature | 200 | idempotent by transaction_id | 400 |
| GET | /api/v1/orders/{order_number}/payment | Yes/Guest | Buyer | - | PaymentResource | - | 403 |

### 11.6 Shipping
| Method | URI | Auth | Role | Request | Response | Validation | Errors |
|---|---|---|---|---|---|---|---|
| POST | /api/v1/shipping/quote | Optional | - | address, state, post_code, items[] or product_id | {method, fee, formatted} | required address fields | 422, 500 provider |
| GET | /api/v1/admin/shipping/settings | Yes | Admin | - | Settings group shipping | - | 403 |
| PUT | /api/v1/admin/shipping/settings | Yes | Admin | method, fixed_rate, etc. | Settings | validated per type | 422 |

### 11.7 Customer Profile
| Method | URI | Auth | Role | Request | Response | Validation | Errors |
|---|---|---|---|---|---|---|---|
| GET | /api/v1/me | Yes | User | - | UserResource | - | 401 |
| PUT | /api/v1/me | Yes | User | name, phone, address, state, post_code | UserResource | phone/postcode format | 422 |
| PUT | /api/v1/me/password | Yes | User | current_password, password | 204 | current correct | 422 |

### 11.8 Settings (Public vs Admin)
| Method | URI | Auth | Role | Request | Response | Validation | Errors |
|---|---|---|---|---|---|---|---|
| GET | /api/v1/settings/public | No | - | - | {branding, currency, localization...} public only | - | - |
| GET | /api/v1/admin/settings | Yes | Admin | ?group | SettingResource[] | - | 403 |
| PUT | /api/v1/admin/settings | Yes | Admin | {key: value} batch or single | SettingResource | per-key rules, sanitized | 422, 403 |

---

## 12. Authentication & Authorization

- **Auth:** Laravel Fortify (existing) + Sanctum for SPA API. Email verification, password reset, 2FA optional (existing). Guest checkout bypasses auth; order lookup via order_number + email/phone token TBC.
- **RBAC:** Roles via `is_admin` flag or `roles` table (TBC; recommend `spatie/laravel-permission` or simple gate). Seller capability derived from `sellers` existence + `is_active_as_seller`.
- **Policies:** ProductPolicy, OrderPolicy, CategoryPolicy, SettingPolicy. Seller ownership checked on every product/order mutation.
- **Middleware:** `auth`, `verified` (where needed), `can:admin`, `can:seller`, `throttle`.
- **Guest Order Access:** Order confirmation via signed URL or email+order_number check; do not expose other guests' orders.

---

## 13. Product & Seller System

- **Seller Activation:** User profile toggle → `POST /seller/activate` → creates `sellers` row (status active; TBC if approval workflow needed → pending). Store name/slug unique.
- **Product CRUD:** Seller-scoped; admin can manage all. Validation: name required, slug auto-generated unique, category exists, price decimal >=0, stock integer >=0, images validated (mimes jpg/png/webp, max TBC 2MB), status enum.
- **Category:** Admin CRUD; product belongs to one category; parent_id hierarchy TBC. Slug unique.
- **Stock:** Decrement on successful payment (or on order create with reservation TBC). Prevent oversell via DB transaction + pessimistic lock or optimistic check.
- **Images:** Stored via `storage/app/public/products`; path saved; is_primary flag.

---

## 14. Order System

### 14.1 Order Model
- `orders` aggregates customer snapshot, currency, subtotal/shipping/total, status, shipping_method; `order_items` per product with seller_id and snapshots.

### 14.2 Statuses
- **Order:** `pending_payment` → `paid` → `processing` → `shipped` → `completed` / `cancelled` / `expired`. TBC if admin needs `refunded`.
- **Payment:** `pending` → `paid` / `failed` / `expired` / `cancelled`. Reconciled via callback.
- **Sync:** Payment `paid` triggers Order `paid`; failure keeps `pending_payment` until retry or expiry.

### 14.3 Multi-Seller Handling
- One `orders` row per customer checkout; N `order_items` each with `seller_id`. Totals computed at order level; seller dashboard filters `order_items.seller_id = current_seller`. Future split: sub-orders per seller (TBC) — current design avoids extra table; sub-order can be derived view.

### 14.4 Lifecycle
1. Checkout validates stock + buyer fields + shipping quote.
2. DB transaction creates order + items + payment (pending) + reserves stock (TBC).
3. PaymentService creates PayNet intent → returns redirect/QR.
4. Callback verifies signature → idempotent update → dispatch notifications → clear expiration.

### 14.5 Expiration
- `orders.expired_at` = created + settings `checkout.order_expiration` (e.g., 30 min TBC). Scheduled job expires `pending_payment` past due, restores stock, marks payment expired.

---

## 15. Payment System

### 15.1 Architecture
```
PaymentService (interface)
  ├─ PayNetGateway implements PaymentGateway
  │    ├─ createIntent(order, method) → {redirect_url|qr, provider_ref}
  │    ├─ verifyCallback(payload, signature): bool
  │    └─ getStatus(provider_ref): PaymentStatus
  └─ TBC additional gateways
```
- `PaymentService` handles order/payment creation, status mapping, retry, idempotency. `PayNetGateway` encapsulates HTTP calls, signing, sandbox/production switch via settings/env.

### 15.2 PaymentService Abstraction
- Methods: `initiate(order, method)`, `handleCallback(payload)`, `handleWebhook(payload)`, `markFailed`, `markExpired`.
- Never expose credentials to frontend; all secrets server-only.

### 15.3 Order/Payment Relationship
- `orders 1—1 payments`; payment amount = order total; currency MYR; payment row created with order.

### 15.4 Callback/Webhook
- Endpoint `POST /api/v1/payments/callback` (and/or webhook) — public but signature-verified (HMAC/shared secret TBC). Idempotent by `transaction_id`. Queue job `ProcessPaymentCallback` for async handling. Return 200 quickly.

### 15.5 Error Handling
- Invalid signature → 400, log, alert.
- Provider unreachable → retry with backoff, keep pending, UX shows "pending — check again".
- Failed payment → payment `failed`, order remains `pending_payment` allowing retry or new payment intent (TBC limit).
- Pending → polling endpoint `GET /orders/{n}/payment` for frontend to reflect status.

### 15.6 Placeholders (TBC)
- Exact PayNet API base URL, auth method, FPX vs DuitNow payload differences, signature algorithm, redirect vs QR flows, sandbox credentials. Mark endpoint paths as `TBC_PAYNET_API_BASE`.

---

## 16. Shipping System

### 16.1 Supported Methods
1. **Fixed Rate:** Single configurable fee (e.g., RM 5.00) applied to all orders; free shipping threshold optional.
2. **External Shipping API:** Client-provided Malaysia shipping API for automatic calculation (provider TBC).

### 16.2 ShippingService Abstraction
```
ShippingService (interface)
  ├─ FixedRateShippingProvider
  └─ ExternalShippingProvider (TBD)
Methods: quote(address, items): {method, fee, meta}
```
- `ShippingService::quote()` selects provider per `settings.shipping.method`. Order creation stores `shipping_fee` snapshot; provider payload optionally in `shippings` table.

### 16.3 Configuration
- Settings: `shipping.method` (fixed|external), `shipping.fixed_rate`, `shipping.free_shipping_enabled`, `shipping.free_shipping_threshold`, `shipping.provider_name`, `shipping.api_enabled`, `shipping.api_config` (private, server-only).

### 16.4 Future Integration
- Adding a new provider requires new `ShippingProvider` implementation + settings entry, no order schema change.

---

## 17. Admin System

Minimum admin area:

| Section | Features |
|---|---|
| **Dashboard** | Orders count, revenue (MYR), pending payments, low stock, seller count, recent orders |
| **User Management** | List/search, role assignment, disable, view orders |
| **Seller Management** | List, approve/suspend (TBC workflow), view products/orders |
| **Product Management** | CRUD all products, status moderation, category assignment |
| **Category Management** | CRUD, slug, hierarchy TBC, sort |
| **Order Management** | List/filter by status/payment/seller, detail with items/payment/shipping, update status |
| **Payment Management** | List, view callback payloads, retry, refund TBC |
| **Shipping Settings** | Method, fixed rate, API toggle, thresholds, provider config (private fields masked) |
| **Marketplace Settings** | Name/description/status/maintenance/registration toggles |
| **Settings (11 categories)** | General, Branding, Currency, Marketplace, Checkout, Payment, Shipping, Localization, Contact, SEO, Email — see §7.12 |

- All admin routes under `/admin` with `auth` + `can:admin` middleware; Inertia pages at `resources/js/pages/Admin/*`.
- Policies enforce admin; audit log TBC.

---

## 18. Frontend/UI Requirements

> Design reference: `C:\Users\ASUS\Downloads\design.md` (Shopee-inspired marketplace UI, observed Sept 2026). This section adopts its visual language and interaction patterns as UI guidance only. Use original Barokah branding/assets — do NOT use Shopee trademarks. Business scope remains per source document; any design-implied backend feature not in source scope is marked TBC (see §24).

### 18.1 Design Direction
- Commerce-first, promotion-heavy, bright, energetic, highly scannable; dense but not chaotic.
- White surfaces dominant; brand orange for brand/price/CTA/discount/active states; light-gray page background between modules.
- Rectangular content modules, compact typography, product imagery, repeated horizontal carousels/grids.
- Desktop-first for catalog browsing, fully responsive for mobile. Visual priority order: (1) Search, (2) Promotions/banners, (3) Quick-access services, (4) Time-sensitive deals, (5) Best-selling/live/category discovery, (6) Recommendations.

### 18.2 Brand / Color / Typography / Spacing Tokens
- Default tokens come from design reference; actual brand colors MUST be read from Settings (`branding.primary_color`, `branding.secondary_color`) with design values as application defaults:
```css
:root {
  --brand-primary: #ee4d2d;
  --brand-primary-hover: #d94426;
  --brand-primary-soft: #fff1ed;
  --accent-red: #d0011b;
  --accent-yellow: #ffbb00;
  --accent-navy: #113366;
  --accent-blue: #0053de;
  --accent-cyan: #26aa99;
  --bg-page: #f5f5f5;
  --bg-surface: #ffffff;
  --bg-muted: #fafafa;
  --text-primary: #222222;
  --text-secondary: #555555;
  --text-muted: #888888;
  --text-faint: #aaaaaa;
  --text-inverse: #ffffff;
  --border-default: #e8e8e8;
  --border-soft: #f0f0f0;
  --shadow-card: 0 1px 3px rgba(0, 0, 0, 0.08);
  --shadow-hover: 0 4px 14px rgba(0, 0, 0, 0.12);
  --container-max: 1200px;
  --grid-gap: 10px;
  --section-gap: 20px;
}
```
- Typography: compact sans stack `Arial, Helvetica, "Noto Sans", sans-serif`; scale 10/11/12/13/14/16/18/20/24px; 400 body, 500–600 section emphasis, 600–700 price/discount/CTA. Section labels 16px medium; product title 12–14px; price 16–18px (detail 28–32px); discount badge 10–12px bold.
- Spacing: 4px base (4/8/12/16/20/24/32/40px). Cards 8–16px padding; homepage sections 16–24px gaps.
- Radius/borders: 2–4px cards/controls; pill only for badges/chips; thin neutral borders, hover shadows only as interactive cue. No gradients in core UI (campaign artwork may be richer).
- Tailwind + existing shadcn/ui components; implement tokens as CSS variables / Tailwind theme extension. No hard-coded site name or currency strings — branding/currency via settings store.

### 18.3 Page Structure & Container
- Desktop hierarchy: `<top-utility-bar/> <main-header> (logo + search + cart placeholder) <search-keywords/> <main> (hero-promo-grid, quick-services, flash-sale, best-sellers, shoppable-live, categories, recommendations) </main> <footer/>`.
- Centered container: `width: min(1200px, calc(100% - 32px))`; standard 1180–1200px; never stretch indefinitely on ultra-wide.
- Header: utility bar (30–34px, brand bg, white 12–13px text: seller center, help, language, register/login); main search header 70–80px brand bg with logo 160–190px, white search shell (min-height 40px, 3px padding, 2px radius, orange button 60px inside), cart zone 48–72px; trending keyword row 11–12px below search.
- Hero: asymmetric 2/3 + 1/3 grid, 230–260px height, 4–8px gap; autoplay with dots, swipe on mobile, `prefers-reduced-motion` respected.
- Quick services: 8–10 tiles/row desktop (90–120px wide, 42–52px icon, 12–13px label max 2 lines), whole tile clickable, subtle hover lift. Content is TBC (no finance/pay-later/games backend assumed).
- Section pattern: white surface, `margin-top: 20px`, header min-height 56px with `[TITLE ... Lihat Semua >]`, orange for campaign titles, dark gray for discovery.
- Footer: dynamic contact/business/SEO content from Settings.

### 18.4 Product Cards, Grids & Discovery
- ProductCard anatomy: square image (`aspect-ratio: 1/1`, `object-fit: cover`, white bg, lazy-load below fold) + badge overlay + 2-line clamped title (12–14px, lh 1.35–1.45) + orange price + gray strikethrough original + gray sold/rating + muted location. Hover: translateY(-2px) + brand border + shadow. Badges (mall/preferred/cashback/free-shipping/discount) never overwhelm image/title.
- Flash-sale cards (UI only; promo engine TBC): 180–200px wide, discount badge upper-right, orange price, 11–12px urgency line + orange-on-pale-orange progress bar.
- Best sellers (UI only; ranking algorithm TBC): 6–8 horizontal cards with image + short name + prominent sold count, carousel if overflow.
- Live section (UI placeholder only; streaming backend TBC): horizontal thumbnails (16:9/portrait), red/orange LIVE badge + viewer count + title; no simultaneous autoplay.
- Categories: white surface grid ~10 columns desktop (2 rows acceptable), tile min-height 140px, thin `#f0f0f0` dividers, centered square visual + label, gentle hover.
- Recommendation feed: longest section; desktop ≥1200px 6 cols / 992–1199 5 cols / 768–991 4 cols / mobile 2–3 cols (small mobile 2 cols), gap 10px (8px mobile), 8px outer padding mobile; skeleton loaders for lazy products, no blocking full-page spinner.
- Search/listing page: sidebar 180–220px (category, location, price min/max, rating, shipping, clear-all; 12–14px) + toolbar (relevance, latest, best-selling, price; active = orange) + product grid.
- Density: many small cards over few oversized; short titles; numerics near card bottom; section headers over editorial headlines; promotions strong but data predictable; minimal decorative whitespace.
- Data contracts (frontend types, price in MYR via `formatPrice()` — never `Rp` or manual `RM ` concat):
```ts
type ProductCardData = { id: string; slug: string; name: string; image: string; price: number; originalPrice?: number; discountPercent?: number; rating?: number; soldCount?: number; location?: string; badges?: string[]; freeShipping?: boolean; stockLabel?: string; };
type Category = { id: string; name: string; image: string; href: string; };
```
`soldCount`/`rating`/`discount` render only when backend provides them; otherwise omit (TBC backends).

### 18.5 Product Detail & Buy Flow
- Desktop layout: gallery (square main + thumbnail rail, orange border selected) | info (title, rating/sold, price strip on soft-orange panel 28–32px orange + gray strikethrough + badge, promo TBC, variants TBC, shipping quote, quantity, actions).
- Actions: design shows `[Add to Cart] [Buy Now]` (pale-orange vs solid orange, 44–48px, 2–4px radius). Business rule prevails: **Buy Now is mandatory and initiates direct checkout without cart**. Cart button/header icon is UI placeholder only (TBC — see §24); do NOT implement cart backend unless confirmed.
- Buy → Checkout: 4-step wizard (Buyer Info → Shipping → Payment → Confirmation) with progress, inline validation, MYR formatting throughout, contextual sticky action bar on mobile.
- Form controls: min-height 40px, `#d8d8d8` border, 2px radius, focus ring `rgba(238,77,45,.08)`; errors red 12px below field. Buttons: primary solid brand, secondary-orange pale, neutral white/gray; no excessive rounding.
- Icons: SVG line/solid, consistent stroke; header 14–18px, search/cart 22–28px, services 42–52px, inline meta 12–16px. Keyboard accessible; `alt` on images; status not color-only; visible focus; 44×44px touch targets.
- States: skeletons for loading; concise empty state (`No products found / Try changing filters / [Reset Filter]`); toasts 2–4s (desktop upper-right/center, mobile above bottom nav); centered modal 4–6px radius with confirm/cancel for critical purchase decisions. Error states: out-of-stock, payment failed/pending, shipping quote failure, validation errors.

### 18.6 Mobile, Breakpoints & Sticky
- ≤767px app-like: sticky top search + cart; utility bar hidden; keywords scrollable/hidden; hero single full-width carousel; services 5/row (2 rows or paging, 44–48px icons, 11–12px labels); grid 2 cols.
- Sticky mobile bottom nav (Home | Feed/Live | Cart | Notifications | Me — Cart/Live/Feed are placeholders, TBC): white, top border, 52–60px, orange active. Replaced by contextual purchase bar on product/checkout pages. Allowed sticky: desktop search after scroll (optional), mobile search, mobile bottom nav, mobile purchase bar. Never stack too many fixed bars.
- Breakpoints: ≤575, 576–767, 768–991, 992–1199, ≥1200. Test widths: 375, 390, 430, 768, 1024, 1366, 1440px.

### 18.7 Component Tree (Homepage Reference)
```
HomePage → UtilityBar, MainHeader (BrandLogo, SearchBox, SuggestedKeywords, CartButton[TBC]), HeroPromo (HeroCarousel, SidePromos), QuickServices, FlashSaleSection (DealCard[] [TBC backend]), BestSellerSection (BestSellerCard[] [TBC ranking]), LiveSection (LiveCard[] [placeholder]), CategorySection (CategoryTile[]), RecommendationSection (ProductCard[]), Footer
```
- Build order per design: (1) tokens+container, (2) header+search, (3) product card, (4) grid, (5) hero, (6) categories, (7) services, (8) flash carousel (UI), (9) best-seller carousel (UI), (10) live cards (placeholder), (11) search/filter page, (12) product detail, (13) mobile bottom nav, (14) loading/empty/error/modal.
- Visual QA before done: orange consistent; gray page + white modules; search dominant; consistent image ratio; titles truncate; price scannable; badges non-covering; 5–6/row desktop, 2/row mobile; gaps not whitespace; hover non-layout-shifting; touch targets; sticky bars non-covering; long names truncate.

---

## 19. Localization

- **Default:** English (`en`).
- **Additional:** Malay (`ms`) via GTranslate (JS widget). Architecture must allow future languages without rewrite.
- **Frontend Text Structure:**
  - Use translation keys (`resources/js/lang/en.json`, `ms.json`) or Laravel `__()` for server-rendered strings; Vue components reference keys, not literals.
  - Settings: `localization.default_language`, `localization.available_languages`, `date_format`, `time_format`, `timezone`, `number_format`, `currency_format` (TBC if separate).
  - GTranslate script inclusion toggled via `localization.gtranslate_enabled` setting (TBC).
- **Date/Number:** Format via settings-driven formatters; timezone from settings.

---

## 20. Security Requirements

- **Authentication:** Fortify + Sanctum; password hashing, email verification, throttling on login.
- **Authorization:** Gates/Policies for every resource; seller ownership validation on product/order mutations.
- **RBAC:** Admin/seller/buyer gates; middleware protection on admin/seller routes.
- **Request Validation:** FormRequests for all inputs; buyer fields, product, settings validated and sanitized.
- **Mass Assignment:** `$fillable` only; no `$guarded = []`.
- **File Upload:** Validate mimes/size, store outside public root via storage link, randomize filenames, scan TBC.
- **API Security:** Sanctum CSRF for SPA, throttle (`throttle:api`), no private settings in public endpoint.
- **Payment Webhook Verification:** HMAC/signature check (TBC algorithm), idempotency, replay protection, log all callbacks.
- **Sensitive Config:** Payment/shipping/SMTP secrets in `.env` + encrypted settings (private, `is_public=false`), never returned to frontend, masked in admin UI.
- **Rate Limiting:** Login, register, checkout, callback (separate limits).
- **Other:** XSS via escaped outputs, SQL injection via Eloquent, CSRF on web routes, secure headers.

---

## 21. Testing Strategy

| Level | Scope | Tools | Examples |
|---|---|---|---|
| **Unit** | Services, formatters, policies, enums | Pest | CurrencyFormatter formats MYR/RM per settings; ShippingService returns fixed rate; PaymentService maps statuses; Policy allows owner |
| **Feature** | HTTP + Inertia, auth flows | Pest Feature | Guest can Buy → order created; registered history; seller activation; product CRUD ownership; order creation transaction |
| **API** | REST endpoints, validation, auth, RBAC | Pest + `assertJson` | 422 on missing buyer fields; 403 seller cannot edit others product; public settings excludes private; payment callback signature invalid → 400 |
| **Auth** | Fortify flows | Existing tests extend | Registration, login, 2FA, password reset |
| **Authorization** | Policy matrix | Pest | Admin can manage all; seller scoped; guest cannot access seller routes |
| **Seller Ownership** | Product/order isolation | Pest | Seller A cannot update Seller B product; seller sees only own order items |
| **Product CRUD** | Validation, slug, images | Pest | Duplicate slug handled; image mimes rejected |
| **Order Creation** | Multi-seller, stock, totals | Pest | Order with 2 sellers creates 2 items; stock decremented after payment |
| **Payment Flow** | Initiate, callback, idempotency | Pest + mocked PayNetGateway | Pending → paid on valid callback; duplicate callback idempotent; failed → order retry |
| **Shipping Calculation** | Fixed + external mock | Pest | Fixed fee from settings; external provider mocked quote |
| **Guest Checkout** | No auth required | Pest | Guest order user_id null, retrievable via order_number + email |
| **Multi-Seller Orders** | Aggregation + seller views | Pest | Admin sees all items; each seller sees subset |

- Coverage target TBC (recommend 80%+ on services/policies). Run `php artisan test --compact`; Pest.

---

## 22. Development Phases

### Phase 1: Project Foundation & Architecture
- **Objectives:** Scaffold scalable Laravel+Vue foundation, settings/currency core, directory structure.
- **Features:** Laravel 11/12 base (existing Fortify/Inertia), marketplace config, Setting model/migration, SettingsService with cache, CurrencyFormatter, base layouts, CI.
- **DB:** `settings` table, add seller-related columns to users if needed.
- **Backend:** Setting model, SettingsService, CurrencyFormatter, config files, middleware, base policies.
- **Frontend:** Settings store, priceFormatter, branding components.
- **API:** `GET /settings/public` (public only).
- **Testing:** SettingsService, CurrencyFormatter, public settings excludes private.
- **Dependencies:** None.
- **Acceptance:** Settings CRUD via tinker, public endpoint returns only public, price displays via formatter.

### Phase 2: Authentication & User Roles
- **Objectives:** Complete auth + role system + seller activation.
- **Features:** Extend Fortify, seller activation toggle, role gates, user admin.
- **DB:** `sellers` table, users `is_active_as_seller` flag.
- **Backend:** Seller model, activation controller, policies, admin user management.
- **Frontend:** Profile "Active as Seller" UI, auth pages.
- **API:** `POST /seller/activate`, `GET /me`, `PUT /me`.
- **Testing:** Activation flow, role gates, auth tests.
- **Dependencies:** Phase 1.
- **Acceptance:** User can become seller; admin can assign roles; gates enforced.

### Phase 3: Seller System
- **Objectives:** Seller profile and dashboard.
- **Features:** Seller dashboard, store profile, product ownership scope.
- **DB:** Sellers seeded.
- **Backend:** Seller policies, seller order scope (via order_items).
- **Frontend:** `Seller/Dashboard.vue`, store settings.
- **API:** Seller order listing.
- **Testing:** Seller isolation, ownership.
- **Dependencies:** Phase 2.
- **Acceptance:** Seller sees only own products/orders.

### Phase 4: Product & Category System
- **Objectives:** Catalog management.
- **Features:** Category CRUD (admin), Product CRUD (seller/admin), images, status, slug.
- **DB:** `categories`, `products`, `product_images`.
- **Backend:** Product/Category controllers, requests, resources, policies, image handling.
- **Frontend:** Seller product pages, admin category/product pages, product listing/detail (Shopee-like grid).
- **API:** Product/category endpoints (public + seller).
- **Testing:** Product CRUD, validation, ownership, slug uniqueness, image validation.
- **Dependencies:** Phase 3.
- **Acceptance:** Seller can CRUD own products; public can browse.

### Phase 5: Marketplace Frontend
- **Objectives:** Shopee-inspired homepage per design.md + listing/detail; Velocity retained only for checkout/admin patterns where design.md is silent.
- **Features:** Home (utility bar, search header, hero grid, quick-services UI, flash/best-seller/live UI placeholders, categories, recommendations), search, category filters, product detail with Buy Now (mandatory, no cart backend).
- **DB:** Seed categories/products.
- **Backend:** Listing APIs with pagination/search.
- **Frontend:** Home (§18.7 tree), listing (sidebar 180–220px + sort toolbar), detail (gallery + price strip + Buy Now), mobile bottom nav, footer (dynamic branding); design tokens as CSS variables, brand colors from settings.
- **API:** Listing/search.
- **Testing:** Listing pagination, search + visual QA per §18.7 (orange consistent, 5–6/row desktop, 2/row mobile, titles truncate, sticky non-covering).
- **Dependencies:** Phase 4.
- **Acceptance:** Homepage renders per §18.3–18.4 with original Barokah branding; detail Buy Now initiates direct checkout; flash/best-seller/live render as UI-only without invented backends.

### Phase 6: Buy & Checkout Flow
- **Objectives:** 4-step direct Buy without cart.
- **Features:** Buyer Information (5 fields), shipping quote, payment method selection, order creation.
- **DB:** `orders`, `order_items` initial.
- **Backend:** CheckoutController, BuyerInformationRequest, order creation transaction.
- **Frontend:** Checkout wizard (4 steps), validation, order confirmation.
- **API:** `POST /orders`, `POST /shipping/quote`.
- **Testing:** Guest checkout, validation, order creation.
- **Dependencies:** Phase 5.
- **Acceptance:** Guest can complete Buy flow to pending_payment.

### Phase 7: Order System
- **Objectives:** Multi-seller order lifecycle + status.
- **Features:** Order status machine, seller-scoped views, admin order management, expiration job.
- **DB:** Order status enums, expired_at.
- **Backend:** OrderService, scheduled ExpirePendingOrder, notifications.
- **Frontend:** Order history, seller orders, admin orders.
- **API:** Order listing/detail per role.
- **Testing:** Multi-seller order, status transitions, expiration.
- **Dependencies:** Phase 6.
- **Acceptance:** Multi-seller order aggregates correctly; expiration works.

### Phase 8: Payment Integration
- **Objectives:** PayNet FPX/DuitNow via abstraction.
- **Features:** PaymentService, PayNetGateway (mockable), initiate, callback/webhook, status sync.
- **DB:** `payments` table.
- **Backend:** PaymentController, webhook verification, queue jobs.
- **Frontend:** Payment method selector, redirect/QR, pending polling, confirmation.
- **API:** Payment initiate/callback/status.
- **Testing:** Payment initiate, valid/invalid callback, idempotency, pending/failed flows.
- **Dependencies:** Phase 7.
- **Acceptance:** Sandbox PayNet flow succeeds; failed/pending handled; no secrets exposed.

### Phase 9: Shipping Integration
- **Objectives:** Fixed Rate + external API abstraction.
- **Features:** ShippingService, FixedRate provider, External provider skeleton.
- **DB:** Shipping config via settings; optional `shippings` quote history.
- **Backend:** ShippingController, provider strategy, admin shipping settings UI.
- **Frontend:** Shipping step quote display, admin shipping settings.
- **API:** Quote, admin shipping settings.
- **Testing:** Fixed rate, external mock, free threshold.
- **Dependencies:** Phase 6 (checkout).
- **Acceptance:** Fixed rate configurable; switching provider requires no order refactor.

### Phase 10: Admin System
- **Objectives:** Complete admin area + 11-category Settings UI.
- **Features:** Dashboard metrics, user/seller/product/category/order/payment management, Settings UI with validation/caching.
- **DB:** Settings seeded with defaults.
- **Backend:** Admin controllers, dashboard queries, Settings admin endpoints.
- **Frontend:** Admin layouts/pages, Settings categorized forms.
- **API:** `GET/PUT /admin/settings`, dashboard data.
- **Testing:** Admin RBAC, settings validation, public/private separation, cache invalidation.
- **Dependencies:** Phases 1-9.
- **Acceptance:** Admin can manage all sections; settings changes propagate; private never public.

### Phase 11: Testing & Security
- **Objectives:** Harden and cover.
- **Features:** Pest suite for all §21 scopes, policy tests, webhook security, rate limiting, file upload, mass assignment audits.
- **DB:** Test factories/seeders complete.
- **Backend:** Throttles, webhook signature, sanitization.
- **Frontend:** XSS-safe rendering, formatter usage audit.
- **API:** Security tests (403, 422, 400).
- **Testing:** Full suite green, coverage report.
- **Dependencies:** Phases 1-10.
- **Acceptance:** All tests pass; no hard-coded currency/branding; secrets not exposed.

### Phase 12: Production Deployment
- **Objectives:** Ship.
- **Features:** Env config, queue/scheduler, storage link, GTranslate inclusion, SEO, backups, monitoring.
- **DB:** Production migration, seed settings defaults.
- **Backend:** Horizon/queue, scheduler for expiration, logging.
- **Frontend:** Build, CDN, OG/meta from settings.
- **API:** Rate limits production-tuned.
- **Testing:** Smoke/E2E (guest buy → payment mock → confirmation), load basic.
- **Dependencies:** Phase 11.
- **Acceptance:** Deployed, guest can buy real PayNet sandbox, settings manageable without deploy.

---

## 23. Acceptance Criteria (Global)

- Admin can manage supported settings; public settings retrievable by frontend; private settings never exposed.
- Currency formatting centralized; branding dynamic; shipping/payment configuration dynamic and server-only for secrets.
- Multi-seller order contains products from different sellers with seller-specific item ownership.
- Direct Buy flow completes for guest and authenticated buyer with 5 buyer fields validated.
- PayNet FPX/DuitNow initiate + callback + pending/failed handling works with mocked and (when provided) real credentials; no hard-coded endpoints/credentials.
- ShippingService abstraction allows Fixed Rate or external API without order schema change.
- Seller cannot access other sellers' products/orders; admin can access all.
- Homepage, listing, product detail, and mobile layout follow design reference `C:\Users\ASUS\Downloads\design.md` (§18.1–18.7) with original Barokah branding (no Shopee trademarks); checkout/admin follow Velocity patterns only where design.md is silent; flash/best-seller/live/cart-affordances are UI-only placeholders per §24 items 21–25.
- English default, Malay via GTranslate, keys-structured text.
- Pest suite covers §21; Pint clean; no hard-coded business config.

---

## 24. TBC / Open Questions

| # | Item | Decision Required |
|---|---|---|
| 1 | **PayNet API details** | Base URL, auth, FPX vs DuitNow request/response shapes, signature algorithm, redirect vs QR, sandbox/prod credentials, webhook URL registration, retry policy. Block Phase 8 real integration. Use placeholders `TBC_PAYNET_*`. |
| 2 | **Shipping API provider & spec** | Provider name, base URL, auth, request (origin/postcode/weight) and response shapes, Malaysia coverage, rate calculation. Block Phase 9 external. Use `TBC_SHIPPING_*`. |
| 3 | **Cart vs Buy-only** | Spec mandates direct Buy without cart. Is cart needed later? Impacts Order architecture (single product vs multi-product checkout). Recommend keep Buy-only for MVP, add cart as enhancement without breaking order model. |
| 4 | **Buyer email field** | Source lists 5 fields without email. Is email required for receipt/guest lookup? Propose optional email (+ validation) but confirm. |
| 5 | **Phone/postcode formats** | Malaysia validation regex for phone and postcode, state dropdown list (14 states/territories). Confirm exact options. |
| 6 | **Seller approval workflow** | Is activation instant or admin-approval (pending → active)? Spec says activate from profile; approval TBC. |
| 7 | **Roles storage** | Simple flags vs `spatie/laravel-permission`. Decision affects RBAC implementation. |
| 8 | **Category hierarchy** | Single level or parent/child? Propose nullable parent_id but confirm. |
| 9 | **Price storage** | Decimal vs integer minor units (cents). Recommend decimal 10,2 with formatter, but confirm rounding/tax needs. |
| 10 | **Stock decrementation timing** | On order create (reserve) vs on payment success. Recommend reserve on create + restore on expiry/failure, but confirm. |
| 11 | **Order number format** | Human-readable pattern (e.g., `BRK-YYYYMMDD-XXXX`). TBC. |
| 12 | **Order sub-orders per seller** | Current: one order + seller_id on items. Alternative: split sub-orders per seller for fulfillment. TBC if fulfillment requires split. |
| 13 | **Reviews/ratings, inventory, maintenance mode** | Settings toggles exist but business behavior undefined. Do we implement review backend now or just toggle? Mark TBC. |
| 14 | **Minimum/maximum order amount & expiration** | Values for checkout settings; propose 0 / no max / 30min but confirm. |
| 15 | **GTranslate implementation** | Widget JS inclusion vs API? Confirm GTranslate plan (free widget vs paid). |
| 16 | **Image constraints** | Max size, count per product, allowed mimes, CDN need. Propose 5 images, 2MB, jpg/png/webp. |
| 17 | **Shipping free threshold & fee** | Default fixed rate value (e.g., RM 5-10). TBC. |
| 18 | **Currency future** | Only MYR now but settings allows change; confirm if multi-currency per product needed later (TBC, not MVP). |
| 19 | **Notifications** | Email/SMS/WhatsApp for order/payment/seller? Spec lists Email settings but behavior TBC. |
| 20 | **SEO page-level** | Global settings now, page-level extensible later — confirm if product/category need per-page SEO now. |
| 21 | **Flash-sale / promo engine (design §12)** | Design shows flash-sale cards with discount badge, urgency line, progress bar. No promo backend in source scope. UI may render placeholders; engine (discounts, schedules, stock allocation) TBC. Do NOT build promo backend unless confirmed. |
| 22 | **Best-seller ranking (design §14)** | Design shows "Produk Terlaris" with sold counts. Ranking algorithm and `soldCount` source undefined. UI shows sold count only when backend provides it; ranking backend TBC. |
| 23 | **Live shopping (design §15)** | Design shows live thumbnails + LIVE badge. No streaming backend in scope. UI placeholder only; backend TBC. |
| 24 | **Cart UI vs backend (design §19)** | Design shows Add-to-Cart button style + header cart icon + bottom-nav cart tab. Business rule: Buy Now only, no cart backend. Cart affordances are UI placeholders. Confirm if/when cart backend is wanted. |
| 25 | **Quick services, ratings, original price, location (design §§10,13)** | Design shows service tiles, star ratings, strikethrough original price, seller location. None have source-scope backends. Render only when backend provides data; backends TBC. |
| 26 | **Design tokens vs brand settings** | Design tokens (#ee4d2d etc.) are application defaults. Actual colors come from `branding.primary_color` / `secondary_color` settings. Confirm final Barokah brand colors. |
| 27 | **Suggested keywords & banners** | Trending keyword row, hero/side promos, campaign artwork sources undefined. Confirm whether admin-manageable (settings/CMS TBC) or static seed. |

Additional settings TBC items (per spec): any setting whose business behavior not defined must remain TBC and not be implemented beyond the toggle.
Design-implied backends (items 21–25) must remain UI-only placeholders until confirmed; do NOT silently introduce promo/ranking/live/cart/review backends.

---

## 25. Recommended Project Structure

```
barokah/
  app/
    Enums/          # OrderStatus, PaymentStatus, PaymentMethod, ShippingMethod, SettingType/Group
    Models/         # User, Seller, Category, Product, ProductImage, Order, OrderItem, Payment, Setting
    Services/       # SettingsService, CurrencyFormatter, PaymentService, PayNetGateway, ShippingService
    Http/
      Controllers/
        Web/        # Inertia pages
        Api/V1/     # REST
        Admin/
      Requests/     # FormRequests
      Resources/    # API Resources
      Middleware/
    Policies/
    Jobs/           # ProcessPaymentCallback, ExpirePendingOrder
    Notifications/
    Providers/
  config/
    marketplace.php (incl. design token defaults mirroring design.md §2/§37)
    paynet.php
    shipping.php
  resources/css/
    marketplace-tokens.css (CSS variables from §18.2; brand colors overridden at runtime from settings)
  database/
    migrations/
    factories/
    seeders/        # Settings defaults, categories, demo products
  resources/
    js/
      pages/        # Home, Product, Checkout/*, Seller/*, Admin/*, Admin/Settings/*
      components/   # ui/*, product/*, checkout/*, admin/*
      stores/       # settings, checkout
      services/     # priceFormatter, settingsService
      composables/
      lang/         # en.json, ms.json (keys)
    css/
    views/
  routes/
    web.php
    api.php
    admin.php
    console.php
  tests/
    Feature/        # Auth, Seller, Product, Order, Payment, Shipping, Settings
    Unit/           # Services, Formatter, Policies
```

**Key Principles:**
- Terminology consistent (Buyer = Customer, Seller = store owner, Order = customer order, Payment = PayNet transaction, Shipping = fulfillment fee).
- No silent requirement changes; all assumptions flagged TBC.
- No unnecessary features; no hard-coded business config — every configurable value via SettingsService.
- Each module independently understandable; payment/shipping integrations swappable without order refactoring.

---

*End of Specification — Ready for tasks.md / checklist.md and implementation.*
