<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'seller_id', 'shipping_fee', 'shipping_provider', 'courier', 'waybill_number', 'tracking_url', 'tracking_status', 'notes'])]
class OrderSellerTracking extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }
}
