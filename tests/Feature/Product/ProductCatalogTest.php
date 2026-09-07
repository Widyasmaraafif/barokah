<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createProductSeller(): User
{
    $user = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $user->id]);

    return $user->refresh();
}

function validProductPayload(Category $category, array $overrides = []): array
{
    return array_merge([
        'category_id' => $category->id,
        'name' => 'Keripik Pisang Original',
        'description' => 'Crispy banana chips.',
        'price' => '25.50',
        'stock' => 10,
        'status' => ProductStatus::Active->value,
    ], $overrides);
}

test('guest cannot create seller products', function () {
    $category = Category::factory()->create();

    $this->postJson('/api/v1/seller/products', validProductPayload($category))
        ->assertUnauthorized();
});

test('buyer without seller capability cannot create seller products', function () {
    $buyer = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($buyer)->postJson('/api/v1/seller/products', validProductPayload($category))
        ->assertForbidden();
});

test('seller can create a product with unique slug', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $category = Category::factory()->create();

    $response = $this->actingAs($seller)->postJson(
        '/api/v1/seller/products',
        validProductPayload($category)
    );

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Keripik Pisang Original')
        ->assertJsonPath('data.slug', 'keripik-pisang-original');

    expect(Product::query()->where('seller_id', $seller->seller->id)->count())->toBe(1);
});

test('seller product creation auto-increments duplicate slugs', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $category = Category::factory()->create();

    $this->actingAs($seller)->postJson('/api/v1/seller/products', validProductPayload($category))
        ->assertCreated();

    $response = $this->actingAs($seller)->postJson(
        '/api/v1/seller/products',
        validProductPayload($category)
    );

    $response->assertCreated()->assertJsonPath('data.slug', 'keripik-pisang-original-2');
});

test('seller product creation validates required fields', function () {
    Storage::fake('public');
    $seller = createProductSeller();

    $this->actingAs($seller)->postJson('/api/v1/seller/products', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id', 'name', 'price', 'stock', 'status']);
});

test('seller product creation rejects negative price and stock', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $category = Category::factory()->create();

    $this->actingAs($seller)->postJson(
        '/api/v1/seller/products',
        validProductPayload($category, ['price' => -5, 'stock' => -1])
    )->assertUnprocessable()->assertJsonValidationErrors(['price', 'stock']);
});

test('seller product creation stores uploaded images with primary flag', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $category = Category::factory()->create();

    $response = $this->actingAs($seller)->postJson('/api/v1/seller/products', array_merge(
        validProductPayload($category),
        [
            'images' => [
                UploadedFile::fake()->image('chips-1.jpg'),
                UploadedFile::fake()->image('chips-2.png'),
            ],
        ]
    ));

    $response->assertCreated()->assertJsonCount(2, 'data.images');

    $product = Product::query()->first();
    expect($product->images)->toHaveCount(2);
    expect($product->images->firstWhere('is_primary', true))->not->toBeNull();
    Storage::disk('public')->assertExists($product->images->first()->path);
});

test('seller product creation rejects invalid image mime', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $category = Category::factory()->create();

    $this->actingAs($seller)->postJson('/api/v1/seller/products', array_merge(
        validProductPayload($category),
        ['images' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')]]
    ))->assertUnprocessable()->assertJsonValidationErrors(['images.0']);
});

test('seller cannot update another sellers product', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $other = createProductSeller();
    $product = Product::factory()->create(['seller_id' => $other->seller->id]);

    $this->actingAs($seller)->putJson("/api/v1/seller/products/{$product->id}", [
        'name' => 'Hijacked Name',
    ])->assertForbidden();

    expect($product->refresh()->name)->not->toBe('Hijacked Name');
});

test('seller can update own product and rename slug', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $product = Product::factory()->create(['seller_id' => $seller->seller->id]);

    $response = $this->actingAs($seller)->putJson("/api/v1/seller/products/{$product->id}", [
        'name' => 'Hijab Premium Silk',
        'price' => '59.90',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.slug', 'hijab-premium-silk')
        ->assertJsonPath('data.price', '59.90');
});

test('seller can remove images on update', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $product = Product::factory()->create(['seller_id' => $seller->seller->id]);
    $image = ProductImage::factory()->create(['product_id' => $product->id]);
    Storage::disk('public')->put($image->path, 'fake-content');

    $this->actingAs($seller)->putJson("/api/v1/seller/products/{$product->id}", [
        'remove_image_ids' => [$image->id],
    ])->assertOk();

    expect(ProductImage::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($image->path);
});

test('seller cannot delete another sellers product', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $other = createProductSeller();
    $product = Product::factory()->create(['seller_id' => $other->seller->id]);

    $this->actingAs($seller)->deleteJson("/api/v1/seller/products/{$product->id}")
        ->assertForbidden();

    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});

