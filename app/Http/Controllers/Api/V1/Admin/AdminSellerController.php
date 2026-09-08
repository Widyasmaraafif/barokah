<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerResource;
use App\Models\Seller;
use App\Rules\CityInState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin seller management (spec §17): list all stores, view products
 * and orders, approve/suspend stores.
 *
 * TBC (spec §24 item 6): the approval workflow is undecided, so status
 * updates simply persist the enum without side-effect notifications.
 */
class AdminSellerController extends Controller
{
    /**
     * Paginated store list with status filter.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(SellerStatus::class)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $sellers = Seller::query()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status instanceof \BackedEnum ? $status->value : $status))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('store_name', 'like', "%{$search}%"))
            ->with('user')
            ->latest()
            ->paginate(15);

        return SellerResource::collection($sellers);
    }

    /**
     * Show one store with its owner.
     */
    public function show(Seller $seller): SellerResource
    {
        return new SellerResource($seller->load('user'));
    }

    /**
     * Update store profile (name/slug/description/contacts/location/
     * bank account/state/city/photo) plus approve, suspend, or
     * reactivate status.
     *
     * Accepts JSON or multipart form data; photo uploads use POST with
     * `_method=PUT` (same method-spoofing pattern as product images).
     */
    public function update(Request $request, Seller $seller): SellerResource
    {
        // Multipart form data sends empty strings for cleared inputs;
        // normalize them to null so `nullable` rules apply.
        foreach (['slug', 'description', 'phone', 'whatsapp', 'store_location', 'bank_account', 'state', 'city'] as $nullableField) {
            if ($request->has($nullableField) && $request->input($nullableField) === '') {
                $request->merge([$nullableField => null]);
            }
        }

        // City validation needs the state in the same payload; fall back to
        // the stored state when only the city is being updated (otherwise
        // CityInState would ask to "select a state first").
        if (! $request->has('state') && $request->filled('city') && $seller->state !== null && $seller->state !== '') {
            $request->merge(['state' => $seller->state]);
        }

        $validated = $request->validate([
            'store_name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sellers', 'store_name')->ignore($seller->id)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('sellers', 'slug')->ignore($seller->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'required', Rule::enum(SellerStatus::class)],
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

            return $seller->refresh()->load('user');
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
