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

use App\Enums\PurchaseOrderType;
use App\Models\Purchase\PurchaseOrder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A purchase order: goods ordered from a supplier.
 *
 * What is ordered is carried by {@see PurchaseOrderLineData}. This carries what is
 * true of the order itself: who it is with, when it was placed, the terms it was
 * placed on, and what it became.
 *
 * The terms and the due date are separate rather than one derived from the other,
 * because a buyer may fix either independently. Deriving the due date from the
 * order date every time it is read would silently change it if the terms were
 * later edited, and the date the supplier is owed by is a commitment rather than a
 * calculation.
 *
 * It is built two ways and both are plain from():
 *
 *   PurchaseOrderData::from($request->validated())   a form, to raise one
 *   PurchaseOrderData::fromModel($order)              a saved one, to read it
 *
 * The properties the first of those cannot supply are optional, because an order
 * being raised has no approver, no invoice number and no key yet. It is filled in
 * by whoever creates the row, and Data::from leaves it null rather than inventing
 * a value.
 */
class PurchaseOrderData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('document_number')]
        public ?string $documentNumber = null,

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('contact_id')]
        public ?string $contactId = null,

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('contact_name')]
        public ?string $contactName = null,

        #[MapName('purchase_type')]
        public ?PurchaseOrderType $purchaseType = null,

        #[MapName('supplier_reference_number')]
        public ?string $supplierReferenceNumber = null,

        #[MapName('supplier_sales_order_number')]
        public ?string $supplierSalesOrderNumber = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('expected_date')]
        public ?CarbonImmutable $expectedDate = null,

        #[MapName('credit_term_days')]
        public ?int $creditTermDays = null,

        #[MapName('due_date')]
        public ?CarbonImmutable $dueDate = null,

        #[MapName('is_partial_received')]
        public bool $isPartialReceived = false,

        #[MapName('is_received')]
        public bool $isReceived = false,

        #[MapName('subtotal')]
        public ?string $subtotal = null,

        #[MapName('tax_amount')]
        public ?string $taxAmount = null,

        #[MapName('total_amount')]
        public ?string $totalAmount = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('approved_by')]
        public ?string $approvedBy = null,

        #[MapName('approved_at')]
        public ?CarbonImmutable $approvedAt = null,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt = null,

        #[MapName('attachment')]
        public mixed $attachment = null,
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
    public static function fromModel(PurchaseOrder $model): self
    {
        return new self(
            id: $model->id,
            documentNumber: $model->document_number,
            companyId: $model->company_id,
            contactId: $model->contact_id,
            companyName: $model->company_name,
            contactName: $model->contact_name,
            purchaseType: $model->purchase_type,
            supplierReferenceNumber: $model->supplier_reference_number,
            supplierSalesOrderNumber: $model->supplier_sales_order_number,
            date: self::moment($model->date),
            expectedDate: self::moment($model->expected_date),
            creditTermDays: $model->credit_term_days,
            dueDate: self::moment($model->due_date),
            isPartialReceived: (bool) $model->is_partial_received,
            isReceived: (bool) $model->is_received,
            subtotal: $model->subtotal,
            taxAmount: $model->tax_amount,
            totalAmount: $model->total_amount,
            notes: $model->notes,
            approvedBy: $model->approved_by,
            approvedAt: self::moment($model->approved_at),
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a purchase order is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Creating from the full row
     * would insert null for approved_by and every other column the model fills in
     * for itself, and the order would be written already unattributed.
     *
     * So the creation columns are named one by one, and the columns the model owns
     * are simply absent from the array. What is left out is not forgotten; it is
     * the part that had not happened yet when this document was raised.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'company_id' => $this->companyId,
            'contact_id' => $this->contactId,
            'document_number' => $this->documentNumber,
            'purchase_type' => $this->purchaseType?->value,
            'supplier_reference_number' => $this->supplierReferenceNumber,
            'supplier_sales_order_number' => $this->supplierSalesOrderNumber,
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'date' => $this->date?->format('Y-m-d'),
            'expected_date' => $this->expectedDate?->format('Y-m-d'),
            'credit_term_days' => $this->creditTermDays,
            'due_date' => $this->dueDate?->format('Y-m-d'),
            'is_partial_received' => $this->isPartialReceived,
            'is_received' => $this->isReceived,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'notes' => $this->notes,
        ];
    }

    /**
     * The row as the purchase_orders table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->documentNumber,
            'company_id' => $this->companyId,
            'contact_id' => $this->contactId,
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'purchase_type' => $this->purchaseType?->value,
            'supplier_reference_number' => $this->supplierReferenceNumber,
            'supplier_sales_order_number' => $this->supplierSalesOrderNumber,
            'date' => $this->date?->format('Y-m-d'),
            'expected_date' => $this->expectedDate?->format('Y-m-d'),
            'credit_term_days' => $this->creditTermDays,
            'due_date' => $this->dueDate?->format('Y-m-d'),
            'is_partial_received' => $this->isPartialReceived,
            'is_received' => $this->isReceived,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'notes' => $this->notes,
            'approved_by' => $this->approvedBy,
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
