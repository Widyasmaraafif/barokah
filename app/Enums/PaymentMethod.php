<?php

namespace App\Enums;

/**
 * Payment methods: PayNet (FPX + DuitNow) plus manual bank transfer and
 * static QR code paid off-app and verified by admin.
 */
enum PaymentMethod: string
{
    case Fpx = 'fpx';
    case DuitNow = 'duitnow';
    case BankTransfer = 'bank_transfer';
    case QrCode = 'qr_code';

    public function isManual(): bool
    {
        return in_array($this, [self::BankTransfer, self::QrCode], true);
    }

    public function isPayNet(): bool
    {
        return in_array($this, [self::Fpx, self::DuitNow], true);
    }
}
