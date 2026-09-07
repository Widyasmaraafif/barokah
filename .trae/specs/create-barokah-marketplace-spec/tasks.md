# Tasks — Barokah Multi-Seller Marketplace

> Change ID: `create-barokah-marketplace-spec`
> Sumber: `spec.md` di folder yang sama. Jangan ubah requirement secara diam-diam. Semua item TBC tetap TBC.

- [x] Task 1: Fondasi Proyek & Arsitektur (Fase 1)
  - [x] SubTask 1.1: Struktur direktori `app/Services`, `app/Enums`, `resources/js/stores`, `resources/js/services`, `resources/js/components/marketplace`, `resources/css/marketplace-tokens.css`, `routes/admin.php`
  - [x] SubTask 1.2: Tabel `settings` + model `Setting` + `SettingsService` dengan cache + invalidasi
  - [x] SubTask 1.3: `CurrencyFormatter` / `PriceFormatter` terpusat + store `settings` di Vue + token design (§18.2) sebagai default, warna brand dari settings
  - [x] SubTask 1.4: Endpoint `GET /api/v1/settings/public` (hanya public) + config `marketplace.php`
  - [x] SubTask 1.5: Test: settings public vs private, formatter MYR/RM

- [x] Task 2: Autentikasi & Peran (Fase 2)
  - [x] SubTask 2.1: Peran Admin/Seller/Buyer (gate/policy + middleware `can:admin`, `can:seller`)
  - [x] SubTask 2.2: Aktivasi "Active as Seller" dari profil + tabel `sellers` + `POST /api/v1/seller/activate`
  - [x] SubTask 2.3: Endpoint profil `GET/PUT /api/v1/me`
  - [x] SubTask 2.4: Test: aktivasi seller, RBAC, auth

- [x] Task 3: Sistem Seller (Fase 3)
  - [x] SubTask 3.1: Model `Seller`, policy `SellerPolicy`, dashboard seller
  - [x] SubTask 3.2: Halaman `Seller/Dashboard.vue` + API `GET /api/v1/seller/orders` (via order_items)
  - [x] SubTask 3.3: Test: isolasi seller (hanya produk/order milik sendiri)

- [x] Task 4: Produk & Kategori (Fase 4)
  - [x] SubTask 4.1: Tabel `categories`, `products`, `product_images` + relasi
  - [x] SubTask 4.2: CRUD seller `POST/PUT/DELETE /api/v1/seller/products` + validasi + slug unik + upload gambar
  - [x] SubTask 4.3: API publik `GET /api/v1/products`, `GET /api/v1/products/{slug}`, `GET /api/v1/categories`
  - [x] SubTask 4.4: Halaman listing + detail produk + `ProductPolicy`
  - [x] SubTask 4.5: Test: CRUD, ownership, validasi gambar, slug

- [x] Task 5: Frontend Marketplace (Fase 5, mengacu design `C:\Users\ASUS\Downloads\design.md` §18.1–18.7, branding orisinal Barokah)
  - [x] SubTask 5.1: Token + container + header (UtilityBar, MainHeader: logo/search/cart-placeholder, keywords) + hero grid 2/3+1/3 + footer dinamis
  - [x] SubTask 5.2: ProductCard + grid rekomendasi (6/5/4/2 kolom) + skeleton; CategorySection (~10 kolom); QuickServices UI (konten TBC)
  - [x] SubTask 5.3: Flash/best-seller/live sebagai UI-only placeholder (tanpa backend promo/ranking/streaming); sold/rating/diskon hanya bila backend menyediakan
  - [x] SubTask 5.4: Listing (sidebar 180–220px + sort toolbar) + filter/search + pagination + empty state + mobile bottom nav
  - [x] SubTask 5.5: Detail produk (galeri + price strip + Buy Now wajib tanpa cart backend; tombol cart hanya placeholder) + checkout pattern
  - [x] SubTask 5.6: Test: listing, search, pagination + visual QA §18.7 (orange konsisten, 5–6/row desktop, 2/row mobile, judul truncate, sticky tidak menutupi)

