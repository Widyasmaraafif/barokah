<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'state' => $this->state,
            'city' => $this->city,
            'post_code' => $this->post_code,
            'is_admin' => $this->isAdmin(),
            'is_active_as_seller' => (bool) $this->is_active_as_seller,
            'is_seller' => $this->isSeller(),
            'seller' => SellerResource::make($this->whenLoaded('seller')),
        ];
    }
}
