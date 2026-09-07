<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class SellerOrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'seller_id' => $this->seller_id,
            'product_name' => $this->product_name_snapshot,
            'product_slug' => $this->product_slug_snapshot,
            'price' => $this->price_snapshot,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
        ];
    }
}
