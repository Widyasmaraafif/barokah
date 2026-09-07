<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerOrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SellerOrderController extends Controller
{
    /**
     * List orders containing items owned by the current seller.
     *
     * Scoped via order_items.seller_id (spec §14.3); only the current
     * seller's items are serialized.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $sellerId = $request->user()->seller()->value('id');

        $orders = Order::query()
            ->whereHas('items', fn ($query) => $query->where('seller_id', $sellerId))
            ->with(['items' => fn ($query) => $query->where('seller_id', $sellerId)])
            ->latest()
            ->paginate(15);

        return SellerOrderResource::collection($orders);
    }

    /**
     * Show one order scoped to the current seller's items.
     *
     * Orders without the seller's items return 404 so sellers cannot
     * probe other sellers' orders.
     */
    public function show(Request $request, string $orderNumber): SellerOrderResource
    {
        $sellerId = $request->user()->seller()->value('id');

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->whereHas('items', fn ($query) => $query->where('seller_id', $sellerId))
            ->with(['items' => fn ($query) => $query->where('seller_id', $sellerId)])
            ->first();

        if ($order === null || $request->user()?->cannot('view', $order)) {
            abort(404);
        }

        return new SellerOrderResource($order);
    }
}
