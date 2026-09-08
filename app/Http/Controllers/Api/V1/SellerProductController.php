<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seller-scoped product CRUD (spec §11.3). Every query is scoped to the
 * current seller; ownership is additionally enforced by ProductPolicy.
 */
class SellerProductController extends Controller
{
    /**
     * List the current seller's products.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Product::class);

        $products = Product::query()
            ->whereBelongsTo(request()->user()->seller)
            ->with(['category', 'images'])
            ->when(request()->string('status')->toString() !== '', function ($query, $status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return ProductResource::collection($products);
    }

    /**
     * Create a product owned by the current seller.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($request, $validated) {
            /** @var Product $product */
            $product = $request->user()->seller->products()->create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['slug'] ?? $validated['name']),
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'stock' => $validated['stock'],
                'weight_grams' => $validated['weight_grams'] ?? 0,
                'status' => $validated['status'],
            ]);

            $this->storeImages($product, $request->file('images', []));

            return $product->load(['seller', 'category', 'images']);
        });

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    /**
     * Show a single product owned by the current seller.
     */
    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product->load(['seller', 'category', 'images']));
    }

    /**
     * Update a product owned by the current seller.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($request, $product, $validated) {
            if (array_key_exists('slug', $validated)) {
                $validated['slug'] = $validated['slug'] === null || $validated['slug'] === ''
                    ? $this->uniqueSlug($validated['name'] ?? $product->name, $product->id)
                    : $this->uniqueSlug($validated['slug'], $product->id);
            }

            $product->fill($validated);

            if (array_key_exists('name', $validated) && ! array_key_exists('slug', $validated)) {
                $product->slug = $this->uniqueSlug($validated['name'], $product->id);
            }

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
     * Delete a product owned by the current seller.
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
