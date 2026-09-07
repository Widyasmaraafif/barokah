<?php

namespace App\Enums;

enum SellerStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Suspended = 'suspended';
}
