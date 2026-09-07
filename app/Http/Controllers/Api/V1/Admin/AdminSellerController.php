<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerResource;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
     * Approve, suspend, or reactivate a store.
     */
    public function update(Request $request, Seller $seller): SellerResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(SellerStatus::class)],
        ]);

        $seller->update(['status' => $validated['status']]);

        return new SellerResource($seller->refresh()->load('user'));
    }
}
