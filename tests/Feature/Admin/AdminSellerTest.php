<?php

use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function adminSellerEditor(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('admin views seller detail with owner and products', function () {
    $admin = adminSellerEditor();
    $owner = User::factory()->create(['is_active_as_seller' => true]);
    $seller = Seller::factory()->create(['user_id' => $owner->id]);
    $product = Product::factory()->create(['seller_id' => $seller->id]);

    $this->withoutVite()->actingAs($admin)->get(route('admin.sellers.show', $seller->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sellers/Show')
            ->where('seller.store_name', $seller->store_name)
            ->where('seller.slug', $seller->slug)
            ->where('seller.owner.email', $owner->email)
            ->has('seller.products', 1)
            ->where('seller.products.0.name', $product->name)
        );
});

test('admin views seller edit form', function () {
    $admin = adminSellerEditor();
    $seller = Seller::factory()->create();

    $this->withoutVite()->actingAs($admin)->get(route('admin.sellers.edit', $seller->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sellers/Edit')
            ->where('seller.store_name', $seller->store_name)
        );
});

test('guests and buyers cannot view admin seller detail', function () {
    $seller = Seller::factory()->create();

    $this->get(route('admin.sellers.show', $seller->id))->assertRedirect('/login');

    $buyer = User::factory()->create();

    $this->actingAs($buyer)->get(route('admin.sellers.show', $seller->id))->assertForbidden();
});

test('admin can update seller profile fields', function () {
    $admin = adminSellerEditor();
    $seller = Seller::factory()->create();

    $response = $this->actingAs($admin)->putJson("/api/v1/admin/sellers/{$seller->id}", [
        'store_name' => 'Kedai Baharu',
        'description' => 'Penerangan baharu.',
        'status' => 'suspended',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.store_name', 'Kedai Baharu')
        ->assertJsonPath('data.slug', 'kedai-baharu')
        ->assertJsonPath('data.description', 'Penerangan baharu.');

    expect($seller->refresh())
        ->store_name->toBe('Kedai Baharu')
        ->slug->toBe('kedai-baharu');
});

test('admin can update seller contact location bank and city fields', function () {
    $admin = adminSellerEditor();
    $seller = Seller::factory()->create();

    $response = $this->actingAs($admin)->putJson("/api/v1/admin/sellers/{$seller->id}", [
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
        ->assertJsonPath('data.bank_account', 'Maybank a.n. Nama Pemilik Rekening 1234567890')
        ->assertJsonPath('data.state', 'Selangor')
        ->assertJsonPath('data.city', 'Klang');

    expect($seller->refresh())
        ->store_location->toBe('No. 12, Jalan Meru, Klang')
        ->bank_account->toBe('Maybank a.n. Nama Pemilik Rekening 1234567890');
});

test('admin seller update rejects city outside state', function () {
    $admin = adminSellerEditor();
    $seller = Seller::factory()->create();

    $this->actingAs($admin)->putJson("/api/v1/admin/sellers/{$seller->id}", [
        'state' => 'Selangor',
        'city' => 'Johor Bahru',
    ])->assertUnprocessable()->assertJsonValidationErrors('city');
});

test('admin can upload and remove seller profile photo', function () {
    Storage::fake('public');
    $admin = adminSellerEditor();
    $seller = Seller::factory()->create();

    $response = $this->actingAs($admin)->post("/api/v1/admin/sellers/{$seller->id}", [
        '_method' => 'PUT',
        'profile_photo' => UploadedFile::fake()->image('store.jpg'),
    ]);

    $response->assertOk();
    $path = $seller->refresh()->profile_photo_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    $this->actingAs($admin)->putJson("/api/v1/admin/sellers/{$seller->id}", [
        'remove_profile_photo' => true,
    ])->assertOk();

    expect($seller->refresh()->profile_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('admin seller update rejects duplicate store name', function () {
    $admin = adminSellerEditor();
    $seller = Seller::factory()->create();
    $other = Seller::factory()->create();

    $this->actingAs($admin)->putJson("/api/v1/admin/sellers/{$seller->id}", [
        'store_name' => $other->store_name,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('store_name');
});

test('buyers cannot update sellers', function () {
    $buyer = User::factory()->create();
    $seller = Seller::factory()->create();

    $this->actingAs($buyer)->putJson("/api/v1/admin/sellers/{$seller->id}", [
        'status' => 'active',
    ])->assertForbidden();
});
