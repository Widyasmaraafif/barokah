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
            ->with('items')
            ->firstOrFail();

        return new OrderResource($order);
    }
}
