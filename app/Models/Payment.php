<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * PayNet payment intent for one order (spec §10.2/§15.3).
 *
 * Provider payloads are server-only and never serialized to the frontend.
 *
 * @property int $id
 * @property int $order_id
 * @property string $payment_gateway
 * @property PaymentMethod $payment_method
 * @property string $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property string|null $transaction_id
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $callback_payload
 * @property string|null $proof_path
 * @property Carbon|null $proof_uploaded_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'order_id',
    'payment_gateway',
    'payment_method',
    'amount',
    'currency',
    'status',
    'transaction_id',
    'payload',
    'callback_payload',
    'proof_path',
    'proof_uploaded_at',
    'paid_at',
    'failed_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'payload' => 'array',
            'callback_payload' => 'array',
            'proof_uploaded_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Public URL for the uploaded proof image (null when not uploaded).
     */
    public function proofUrl(): ?string
    {
        if ($this->proof_path === null || $this->proof_path === '') {
            return null;
        }

        if (str_starts_with($this->proof_path, 'http')) {
            return $this->proof_path;
        }

        return Storage::disk('public')->url($this->proof_path);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            PaymentStatus::Paid,
            PaymentStatus::Failed,
            PaymentStatus::Expired,
            PaymentStatus::Cancelled,
        ], true);
    }
}
