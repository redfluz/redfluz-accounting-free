<?php

namespace App\Enums\Purchase;

enum AcquisitionType: string
{
    case STOCK = 'stock';
    case EXPENSE = 'expense';
    case FIXED_ASSET = 'fixed_asset';
}
