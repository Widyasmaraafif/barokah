<?php

use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function activeStoreOwner(): User
{
    $user = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $user->id]);

    return $user->refresh();
}

test('seller index redirects to seller dashboard', function () {
    $seller = activeStoreOwner();

    $this->actingAs($seller)->get('/seller')->assertRedirect('/seller/dashboard');
});

test('seller views own store settings page', function () {
    $seller = activeStoreOwner();

    $this->withoutVite()->actingAs($seller)->get(route('seller.settings'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Seller/Settings')
            ->where('seller.store_name', $seller->seller->store_name)
            ->where('seller.slug', $seller->seller->slug)
        );
});

test('guest and buyer cannot view seller settings', function () {
    $this->get(route('seller.settings'))->assertRedirect('/login');

    $buyer = User::factory()->create();

    $this->actingAs($buyer)->get(route('seller.settings'))->assertForbidden();
});

test('seller updates own store profile fields', function () {
    $seller = activeStoreOwner();

    $response = $this->actingAs($seller)->putJson('/api/v1/seller/settings', [
        'phone' => '03-55123456',
        'whatsapp' => '60123456789',
        'store_location' => 'No. 12, Jalan Meru, Klang',
        'bank_account' => 'Maybank a.n. Nama Pemilik Rekening 1234567890',
        'state' => 'Selangor',
        'city' => 'Klang',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.phone', '03-55123456')
        ->assertJsonPath('data.whatsapp', '60123456789')
        ->assertJsonPath('data.state', 'Selangor')
        ->assertJsonPath('data.city', 'Klang');

    expect($seller->seller->refresh())
        ->store_location->toBe('No. 12, Jalan Meru, Klang')
        ->bank_account->toBe('Maybank a.n. Nama Pemilik Rekening 1234567890');
});

test('seller settings rejects city outside state', function () {
    $seller = activeStoreOwner();

    $this->actingAs($seller)->putJson('/api/v1/seller/settings', [
        'state' => 'Selangor',
        'city' => 'Johor Bahru',
    ])->assertUnprocessable()->assertJsonValidationErrors('city');
});

test('seller can upload own store profile photo', function () {
    Storage::fake('public');
    $seller = activeStoreOwner();

    $response = $this->actingAs($seller)->post('/api/v1/seller/settings', [
        '_method' => 'PUT',
        'profile_photo' => UploadedFile::fake()->image('store.jpg'),
    ]);

    $response->assertOk();

    $path = $seller->seller->refresh()->profile_photo_path;

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('buyer cannot update seller settings', function () {
    $buyer = User::factory()->create();

    $this->actingAs($buyer)->putJson('/api/v1/seller/settings', [
        'phone' => '03-55123456',
    ])->assertForbidden();
});
