<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Admin customer/buyer management (spec §17): list/search customers,
 * view their orders, update profile fields, toggle admin capability
 * and seller activation.
 */
class AdminUserController extends Controller
{
    use ProfileValidationRules;

    /**
     * Paginated customer list with name/email search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $users = User::query()
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when(array_key_exists('is_admin', $validated), fn ($query) => $query->where('is_admin', $validated['is_admin']))
            ->with('seller')
            ->latest()
            ->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * Show one customer with their seller profile.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user->load('seller'));
    }

    /**
     * Update profile fields plus the admin capability and seller activation flag.
     */
    public function update(Request $request, User $user): UserResource
    {
        $validated = $request->validate([
            'name' => array_merge(['sometimes'], $this->nameRules()),
            'email' => array_merge(['sometimes'], $this->emailRules($user->id)),
            // Phone/postcode formats are TBC (spec §24 item 5); only length is enforced.
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'state' => ['sometimes', 'nullable', 'string', Rule::in(config('malaysia.states', []))],
            'post_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_admin' => ['sometimes', 'required', 'boolean'],
            'is_active_as_seller' => ['sometimes', 'required', 'boolean'],
        ]);

        $user->forceFill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user->refresh()->load('seller'));
    }
}
