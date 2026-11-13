<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum GeneralLedgerEntryType: string
{
    case RECEIVABLE = 'receivable';
    case REVENUE = 'revenue';
    case TAX = 'tax';
    case DISCOUNT = 'discount';
    case COGS = 'cogs';
    case INVENTORY = 'inventory';
    case PAYABLE = 'payable';
}
