<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Buyer order history (spec §11.4/§14.3).
 *
 * Authenticated buyers see only orders linked to their user_id. Guest
 * order lookup by order_number + email/phone token is TBC (spec §12).
 */
class OrderController extends Controller
{
    /**
     * List the authenticated buyer's own orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->whereBelongsTo($request->user())
            ->with('items')
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    /**
     * Show one order by order_number when the policy allows it.
     *
     * Unowned order_numbers return 404 so buyers cannot probe other
     * customers' orders.
     */
    public function show(Request $request, string $orderNumber): OrderResource
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->with('items')
            ->first();

        if ($order === null || $request->user()?->cannot('view', $order)) {
            abort(404);
        }

        return new OrderResource($order);
    }
}
