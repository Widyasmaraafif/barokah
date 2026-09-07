<?php

use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminSellerController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentCallbackController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PublicSettingController;
use App\Http\Controllers\Api\V1\SellerActivationController;
use App\Http\Controllers\Api\V1\SellerOrderController;
use App\Http\Controllers\Api\V1\SellerProductController;
use App\Http\Controllers\Api\V1\ShippingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('settings/public', PublicSettingController::class)->name('settings.public');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');

    // Guest direct Buy checkout (spec §11.4/§14.4); guest allowed so the
    // route sits outside the auth group, throttled per spec §20.
    Route::post('orders', [CheckoutController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('orders.store');

    // PayNet initiation + status polling (spec §11.5/§15); guest orders
    // stay reachable by order_number until the guest token lands (TBC §12).
    Route::post('orders/{orderNumber}/payments', [PaymentController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('orders.payments.store');
    Route::get('orders/{orderNumber}/payment', [PaymentController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('orders.payment.show');

    // PayNet callback/webhook (spec §11.5/§15.4): public but
    // signature-verified, stricter throttle per spec §20.
    Route::post('payments/callback', PaymentCallbackController::class)
        ->middleware('throttle:60,1')
        ->name('payments.callback');
    Route::post('payments/webhook', PaymentCallbackController::class)
        ->middleware('throttle:60,1')
        ->name('payments.webhook');

    // Shipping quote (spec §11.6/§16.2); guest allowed, throttled per spec §20.
    Route::post('shipping/quote', [ShippingController::class, 'quote'])
        ->middleware('throttle:30,1')
        ->name('shipping.quote');

    // Session auth (Fortify web guard) is used for the Inertia SPA until the
    // Sanctum decision (spec §7.5/§12) is confirmed and the dependency added.
    Route::middleware('auth')->group(function (): void {
        Route::get('me', [ProfileController::class, 'show'])->name('me.show');
        Route::put('me', [ProfileController::class, 'update'])->name('me.update');
        Route::put('me/password', [ProfileController::class, 'updatePassword'])->name('me.password');
        Route::post('seller/activate', SellerActivationController::class)->name('seller.activate');

        // Buyer order history (spec §11.4): own orders only.
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');

        Route::middleware('can:seller')->group(function (): void {
            Route::get('seller/orders', [SellerOrderController::class, 'index'])->name('seller.orders.index');
            Route::get('seller/orders/{orderNumber}', [SellerOrderController::class, 'show'])->name('seller.orders.show');
            // Products resolve by ID here (spec §11.3 `{id}`); the public
            // detail route resolves by slug via Product::getRouteKeyName().
            Route::get('seller/products', [SellerProductController::class, 'index'])->name('seller.products.index');
            Route::post('seller/products', [SellerProductController::class, 'store'])->name('seller.products.store');
            Route::get('seller/products/{product:id}', [SellerProductController::class, 'show'])->name('seller.products.show');
            Route::put('seller/products/{product:id}', [SellerProductController::class, 'update'])->name('seller.products.update');
            Route::delete('seller/products/{product:id}', [SellerProductController::class, 'destroy'])->name('seller.products.destroy');
        });

        Route::middleware('can:admin')->group(function (): void {
            Route::get('admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
            Route::get('admin/orders/{orderNumber}', [AdminOrderController::class, 'show'])->name('admin.orders.show');

            Route::get('admin/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
            Route::put('admin/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');

            Route::get('admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
            Route::get('admin/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
            Route::put('admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');

            Route::get('admin/sellers', [AdminSellerController::class, 'index'])->name('admin.sellers.index');
            Route::get('admin/sellers/{seller}', [AdminSellerController::class, 'show'])->name('admin.sellers.show');
            Route::put('admin/sellers/{seller}', [AdminSellerController::class, 'update'])->name('admin.sellers.update');

            Route::get('admin/products', [AdminProductController::class, 'index'])->name('admin.products.index');
            Route::get('admin/products/{product:id}', [AdminProductController::class, 'show'])->name('admin.products.show');
            Route::put('admin/products/{product:id}', [AdminProductController::class, 'update'])->name('admin.products.update');
            Route::delete('admin/products/{product:id}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');

            Route::get('admin/categories', [AdminCategoryController::class, 'index'])->name('admin.categories.index');
            Route::post('admin/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
            Route::put('admin/categories/{category}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
            Route::delete('admin/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');

            Route::get('admin/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
            Route::get('admin/payments/{payment}', [AdminPaymentController::class, 'show'])->name('admin.payments.show');
        });
    });
});
