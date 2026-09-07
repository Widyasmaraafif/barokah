<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActivateSellerRequest;
use App\Http\Resources\Api\V1\SellerResource;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerActivationController extends Controller
{
    public function __invoke(ActivateSellerRequest $request): SellerResource|JsonResponse
    {
        $user = $request->user();

        if ($user->seller()->exists()) {
            return response()->json([
                'message' => 'Buyer is already active as seller.',
            ], 403);
        }

        $validated = $request->validated();

        $seller = DB::transaction(function () use ($user, $validated) {
            /** @var Seller $seller */
            $seller = $user->seller()->create([
                // TBC (spec §24 item 6): instant activation is assumed until the
                // approval workflow decision is confirmed.
                'store_name' => $validated['store_name'],
                'slug' => $this->uniqueSlug($validated['store_name']),
                'description' => $validated['description'] ?? null,
                'status' => SellerStatus::Active,
            ]);

            $user->forceFill(['is_active_as_seller' => true])->save();

            return $seller;
        });

        return (new SellerResource($seller))->response()->setStatusCode(201);
    }

    protected function uniqueSlug(string $storeName): string
    {
        $base = Str::slug($storeName);
        $slug = $base === '' ? Str::random(8) : $base;
        $candidate = $slug;
        $counter = 2;

        while (Seller::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
