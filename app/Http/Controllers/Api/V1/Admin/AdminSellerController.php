<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerResource;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin seller management (spec §17): list all stores, view products
 * and orders, approve/suspend stores.
 *
 * TBC (spec §24 item 6): the approval workflow is undecided, so status
 * updates simply persist the enum without side-effect notifications.
 */
class AdminSellerController extends Controller
{
    /**
     * Paginated store list with status filter.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(SellerStatus::class)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $sellers = Seller::query()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status instanceof \BackedEnum ? $status->value : $status))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('store_name', 'like', "%{$search}%"))
            ->with('user')
            ->latest()
            ->paginate(15);

        return SellerResource::collection($sellers);
    }

    /**
     * Show one store with its owner.
     */
    public function show(Seller $seller): SellerResource
    {
        return new SellerResource($seller->load('user'));
    }

    /**
     * Update store profile (name/slug/description) plus approve,
     * suspend, or reactivate status.
     */
    public function update(Request $request, Seller $seller): SellerResource
    {
        $validated = $request->validate([
            'store_name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sellers', 'store_name')->ignore($seller->id)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('sellers', 'slug')->ignore($seller->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'required', Rule::enum(SellerStatus::class)],
        ]);

        if (array_key_exists('slug', $validated)) {
            $validated['slug'] = $validated['slug'] === null || $validated['slug'] === ''
                ? $this->uniqueSlug($validated['store_name'] ?? $seller->store_name, $seller->id)
                : $this->uniqueSlug($validated['slug'], $seller->id);
        }

        if (array_key_exists('store_name', $validated) && ! array_key_exists('slug', $validated)) {
            $validated['slug'] = $this->uniqueSlug($validated['store_name'], $seller->id);
        }

        $seller->fill($validated)->save();

        return new SellerResource($seller->refresh()->load('user'));
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base === '' ? Str::random(8) : $base;
        $candidate = $slug;
        $counter = 2;

        while (Seller::query()->where('slug', $candidate)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
