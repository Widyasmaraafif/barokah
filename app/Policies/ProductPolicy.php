<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Seller product listing is scoped in the controller; viewing the
     * index requires only the seller capability.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSeller() || $user->isAdmin();
    }

    /**
     * Sellers view only their own products; admins view any.
     */
    public function view(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $product->seller_id === $user->seller?->id && $user->isSeller();
    }

    /**
     * Product creation requires the seller capability.
     */
    public function create(User $user): bool
    {
        return $user->isSeller() || $user->isAdmin();
    }

    /**
     * Sellers update only their own products; admins update any.
     */
    public function update(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $product->seller_id === $user->seller?->id && $user->isSeller();
    }

    /**
     * Sellers delete only their own products; admins delete any.
     */
    public function delete(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $product->seller_id === $user->seller?->id && $user->isSeller();
    }

    /**
     * Product restoration is an admin-only operation.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    /**
     * Permanent product removal is an admin-only operation.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
