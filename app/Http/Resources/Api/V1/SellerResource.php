<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Seller */
class SellerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'profile_photo_url' => $this->profile_photo_url,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'store_location' => $this->store_location,
            'bank_account' => $this->bank_account,
            'state' => $this->state,
            'city' => $this->city,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
        ];
    }
}
