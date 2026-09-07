<?php

use App\Enums\SellerStatus;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

test('admin gate allows only admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $buyer = User::factory()->create();

    expect(Gate::forUser($admin)->allows('admin'))->toBeTrue();
    expect(Gate::forUser($buyer)->allows('admin'))->toBeFalse();
});

test('seller gate requires active seller record', function () {
    $buyer = User::factory()->create();
    $inactiveFlag = User::factory()->create();
    Seller::factory()->create(['user_id' => $inactiveFlag->id, 'status' => SellerStatus::Active]);

    $seller = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $seller->id, 'status' => SellerStatus::Active]);

    $suspended = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $suspended->id, 'status' => SellerStatus::Suspended]);

    expect(Gate::forUser($buyer)->allows('seller'))->toBeFalse();
    expect(Gate::forUser($inactiveFlag)->allows('seller'))->toBeFalse();
    expect(Gate::forUser($seller)->allows('seller'))->toBeTrue();
    expect(Gate::forUser($suspended)->allows('seller'))->toBeFalse();
});

test('can:admin middleware protects admin routes', function () {
    $buyer = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    Route::middleware(['auth', 'can:admin'])->get('/_test-admin-gate', fn () => 'ok');

    $this->get('/_test-admin-gate')->assertRedirect('/login');
    $this->actingAs($buyer)->get('/_test-admin-gate')->assertForbidden();
    $this->actingAs($admin)->get('/_test-admin-gate')->assertOk();
});

test('non-seller cannot pass can:seller middleware', function () {
    $buyer = User::factory()->create();

    Route::middleware(['auth', 'can:seller'])->get('/_test-seller-gate', fn () => 'ok');

    $this->get('/_test-seller-gate')->assertRedirect('/login');
    $this->actingAs($buyer)->get('/_test-seller-gate')->assertForbidden();

    $seller = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $seller->id, 'status' => SellerStatus::Active]);

    $this->actingAs($seller)->get('/_test-seller-gate')->assertOk();
});
