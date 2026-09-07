# Checklist — Barokah Multi-Seller Marketplace

> Change ID: `create-barokah-marketplace-spec`
> Centang hanya jika terbukti via kode/test. Semua TBC tetap eksplisit.

- [x] Spesifikasi 25 bagian lengkap (Overview → Struktur Proyek) dan konsisten terminologi
- [x] Tidak ada requirement bisnis baru yang dikarang; item tak terdefinisi bertanda TBC dengan keputusan yang dibutuhkan
- [x] Peran Admin/Seller/Buyer + matriks kapabilitas terdefinisi
- [x] Produk: field ownership, category, name, slug, description, price, stock, images, status, timestamps — tanpa field tambahan non-TBC
- [x] Buy flow: Detail → Buy → Buyer Info → Shipping → Payment → Konfirmasi tanpa cart wajib
- [x] Buyer form memuat tepat: Name, Address, State, Post Code, Phone Number
- [x] Payment: FPX + DuitNow via PayNet, abstraksi PaymentService, status, callback/webhook, relasi order/payment, error/failed/pending — endpoint API PayNet berupa placeholder/TBC
- [x] Currency MYR/RM dengan CurrencyFormatter terpusat; tidak ada konkatenasi manual di Vue
- [x] Shipping: Fixed Rate + abstraksi ShippingService untuk API eksternal; provider/spesifikasi TBC
- [x] Order multi-seller: Order, OrderItem, relasi seller, payment, shipping, status order/payment, penanganan info per-seller
- [x] Admin: Dashboard, User, Seller, Product, Category, Order, Payment, Shipping settings, Marketplace settings
- [x] Frontend: mengikuti design `C:\Users\ASUS\Downloads\design.md` §18.1–18.7 (token, header, hero, kartu, grid 6/5/4/2, listing, detail, mobile nav, visual QA) dengan branding orisinal Barokah; checkout/admin ala-Velocity hanya bila design diam; flash/best-seller/live/cart hanya UI placeholder (TBC §24 item 21–25)
- [x] Bahasa: English default, Malay via GTranslate, teks berbasis key
- [x] Arsitektur Laravel + Vue: direktori, modul, API, services, controllers, requests, resources, models, policies, relasi DB, error handling, SoC
- [x] Keamanan: auth, authorization, RBAC, ownership seller, validasi, mass assignment, upload, API, verifikasi webhook, proteksi config sensitif, rate limiting
- [x] ERD: User, Seller, Product, Category, Order, OrderItem, Payment, Shipping + relasi jelas; tabel tambahan hanya bila perlu dan bertanda
- [x] API: contoh Auth, Products, Categories, Seller products, Orders, Payments, Shipping, Customer profile — tiap endpoint ada method, URI, auth, role, request, response, validasi, error
- [x] Testing: unit, feature, API, auth, authorization, ownership, CRUD produk, pembuatan order, payment, shipping, guest checkout, multi-seller
- [x] 12 fase pengembangan dengan objectives, features, DB, backend, frontend, API, testing, dependencies, acceptance
- [x] Settings dinamis: 11 kategori UI, public vs private, API settings, caching, defaults, prioritas, aturan developer no hard-code
- [x] Acceptance criteria settings terpenuhi (admin kelola, public terpisah, currency terpusat, branding dinamis, dst.)
- [x] Peran Admin/Seller/Buyer + matriks kapabilitas terverifikasi via gate/policy + test RBAC
- [x] Fondasi settings + formatter + endpoint public terverifikasi via test public vs private
- [x] Aktivasi seller dari profil + isolasi seller terverifikasi via test
- [x] Payment PayNet (initiate, callback, idempotensi) terverifikasi via PaymentFlowTest
- [x] TBC/Open Questions terdokumentasi (PayNet, shipping API, cart, email buyer, format telepon/postcode, approval seller, roles, kategori hierarki, price storage, stok timing, order number, sub-order, reviews, flash/ranking/live/services/keywords/banners dari design, dsb.)
