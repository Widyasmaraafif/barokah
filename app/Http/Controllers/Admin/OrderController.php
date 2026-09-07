<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thin Inertia shells for admin order management (spec §17). Detail
 * reuses the same order + items + payment scope as AdminOrderController.
 */
class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Orders/Index');
    }

    public function show(string $orderNumber): Response
    {
        $order = Order::query()->where('order_number', $orderNumber)->with(['items', 'payment'])->firstOrFail();

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }
}
