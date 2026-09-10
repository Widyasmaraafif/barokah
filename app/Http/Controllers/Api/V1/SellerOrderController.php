<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
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
            ->whereHas('payment', fn ($query) => $query->whereIn('status', [PaymentStatus::Paid, 'verified']))
            ->with(['items' => fn ($query) => $query->where('seller_id', $sellerId)])
            ->latest()
            ->paginate(15);

        return SellerOrderResource::collection($orders);
    }

    public function update(Request $request, string $orderNumber): SellerOrderResource
    {
        $sellerId = $request->user()->seller()->value('id');
        $validated = $request->validate([
            'courier' => ['nullable', 'string', 'max:255'],
            'waybill_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
            'tracking_status' => ['required', 'in:packed,shipped'],
        ]);

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->whereHas('items', fn ($query) => $query->where('seller_id', $sellerId))
            ->whereHas('payment', fn ($query) => $query->whereIn('status', [PaymentStatus::Paid, 'verified']))
            ->firstOrFail();

        $tracking = $order->sellerTrackings()->updateOrCreate(
            ['seller_id' => $sellerId],
            $validated,
        );

        return new SellerOrderResource($order->refresh()->load([
            'items' => fn ($query) => $query->where('seller_id', $sellerId),
            'sellerTrackings' => fn ($query) => $query->whereKey($tracking->id),
        ]));
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
            ->whereHas('payment', fn ($query) => $query->whereIn('status', [PaymentStatus::Paid, 'verified']))
            ->with(['items' => fn ($query) => $query->where('seller_id', $sellerId), 'sellerTrackings' => fn ($query) => $query->where('seller_id', $sellerId)])
            ->first();

        if ($order === null || $request->user()?->cannot('view', $order)) {
            abort(404);
        }

        return new SellerOrderResource($order);
    }
}