test('seller can delete own product and its image files', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $product = Product::factory()->create(['seller_id' => $seller->seller->id]);
    $image = ProductImage::factory()->create(['product_id' => $product->id]);
    Storage::disk('public')->put($image->path, 'fake-content');

    $this->actingAs($seller)->deleteJson("/api/v1/seller/products/{$product->id}")
        ->assertNoContent();

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($image->path);
});

test('seller product listing is scoped to own products', function () {
    Storage::fake('public');
    $seller = createProductSeller();
    $other = createProductSeller();

    Product::factory()->count(2)->create(['seller_id' => $seller->seller->id]);
    Product::factory()->create(['seller_id' => $other->seller->id]);

    $this->actingAs($seller)->getJson('/api/v1/seller/products')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('public product listing shows only active products', function () {
    $seller = createProductSeller();
    $category = Category::factory()->create();
    Product::factory()->create([
        'seller_id' => $seller->seller->id,
        'category_id' => $category->id,
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'seller_id' => $seller->seller->id,
        'category_id' => $category->id,
        'status' => ProductStatus::Draft,
    ]);

    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('public product listing filters by category search and sort', function () {
    $seller = createProductSeller();
    $category = Category::factory()->create(['slug' => 'keripik-1']);
    $otherCategory = Category::factory()->create(['slug' => 'hijab-1']);

    Product::factory()->create([
        'seller_id' => $seller->seller->id,
        'category_id' => $category->id,
        'name' => 'Keripik Pisang Manis',
        'price' => '30.00',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'seller_id' => $seller->seller->id,
        'category_id' => $otherCategory->id,
        'name' => 'Hijab Silk Premium',
        'price' => '10.00',
        'status' => ProductStatus::Active,
    ]);

    $this->getJson('/api/v1/products?category=keripik-1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Keripik Pisang Manis']);

    $this->getJson('/api/v1/products?search=Silk')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Hijab Silk Premium']);

    $response = $this->getJson('/api/v1/products?sort=price_asc')->assertOk();
    expect((float) $response->json('data.0.price'))->toBeLessThan((float) $response->json('data.1.price'));
});

test('public product detail resolves by slug with relations', function () {
    $seller = createProductSeller();
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'seller_id' => $seller->seller->id,
        'category_id' => $category->id,
        'status' => ProductStatus::Active,
    ]);
    ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => true]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonPath('data.category.slug', $category->slug)
        ->assertJsonPath('data.seller.store_name', $seller->seller->store_name)
        ->assertJsonCount(1, 'data.images');
});

test('public product detail returns 404 for draft products', function () {
    $product = Product::factory()->create(['status' => ProductStatus::Draft]);

    $this->getJson("/api/v1/products/{$product->slug}")->assertNotFound();
});

test('public categories listing returns active categories', function () {
    Category::factory()->create(['is_active' => true]);
    Category::factory()->create(['is_active' => false]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('product policy isolates sellers and allows admin', function () {
    $seller = createProductSeller();
    $other = createProductSeller();
    $admin = User::factory()->create(['is_admin' => true]);
    $buyer = User::factory()->create();

    $product = Product::factory()->create(['seller_id' => $seller->seller->id]);

    expect($seller->can('view', $product))->toBeTrue();
    expect($seller->can('update', $product))->toBeTrue();
    expect($seller->can('delete', $product))->toBeTrue();
    expect($other->can('view', $product))->toBeFalse();
    expect($other->can('update', $product))->toBeFalse();
    expect($other->can('delete', $product))->toBeFalse();
    expect($admin->can('view', $product))->toBeTrue();
    expect($admin->can('update', $product))->toBeTrue();
    expect($buyer->can('view', $product))->toBeFalse();
});

test('product listing page renders active products', function () {
    $seller = createProductSeller();
    $product = Product::factory()->create([
        'seller_id' => $seller->seller->id,
        'status' => ProductStatus::Active,
    ]);

    $this->withoutVite()->get(route('products.index'))->assertOk()->assertSee($product->name);
});

test('product detail page renders and 404s for drafts', function () {
    $active = Product::factory()->create(['status' => ProductStatus::Active]);
    $draft = Product::factory()->create(['status' => ProductStatus::Draft]);

    $this->withoutVite()->get(route('products.show', $active->slug))->assertOk()->assertSee($active->name);
    $this->get(route('products.show', $draft->slug))->assertNotFound();
});
