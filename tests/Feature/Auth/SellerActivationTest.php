<?php

use App\Enums\SellerStatus;
use App\Models\Seller;
use App\Models\User;

test('guest cannot activate as seller', function () {
    $this->postJson('/api/v1/seller/activate', [
        'store_name' => 'Barokah Store',
    ])->assertUnauthorized();
});

test('authenticated buyer can activate as seller', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/seller/activate', [
        'store_name' => 'Barokah Store',
        'description' => 'Keripik and hijab store.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.store_name', 'Barokah Store')
        ->assertJsonPath('data.status', SellerStatus::Active->value);

    expect($user->refresh()->is_active_as_seller)->toBeTrue();
    expect(Seller::query()->where('user_id', $user->id)->exists())->toBeTrue();
    expect($user->refresh()->isSeller())->toBeTrue();
});

test('seller activation requires a unique store name', function () {
    $existing = Seller::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/seller/activate', [
        'store_name' => $existing->store_name,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('store_name');

    expect($user->refresh()->is_active_as_seller)->toBeFalse();
});

test('buyer already active as seller receives forbidden on repeat activation', function () {
    $user = User::factory()->create();
    Seller::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['is_active_as_seller' => true])->save();

    $this->actingAs($user)->postJson('/api/v1/seller/activate', [
        'store_name' => 'Another Store',
    ])->assertForbidden();
});
