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

namespace App\Models\Inventory;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin Builder<ProductAttachment>
 *
 * @property string $id
 * @property string $product_id
 * @property string $file_path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int $file_size
 * @property string|null $uploaded_by
 * @property string|null $uploaded_by_name
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Product $product
 *
 * @noinspection PhpUnused
 */
#[Table('product_attachments', key: 'id')]
#[Fillable([
    'product_id',
    'file_path',
    'original_name',
    'mime_type',
    'file_size',
    'uploaded_by',
    'uploaded_by_name',
])]
class ProductAttachment extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the product that owns this image.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'string',
            'file_path' => 'string',
            'original_name' => 'string',
            'mime_type' => 'string',
            'file_size' => 'integer',
            'uploaded_by' => 'string',
            'uploaded_by_name' => 'string',
        ];
    }
}
