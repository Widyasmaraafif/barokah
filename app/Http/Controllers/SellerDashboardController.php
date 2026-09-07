<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerDashboardController extends Controller
{
    /**
     * Seller dashboard with seller-scoped order aggregates.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load('seller');
        $sellerId = $user->seller?->id;

        $items = $sellerId === null
            ? OrderItem::query()->whereRaw('1 = 0')
            : OrderItem::query()->where('seller_id', $sellerId);

        // TBC (spec §13/§14.3, tasks SubTask 3.1): dashboard shows order
        // counts and item revenue only. Product metrics land in Task 4 once
        // the products table exists.
        return Inertia::render('Seller/Dashboard', [
            'seller' => $user->seller,
            'stats' => [
                'orders_count' => (clone $items)->distinct()->count('order_id'),
                'items_count' => (clone $items)->count(),
                'revenue' => (string) (clone $items)->sum('subtotal'),
            ],
        ]);
    }
}
