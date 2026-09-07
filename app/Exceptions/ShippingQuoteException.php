<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Shipping quote failure (spec §8.6/§11.6).
 *
 * Thrown when a shipping provider cannot produce a quote (missing
 * TBC_SHIPPING_* configuration, provider unreachable, or unexpected
 * response shape). Controllers map this to a 500 JSON response without
 * leaking server-side credentials.
 */
class ShippingQuoteException extends RuntimeException {}
