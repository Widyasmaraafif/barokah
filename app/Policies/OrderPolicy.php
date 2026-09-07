<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Buyers list only their own orders; sellers/admins are scoped in
     * their controllers (spec §14.3).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Buyer owns the order (user_id), seller owns at least one item
     * (order_items.seller_id), admin sees all (spec §8.5/§14.3).
     *
     * Guest order access via order_number + email/phone token is TBC
     * (spec §12/§24); unowned orders return 404, never 403, so guests
     * cannot probe other orders.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($order->user_id !== null && $order->user_id === $user->id) {
            return true;
        }

        $sellerId = $user->seller()->value('id');

        if ($sellerId === null) {
            return false;
        }

        return $order->items()->where('seller_id', $sellerId)->exists();
    }

    /**
     * Order status updates are admin-only in Task 7. Payment-driven
     * transitions (paid/processing) land in Task 8 with PaymentService.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
