<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('database seeder seeds fixed demo accounts and catalog', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();

    $admin = User::query()->where('email', 'admin@barokah.local')->firstOrFail();
    expect($admin->is_admin)->toBeTrue();
    expect(Hash::check('password', $admin->password))->toBeTrue();

    foreach (['seller.keripik@barokah.local', 'seller.hijab@barokah.local', 'seller.kerudung@barokah.local'] as $email) {
        $sellerUser = User::query()->where('email', $email)->firstOrFail();
        expect($sellerUser->is_active_as_seller)->toBeTrue();
        expect($sellerUser->seller)->not->toBeNull();
    }

    expect(User::query()->where('email', 'customer@barokah.local')->exists())->toBeTrue();
    expect(Seller::query()->count())->toBe(3);

    expect(Category::query()->whereIn('slug', ['keripik', 'hijab', 'kerudung'])->count())->toBe(3);
    expect(Product::query()->whereIn('slug', [
        'keripik-pisang-original',
        'keripik-singkong-balado',
        'hijab-paris-premium',
        'pashmina-kaos-basic',
        'kerudung-bergo-maryam',
        'kerudung-segi-empat-voal',
    ])->count())->toBe(6);

    $product = Product::query()->where('slug', 'keripik-pisang-original')->firstOrFail();
    expect($product->images)->toHaveCount(1);
    expect($product->images->firstWhere('is_primary', true))->not->toBeNull();
    expect(ProductImage::query()->count())->toBe(6);

    expect(Setting::query()->count())->toBeGreaterThan(0);
});

test('database seeder keeps order totals and payments consistent', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Order::query()->whereIn('order_number', ['BRK-DEMO-0001', 'BRK-DEMO-0002', 'BRK-DEMO-0003'])->count())->toBe(3);

    foreach (Order::query()->with(['items', 'payment'])->get() as $order) {
        $itemsSubtotal = (float) $order->items->sum('subtotal');
        expect((float) $order->subtotal)->toEqual($itemsSubtotal);
        expect((float) $order->total)->toEqual($itemsSubtotal + (float) $order->shipping_fee);
        expect($order->payment)->not->toBeNull();
        expect((float) $order->payment->amount)->toEqual((float) $order->total);
    }

    $pending = Order::query()->where('order_number', 'BRK-DEMO-0001')->firstOrFail();
    expect($pending->items)->toHaveCount(2);
    expect($pending->payment->status->value)->toBe('pending');

    $paid = Order::query()->where('order_number', 'BRK-DEMO-0002')->firstOrFail();
    expect($paid->payment->status->value)->toBe('paid');
    expect($paid->payment->transaction_id)->toBe('PAYNET-DEMO-0002');

    expect(Payment::query()->count())->toBe(3);
});

test('database seeder is safe to run twice', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(5);
    expect(Category::query()->count())->toBe(3);
    expect(Product::query()->count())->toBe(6);
    expect(Order::query()->count())->toBe(3);
    expect(Payment::query()->count())->toBe(3);
});
