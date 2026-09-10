<?php

namespace App\Http\Controllers\Web;

use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\SellerResource;
use App\Models\Seller;
use Inertia\Inertia;
use Inertia\Response;

class SellerShowController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $seller = Seller::query()
            ->where('status', SellerStatus::Active)
            ->where('slug', $slug)
            ->with(['products' => fn ($query) => $query->active()->with(['images', 'category'])])
            ->firstOrFail();

        return Inertia::render('Seller/Show', [
            'seller' => new SellerResource($seller),
            'products' => ProductResource::collection($seller->products),
        ]);
    }
}
