<?php

namespace App\Enums;

/**
 * PayNet payment methods (spec §3/§5: FPX + DuitNow only).
 */
enum PaymentMethod: string
{
    case Fpx = 'fpx';
    case DuitNow = 'duitnow';
}
