<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Category browsing is public; listing for management is admin-only.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins may view any category.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    /**
     * Category creation is admin-only (spec §13).
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Category updates are admin-only (spec §13).
     */
    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    /**
     * Category removal is an admin-only operation.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    /**
     * Category restoration is an admin-only operation.
     */
    public function restore(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    /**
     * Permanent category removal is an admin-only operation.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
