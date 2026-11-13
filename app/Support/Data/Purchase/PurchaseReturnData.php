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

namespace App\Support\Data\Purchase;

use App\Models\Inventory\ProductLedger;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A purchase return: stock returned to a supplier.
 *
 * It is built two ways and both are plain from():
 *
 *   PurchaseReturnData::from($request->validated())   a form, to raise one
 *   PurchaseReturnData::fromModel($return)             a saved one, to read it
 *
 * The properties the first of those cannot supply are optional, because a return
 * being raised has no key yet. It is filled in by whoever creates the row, and
 * Data::from leaves it null rather than inventing a value.
 */
class PurchaseReturnData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('source_entry_id')]
        public ?string $sourceEntryId = null,

        #[MapName('quantity')]
        public ?string $quantity = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('document_number')]
        public ?string $documentNumber = null,

        #[MapName('reference_number')]
        public ?string $referenceNumber = null,

        #[MapName('product_id')]
        public ?string $productId = null,

        #[MapName('product_name')]
        public ?string $productName = null,

        #[MapName('product_sku')]
        public ?string $productSku = null,

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('stock_location_id')]
        public ?string $stockLocationId = null,

        #[MapName('stock_location_name')]
        public ?string $stockLocationName = null,

        #[MapName('unit_cost')]
        public ?string $unitCost = null,

        #[MapName('total_cost')]
        public ?string $totalCost = null,

        #[MapName('status')]
        public ?string $status = null,

        #[MapName('activated_at')]
        public ?CarbonImmutable $activatedAt = null,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt = null,
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
     * date is the string the database stores rather than the date object the
     * model holds, and the two are written in different formats. Taking the
     * model's attributes instead means the cast is what is mapped.
     */
    public static function fromModel(ProductLedger $model): self
    {
        return new self(
            id: $model->id,
            sourceEntryId: $model->source_entry_id,
            quantity: $model->quantity,
            date: self::moment($model->date),
            notes: $model->notes,
            documentNumber: $model->document_number,
            referenceNumber: $model->reference_number,
            productId: $model->product_id,
            productName: $model->product_name,
            productSku: $model->product_sku,
            companyId: $model->company_id,
            companyName: $model->company_name,
            stockLocationId: $model->stock_location_id,
            stockLocationName: $model->stock_location_name,
            unitCost: $model->unit_cost,
            totalCost: $model->total_cost,
            status: $model->status->value,
            activatedAt: self::moment($model->activated_at),
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a purchase return is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Creating from the full row
     * would insert null for every column the model fills in for itself, and the
     * return would be written already unattributed.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'source_entry_id' => $this->sourceEntryId,
            'quantity' => $this->quantity,
            'date' => $this->date?->format('Y-m-d'),
            'notes' => $this->notes,
        ];
    }

    /**
     * The row as the product_ledgers table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'source_entry_id' => $this->sourceEntryId,
            'quantity' => $this->quantity,
            'date' => $this->date?->format('Y-m-d'),
            'notes' => $this->notes,
            'document_number' => $this->documentNumber,
            'reference_number' => $this->referenceNumber,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'stock_location_id' => $this->stockLocationId,
            'stock_location_name' => $this->stockLocationName,
            'unit_cost' => $this->unitCost,
            'total_cost' => $this->totalCost,
            'status' => $this->status,
            'activated_at' => $this->activatedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
