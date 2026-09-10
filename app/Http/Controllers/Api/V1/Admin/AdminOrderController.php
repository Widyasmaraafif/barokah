<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Admin order management (spec §11.4/§17): list/filter all orders and
 * view any order detail with items. Status mutation lands with
 * PaymentService in Task 8; Task 7 is read-only for admins.
 */
class AdminOrderController extends Controller
{
    /**
     * Paginated admin order list with status filter.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
        ]);

        $orders = Order::query()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with('items')
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    /**
     * Admin detail view for any order by order_number.
     */
    public function show(string $orderNumber): OrderResource
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->with(['items', 'sellerTrackings'])
            ->firstOrFail();

        return new OrderResource($order);
    }

    public function update(Request $request, string $orderNumber): OrderResource
    {
        $validated = $request->validate([
            'courier' => ['nullable', 'string', 'max:255'],
            'waybill_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
            'tracking_status' => ['nullable', 'string', 'max:50'],
        ]);

        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();
        $sellerId = $validated['seller_id'];
        unset($validated['seller_id']);
        abort_unless($order->items()->where('seller_id', $sellerId)->exists(), 422);
        OrderSellerTracking::updateOrCreate(['order_id' => $order->id, 'seller_id' => $sellerId], $validated);

        return new OrderResource($order->refresh()->load('sellerTrackings'));
    }
}
