<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Order view scoped to a single seller: only that seller's items are
 * included. Order-level totals still reflect the whole checkout
 * (spec §14.3); seller revenue is derived from the included items.
 *
 * @mixin Order
 */
class SellerOrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'currency_code' => $this->currency_code,
            'customer_name' => $this->customer_name,
            'customer_address' => $this->customer_address,
            'customer_state' => $this->customer_state,
            'customer_city' => $this->customer_city,
            'customer_post_code' => $this->customer_post_code,
            'shipping_address' => $this->shipping_address,
            'shipping_state' => $this->shipping_state,
            'shipping_city' => $this->shipping_city,
            'shipping_post_code' => $this->shipping_post_code,
            'subtotal' => $this->items->sum('subtotal'),
            'tracking' => $this->sellerTrackings->first(),
            'created_at' => $this->created_at,
            'items' => SellerOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
