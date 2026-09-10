<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer order with buyer snapshot, totals, and items (spec §10.2/§14.1).
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
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
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'shipping_address' => $this->shipping_address,
            'shipping_state' => $this->shipping_state,
            'shipping_city' => $this->shipping_city,
            'shipping_post_code' => $this->shipping_post_code,
            'subtotal' => $this->subtotal,
            'shipping_fee' => $this->shipping_fee,
            'total' => $this->total,
            'shipping_method' => $this->shipping_method,
            'shipping_provider' => $this->shipping_provider,
            'courier' => $this->courier,
            'waybill_number' => $this->waybill_number,
            'tracking_url' => $this->tracking_url,
            'tracking_status' => $this->tracking_status,
            'expired_at' => $this->expired_at,
            'created_at' => $this->created_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
