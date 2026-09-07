<?php

namespace App\Policies;

use App\Models\Seller;
use App\Models\User;

class SellerPolicy
{
    /**
     * Only admins may list all seller profiles. Sellers are limited to
     * their own profile via `view`, keeping seller isolation (spec §13).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins may view any profile; sellers may view only their own.
     */
    public function view(User $user, Seller $seller): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $seller->user_id === $user->id && $user->isSeller();
    }

    /**
     * Any buyer without a seller record may request activation.
     * Duplicate activation is rejected by the controller with 403.
     */
    public function create(User $user): bool
    {
        return $user->seller()->doesntExist();
    }

    /**
     * Admins may update any profile; sellers may update only their own
     * store profile.
     */
    public function update(User $user, Seller $seller): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $seller->user_id === $user->id && $user->isSeller();
    }

    /**
     * Store removal is an admin-only operation.
     */
    public function delete(User $user, Seller $seller): bool
    {
        return $user->isAdmin();
    }

    /**
     * Store restoration is an admin-only operation.
     */
    public function restore(User $user, Seller $seller): bool
    {
        return $user->isAdmin();
    }

    /**
     * Permanent store removal is an admin-only operation.
     */
    public function forceDelete(User $user, Seller $seller): bool
    {
        return $user->isAdmin();
    }
}
