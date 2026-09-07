<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Marketplace homepage (spec §18.3/§18.7).
 *
 * All slices come from active products only. Flash-sale and best-seller
 * sections are UI previews reusing the latest actives — promo/ranking
 * backends are TBC (spec §24 items 21-22) and no discount is applied.
 */
class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(20)
            ->get();

        $latest = Product::query()
            ->active()
            ->with(['seller', 'category', 'images'])
            ->latest()
            ->limit(24)
            ->get();

        return Inertia::render('Home', [
            'categories' => CategoryResource::collection($categories),
            'latestProducts' => ProductResource::collection($latest),
            'flashSaleProducts' => ProductResource::collection($latest->take(8)->values()),
            'bestSellerProducts' => ProductResource::collection($latest->take(8)->values()),
        ]);
    }
}
