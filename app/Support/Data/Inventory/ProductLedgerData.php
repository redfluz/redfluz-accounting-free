<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

declare(strict_types=1);

namespace App\Support\Data\Inventory;

use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Models\Inventory\ProductLedger;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One movement of stock: what moved, which way, and why.

    A movement is recorded rather than a balance kept on the product, so that the
    stock figure is always the sum of what actually happened and can be traced back
    to the document that caused it. A balance column would be faster to read and
    would quietly disagree with the movements the moment one was corrected.

    The ledger rows it points at name where the accounting side of the same
    movement was posted, which is what connects the stock figure to the money.
 */
class ProductLedgerData extends Data
{
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('product_id')]
        public ?string $productId,

        #[MapName('stock_location_id')]
        public ?string $stockLocationId,

        #[MapName('stock_location_name')]
        public ?string $stockLocationName,

        #[MapName('company_id')]
        public ?string $companyId,

        #[MapName('source_entry_id')]
        public ?string $sourceEntryId,

        #[MapName('cogs_ledger_id')]
        public ?string $cogsLedgerId,

        #[MapName('inventory_ledger_id')]
        public ?string $inventoryLedgerId,

        #[MapName('variance_ledger_id')]
        public ?string $varianceLedgerId,

        #[MapName('product_sku')]
        public ?string $productSku,

        #[MapName('product_name')]
        public ?string $productName,

        #[MapName('company_name')]
        public ?string $companyName,

        #[MapName('document_number')]
        public ?string $documentNumber,

        #[MapName('reference_number')]
        public ?string $referenceNumber,

        #[MapName('source_type')]
        public ProductMovementSource $sourceType,

        #[MapName('direction')]
        public ProductMovementDirection $direction,

        #[MapName('stock_bucket')]
        public ProductStockBucket $stockBucket,

        #[MapName('date')]
        public CarbonImmutable $date,

        #[MapName('quantity')]
        public string $quantity,

        #[MapName('unit_cost')]
        public ?string $unitCost,

        #[MapName('total_cost')]
        public ?string $totalCost,

        #[MapName('status')]
        public ProductMovementStatus $status,

        #[MapName('activated_at')]
        public ?CarbonImmutable $activatedAt,

        #[MapName('notes')]
        public ?string $notes,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt,

    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * A model hands back whatever Carbon its cast produced; this object holds one
     * concrete type so that a form and a model produce the same thing. The value
     * is the same either way, and only its class differs.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     */
    public static function fromModel(ProductLedger $model): self
    {
        return new self(
            id: $model->id,
            productId: $model->product_id,
            stockLocationId: $model->stock_location_id,
            stockLocationName: $model->stock_location_name,
            companyId: $model->company_id,
            sourceEntryId: $model->source_entry_id,
            cogsLedgerId: $model->cogs_ledger_id,
            inventoryLedgerId: $model->inventory_ledger_id,
            varianceLedgerId: $model->variance_ledger_id,
            productSku: $model->product_sku,
            productName: $model->product_name,
            companyName: $model->company_name,
            documentNumber: $model->document_number,
            referenceNumber: $model->reference_number,
            sourceType: $model->source_type,
            direction: $model->direction,
            stockBucket: $model->stock_bucket,
            date: CarbonImmutable::instance($model->date),
            quantity: $model->quantity,
            unitCost: $model->unit_cost,
            totalCost: $model->total_cost,
            status: $model->status,
            activatedAt: self::moment($model->activated_at),
            notes: $model->notes,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The row as the product_ledgers table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'source_type' => $this->sourceType->value,
            'direction' => $this->direction->value,
            'stock_bucket' => $this->stockBucket->value,
            'date' => $this->date->format('Y-m-d'),
            'status' => $this->status->value,
            'activated_at' => $this->activatedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
