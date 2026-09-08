<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thin Inertia shells for admin store management (spec §17). Data flows
 * through the admin JSON API; the controller only gates and renders.
 */
class SellerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Sellers/Index');
    }

    public function show(Seller $seller): Response
    {
        return Inertia::render('Admin/Sellers/Show', [
            'seller' => $this->sellerPayload($seller),
        ]);
    }

    public function edit(Seller $seller): Response
    {
        return Inertia::render('Admin/Sellers/Edit', [
            'seller' => $this->sellerPayload($seller),
        ]);
    }

    /**
     * Explicit seller payload with unwrapped enums and timestamps
     * (same pattern as OrderController detail mapping).
     *
     * @return array<string, mixed>
     */
    protected function sellerPayload(Seller $seller): array
    {
        $seller->load(['user', 'products']);

        return [
            'id' => $seller->id,
            'store_name' => $seller->store_name,
            'slug' => $seller->slug,
            'status' => $seller->status instanceof \BackedEnum ? $seller->status->value : $seller->status,
            'description' => $seller->description,
            'profile_photo_url' => $seller->profile_photo_url,
            'phone' => $seller->phone,
            'whatsapp' => $seller->whatsapp,
            'store_location' => $seller->store_location,
            'bank_account' => $seller->bank_account,
            'state' => $seller->state,
            'city' => $seller->city,
            'created_at' => $seller->created_at,
            'updated_at' => $seller->updated_at,
            'owner' => $seller->user ? [
                'id' => $seller->user->id,
                'name' => $seller->user->name,
                'email' => $seller->user->email,
                'phone' => $seller->user->phone,
                'is_active_as_seller' => (bool) $seller->user->is_active_as_seller,
            ] : null,
            'products' => $seller->products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'stock' => $product->stock,
                'status' => $product->status instanceof \BackedEnum ? $product->status->value : $product->status,
            ])->values(),
        ];
    }
}
