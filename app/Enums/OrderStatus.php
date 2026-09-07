<?php

namespace App\Enums;

/**
 * Customer order lifecycle (spec §14.2).
 *
 * pending_payment → paid → processing → shipped → completed
 *                                   ↘ cancelled / expired
 *
 * TBC (spec §24 item 14 + TBC refunded): `refunded` is not included until
 * admin confirms. Payment-driven transitions (paid/processing) land in
 * Task 8 with PaymentService; only pending_payment/expired are mutated in
 * Task 7 (creation + ExpirePendingOrder job).
 */
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
