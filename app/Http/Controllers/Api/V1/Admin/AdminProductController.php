<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * Update any product: all fields + image management.
     *
     * Follows the same image handling pattern as SellerProductController
     * so admins can fully manage any product.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($request, $product, $validated) {
            if (array_key_exists('name', $validated)) {
                $product->slug = $this->uniqueSlug($validated['name'], $product->id);
            }

            $product->fill($validated);
            $product->save();

            if (! empty($validated['remove_image_ids'])) {
                $images = $product->images()->whereIn('id', $validated['remove_image_ids'])->get();

                foreach ($images as $image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                }
            }

            $this->storeImages($product->refresh(), $request->file('images', []));

            return $product->load(['seller', 'category', 'images']);
        });

        return new ProductResource($product);
    }

    /**
     * Remove any product and its image files.
     */
    public function destroy(Product $product): Response
    {
        Gate::authorize('delete', $product);

        DB::transaction(function () use ($product): void {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            $product->delete();
        });

        return response()->noContent();
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base === '' ? Str::random(8) : $base;
        $candidate = $slug;
        $counter = 2;

        while (Product::query()->where('slug', $candidate)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    protected function storeImages(Product $product, array $files): void
    {
        if ($files === []) {
            return;
        }

        $startOrder = (int) $product->images()->max('sort_order') + 1;
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach (array_values($files) as $index => $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'sort_order' => $startOrder + $index,
                'is_primary' => ! $hasPrimary && $index === 0,
            ]);
        }
    }
}
