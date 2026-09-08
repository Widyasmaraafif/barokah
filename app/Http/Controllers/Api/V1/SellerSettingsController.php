<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerResource;
use App\Models\Seller;
use App\Rules\CityInState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Own store settings for authenticated sellers (spec §13).
 *
 * Same profile fields as the admin store form, except `status` which
 * stays admin-only. Photo uploads use POST with `_method=PUT` (same
 * method-spoofing pattern as product images / admin sellers).
 */
class SellerSettingsController extends Controller
{
    /**
     * Show the current seller's own store profile.
     */
    public function show(Request $request): SellerResource
    {
        $seller = $request->user()->load('seller')->seller;

        abort_if($seller === null, 404);

        Gate::authorize('view', $seller);

        return new SellerResource($seller);
    }

    /**
     * Update the current seller's own store profile.
     */
    public function update(Request $request): SellerResource
    {
        $seller = $request->user()->load('seller')->seller;

        abort_if($seller === null, 404);

        Gate::authorize('update', $seller);

        // Multipart form data sends empty strings for cleared inputs;
        // normalize them to null so `nullable` rules apply.
        foreach (['slug', 'description', 'phone', 'whatsapp', 'store_location', 'bank_account', 'state', 'city'] as $nullableField) {
            if ($request->has($nullableField) && $request->input($nullableField) === '') {
                $request->merge([$nullableField => null]);
            }
        }

        // City validation needs the state in the same payload; fall back to
        // the stored state when only the city is being updated.
        if (! $request->has('state') && $request->filled('city') && $seller->state !== null && $seller->state !== '') {
            $request->merge(['state' => $seller->state]);
        }

        $validated = $request->validate([
            'store_name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sellers', 'store_name')->ignore($seller->id)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('sellers', 'slug')->ignore($seller->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            // Phone/whatsapp formats are TBC (spec §24 item 5); only length is enforced.
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'store_location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'bank_account' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', Rule::in(config('malaysia.states', []))],
            'city' => ['sometimes', 'nullable', 'string', 'max:100', new CityInState('state')],
            'profile_photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_photo' => ['sometimes', 'nullable', 'boolean'],
        ]);

        if (array_key_exists('slug', $validated)) {
            $validated['slug'] = $validated['slug'] === null || $validated['slug'] === ''
                ? $this->uniqueSlug($validated['store_name'] ?? $seller->store_name, $seller->id)
                : $this->uniqueSlug($validated['slug'], $seller->id);
        }

        if (array_key_exists('store_name', $validated) && ! array_key_exists('slug', $validated)) {
            $validated['slug'] = $this->uniqueSlug($validated['store_name'], $seller->id);
        }

        $seller = DB::transaction(function () use ($request, $seller, $validated) {
            $removePhoto = $request->boolean('remove_profile_photo', false);
            $photo = $request->file('profile_photo');

            unset($validated['profile_photo'], $validated['remove_profile_photo']);

            $seller->fill($validated);

            if ($photo !== null) {
                if ($seller->profile_photo_path !== null && $seller->profile_photo_path !== '') {
                    Storage::disk('public')->delete($seller->profile_photo_path);
                }

                $seller->profile_photo_path = $photo->store('sellers', 'public');
            } elseif ($removePhoto) {
                if ($seller->profile_photo_path !== null && $seller->profile_photo_path !== '') {
                    Storage::disk('public')->delete($seller->profile_photo_path);
                }

                $seller->profile_photo_path = null;
            }

            $seller->save();

            return $seller->refresh();
        });

        return new SellerResource($seller);
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base === '' ? Str::random(8) : $base;
        $candidate = $slug;
        $counter = 2;

        while (Seller::query()->where('slug', $candidate)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
