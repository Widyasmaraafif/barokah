<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Seller;
use App\Services\CurrencyFormatter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin dashboard with marketplace metrics (spec §17): orders count,
 * revenue (MYR), pending payments, low stock, seller count, recent
 * orders.
 */
class DashboardController extends Controller
{
    public function __invoke(CurrencyFormatter $currency): Response
    {
        $paidStatuses = [
            OrderStatus::Paid,
            OrderStatus::Processing,
            OrderStatus::Shipped,
            OrderStatus::Completed,
        ];

        $revenue = (string) Order::query()->whereIn('status', $paidStatuses)->sum('total');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'orders_count' => Order::query()->count(),
                'revenue' => $revenue,
                'revenue_formatted' => $currency->format((float) $revenue),
                'currency_code' => $currency->code(),
                'pending_payments' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
                'pending_orders' => Order::query()->pendingPayment()->count(),
                'low_stock_count' => Product::query()->where('stock', '<=', 5)->count(),
                'seller_count' => Seller::query()->count(),
                'product_count' => Product::query()->count(),
            ],
            'recent_orders' => Order::query()->with('items')->latest()->limit(10)->get(),
        ]);
    }
}
