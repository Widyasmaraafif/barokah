<?php

use App\Models\ShippingRate;
use App\Models\User;

function shippingRateAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('admin can add shipping rates repeatedly', function () {
    $admin = shippingRateAdmin();

    $this->actingAs($admin)->postJson('/api/v1/admin/shipping-rates', [
        'from_state' => 'Kedah',
        'from_city' => 'Alor Setar',
        'to_state' => 'Selangor',
        'to_city' => 'Shah Alam',
        'rate' => 12.50,
    ])->assertCreated();

    $this->actingAs($admin)->postJson('/api/v1/admin/shipping-rates', [
        'from_state' => null,
        'from_city' => null,
        'to_state' => 'Kedah',
        'to_city' => null,
        'rate' => 9,
    ])->assertCreated();

    expect(ShippingRate::query()->count())->toBe(2);
});

test('shipping rate validates city for selected state', function () {
    $admin = shippingRateAdmin();

    $this->actingAs($admin)->postJson('/api/v1/admin/shipping-rates', [
        'from_state' => 'Kedah',
        'from_city' => null,
        'to_state' => 'Selangor',
        'to_city' => 'Johor Bahru',
        'rate' => 10,
    ])->assertUnprocessable()->assertJsonValidationErrors('to_city');
});

test('buyers cannot manage shipping rates', function () {
    $buyer = User::factory()->create();

    $this->actingAs($buyer)->postJson('/api/v1/admin/shipping-rates', [
        'from_state' => null,
        'from_city' => null,
        'to_state' => 'Selangor',
        'to_city' => 'Shah Alam',
        'rate' => 10,
    ])->assertForbidden();
});
