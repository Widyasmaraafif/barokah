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
            'total' => $this->total,
            'created_at' => $this->created_at,
            'items' => SellerOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
