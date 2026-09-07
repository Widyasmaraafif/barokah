<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Expire checkout orders past expired_at (spec §14.5).
 *
 * Only pending_payment orders transition to expired; reserved stock is
 * restored per item and the pending payment row (if any) is marked
 * expired alongside the order. TBC (spec §24 item 19): no notification
 * is sent until the channel is confirmed.
 */
class ExpirePendingOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Expire all overdue pending orders and restore reserved stock.
     */
    public function handle(): int
    {
        $expired = 0;

        Order::query()
            ->pendingPayment()
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->with('items')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$expired): void {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, &$expired): void {
                        /** @var Order|null $locked */
                        $locked = Order::query()
                            ->whereKey($order->id)
                            ->where('status', OrderStatus::PendingPayment)
                            ->lockForUpdate()
                            ->first();

                        if ($locked === null || ! $locked->isExpired()) {
                            return;
                        }

                        foreach ($locked->items as $item) {
                            if ($item->product_id !== null) {
                                Product::query()
                                    ->whereKey($item->product_id)
                                    ->increment('stock', $item->quantity);
                            }
                        }

                        $locked->update(['status' => OrderStatus::Expired]);
                        $locked->payment()->where('status', PaymentStatus::Pending)->update(['status' => PaymentStatus::Expired]);
                        $expired++;
                    });
                }
            });

        return $expired;
    }
}
