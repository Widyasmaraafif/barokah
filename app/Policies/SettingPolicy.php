<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    /**
     * Only admins may list settings. Public values are served via the
     * public endpoint (spec §11.8) without authentication.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins may view a single setting entry.
     */
    public function view(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins may create setting entries.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins may update settings (spec §17).
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    /**
     * Setting removal is an admin-only operation.
     */
    public function delete(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    /**
     * Setting restoration is an admin-only operation.
     */
    public function restore(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    /**
     * Permanent setting removal is an admin-only operation.
     */
    public function forceDelete(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }
}
