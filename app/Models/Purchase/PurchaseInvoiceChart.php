<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace App\Models\Purchase;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder<PurchaseInvoiceChart>
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $payable_id
 * @property string|null $inventory_id
 * @property string|null $tax_id
 * @property string|null $discount_id
 * @property string|null $description
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @noinspection PhpUnused
 */
#[Table('purchase_invoice_charts', key: 'id')]
#[Fillable([
    'company_id',
    'payable_id',
    'inventory_id',
    'tax_id',
    'discount_id',
    'description',
])]
class PurchaseInvoiceChart extends Model
{
    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'company_id' => 'string',
            'payable_id' => 'string',
            'inventory_id' => 'string',
            'tax_id' => 'string',
            'discount_id' => 'string',
            'description' => 'string',
        ];
    }
}
