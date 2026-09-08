<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Minimal order model per spec §10.2/§14. Status enums and payment
 * handling land in Task 7; checkout creation lands in Task 6.
 *
 * @property int $id
 * @property string $order_number
 * @property int|null $user_id
 * @property string $customer_name
 * @property string $customer_address
 * @property string $customer_state
 * @property string|null $customer_city
 * @property string $customer_post_code
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property string $currency_code
 * @property string $subtotal
 * @property string $shipping_fee
 * @property string $total
 * @property OrderStatus $status
 * @property string $shipping_method
 * @property string|null $shipping_provider
 * @property string|null $notes
 * @property Carbon|null $expired_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'order_number',
    'user_id',
    'customer_name',
    'customer_address',
    'customer_state',
    'customer_city',
    'customer_post_code',
    'customer_phone',
    'customer_email',
    'currency_code',
    'subtotal',
    'shipping_fee',
    'total',
    'status',
    'shipping_method',
    'shipping_provider',
    'notes',
    'expired_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
            'expired_at' => 'datetime',
        ];
    }

    /**
     * Orders still awaiting PayNet settlement (spec §14.2).
     */
    #[Scope]
    protected function pendingPayment(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PendingPayment);
    }

    /**
     * Whether the pending order has passed its checkout expiry.
     */
    public function isExpired(): bool
    {
        if ($this->status !== OrderStatus::PendingPayment) {
            return false;
        }

        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    /**
     * Whether the pending order can still be paid.
     */
    public function isPayable(): bool
    {
        return $this->status === OrderStatus::PendingPayment && ! $this->isExpired();
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
