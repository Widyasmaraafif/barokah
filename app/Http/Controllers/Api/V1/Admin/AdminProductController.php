<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Admin product management (spec §17): CRUD all products, status
 * moderation, category assignment.
 *
 * Sellers manage their own products via SellerProductController; admins
 * may moderate any product here (ProductPolicy grants admin override).
 */
class AdminProductController extends Controller
{
    /**
     * Paginated product list with status/seller/category filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'seller_id' => ['nullable', 'integer', Rule::exists('sellers', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $products = Product::query()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status instanceof \BackedEnum ? $status->value : $status))
            ->when($validated['seller_id'] ?? null, fn ($query, $sellerId) => $query->where('seller_id', $sellerId))
            ->when($validated['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->with(['seller', 'category', 'images'])
            ->latest()
            ->paginate(15);

        return ProductResource::collection($products);
    }

    /**
     * Show any product with relations.
     */
    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product->load(['seller', 'category', 'images']));
    }

    /**
     * Moderate a product: status and category assignment.
     */
    public function update(Request $request, Product $product): ProductResource
    {
        Gate::authorize('update', $product);

        $validated = $request->validate([
            'status' => ['sometimes', 'required', Rule::enum(ProductStatus::class)],
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')],
        ]);

        $product->fill($validated)->save();

        return new ProductResource($product->refresh()->load(['seller', 'category', 'images']));
    }

    /**
     * Remove any product (cascades images via model events).
     */
    public function destroy(Product $product): Response
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }
}
