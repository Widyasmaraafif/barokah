<?php

use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\SellerProductController;
use App\Http\Controllers\SellerSettingsController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ProductIndexController;
use App\Http\Controllers\Web\ProductShowController;
use App\Http\Controllers\Web\SellerShowController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('products', ProductIndexController::class)->name('products.index');
Route::get('products/{slug}', ProductShowController::class)->name('products.show');
Route::get('sellers/{slug}', SellerShowController::class)->name('sellers.show');
Route::get('cart', CartController::class)->name('cart.show');

// Multi-item cart checkout must precede the product slug route.
Route::get('checkout/cart', [CheckoutController::class, 'cart'])->name('checkout.cart');

// Direct Buy wizard (spec §6.1/§18.5); guest allowed. Confirmation lookup
// by order_number is scoped per role in Task 7 (TBC spec §12 guest token).
Route::get('checkout/{slug}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::get('checkout/confirmation/{orderNumber}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
Route::get('checkout/order/{orderNumber}', [CheckoutController::class, 'confirmation'])->name('checkout.resume');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::redirect('seller', 'seller/dashboard')->name('seller.index');
    Route::get('seller/dashboard', SellerDashboardController::class)
        ->middleware('can:seller')
        ->name('seller.dashboard');
    Route::get('seller/settings', SellerSettingsController::class)
        ->middleware('can:seller')
        ->name('seller.settings');
    Route::get('seller/orders', [SellerOrderController::class, 'index'])
        ->middleware('can:seller')
        ->name('seller.orders.index');
    Route::get('seller/orders/{orderNumber}', [SellerOrderController::class, 'show'])
        ->middleware('can:seller')
        ->name('seller.orders.show');
    Route::get('seller/products', [SellerProductController::class, 'index'])
        ->middleware('can:seller')
        ->name('seller.products.index');
    Route::get('seller/products/create', [SellerProductController::class, 'create'])
        ->middleware('can:seller')
        ->name('seller.products.create');
    Route::get('seller/products/{product:id}/edit', [SellerProductController::class, 'edit'])
        ->middleware('can:seller')
        ->name('seller.products.edit');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
