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

namespace App\Support\Data\Sales;

use App\Enums\SalesOrderStatus;
use App\Models\Sales\SalesOrder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A sales order: a confirmed commitment to supply.
 *
 * What a sales order commits to is carried by {@see SalesOrderLineData}. This
 * carries only what is true of the order as a whole: who it is for, when it was
 * placed, when it is expected, what it came from, and where it has got to.
 *
 * The totals are here rather than on the lines because they are the order's own
 * figures and are what a reader of the order asks for first. The lines carry the
 * amounts that produce them.
 *
 * It is built two ways and both are plain from():
 *
 *   SalesOrderData::from($request->validated())   a form, to raise one
 *   SalesOrderData::fromModel($order)              a saved one, to read it
 *
 * The properties the first of those cannot supply are optional, because an order
 * being raised has no number, no totals and no approval yet.
 */
class SalesOrderData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        // ---- what the form supplies ----

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('contact_id')]
        public ?string $contactId = null,

        #[MapName('customer_reference_number')]
        public ?string $customerReferenceNumber = null,

        #[MapName('order_date')]
        public ?CarbonImmutable $orderDate = null,

        #[MapName('expected_delivery_date')]
        public ?CarbonImmutable $expectedDeliveryDate = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('attachment')]
        public mixed $attachment = null,

        // ---- what the caller supplies ----

        #[MapName('document_number')]
        public ?string $documentNumber = null,

        // ---- what the form cannot supply ----

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('contact_name')]
        public ?string $contactName = null,

        #[MapName('quotation_id')]
        public ?string $quotationId = null,

        #[MapName('status')]
        public ?SalesOrderStatus $status = null,

        #[MapName('subtotal')]
        public ?string $subtotal = null,

        #[MapName('total_amount')]
        public ?string $totalAmount = null,

        #[MapName('attachment_path')]
        public ?string $attachmentPath = null,

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
    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * @param  CarbonInterface|null  $value  The moment to normalise.
     * @return CarbonImmutable|null The moment as an immutable, or null.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * The columns a sales order is created from, and no others.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->documentNumber,
            'company_id' => $this->companyId,
            'contact_id' => $this->contactId,
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'quotation_id' => $this->quotationId,
            'customer_reference_number' => $this->customerReferenceNumber,
            'order_date' => $this->orderDate?->format('Y-m-d'),
            'expected_delivery_date' => $this->expectedDeliveryDate?->format('Y-m-d'),
            'status' => $this->status->value ?? SalesOrderStatus::DRAFT->value,
            'subtotal' => $this->subtotal,
            'total_amount' => $this->totalAmount,
            'notes' => $this->notes,
            'attachment_path' => $this->attachmentPath,
        ];
    }

    /**
     * The row as the sales_orders table wants it.
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
            'quotation_id' => $this->quotationId,

            'customer_reference_number' => $this->customerReferenceNumber,
            'order_date' => $this->orderDate?->format('Y-m-d'),
            'expected_delivery_date' => $this->expectedDeliveryDate?->format('Y-m-d'),
            'status' => $this->status?->value,
            'subtotal' => $this->subtotal,
            'total_amount' => $this->totalAmount,
            'attachment_path' => $this->attachmentPath,
            'notes' => $this->notes,
            'approved_by' => $this->approvedBy,
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the
     * model holds, and the two are written in different formats. Taking the
     * model's attributes instead means the cast is what is mapped, so a column
     * cast to a date, an enum or a decimal is mapped as the type it actually is.
     */
    public static function fromModel(SalesOrder $model): self
    {
        return new self(
            id: $model->id,
            companyId: $model->company_id,
            contactId: $model->contact_id,
            customerReferenceNumber: $model->customer_reference_number,
            orderDate: self::moment($model->order_date),
            expectedDeliveryDate: self::moment($model->expected_delivery_date),
            notes: $model->notes,
            documentNumber: $model->document_number,
            companyName: $model->company_name,
            contactName: $model->contact_name,
            quotationId: $model->quotation_id,
            status: $model->status,
            subtotal: $model->subtotal,
            totalAmount: $model->total_amount,
            attachmentPath: $model->attachment_path,
            approvedBy: $model->approved_by,
            approvedAt: self::moment($model->approved_at),
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }
}
