<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ShippingRateRequest;
use App\Models\ShippingRate;
use Illuminate\Http\JsonResponse;

class AdminShippingRateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => ShippingRate::query()
                ->orderBy('to_state')
                ->orderByRaw('to_city is null desc')
                ->orderBy('to_city')
                ->get(),
        ]);
    }

    public function store(ShippingRateRequest $request): JsonResponse
    {
        $rate = ShippingRate::query()->create($request->validated());

        return response()->json(['data' => $rate], 201);
    }

    public function update(ShippingRateRequest $request, ShippingRate $shippingRate): JsonResponse
    {
        $shippingRate->update($request->validated());

        return response()->json(['data' => $shippingRate->refresh()]);
    }

    public function destroy(ShippingRate $shippingRate): JsonResponse
    {
        $shippingRate->delete();

        return response()->json(status: 204);
    }
}