- [x] Task 6: Alur Buy & Checkout (Fase 6)
  - [x] SubTask 6.1: Tabel `orders` + `order_items` (snapshot + seller_id)
  - [x] SubTask 6.2: Wizard 4 langkah: Buyer Information (5 field wajib) → Shipping → Payment → Konfirmasi
  - [x] SubTask 6.3: `CheckoutController` + `BuyerInformationRequest` + transaksi order `POST /api/v1/orders`
  - [x] SubTask 6.4: Test: guest checkout, validasi buyer, pembuatan order

- [x] Task 7: Sistem Order Multi-Seller (Fase 7)
  - [x] SubTask 7.1: Status order & payment enum + sinkronisasi + `expired_at`
  - [x] SubTask 7.2: Job `ExpirePendingOrder` + scheduler + restore stok
  - [x] SubTask 7.3: View order per peran (buyer/seller/admin) + `OrderPolicy`
  - [x] SubTask 7.4: Test: multi-seller order, transisi status, expiry

- [x] Task 8: Integrasi Pembayaran PayNet (Fase 8)
  - [x] SubTask 8.1: Tabel `payments` + `PaymentService` + `PayNetGateway` (placeholder `TBC_PAYNET_*`)
  - [x] SubTask 8.2: Initiate `POST /api/v1/orders/{n}/payments` (fpx/duitnow) + status `GET .../payment`
  - [x] SubTask 8.3: Callback/webhook terverifikasi + job `ProcessPaymentCallback` + idempotensi
  - [x] SubTask 8.4: UI metode bayar + polling pending + halaman konfirmasi
  - [x] SubTask 8.5: Test: initiate, callback valid/invalid signature, idempotensi, failed/pending

- [x] Task 9: Integrasi Shipping (Fase 9)
  - [x] SubTask 9.1: `ShippingService` + `FixedRateShippingProvider` + skeleton `ExternalShippingProvider` (`TBC_SHIPPING_*`)
  - [x] SubTask 9.2: `POST /api/v1/shipping/quote` + snapshot `shipping_fee` di order
  - [x] SubTask 9.3: Test: fixed rate, threshold gratis, mock eksternal

- [x] Task 10: Sistem Admin & Settings (Fase 10)
  - [x] SubTask 10.1: Area `/admin` + Dashboard metrik + CRUD user/seller/product/category/order/payment
  - [x] SubTask 10.2: UI Settings 11 kategori (General, Branding, Currency, Marketplace, Checkout, Payment, Shipping, Localization, Contact, SEO, Email)
  - [x] SubTask 10.3: API `GET/PUT /api/v1/admin/settings` + validasi per-key + sanitasi + mask private
  - [x] SubTask 10.4: Seed default settings (branding, MYR/RM, en) + prioritas DB > env > default
  - [x] SubTask 10.5: Test: RBAC admin, validasi settings, public/private, invalidasi cache

- [x] Task 11: Testing & Keamanan (Fase 11)
  - [x] SubTask 11.1: Suite Pest: unit, feature, API, ownership, guest, multi-seller, payment, shipping
  - [x] SubTask 11.2: Audit: fillable, upload, throttle, webhook signature, XSS, secrets tidak bocor
  - [x] SubTask 11.3: Pint + coverage + audit no hard-code (`RM`, nama marketplace, fee)

- [x] Task 12: Deployment Produksi (Fase 12)
  - [x] SubTask 12.1: Env, queue/scheduler, storage link, GTranslate, SEO/meta dari settings
  - [x] SubTask 12.2: Migrasi + seed settings produksi + smoke E2E (guest buy → payment mock → konfirmasi)

# Task Dependencies
- Task 2 depends on Task 1
- Task 3 depends on Task 2
- Task 4 depends on Task 3
- Task 5 depends on Task 4
- Task 6 depends on Task 5
- Task 7 depends on Task 6
- Task 8 depends on Task 7
- Task 9 depends on Task 6
- Task 10 depends on Task 1, Task 7, Task 8, Task 9
- Task 11 depends on Task 10
- Task 12 depends on Task 11
- Task 8 dan Task 9 dapat dikerjakan paralel setelah Task 7 / Task 6
