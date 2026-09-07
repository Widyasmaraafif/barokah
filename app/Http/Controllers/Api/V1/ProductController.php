<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public product browsing (spec §11.2). Only active products are listed;
 * detail also resolves only active products by slug.
 */
class ProductController extends Controller
{
    /**
     * Paginated active products with optional category/search/sort filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $sort = $request->string('sort')->toString();
        $direction = in_array($sort, ['price_asc', 'price_desc', 'latest', 'name'], true) ? $sort : 'latest';

        $products = Product::query()
            ->active()
            ->with(['seller', 'category', 'images'])
            ->when($request->string('category')->toString() !== '', function ($query) use ($request): void {
                $query->whereHas('category', fn ($category) => $category->where('slug', $request->string('category')->toString()));
            })
            ->when($request->string('search')->toString() !== '', function ($query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(fn ($nested) => $nested->where('name', 'like', $search)->orWhere('description', 'like', $search));
            })
            ->when($direction === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when($direction === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->when($direction === 'name', fn ($query) => $query->orderBy('name'))
            ->when($direction === 'latest', fn ($query) => $query->latest())
            ->paginate(15)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    /**
     * Show a single active product by slug.
     */
    public function show(string $slug): ProductResource
    {
        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->with(['seller', 'category', 'images'])
            ->firstOrFail();

        return new ProductResource($product);
    }
}
