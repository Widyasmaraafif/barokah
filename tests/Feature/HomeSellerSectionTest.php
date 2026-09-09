<?php

use App\Enums\SellerStatus;
use App\Models\Seller;
use Inertia\Testing\AssertableInertia as Assert;

it('shares active sellers with homepage and excludes inactive sellers', function () {
    $active = Seller::factory()->create(['status' => SellerStatus::Active]);
    Seller::factory()->create(['status' => SellerStatus::Pending]);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('sellers.data', 1)
            ->where('sellers.data.0.id', $active->id)
        );
});
