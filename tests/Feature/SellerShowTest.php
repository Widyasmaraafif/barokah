<?php

use App\Enums\SellerStatus;
use App\Models\Seller;
use Inertia\Testing\AssertableInertia as Assert;

it('shows active seller with active products', function () {
    $seller = Seller::factory()->create(['status' => SellerStatus::Active]);
    $seller->products()->createMany([
        ['category_id' => 1, 'name' => 'Active Product', 'slug' => 'active-product', 'price' => 10, 'stock' => 1, 'weight_grams' => 100, 'status' => 'active'],
        ['category_id' => 1, 'name' => 'Draft Product', 'slug' => 'draft-product', 'price' => 10, 'stock' => 1, 'weight_grams' => 100, 'status' => 'draft'],
    ]);

    $this->get(route('sellers.show', $seller->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Seller/Show')
            ->where('seller.data.id', $seller->id)
            ->has('products.data', 1));
});

it('does not show inactive seller', function () {
    $seller = Seller::factory()->create(['status' => SellerStatus::Pending]);

    $this->get(route('sellers.show', $seller->slug))->assertNotFound();
});
