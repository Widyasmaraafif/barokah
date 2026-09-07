<?php

use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ProductIndexController;
use App\Http\Controllers\Web\ProductShowController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('products', ProductIndexController::class)->name('products.index');
Route::get('products/{slug}', ProductShowController::class)->name('products.show');

// Direct Buy wizard (spec §6.1/§18.5); guest allowed. Confirmation lookup
// by order_number is scoped per role in Task 7 (TBC spec §12 guest token).
Route::get('checkout/{slug}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::get('checkout/confirmation/{orderNumber}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('seller/dashboard', SellerDashboardController::class)
        ->middleware('can:seller')
        ->name('seller.dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
