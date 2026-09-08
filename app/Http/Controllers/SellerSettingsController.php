<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerSettingsController extends Controller
{
    /**
     * Own store settings page for authenticated sellers (spec §13).
     */
    public function __invoke(Request $request): Response
    {
        $seller = $request->user()->load('seller')->seller;

        abort_if($seller === null, 404);

        return Inertia::render('Seller/Settings', [
            'seller' => [
                'id' => $seller->id,
                'store_name' => $seller->store_name,
                'slug' => $seller->slug,
                'status' => $seller->status instanceof \BackedEnum ? $seller->status->value : $seller->status,
                'description' => $seller->description,
                'profile_photo_url' => $seller->profile_photo_url,
                'phone' => $seller->phone,
                'whatsapp' => $seller->whatsapp,
                'store_location' => $seller->store_location,
                'bank_account' => $seller->bank_account,
                'state' => $seller->state,
                'city' => $seller->city,
            ],
        ]);
    }
}
