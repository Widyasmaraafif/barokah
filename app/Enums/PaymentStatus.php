<?php

namespace App\Enums;

/**
 * PayNet payment intent lifecycle (spec §14.2/§15).
 *
 * pending → paid / failed / expired / cancelled. Rows are created in Task 8
 * with the payments table; Task 7 only defines the enum so order status
 * sync follows the spec vocabulary. Payment `paid` triggers order `paid`
 * (Task 8); failure keeps the order in pending_payment until retry/expiry.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
